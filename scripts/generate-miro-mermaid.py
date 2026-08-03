#!/usr/bin/env python3
"""Generate exact Mermaid flowchart from Miro board JSON extract."""
import json
import math
import re
import sys
from collections import defaultdict
from html import unescape

MIRO_JSON = sys.argv[1] if len(sys.argv) > 1 else (
    '/Users/kamran/.cursor/browser-logs/cdp-response-Runtime.evaluate-2026-07-31T15-47-04-982Z.json'
)


def strip_html(s):
    s = re.sub(r'<[^>]+>', ' ', s or '')
    s = unescape(s)
    return re.sub(r'\s+', ' ', s).strip()


def endpoint_id(ep):
    if isinstance(ep, dict):
        return ep.get('id') or ep.get('item')
    return ep


def load_items(path):
    with open(path) as f:
        data = json.load(f)
    val = data
    while isinstance(val, dict):
        if 'value' in val and isinstance(val['value'], list):
            return val['value']
        if 'result' in val:
            val = val['result']
        else:
            break
    return val


def is_decision(text, shape):
    if shape == 'rhombus':
        return True
    return text in {
        'WHAT DO YOU KNOW ABOUT LEAD', 'CALL RECEIVED BY USER', 'LEAD QUALIFIER',
        'CUstomer Want to talk to Provider first before service', 'CUSTOMER WANT SERVICE ?',
        'Provide ready for Service SERVICE ?', 'ANY ONE REPLIED IN 10 MIns',
        'ANY PROVIDER REPLIED AND IS READY FOR DISCUSSION',
        'ANY PROVIDER REPLIED AND IS READY FOR SERvice', 'GOT NEXT AVAILIABILITY OF PROVIDERS',
        'Ask Available for Brief Call', 'IS available now', 'Documents Shared',
    }


def is_hex_end(text):
    return (
        text in {
            'INVALID LEAD', 'FUTURE CUSTOMER LEAD', 'CANEL THE LEAD',
            'CUSTOMER DENIES TAKING SERVICE AT ALL', 'CANCEL THE LEAD and close its chat and lead',
            'STEP Four Add in PANEL and add in related whatsapp groups',
        }
        or text.startswith('Take 100 Rupees Booking confirmation')
    )


def is_lead_type(text):
    return text in {'UNKNOWN LEAD', 'CUSTOMER LEAD', 'PROVIDER LEAD'}


def is_message(text):
    u = text.upper()
    return 'SEND WHATSAPP' in u or 'SEND MESSAGE TO PROVIDER' in u or 'SEND MESSAGE TO Customer' in text


def fmt_node(mid, text):
    t = text.replace('"', "'")
    if is_hex_end(text):
        return f'{mid}@{{{{"{t}"}}}}'
    if text in {
        'WHAT DO YOU KNOW ABOUT LEAD', 'CALL RECEIVED BY USER', 'LEAD QUALIFIER',
        'CUstomer Want to talk to Provider first before service', 'CUSTOMER WANT SERVICE ?',
        'Provide ready for Service SERVICE ?', 'ANY ONE REPLIED IN 10 MIns',
        'ANY PROVIDER REPLIED AND IS READY FOR DISCUSSION',
        'ANY PROVIDER REPLIED AND IS READY FOR SERvice', 'GOT NEXT AVAILIABILITY OF PROVIDERS',
        'Ask Available for Brief Call', 'IS available now', 'Documents Shared',
    }:
        return f'{mid}@{{{{"{t}"}}}}'
    if is_lead_type(text):
        return f'{mid}[["{t}"]]'
    if is_message(text):
        return f'{mid}(["{t}"])'
    if text in {'Social Media', 'Facebook', 'Instagram', 'Direct Calls', 'AI Chat', 'In Panel'}:
        return f'{mid}[{text}]'
    return f'{mid}["{t}"]'


# Map each Miro widget id -> stable mermaid id (matches existing chart conventions)
def build_id_map(nodes):
    mid = {}

    def one(text, mermaid_id):
        for iid, n in sorted(nodes.items(), key=lambda x: (x[1]['y'], x[1]['x'])):
            if n['text'] == text and iid not in mid:
                mid[iid] = mermaid_id
                return iid
        return None

    def nth(text, n, suffix):
        cands = sorted(
            [(iid, n['y'], n['x']) for iid, n in nodes.items() if n['text'] == text],
            key=lambda x: (x[1], x[2]),
        )
        if len(cands) >= n:
            mid[cands[n - 1][0]] = suffix

    # singles
    singles = {
        'Social Media': 'SM', 'Facebook': 'FB', 'Instagram': 'IG', 'Direct Calls': 'DC',
        'Call on 8899881555': 'P1', 'Call on 8899556555': 'P2', 'Call on 889918155': 'P3',
        'Call on 9103076946': 'P4', 'AI Chat': 'AI', 'Website Booking Customer': 'WBC',
        'Website Booking Provider': 'WBP', 'In Panel': 'IP', 'Custom App Request': 'CAR',
        'Direct App On-Boarding': 'DAO', 'Direct App Booking': 'DAB',
        'LEAD AUTO CREATED IN ADMIN PANEL': 'AUTO',
        'Collect all Possible detail from customer and create a new lead': 'MANUAL',
        'WHAT DO YOU KNOW ABOUT LEAD': 'KNOW',
        'NO IDEA ABOUT THE LEAD Customer only send normal chat message of Just called us but didnot give proper information in order to classify the lead': 'NOIDEA',
        'UNKNOWN LEAD': 'UNK',
        'We need to call the user and collect details what user wants from Panun Kaergar': 'CALLUSER',
        'After sending the message to User Update the leads with Initial Remarks and Set a new Folloup date for next day for that lead': 'UPDUNK',
        'CALL RECEIVED BY USER': 'PICKED', 'LEAD QUALIFIER': 'QUAL',
        'CALL THE CUSTOMER AND GET DETAILS OF SERVICE THEY WANT AND CONFORM WITH THEM': 'CALLC',
        'CUstomer Want to talk to Provider first before service': 'TALK',
        'BEFORE CONFRENECE CALL MAKE SURE TO INFORM CUSTOMER that if you face any issue with this provider in terms of pricing and quality please do inform us we will find a best service provider for you. We have multiple service providers registered with us. so that we keep room for further discussion with customer incase provider didnt gave genuine pricing': 'BEFORE',
        'CUSTOMER WANT SERVICE ?': 'WANTS', 'Provide ready for Service SERVICE ?': 'PRREADY',
        'CUSTOMER WANT TO DISCUSS WITH SOMEONE ELSE OR SAYING WILL INFORM LATER ASk customer whats the they want to discuss try to convince they and make them book': 'DISCUSS',
        'CUSTOMER DENIES TAKING SERVICE AT ALL': 'DENY',
        'CANCEL THE LEAD and close its chat and lead': 'CANCEL',
        'SETUP A CONFRENCE CALL WITH PROVIDER AND CUSTOMER': 'CONF',
        'Ask Available for Brief Call': 'ASK', 'IS available now': 'NOW', 'Documents Shared': 'DOCS',
        'STEP ONE Brief Call GIve provider brief information about panun kaergar, comission info GET DETAILS OF SERVICE THEY Provider, area of service AND ASK FOR D': 'S1',
        'STEP TWO SEND THEM AGREEMENT AND ASK FOR DOCUMENTS Ask provider for date time by when will they submit form and documsnend': 'S2',
        'STEP THree FInal Call explain work , groups , process': 'S3',
        'STEP Four Add in PANEL and add in related whatsapp groups': 'S4',
        'Schedule a folloup data time for date time by': 'PFU1',
        'User asks for services we do not provide or is asking for service in no servicable area Make sure to fill proper details what servie they were looking or where or if non response add proper details': 'NOSVC',
        'INform them about panun kaergar and try to do outbound sales ask them if you ever need service or any relative needs refer PAnun KAergar to them': 'OUTBOUND',
    }
    for text, m in singles.items():
        one(text, m)

    # duplicates by y-order
    nth('CUSTOMER LEAD', 1, 'CUS')
    nth('CUSTOMER LEAD', 2, 'CUS2')
    nth('PROVIDER LEAD', 1, 'PRO')
    nth('PROVIDER LEAD', 2, 'PRO2')
    nth('INVALID LEAD', 1, 'INV')
    nth('INVALID LEAD', 2, 'INV2')
    nth('FUTURE CUSTOMER LEAD', 1, 'FUT')
    nth('FUTURE CUSTOMER LEAD', 2, 'FUT2')
    nth('CANEL THE LEAD', 1, 'CAN1')
    nth('CANEL THE LEAD', 2, 'CAN2')
    nth('ANY ONE REPLIED IN 10 MIns', 1, 'TEN_D')
    nth('ANY ONE REPLIED IN 10 MIns', 2, 'TEN_S')
    nth('CALL ALL NEARBY PROVIDERS AND CHECK AVAILIBILITY for customer date and next availibility', 1, 'CALLALL_D')
    nth('CALL ALL NEARBY PROVIDERS AND CHECK AVAILIBILITY for customer date and next availibility', 2, 'CALLALL_S')
    nth('GOT NEXT AVAILIABILITY OF PROVIDERS', 1, 'AVAIL_D1')
    nth('GOT NEXT AVAILIABILITY OF PROVIDERS', 2, 'AVAIL_D2')
    nth('GOT NEXT AVAILIABILITY OF PROVIDERS', 3, 'AVAIL_S1')
    nth('GOT NEXT AVAILIABILITY OF PROVIDERS', 4, 'AVAIL_S2')
    nth('Call Customer and infrom him Currently our provider as busy ask for more time and then tell him we will update you', 1, 'BUSY_D')
    nth('Call Customer and infrom him Currently our provider as busy ask for more time and then tell him we will update you', 2, 'BUSY_S')
    nth('Call Customer and infrom him our provider are available at these time and if you are ok we can schedule for that data', 1, 'OFFER_D')
    nth('Call Customer and infrom him our provider are available at these time and if you are ok we can schedule for that data', 2, 'OFFER_S')
    nth('CUstomer Okay to for rescheduling', 1, 'OK_D')
    nth('CUstomer Okay to for rescheduling', 2, 'OK_S')
    nth('CUstomer Not okay with reschdulinhg', 1, 'NOTOK_D')
    nth('Customer Not okay with reschdulinhg', 1, 'NOTOK_S')
    nth('Check One more time if any provider is willing to go on customer requested dates', 1, 'RECHECK_D')
    nth('Check One more time if any provider is willing to go on customer requested dates', 2, 'RECHECK_S')
    nth('SETUP A Followup UP DATE time WITH customer and check later', 1, 'FU2_D')
    nth('SETUP A Followup UP DATE time WITH customer and check later', 2, 'FU2_S')
    nth('SETUP A Followup UP DATE time WITH customer and ask other dates if customer is ready and check later', 1, 'FU1')
    nth('Take 100 Rupees Booking confirmation amount and create the booking and send confirmation messages to both provider and customer and setup folloup up dates', 1, 'BOOK_D')
    nth('Take 100 Rupees Booking confirmation amount and create the booking and send confirmation messages to both provider and customer and setup folloup up dates', 2, 'BOOK_S')
    nth('Schedule a folloup data time for that leads and call that provider on that time', 1, 'PFU2')
    nth('Schedule a new folloup data time for that leads and call that provider on that time', 1, 'PFU3')

    # messages - by y order
    msg_texts = [
        ('SEND WHATSAPP MESSAGE TO USER', 'WA1'),
        ('SEND WHATSAPP MESSAGE TO USER', 'WA2'),
        ('SEND WHATSAPP MESSAGE TO USER', 'WA3'),
        ('SEND MESSAGE TO PROVIDER GROUP AND CHECK WHICH PROVIDER IS AVAILABLE FOR Discussion', 'PGDISC'),
        ('SEND MESSAGE TO PROVIDER GROUP AND CHECK WHICH PROVIDER IS AVAILABLE FOR TH', 'PGSVC'),
        ('SEND MESSAGE TO Customer and infrom him aout wht we discussed on call', 'MSG_D1'),
        ('SEND MESSAGE TO Customer and infrom him aout wht we discussed on call', 'MSG_D2'),
        ('SEND MESSAGE TO Customer and infrom him aout wht we discussed on call', 'MSG_S1'),
        ('SEND MESSAGE TO Customer and infrom him aout wht we discussed on call', 'MSG_S2'),
        ('After sending the message to User Update the leads make it as customer lead', 'UPD1S'),
        ('After sending the message to User Update the leads make it as customer lead', 'UPD1D'),
        ('ANY PROVIDER REPLIED AND IS READY FOR DISCUSSION', 'READYDISC'),
        ('ANY PROVIDER REPLIED AND IS READY FOR SERvice', 'READYSVC'),
    ]
    for prefix, mermaid_id in msg_texts:
        for iid, n in sorted(nodes.items(), key=lambda x: (x[1]['y'], x[1]['x'])):
            if iid in mid:
                continue
            if n['text'].startswith(prefix.split(' FOR TH')[0]) or (
                prefix.startswith('SEND WHATSAPP') and n['text'].startswith('SEND WHATSAPP')
            ):
                if mid.get(iid):
                    continue
                # assign by order for repeated prefixes
                pass
    # assign remaining messages manually by y
    wa = sorted(
        [(i, nodes[i]) for i in nodes if nodes[i]['text'].startswith('SEND WHATSAPP')],
        key=lambda x: (x[1]['y'], x[1]['x']),
    )
    for m, (iid, _) in zip(['WA1', 'WA2', 'WA3'], wa):
        mid[iid] = m
    upd = sorted(
        [(i, nodes[i]) for i in nodes if nodes[i]['text'].startswith('After sending the message to User Update the leads make it as customer')],
        key=lambda x: (x[1]['y'], x[1]['x']),
    )
    for m, (iid, _) in zip(['UPD1S', 'UPD1D'], upd):
        mid[iid] = m
    pg = sorted(
        [(i, nodes[i]) for i in nodes if 'SEND MESSAGE TO PROVIDER GROUP' in nodes[i]['text']],
        key=lambda x: (x[1]['y'], x[1]['x']),
    )
    for m, (iid, _) in zip(['PGDISC', 'PGSVC'], pg):
        mid[iid] = m
    msg = sorted(
        [(i, nodes[i]) for i in nodes if nodes[i]['text'].startswith('SEND MESSAGE TO Customer')],
        key=lambda x: (x[1]['y'], x[1]['x']),
    )
    for m, (iid, _) in zip(['MSG_D1', 'MSG_D2', 'MSG_S1', 'MSG_S2'], msg):
        mid[iid] = m
    one('ANY PROVIDER REPLIED AND IS READY FOR DISCUSSION', 'READYDISC')
    one('ANY PROVIDER REPLIED AND IS READY FOR SERvice', 'READYSVC')

    unmapped = [iid for iid in nodes if iid not in mid]
    if unmapped:
        for iid in unmapped:
            t = nodes[iid]['text']
            base = re.sub(r'[^A-Za-z0-9]', '', t)[:10].upper() or 'X'
            mid[iid] = f'U_{base}_{iid[-4:]}'
    return mid


def resolve_edge(s, e, nodes):
    ns, ne = nodes[s], nodes[e]
    if 'Direct Calls' in ne['text'] and 'Call on' in ns['text']:
        return None
    if 'Call on' in ns['text'] and 'Call on' in ne['text']:
        return None

    fr, to = s, e
    if ne['y'] < ns['y'] - 800:
        if is_decision(ne['text'], ne['shape']):
            fr, to = e, s
        elif ne['y'] + 500 < ns['y']:
            fr, to = e, s

    return fr, to


def nearest_label(fr, to, nodes, labels):
    mx = (nodes[fr]['x'] + nodes[to]['x']) / 2
    my = (nodes[fr]['y'] + nodes[to]['y']) / 2
    best, dist = '', 4500
    for lv in labels.values():
        d = math.hypot(lv['x'] - mx, lv['y'] - my)
        if d < dist:
            dist, best = d, lv['text']
    return best if dist <= 4000 else ''


def main():
    items = load_items(MIRO_JSON)
    nodes = {}
    labels = {}
    connectors = []
    for i in items:
        if i['type'] == 'connector':
            connectors.append(i)
        elif i['type'] == 'text':
            t = strip_html(i.get('content', ''))
            if t:
                labels[i['id']] = {'text': t, 'x': i['x'], 'y': i['y']}
        else:
            t = strip_html(i.get('content', ''))
            if t:
                nodes[i['id']] = {
                    'text': re.sub(r'\s+', ' ', t).strip(),
                    'x': i['x'], 'y': i['y'],
                    'shape': (i.get('shape') or '').lower(),
                }

    id_map = build_id_map(nodes)
    edges = {}

    for c in connectors:
        s = endpoint_id(c.get('start'))
        e = endpoint_id(c.get('end'))
        if s not in nodes or e not in nodes:
            continue
        resolved = resolve_edge(s, e, nodes)
        if not resolved:
            continue
        fr, to = resolved
        lb = nearest_label(fr, to, nodes, labels)
        key = (id_map[fr], id_map[to])
        if key not in edges or (lb and not edges[key]):
            edges[key] = lb

    # KNOW branches (miro stores some inverted)
    know = id_map[next(i for i, n in nodes.items() if n['text'] == 'WHAT DO YOU KNOW ABOUT LEAD')]
    forced = [
        (know, 'CUS', 'USER NEEDS SERVICE'),
        (know, 'PRO', 'USER Want to join as SERVICE Provider'),
        (know, 'NOIDEA', 'User has no idea about enquiry or wants service but in futre or saved number for future use'),
        (know, 'INV', 'INVALID'),
        (know, 'FUT', 'Future interest'),
        (know, 'NOSVC', 'No service / out of area'),
        (know, 'OUTBOUND', 'Outbound'),
        ('QUAL', 'CUS2', 'USER NEEDS SERVICE'),
        ('QUAL', 'PRO2', 'USER Want to join as SERVICE Provider'),
        ('QUAL', 'INV', 'INVALID'),
        ('QUAL', 'FUT', 'Future / saved for later'),
        ('QUAL', 'NOSVC', 'No service / out of area'),
        ('QUAL', 'OUTBOUND', 'Outbound'),
        ('NOSVC', 'INV2', ''),
        ('OUTBOUND', 'FUT2', ''),
    ]
    for a, b, lb in forced:
        edges[(a, b)] = lb

    ordered = sorted(nodes.items(), key=lambda x: (x[1]['y'], x[1]['x']))
    seen = set()
    lines = ['flowchart TB']
    for iid, n in ordered:
        m = id_map[iid]
        if m in seen:
            continue
        seen.add(m)
        lines.append('  ' + fmt_node(m, n['text']))

    for (a, b), lb in sorted(edges.items(), key=lambda x: x[0]):
        if lb:
            lb = lb.replace('"', "'")
            lines.append(f'  {a} -->|"{lb}"| {b}')
        else:
            lines.append(f'  {a} --> {b}')

    # styling (same as admin page)
    lines.extend([
        '',
        '  classDef pgEntry fill:#dbeafe,stroke:#2563eb,color:#1e3a8a,stroke-width:2px',
        '  classDef pgAction fill:#fff7ed,stroke:#ea580c,color:#7c2d12,stroke-width:2px',
        '  classDef pgMessage fill:#ecfdf5,stroke:#059669,color:#064e3b,stroke-width:2px',
        '  classDef pgInfo fill:#f5f3ff,stroke:#7c3aed,color:#4c1d95,stroke-width:2px',
        '  classDef pgDecision fill:#f8fafc,stroke:#64748b,color:#0f172a,stroke-width:2px',
        '  classDef pgLeadType fill:#ccfbf1,stroke:#0f766e,color:#134e4a,stroke-width:3px',
        '  classDef pgEnd fill:#ffe4e6,stroke:#e11d48,color:#881337,stroke-width:3px',
        '  classDef pgEndSuccess fill:#dcfce7,stroke:#16a34a,color:#14532d,stroke-width:3px',
        '',
        '  class SM,DC,FB,IG,P1,P2,P3,P4,AI,WBC,WBP,IP,CAR,DAO,DAB pgEntry',
        '  class AUTO,MANUAL,CALLUSER,CALLC,CALLALL_D,CALLALL_S,CONF,BUSY_D,BUSY_S,OFFER_D,OFFER_S,RECHECK_D,RECHECK_S,DISCUSS,FU1,FU2_D,FU2_S,OK_D,OK_S,NOTOK_D,NOTOK_S,S1,S2,S3,PFU1,PFU2,PFU3 pgAction',
        '  class WA1,WA2,WA3,PGDISC,PGSVC,MSG_D1,MSG_D2,MSG_S1,MSG_S2 pgMessage',
        '  class BEFORE,NOIDEA,NOSVC,OUTBOUND,UPDUNK,UPD1S,UPD1D pgInfo',
        '  class KNOW,PICKED,QUAL,TALK,TEN_D,TEN_S,READYDISC,READYSVC,AVAIL_D1,AVAIL_D2,AVAIL_S1,AVAIL_S2,WANTS,PRREADY,ASK,NOW,DOCS pgDecision',
        '  class UNK,CUS,PRO,CUS2,PRO2 pgLeadType',
        '  class INV,FUT,INV2,FUT2,CANCEL,CAN1,CAN2,DENY pgEnd',
        '  class BOOK_D,BOOK_S,S4 pgEndSuccess',
    ])

    out = '\n'.join(lines) + '\n'
    print(out)
    print(f'# nodes={len(seen)} edges={sum(1 for l in lines if "-->" in l)}', file=sys.stderr)


if __name__ == '__main__':
    main()

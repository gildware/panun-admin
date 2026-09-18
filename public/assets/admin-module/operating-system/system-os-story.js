window.PK_STORY = {
  overview: {
    lede: "Panun Kaergar is one home-services operating system. Growth creates what the market can buy. Operations converts demand into delivered work. Control keeps money, people and systems in hand — it does not sit inside the booking line.",
    treeHow: "Each white box is one desk. Click the name to open that module. The chips inside the box are the processes that desk runs."
  },
  groups: {
    growth: {
      title: "Growth",
      line: "Where and how the business grows.",
      explain: "Growth decides what is worth selling, where to sell it, how people hear about it, and which partners can bring extra demand. Growth does not convert an enquiry into a booking, and it does not send a provider to a home.",
      pageLine: "Open a module below to see what that desk does, what it must not do, and the processes it runs."
    },
    operations: {
      title: "Operations",
      line: "How demand becomes completed, billed, supported work.",
      explain: "Operations takes the enquiry, turns it into a confirmed booking, coordinates the job, deploys the provider network, supports the customer, bills the work, and checks quality. This is the line the customer actually feels.",
      pageLine: "Open a module below to see how that desk takes work from the last seat and hands a complete object to the next."
    },
    control: {
      title: "Control",
      line: "How money, people and systems stay in hand.",
      explain: "Control spans every function. Finance plans the business. HR runs employees. Technology runs systems and data. None of these three sit in the booking flow — they keep the flow possible.",
      pageLine: "These three desks support every Growth and Operations function. They are not steps in the booking."
    }
  },
  sections: {
    purpose: {
      why: "Why this function exists",
      how: "Read this first. If the purpose is fuzzy, every SOP later will fight every other SOP."
    },
    role: {
      why: "The seat that owns the work",
      how: "One named role reports to one place. A function without a seat becomes everybody's job and nobody's job."
    },
    responsibilities: {
      why: "The work areas this seat must cover",
      how: "Each letter is an owned patch. If an item sits in two letters, split it. If it sits in no letter, it will be dropped."
    },
    processes: {
      why: "How work actually moves inside this function",
      how: "A process is a repeating path with an outcome. Follow the arrows. The last box should be a handoff, a record, or a decision — not a meeting."
    },
    sops: {
      why: "The exact steps, in order",
      how: "Open only the SOP you need. Incomplete steps are defects. Do not skip a step to 'save time' — that is how the next function inherits a broken object."
    },
    daily: {
      why: "What this desk does every working day",
      how: "Daily work keeps the board current. If daily items slip, weekly reports become fiction."
    },
    weekly: {
      why: "The rhythm that keeps the function honest",
      how: "Weekly is for patterns, not panic. Demand, conversion, fulfilment and money should be readable in one sitting."
    },
    monthly: {
      why: "The review that changes the system",
      how: "Monthly is where opportunities, launches, quality themes and budget variance get decided — not rediscovered."
    },
    kpis: {
      why: "How we know the function is working",
      how: "A target without an owner is decoration. Read the measure, then ask which SOP produces it."
    },
    checklists: {
      why: "What must be true before work moves on",
      how: "A checklist is a gate, not a diary. If an item is unchecked, the object is not ready to hand off."
    },
    forms: {
      why: "Where the work is written down",
      how: "If it is not on a form or register, the next function cannot receive it. Memory is not a record."
    },
    reports: {
      why: "What management sees",
      how: "Reports are compressed truth. They should come from the same records the desk already keeps — not from a separate story."
    },
    handoffs: {
      why: "How this function joins the rest of the system",
      how: "Inbound is what this seat must be able to receive. Outbound is what it owes others. Incomplete objects are returned, not silently repaired."
    },
    escalation: {
      why: "When this seat cannot finish the work",
      how: "Escalation has a trigger, a route, an SLA and a record. A WhatsApp to a founder is not an escalation path."
    },
    improvement: {
      why: "How learning returns to the system",
      how: "Quality, complaints and demand signals must feed Growth and the owning Operations module. Otherwise the next cycle starts from a new idea in isolation."
    },
    statuses: {
      why: "The allowed states of the object",
      how: "Status is a control. If a card can sit in two statuses, the lifecycle is leaking."
    },
    lifecycles: {
      why: "The path from start to close",
      how: "Read left to right. Skipping a stage is how bookings, providers or settlements go missing."
    },
    requests: {
      why: "The kinds of work this desk accepts",
      how: "Different request types need different SOPs. Mixing them in one pile creates fake urgency."
    },
    commercial: {
      why: "The money rules this function must obey",
      how: "Commercial rules are not negotiable at the desk. If a case does not fit, escalate — do not invent a discount."
    },
    boundary: {
      why: "What this function does not own",
      how: "The fence is the design. If you do someone else's work 'just this once', the object will never be complete at the real owner."
    }
  },
  cycle: {
    explain: "The operating cycle is the customer-facing line. Research recommends. Marketing creates demand. Sales converts the enquiry. Customer Operations coordinates the booking. Provider Operations deploys the network. Customer Experience supports the person. Revenue settles the money. Quality checks the work. Learning then returns to Market Intelligence so the next growth cycle is evidence, not guesswork.",
    objects: "Watch the gold objects. Confirmed booking, service delivery, service completion and feedback are what actually travel. Control does not appear as a step here because it supports every step."
  },
  handoffsPage: {
    explain: "A handoff is a filled form one desk sends to the next. Every box on the form has a real example below. If a box is empty, the next desk sends it back. Do not quietly fill it in for them — that trains the last desk to stay sloppy.",
    rule: "Empty boxes come back. A WhatsApp shout is not a handoff.",
    good: "BK-4418: Farooq Ahmad, AC servicing, House 12 Rajbagh, 19 Sep 11:00–13:00, extra indoor units ×2, phone on the form.",
    bad: "\"Book that AC guy for Farooq tomorrow.\"",
    anatomy: [
      { title: "What", text: "The name of the form. Say it out loud: “this is a confirmed booking”, not “that AC thing”." },
      { title: "When", text: "The moment you send it — booking confirmed, job done, complaint logged." },
      { title: "The boxes", text: "Every field in the filled example. Missing one = send it back." },
      { title: "Who gets it", text: "Only this desk starts the next work. Others may read. They may not silently fix." }
    ],
    lanes: [
      {
        id: "offers",
        name: "Research & offers",
        tint: "growth",
        explain: "Research proves people want a job. Service Development writes the actual offer. Until these forms are filled, Marketing and Sales are guessing.",
        ids: ["h01", "h05", "h06", "h07", "h08"]
      },
      {
        id: "demand",
        name: "Markets & demand",
        tint: "growth",
        explain: "Where we work, and how an enquiry arrives. Do not advertise a place Operations cannot serve.",
        ids: ["h02", "h03", "h04", "h09", "h10", "h11", "h12", "h13", "h14"]
      },
      {
        id: "conversion",
        name: "Conversion",
        tint: "ops",
        explain: "Sales turns a complete enquiry into a confirmed booking, or closes it with a real reason. Outcomes go back so ads and partners can be judged.",
        ids: ["h16", "h17", "h18"]
      },
      {
        id: "fulfilment",
        name: "Fulfilment",
        tint: "ops",
        explain: "Customer Operations owns the booking. Provider Operations owns the technician. Customer Experience only speaks facts written on the form.",
        ids: ["h19", "h20", "h21", "h22"]
      },
      {
        id: "settlement",
        name: "Settlement",
        tint: "ok",
        explain: "Money starts when the job is done. Revenue bills. Customer Experience may only say a money status Revenue has written.",
        ids: ["h23", "h26", "h34", "h35"]
      },
      {
        id: "quality",
        name: "Quality & learning",
        tint: "wait",
        explain: "Broken jobs, complaints, and repeating themes. Quality acts on people and processes, then sends patterns back so the next cycle is evidence.",
        ids: ["h24", "h25", "h27", "h28", "h29", "h30", "h31", "h32", "h33"]
      },
      {
        id: "control",
        name: "Control span",
        tint: "control",
        explain: "Budget, staff access, and systems. These forms support every desk. They are not market research and they are not a booking.",
        ids: ["h15", "h36", "h37", "h38", "h39", "h40", "h41", "h42", "h43", "h44"]
      }
    ],
    objects: {
      h01: {
        meaning: "A service-shaped hypothesis that survived demand, competition and a feasibility check.",
        why: "Offer & Service Development must not invent SKUs from a hunch. They design only what MI has validated.",
        completeWhen: "OPP-ID, hypothesis, demand evidence IDs, competitor note, feasibility, priority, Validation Report, status Validated.",
        returnIf: "No numbers, no OPP-ID, or ‘Validated’ without a report.",
        receiverDoes: "OSD opens a service opportunity and starts definition. They do not run ads.",
        example: "OPP-019: AC gas refill as a standalone bookable service in Srinagar City. 28 unmet asks in W35–W37. CMP-07 already sells it."
      },
      h02: {
        meaning: "Demand evidence that a named catchment is worth Expansion looking at — not a launch order.",
        why: "Market Expansion decides entry. MI only proves people are asking from a place we do not serve well.",
        completeWhen: "Area name, week IDs, enquiries vs bookings, coverage No/Thin, competitor note.",
        returnIf: "Vague ‘south Kashmir’ with no area name, or demand without weeks.",
        receiverDoes: "Expansion starts area research. They do not tell Marketing to launch.",
        example: "Pulwama W37: 14 enquiries, 2 bookings, coverage No. Top asks plumbing and electrical. OPP-014."
      },
      h03: {
        meaning: "The weekly picture Marketing is allowed to use: what to talk about, where, and which claims to avoid.",
        why: "Marketing must not invent demand or competitor insults. This object is the brief, not a campaign plan.",
        completeWhen: "Week ID, priority services, priority areas, competitor changes, explicit ‘do not claim X’.",
        returnIf: "A dump of all trackers with no priorities, or a request to ‘make a campaign’.",
        receiverDoes: "Marketing plans content on approved offers in those services and areas.",
        example: "W37: push AC servicing in Srinagar. Do not match CoolHome’s ₹499 unless Finance and OSD agree. Do not advertise Pulwama."
      },
      h04: {
        meaning: "A channel-shaped gap (hotels, shops, platforms) — not a signed partner.",
        why: "Partnerships & Channels qualify partners. MI only spots that we cannot reach a segment ourselves.",
        completeWhen: "Customer gap in one sentence, suggested channel type, evidence ID.",
        returnIf: "A named uncle who ‘knows someone’ with no customer gap.",
        receiverDoes: "PC logs a partnership opportunity and qualifies it. Leads still go to Sales later.",
        example: "Gulmarg tourist stays ask for same-day AC via hotel desks; we have no hotel channel. Suggest hotel reception partnerships."
      },
      h05: {
        meaning: "The customer-facing offer Marketing is allowed to talk about — definition, inclusions, exclusions, approved claims.",
        why: "Ads can only use what OSD has launched. Marketing does not invent SKUs or promises.",
        completeWhen: "Definition, inclusions/exclusions, approved claims.",
        returnIf: "A draft SKU, or claims OSD has not signed.",
        receiverDoes: "Marketing plans campaigns on this offer only. They do not change scope.",
        example: "AC gas refill — standalone visit in Srinagar. Includes leak check + refill. Does not include compressor repair. Approved claim: same-day slots where coverage exists."
      },
      h06: {
        meaning: "What Sales may promise when converting an enquiry.",
        why: "Sales cannot invent a price or a scope at the desk.",
        completeWhen: "Scope and what not to promise.",
        returnIf: "‘Tell them we’ll figure it out on site.’",
        receiverDoes: "Sales qualifies against this definition and either books or closes honestly.",
        example: "Deep cleaning: 2BHK standard, 4 hours, chemicals included. Do not promise sofa shampoo unless that SKU is on the booking."
      },
      h07: {
        meaning: "How the job must actually be delivered — steps and quality bar for Operations.",
        why: "Customer Operations cannot invent a method on the day.",
        completeWhen: "Delivery steps and quality requirements.",
        returnIf: "A marketing blurb with no steps.",
        receiverDoes: "CO uses it to schedule, brief and close the job.",
        example: "Plumbing leak fix: isolate, locate, repair, test run 5 min, photo of dry joint. Quality: no drip after 10 min."
      },
      h08: {
        meaning: "Skills, tools and areas the network must have before the service is sold at volume.",
        why: "PO builds the bench. OSD does not recruit plumbers.",
        completeWhen: "Skills, tools, areas.",
        returnIf: "‘Need more people’ with no skill list.",
        receiverDoes: "PO recruits and activates providers against the requirement.",
        example: "AC gas refill: licensed gas handling, gauges, Srinagar City + Bemina. Minimum 6 active providers before Marketing scales."
      },
      h09: {
        meaning: "How many providers, with which skills, an approved expansion area needs before launch.",
        why: "Expansion decides where; PO supplies the network.",
        completeWhen: "Area, launch services, provider numbers/skills.",
        returnIf: "‘Launch Pulwama’ with no capacity numbers.",
        receiverDoes: "PO builds coverage and reports readiness.",
        example: "Pulwama launch: plumbing + electrical. 8 verified providers, 4 of each skill, live availability calendar."
      },
      h10: {
        meaning: "Permission to create demand in a named area on a named date for live services.",
        why: "Marketing must not advertise an area Operations cannot fulfil.",
        completeWhen: "Area, services live, launch date.",
        returnIf: "A hopeful date with no ops go-live.",
        receiverDoes: "Marketing runs the area campaign from that date, tagged to the area.",
        example: "Bemina live 12 Oct for plumbing, electrical, AC servicing. Do not mention masonry yet."
      },
      h11: {
        meaning: "Sales may now accept bookings from this catchment.",
        why: "Sales must not convert enquiries for places we do not serve.",
        completeWhen: "Area live for booking.",
        returnIf: "Informal ‘we can try Pulwama.’",
        receiverDoes: "LMS marks the area bookable and rejects out-of-area honestly.",
        example: "Srinagar City + Bemina bookable. Pulwama = not live — close or waitlist, do not fake a slot."
      },
      h12: {
        meaning: "Operations confirmation that the new area can take jobs on day one.",
        why: "Launch without this object creates complaints, not growth.",
        completeWhen: "Operations go-live confirmation.",
        returnIf: "Marketing is already live and CO was not asked.",
        receiverDoes: "CO opens the area on the board and staffs the launch window.",
        example: "Bemina go-live signed by CO: assignment SLA 2h, CX scripts updated, exception route named."
      },
      h13: {
        meaning: "A tagged lead — the only demand object Marketing is allowed to hand to Sales.",
        why: "Marketing generates; Sales converts. Mixing them hides which engine failed.",
        completeWhen: "Source, campaign, service, location.",
        returnIf: "A comment screenshot with no source tag or location.",
        receiverDoes: "LMS owns the lead lifecycle to booked, lost or invalid.",
        example: "Meta W37 AC servicing, Srinagar City, name + WhatsApp. Campaign: AC-Sept-Cool. Source: paid_social."
      },
      h14: {
        meaning: "An enquiry that arrived through a partner, not through our ads.",
        why: "Partner quality is a channel KPI. The lead still belongs to Sales.",
        completeWhen: "Partner, source tag, customer requirement.",
        returnIf: "A verbal ‘hotel sent someone’ with no partner ID.",
        receiverDoes: "LMS converts it like any other lead, tagged to the partner.",
        example: "Hotel Darbar reception, guest wants same-day plumber, Lal Chowk. Partner: HT-012."
      },
      h15: {
        meaning: "What was actually spent against the approved marketing budget.",
        why: "Finance cannot control cash from a campaign screenshot.",
        completeWhen: "Campaign, amount, period.",
        returnIf: "‘We spent about ₹40k’ with no campaign or document.",
        receiverDoes: "FPC books the actuals against the budget line.",
        example: "AC-Sept-Cool Meta: ₹38,400, 1–15 Sep, invoice + platform export attached."
      },
      h16: {
        meaning: "The gold object. A sellable, schedulable job the customer has agreed to.",
        why: "Operations starts only on a confirmed booking — not on a warm lead.",
        completeWhen: "Customer, service, location, schedule, special requirements.",
        returnIf: "Missing slot, missing address, or a service not in the catalogue.",
        receiverDoes: "CO verifies, schedules, requests a provider, and drives the job to completion.",
        example: "BK-4418: AC servicing, 19 Sep 11:00–13:00, Rajbagh 2BHK, extra indoor units ×2, customer Farooq."
      },
      h17: {
        meaning: "What happened to marketing-sourced leads so Marketing can stop guessing.",
        why: "Cost per booking is a lie if lost reasons never return.",
        completeWhen: "Source and outcome reason.",
        returnIf: "‘Didn’t convert’ with no reason code.",
        receiverDoes: "Marketing kills or scales campaigns using real close reasons.",
        example: "paid_social / AC-Sept-Cool: lost — out of area (Pulwama). Stop spending on Pulwama creative."
      },
      h18: {
        meaning: "Whether a partner-sourced lead booked, was invalid, or was lost — and why.",
        why: "Partnerships cannot manage a channel without close data.",
        completeWhen: "Partner and outcome.",
        returnIf: "A booking count with no lead IDs.",
        receiverDoes: "PC keeps, coaches or cuts the partner.",
        example: "HT-012: 6 leads in W37, 2 booked, 3 invalid (no address), 1 lost (price)."
      },
      h19: {
        meaning: "A specific job’s need: service, area, window, skill — not a named favourite provider.",
        why: "CO asks for capacity. PO chooses who is suitable and available.",
        completeWhen: "Service, area, time window, skill.",
        returnIf: "‘Send Altaf’ with no requirement object.",
        receiverDoes: "PO returns an accepted assignment or a cannot-fill, fast.",
        example: "BK-4418 AC servicing, Rajbagh, 11:00–13:00, gas-handling certified."
      },
      h20: {
        meaning: "A named provider who has accepted the job with an ETA or schedule.",
        why: "CO cannot tell the customer ‘someone is coming’ without this object.",
        completeWhen: "Provider, ETA/schedule, acceptance.",
        returnIf: "A maybe, or a name with no accept timestamp.",
        receiverDoes: "CO locks the assignment, informs CX, monitors the visit.",
        example: "PR-208 Altaf accepted BK-4418, ETA 11:20, accepted 09:14."
      },
      h21: {
        meaning: "A fact CX must tell the customer: confirm, assign, delay, reschedule, complete.",
        why: "CX is the voice. CO is the driver. The message must carry updated facts.",
        completeWhen: "Booking, message type, updated facts.",
        returnIf: "‘Tell them something’ with no facts.",
        receiverDoes: "CX sends the approved message and logs it.",
        example: "BK-4418 assigned, PR-208, ETA 11:20. Message type: assignment."
      },
      h22: {
        meaning: "A customer request that changes the job: reschedule, update, delay complaint.",
        why: "CX does not dispatch. CO must change the board.",
        completeWhen: "Booking, request type, customer constraint.",
        returnIf: "A chat dump with no request type.",
        receiverDoes: "CO reschedules or updates, then sends a new communication event.",
        example: "BK-4418 reschedule: customer blocked until 16:00. Keep same provider if possible."
      },
      h23: {
        meaning: "Proof the service was delivered so money can start.",
        why: "RSM does not bill on Sales optimism or a verbal ‘done.’",
        completeWhen: "Booking, service delivered, completion time.",
        returnIf: "No completion timestamp, or service differs from the booking.",
        receiverDoes: "RSM invoices, collects and starts settlement.",
        example: "BK-4418 completed 19 Sep 12:08, AC servicing + extra indoor unit. Photos on the job card."
      },
      h24: {
        meaning: "A job that broke the path, or a scheduled sample for Quality to inspect.",
        why: "Quality must see exceptions, not only shouting customers.",
        completeWhen: "Booking and exception type.",
        returnIf: "A WhatsApp rant with no booking ID.",
        receiverDoes: "QM logs, samples or audits.",
        example: "BK-4401 exception: provider no-show, reassigned after 70 min. Sample for assignment SLA audit."
      },
      h25: {
        meaning: "A categorized complaint about service or provider quality — not a billing ticket.",
        why: "A kind apology is not a pass. Quality owns the standard.",
        completeWhen: "Booking, severity, description.",
        returnIf: "Uncategorized ‘customer unhappy.’",
        receiverDoes: "QM investigates against the standard.",
        example: "BK-4390 severity high: AC still warm after ‘wet service’. Customer photos attached."
      },
      h26: {
        meaning: "A money question from the customer, not a quality audit.",
        why: "CX must not invent refunds. RSM owns booking-level money.",
        completeWhen: "Booking and request type.",
        returnIf: "‘Give them ₹200 off’ with no RSM object.",
        receiverDoes: "RSM applies invoice, payment or refund rules and returns status to CX.",
        example: "BK-4418 refund query: charged extra indoor unit customer says was not done. Request type: dispute."
      },
      h27: {
        meaning: "Facts about how a provider behaved on a job — for the network owner.",
        why: "PO manages the person. CX does not deactivate providers.",
        completeWhen: "Provider, booking, facts.",
        returnIf: "A vibe with no facts or booking.",
        receiverDoes: "PO records the event and may restrict the provider pending Quality.",
        example: "PR-208 on BK-4388: arrived 55 min late, no call. Customer statement + CX log."
      },
      h28: {
        meaning: "Network events Quality needs: rejections, issues, ratings, status changes.",
        why: "Quality cannot see the network if PO only keeps it in a group chat.",
        completeWhen: "Provider, event, period.",
        returnIf: "A ranking screenshot with no events.",
        receiverDoes: "QM uses it in audits and repeat-failure reviews.",
        example: "PR-155 Sep: 4 job rejections in Bemina, 2 late arrivals, rating 3.6."
      },
      h29: {
        meaning: "A required action on a named provider after repeat failure or audit.",
        why: "PO executes network consequences. QM does not silently delete people.",
        completeWhen: "Provider, finding, required action.",
        returnIf: "‘This guy is bad’ with no finding.",
        receiverDoes: "PO coaches, restricts or deactivates and records the action.",
        example: "PR-155: repeat late arrival (3 in 14 days). Action: 14-day restriction + coaching. Due 25 Sep."
      },
      h30: {
        meaning: "A change CO must make to a process after a fulfilment failure or SOP deviation.",
        why: "One apology does not fix a broken assignment path.",
        completeWhen: "Process, cause, action, due date.",
        returnIf: "A lecture with no due date.",
        receiverDoes: "CO changes the SOP practice and reports back.",
        example: "Assignment SLA: jobs unassigned >45 min had no escalation. Action: board alert + named backup. Due 22 Sep."
      },
      h31: {
        meaning: "A repeating service problem that may be the offer, not one provider.",
        why: "OSD must fix the SKU or the method if the pattern is systemic.",
        completeWhen: "Service, pattern, evidence.",
        returnIf: "A single bad job.",
        receiverDoes: "OSD opens a service improvement or retirement review.",
        example: "AC servicing: ‘still warm’ on wet-service jobs, 11 cases in Srinagar, Aug–Sep. Review method + inclusions."
      },
      h32: {
        meaning: "A repeating quality failure that looks like a market or offer problem, not one bad job.",
        why: "MI must not ignore Quality. Recurring ‘AC not cooling after service’ is demand and offer evidence.",
        completeWhen: "Trend, service, area, period, evidence pointer from Quality.",
        returnIf: "A single complaint with no pattern.",
        receiverDoes: "MI logs a trend and/or opportunity. Does not run the audit.",
        example: "QM monthly: AC servicing complaints in Srinagar 3 weeks running after wet-service jobs. Signal to OSD + trend TR-0xx."
      },
      h33: {
        meaning: "What customers keep saying after jobs — ratings and repeat themes.",
        why: "CX sees language CRM booking types miss (‘came late’, ‘wanted evening slot’). That is market-research input.",
        completeWhen: "Period, rating summary, repeat issues, services/areas named.",
        returnIf: "A folder of raw tickets with no theme.",
        receiverDoes: "MI updates the Trend Tracker and may open an opportunity (hours, SKU, area).",
        example: "August CX: evening-slot requests mentioned on 22 plumbing jobs. TR-018. Not a Sales conversion issue."
      },
      h34: {
        meaning: "Revenue, collections, payouts and exceptions from jobs — fuel for the business plan.",
        why: "FPC does not invoice bookings; it needs the settlement truth.",
        completeWhen: "Revenue, collections, payouts, exceptions.",
        returnIf: "A cash-in-hand number with no exception list.",
        receiverDoes: "FPC updates cash, variance and management accounts.",
        example: "W37: billed ₹4.2L, collected ₹3.7L, payouts ₹2.1L, 6 unbilled completions, 2 disputes."
      },
      h35: {
        meaning: "What CX is allowed to tell the customer about invoice, payment, refund or dispute.",
        why: "CX must not guess money status.",
        completeWhen: "Booking and status.",
        returnIf: "Verbal ‘I think they paid.’",
        receiverDoes: "CX messages the customer with the status.",
        example: "BK-4418: extra-unit dispute rejected — job card shows 3 indoor units. Status: payable, no refund."
      },
      h36: {
        meaning: "Employee join, leave, attendance and vacancy cost — not provider payouts.",
        why: "FPC plans the company. HR owns the employee facts.",
        completeWhen: "Headcount, role, cost impact.",
        returnIf: "A rumour that someone resigned.",
        receiverDoes: "FPC updates people cost vs plan.",
        example: "CO executive joined 1 Sep. Vacancy: Sales closer still open, 22 days."
      },
      h37: {
        meaning: "Who needs which systems from which date — onboarding or exit.",
        why: "TD does not decide who works here. HR does.",
        completeWhen: "Person, role, systems, effective date.",
        returnIf: "‘Give him WhatsApp admin’ with no role or date.",
        receiverDoes: "TD grants or removes access and returns confirmation.",
        example: "Ayaan, CO executive, needs booking board + CX inbox from 1 Sep. No payout module."
      },
      h38: {
        meaning: "Proof access was granted or removed.",
        why: "HR cannot close onboarding or exit without the system fact.",
        completeWhen: "Person, systems, completed time.",
        returnIf: "‘Should be done’ with no timestamp.",
        receiverDoes: "HR files it on the employee record.",
        example: "Ayaan: booking board + CX inbox granted 1 Sep 09:40. Payout module not assigned."
      },
      h39: {
        meaning: "What each function may spend this period.",
        why: "Desks cannot invent budget. Overspend is a control failure.",
        completeWhen: "Department limits and period.",
        returnIf: "A verbal ‘keep it reasonable.’",
        receiverDoes: "Every function plans work inside the limit and raises expense objects for actuals.",
        example: "Sep: Marketing ₹2.4L, Expansion ₹0.8L, no unapproved tools spend."
      },
      h40: {
        meaning: "A named spend against a budget line, with authorization and a document.",
        why: "FPC cannot control cash from group-chat payments.",
        completeWhen: "Amount, budget line, authorization, document.",
        returnIf: "A UPI screenshot with no budget line.",
        receiverDoes: "FPC approves or books the actual, or sends it back.",
        example: "MKT Meta boost ₹8,000, line: paid social Sep, authorized by Growth Function, invoice attached."
      },
      h41: {
        meaning: "A systems, data or access need with a requester, system and priority.",
        why: "TD cannot treat every WhatsApp as a ticket.",
        completeWhen: "Requester, system, priority.",
        returnIf: "‘The app is broken’ with no system or impact.",
        receiverDoes: "TD triages, restores or changes, then hands back a working object.",
        example: "CO board not showing Bemina jobs. Requester: CO desk. Priority: high. System: booking board."
      },
      h42: {
        meaning: "What changed and who can use it after an incident or a report issue.",
        why: "Functions must know the pipe is actually usable.",
        completeWhen: "What changed and who can use it.",
        returnIf: "‘Fixed’ with no user list or change note.",
        receiverDoes: "The owning function continues work on the restored tool.",
        example: "Booking board Bemina filter restored 18 Sep 14:10. CO and CX can use. Change note in TD register."
      },
      h43: {
        meaning: "Which staff the policy, KPI or training applies to.",
        why: "SOPs die when people are not told they apply.",
        completeWhen: "Who it applies to.",
        returnIf: "A PDF in a drive with no audience.",
        receiverDoes: "Function leads apply it to their seats.",
        example: "Assignment SLA coaching: all CO executives, due 25 Sep. Attendance logged by HR."
      },
      h44: {
        meaning: "Whether a requested seat may be hired.",
        why: "HR must not recruit against a hope. FPC confirms the money.",
        completeWhen: "Role and approved / not approved.",
        returnIf: "‘We probably need someone.’",
        receiverDoes: "HR opens or holds the vacancy.",
        example: "Sales closer: approved 1 FTE from Oct. CO second desk: not approved this quarter."
      }
    }
  },
  controlPage: {
    explain: "Control is a span, not a station. Financial Planning & Control owns the business plan, cash and expense limits. People & HR owns employees — never providers by default. Technology & Data owns systems, access and the information those systems carry. They hang above Growth and Operations so every function can run, without taking over the booking."
  },
  connections: {
    c3: {
      teach: "This is the most important fence in the company. Marketing may only generate the enquiry. Lead Management & Sales may only qualify and convert it. Customer Operations may only fulfil the confirmed booking. If the same person does all three, you cannot tell whether demand, conversion or delivery is failing."
    },
    c1: {
      teach: "Nothing should be marketed that Intelligence has not evidenced and Service Development has not made sellable. Marketing without an approved offer creates enquiries Sales cannot convert and Operations cannot deliver."
    },
    c2: {
      teach: "A new area is not a Facebook post. Intelligence finds the market, Expansion decides entry, Provider Operations builds capacity, then Marketing and Sales may turn the tap. Launching demand before capacity produces complaints, not growth."
    },
    c4: {
      teach: "Customer Operations owns the booking. Provider Operations owns the network. CO asks for a suitable available provider; PO finds one; CO stays with the customer through delivery. Quality later tells PO how the network actually performed."
    },
    c5: {
      teach: "When the service is complete, Revenue & Settlement bills the customer, calculates commission and pays the provider. That is one booking's money. It is not the company's budget, cash plan or expense control — those stay in Financial Planning & Control."
    },
    c6: {
      teach: "Quality is independent. CX handles the customer's experience. CO handles execution. PO handles the network. Quality asks whether the standard was met, finds the root cause, and sends corrective action back. It is not a second complaint desk."
    },
    c7: {
      teach: "Two money layers. RSM is transaction control: invoice, collection, commission, payout, closed settlement. FPC is business control: budget, cash, spend limits, management accounts. Mixing them hides both leakage and insolvency."
    },
    c8: {
      teach: "HR knows who is employed, on what terms, and whether they are performing. Finance needs that people data to plan salary cost. HR does not set the budget. Finance does not run recruitment."
    },
    c9: {
      teach: "Providers are the operating network. Employees are the company. A craftsman with a toolbox is not on People & HR by default. If you put providers into HR, you will pay them like staff and manage them like neither."
    },
    c10: {
      teach: "Technology is the pipes: apps, access, backups, integrations. Data is what flows in the pipes: customers, bookings, money, KPIs. A working system with dirty data is still a broken operating system."
    },
    c11: {
      teach: "The whole machine in one line: Growth creates, Operations delivers, Control supports. If you only staff Operations, you will run out of the right work. If you only staff Growth, you will sell what you cannot deliver."
    },
    c12: {
      teach: "Customer Experience is throughout and after the job: communication, support, complaints, feedback, recovery. It does not convert leads, assign providers, audit quality, or settle money. Keep CX close to the customer and out of other people's registers."
    }
  },
  packs: {
    mi: {
      inPlain: "Market Intelligence is the evidence desk. It counts what people ask for, watches named competitors, logs outside-world trends, names opportunities, and either validates them or kills them. It does not design a service, run an ad, take a booking, or enter a town.",
      does: ["Demand trackers (what people asked, what we sell, where)", "Named competitors and dated changes", "External trends CRM cannot see", "Opportunity register + validation decisions", "Weekly and monthly reports compiled from those registers"],
      doesNot: ["Design the offer", "Run campaigns", "Convert enquiries", "Enter a new area", "Recruit providers"],
      ifBroken: "Growth guesses. Marketing sells the wrong thing in the wrong town. Expansion launches before demand is real.",
      how: [
        { title: "File then count", text: "Index the source in the MI Database, then close the week’s Customer, Service and Area demand trackers." },
        { title: "Watch the outside", text: "Log competitor changes and external trends in their own files — never mix them into demand counts." },
        { title: "Name, then decide", text: "One hypothesis per opportunity. Validate or kill it. Hand a complete object to OSD, Expansion or Partnerships." }
      ]
    },
    mkt: {
      inPlain: "Marketing creates awareness and qualified demand for offers that already exist. It fills the top of the funnel with enquiries that Sales can convert. It does not close the booking, and it does not change the service definition to make an ad easier.",
      does: ["Plans and campaigns", "Content on approved claims", "Lead generation with source tracking", "Cost per lead and per booking"],
      doesNot: ["Qualify or convert the lead", "Invent inclusions", "Fulfil the job", "Set company budget"],
      ifBroken: "Sales starves, or Sales drowns in junk enquiries that cannot be delivered.",
      how: [
        { title: "Take the offer", text: "Only market what Service Development has approved to sell." },
        { title: "Create demand", text: "Run content and campaigns with a source, a claim, and a place for the enquiry to land." },
        { title: "Hand the enquiry", text: "Pass a complete enquiry object to Lead Management & Sales. Do not convert it yourself." }
      ]
    },
    sd: {
      inPlain: "Offer & Service Development turns a market need into something Panun Kaergar can actually sell and deliver. Catalogue, inclusions, exclusions, price logic and launch readiness live here. Without this function, Marketing promises and Operations improvises.",
      does: ["Service catalogue", "New and improved offers", "Validation and launch readiness", "What Sales is allowed to promise"],
      doesNot: ["Run ads", "Take bookings", "Recruit providers as a network owner", "Set quality audit standards"],
      ifBroken: "The company sells vapour. Complaints rise because the job was never specified.",
      how: [
        { title: "Receive the need", text: "Take validated opportunities from Market Intelligence and complaint/quality themes from Operations." },
        { title: "Design the offer", text: "Define scope, exclusions, delivery needs and commercial shape." },
        { title: "Release it", text: "Only then may Marketing sell it, Sales book it, and Customer Operations fulfil it." }
      ]
    },
    me: {
      inPlain: "Market Expansion decides where else Panun Kaergar should operate. It researches the area, tests feasibility, and plans the launch. It does not shout about the new town until capacity exists.",
      does: ["Area research", "Feasibility", "Expansion plan", "Launch checklist with provider and service readiness"],
      doesNot: ["Build the provider network itself", "Run the local campaign as Marketing", "Convert the first leads", "Own the P&L as Finance"],
      ifBroken: "The brand arrives before the craftsmen. Demand becomes disappointment.",
      how: [
        { title: "Find the area", text: "Use Intelligence evidence, not a hunch about a cousin's town." },
        { title: "Prove we can operate", text: "Demand, competition, providers, services and cost must all clear a gate." },
        { title: "Launch in order", text: "Capacity first, then Marketing, then Sales, then Customer Operations." }
      ]
    },
    pc: {
      inPlain: "Partnerships & Channels build external routes to customers and sometimes to supply. Hotels, shops, platforms and other allies send leads. Those leads still go to Sales — this function does not convert them at the partner's desk.",
      does: ["Find and qualify partners", "Onboard and manage the channel", "Track partner-sourced leads and bookings", "Partner performance"],
      doesNot: ["Close the customer booking", "Pay providers", "Replace Marketing's own demand engine"],
      ifBroken: "Leads leak at the partner. Nobody knows which channel is worth the commercial deal.",
      how: [
        { title: "Choose the channel", text: "A partner must bring demand, supply or strategic access we cannot get cheaper ourselves." },
        { title: "Switch the lead on", text: "Onboarding includes how an enquiry is captured and handed to Sales." },
        { title: "Measure the channel", text: "Leads, qualified leads and bookings by partner — then keep, fix or cut." }
      ]
    },
    lms: {
      inPlain: "Lead Management & Sales is the conversion seat. An enquiry comes in. This desk qualifies it, explains the approved offer, and either creates a confirmed booking or closes the lead honestly. It does not invent a price, dispatch a provider, or soothe a post-job complaint.",
      does: ["Lead intake", "Qualification and follow-up", "Booking conversion", "Lead statuses"],
      doesNot: ["Create demand", "Fulfil the booking", "Support after service as CX", "Settle money"],
      ifBroken: "Demand dies in the inbox, or Operations inherits bookings that cannot be delivered.",
      how: [
        { title: "Catch the enquiry", text: "Every lead gets a source, a need, an area and an owner." },
        { title: "Qualify", text: "Can we sell this approved service, in this area, at this time? If not, close or route — do not fake a yes." },
        { title: "Confirm the booking", text: "The gold object leaves this desk complete. Customer Operations is now in charge." }
      ]
    },
    co: {
      inPlain: "Customer Operations owns the booking after it is confirmed. Schedule, assign, monitor, complete. It asks Provider Operations for a suitable available provider, stays with the customer through delivery, and closes the job so money and quality can start. Customer Experience sits inside this journey as the support voice — it does not take the booking over.",
      does: ["Booking intake from Sales", "Scheduling and assignment", "Service monitoring", "Completion and close"],
      doesNot: ["Convert the lead", "Own the provider network", "Audit quality independently", "Bill and pay"],
      ifBroken: "Confirmed bookings go dark. The customer has a ticket and nobody is driving the job.",
      how: [
        { title: "Receive the booking", text: "Check the object is complete. If Sales skipped fields, send it back." },
        { title: "Make it real", text: "Schedule, request a provider, keep the customer informed with CX." },
        { title: "Close it", text: "Service completion is a handoff to Revenue, Quality and CX follow-up — not a shrug." }
      ]
    },
    cx: {
      inPlain: "Customer Experience is nested under Customer Operations. It is the customer's companion throughout and after the job: messages, support, complaints, feedback, recovery. It is not Sales, not dispatch, not Quality, not Finance.",
      does: ["Communication", "Support", "Complaints", "Feedback", "Service recovery"],
      doesNot: ["Convert leads", "Assign providers", "Set quality standards or audits", "Collect or settle money"],
      ifBroken: "The job may happen, but the customer feels abandoned — and learning never returns to Growth.",
      how: [
        { title: "Talk in time", text: "Before, during and after the visit the customer should never have to guess." },
        { title: "Hold the issue", text: "Support and complaints are logged, owned and closed — not dumped into Quality or Sales." },
        { title: "Send learning back", text: "Feedback and recovery themes go to Quality, Service Development and Market Intelligence." }
      ]
    },
    po: {
      inPlain: "Provider Operations builds and runs the network that can actually do the jobs. Acquisition, verification, activation, availability, deployment, performance. Providers are not employees. This desk does not take customer bookings or pay settlements.",
      does: ["Network supply", "Verification and activation", "Availability and deployment", "Provider performance"],
      doesNot: ["Convert customer enquiries", "Own the booking", "Handle customer complaints as CX", "Pay the provider as RSM"],
      ifBroken: "Customer Operations has bookings and nobody qualified to send. Quality will fail in public.",
      how: [
        { title: "Build the bench", text: "Find, check and activate providers against the services we sell." },
        { title: "Answer the request", text: "When CO sends a requirement, return a suitable available provider — or say we cannot, fast." },
        { title: "Keep the network honest", text: "Performance and Quality feedback change who stays active." }
      ]
    },
    rsm: {
      inPlain: "Revenue & Settlement Management is booking-level money. Bill the customer, collect, calculate commission, pay the provider, close the settlement. It is not the company's budget or cash strategy.",
      does: ["Customer billing and collections", "Commission", "Provider payouts", "Reconciliation and exceptions"],
      doesNot: ["Set company budgets", "Run payroll for employees", "Fulfil the job", "Create demand"],
      ifBroken: "Work is done and nobody is paid correctly — or cash leaks one booking at a time.",
      how: [
        { title: "Wait for completion", text: "Money starts when Customer Operations closes the service, not when Sales is optimistic." },
        { title: "Bill and collect", text: "The customer invoice must match the delivered offer." },
        { title: "Settle the provider", text: "Commission and payout follow commercial rules. Exceptions escalate, they are not whispered." }
      ]
    },
    qm: {
      inPlain: "Quality Management is the independent check. Standards, monitoring, audits, root cause, corrective action. It must stay far enough from CX that a kind apology does not count as a pass.",
      does: ["Quality standards", "Monitoring and audits", "Root cause", "Corrective action", "Improvement loops"],
      doesNot: ["Become a complaint hotline", "Dispatch providers", "Convert leads", "Settle money"],
      ifBroken: "Failures repeat. Marketing keeps selling a broken experience. Providers are not coached with evidence.",
      how: [
        { title: "Set the bar", text: "A service has a standard or it is improvised." },
        { title: "Sample and audit", text: "Look at completed work, not only at shouting customers." },
        { title: "Correct and feed back", text: "Actions go to CO, PO and Service Development. Themes go to Market Intelligence." }
      ]
    },
    fpc: {
      inPlain: "Financial Planning & Control is the business money layer: plans, budgets, cash, expense control, management accounts. It uses settlement and people data, but it does not invoice a booking or recruit a plumber.",
      does: ["Planning and budgeting", "Cash flow", "Expense control", "Financial reporting"],
      doesNot: ["Customer invoices", "Provider payouts", "Employee hiring", "Running apps"],
      ifBroken: "The company can be busy and still run out of cash, or spend without a plan.",
      how: [
        { title: "Plan the year", text: "Every function gets a budget it can actually be held to." },
        { title: "Watch cash and spend", text: "Variance is a control signal, not an argument after the money is gone." },
        { title: "Report the business", text: "Management accounts tell whether the operating system is profitable — not whether one job was billed." }
      ]
    },
    hr: {
      inPlain: "People & HR is the employee system: workforce plan, recruitment, onboarding, records, attendance, performance, training, lifecycle. Providers stay in Provider Operations. Confusing the two creates the wrong contracts and the wrong care.",
      does: ["Workforce planning", "Recruitment and onboarding of staff", "Employee records and performance", "Training and lifecycle"],
      doesNot: ["Provider onboarding", "Customer bookings", "Company cash strategy", "Quality audits of jobs"],
      ifBroken: "Desks sit empty, or they are filled with people who were never trained for the SOP they must run.",
      how: [
        { title: "Plan the seats", text: "Each function needs named capacity, not a hero." },
        { title: "Hire employees", text: "Staff get IDs, records and a manager. Providers do not enter this door by default." },
        { title: "Keep them able", text: "Performance and training are how SOPs stay alive when someone goes on leave." }
      ]
    },
    td: {
      inPlain: "Technology & Data keeps the pipes and the water separate in our heads: systems, apps, access, backups on one side; customer, booking, money and KPI information on the other. If the system is up but the data is wrong, the operating system is still down.",
      does: ["Systems and applications", "Access and security", "Backup and recovery", "Data accuracy and reporting pipes"],
      doesNot: ["Decide what to sell", "Take a booking as Sales", "Approve a payout as RSM", "Hire staff as HR"],
      ifBroken: "Every other function works in chats and spreadsheets, and the truth cannot be handed off.",
      how: [
        { title: "Run the systems", text: "The tools each function needs must be available, permissioned and backed up." },
        { title: "Protect access", text: "Keys and roles stop one desk from silently editing another desk's register." },
        { title: "Keep data fit to hand off", text: "A handoff object is only as good as the fields the system stores." }
      ]
    }
  },
  heads: {
    operations: {
      title: "Head of Operations",
      reportsTo: "Founder / Management",
      purpose: "Own the Operations Function: the customer-facing line from enquiry to confirmed booking, fulfilment, support, billing, settlement and quality. This seat does not run any one desk. It holds the six desks to their SOPs, reads the line as one system, and closes defects that sit between desks.",
      inPlain: "If conversion is slow, fulfilment is dark, providers are missing, money is uncleared or quality is repeating, the Head of Operations is the person who must see which desk failed — and which neighbouring function starved it. Doing the desk work yourself hides the failure.",
      does: [
        "Hold six desks to their packs, SOPs and KPIs",
        "Keep the enquiry-to-settlement line visible every day",
        "Close cross-desk escalations the desks cannot finish",
        "Sign operational readiness for a new service or area",
        "Protect the fences: Marketing generates, Sales converts, Operations fulfils, Quality checks, RSM settles booking-level money"
      ],
      doesNot: [
        "Convert leads or invent prices (Lead Management & Sales)",
        "Assign providers or own the booking (Customer Operations)",
        "Run the complaint/support queue as CX",
        "Recruit, verify or pay providers as the network or settlement owner",
        "Write quality standards or pass an audit by apologising",
        "Set the company budget or hire as Finance / HR"
      ],
      responsibilities: [
        { title: "Line ownership", text: "The Head owns the path, not the tasks. Enquiry in, confirmed booking, assigned visit, completed job, billed and settled work, quality loop closed. If any object stalls, this seat names the desk and the missing field." },
        { title: "Desk performance", text: "Each desk has a named executive and a pack. The Head reviews whether SOPs are run, KPIs are produced from registers, and weekly reports match the board — not a separate story." },
        { title: "Cross-desk defects", text: "A complete booking that Customer Operations cannot fulfil, a provider with no payout, a complaint that Quality never sees — these are Head-of-Operations problems. Desks must not silently repair another desk's incomplete object." },
        { title: "Capacity and coverage", text: "Sales may only convert approved services in live areas. The Head confirms Provider Operations can actually staff what Growth wants to sell, before Marketing is allowed to shout." },
        { title: "Escalation court", text: "Lead SLA breach, unassigned booking, high-severity complaint, critical quality or safety issue, and stalled settlement all route to the Operations Function when the desk cannot close them." },
        { title: "Operating review", text: "Daily exception board. Weekly Operations Review. Monthly Operations Pack to Management. These three artefacts are this seat's work product. Desk reports feed them; they are not copied again." }
      ],
      monitor: {
        daily: [
          "Unprocessed leads at day end — target 0 (LMS board)",
          "Unassigned or delayed bookings vs SLA — target 0 (CO board)",
          "Open CX cases with no update (CX tracker)",
          "Today's visits: assigned, on the way, completed, exception",
          "Completed jobs not yet billed (RSM)",
          "Critical quality / safety flags opened today (QM)"
        ],
        weekly: [
          "Qualified-lead conversion vs last week (LMS) — pattern, not panic",
          "Fulfilment rate, rejection recovery, cancellations (CO)",
          "Complaint volume, resolution SLA, repeat complaints (CX)",
          "Coverage gaps by service and area; acceptance and no-show (PO)",
          "Reconciliation items past SLA; outstanding collections (RSM)",
          "Open corrective actions and recurring failure themes (QM)"
        ],
        monthly: [
          "Where the line leaked: demand vs conversion vs fulfilment vs money vs quality",
          "Capacity vs what Growth wants to sell next month",
          "Which desk KPIs are green while the customer still suffers — that is a boundary leak",
          "Launch or area-readiness: providers, coverage, settlement, quality standard in place",
          "What Operations must ask Growth to stop selling, or Control to staff/fix"
        ]
      },
      kpis: [
        { name: "Line clear at day end", target: "0 unprocessed leads and 0 unassigned bookings past SLA", from: "LMS + CO" },
        { name: "Booking fulfilment", target: "≥95% of confirmed bookings completed or honestly exceptioned", from: "CO" },
        { name: "Conversion health", target: "Qualified-lead → booking tracked monthly; lost reasons ≥95% captured", from: "LMS" },
        { name: "Customer case hygiene", target: "≥95% query response SLA; open cases 100% updated", from: "CX" },
        { name: "Network fit for the board", target: "≥95% coverage of live services and live areas", from: "PO" },
        { name: "Completed work billed", target: "100% of completed bookings billed; recon items 0 beyond SLA", from: "RSM" },
        { name: "Quality loop closed", target: "Critical issues routed immediately; corrective actions verified", from: "QM" },
        { name: "Head artefacts on time", target: "Daily board, Weekly Operations Review, Monthly Operations Pack — 100%", from: "This seat" }
      ],
      documents: [
        { name: "Operations Exception Board", kind: "Live register", oneRow: "One stalled object: lead, booking, case, payout or quality flag", usedBy: "Head, updated from desk boards" },
        { name: "Cross-desk Escalation Log", kind: "Register", oneRow: "One escalation that a desk could not close", usedBy: "OPS-H-03" },
        { name: "Capacity & Coverage Snapshot", kind: "Weekly sheet", oneRow: "One live service × area: can we sell and fulfil it this week?", usedBy: "Head + PO + LMS" },
        { name: "Operations Launch Sign-off", kind: "Gate form", oneRow: "One new service or area: ops-ready or not", usedBy: "OPS-H-05" },
        { name: "Weekly Operations Review", kind: "Report pack", oneRow: "One week of the line, by desk, with exceptions", usedBy: "Head to Management" },
        { name: "Monthly Operations Pack", kind: "Report pack", oneRow: "One month: conversion, fulfilment, network, money, quality, asks to Growth and Control", usedBy: "Head to Management" }
      ],
      reports: [
        { when: "Daily", name: "Exception flash", contains: "Only what is red: unprocessed leads, unassigned bookings, overdue CX cases, unbilled completions, critical quality. No essay.", audience: "Head + desk executives" },
        { when: "Weekly", name: "Weekly Operations Review", contains: "Line volumes, SLA hits/misses, top exceptions, coverage gaps, money recon still open. Built from desk weekly reports — do not re-key.", audience: "Founder / Management" },
        { when: "Monthly", name: "Monthly Operations Pack", contains: "Trends, lost-lead and quality themes for Growth, capacity and training asks for Control, launch sign-offs, SOP defects to fix.", audience: "Founder / Management; copies to Growth and Control heads" }
      ],
      ask: [
        { team: "Marketing", pack: "mkt", ask: "Source-tagged enquiries only. No converted bookings. Campaign claims must match the approved offer." },
        { team: "Partnerships & Channels", pack: "pc", ask: "Partner-tagged leads with partner ID. Poor or untagged partner leads go back — Sales does not guess the source." },
        { team: "Offer & Service Development", pack: "sd", ask: "Approved inclusions, exclusions, price logic and launch pack before Sales is allowed to promise a job." },
        { team: "Market Expansion", pack: "me", ask: "Live area list and launch date. Do not ask Operations to fulfil a town that was never signed off." },
        { team: "Market Intelligence", pack: "mi", ask: "Evidence when conversion or coverage is failing: demand vs bookings by area/service, not a hunch." },
        { team: "People & HR", pack: "hr", ask: "Named employees in each ops seat, attendance, and training records. Providers stay in Provider Operations." },
        { team: "Technology & Data", pack: "td", ask: "CRM, booking, assignment, billing and quality registers that can actually hand off a complete object. Wrong fields are an ops outage." },
        { team: "Financial Planning & Control", pack: "fpc", ask: "Whether payouts and refunds are cash-possible this week. RSM executes booking-level money; Finance owns the business cash plan." }
      ],
      share: [
        { team: "Marketing / Partnerships", pack: "mkt", share: "Lost-lead patterns and source conversion. Which channels send junk. Do not ask them to convert — ask them to fix demand quality." },
        { team: "Offer & Service Development", pack: "sd", share: "Jobs we cannot fulfil as sold, complaint themes on inclusions, quality failures that mean the offer is wrong." },
        { team: "Market Intelligence", pack: "mi", share: "Fulfilment and quality facts the CRM will not invent: area we cannot cover, service that keeps failing." },
        { team: "Market Expansion", pack: "me", share: "Honest ops-ready / not-ready on a new area: providers, coverage, settlement, quality standard." },
        { team: "Financial Planning & Control", pack: "fpc", share: "Closed settlement totals, outstanding collections, payout liabilities — via RSM's monthly close, not a parallel spreadsheet." },
        { team: "People & HR", pack: "hr", share: "Which ops seats are empty or untrained, and which SOPs break when someone is on leave." },
        { team: "Technology & Data", pack: "td", share: "Missing handoff fields, duplicate registers, boards that cannot show unprocessed work at day end." }
      ],
      sops: [
        { id: "OPS-H-01", title: "Daily line check", when: "Every working day before close", steps: ["Open LMS: unprocessed and SLA-breached leads", "Open CO: unassigned, delayed, today's visits", "Open CX: cases with no update", "Open RSM: completed and unbilled", "Open QM: new critical flags", "Write only exceptions onto the Operations Exception Board", "Name an owner and a next time for each red row", "Do not work the row yourself unless the desk is empty"] },
        { id: "OPS-H-02", title: "Weekly Operations Review", when: "Same weekday each week", steps: ["Collect desk weekly reports — do not rebuild them", "Read conversion, fulfilment, coverage, collections, quality as one line", "Mark which leaks are desk SOP failure vs missing inbound object from Growth or Control", "Agree one fix per leaking desk, with owner and due date", "Issue the Weekly Operations Review to Management", "Send Growth-only and Control-only asks as separate objects, not a rant"] },
        { id: "OPS-H-03", title: "Cross-desk escalation", when: "A desk cannot close an object inside its SLA", steps: ["Confirm the desk already recorded the case on its register", "Identify the incomplete object and the missing field or missing neighbour", "If the neighbour is another ops desk, return the object — do not patch in silence", "If the neighbour is Growth or Control, send a named ask with evidence", "Set a Head-level due time", "Update the customer-facing desk (CO or CX) so the customer is not guessing", "Close on the Escalation Log with outcome"] },
        { id: "OPS-H-04", title: "Capacity and coverage decision", when: "LMS cannot sell, CO cannot assign, or Growth wants more demand", steps: ["Read PO coverage by service and area", "Read CO unassigned and rejection", "Read LMS lost reasons for area/service unavailable", "Decide: stop selling, recruit, or temporarily constrain Marketing", "Write the Capacity & Coverage Snapshot", "Tell LMS and Marketing the live list — not a chat rumour"] },
        { id: "OPS-H-05", title: "Operations launch sign-off", when: "A new service or area is proposed", steps: ["Require SD approved offer and ME/PO capacity evidence", "Check PO: verified providers in the area/service", "Check LMS: what Sales is allowed to promise", "Check CO/CX: booking and customer communication can run", "Check RSM: commercial rule can bill and settle this job", "Check QM: a standard exists so the job is not improvised", "Sign ready / not ready. Not ready means Marketing does not launch"] },
        { id: "OPS-H-06", title: "Monthly Operations Pack", when: "Month close", steps: ["Take desk monthly reports as source", "Show the line: enquiry → booking → fulfilment → bill → quality", "Name recurring lost-lead, complaint and quality themes for Growth", "Name seat, training and system defects for Control", "Record launch sign-offs and coverage changes", "Submit on time. If a desk report is missing, that is a Head KPI miss — not a reason to invent numbers"] }
      ],
      escalationsIn: [
        { trigger: "Lead SLA breach", from: "LMS", sla: "As per first-response SLA", record: "Follow-Up Tracker + Exception Board" },
        { trigger: "Confirmed booking not accepted / unassigned past SLA", from: "CO / LMS", sla: "Same day", record: "Booking Register + Escalation Log" },
        { trigger: "High-severity complaint or CX SLA miss", from: "CX", sla: "CX-SOP-03", record: "Customer Escalation Register" },
        { trigger: "Critical quality, safety or abuse", from: "QM", sla: "Immediate", record: "Quality Issue Register" },
        { trigger: "Settlement or recon stuck beyond SLA", from: "RSM", sla: "As per recon SLA", record: "Daily Reconciliation + Escalation Log" },
        { trigger: "Coverage collapse (cannot staff live services/areas)", from: "PO / CO", sla: "Same day to constrain selling", record: "Capacity & Coverage Snapshot" }
      ],
      cadence: {
        daily: ["Exception Board (OPS-H-01)", "Named owners on every red row", "No silent hero work on another desk's queue"],
        weekly: ["Weekly Operations Review (OPS-H-02)", "Capacity & Coverage Snapshot (OPS-H-04)", "One fix per leaking desk"],
        monthly: ["Monthly Operations Pack (OPS-H-06)", "Launch sign-offs still open (OPS-H-05)", "Asks to Growth and Control as complete objects"]
      }
    }
  },
  flowArt: {
    c1: "demand",
    c2: "fulfil",
    c3: "boundary",
    c4: "fulfil",
    c5: "money",
    c6: "learn",
    c7: "money",
    c8: "people",
    c9: "people",
    c10: "td",
    c11: "hero",
    c12: "boundary"
  }
};

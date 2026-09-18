window.PK_SYSTEM = {
  title: "Panun Kaergar",
  grouping: "Management & Control",
  updated: "17 Sep 2026",
  purpose: "Panun Kaergar is run through Management & Control. Growth creates what the market can buy. Operations converts demand into delivered work. Control plans money, people, and technology so the business can operate.",
  groups: [
    { id: "growth", name: "Growth", purpose: "Where and how the business grows." },
    { id: "operations", name: "Operations", purpose: "How demand is converted, fulfilled, billed, and quality-checked." },
    { id: "control", name: "Control", purpose: "How money, people, and systems keep the business in control." }
  ],
  connections: [
    {
      id: "c3",
      kind: "boundary",
      title: "Critical system boundary",
      subtitle: "Who generates demand, who converts, and who fulfils — these seats must not mix.",
      distinction: "Marketing generates the enquiry. Lead Management & Sales qualifies and converts it into a confirmed booking. Customer Operations fulfils that booking. Demand creation, conversion, and fulfilment stay in separate modules.",
      steps: [
        { pack: "mkt", action: "Generates enquiry" },
        { pack: "lms", action: "Qualifies + converts" },
        { pack: "co", action: "Fulfils booking" }
      ],
      chain: [
        { pack: "mkt" },
        { action: "Generates enquiry" },
        { pack: "lms" },
        { action: "Qualifies + converts" },
        { artifact: "Confirmed booking" },
        { pack: "co" },
        { action: "Fulfils booking" }
      ]
    },
    {
      id: "c1",
      title: "Demand creation to booking",
      subtitle: "What we sell, then how we fill the funnel",
      distinction: "Market Intelligence discovers what the market needs. Offer & Service Development turns that need into something Panun Kaergar can actually sell and deliver. Marketing then takes that approved offer to the market.",
      steps: [
        { pack: "mi", action: "Identifies market/customer opportunity" },
        { pack: "sd", action: "Turns opportunity into a viable service" },
        { pack: "mkt", action: "Creates awareness and demand" },
        { pack: "lms", action: "Converts demand into bookings" }
      ]
    },
    {
      id: "c2",
      title: "New market entry to delivery",
      subtitle: "Where we expand, then how we operate there",
      distinction: "Market Intelligence finds attractive markets. Market Expansion decides whether and how to enter. Provider Operations builds capacity. Marketing creates demand. Lead Management & Sales converts enquiries. Customer Operations delivers.",
      steps: [
        { pack: "mi", action: "Identifies attractive markets/areas" },
        { pack: "me", action: "Determines whether and how to enter" },
        { pack: "po", action: "Builds required provider capacity" },
        { pack: "mkt", action: "Creates demand in the new market" },
        { pack: "lms", action: "Converts enquiries" },
        { pack: "co", action: "Delivers the service" }
      ]
    },
    {
      id: "c4",
      kind: "boundary",
      title: "Provider network to service delivery",
      subtitle: "Provider Operations owns the network. Customer Operations owns the booking.",
      distinction: "Customer Operations sends the provider requirement. Provider Operations finds a suitable available provider. Customer Operations then coordinates the customer and booking through service delivery. Quality Management returns provider performance feedback to Provider Operations.",
      steps: [
        { pack: "po", action: "Maintains provider network" },
        { pack: "co", action: "Sends provider requirement" },
        { pack: "po", action: "Finds suitable available provider" },
        { pack: "co", action: "Coordinates customer + booking" },
        { pack: "qm", action: "Returns provider performance feedback" }
      ],
      chain: [
        { pack: "po" },
        { action: "Maintains provider network" },
        { pack: "co" },
        { action: "Sends provider requirement" },
        { pack: "po" },
        { action: "Finds suitable available provider" },
        { pack: "co" },
        { action: "Coordinates customer + booking" },
        { artifact: "Service delivery" },
        { pack: "qm" },
        { action: "Provider performance feedback" },
        { pack: "po" }
      ]
    },
    {
      id: "c5",
      title: "Revenue flow",
      subtitle: "Completed service becomes bill, collection, commission, payout, then closed settlement.",
      distinction: "Revenue & Settlement Management is transaction-level control. Financial Planning & Control is business-level financial control. They must not be mixed.",
      steps: [
        { pack: "co", action: "Service completed" },
        { pack: "rsm", action: "Customer billing & collections" },
        { pack: "rsm", action: "Revenue & commission calculation" },
        { pack: "rsm", action: "Reconciliation and settlement closed" }
      ]
    },
    {
      id: "c6",
      title: "Quality after fulfilment",
      subtitle: "Quality checks whether the process actually worked.",
      distinction: "Customer issue/support is Customer Experience. Provider network is Provider Operations. Service execution is Customer Operations. Quality standard, audit, failure analysis and corrective action is Quality Management.",
      steps: [
        { pack: "lms", action: "Confirmed booking" },
        { pack: "co", action: "Service fulfilment" },
        { pack: "po", action: "Service delivery capacity" },
        { pack: "qm", action: "Pass or investigate quality issue" }
      ]
    },
    {
      id: "c7",
      kind: "boundary",
      title: "Boundary with Finance",
      subtitle: "Transaction control versus business financial control.",
      distinction: "Revenue & Settlement Management owns billing, collections, commission, provider payouts and settlement. Financial Planning & Control owns budget, cash flow, financial planning, expense control, financial reporting and business financial decisions.",
      steps: [
        { pack: "rsm", action: "Transaction-level control" },
        { pack: "fpc", action: "Business-level financial control" }
      ]
    },
    {
      id: "c8",
      kind: "boundary",
      title: "People data to financial planning",
      subtitle: "HR owns the employee lifecycle. Finance owns the cost of people.",
      distinction: "People & HR manages headcount, recruitment, records, attendance, performance, training and employee lifecycle. Financial Planning & Control uses people data for salary budget, manpower cost, department budgets and financial planning.",
      steps: [
        { pack: "hr", action: "Employee lifecycle and people data" },
        { pack: "fpc", action: "Salary budget and manpower cost" }
      ]
    },
    {
      id: "c9",
      kind: "boundary",
      title: "Employees vs providers",
      subtitle: "People & HR owns employees. Provider Operations owns the operating network.",
      distinction: "Providers are part of the operating network, not employees by default. Recruitment, verification, activation, performance and status of providers stay under Provider Operations.",
      steps: [
        { pack: "hr", action: "Panun Kaergar employees" },
        { pack: "po", action: "Service providers" }
      ]
    },
    {
      id: "c10",
      kind: "boundary",
      title: "Technology vs Data",
      subtitle: "Systems and infrastructure versus the business information they carry.",
      distinction: "Technology covers applications, systems, integrations, infrastructure, access, backups and technical support. Data covers customer, provider, booking, financial, marketing, operational, KPI and reporting data.",
      steps: [
        { pack: "td", action: "Applications, systems, access, backups" },
        { pack: "td", action: "Business data and reporting" }
      ]
    },
    {
      id: "c11",
      title: "Full operating connection",
      subtitle: "Growth creates. Operations delivers. Control keeps the business in hand.",
      distinction: "The locked Panun Kaergar Management & Control architecture is now defined at module level.",
      steps: [
        { pack: "mi", action: "Growth" },
        { pack: "lms", action: "Operations" },
        { pack: "fpc", action: "Control" }
      ]
    },
    {
      id: "c12",
      kind: "boundary",
      title: "Customer Experience boundaries",
      subtitle: "CX supports the customer throughout and after service. It does not convert, fulfil, audit, or settle.",
      distinction: "Lead Management & Sales converts before booking. Customer Operations coordinates fulfilment after booking. Customer Experience covers communication, support, complaints, feedback and recovery throughout and after service. Quality Management independently audits. Revenue & Settlement owns billing, collections and settlements.",
      steps: [
        { pack: "lms", action: "Before booking — convert" },
        { pack: "co", action: "After booking — fulfil" },
        { pack: "cx", action: "Throughout + after — support" },
        { pack: "qm", action: "Independent quality control" },
        { pack: "rsm", action: "Financial transactions" }
      ]
    }
  ],
  packs: [
    {
      id: "mi",
      group: "growth",
      name: "Market Intelligence",
      short: "Understand the market",
      question: "Where and what should we grow?",
      status: "complete",
      role: "Market Intelligence Executive",
      reportsTo: "Growth Function",
      purpose: "Keep one truthful picture of demand, competitors, market change and opportunities so Growth decides from evidence. MI does not design offers, run ads, convert enquiries, enter new areas, or recruit providers.",
      distinction: "A is our demand numbers. B is named competitors. C is the outside world (seasons, habits, segments). D names an opportunity. E decides go / no-go. F keeps the registers clean and the reports on time. Those six jobs must not copy each other.",
      responsibilities: [
        {
          letter: "A",
          title: "Customer & Demand Research",
          art: "os-art/pk-mi-resp-a.png",
          purpose: "Know what people in our catchments are asking for, booking, and failing to get — by service and by area.",
          owns: "Demand truth from Panun Kaergar enquiries, bookings and unmet requests.",
          not: "Does not own competitor files (B), external trend essays (C), or turning a gap into an opportunity card (D).",
          registers: ["Customer Demand Tracker", "Service Demand Tracker", "Area Demand Tracker"],
          sop: "MI-SOP-02",
          items: [
            "Close each week’s Customer, Service and Area demand trackers from the CRM extract — not from memory.",
            "Separate catalogue demand (services we already sell) from unmet demand (asked, not sold).",
            "Flag a service or area that is up or down versus the last four weeks.",
            "Hand a recurring unmet request to Opportunity Identification (D) as a signal — do not open an opportunity yourself if you are only counting."
          ]
        },
        {
          letter: "B",
          title: "Competitor Intelligence",
          art: "os-art/pk-mi-resp-b.png",
          purpose: "Know who else a Kashmir household could hire instead of Panun Kaergar, and what they just changed.",
          owns: "Named competitor master records and dated change observations.",
          not: "Does not own our own enquiry counts (A), and does not write marketing claims (Marketing).",
          registers: ["Competitor Database", "Competitor Monitoring Sheet"],
          sop: "MI-SOP-03",
          items: [
            "Keep an approved watch-list. A competitor not on the list is not ‘monitored’.",
            "One database row per competitor (who they are). One monitoring row per change (what moved).",
            "Record price, offer, area and review changes with source and date the same day they are seen.",
            "If a change can steal our bookings this week, escalate to Growth within one working day — do not wait for the weekly report."
          ]
        },
        {
          letter: "C",
          title: "Market Research",
          art: "os-art/pk-mi-resp-c.png",
          purpose: "See the outside world that our CRM cannot see: seasons, household habits, new service categories, underserved segments.",
          owns: "External evidence in the Market Trend Tracker.",
          not: "Does not re-count our bookings (A) and does not maintain competitor cards (B).",
          registers: ["Market Trend Tracker"],
          sop: "MI-SOP-04",
          items: [
            "Only log a trend that can change what we sell, where we sell, or how customers buy.",
            "Every trend needs a first-seen date, a source, a likely effect on named services or areas, and a review date.",
            "Approved sources only: CX/Quality monthly themes, field notes, public listings, seasonal calendars — not rumours.",
            "A trend that implies a new service, area or channel is a signal to D, not a validation."
          ]
        },
        {
          letter: "D",
          title: "Opportunity Identification",
          art: "os-art/pk-mi-resp-d.png",
          purpose: "Turn a demand spike, competitor move, trend or CX/Quality theme into one named row in the Opportunity Register.",
          owns: "The register row from New until it is assigned for validation.",
          not: "Does not stamp Validated / Rejected (E). Does not write a service definition (Offer & Service Development).",
          registers: ["Opportunity Register"],
          sop: "MI-SOP-05",
          items: [
            "One opportunity = one hypothesis (new service / new area / segment / partnership / stop-or-shrink).",
            "Link the evidence (which tracker row, competitor observation, or trend ID).",
            "Set type and first-cut priority. Assign an owner for validation. Status becomes Validate.",
            "Do not create a second opportunity for the same hypothesis — update the existing row."
          ]
        },
        {
          letter: "E",
          title: "Opportunity Validation",
          art: "os-art/pk-mi-resp-e.png",
          purpose: "Decide, with evidence, whether Growth should act — or stop.",
          owns: "The decision and the Opportunity Validation Report.",
          not: "Does not design the offer, enter the area, sign a partner, or run an ad.",
          registers: ["Opportunity Register", "Opportunity Validation Report"],
          sop: "MI-SOP-06",
          items: [
            "Test demand, competition, operational/provider feasibility, and a rough revenue sketch.",
            "Close only as Validated, Not validated, or Further research — never ‘interesting’.",
            "Validated service → Offer & Service Development. Validated area → Market Expansion. Validated channel → Partnerships. Stop-or-shrink → Growth Function.",
            "If a High priority opportunity sits more than 10 working days without a decision, that is a miss on the KPI — not a parking lot."
          ]
        },
        {
          letter: "F",
          title: "Reporting & Data Management",
          art: "os-art/pk-mi-resp-f.png",
          purpose: "Make the registers trustworthy and turn them into weekly and monthly reports. No new research lives here.",
          owns: "Market Intelligence Database hygiene, weekly report, monthly report, same-day critical alerts.",
          not: "Does not invent insights that are not already in A–E registers.",
          registers: ["Market Intelligence Database", "Weekly Market Intelligence Report", "Monthly Market Intelligence Report"],
          sop: "MI-SOP-01 · MI-SOP-07 · MI-SOP-08",
          items: [
            "Every new fact enters through the MI Database (source, date, owner) before it is trusted in a tracker.",
            "Weekly report is compiled from closed weekly trackers — not from WhatsApp memory.",
            "Monthly report is the decision pack: grow / stop / research, plus the opportunity funnel.",
            "Duplicates, blank owners, and rows older than their update cycle are SOP-07 work, not optional tidying."
          ]
        }
      ],
      cadence: {
        daily: [
          "Ingest yesterday’s CRM extract into the three demand trackers (SOP-02) if the extract arrived.",
          "Log any competitor or trend observation with source + date (SOP-03 / SOP-04).",
          "Escalate a booking-threatening competitor or market change to Growth the same working day.",
          "File the source in the Market Intelligence Database (SOP-01) before treating the fact as official."
        ],
        weekly: [
          "Close week: Service, Area and Customer demand trackers marked Complete for that week.",
          "Walk the competitor watch-list; one monitoring row per change, or ‘no change’ dated.",
          "Create or update opportunity rows from this week’s signals (SOP-05).",
          "Compile and submit the Weekly Market Intelligence Report (SOP-08) from the registers.",
          "Hand any newly Validated opportunities to the owning module the same week."
        ],
        monthly: [
          "Full review of every Active competitor in the Competitor Database (not only those that moved).",
          "Four-week demand vs previous four weeks, by service and by area.",
          "Opportunity funnel: New / Validate / Validated / Rejected / Handed over — no silent rows.",
          "Clear validation backlog: every assigned High opportunity decided or dated Further research.",
          "Submit Monthly Market Intelligence Report. Set next month’s research list (what we will actually collect)."
        ]
      },
      sops: [
        {
          id: "MI-SOP-01",
          title: "Market Data Collection",
          art: "os-art/pk-mi-sop-01.png",
          purpose: "Get a fact from an approved source into the Market Intelligence Database so later analysis is not based on chat.",
          when: "Whenever a new extract, listing, field note, CX/Quality report, or competitor observation arrives — at least every working day when CRM extract is due.",
          uses: ["Market Intelligence Database"],
          inputs: ["CRM enquiry/booking extract", "Approved public listings", "CX or Quality monthly theme", "Field note with author"],
          output: "A Database row with source, date of data, date collected, owner, and where the fact now lives (which tracker).",
          steps: [
            "Accept only an approved source. If the source is not on the list, park it and ask Growth — do not mix it into trackers.",
            "Create or update a Market Intelligence Database row: Record ID, dataset type, source name, source type, date of data, date collected, collected by.",
            "Mark Verified only after you can re-open the source. Unverified facts may not drive an opportunity.",
            "Point ‘Storage location’ at the live register (e.g. Service Demand Tracker W37) — the Database is an index, not a second copy of all numbers.",
            "Set Next update due (daily extract = next working day; competitor full review = month-end).",
            "Only then may SOP-02 / 03 / 04 write into their trackers from this source."
          ]
        },
        {
          id: "MI-SOP-02",
          title: "Customer Demand Analysis",
          art: "os-art/pk-mi-sop-02.png",
          purpose: "Turn raw enquiries and bookings into three non-overlapping demand views: what people asked, what we sell, where it is.",
          when: "After each CRM extract is indexed in the MI Database. Close the week every Friday (or last working day).",
          uses: ["Customer Demand Tracker", "Service Demand Tracker", "Area Demand Tracker"],
          inputs: ["Verified CRM extract (SOP-01)", "Live service catalogue names from Offer & Service Development"],
          output: "Completed weekly rows on all three demand trackers, with rising / falling / stable flags.",
          steps: [
            "Pull only Verified extracts. Count enquiries and bookings for the ISO week.",
            "Service Demand Tracker: one row per catalogue service × week. Fill enquiries, bookings, completed jobs if available, vs 4-week average, direction.",
            "Area Demand Tracker: one row per operating area × week. Fill enquiries, bookings, top 3 services, whether we have provider coverage (from last known PO snapshot — do not invent).",
            "Customer Demand Tracker: one row per customer-worded request theme × week (e.g. ‘AC gas refill’, ‘evening plumber’). Map to a catalogue service if one exists; else mark Unmet = Yes.",
            "Do not put competitor prices here. Do not open an opportunity here — send a signal to SOP-05 if Unmet repeats or a service/area moves >25% vs 4-week average.",
            "Mark the week Complete. Incomplete weeks cannot feed the Weekly Report."
          ]
        },
        {
          id: "MI-SOP-03",
          title: "Competitor Analysis",
          art: "os-art/pk-mi-sop-03.png",
          purpose: "Keep the watch-list true, and log what actually changed — not a weekly essay.",
          when: "Any observed change, same working day. Full database review monthly.",
          uses: ["Competitor Database", "Competitor Monitoring Sheet"],
          inputs: ["Approved competitor list", "Public listings, customer mentions from CX, field notes"],
          output: "Updated competitor master and/or a dated monitoring observation.",
          steps: [
            "If the name is not in the Competitor Database, add a master row (name, type local/national, areas, services, price band, last review date, status Active/Watch).",
            "If something moved (price, offer, area, reviews), add a Monitoring Sheet row: date, competitor ID, what changed, source, likely impact on PK, action Watch / Brief Marketing / Escalate.",
            "Do not rewrite the master instead of logging a change — the sheet is the history.",
            "Compare only facts we can show. ‘They are cheaper’ without a figure is not a row.",
            "If impact is ‘can steal bookings this week’, escalate to Growth within one working day and note it on the Weekly Report alerts.",
            "Monthly: open every Active competitor, confirm still exists, refresh price band, date Last full review."
          ]
        },
        {
          id: "MI-SOP-04",
          title: "Market Trend Monitoring",
          art: "os-art/pk-mi-sop-04.png",
          purpose: "Record external change that our CRM will not show by itself.",
          when: "When a qualifying signal appears. Review open trends monthly.",
          uses: ["Market Trend Tracker"],
          inputs: ["Seasonal calendar", "CX/Quality themes", "Field notes", "Public market information on the approved list"],
          output: "A trend row with evidence, affected services/areas, confidence, next review date.",
          steps: [
            "Ask: can this change what we sell, where, or how people buy? If no, do not log it.",
            "Create trend ID, theme (season / habit / regulation / category / segment), first-seen date, source.",
            "Name the PK services or areas that would feel it. Confidence High / Medium / Low.",
            "If the trend implies a new service, area or channel, raise a signal for SOP-05 — the trend row is not the opportunity.",
            "Set a review date. On review, mark Still true / Faded / Became opportunity OPP-xx.",
            "Never paste CRM booking totals into this tracker."
          ]
        },
        {
          id: "MI-SOP-05",
          title: "Opportunity Identification",
          art: "os-art/pk-mi-sop-05.png",
          purpose: "Give Growth one named hypothesis per idea, with a pointer to evidence.",
          when: "When demand, competitor, trend, CX or Quality produces a repeatable signal.",
          uses: ["Opportunity Register"],
          inputs: ["Closed demand weeks", "Monitoring observations", "Trend IDs", "CX/Quality handoff objects"],
          output: "Opportunity row in status New then Validate, with type and evidence links.",
          steps: [
            "Search the register first. Same hypothesis = update the old row, do not duplicate.",
            "Write one sentence hypothesis: ‘We should sell X / enter Y / partner for Z / stop W because …’",
            "Set type: New service / New area / Segment / Partnership / Stop-or-shrink.",
            "Paste evidence IDs (e.g. SDT-W37-AC, CMS-044, TR-012, CX monthly).",
            "First-cut priority High / Medium / Low. Assign validator. Status = Validate.",
            "Do not stamp Validated here. Do not brief Marketing."
          ]
        },
        {
          id: "MI-SOP-06",
          title: "Opportunity Validation",
          art: "os-art/pk-mi-sop-06.png",
          purpose: "Close the hypothesis with a decision Growth can hand off.",
          when: "Once an opportunity is in Validate. High priority within 10 working days.",
          uses: ["Opportunity Register", "Opportunity Validation Report"],
          inputs: ["Opportunity row", "Demand trackers", "Competitor files", "Feasibility notes from PO/CO if asked"],
          output: "Validation Report plus register status Validated / Not validated / Further research, and a handoff if Validated.",
          steps: [
            "Open a Validation Report with the OPP-ID. Restate the hypothesis.",
            "Demand: numbers from A (counts, unmet, area). If numbers are missing, send back to SOP-02 — do not guess.",
            "Competition: relevant B rows. ‘No competitor found’ is a finding, not a skip.",
            "Feasibility: can we deliver? Ask Provider Operations or Customer Operations for a yes/no/unknown — MI does not invent provider capacity.",
            "Rough revenue: order-of-magnitude from price band × likely jobs, labelled Estimate.",
            "Decide: Validated / Not validated / Further research. Name the receiving module if Validated (SD, ME, PC, or Growth for stop).",
            "Hand the complete object (SOP handoff). Incomplete packages are not ‘almost validated’."
          ]
        },
        {
          id: "MI-SOP-07",
          title: "Data Management",
          art: "os-art/pk-mi-sop-07.png",
          purpose: "Keep one row for one thing, with an owner and a date.",
          when: "Weekly hygiene plus any time a duplicate or blank owner is found.",
          uses: ["Market Intelligence Database", "All MI registers"],
          inputs: ["Live registers"],
          output: "No duplicate OPP or competitor IDs; no official row without source, date, owner.",
          steps: [
            "Scan Opportunity Register for duplicate hypotheses; merge into the earliest ID, log the merge in the Database.",
            "Scan Competitor Database for duplicate names/aliases; keep one master, point monitoring rows at it.",
            "Reject (or mark Unverified) any tracker row whose MI Database source is missing.",
            "Archive Inactive competitors and Faded trends — do not delete history.",
            "List rows past their Next update due. Update or explain.",
            "Hygiene is finished when the Weekly Report can be built without opening WhatsApp."
          ]
        },
        {
          id: "MI-SOP-08",
          title: "Market Intelligence Reporting",
          art: "os-art/pk-mi-sop-08.png",
          purpose: "Compile what the registers already know. Reporting is not a new research exercise.",
          when: "Weekly report every week. Monthly report at month-end. Critical alert the same day.",
          uses: ["Weekly Market Intelligence Report", "Monthly Market Intelligence Report"],
          inputs: ["Closed weekly trackers", "Monitoring sheet", "Opportunity Register", "Open trends"],
          output: "Submitted weekly or monthly report, archived, with recommendations that point at register IDs.",
          steps: [
            "Do not collect new field data inside this SOP. If a number is missing, send back to 01–04.",
            "Weekly: fill the weekly template (demand moves, competitor moves, new opportunities, alerts, recommendations for this week).",
            "Every recommendation names a receiving function and an OPP-ID or tracker ID.",
            "Monthly: fill the monthly template (4-week demand, competitor landscape, opportunity funnel, grow/stop/research, next month’s collection list).",
            "Submit to Growth Function. Archive in the report store. Mark report date on the MI Database.",
            "If a Validated opportunity was not handed this week, that is an exception on the report — not a footnote."
          ]
        }
      ],
      kpis: [
        { group: "Data & Research", name: "Rows with source, date and owner", target: "≥95% of live register rows" },
        { group: "Data & Research", name: "Weekly demand trackers closed on time", target: "100% of operating weeks" },
        { group: "Data & Research", name: "CRM extract indexed same working day", target: "100%" },
        { group: "Market Monitoring", name: "Watch-list coverage", target: "100% of Active competitors dated this month" },
        { group: "Market Monitoring", name: "Service demand coverage", target: "100% of catalogue services in the week’s tracker" },
        { group: "Market Monitoring", name: "Area demand coverage", target: "100% of live service areas in the week’s tracker" },
        { group: "Opportunities", name: "New opportunities with evidence links", target: "≥4 per month (not empty ideas)" },
        { group: "Opportunities", name: "High-priority validation cycle time", target: "Decision within 10 working days" },
        { group: "Opportunities", name: "Assigned validations closed", target: "100% of opportunities in Validate" },
        { group: "Reporting", name: "Weekly report submitted", target: "100% by agreed weekday" },
        { group: "Reporting", name: "Critical market/competitor alert", target: "Within 1 working day" },
        { group: "Reporting", name: "Monthly report submitted", target: "100% within 3 working days of month-end" }
      ],
      records: ["Market Intelligence Database","Customer Demand Tracker","Service Demand Tracker","Area Demand Tracker","Competitor Database","Competitor Monitoring Sheet","Market Trend Tracker","Opportunity Register","Opportunity Validation Report","Weekly Market Intelligence Report","Monthly Market Intelligence Report"],
      formSpecs: [
        {
          name: "Market Intelligence Database",
          kind: "Index — not a second copy of the numbers",
          oneRow: "One incoming source / dataset, so we can trust later trackers.",
          notHere: "Do not copy weekly enquiry or booking counts into this file. It is an index of sources. The numbers live in the three demand trackers.",
          usedBy: "MI-SOP-01, MI-SOP-07",
          fields: [
            { name: "Record ID", example: "MID-0142", note: "Never reuse" },
            { name: "Dataset type", example: "Demand extract", note: "Demand / Competitor / Trend / Opportunity / Report" },
            { name: "Source name", example: "PK CRM bookings export", note: "Must be on the approved source list" },
            { name: "Source type", example: "CRM extract", note: "CRM / listing / field note / CX report / Quality report" },
            { name: "Date of data", example: "2026-W37", note: "The week or day the fact is about" },
            { name: "Date collected", example: "2026-09-15", note: "When MI filed it" },
            { name: "Collected by", example: "Aamir, MI Executive", note: "Named person" },
            { name: "Verification", example: "Verified", note: "Verified only if source can be re-opened" },
            { name: "Storage location", example: "Service Demand Tracker W37", note: "Which live register holds the numbers" },
            { name: "Next update due", example: "2026-09-16", note: "Daily extract = next working day" }
          ],
          sample: [
            { "Record ID": "MID-0142", "Dataset type": "Demand extract", "Source": "PK CRM", "Date of data": "2026-W37", "Verification": "Verified", "Lives in": "Service + Area + Customer trackers W37" },
            { "Record ID": "MID-0148", "Dataset type": "Competitor", "Source": "Public listing — CoolHome Srinagar", "Date of data": "2026-09-16", "Verification": "Verified", "Lives in": "CMS-044" }
          ]
        },
        {
          name: "Customer Demand Tracker",
          kind: "What people asked, in their words",
          oneRow: "One request theme × one week (not necessarily a catalogue service).",
          notHere: "Do not list catalogue services here — that is the Service Demand Tracker. Do not total a whole town here — that is the Area Demand Tracker. Keep the customer’s words.",
          usedBy: "MI-SOP-02",
          fields: [
            { name: "Week", example: "2026-W37", note: "ISO week" },
            { name: "Request (customer words)", example: "AC gas refill", note: "Do not tidy into catalogue language yet" },
            { name: "Mapped catalogue service", example: "None — not sold", note: "Blank or service code if we already sell it" },
            { name: "Area", example: "Srinagar City", note: "Where they asked from" },
            { name: "Enquiry count", example: "11", note: "From CRM" },
            { name: "Unmet", example: "Yes", note: "Yes if we cannot book this today" },
            { name: "Signal to D?", example: "Yes — third week in a row", note: "Only if repeating or spiking" }
          ],
          sample: [
            { Week: "2026-W37", Request: "AC gas refill", Mapped: "—", Area: "Srinagar City", Enquiries: "11", Unmet: "Yes" },
            { Week: "2026-W37", Request: "evening plumber", Mapped: "Plumbing — general", Area: "Anantnag", Enquiries: "6", Unmet: "Partial (no evening slots)" }
          ]
        },
        {
          name: "Service Demand Tracker",
          kind: "What we already sell",
          oneRow: "One catalogue service × one week.",
          notHere: "Do not write unmet customer phrasing here (Customer Demand Tracker). Do not put competitor prices here. Do not mix several services on one row.",
          usedBy: "MI-SOP-02, Weekly report",
          fields: [
            { name: "Week", example: "2026-W37", note: "" },
            { name: "Service", example: "AC servicing", note: "Catalogue name from Offer & Service Development" },
            { name: "Enquiries", example: "48", note: "" },
            { name: "Bookings", example: "19", note: "" },
            { name: "Completed jobs", example: "16", note: "If Operations extract available" },
            { name: "vs 4-week average", example: "Bookings +32%", note: "Direction uses this, not last week alone" },
            { name: "Direction", example: "Rising", note: "Rising / Falling / Stable" }
          ],
          sample: [
            { Week: "2026-W37", Service: "AC servicing", Enquiries: "48", Bookings: "19", vs4w: "+32%", Direction: "Rising" },
            { Week: "2026-W37", Service: "Water heater repair", Enquiries: "9", Bookings: "3", vs4w: "−18%", Direction: "Falling" }
          ]
        },
        {
          name: "Area Demand Tracker",
          kind: "Where demand sits",
          oneRow: "One live (or watched) area × one week.",
          notHere: "Do not explode every catalogue service in this file — top 3 services asked is enough. Do not invent provider coverage; ask Provider Operations.",
          usedBy: "MI-SOP-02, Market Expansion signals",
          fields: [
            { name: "Week", example: "2026-W37", note: "" },
            { name: "Area", example: "Pulwama", note: "Named catchment, not ‘south’" },
            { name: "Enquiries", example: "14", note: "" },
            { name: "Bookings", example: "2", note: "" },
            { name: "Top 3 services asked", example: "AC; plumbing; wiring", note: "" },
            { name: "Provider coverage", example: "No", note: "Last known from Provider Operations — MI does not guess" },
            { name: "Uncovered demand", example: "12 enquiries we could not book", note: "Signal to D / Expansion" }
          ],
          sample: [
            { Week: "2026-W37", Area: "Srinagar City", Enquiries: "90", Bookings: "41", Coverage: "Yes", Uncovered: "4" },
            { Week: "2026-W37", Area: "Pulwama", Enquiries: "14", Bookings: "2", Coverage: "No", Uncovered: "12" }
          ]
        },
        {
          name: "Competitor Database",
          kind: "Who they are",
          oneRow: "One competitor organisation.",
          notHere: "Do not log ‘what changed this week’ here — that is the Monitoring Sheet. This file is who they are, not the history of moves.",
          usedBy: "MI-SOP-03 monthly review",
          fields: [
            { name: "Competitor ID", example: "CMP-07", note: "" },
            { name: "Name", example: "CoolHome Srinagar", note: "Trading name" },
            { name: "Type", example: "Local", note: "Local / national / informal" },
            { name: "Areas", example: "Srinagar City, Ganderbal", note: "" },
            { name: "Services overlap", example: "AC install, AC gas, servicing", note: "Against our catalogue" },
            { name: "Price band", example: "AC service ₹700–900", note: "Band, not a secret spreadsheet of every SKU" },
            { name: "Status", example: "Active", note: "Active / Watch / Inactive" },
            { name: "Last full review", example: "2026-09-30", note: "Monthly for Active" }
          ],
          sample: [
            { ID: "CMP-07", Name: "CoolHome Srinagar", Type: "Local", Areas: "Srinagar City", Overlap: "AC", Status: "Active" },
            { ID: "CMP-12", Name: "ValleyFix", Type: "Local", Areas: "Anantnag, Pulwama", Overlap: "Plumbing, electrical", Status: "Watch" }
          ]
        },
        {
          name: "Competitor Monitoring Sheet",
          kind: "What moved",
          oneRow: "One dated observation of a change.",
          notHere: "Do not create a second master record here. If the name is new, add the Competitor Database row first, then log the change against that ID.",
          usedBy: "MI-SOP-03 daily, Weekly report alerts",
          fields: [
            { name: "Observation ID", example: "CMS-044", note: "" },
            { name: "Date seen", example: "2026-09-16", note: "Same day as collection" },
            { name: "Competitor ID", example: "CMP-07", note: "Must exist in Database" },
            { name: "What changed", example: "AC servicing offer ₹499 this week", note: "Price / offer / area / reviews" },
            { name: "Source", example: "Their Instagram listing", note: "Re-openable" },
            { name: "Impact on PK", example: "Can undercut our AC servicing in Srinagar this week", note: "" },
            { name: "Action", example: "Escalate to Growth; brief Marketing not to match unverified", note: "Watch / Brief / Escalate" }
          ],
          sample: [
            { ID: "CMS-044", Date: "2026-09-16", Competitor: "CMP-07 CoolHome", Change: "₹499 AC service week", Action: "Escalate" },
            { ID: "CMS-045", Date: "2026-09-17", Competitor: "CMP-12 ValleyFix", Change: "Now listing Pulwama", Action: "Watch — signal to Expansion" }
          ]
        },
        {
          name: "Market Trend Tracker",
          kind: "Outside world",
          oneRow: "One external trend, not a booking total.",
          notHere: "Do not paste CRM booking totals here (Service / Area trackers). Do not log a competitor price change here (Monitoring Sheet). A trend is something outside our CRM.",
          usedBy: "MI-SOP-04",
          fields: [
            { name: "Trend ID", example: "TR-012", note: "" },
            { name: "Theme", example: "Season", note: "Season / habit / regulation / category / segment" },
            { name: "Statement", example: "AC servicing demand rises sharply from mid-May in Srinagar", note: "One sentence" },
            { name: "First seen", example: "2026-05-12", note: "" },
            { name: "Evidence", example: "Three summers of Service Demand Tracker + CX heat complaints", note: "" },
            { name: "Affects", example: "AC servicing, AC gas; Srinagar City", note: "Named services/areas" },
            { name: "Confidence", example: "High", note: "High / Medium / Low" },
            { name: "Review date", example: "2026-10-01", note: "Still true / Faded / Became OPP-xx" }
          ],
          sample: [
            { ID: "TR-012", Theme: "Season", Statement: "AC peak from mid-May in Srinagar", Affects: "AC services", Confidence: "High" },
            { ID: "TR-018", Theme: "Habit", Statement: "More households asking for evening slots after 6pm", Affects: "Plumbing, electrical", Confidence: "Medium" }
          ]
        },
        {
          name: "Opportunity Register",
          kind: "One hypothesis per row",
          oneRow: "One growth idea with evidence and a status.",
          notHere: "Do not paste the evidence narrative here — link tracker / CMS / trend IDs. The decision write-up lives in the Opportunity Validation Report, not in extra register columns.",
          usedBy: "MI-SOP-05, MI-SOP-06",
          fields: [
            { name: "OPP-ID", example: "OPP-019", note: "Never reuse" },
            { name: "Type", example: "New service", note: "New service / New area / Segment / Partnership / Stop-or-shrink" },
            { name: "Hypothesis", example: "Sell AC gas refill as its own bookable service in Srinagar City", note: "One sentence" },
            { name: "Evidence links", example: "CDT-W35..W37 gas refill; CMP-07 already sells it", note: "IDs, not paragraphs" },
            { name: "Priority", example: "High", note: "High / Medium / Low" },
            { name: "Status", example: "Validate", note: "New / Validate / Validated / Not validated / Further research / Handed over" },
            { name: "Validator", example: "Aamir", note: "Named" },
            { name: "Hand to", example: "Offer & Service Development (if Validated)", note: "Empty until E closes" },
            { name: "Decision date", example: "—", note: "Required for High within 10 working days" }
          ],
          sample: [
            { ID: "OPP-019", Type: "New service", Hypothesis: "AC gas refill as standalone SKU, Srinagar", Status: "Validate", Priority: "High" },
            { ID: "OPP-014", Type: "New area", Hypothesis: "Enter Pulwama for plumbing + electrical", Status: "Further research", Priority: "Medium" }
          ]
        }
      ],
      reportSpecs: [
        {
          name: "Opportunity Validation Report",
          when: "Each time SOP-06 closes an opportunity",
          reader: "The module that must act (SD, Expansion, Partnerships) and Growth Function",
          purpose: "A single opportunity’s evidence pack. Not a weekly roundup.",
          notThis: "Do not use this template to summarise the whole market. One OPP-ID only. If you are describing several ideas, you are writing the weekly or monthly report instead.",
          sections: [
            { title: "1. Identity", must: "OPP-ID, type, one-sentence hypothesis, author, date." },
            { title: "2. Demand evidence", must: "Counts from Customer/Service/Area trackers with week IDs. Say if unmet. No guessed numbers." },
            { title: "3. Competition", must: "Relevant CMP/CMS IDs, overlap, price band. Or an explicit ‘none found’ with sources checked." },
            { title: "4. Feasibility", must: "Can Operations/Providers deliver? Yes / No / Unknown with who was asked. MI does not invent capacity." },
            { title: "5. Revenue sketch", must: "Order-of-magnitude only, labelled Estimate (price band × likely jobs)." },
            { title: "6. Decision", must: "Validated / Not validated / Further research. If Further research: what fact is missing and who will collect it." },
            { title: "7. Handoff", must: "If Validated: receiving module, what they are expected to do next, attachments (register IDs)." }
          ],
          sampleLine: "OPP-019 Validated → Offer & Service Development: create AC gas refill as a sellable service for Srinagar City. Evidence: 11+9+8 unmet asks in W35–W37; CMP-07 already selling."
        },
        {
          name: "Weekly Market Intelligence Report",
          when: "Every week, compiled from closed registers (SOP-08)",
          reader: "Growth Function; Marketing reads the insight section; SD/ME/PC read new Validated items",
          purpose: "What moved this week and what Growth should do this week. No new fieldwork inside the report.",
          notThis: "Do not collect new field data while writing this. Do not turn it into a monthly strategy. If a number is missing, send it back to SOP-01–04.",
          sections: [
            { title: "1. Demand this week", must: "Services and areas that rose or fell vs 4-week average. Attach week ID. Unmet request themes." },
            { title: "2. Competitor movements", must: "CMS rows this week, or a dated ‘no change on watch-list’." },
            { title: "3. New or updated opportunities", must: "OPP-IDs opened, validated, rejected, or handed. None left ‘informal’." },
            { title: "4. Critical alerts", must: "Anything escalated within 1 working day — or an explicit None." },
            { title: "5. Recommendations this week", must: "Each line: do X, by whom (module), because (register ID)." },
            { title: "6. Backlog", must: "High opportunities still in Validate past 10 working days." }
          ],
          sampleLine: "W37: AC servicing bookings +32% vs 4-week avg (Srinagar). CoolHome ₹499 offer (CMS-044) escalated. OPP-019 still in Validate — due 22 Sep."
        },
        {
          name: "Monthly Market Intelligence Report",
          when: "Within 3 working days of month-end",
          reader: "Growth Function for grow / stop / research decisions",
          purpose: "A month’s pattern, not a thicker weekly. This is the pack that should change next month’s collection list.",
          notThis: "Do not paste four weekly reports end to end. Pattern, funnel, grow/stop/research, and what we will actually collect next month.",
          sections: [
            { title: "1. Four-week demand", must: "By service and by area vs the previous four weeks. Winners, losers, unmet themes that lasted the month." },
            { title: "2. Competitor landscape", must: "Every Active competitor reviewed (Last full review dated). Material changes this month." },
            { title: "3. Opportunity funnel", must: "Counts: New, Validate, Validated, Not validated, Handed over. List stuck rows." },
            { title: "4. Grow / stop / research", must: "Three lists with OPP or tracker IDs. Stop-or-shrink is allowed." },
            { title: "5. What we handed", must: "Validated objects sent to SD / ME / PC this month and whether they were accepted." },
            { title: "6. Next month’s collection list", must: "Which sources, competitors, areas we will actually watch — not a wish list." }
          ],
          sampleLine: "September: grow AC servicing + gas refill (OPP-019). Do not expand Pulwama until PO coverage exists (OPP-014 Further research). Watch evening-slot habit (TR-018)."
        }
      ],
      handoffExplain: {
        "New service we should sell": {
          meaning: "Customers keep asking for a job we do not sell yet. Research has checked the numbers. Service Development must now write the offer.",
          why: "Service Development must not invent services from a hunch.",
          completeWhen: "Opportunity ID, what was asked, area, count of asks, competitor, can we deliver, priority, report attached.",
          returnIf: "No count of real requests, or Validated with no report.",
          receiverDoes: "They write the offer. They do not run ads.",
          example: "OPP-019: 28 people in Srinagar asked for AC gas refill as its own visit, 1–21 Sep."
        },
        "Area we do not cover well": {
          meaning: "People from a named place are asking, and we barely serve them. Expansion must study it — this is not a launch order.",
          why: "Expansion decides entry. Research only proves the asks.",
          completeWhen: "Area name, weeks, enquiries vs bookings, coverage, top asks.",
          returnIf: "“South Kashmir” with no town name.",
          receiverDoes: "They research whether to enter. They do not tell Marketing to advertise.",
          example: "Pulwama town: 14 enquiries, 2 bookings, we do not serve it. Plumbing and electrical."
        },
        "This week's demand brief": {
          meaning: "What Marketing may talk about this week, where, and which claims are forbidden.",
          why: "Marketing must not invent demand or prices Research has not cleared.",
          completeWhen: "Week, push services, push areas, do not advertise, do not claim.",
          returnIf: "A dump of trackers with no priorities.",
          receiverDoes: "They plan ads on approved services in live areas only.",
          example: "15–21 Sep: push AC servicing in Srinagar and Bemina. Do not advertise Pulwama."
        },
        "Customers we cannot reach ourselves": {
          meaning: "A type of customer we miss unless a partner sends them. Not a signed partner.",
          why: "Partnerships qualifies partners. Research only names the gap.",
          completeWhen: "Who we miss, channel type, evidence.",
          returnIf: "A named uncle with no customer gap.",
          receiverDoes: "They find a real partner of that type.",
          example: "Gulmarg hotel guests asking at reception for same-day AC — we have no hotel partner."
        },
        "Quality problem that looks like a market problem": {
          meaning: "The same complaint keeps coming. That is research evidence, not one ticket.",
          why: "Research must not ignore Quality trends.",
          completeWhen: "Trend, service, area, period, quality pointer.",
          returnIf: "One complaint.",
          receiverDoes: "They log a trend. They do not run the audit.",
          example: "AC still warm after wet-service jobs, Srinagar, three weeks running."
        },
        "What customers keep saying this month": {
          meaning: "Repeated phrases after jobs. That is research input, not a Sales problem.",
          why: "Customer Experience hears language the booking form misses.",
          completeWhen: "Month, ratings, repeat theme, where.",
          returnIf: "A folder of raw tickets with no theme.",
          receiverDoes: "They update the trend list.",
          example: "August: 22 plumbing customers asked for an evening slot."
        }
      },
      output: ["Verified demand trackers", "Competitor watch-list and change log", "Trend register", "Opportunity Register with decisions", "Validation reports", "Weekly and monthly MI reports", "Handoffs to OSD, Expansion, Partnerships, Marketing"]
    },

    {
      id: "mkt",
      group: "growth",
      name: "Marketing",
      short: "Create awareness and demand",
      question: "How do we create awareness and demand?",
      status: "complete",
      role: "Marketing Executive",
      reportsTo: "Growth Function",
      purpose: "Create awareness, generate demand and consistently bring qualified customer enquiries for Panun Kaergar's services.",
      responsibilities: [
        { letter: "A", title: "Marketing Planning", items: ["Prepare monthly marketing plan","Define marketing objectives","Select services to promote","Select target customer segments","Select target locations","Select marketing channels","Define campaign budgets","Define campaign KPIs"] },
        { letter: "B", title: "Content & Creative Management", items: ["Identify content requirements","Prepare content briefs","Coordinate creative production","Create service-focused content","Create problem/solution content","Create educational content","Create promotional content","Create customer trust content","Maintain brand consistency","Maintain creative/content library"] },
        { letter: "C", title: "Digital Marketing", items: ["Manage Meta campaigns","Manage Google campaigns where applicable","Manage social media presence","Manage website marketing content","Monitor campaign performance","Monitor traffic and enquiries","Coordinate campaign optimization"] },
        { letter: "D", title: "Local Marketing", items: ["Identify high-potential local areas","Plan area-specific campaigns","Coordinate offline promotional activities","Manage local promotional material","Coordinate local partnerships where required","Track enquiries generated from offline activities"] },
        { letter: "E", title: "Campaign Management", items: ["Define campaign objective","Select service/offer","Define target audience","Define location","Prepare creative","Prepare campaign copy","Configure campaign","Launch campaign","Monitor performance","Optimize campaign","Close and document campaign"] },
        { letter: "F", title: "Lead Generation", items: ["Generate customer enquiries","Track source of every enquiry","Track campaign-generated leads","Identify high-performing channels","Identify low-performing channels","Coordinate with Lead Management & Sales"] },
        { letter: "G", title: "Brand Management", items: ["Maintain Panun Kaergar brand standards","Maintain approved logo usage","Maintain approved messaging","Maintain visual consistency","Maintain service communication standards","Prevent unapproved marketing claims"] },
        { letter: "H", title: "Marketing Reporting", items: ["Track campaign spend","Track reach and impressions","Track enquiries","Track cost per enquiry","Track qualified leads","Track bookings generated","Track cost per booking","Compare channels","Identify improvement areas","Submit marketing reports"] }
      ],
      cadence: {
        daily: ["Check active campaigns","Check campaign spend","Check incoming marketing leads","Check campaign performance","Review comments/messages where applicable","Record important marketing observations","Escalate major campaign issues"],
        weekly: ["Review all active campaigns","Review spend vs budget","Review enquiries by channel","Review cost per enquiry","Review qualified leads","Review bookings generated","Identify underperforming campaigns","Identify winning creatives/messages","Plan next week's content","Plan campaign changes","Submit Weekly Marketing Report"],
        monthly: ["Prepare monthly marketing plan","Review previous month's performance","Review marketing budget","Review channel performance","Review service-wise demand generated","Review area-wise demand generated","Review customer acquisition cost","Review creative performance","Identify marketing opportunities","Define next month's campaigns","Submit Monthly Marketing Report"]
      },
      sops: [
        { id: "MKT-SOP-01", title: "Marketing Planning", steps: ["Review business/growth objectives","Review Market Intelligence findings","Review previous marketing performance","Select priority services","Select target locations","Select target audiences","Select channels","Define budget","Define KPIs","Prepare marketing calendar","Submit plan for approval"] },
        { id: "MKT-SOP-02", title: "Content Production", steps: ["Identify content objective","Select service/topic","Define audience","Prepare creative brief","Produce copy/design/video","Check brand standards","Check factual accuracy","Add CTA","Approve creative","Store final version"] },
        { id: "MKT-SOP-03", title: "Campaign Launch", steps: ["Confirm campaign objective","Confirm audience","Confirm location","Confirm creative","Confirm landing destination","Confirm tracking","Set budget","Configure campaign","Review before publishing","Launch"] },
        { id: "MKT-SOP-04", title: "Campaign Monitoring", steps: ["Check spend","Check reach/impressions","Check clicks","Check enquiries","Check qualified leads","Check bookings","Compare against targets","Identify abnormal performance","Record action taken"] },
        { id: "MKT-SOP-05", title: "Campaign Optimization", steps: ["Identify underperforming campaign","Identify performance problem","Check audience","Check creative","Check offer/message","Check landing/lead process","Make controlled change","Monitor after change","Record result"] },
        { id: "MKT-SOP-06", title: "Lead Source Tracking", steps: ["Assign source to every enquiry","Record campaign","Record service requested","Record location","Track qualification","Track booking outcome","Calculate source performance"] },
        { id: "MKT-SOP-07", title: "Budget Control", steps: ["Record approved budget","Record daily spend","Compare spend against plan","Identify overspend risk","Stop/pause unauthorized overspending","Submit budget report"] },
        { id: "MKT-SOP-08", title: "Marketing Reporting", steps: ["Collect channel data","Collect lead data","Collect booking data","Calculate marketing KPIs","Compare with previous period","Identify what worked","Identify what did not work","Document actions","Submit report"] }
      ],
      kpis: [
        { name: "Marketing Plan Completion", target: "100% monthly" },
        { name: "Campaign Launch On-Time", target: "≥95%" },
        { name: "Content Calendar Completion", target: "≥95%" },
        { name: "Campaign Tracking Coverage", target: "100%" },
        { name: "Lead Source Identification", target: "≥95%" },
        { name: "Marketing Leads Generated", target: "Monthly target" },
        { name: "Qualified Leads Generated", target: "Monthly target" },
        { name: "Cost Per Lead", target: "Within approved target" },
        { name: "Cost Per Qualified Lead", target: "Within approved target" },
        { name: "Marketing-Generated Bookings", target: "Monthly target" },
        { name: "Cost Per Booking", target: "Within approved target" },
        { name: "Marketing Budget Variance", target: "≤10%" },
        { name: "Weekly Report Submission", target: "100% on time" },
        { name: "Monthly Report Submission", target: "100% on time" }
      ],
      records: ["Annual Marketing Plan","Monthly Marketing Plan","Marketing Calendar","Content Calendar","Creative Library","Campaign Register","Campaign Performance Tracker","Marketing Budget Tracker","Lead Source Tracker","Channel Performance Tracker","Weekly Marketing Report","Monthly Marketing Report"],
      output: ["Marketing Plan","Campaigns","Content & Creatives","Customer Reach","Enquiries","Qualified Leads","Bookings","Marketing Performance Data","Growth Insights"]
    },
    {
      id: "sd",
      group: "growth",
      name: "Offer & Service Development",
      short: "Create/improve what we sell",
      question: "What new/improved services should we sell?",
      status: "complete",
      role: "Service Development Executive",
      reportsTo: "Growth Function",
      purpose: "Identify, design, improve and validate service offerings that meet customer demand and can be delivered profitably through Panun Kaergar.",
      alias: "Originally listed as Service Development.",
      responsibilities: [
        { letter: "A", title: "Service Portfolio Management", items: ["Maintain complete service catalogue","Maintain service categories","Maintain individual services","Define service descriptions","Define service scope","Define service inclusions","Define service exclusions","Identify outdated services","Recommend service additions/removals"] },
        { letter: "B", title: "Customer Need Identification", items: ["Review Market Intelligence findings","Review customer enquiries","Review rejected/unfulfilled requests","Identify recurring customer problems","Identify missing services","Convert customer problems into service opportunities"] },
        { letter: "C", title: "New Service Development", items: ["Define proposed service","Define target customer","Define service scope","Define required tools/materials","Define provider skill requirements","Define service delivery process","Define quality requirements","Prepare service proposal"] },
        { letter: "D", title: "Existing Service Improvement", items: ["Review service complaints","Review service failures","Review customer feedback","Review provider feedback","Identify recurring problems","Improve service process","Improve service definition","Document changes"] },
        { letter: "E", title: "Service Feasibility", items: ["Check customer demand","Check provider availability","Check provider skill capability","Check required equipment","Check operational requirements","Check service-area feasibility","Estimate service economics","Identify operational risks"] },
        { letter: "F", title: "Service Pricing Input", items: ["Research market pricing","Identify customer price expectations","Identify service delivery costs","Identify provider payout requirements","Identify Panun Kaergar charges/commission impact","Submit pricing recommendation"] },
        { letter: "G", title: "Service Launch", items: ["Prepare approved service information","Coordinate service catalogue update","Coordinate provider capability requirements","Prepare customer-facing information","Coordinate marketing requirements","Coordinate operational readiness","Conduct controlled launch","Monitor initial performance"] },
        { letter: "H", title: "Service Performance Review", items: ["Track demand","Track bookings","Track fulfilment","Track complaints","Track cancellations","Track customer feedback","Track provider performance","Recommend continuation, improvement or withdrawal"] }
      ],
      cadence: {
        daily: ["Review new service requests","Review unusual/unfulfilled requests","Record potential service gaps","Review service-related complaints where relevant","Update service development records"],
        weekly: ["Review service demand","Review new service opportunities","Review rejected/unfulfilled service requests","Review customer feedback","Review provider feedback","Review active service-development projects","Submit Service Development Report"],
        monthly: ["Review complete service portfolio","Identify services requiring improvement","Identify potential new services","Review service profitability/economics","Review service quality data","Review service demand trends","Review newly launched services","Submit Monthly Service Portfolio Review"]
      },
      sops: [
        { id: "SD-SOP-01", title: "Service Opportunity Identification", steps: ["Collect demand signals","Review customer problems","Review unfulfilled requests","Identify recurring requirement","Define potential service","Record in Service Opportunity Register","Move qualified opportunity to validation"] },
        { id: "SD-SOP-02", title: "New Service Validation", steps: ["Define service hypothesis","Validate customer demand","Analyse competition","Check provider capability","Check operational requirements","Estimate economics","Identify risks","Document findings","Mark as: Approved for Development / Further Research / Rejected"] },
        { id: "SD-SOP-03", title: "Service Design", steps: ["Define service name","Define service scope","Define inclusions","Define exclusions","Define customer requirements","Define provider requirements","Define delivery steps","Define quality standards","Prepare Service Definition Sheet"] },
        { id: "SD-SOP-04", title: "Service Pricing Recommendation", steps: ["Collect market pricing","Estimate service delivery cost","Estimate provider payout","Consider Panun Kaergar charges","Analyse customer price sensitivity","Prepare pricing options","Submit recommendation for approval"] },
        { id: "SD-SOP-05", title: "Service Launch", steps: ["Confirm service approval","Confirm pricing","Confirm service definition","Confirm provider capability","Confirm operational readiness","Update service catalogue","Prepare marketing information","Launch service","Record launch date"] },
        { id: "SD-SOP-06", title: "Service Performance Review", steps: ["Collect service data","Review demand","Review bookings","Review fulfilment","Review complaints","Review cancellations","Review customer feedback","Review provider feedback","Prepare improvement action"] },
        { id: "SD-SOP-07", title: "Service Retirement", steps: ["Identify consistently underperforming service","Review demand","Review economics","Review operational problems","Document reason","Recommend withdrawal","Stop new promotion after approval","Update service catalogue","Archive service records"] }
      ],
      kpis: [
        { name: "Service Catalogue Accuracy", target: "100%" },
        { name: "Service Definition Completion", target: "100% active services" },
        { name: "Service Opportunity Review", target: "100% identified opportunities" },
        { name: "New Service Validation", target: "Within 15 working days" },
        { name: "Service Launch Readiness", target: "100% checklist completion" },
        { name: "New Services Launched", target: "Monthly target" },
        { name: "Service Improvement Projects Completed", target: "Monthly target" },
        { name: "Unfulfilled Demand Converted to Opportunities", target: "≥90% recorded" },
        { name: "Service Complaint Review", target: "100% reviewed" },
        { name: "Monthly Service Portfolio Review", target: "100% on time" }
      ],
      records: ["Service Catalogue","Service Definition Sheet","Service Opportunity Register","Service Validation Report","Service Development Register","Service Pricing Analysis","Service Launch Checklist","Service Performance Tracker","Service Improvement Register","Service Retirement Register"],
      output: ["Customer Need","Service Opportunity","Validation","Service Design","Operational Readiness","Service Launch","Performance Monitoring","Service Improvement"]
    },
    {
      id: "me",
      group: "growth",
      name: "Market Expansion",
      short: "Decide where to expand",
      question: "Where else can we operate and grow?",
      status: "complete",
      role: "Market Expansion Executive",
      reportsTo: "Growth Function",
      purpose: "Identify, evaluate and systematically enter new geographic areas, customer segments and markets where Panun Kaergar can operate profitably and sustainably.",
      responsibilities: [
        { letter: "A", title: "Expansion Opportunity Identification", items: ["Identify potential new areas","Identify underserved locations","Review customer demand outside current areas","Review service requests from new areas","Identify potential customer segments","Maintain Expansion Opportunity Register"] },
        { letter: "B", title: "Area Market Research", items: ["Research population/customer base","Research demand for home services","Identify high-demand services","Identify existing competitors","Research competitor coverage","Research local pricing","Identify local customer behaviour","Assess market potential"] },
        { letter: "C", title: "Service Availability Assessment", items: ["Identify services suitable for the new area","Check provider availability","Check provider density","Check provider skills","Identify service gaps","Determine initial service portfolio"] },
        { letter: "D", title: "Market Feasibility", items: ["Estimate potential demand","Estimate potential bookings","Estimate customer acquisition cost","Estimate operating requirements","Estimate expected revenue","Identify operational risks","Identify competitive risks","Prepare Market Expansion Feasibility Report"] },
        { letter: "E", title: "Expansion Planning", items: ["Define target area","Define launch services","Define provider requirements","Define marketing requirements","Define operational requirements","Define launch budget","Define launch KPIs","Prepare Expansion Plan"] },
        { letter: "F", title: "Market Launch", items: ["Confirm provider readiness","Confirm service readiness","Confirm customer operations readiness","Coordinate marketing launch","Launch controlled market entry","Monitor initial demand","Monitor fulfilment","Record launch performance"] },
        { letter: "G", title: "Expansion Performance", items: ["Track enquiries","Track bookings","Track fulfilment rate","Track customer acquisition cost","Track revenue","Track service coverage","Track provider availability","Track customer feedback","Recommend scale, improve, pause or exit"] }
      ],
      cadence: {
        daily: ["Review expansion-related enquiries","Record demand from uncovered areas","Review new market information","Track active expansion projects","Escalate significant market changes"],
        weekly: ["Review potential expansion areas","Review demand by area","Review provider availability","Review competitor coverage","Review active expansion projects","Update Expansion Opportunity Register","Submit Weekly Expansion Report"],
        monthly: ["Review expansion pipeline","Evaluate new market opportunities","Review active expansion performance","Compare planned vs actual results","Review expansion costs","Review service coverage","Identify next expansion opportunities","Submit Monthly Expansion Report"]
      },
      sops: [
        { id: "ME-SOP-01", title: "Area Opportunity Identification", steps: ["Collect demand signals","Identify uncovered area","Record customer/service demand","Check existing coverage","Record opportunity","Move qualified area to research"] },
        { id: "ME-SOP-02", title: "Area Market Research", steps: ["Define target area","Research customer base","Research service demand","Research competitors","Research pricing","Research service gaps","Record sources and findings","Prepare Area Market Report"] },
        { id: "ME-SOP-03", title: "Expansion Feasibility", steps: ["Assess demand","Assess competition","Assess provider availability","Assess operational requirements","Estimate acquisition cost","Estimate revenue potential","Identify risks","Prepare feasibility recommendation"] },
        { id: "ME-SOP-04", title: "Expansion Planning", steps: ["Define target area","Define launch services","Define provider requirement","Define marketing plan","Define operational requirements","Define budget","Define launch KPIs","Prepare Expansion Plan"] },
        { id: "ME-SOP-05", title: "Market Launch", steps: ["Confirm provider readiness","Confirm service readiness","Confirm operations readiness","Confirm marketing readiness","Launch market","Monitor first-period performance","Record launch results"] },
        { id: "ME-SOP-06", title: "Market Performance Review", steps: ["Collect enquiry data","Collect booking data","Review fulfilment","Review acquisition cost","Review revenue","Review provider coverage","Review customer feedback","Prepare corrective actions"] },
        { id: "ME-SOP-07", title: "Expansion Decision", steps: ["Review market performance","Compare against launch KPIs","Identify performance gaps","Identify required improvements","Prepare evidence","Recommend: Scale / Continue With Improvement / Pause / Exit"] }
      ],
      kpis: [
        { name: "Expansion Opportunity Identification", target: "Monthly target" },
        { name: "Area Research Completion", target: "100% of approved opportunities" },
        { name: "Feasibility Assessment", target: "Within 15 working days" },
        { name: "Expansion Plan Completion", target: "100% before launch" },
        { name: "Provider Readiness", target: "100% before launch" },
        { name: "Service Readiness", target: "100% before launch" },
        { name: "Launch Checklist Completion", target: "100%" },
        { name: "New Area Launches", target: "Approved monthly/quarterly target" },
        { name: "Expansion Budget Variance", target: "≤10%" },
        { name: "Expansion KPI Reporting", target: "100% on time" },
        { name: "Post-Launch Review Completion", target: "100%" }
      ],
      records: ["Expansion Opportunity Register","Area Market Research Report","Competitor Coverage Map","Area Demand Tracker","Provider Availability Assessment","Market Feasibility Report","Expansion Plan","Expansion Budget","Market Launch Checklist","Market Launch Report","Expansion Performance Tracker","Market Exit/Pause Report"],
      output: ["Expansion Opportunity","Market Research","Feasibility Assessment","Expansion Plan","Market Readiness","Controlled Launch","Performance Review","Scale / Improve / Pause / Exit"]
    },
    {
      id: "pc",
      group: "growth",
      name: "Partnerships & Channels",
      short: "Build external routes to customers/business",
      question: "What external channels can bring additional business?",
      status: "complete",
      role: "Partnerships & Channels Executive",
      reportsTo: "Growth Function",
      purpose: "Identify, establish, manage and grow external partnerships and business channels that generate customers, service opportunities, provider supply or strategic value for Panun Kaergar.",
      handoff: "Partner leads are assigned to Lead Management & Sales (PC-SOP-05).",
      responsibilities: [
        { letter: "A", title: "Partnership Opportunity Identification", items: ["Identify potential business partners","Identify customer acquisition channels","Identify referral channels","Identify corporate/institutional opportunities","Identify community/local channels","Identify complementary businesses","Maintain Partnership Opportunity Register"] },
        { letter: "B", title: "Partner Research & Qualification", items: ["Research potential partner","Understand partner's customer base","Identify partnership objective","Assess potential business value","Assess reputation and reliability","Assess operational compatibility","Qualify partnership opportunity"] },
        { letter: "C", title: "Partnership Development", items: ["Define partnership proposition","Define mutual benefits","Define responsibilities","Define referral/lead process","Define commercial terms","Prepare partnership proposal","Conduct partner discussions","Coordinate approval"] },
        { letter: "D", title: "Channel Development", items: ["Identify potential acquisition channels","Define channel purpose","Define channel process","Define channel economics","Set channel targets","Launch channel","Monitor channel performance"] },
        { letter: "E", title: "Partner Onboarding", items: ["Collect partner information","Verify required information","Finalize agreed terms","Define communication process","Define lead/referral process","Provide required materials","Train partner where required","Activate partnership"] },
        { letter: "F", title: "Partner Relationship Management", items: ["Maintain partner database","Maintain regular communication","Track partner activity","Resolve partnership issues","Review partner feedback","Identify additional opportunities","Maintain partner engagement"] },
        { letter: "G", title: "Partner Lead Management", items: ["Track leads received from partners","Record partner source","Track lead qualification","Track bookings","Track revenue generated","Track referral payments where applicable","Report channel contribution"] },
        { letter: "H", title: "Partnership Performance", items: ["Measure leads generated","Measure qualified leads","Measure bookings","Measure revenue","Measure acquisition cost","Measure partner activity","Identify underperforming partnerships","Recommend continuation, improvement or closure"] }
      ],
      cadence: {
        daily: ["Check active partnership communications","Follow up with prospective partners","Record new partnership opportunities","Track partner-generated enquiries","Resolve pending partnership issues","Update partnership records"],
        weekly: ["Review partnership pipeline","Follow up with prospective partners","Review active partner performance","Review partner-generated leads","Review bookings from channels","Review partner issues","Identify new channel opportunities","Submit Weekly Partnerships Report"],
        monthly: ["Review complete partner portfolio","Review leads by partner/channel","Review bookings by partner/channel","Review revenue generated","Review partnership costs","Review inactive partnerships","Identify new partnership opportunities","Plan partner engagement activities","Submit Monthly Partnerships Report"]
      },
      sops: [
        { id: "PC-SOP-01", title: "Partnership Opportunity Identification", steps: ["Identify potential partner/channel","Define potential value","Identify target customer/service connection","Record opportunity","Move qualified opportunity to research"] },
        { id: "PC-SOP-02", title: "Partner Qualification", steps: ["Collect partner information","Research partner","Assess customer/channel relevance","Assess reputation","Assess operational compatibility","Assess expected value","Mark as: Qualified / Further Review / Not Suitable"] },
        { id: "PC-SOP-03", title: "Partnership Proposal", steps: ["Define partnership objective","Define mutual benefits","Define responsibilities","Define lead/referral mechanism","Define commercial terms","Prepare proposal","Submit for approval","Present to partner"] },
        { id: "PC-SOP-04", title: "Partner Onboarding", steps: ["Confirm approval","Collect required information","Confirm agreed terms","Set up partner record","Explain operating process","Provide marketing/referral material","Test lead/referral process","Activate partner"] },
        { id: "PC-SOP-05", title: "Partner Lead Tracking", steps: ["Receive partner lead","Record partner source","Assign lead to Lead Management & Sales","Track lead status","Track booking outcome","Record revenue","Update partner performance"] },
        { id: "PC-SOP-06", title: "Partner Performance Review", steps: ["Collect partner activity","Calculate leads","Calculate qualified leads","Calculate bookings","Calculate revenue","Review costs","Review partner feedback","Prepare improvement action"] },
        { id: "PC-SOP-07", title: "Partner Relationship Management", steps: ["Schedule regular follow-up","Review pending issues","Collect feedback","Share relevant updates","Identify additional opportunities","Record interaction"] },
        { id: "PC-SOP-08", title: "Partnership Closure", steps: ["Identify reason for closure","Review outstanding leads/issues","Complete pending obligations","Disable referral/channel process","Update partner status","Archive partnership records"] }
      ],
      kpis: [
        { name: "Partnership Opportunities Identified", target: "Monthly target" },
        { name: "Qualified Partnership Opportunities", target: "Monthly target" },
        { name: "New Partnerships Activated", target: "Monthly target" },
        { name: "Partner Onboarding Completion", target: "100%" },
        { name: "Partner Lead Tracking", target: "100%" },
        { name: "Partner-Generated Leads", target: "Monthly target" },
        { name: "Partner-Generated Qualified Leads", target: "Monthly target" },
        { name: "Partner-Generated Bookings", target: "Monthly target" },
        { name: "Partner-Generated Revenue", target: "Monthly target" },
        { name: "Active Partner Rate", target: "≥80%" },
        { name: "Partner Review Completion", target: "100% monthly" },
        { name: "Partnership Report Submission", target: "100% on time" }
      ],
      records: ["Partnership Opportunity Register","Partner Database","Partner Qualification Form","Partnership Proposal","Partnership Agreement/Terms","Partner Onboarding Checklist","Partner Communication Log","Partner Lead Tracker","Channel Performance Tracker","Partner Performance Report","Weekly Partnerships Report","Monthly Partnerships Report"],
      output: ["Partnership Opportunity","Partner Qualification","Partnership Proposal","Partner Onboarding","Active Channel","Partner Leads","Bookings","Revenue","Partnership Performance"]
    },
    {
      id: "lms",
      group: "operations",
      name: "Lead Management & Sales",
      short: "Turn enquiries into bookings",
      question: "How do we turn enquiries into confirmed bookings?",
      status: "complete",
      role: "Sales Executive",
      reportsTo: "Operations Function",
      purpose: "Convert customer enquiries into confirmed service bookings by responding quickly, understanding the customer's requirement, qualifying the enquiry, communicating the approved service information and completing the booking process.",
      handoff: "Confirmed bookings are handed off to Customer Operations (LMS-SOP-04). Marketing and Partnerships & Channels generate enquiries; this module does not fulfil the job.",
      statuses: ["New","Contacted","Requirement Confirmed","Qualified","Booking Pending","Booking Confirmed","Follow-Up Required","Customer Not Responding","Lost","Invalid"],
      statusLabel: "Lead status",
      responsibilities: [
        { letter: "A", title: "Lead Intake", items: ["Receive customer enquiries","Receive leads from Marketing","Receive leads from Partnerships & Channels","Receive website/app/WhatsApp/call enquiries","Create lead record","Record customer information","Record requested service","Record location","Record enquiry source","Record enquiry date/time"] },
        { letter: "B", title: "Lead Response", items: ["Contact customer","Respond within defined response time","Confirm customer requirement","Confirm service location","Confirm preferred schedule","Explain applicable booking information","Record customer response"] },
        { letter: "C", title: "Lead Qualification", items: ["Identify required service","Determine whether service is supported","Confirm service location is covered","Identify urgency","Identify customer availability","Capture relevant job details","Identify special requirements","Classify lead status"] },
        { letter: "D", title: "Service & Booking Communication", items: ["Provide approved service information","Explain applicable visiting/service charges","Explain booking process","Explain expected next steps","Never invent pricing","Never promise unavailable services","Obtain customer confirmation"] },
        { letter: "E", title: "Booking Creation", items: ["Capture complete booking information","Confirm customer details","Confirm service requirement","Confirm location","Confirm schedule","Record agreed information","Create booking","Handoff booking to Customer Operations"] },
        { letter: "F", title: "Follow-up", items: ["Follow up on unanswered leads","Follow up on undecided customers","Follow up on incomplete bookings","Follow up on payment/confirmation where applicable","Record every follow-up","Close inactive leads according to SOP"] },
        { letter: "G", title: "Lost Lead Management", items: ["Record lost lead","Record reason for loss","Categorize loss reason","Identify recoverable leads","Send recurring loss patterns to Growth/Marketing"] },
        { letter: "H", title: "Sales Reporting", items: ["Track incoming leads","Track response time","Track qualified leads","Track bookings","Track lost leads","Track conversion rate","Track lead source","Submit sales reports"] }
      ],
      cadence: {
        daily: ["Check all new leads","Respond to new leads","Contact pending leads","Complete scheduled follow-ups","Convert qualified leads","Create confirmed bookings","Update every lead status","Record lost-lead reasons","Check unprocessed leads before end of day"],
        weekly: ["Review total leads","Review leads by source","Review response time","Review qualification rate","Review booking conversion","Review lost leads","Review reasons for lost leads","Review pending follow-ups","Identify sales process problems","Submit Weekly Sales Report"],
        monthly: ["Review monthly lead volume","Review conversion rate","Review source-wise conversion","Review service-wise conversion","Review area-wise conversion","Review lost-lead patterns","Review sales performance","Identify process improvements","Submit Monthly Sales Report"]
      },
      sops: [
        { id: "LMS-SOP-01", title: "Lead Intake", steps: ["Receive enquiry","Identify source","Create lead record","Record customer details","Record service requirement","Record location","Record enquiry time","Assign initial status = NEW"] },
        { id: "LMS-SOP-02", title: "First Response", steps: ["Open new lead","Contact customer","Introduce Panun Kaergar","Understand requirement","Confirm service","Confirm location","Confirm preferred time","Provide approved information","Update lead status"] },
        { id: "LMS-SOP-03", title: "Lead Qualification", steps: ["Confirm requested service","Check service availability","Check service-area coverage","Capture job details","Identify customer schedule","Identify special requirements","Classify lead","Update CRM/lead record"] },
        { id: "LMS-SOP-04", title: "Booking Conversion", steps: ["Confirm customer requirement","Confirm customer information","Confirm location","Confirm requested schedule","Explain applicable charges/process","Obtain customer confirmation","Create booking","Mark lead = BOOKING CONFIRMED","Handoff to Customer Operations"] },
        { id: "LMS-SOP-05", title: "Lead Follow-up", steps: ["Identify follow-up due","Contact customer","Ask whether service is still required","Address permitted questions","Attempt booking where appropriate","Record outcome","Schedule next follow-up","Close as Lost when follow-up limit is reached"] },
        { id: "LMS-SOP-06", title: "Lost Lead", steps: ["Confirm lead will not proceed","Record loss reason","Select standardized loss category","Record customer feedback where available","Mark lead = LOST","Make lead available for reporting"] },
        { id: "LMS-SOP-07", title: "Invalid Lead", steps: ["Identify invalid enquiry","Verify reason","Record reason","Mark lead = INVALID","Exclude from normal conversion calculations"] },
        { id: "LMS-SOP-08", title: "Sales Reporting", steps: ["Export/collect lead data","Calculate total leads","Calculate qualified leads","Calculate bookings","Calculate conversion rate","Calculate source-wise conversion","Analyse lost leads","Identify recurring problems","Submit report"] }
      ],
      kpis: [
        { name: "New Lead Response", target: "≥95% within defined SLA" },
        { name: "Lead Record Completion", target: "≥98%" },
        { name: "Lead Contact Rate", target: "≥95%" },
        { name: "Lead Qualification Completion", target: "≥95%" },
        { name: "Follow-Up Completion", target: "≥95%" },
        { name: "Qualified Lead → Booking Conversion", target: "Track monthly baseline" },
        { name: "Booking Creation Accuracy", target: "≥99%" },
        { name: "Lead Source Tracking", target: "≥98%" },
        { name: "Lost Lead Reason Capture", target: "≥95%" },
        { name: "Unprocessed Leads at Day End", target: "0" },
        { name: "Weekly Sales Report", target: "100% on time" }
      ],
      records: ["Lead Register / CRM","Lead Source Tracker","Lead Qualification Record","Follow-Up Tracker","Booking Register","Lost Lead Register","Invalid Lead Register","Customer Requirement Record","Daily Sales Report","Weekly Sales Report","Monthly Sales Report"],
      output: ["Customer Enquiry","Lead Created","Lead Contacted","Requirement Confirmed","Qualified Lead","Booking Confirmed","Customer Operations Handoff"]
    },
    {
      id: "co",
      group: "operations",
      name: "Customer Operations",
      short: "Coordinate booking to completion",
      question: "How do we process and fulfill customer bookings?",
      status: "complete",
      role: "Customer Operations Executive",
      reportsTo: "Operations Function",
      purpose: "Coordinate and control the customer booking journey from confirmed booking through service completion, ensuring that every booking is correctly scheduled, assigned, communicated and closed.",
      distinction: "Customer Operations controls the booking and service-delivery workflow. Customer Experience sits inside this process and controls how the customer is supported throughout that workflow.",
      nested: [
        { name: "Booking Fulfilment", items: ["Assignment","Scheduling","Monitoring","Completion"] },
        { name: "Customer Experience", pack: "cx", items: ["Customer Communication","Support","Feedback","Complaints","Post-Service Experience"] }
      ],
      statusLabel: "Booking flow",
      statuses: ["Confirmed Booking","Booking Verification","Provider Search","Provider Assignment","Provider Accepted","Customer Confirmation","Scheduled Visit","On Going","Service Completed","Operational Closure"],
      responsibilities: [
        { letter: "A", title: "Booking Intake", items: ["Receive confirmed bookings from Sales","Verify booking information","Verify customer details","Verify service requirement","Verify location","Verify requested schedule","Identify missing information"] },
        { letter: "B", title: "Service Fulfilment Coordination", items: ["Check provider availability","Identify suitable provider","Coordinate provider assignment","Confirm provider acceptance","Communicate booking details","Coordinate scheduled visit","Monitor fulfilment progress"] },
        { letter: "C", title: "Customer Communication", items: ["Confirm booking with customer","Provide booking updates","Communicate provider assignment where applicable","Communicate schedule changes","Handle operational questions","Keep customer informed during delays","Coordinate with Customer Experience for support issues"] },
        { letter: "D", title: "Booking Monitoring", items: ["Monitor pending bookings","Monitor accepted bookings","Monitor upcoming visits","Monitor ongoing jobs","Identify delayed bookings","Identify unassigned bookings","Escalate fulfilment problems","Ensure booking status is updated"] },
        { letter: "E", title: "Exceptions & Service Recovery", items: ["Handle provider rejection","Handle provider no-show","Handle customer rescheduling","Handle provider delay","Handle assignment failure","Coordinate replacement provider","Escalate unresolved issues","Record root cause"] },
        { letter: "F", title: "Job Completion", items: ["Confirm service completion","Confirm booking status","Ensure required completion information is recorded","Trigger customer follow-up where required","Trigger billing/settlement process where applicable","Close operational workflow"] },
        { letter: "G", title: "Operations Reporting", items: ["Track booking volume","Track fulfilment rate","Track assignment time","Track delays","Track cancellations","Track provider rejection","Track operational failures","Submit operations reports"] }
      ],
      cadence: {
        daily: ["Review all new confirmed bookings","Verify booking information","Assign pending bookings","Monitor today's scheduled visits","Monitor ongoing jobs","Follow up on delayed jobs","Handle exceptions","Update booking statuses","Ensure no booking remains unattended"],
        weekly: ["Review fulfilment performance","Review unassigned bookings","Review provider rejection","Review cancellations","Review delays","Review service failures","Identify recurring operational problems","Submit Weekly Operations Report"],
        monthly: ["Review booking fulfilment trends","Review service-wise fulfilment","Review area-wise fulfilment","Review provider availability problems","Review cancellation trends","Review operational exceptions","Identify process improvements","Submit Monthly Customer Operations Report"]
      },
      sops: [
        { id: "CO-SOP-01", title: "Booking Intake", steps: ["Receive confirmed booking","Verify customer information","Verify service","Verify location","Verify schedule","Identify missing information","Move booking to Provider Search"] },
        { id: "CO-SOP-02", title: "Provider Assignment", steps: ["Review service requirement","Identify suitable providers","Check availability","Contact provider","Share booking details","Confirm acceptance","Assign provider","Update booking status"] },
        { id: "CO-SOP-03", title: "Customer Confirmation", steps: ["Confirm provider/visit information","Confirm scheduled time","Communicate relevant instructions","Record customer confirmation","Update booking"] },
        { id: "CO-SOP-04", title: "Booking Monitoring", steps: ["Review pending bookings","Review accepted bookings","Review today's visits","Check provider arrival/progress","Check delayed jobs","Contact required party","Escalate exceptions","Update status"] },
        { id: "CO-SOP-05", title: "Provider Rejection / No-Show", steps: ["Record rejection/no-show","Contact provider where appropriate","Identify replacement provider","Contact replacement provider","Update customer","Reassign booking","Record root cause"] },
        { id: "CO-SOP-06", title: "Customer Rescheduling", steps: ["Receive rescheduling request","Record requested change","Check provider availability","Confirm new schedule","Update booking","Notify relevant parties"] },
        { id: "CO-SOP-07", title: "Service Completion", steps: ["Confirm provider completed service","Verify required completion data","Update booking status","Record completion time","Trigger customer follow-up","Trigger billing/settlement","Close operational task"] },
        { id: "CO-SOP-08", title: "Operational Escalation", steps: ["Identify issue","Assess urgency","Record issue","Attempt permitted resolution","Escalate unresolved issue","Communicate with customer","Record final resolution"] }
      ],
      kpis: [
        { name: "Booking Verification Completion", target: "100%" },
        { name: "Provider Assignment Completion", target: "≥95% within defined SLA" },
        { name: "Booking Fulfilment Rate", target: "≥95%" },
        { name: "Unassigned Bookings at SLA Breach", target: "0" },
        { name: "Customer Update Compliance", target: "≥95%" },
        { name: "Booking Status Accuracy", target: "≥98%" },
        { name: "Provider Rejection Recovery", target: "≥90%" },
        { name: "Operational Cancellation Rate", target: "Track and reduce monthly" },
        { name: "Operational Exception Recording", target: "100%" },
        { name: "Weekly Operations Report", target: "100% on time" }
      ],
      records: ["Booking Register","Booking Verification Checklist","Provider Assignment Log","Provider Acceptance Record","Customer Communication Log","Booking Status Tracker","Rescheduling Register","Cancellation Register","Operational Exception Register","Service Completion Record","Daily Operations Report","Weekly Operations Report","Monthly Customer Operations Report"],
      output: ["Confirmed Booking","Verified Booking","Provider Assigned","Customer Confirmed","Service Scheduled","Service Delivered","Completion Recorded","Billing/Settlement Triggered","Operational Closure"]
    },
    {
      id: "po",
      group: "operations",
      name: "Provider Operations",
      short: "Build and manage the provider network",
      question: "How do we build, manage, and deploy the provider network?",
      status: "complete",
      role: "Provider Operations Executive",
      reportsTo: "Operations Function",
      purpose: "Build, maintain and manage a reliable provider network capable of fulfilling Panun Kaergar bookings with the required skills, availability, service coverage and quality standards.",
      distinction: "Provider Operations owns the provider network. It does not own lead conversion, booking fulfilment, customer complaints, quality standards, or provider payments.",
      statusLabel: "Provider lifecycle",
      statuses: ["Prospect","Application Received","Information Collected","Documents Verified","Skills Verified","Approved","Active","Available","Deployed","Performance Monitored","Review Required","Temporarily Inactive","Reactivated / Closed"],
      doesNotOwn: [
        { item: "Customer lead conversion", owner: "Lead Management & Sales" },
        { item: "Customer booking fulfilment", owner: "Customer Operations" },
        { item: "Customer complaints/experience", owner: "Customer Experience" },
        { item: "Service quality standards", owner: "Quality Management" },
        { item: "Provider payments", owner: "Revenue & Settlement Management" }
      ],
      responsibilities: [
        { letter: "A", title: "Provider Acquisition", items: ["Identify provider requirements","Identify provider gaps by service","Identify provider gaps by area","Coordinate provider sourcing","Receive provider applications","Maintain provider acquisition pipeline"] },
        { letter: "B", title: "Provider Registration & Verification", items: ["Collect provider information","Collect required documents","Verify submitted information","Verify service skills","Verify service categories","Verify operating areas","Record provider capabilities","Complete registration checklist","Activate approved providers"] },
        { letter: "C", title: "Provider Database Management", items: ["Maintain provider profile","Maintain service categories","Maintain operating areas","Maintain availability","Maintain contact information","Maintain verification status","Maintain provider performance data","Keep records accurate and current"] },
        { letter: "D", title: "Provider Availability Management", items: ["Track provider availability","Track active/inactive status","Identify service coverage gaps","Identify area coverage gaps","Maintain availability information","Coordinate availability updates"] },
        { letter: "E", title: "Booking Deployment", items: ["Receive provider requirement from Customer Operations","Identify suitable providers","Match provider skill to service requirement","Match provider location to booking location","Check availability","Contact provider","Obtain acceptance","Support provider assignment"] },
        { letter: "F", title: "Provider Performance Management", items: ["Track booking acceptance","Track response time","Track attendance","Track cancellations","Track no-shows","Track customer complaints","Track service quality","Maintain provider performance records"] },
        { letter: "G", title: "Provider Engagement", items: ["Communicate operational updates","Explain service processes","Communicate policies","Collect provider feedback","Identify provider concerns","Improve provider participation","Maintain provider relationships"] },
        { letter: "H", title: "Provider Training & Readiness", items: ["Identify training requirements","Provide process training","Provide platform/process guidance","Communicate quality standards","Conduct onboarding orientation","Record training completion"] },
        { letter: "I", title: "Provider Status Management", items: ["Activate providers","Temporarily deactivate providers","Reactivate providers","Flag providers requiring review","Maintain provider status history","Escalate serious provider issues"] }
      ],
      cadence: {
        daily: ["Review provider applications","Complete pending verification","Update provider records","Check provider availability","Support provider deployment","Monitor provider responses","Handle provider operational issues","Update provider status"],
        weekly: ["Review provider acquisition pipeline","Review verification pending cases","Review active provider count","Review provider availability","Review service-wise provider coverage","Review area-wise provider coverage","Review provider acceptance rate","Review provider cancellations/no-shows","Identify provider gaps","Submit Weekly Provider Operations Report"],
        monthly: ["Review provider network performance","Review active vs inactive providers","Review provider productivity","Review service coverage","Review area coverage","Review provider quality issues","Review provider retention/activity","Identify recruitment requirements","Identify training requirements","Submit Monthly Provider Operations Report"]
      },
      sops: [
        { id: "PO-SOP-01", title: "Provider Acquisition", steps: ["Identify provider requirement","Define required service/area/skill","Source potential providers","Contact provider","Explain Panun Kaergar process","Collect application","Move qualified provider to verification"] },
        { id: "PO-SOP-02", title: "Provider Registration", steps: ["Collect provider profile","Collect contact information","Collect service categories","Collect operating areas","Collect required documents","Record experience/skills","Submit for verification"] },
        { id: "PO-SOP-03", title: "Provider Verification", steps: ["Check application completeness","Verify required documents","Verify contact details","Verify service capability","Verify operating area","Record verification result","Approve / Reject / Hold","Record reason for decision"] },
        { id: "PO-SOP-04", title: "Provider Activation", steps: ["Confirm verification completion","Confirm required onboarding","Create/activate provider account","Assign service categories","Assign service areas","Record availability","Explain operational process","Mark provider = ACTIVE"] },
        { id: "PO-SOP-05", title: "Provider Matching", steps: ["Receive service requirement","Identify required skill","Identify required area","Check provider availability","Shortlist suitable providers","Contact providers","Confirm acceptance","Return assignment information to Customer Operations"] },
        { id: "PO-SOP-06", title: "Provider Availability", steps: ["Request/update availability","Record availability","Update active/inactive status","Identify unavailable periods","Identify coverage gaps","Escalate critical shortages"] },
        { id: "PO-SOP-07", title: "Provider Performance Review", steps: ["Collect booking data","Review acceptance rate","Review response time","Review attendance","Review cancellations","Review complaints","Review quality issues","Identify recurring problems","Create corrective action"] },
        { id: "PO-SOP-08", title: "Provider Issue Management", steps: ["Record issue","Categorize issue","Contact provider","Investigate facts","Determine corrective action","Record resolution","Escalate serious/repeated issues"] },
        { id: "PO-SOP-09", title: "Provider Status Change", steps: ["Identify reason for status change","Review provider record","Confirm required action","Update status","Record effective date","Record reason","Notify relevant operational functions"] }
      ],
      kpis: [
        { name: "Provider Application Processing", target: "≥95% within SLA" },
        { name: "Registration Record Completion", target: "≥98%" },
        { name: "Verification Completion", target: "≥95% within SLA" },
        { name: "Provider Activation Accuracy", target: "≥99%" },
        { name: "Provider Database Accuracy", target: "≥98%" },
        { name: "Provider Availability Coverage", target: "≥95%" },
        { name: "Provider Assignment Support", target: "≥95% within SLA" },
        { name: "Provider Acceptance Rate", target: "Track monthly" },
        { name: "Provider No-Show Rate", target: "Track and reduce monthly" },
        { name: "Provider Cancellation Rate", target: "Track and reduce monthly" },
        { name: "Active Provider Rate", target: "Monthly target" },
        { name: "Service Coverage", target: "≥95% of active services" },
        { name: "Area Coverage", target: "≥95% of active service areas" },
        { name: "Provider Performance Reviews", target: "100% scheduled reviews" }
      ],
      records: ["Provider Acquisition Register","Provider Application","Provider Registration Form","Provider Document Checklist","Provider Verification Record","Provider Database","Provider Skill Matrix","Provider Service Area Matrix","Provider Availability Tracker","Provider Assignment Log","Provider Performance Tracker","Provider Issue Register","Provider Training Register","Provider Status History"],
      output: ["Provider Requirement","Provider Sourcing","Registration","Verification","Activation","Availability","Provider Matching","Booking Deployment","Performance Monitoring","Provider Development / Status Management"]
    },
    {
      id: "rsm",
      group: "operations",
      name: "Revenue & Settlement Management",
      short: "Bill, collect, and settle completed work",
      question: "How is every completed service billed, collected, and settled?",
      status: "complete",
      role: "Revenue & Settlement Executive",
      reportsTo: "Operations Function",
      purpose: "Ensure that every completed service is correctly billed, collected, recorded and settled with the provider according to Panun Kaergar's approved commercial rules.",
      distinction: "Transaction-level control: billing, collections, commission, provider payouts and settlement. Business-level financial control stays with Financial Planning & Control.",
      nested: [
        { name: "Customer Billing & Collections", items: ["Customer billing","Customer collections"] },
        { name: "Provider Payouts & Settlements", items: ["Commission calculation","Provider payouts","Booking-level reconciliation"] }
      ],
      commercialRules: [
        { when: "Service value ≤ ₹1,000", rule: "Panun Kaergar Service Charge → ₹100" },
        { when: "Service value > ₹1,000", rule: "Panun Kaergar Commission → 10%" },
        { when: "Spare parts / materials", rule: "Commission is calculated only on applicable service charges, not on separately identified spare-parts/material costs, according to the approved commercial policy." }
      ],
      boundaryWith: {
        title: "Boundary with Finance",
        thisModule: { name: "Revenue & Settlement Management", layer: "Transaction-level control", items: ["Billing","Collections","Commission","Provider payouts","Settlement"] },
        otherModule: { name: "Financial Planning & Control", pack: "fpc", layer: "Business-level financial control", items: ["Budget","Cash flow","Financial planning","Expense control","Financial reporting","Business financial decisions"] }
      },
      responsibilities: [
        { letter: "A", title: "Customer Billing", items: ["Receive completed-service information","Verify billable service","Verify applicable charges","Prepare customer invoice/bill","Communicate final amount","Record invoice","Correct billing errors"] },
        { letter: "B", title: "Customer Collections", items: ["Track amount due","Record payment","Verify payment status","Follow up on pending payments","Record failed/partial payments","Escalate overdue amounts"] },
        { letter: "C", title: "Service Charge & Commission Calculation", items: ["Identify applicable service amount","Separate service charges from spare-parts/material costs","Apply approved commission rules","Calculate Panun Kaergar revenue","Calculate provider payable amount","Record calculation"] },
        { letter: "D", title: "Provider Payouts", items: ["Identify completed eligible bookings","Verify provider payable amount","Check deductions/adjustments","Prepare payout statement","Process approved payout","Record payment","Maintain payout history"] },
        { letter: "E", title: "Settlement Reconciliation", items: ["Compare booking records with billing records","Compare payments with invoices","Compare provider payouts with completed bookings","Identify discrepancies","Investigate discrepancies","Correct approved discrepancies","Close settlement period"] },
        { letter: "F", title: "Refunds & Adjustments", items: ["Receive approved refund/adjustment request","Verify supporting booking information","Calculate adjustment","Record reason","Process approved adjustment","Update settlement records"] },
        { letter: "G", title: "Disputed Transactions", items: ["Record billing/payment dispute","Collect booking evidence","Verify customer payment","Verify provider service completion","Coordinate with relevant function","Apply approved resolution","Update financial records"] },
        { letter: "H", title: "Revenue Reporting", items: ["Track gross customer collections","Track service charges","Track Panun Kaergar commission","Track provider payouts","Track outstanding customer amounts","Track settlement discrepancies","Submit revenue reports"] }
      ],
      cadence: {
        daily: ["Review completed bookings","Verify billable amounts","Generate/verify customer bills","Record customer payments","Review pending collections","Calculate eligible provider payouts","Record adjustments","Resolve reconciliation discrepancies","Update revenue records"],
        weekly: ["Reconcile completed bookings vs invoices","Reconcile invoices vs collections","Review outstanding customer payments","Review provider payouts","Review billing errors","Review refunds/adjustments","Review disputed transactions","Submit Weekly Revenue & Settlement Report"],
        monthly: ["Close monthly billing records","Complete customer collection reconciliation","Complete provider settlement reconciliation","Review commission/service-charge revenue","Review outstanding receivables","Review payout liabilities","Review adjustments and refunds","Identify recurring discrepancies","Submit Monthly Revenue & Settlement Report"]
      },
      sops: [
        { id: "RS-SOP-01", title: "Completed Booking Billing", steps: ["Receive completed booking","Verify service completion","Verify service amount","Separate materials/spare parts where applicable","Apply approved commercial rules","Generate customer bill","Record invoice","Mark billing complete"] },
        { id: "RS-SOP-02", title: "Customer Payment Collection", steps: ["Identify amount due","Check payment status","Record successful payment","Record partial/failed payment","Follow up pending amount","Escalate overdue collection"] },
        { id: "RS-SOP-03", title: "Commission Calculation", steps: ["Identify service value","Exclude separately identified spare parts/materials","Check applicable pricing rule","Calculate ₹100 service charge when applicable","Calculate 10% commission when applicable","Calculate provider payable amount","Record calculation"] },
        { id: "RS-SOP-04", title: "Provider Settlement", steps: ["Identify completed eligible booking","Verify customer payment status","Verify service amount","Verify Panun Kaergar charge/commission","Calculate provider payable","Check adjustments","Prepare settlement statement","Process approved payout","Record settlement"] },
        { id: "RS-SOP-05", title: "Daily Reconciliation", steps: ["Compare completed bookings","Compare invoices","Compare payments","Compare provider settlements","Identify discrepancies","Investigate discrepancy","Correct approved error","Close reconciliation"] },
        { id: "RS-SOP-06", title: "Refund / Adjustment", steps: ["Receive approved request","Verify booking","Verify reason","Verify amount","Record adjustment","Process approved refund/adjustment","Reconcile transaction"] },
        { id: "RS-SOP-07", title: "Disputed Transaction", steps: ["Record dispute","Freeze affected settlement where required","Collect transaction evidence","Verify booking/service/payment records","Coordinate with relevant function","Apply approved resolution","Close dispute record"] },
        { id: "RS-SOP-08", title: "Month-End Settlement", steps: ["Freeze settlement period","Reconcile completed bookings","Reconcile customer collections","Reconcile provider payouts","Reconcile outstanding amounts","Investigate open discrepancies","Prepare month-end report","Close period"] }
      ],
      kpis: [
        { name: "Billing Accuracy", target: "≥99%" },
        { name: "Completed Booking Billing", target: "100%" },
        { name: "Payment Recording Accuracy", target: "≥99%" },
        { name: "Customer Collection Recording", target: "100%" },
        { name: "Commission Calculation Accuracy", target: "≥99%" },
        { name: "Provider Settlement Accuracy", target: "≥99%" },
        { name: "Reconciliation Completion", target: "100%" },
        { name: "Unresolved Reconciliation Items", target: "0 beyond SLA" },
        { name: "Approved Provider Payouts Processed", target: "≥95% within SLA" },
        { name: "Outstanding Collection Follow-Up", target: "≥95%" },
        { name: "Monthly Settlement Closure", target: "100% on time" }
      ],
      records: ["Billing Register","Customer Invoice Register","Customer Payment Register","Outstanding Receivables Register","Commission Calculation Register","Provider Payout Register","Provider Settlement Statement","Refund Register","Adjustment Register","Dispute Transaction Register","Daily Reconciliation","Weekly Revenue Report","Monthly Revenue & Settlement Report"],
      output: ["Completed Booking","Bill Generated","Customer Payment","Revenue Recorded","Provider Payable Calculated","Provider Settlement","Reconciliation","Financial Records Closed"]
    },
    {
      id: "qm",
      group: "operations",
      name: "Quality Management",
      short: "Set, check, and correct service quality",
      question: "How do we keep service delivery consistent and correct failures?",
      status: "complete",
      role: "Quality & Compliance Executive",
      reportsTo: "Operations Function",
      purpose: "Define, monitor and improve Panun Kaergar's service quality standards so that customer bookings are delivered consistently and service failures are identified, investigated and corrected.",
      distinction: "Quality Management does not become another customer-support team. It stays independent enough to check whether the process actually worked.",
      statusLabel: "Quality control flow",
      statuses: ["Service Completed","Quality Data Collected","Customer Feedback Reviewed","Quality Check","Issue Identified? NO → Close Quality Check","Issue Identified? YES → Record Issue","Investigate","Root Cause","Corrective Action","Verification","Close","Quality Data → Continuous Improvement"],
      doesNotOwn: [
        { item: "Customer issue/support", owner: "Customer Experience" },
        { item: "Provider network management", owner: "Provider Operations" },
        { item: "Service execution", owner: "Customer Operations" },
        { item: "Quality standard, audit, failure analysis and corrective action", owner: "Quality Management" }
      ],
      responsibilities: [
        { letter: "A", title: "Quality Standards", items: ["Define service quality standards","Define provider conduct standards","Define customer-service standards","Define booking-process standards","Define completion requirements","Define documentation requirements","Maintain Quality Standards Manual"] },
        { letter: "B", title: "Service Quality Monitoring", items: ["Monitor completed bookings","Review customer feedback","Review complaints","Review service failures","Review repeat-service requests","Review provider performance","Identify quality deviations"] },
        { letter: "C", title: "Quality Inspection", items: ["Select bookings for quality review","Review service completion information","Review customer feedback","Verify required evidence","Check compliance with service standards","Record inspection result"] },
        { letter: "D", title: "Complaint & Failure Analysis", items: ["Receive quality-related cases","Categorize complaint/failure","Investigate available evidence","Identify immediate cause","Identify root cause","Determine corrective action","Record final outcome"] },
        { letter: "E", title: "Corrective & Preventive Action", items: ["Create corrective action","Assign responsible function","Define completion deadline","Track action","Verify implementation","Check whether problem recurred","Close action"] },
        { letter: "F", title: "Provider Quality Control", items: ["Monitor provider quality","Identify repeated quality failures","Review provider complaints","Recommend provider training","Recommend provider review","Track corrective actions","Escalate serious/repeated failures"] },
        { letter: "G", title: "Process Quality", items: ["Audit operational processes","Check SOP compliance","Identify process deviations","Identify recurring operational failures","Recommend process improvements","Verify implementation"] },
        { letter: "H", title: "Quality Reporting", items: ["Track quality incidents","Track complaints","Track repeat-service cases","Track provider quality","Track SOP compliance","Track corrective actions","Submit quality reports"] }
      ],
      cadence: {
        daily: ["Review new quality complaints","Review serious service failures","Review customer feedback requiring action","Review open corrective actions","Escalate critical quality issues","Update quality records"],
        weekly: ["Review completed quality checks","Review complaints","Review repeat-service cases","Review provider quality issues","Review SOP deviations","Review open corrective actions","Identify recurring problems","Submit Weekly Quality Report"],
        monthly: ["Review overall quality performance","Review service-wise quality","Review provider-wise quality","Review complaint trends","Review repeat-service trends","Review SOP compliance","Review corrective-action effectiveness","Update quality standards where required","Submit Monthly Quality Report"]
      },
      sops: [
        { id: "QM-SOP-01", title: "Quality Standard Definition", steps: ["Identify service/process","Define expected outcome","Define measurable quality criteria","Define acceptable deviation","Define evidence requirements","Document standard","Approve and publish"] },
        { id: "QM-SOP-02", title: "Service Quality Check", steps: ["Select completed booking","Review service information","Review customer feedback","Check quality criteria","Record findings","Mark Pass / Fail / Review Required","Create action if required"] },
        { id: "QM-SOP-03", title: "Customer Complaint Quality Review", steps: ["Receive complaint","Categorize issue","Collect booking information","Collect relevant evidence","Review customer account","Review provider account","Determine facts","Identify root cause","Record outcome"] },
        { id: "QM-SOP-04", title: "Root Cause Analysis", steps: ["Define problem","Collect evidence","Identify immediate cause","Identify process/system cause","Identify recurring pattern","Document root cause","Recommend corrective action"] },
        { id: "QM-SOP-05", title: "Corrective Action", steps: ["Create corrective action","Define required change","Assign responsible function","Set deadline","Monitor progress","Verify completion","Measure effectiveness","Close action"] },
        { id: "QM-SOP-06", title: "Provider Quality Review", steps: ["Collect provider quality data","Review complaints","Review repeat failures","Review customer feedback","Identify quality pattern","Determine required action","Coordinate provider corrective action","Record outcome"] },
        { id: "QM-SOP-07", title: "SOP Compliance Audit", steps: ["Select process","Select sample records","Compare actual process with SOP","Record deviations","Identify cause","Create corrective action","Verify compliance"] },
        { id: "QM-SOP-08", title: "Monthly Quality Review", steps: ["Collect quality data","Analyse complaints","Analyse service failures","Analyse provider performance","Analyse SOP compliance","Review corrective actions","Identify systemic problems","Define improvement priorities","Submit Quality Review"] }
      ],
      kpis: [
        { name: "Quality Check Completion", target: "100% of scheduled checks" },
        { name: "Quality Record Accuracy", target: "≥98%" },
        { name: "Complaint Review Completion", target: "≥95% within SLA" },
        { name: "Critical Issue Escalation", target: "100% within SLA" },
        { name: "Root Cause Analysis Completion", target: "≥95%" },
        { name: "Corrective Action Completion", target: "≥95% within deadline" },
        { name: "Corrective Action Effectiveness", target: "≥90%" },
        { name: "SOP Compliance", target: "≥95%" },
        { name: "Repeat Quality Failure Rate", target: "Track and reduce monthly" },
        { name: "Repeat Customer Complaint Rate", target: "Track and reduce monthly" },
        { name: "Monthly Quality Report", target: "100% on time" }
      ],
      records: ["Quality Standards Manual","Quality Checklist","Service Quality Inspection Register","Quality Issue Register","Complaint Quality Review","Root Cause Analysis Record","Corrective Action Register","Provider Quality Review","SOP Compliance Audit","Quality Improvement Register","Weekly Quality Report","Monthly Quality Report"],
      output: ["Quality Standard","Quality Monitoring","Inspection","Issue Detection","Investigation","Root Cause","Corrective Action","Verification","Continuous Improvement"]
    },
    {
      id: "cx",
      group: "operations",
      parent: "co",
      name: "Customer Experience",
      short: "Communication, support, feedback, recovery",
      question: "How do we support the customer throughout and after the service journey?",
      status: "complete",
      role: "Customer Experience Executive",
      reportsTo: "Customer Operations",
      purpose: "Ensure that customers receive clear communication, timely support, consistent service and proper follow-up throughout and after their Panun Kaergar service journey.",
      distinction: "CX is throughout and after service: communication, support, complaints, feedback and recovery. It does not convert leads, fulfil bookings, audit quality, or settle money.",
      statusLabel: "Customer journey",
      statuses: ["Booking Confirmed","Booking Communication","Service Scheduled","Service Updates","Service Delivered","Feedback Requested","Post-Service Support","Customer Journey Closed"],
      requestTypes: ["Information Request","Booking Update","Rescheduling Request","Delay Complaint","Provider Behaviour Complaint","Service Quality Complaint","Billing/Payment Query","Refund/Adjustment Request","Technical Issue","General Feedback"],
      doesNotOwn: [
        { item: "Convert enquiry → confirmed booking", owner: "Lead Management & Sales" },
        { item: "Coordinate fulfilment after booking", owner: "Customer Operations" },
        { item: "Quality standards, audits, RCA, corrective action", owner: "Quality Management" },
        { item: "Billing, collections, settlements", owner: "Revenue & Settlement" }
      ],
      responsibilities: [
        { letter: "A", title: "Customer Communication", items: ["Send booking confirmations","Send service updates","Communicate delays","Communicate rescheduling","Provide approved service information","Maintain communication records"] },
        { letter: "B", title: "Customer Support", items: ["Receive customer questions","Understand customer issue","Categorize request","Provide approved information","Coordinate with relevant function","Track pending requests","Confirm resolution with customer"] },
        { letter: "C", title: "Complaint Management", items: ["Receive complaint","Record complaint","Categorize complaint","Identify affected booking","Collect customer information","Route complaint to responsible function","Track resolution","Communicate outcome to customer"] },
        { letter: "D", title: "Customer Feedback", items: ["Collect post-service feedback","Record ratings/reviews","Record customer comments","Identify recurring feedback","Identify positive experiences","Forward actionable feedback"] },
        { letter: "E", title: "Post-Service Experience", items: ["Confirm service completion experience","Request customer feedback","Identify unresolved issues","Coordinate repeat-service requirements","Handle post-service queries","Close customer journey"] },
        { letter: "F", title: "Service Recovery", items: ["Identify failed customer experience","Assess issue severity","Coordinate immediate resolution","Keep customer informed","Escalate where required","Record recovery outcome"] },
        { letter: "G", title: "Customer Experience Reporting", items: ["Track customer enquiries","Track complaints","Track response time","Track resolution time","Track feedback","Track ratings","Track repeat complaints","Submit CX reports"] }
      ],
      cadence: {
        daily: ["Review new customer requests","Respond to pending customer queries","Follow up on open complaints","Monitor unresolved customer cases","Communicate operational updates","Review post-service feedback","Update customer records"],
        weekly: ["Review customer complaints","Review response times","Review resolution times","Review customer feedback","Review ratings/reviews","Identify recurring customer problems","Review open cases","Submit Weekly Customer Experience Report"],
        monthly: ["Analyse customer satisfaction trends","Analyse complaint trends","Analyse service-related complaints","Analyse provider-related complaints","Analyse billing-related complaints","Analyse repeat customer issues","Identify customer journey improvements","Submit Monthly Customer Experience Report"]
      },
      sops: [
        { id: "CX-SOP-01", title: "Customer Query Handling", steps: ["Receive customer request","Identify customer","Identify booking where applicable","Understand request","Categorize request","Provide approved information","Route if another function is responsible","Record action","Close request"] },
        { id: "CX-SOP-02", title: "Customer Complaint", steps: ["Receive complaint","Record complaint","Identify booking","Categorize complaint","Assess severity","Acknowledge customer","Assign to responsible function","Track resolution","Communicate outcome","Close complaint"] },
        { id: "CX-SOP-03", title: "Customer Escalation", steps: ["Identify escalation trigger","Record escalation","Identify responsible function","Escalate within SLA","Monitor resolution","Update customer","Record final outcome"] },
        { id: "CX-SOP-04", title: "Service Delay Communication", steps: ["Receive delay information","Verify affected booking","Confirm updated information","Inform customer","Record communication","Continue monitoring"] },
        { id: "CX-SOP-05", title: "Post-Service Feedback", steps: ["Identify completed booking","Send feedback request","Capture rating","Capture comments","Categorize feedback","Identify negative feedback requiring action","Forward actionable cases"] },
        { id: "CX-SOP-06", title: "Service Recovery", steps: ["Identify service failure","Understand customer impact","Record case","Coordinate with Operations/Quality","Implement approved recovery action","Confirm customer outcome","Close case"] },
        { id: "CX-SOP-07", title: "Customer Experience Reporting", steps: ["Collect customer-case data","Calculate response time","Calculate resolution time","Analyse complaints","Analyse feedback","Analyse ratings","Identify recurring issues","Submit report"] }
      ],
      kpis: [
        { name: "Customer Query Response", target: "≥95% within SLA" },
        { name: "Complaint Acknowledgement", target: "≥95% within SLA" },
        { name: "Complaint Resolution", target: "≥90% within defined SLA" },
        { name: "Open Customer Cases Updated", target: "100%" },
        { name: "Customer Communication Compliance", target: "≥95%" },
        { name: "Post-Service Feedback Collection", target: "≥70%" },
        { name: "Complaint Record Completeness", target: "≥98%" },
        { name: "Escalation Compliance", target: "100%" },
        { name: "Repeat Complaint Rate", target: "Track and reduce monthly" },
        { name: "Monthly CX Report", target: "100% on time" }
      ],
      records: ["Customer Support Register","Customer Communication Log","Customer Complaint Register","Customer Escalation Register","Customer Feedback Register","Customer Rating/Review Register","Service Recovery Register","Open Case Tracker","Post-Service Follow-Up Register","Weekly CX Report","Monthly CX Report"],
      output: ["Customer Request","Request Categorized","Response","Resolution / Escalation","Customer Confirmation","Feedback","Customer Journey Closed"]
    },
    {
      id: "fpc",
      group: "control",
      name: "Financial Planning & Control",
      short: "Plan, monitor, and control the money",
      question: "How do we plan, monitor and control financial resources?",
      status: "complete",
      role: "Finance & Control Executive",
      reportsTo: "Control Function",
      purpose: "Plan, monitor and control Panun Kaergar's financial resources, ensuring that revenue, expenses, cash flow, budgets, liabilities and financial performance are accurately tracked and controlled.",
      distinction: "Revenue & Settlement = money generated and settled from individual bookings. Financial Planning & Control = overall financial health and control of the business.",
      statusLabel: "Financial control cycle",
      statuses: ["Plan — Revenue + Expenses + Cash Requirements","Budget — Set approved financial limits","Record — Capture actual financial transactions","Compare — Actual vs Budget vs Forecast","Analyse — Identify variance and cause","Control — Correct overspending / financial leakage","Report — Management Financial Information"],
      nested: [
        { name: "What this module owns", items: ["Budget","Cash flow","Expense control","Forecasting","Financial performance","Management reporting"] },
        { name: "What Revenue & Settlement owns", items: ["Customer billing","Customer collections","Commission calculation","Provider payouts","Booking-level reconciliation"] }
      ],
      doesNotOwn: [
        { item: "Customer billing, collections, commission, provider payouts, booking-level reconciliation", owner: "Revenue & Settlement" }
      ],
      boundaryWith: {
        thisModule: { name: "Financial Planning & Control", layer: "Overall financial health of the business", items: ["Budget","Cash flow","Expense control","Forecasting","Financial performance","Management reporting"] },
        otherModule: { name: "Revenue & Settlement", layer: "Money generated and settled from individual bookings", items: ["Customer billing","Customer collections","Commission calculation","Provider payouts","Booking-level reconciliation"] }
      },
      responsibilities: [
        { letter: "A", title: "Financial Planning", items: ["Prepare annual financial plan","Prepare monthly financial plans","Forecast revenue","Forecast operating expenses","Forecast cash requirements","Identify upcoming financial obligations","Maintain financial forecasts"] },
        { letter: "B", title: "Budget Management", items: ["Prepare departmental budgets","Record approved budgets","Allocate spending limits","Track actual spending","Compare actual vs budget","Identify overspending","Identify underspending","Escalate significant variances"] },
        { letter: "C", title: "Revenue Monitoring", items: ["Receive revenue data from Revenue & Settlement","Track monthly revenue","Track revenue by service","Track revenue by channel","Compare actual revenue with forecast","Identify revenue gaps","Maintain revenue forecast"] },
        { letter: "D", title: "Expense Control", items: ["Maintain expense register","Categorize expenses","Verify approved expenses","Monitor recurring expenses","Monitor discretionary spending","Compare expenses against budget","Escalate unauthorized/excess spending"] },
        { letter: "E", title: "Cash Flow Management", items: ["Track opening cash position","Track customer collections","Track operating expenses","Track provider settlement obligations","Track other liabilities","Forecast upcoming cash requirements","Identify cash shortages","Maintain cash-flow forecast"] },
        { letter: "F", title: "Financial Performance Analysis", items: ["Calculate revenue","Calculate operating expenses","Analyse gross contribution","Analyse operating result","Analyse service economics","Analyse marketing spend vs generated business","Analyse customer acquisition economics","Identify financial trends"] },
        { letter: "G", title: "Financial Control", items: ["Maintain spending authorization rules","Verify financial records","Maintain supporting documentation","Monitor financial controls","Identify control failures","Prevent duplicate/unauthorized payments","Maintain audit trail"] },
        { letter: "H", title: "Payables & Obligations", items: ["Track approved expenses payable","Track salaries and recurring obligations","Track vendor obligations","Track provider settlement liabilities","Track taxes/statutory obligations where applicable","Maintain payment schedule"] },
        { letter: "I", title: "Financial Reporting", items: ["Prepare daily cash position where required","Prepare weekly financial report","Prepare monthly management accounts","Prepare budget variance report","Prepare cash-flow report","Prepare revenue/expense analysis","Escalate material financial risks"] }
      ],
      cadence: {
        daily: ["Review cash position","Review customer collection data","Review major expenses","Review upcoming payment obligations","Record financial transactions","Check unusual financial activity","Escalate urgent financial issues"],
        weekly: ["Review revenue","Review expenses","Review cash flow","Review provider settlement liabilities","Review outstanding customer collections","Review budget variance","Review upcoming obligations","Submit Weekly Financial Control Report"],
        monthly: ["Close monthly financial records","Reconcile revenue","Reconcile expenses","Review budget vs actual","Update revenue forecast","Update expense forecast","Update cash-flow forecast","Analyse financial performance","Identify cost-control opportunities","Submit Monthly Financial Report"]
      },
      sops: [
        { id: "FPC-SOP-01", title: "Annual Financial Planning", steps: ["Review previous financial performance","Review Growth plan","Forecast revenue","Forecast operating expenses","Forecast cash requirements","Identify planned investments","Prepare annual financial plan","Obtain approval"] },
        { id: "FPC-SOP-02", title: "Monthly Budgeting", steps: ["Review annual plan","Review previous month","Prepare monthly revenue forecast","Prepare monthly expense budget","Prepare cash requirement","Record approved budget","Publish budget controls"] },
        { id: "FPC-SOP-03", title: "Expense Control", steps: ["Receive expense request/record","Verify supporting document","Check budget availability","Verify authorization","Record expense","Update budget utilization","Escalate exceptions"] },
        { id: "FPC-SOP-04", title: "Cash Flow Management", steps: ["Record opening cash","Add expected collections","Deduct expected payments","Include provider settlements","Include recurring obligations","Calculate projected closing cash","Identify shortfall/surplus","Update forecast"] },
        { id: "FPC-SOP-05", title: "Budget Variance Analysis", steps: ["Collect actual figures","Compare against budget","Calculate variance","Identify material variance","Identify cause","Record explanation","Define corrective action","Report"] },
        { id: "FPC-SOP-06", title: "Financial Performance Review", steps: ["Collect revenue","Collect expenses","Review contribution","Review operating result","Review cash position","Review service economics","Identify financial risks","Prepare management report"] },
        { id: "FPC-SOP-07", title: "Financial Reconciliation", steps: ["Collect transaction records","Compare financial records","Compare bank/payment records","Identify discrepancies","Investigate discrepancy","Correct approved errors","Close reconciliation"] },
        { id: "FPC-SOP-08", title: "Month-End Financial Closure", steps: ["Close transaction period","Reconcile revenue","Reconcile expenses","Reconcile cash","Reconcile liabilities","Complete variance analysis","Prepare management accounts","Record unresolved items","Close month"] }
      ],
      kpis: [
        { name: "Budget Preparation", target: "100% on time" },
        { name: "Financial Record Accuracy", target: "≥99%" },
        { name: "Expense Documentation", target: "100%" },
        { name: "Budget Variance Reporting", target: "100%" },
        { name: "Cash Forecast Accuracy", target: "≥90%" },
        { name: "Financial Reconciliation", target: "100% monthly" },
        { name: "Unauthorized Expense Rate", target: "0" },
        { name: "Duplicate Payment Rate", target: "0" },
        { name: "Material Variance Investigation", target: "100%" },
        { name: "Financial Report Submission", target: "100% on time" },
        { name: "Month-End Closure", target: "100% on schedule" }
      ],
      records: ["Annual Financial Plan","Monthly Budget","Revenue Forecast","Expense Budget","Expense Register","Cash Flow Forecast","Payment Schedule","Budget vs Actual Report","Financial Reconciliation","Financial Risk Register","Weekly Financial Control Report","Monthly Financial Report"],
      output: ["Financial Plan","Budget","Financial Transactions","Monitoring","Variance Analysis","Corrective Control","Financial Reporting","Management Decision"]
    },
    {
      id: "hr",
      group: "control",
      name: "People & HR",
      short: "Build and maintain the employee system",
      question: "How do we build and maintain the people system required to run the business?",
      status: "complete",
      role: "HR & People Executive",
      reportsTo: "Control Function",
      purpose: "Build and maintain the people system required for Panun Kaergar by managing workforce planning, recruitment, onboarding, employee records, attendance, performance, training, policies and employee lifecycle processes.",
      distinction: "People & HR owns Panun Kaergar employees. Provider Operations owns service providers. Providers are not employees by default.",
      statusLabel: "Employee lifecycle",
      statuses: ["Workforce Requirement","Position Approved","Recruitment","Selection","Offer","Joining","Onboarding","Active Employment","Performance Management","Training & Development","Exit"],
      doesNotOwn: [
        { item: "Service provider recruitment, verification, activation, performance and status", owner: "Provider Operations" },
        { item: "Salary budget, manpower cost, department budgets", owner: "Financial Planning & Control" }
      ],
      nested: [
        { name: "Panun Kaergar employees", items: ["Headcount requirement","Recruitment","Employee records","Attendance","Performance","Training","Employee lifecycle"] },
        { name: "Service providers", items: ["Provider recruitment","Verification","Activation","Performance","Status — owned by Provider Operations"] }
      ],
      boundaryWith: {
        thisModule: { name: "People & HR", layer: "People data", items: ["Headcount requirement","Recruitment","Employee records","Attendance","Performance","Training","Employee lifecycle"] },
        otherModule: { name: "Financial Planning & Control", layer: "Cost of people", items: ["Salary budget","Manpower cost","Department budgets","Financial planning"] }
      },
      responsibilities: [
        { letter: "A", title: "Workforce Planning", items: ["Maintain approved organization structure","Maintain position list","Identify staffing requirements","Identify vacancies","Define job requirements","Maintain manpower plan","Coordinate hiring requirements"] },
        { letter: "B", title: "Recruitment", items: ["Prepare job descriptions","Publish approved vacancies","Source candidates","Maintain candidate database","Screen applications","Coordinate interviews","Record interview results","Coordinate selection","Maintain recruitment records"] },
        { letter: "C", title: "Employee Onboarding", items: ["Collect employee information","Collect required documents","Complete joining formalities","Explain company policies","Explain job responsibilities","Provide required access/equipment","Complete induction","Confirm onboarding completion"] },
        { letter: "D", title: "Employee Records", items: ["Maintain employee master database","Maintain employment records","Maintain attendance records","Maintain leave records","Maintain compensation records","Maintain training records","Maintain performance records","Maintain exit records"] },
        { letter: "E", title: "Attendance & Leave", items: ["Maintain attendance system","Record employee attendance","Process leave requests","Maintain leave balances","Identify attendance irregularities","Prepare attendance reports"] },
        { letter: "F", title: "Performance Management", items: ["Maintain role-specific KPIs","Maintain performance review schedule","Collect performance data","Track KPI achievement","Coordinate performance reviews","Document performance issues","Coordinate improvement plans","Maintain performance history"] },
        { letter: "G", title: "Training & Development", items: ["Identify training requirements","Maintain training calendar","Coordinate employee training","Record training completion","Evaluate training effectiveness","Track employee development"] },
        { letter: "H", title: "Policies & Employee Compliance", items: ["Maintain employee policies","Communicate policies","Maintain acknowledgement records","Monitor policy compliance","Document policy violations","Escalate serious issues"] },
        { letter: "I", title: "Employee Engagement", items: ["Collect employee feedback","Identify workplace issues","Coordinate employee communication","Track engagement initiatives","Identify retention risks"] },
        { letter: "J", title: "Employee Exit", items: ["Receive resignation/termination instruction","Complete exit documentation","Conduct exit process","Recover company assets","Revoke required access","Complete final records","Archive employee file"] }
      ],
      cadence: {
        daily: ["Check attendance","Process urgent HR requests","Update employee records","Handle recruitment follow-ups","Handle employee queries","Record HR actions"],
        weekly: ["Review attendance","Review open vacancies","Review recruitment pipeline","Review onboarding cases","Review pending employee requests","Review upcoming training","Submit Weekly HR Report"],
        monthly: ["Review headcount","Review manpower requirements","Review attendance","Review leave","Review employee turnover","Review recruitment performance","Review training completion","Review performance-review status","Review employee issues","Submit Monthly HR Report"]
      },
      sops: [
        { id: "HR-SOP-01", title: "Manpower Request", steps: ["Identify staffing requirement","Define position","Define responsibilities","Define qualifications/skills","Define KPI requirements","Confirm budget","Obtain approval","Open vacancy"] },
        { id: "HR-SOP-02", title: "Recruitment", steps: ["Prepare job description","Publish vacancy","Collect applications","Screen candidates","Shortlist candidates","Conduct interviews","Record evaluation","Select candidate","Prepare offer"] },
        { id: "HR-SOP-03", title: "Employee Onboarding", steps: ["Confirm joining","Collect documents","Create employee record","Complete joining formalities","Explain policies","Explain role/KPIs","Provide required access","Complete induction","Close onboarding checklist"] },
        { id: "HR-SOP-04", title: "Attendance & Leave", steps: ["Record attendance","Receive leave request","Verify leave balance","Route for approval","Update leave record","Include in monthly attendance report"] },
        { id: "HR-SOP-05", title: "Performance Management", steps: ["Define role KPIs","Set review period","Collect performance data","Compare results with KPIs","Conduct review","Record feedback","Define improvement actions","Schedule next review"] },
        { id: "HR-SOP-06", title: "Training", steps: ["Identify skill gap","Define training requirement","Schedule training","Conduct/coordinate training","Record attendance","Evaluate effectiveness","Update training record"] },
        { id: "HR-SOP-07", title: "Employee Issue Management", steps: ["Receive issue","Record issue","Categorize issue","Review relevant information","Coordinate resolution","Record action taken","Close case"] },
        { id: "HR-SOP-08", title: "Policy Management", steps: ["Identify policy requirement","Draft/update policy","Obtain approval","Publish policy","Communicate to employees","Collect acknowledgement where required","Review periodically"] },
        { id: "HR-SOP-09", title: "Employee Exit", steps: ["Receive approved exit instruction","Record exit date","Complete handover","Recover company assets","Revoke access","Complete required final documentation","Conduct exit process","Archive employee record"] }
      ],
      kpis: [
        { name: "Employee Record Completeness", target: "100%" },
        { name: "Attendance Record Accuracy", target: "≥99%" },
        { name: "Vacancy Processing", target: "Within approved hiring SLA" },
        { name: "Recruitment Pipeline Updates", target: "100% weekly" },
        { name: "Onboarding Completion", target: "100%" },
        { name: "Mandatory Training Completion", target: "100%" },
        { name: "Performance Review Completion", target: "100%" },
        { name: "Leave Record Accuracy", target: "≥99%" },
        { name: "HR Request Resolution", target: "≥95% within SLA" },
        { name: "Exit Checklist Completion", target: "100%" },
        { name: "Monthly HR Report", target: "100% on time" }
      ],
      records: ["Organization Structure","Position Register","Manpower Plan","Job Descriptions","Candidate Register","Interview Evaluation","Employee Master Database","Employee Personnel File","Attendance Register","Leave Register","Training Register","Performance Review Register","Employee Issue Register","Policy Register","Employee Exit Checklist","Monthly HR Report"],
      output: ["Workforce Requirement","Recruitment","Selection","Onboarding","Active Employee","Performance Management","Training & Development","Employee Records","Exit"]
    },
    {
      id: "td",
      group: "control",
      name: "Technology & Data",
      short: "Systems, data, access, and reporting",
      question: "How do we provide the systems, information, automation, and technology needed to run and control the business?",
      status: "complete",
      role: "Technology & Data Executive",
      reportsTo: "Control Function",
      purpose: "Maintain the technology, systems, data and digital infrastructure required for Panun Kaergar to operate reliably, securely and efficiently, while ensuring that business data remains accurate, accessible and protected.",
      distinction: "Technology is applications, systems, integrations, infrastructure, access, backups and technical support. Data is the business information those systems carry.",
      nested: [
        { name: "Technology", items: ["Applications","Systems","Integrations","Infrastructure","Access","Backups","Technical support"] },
        { name: "Data", items: ["Customer data","Provider data","Booking data","Financial data","Marketing data","Operational data","KPI data","Reporting data"] }
      ],
      boundaryWith: {
        thisModule: { name: "Technology", layer: "How the business runs digitally", items: ["Applications","Systems","Integrations","Infrastructure","Access","Backups","Technical support"] },
        otherModule: { name: "Data", layer: "The business information those systems carry", items: ["Customer data","Provider data","Booking data","Financial data","Marketing data","Operational data","KPI data","Reporting data"] }
      },
      lifecycles: [
        { label: "System lifecycle", items: ["Requirement","Evaluation","Approval","Implementation","Testing","Deployment","Monitoring","Maintenance","Improvement","Retirement"] },
        { label: "Data lifecycle", items: ["Data Creation","Data Capture","Validation","Storage","Use","Reporting","Backup","Retention","Secure Disposal"] }
      ],
      responsibilities: [
        { letter: "A", title: "Technology System Management", items: ["Maintain business software systems","Maintain operational platforms","Monitor system availability","Manage system configurations","Maintain integrations","Monitor system errors","Coordinate technical fixes"] },
        { letter: "B", title: "Business Application Management", items: ["Maintain customer-facing applications","Maintain provider-facing systems","Maintain internal operational systems","Maintain CRM/lead systems","Maintain booking systems","Maintain reporting systems","Document system configurations"] },
        { letter: "C", title: "Data Management", items: ["Maintain business data structures","Maintain data definitions","Maintain master data","Monitor data completeness","Monitor data accuracy","Identify duplicate records","Correct approved data errors","Maintain data documentation"] },
        { letter: "D", title: "Data Security & Access Control", items: ["Maintain user access records","Grant approved system access","Remove access when required","Review access permissions","Protect sensitive business data","Monitor unusual access/activity","Maintain access audit records"] },
        { letter: "E", title: "Backup & Recovery", items: ["Maintain backup procedures","Monitor backup completion","Test backup integrity","Maintain recovery procedures","Document recovery dependencies","Conduct periodic recovery tests"] },
        { letter: "F", title: "System Support", items: ["Receive technology issues","Categorize incidents","Prioritize incidents","Troubleshoot issues","Coordinate external technical support","Track resolution","Maintain incident history"] },
        { letter: "G", title: "Technology Change Management", items: ["Receive change requests","Define change requirement","Assess business impact","Test changes","Obtain approval","Implement approved change","Verify implementation","Document change"] },
        { letter: "H", title: "Technology Vendor Management", items: ["Maintain technology vendor list","Track subscriptions","Track licenses","Track renewal dates","Track service issues","Monitor technology costs","Coordinate vendor support"] },
        { letter: "I", title: "Data Reporting & Insights", items: ["Maintain operational dashboards","Provide required business reports","Validate reporting data","Maintain KPI data sources","Identify data anomalies","Support management analysis"] }
      ],
      cadence: {
        daily: ["Check critical system availability","Check system errors","Check critical integrations","Review technology incidents","Review backup status","Handle priority support requests","Update incident/change records"],
        weekly: ["Review system performance","Review unresolved technology issues","Review backup status","Review data-quality issues","Review user-access requests","Review upcoming software renewals","Review active technology changes","Submit Weekly Technology & Data Report"],
        monthly: ["Review system availability","Review technology incidents","Review data quality","Review access permissions","Review backup/recovery status","Review technology costs","Review vendor performance","Review system improvement requirements","Submit Monthly Technology & Data Report"]
      },
      sops: [
        { id: "TD-SOP-01", title: "System Access Management", steps: ["Receive approved access request","Verify user and required system","Assign minimum required access","Record access","Confirm access","Review periodically"] },
        { id: "TD-SOP-02", title: "Access Removal", steps: ["Receive employee/provider status change","Identify affected systems","Disable unauthorized access","Recover company-owned credentials/assets where applicable","Record removal","Confirm completion"] },
        { id: "TD-SOP-03", title: "Technology Incident Management", steps: ["Receive incident","Record incident","Categorize severity","Identify affected system","Troubleshoot","Restore service","Verify resolution","Record root cause where required","Close incident"] },
        { id: "TD-SOP-04", title: "Data Quality Management", steps: ["Identify required data set","Check completeness","Check accuracy","Check duplicates","Identify inconsistent records","Correct approved errors","Record correction","Monitor recurrence"] },
        { id: "TD-SOP-05", title: "Backup Management", steps: ["Confirm backup schedule","Monitor backup execution","Check backup status","Investigate failed backup","Verify backup integrity","Record result"] },
        { id: "TD-SOP-06", title: "Data Recovery", steps: ["Identify recovery requirement","Confirm authorization","Identify required backup","Restore data/system","Verify restored data","Confirm system operation","Record recovery event"] },
        { id: "TD-SOP-07", title: "Technology Change Management", steps: ["Receive change request","Document requirement","Assess impact","Define implementation plan","Test change","Obtain approval","Implement","Verify","Document final state"] },
        { id: "TD-SOP-08", title: "Software / Vendor Management", steps: ["Maintain vendor register","Record subscription/license","Record cost","Record renewal date","Review usage","Review vendor performance","Renew/change/cancel according to approval"] },
        { id: "TD-SOP-09", title: "Data Reporting", steps: ["Identify reporting requirement","Identify data source","Extract data","Validate data","Generate report","Check calculations","Publish report","Archive report"] }
      ],
      kpis: [
        { name: "Critical System Availability", target: "≥99%" },
        { name: "Critical Incident Response", target: "100% within SLA" },
        { name: "Critical Incident Resolution", target: "≥95% within SLA" },
        { name: "Backup Completion", target: "100%" },
        { name: "Backup Verification", target: "100%" },
        { name: "Access Request Processing", target: "≥95% within SLA" },
        { name: "Unauthorized Active Accounts", target: "0" },
        { name: "Data Quality", target: "≥98%" },
        { name: "Scheduled Technology Changes Completed", target: "≥95%" },
        { name: "Technology Asset/Subscription Register Accuracy", target: "100%" },
        { name: "Required Reports Delivered", target: "100% on time" },
        { name: "Recovery Test Completion", target: "100% as scheduled" }
      ],
      records: ["Technology Asset Register","System Register","User Access Register","Access Request Register","Technology Incident Register","System Change Register","Backup Register","Recovery Test Register","Data Quality Register","Software & Subscription Register","Technology Vendor Register","System Documentation","Data Dictionary","Technology Risk Register","Monthly Technology & Data Report"],
      output: ["Business Requirement","Technology/System","Data Capture","Validation","Secure Storage","Operational Use","Reporting","Monitoring","Improvement"]
    }
  ]
};

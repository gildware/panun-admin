window.PK_GROWTH = window.PK_GROWTH || {};
(function () {
  "use strict";
  const G = window.PK_GROWTH;
  if (!G.roles) return;

  function pass(who, why) {
    return { who: who, why: why };
  }
  function field(name, why) {
    return { field: name, why: why };
  }
  function bullet(title, why) {
    return { title: title, why: why };
  }

  const packs = {
    hog: [
      {
        id: "HOG-R1",
        name: "Weekly Growth review",
        when: "Same weekday every week, before 11:00. Written. Not a WhatsApp voice note.",
        owner: "Head of Growth. Marketing, Intelligence, Offer, Expansion and Partnerships send their numbers the day before.",
        objective: "Decide keep, pause, or change on each live way of finding us using cost and bookings — not reach, not stories.",
        collect: [
          field("Spend vs the signed monthly plan, by ads, stalls, or partners", "So you see a leak before the month is gone."),
          field("Enquiries, qualified enquiries, and bookings — by source (digital, field, partner, other)", "People with no written answer for how they found us cannot be improved. Mixing sources hides the ad or page that sends nobody."),
          field("Cost per enquiry and cost per booking vs the signed target", "Cheap noise is still noise. Enquiries that never book are not Growth."),
          field("Lost-lead reasons from Customer Experience, grouped", "Growth learns from why people did not book — not from likes."),
          field("High opportunities still open, with age in working days", "A list of ideas nobody decided is not a decision."),
          field("One action for ads, stalls, or partners that are off plan", "A review without an action is a meeting.")
        ],
        mustHave: ["Week dates", "Plan vs actual spend", "Enquiries by source", "Bookings by source", "Cost per enquiry", "Cost per booking", "Top 3 lost-lead reasons", "Open High items + age", "One action for ads, stalls, or partners that are off plan", "Who owns each action"],
        passTo: [
          pass("CEO", "One page: plan vs actual, cost per booking, and anything that risks money, brand, or a launch."),
          pass("Marketing Manager", "The keep / pause / change for digital and field."),
          pass("Market Intelligence", "Lost-lead patterns and any High item that needs evidence."),
          pass("Offer / Expansion / Partnerships", "Only the actions that sit in their job.")
        ]
      },
      {
        id: "HOG-R2",
        name: "Monthly Growth plan (signed)",
        when: "Last week of the month. No spend starts until this is signed.",
        owner: "Head of Growth signs. Marketing Manager drafts. Intelligence, Offer and Expansion must clear what goes on it.",
        objective: "One written plan for who we sell to, what we may promise, where, through ads, stalls, or partners, with how much money, and what ‘good’ looks like.",
        collect: [
          field("Who we are selling to this month", "Ads, stalls, and partners must not invent a different customer."),
          field("Approved services only — with the claims Marketing may use", "A draft is not a product."),
          field("Approved towns only", "Ads in a town with no providers kill the brand."),
          field("Split of ads, stalls, and partners: digital, field, partner — budget and enquiry target", "Ads and stalls without numbers fight each other."),
          field("Cost per enquiry and cost per booking ceilings", "The plan is the boss, not the ad account."),
          field("What we will not do this month", "A plan that says yes to everything is not a plan.")
        ],
        mustHave: ["Month", "Customer picture", "Service list", "Town list", "Ads / stalls / partners × budget × target", "Cost ceilings", "Will-not-do list", "Head of Growth signature", "Date signed"],
        passTo: [
          pass("CEO", "One-page copy the same day it is signed."),
          pass("Marketing Manager", "The working plan. Digital and Field may not change it without a new signature."),
          pass("Partnerships", "Which kind of hotel desk or shops and towns are open."),
          pass("Customer Experience", "What will be promoted, so they are not surprised.")
        ]
      },
      {
        id: "HOG-R3",
        name: "List of important ideas",
        when: "Updated in the weekly review. High items cannot sit more than 10 working days without go, no-go, or a dated research note.",
        owner: "Head of Growth watches the 10-working-day limit. Intelligence owns the evidence pack.",
        objective: "Turn “we might look at this later” into a decision, so Growth does not run on rumours.",
        collect: [
          field("One row per idea: service, town, or kind of hotel desk or shop", "A list of dreams is not Intelligence."),
          field("Source and date of the evidence", "No fact without a source."),
          field("Age in working days", "The 10-working-day limit is the point of this file."),
          field("Decision: go, no-go, or more research with a date", "Never leave an idea as “we might look at this later”."),
          field("Who gets the file next if go (Offer, Expansion, or Partnerships)", "You discover. You do not do their job.")
        ],
        mustHave: ["Idea", "Type", "Evidence source + date", "Age", "Decision", "Next owner", "Review date"],
        passTo: [
          pass("Market Intelligence", "Keep the register true."),
          pass("Offer / Expansion / Partnerships", "Only the rows marked go."),
          pass("CEO", "Any High item older than 10 working days with no date.")
        ]
      }
    ],
    mkm: [
      {
        id: "MKT-R1",
        name: "Weekly marketing report",
        when: "Same weekday every week, the day before the Growth review.",
        owner: "Marketing Manager. Digital and Field send their numbers the day before this report.",
        objective: "Show whether the signed calendar produced enquiries where Operations wrote how they found us, inside budget — and name the one or two moves for next week.",
        collect: [
          field("Spend vs plan, digital and field separate", "Variance without a name is spend with no owner."),
          field("Enquiries by paid ads, by search (SEO), and by stalls", "So we can kill what is dead. Do not mix search into ads."),
          field("Share of people with a written answer for how they found us (target 95% or more)", "Customer Experience cannot convert a nameless lead."),
          field("Bookings from marketing leads, if Customer Experience has closed them", "Enquiries are not the result. Bookings are."),
          field("Creatives that failed the brand or fact check", "Trust dies when ads promise what the job cannot do."),
          field("One or two changes for next week — not ten", "A list of wishes is not a plan.")
        ],
        mustHave: ["Week dates", "Spend vs plan by ads, stalls, or partners", "Enquiries by paid ads / search / stalls", "Share of people with a written answer for how they found us", "Bookings from marketing", "Off-plan items", "Next-week actions", "Owner of each action"],
        passTo: [
          pass("Head of Growth", "This is the marketing page of the weekly Growth review."),
          pass("Digital Marketing Manager", "Their slice: keep, pause, change."),
          pass("Field Marketing Manager", "Their slice: which towns, which days."),
          pass("Customer Experience", "What will arrive next week and how Operations wrote they found us.")
        ]
      },
      {
        id: "MKT-R2",
        name: "Monthly marketing plan and calendar",
        when: "Last week of the month, before Head of Growth signs.",
        owner: "Marketing Manager drafts. Head of Growth signs. Digital and Field do not set their own month.",
        objective: "Turn the signed Growth plan into days, creatives, budgets, and targets Digital and Field can run without inventing the company.",
        collect: [
          field("Who, offer, town, paid ads or stall, SEO pages, budget, KPI for each line", "A calendar without a target is decoration. Search sits on its own line."),
          field("Approved creatives only — or a dated request for a new one", "No live file without brand and fact check."),
          field("What we will not promote", "Stops Digital ‘testing’ an unapproved service."),
          field("Field days named: town, date, materials", "Random Saturdays cannot be measured."),
          field("Tracking check: every digital line lands somewhere Customer Experience already uses", "Spend with no written answer for how they found us is a leak.")
        ],
        mustHave: ["Month", "Line-by-line calendar", "Budget split", "Creative list", "Will-not-do", "Tracking destinations", "Head of Growth signature"],
        passTo: [
          pass("Head of Growth", "To sign. Spend does not start without this."),
          pass("Digital and Field", "Their working calendar the same day it is signed."),
          pass("Customer Experience", "How leads will arrive and how Operations wrote they found us.")
        ]
      },
      {
        id: "MKT-R3",
        name: "List of enquiries with no source",
        when: "Every working day if Operations has a person with no answer for where they found us. Empty list is the goal.",
        owner: "Marketing Manager. Operations asks today. Digital reads the answers. Digital does not ask the customer.",
        objective: "Never let a person sit with no answer. Operations asks. Operations writes.",
        collect: [
          field("Call, WhatsApp, app, form, stall, or other — and whether Operations asked", "So you can see who still has no answer."),
          field("Who in Operations will ask, by when today", "Same day or it did not happen."),
          field("Pause decision if the source cannot be found", "A campaign you cannot measure must stop.")
        ],
        mustHave: ["Date", "Lead id or phone", "Missing answer", "Owner", "Answer written by (time)", "Paused? yes/no"],
        passTo: [
          pass("Customer Experience", "The corrected tag the same day."),
          pass("Head of Growth", "Only if a campaign had to be paused.")
        ]
      }
    ],
    hom: [
      {
        id: "DIG-R1",
        name: "Daily digital note",
        when: "Every working day if spend jumped, tracking broke, or you paused something. Short. Written.",
        owner: "Digital Marketing Manager.",
        objective: "Catch a leak the same day — not in next week’s meeting.",
        collect: [
          field("Spend today and month-to-date vs the ads line", "The plan is the boss, not the ad account."),
          field("New digital leads, all with a named online place (Meta, Google, search, social, other)", "If Operations did not write how they found us, pause that online place."),
          field("Anything paused, and why", "Marketing Manager must not discover a pause by accident."),
          field("Files that went live today, filed in the library?", "If it is not filed, it did not happen.")
        ],
        mustHave: ["Date", "Spend today", "Spend vs plan", "New enquiries by named online place", "Pauses", "Tracking broken?", "Library same day?"],
        passTo: [
          pass("Marketing Manager", "Every working day when something moved. This is how they unblock you."),
          pass("Customer Experience", "Only the new enquiries — not the commentary.")
        ]
      },
      {
        id: "DIG-R2",
        name: "Weekly digital pack",
        when: "The day before the marketing weekly report. Must include fails, not only wins.",
        owner: "Digital Marketing Manager.",
        objective: "Show, by named online place, whether the signed plan produced enquiries where Operations wrote how they found us — and name 3 things that worked, 3 that failed, and one written request.",
        collect: [
          field("Meta, Google, search, social, other — each on its own line: spend, enquiries, cost, share of people with a written answer for how they found us", "Do not hide an ad or page that sends nobody inside one lump called “digital”."),
          field("3 things that worked", "So we do more of them."),
          field("3 things that failed", "A report with only wins hides bad spend."),
          field("One written request for next week", "More money, a new video, or pause a town — not ten wishes."),
          field("Content log: what went live, where, service, town", "The library is how we learn."),
          field("Bookings from digital leads, if Customer Experience has closed them", "Enquiries are not the result. Bookings are."),
          field("Booking chats handed from comments or threads to Customer Experience", "You generate. You do not close.")
        ],
        mustHave: ["Week dates", "Table by named online place", "Spend vs plan", "Cost vs ceiling", "Share of people with a written answer for how they found us", "3 wins", "3 fails", "one written request", "Content log", "Owner of the written request"],
        passTo: [
          pass("Marketing Manager", "This is the digital page of the weekly marketing report. They send it up to Growth."),
          pass("Intelligence", "Only the ‘people keep asking for X’ lines — as a signal, not a new ad."),
          pass("Customer Experience", "Anything still sitting in a comment or a thread.")
        ]
      },
      {
        id: "DIG-R3",
        name: "Paid ad launch record",
        when: "Once, before spend starts on that ad.",
        owner: "Digital Marketing Manager.",
        objective: "Prove the kit, the file, the town, the budget, and a test lead into Customer Experience were checked — so a live ad is never a guess.",
        collect: [
          field("Town, service, words, budget — matching the signed kit and calendar", "Off-plan launches split the company in two."),
          field("Library file used", "No file from a personal phone."),
          field("Test lead into Customer Experience — pass or fail", "If it fails, do not launch."),
          field("Customer Experience told the ad is live and how leads will arrive", "Speed dies if Customer Experience does not know.")
        ],
        mustHave: ["Calendar line", "File name", "Destination", "Test lead pass", "Budget", "Customer Experience told", "Launch time"],
        passTo: [
          pass("Marketing Manager", "Filed with the ads calendar."),
          pass("Customer Experience", "Launch note the same hour.")
        ]
      },
      {
        id: "DIG-R4",
        name: "Content library log",
        when: "Same day a file is made or goes live. Roll up in the weekly written report.",
        owner: "Digital Marketing Manager. Content Maker files the raw finished file. You own that the log is true.",
        objective: "One dated list of every video and post: what it is, where it came from, where it went, what it did.",
        collect: [
          field("Name, type (ad / social / search / other), service, town, date", "If any of these is missing, it cannot be reused."),
          field("Source of the raw film (Operations, founder, AI)", "Customer and provider videos come from Operations."),
          field("Operations named as source on customer and job films", "Those files are collected and edited, then filed."),
          field("Where it went live, and result if you have it", "A file with no result yet is still filed. Do not wait.")
        ],
        mustHave: ["File name", "Type", "Service", "Town", "Date", "Source", "Where live", "Result or ‘none yet’"],
        passTo: [
          pass("Marketing Manager", "The weekly roll-up, not every file."),
          pass("Content Maker", "The file list for next week, from gaps in this log.")
        ]
      }
    ],
    cmc: [
      {
        id: "CMC-R1",
        name: "Same-day file note",
        when: "Every working day on which you finish a file, or on which a file-list line cannot move.",
        owner: "Content Maker.",
        objective: "The Digital Marketing Manager always knows what is waiting for a yes, what they approved into the library, and what is waiting on Operations footage — the same day.",
        collect: [
          field("Files sent for approval today, with names", "The Digital Marketing Manager must be able to open the review file."),
          field("Files the Digital Marketing Manager approved into the library today, with library names", "The library copy is the company copy."),
          field("Files the Digital Marketing Manager sent back today, with the reason and whether a remake went out", "A no is how first-pass approval is counted."),
          field("File-list items still waiting, and what is waiting", "Usually Operations footage, or a no from the Digital Marketing Manager."),
          field("Anything that went off-kit and was stopped", "The Digital Marketing Manager must see the stop, not only the success.")
        ],
        mustHave: ["Date", "Waiting for approval", "Approved into library", "Sent back or 'none'", "Blocked items", "What is waiting", "Stopped items or 'none'"],
        passTo: [
          pass("Digital Marketing Manager", "Same working day, after files are sent for approval or placed in the library.")
        ]
      },
      {
        id: "CMC-R2",
        name: "Weekly production pack",
        when: "The working day before the Digital Marketing Manager writes the weekly digital pack.",
        owner: "Content Maker.",
        objective: "Show, line by line, what the file list asked for, what the Digital Marketing Manager approved into the library, the week's approval rates, and what is still waiting — including misses.",
        collect: [
          field("File list versus delivered", "Each line: sent for approval, approved into library, blocked, or missed, with a reason."),
          field("First-pass approval rate and eventual approval rate", "First-pass yes versus files the Digital Marketing Manager decided, and eventual yes after remakes."),
          field("Counts by type: AI posts, AI video, brand, customer, job, personal-brand", "The Digital Marketing Manager must see which well is dry."),
          field("Operations footage still outstanding, with working days waiting", "So the Digital Marketing Manager can ask Operations."),
          field("One production risk", "One named constraint.")
        ],
        mustHave: ["Week dates", "Asked vs delivered table", "First-pass approval rate", "Eventual approval rate", "Counts by type", "Outstanding Operations footage", "One risk"],
        passTo: [
          pass("Digital Marketing Manager", "This is the content page of the weekly digital pack.")
        ]
      },
      {
        id: "CMC-R3",
        name: "Next-month film plan support",
        when: "Last week of the month, in time for the Digital Marketing Manager to help write next month's plan.",
        owner: "Content Maker.",
        objective: "List the files next month will need, and which Operations footage must be collected before week one.",
        collect: [
          field("Assets needed next month, by type and service", "So the Digital Marketing Manager can write a real file list."),
          field("Operations footage to collect before week one", "Customer feedback and provider videos, named, with a latest date."),
          field("Library leftovers that can be reused", "Reuse files that already exist and are still on kit."),
          field("Tools or access that failed this month", "Write it so it can be fixed before next month.")
        ],
        mustHave: ["Month", "Needed assets table", "Reuse list", "Operations footage to collect", "Access issues or 'none'"],
        passTo: [
          pass("Digital Marketing Manager", "They fold it into next month's digital slice. Do not send this pack to Head of Growth.")
        ]
      }
    ],
    fmm: [
      {
        id: "FLD-R1",
        name: "Weekly field report",
        when: "The day before the marketing weekly report.",
        owner: "Field Marketing Manager.",
        objective: "Show visits, first local workers, and contracts separately. Name towns that produced nothing after three visits.",
        collect: [
          field("Visit days vs calendar (completed / did not happen)", "A day that did not happen is a missed town. Write it."),
          field("Names to Customer Experience, by town", "Towns that produced nothing must be named so the plan can change."),
          field("First local workers who signed and whose files went to Provider Experience", "A town with a stall and nobody who can do the jobs is a brand problem."),
          field("Office and society contracts signed, waiting, or failed", "A verbal yes is not a contract."),
          field("Empty jobs you covered this week", "A job with no named person has no owner.")
        ],
        mustHave: ["Week", "Visits × town", "First local workers", "Contracts", "Days that did not happen", "Towns that produced nothing", "Notes of empty jobs you covered", "One or two written requests"],
        passTo: [
          pass("Marketing Manager", "This is the field page of the weekly marketing report."),
          pass("Head of Growth", "Only if a town is dead three visits and still on next month’s draft — through Marketing Manager.")
        ]
      },
      {
        id: "FLD-R2",
        name: "Covering-empty-job note",
        when: "Any day Field Visitor, First Provider Onboarding, or Office and Society Contracts is empty, and you are doing that job.",
        owner: "Field Marketing Manager.",
        objective: "Write which box is empty, that you are doing that job, and the same-day checks you did in that job’s name.",
        collect: [
          field("Which job is empty", "Visitor, first providers, or contracts — name it."),
          field("What you did today in that job’s name", "The same-day list, the signed file, or the contract visit.")
        ],
        mustHave: ["Date", "Which job", "You are doing that job today", "Same-day checks"],
        passTo: [
          pass("File", "Every day the job is empty."),
          pass("Marketing Manager", "On the weekly field report.")
        ]
      }
    ],
    fve: [
      {
        id: "VIS-R1",
        name: "Same-day stall list",
        when: "Before you leave the town. Same day as the visit. Not the next morning.",
        owner: "Field Visitor.",
        objective: "Hand Customer Experience a complete list so they can call. You collect names at the stall. Customer Experience books the job and finishes it. You do not take money or book the job yourself.",
        collect: [
          field("Name, phone, service wanted, town", "A missing phone is not an enquiry. Do not pretend."),
          field("Source = stall + town + date", "So we can score the town, not just ‘marketing’. You write this because you met the person."),
          field("Anything people kept asking that we do not sell", "Send as a signal through Field Marketing Manager — not as a new offer you invented.")
        ],
        mustHave: ["Date", "Town", "Pitch", "Each row: name, phone, service, town, source=stall + town + date", "Count of complete rows", "Customer Experience received? time"],
        passTo: [
          pass("Customer Experience", "The list the same day. Confirm they received it."),
          pass("Field Marketing Manager", "The count, not the phone numbers.")
        ]
      },
      {
        id: "VIS-R2",
        name: "Weekly visit note",
        when: "The day before Field Marketing Manager’s weekly field report.",
        owner: "Field Visitor.",
        objective: "Show which planned towns produced enquiries, and name towns that are dead three visits in a row.",
        collect: [
          field("Visit days vs calendar (completed / missed)", "A missed day is a missed town. Write it."),
          field("Enquiries per planned visit day, by town", "Dead towns must be named so the plan can change."),
          field("Materials used and what needs reprinting", "A flyer with last year’s price becomes a fight.")
        ],
        mustHave: ["Week", "Town × day × enquiries", "Missed days", "Dead-town flag", "Material needs"],
        passTo: [
          pass("Field Marketing Manager", "This is the stall page of the weekly field report.")
        ]
      },
      {
        id: "VIS-R3",
        name: "Stall log",
        when: "Every visit day, packed with the same-day list.",
        owner: "Field Visitor.",
        objective: "Prove we stood where the plan said, with approved materials, and did not take money or bookings.",
        collect: [
          field("Town, pitch, start and end", "If it is not written, it did not happen."),
          field("Materials used (this month’s approved boards and flyers only)", "Wrong price on a board is a brand problem."),
          field("Incidents: fight, false claim, someone taking bookings in our name", "Stop. Write. Escalate the same day.")
        ],
        mustHave: ["Date", "Town", "Times", "Materials", "Enquiry count", "Incidents", "No-cash / no-booking confirmation"],
        passTo: [
          pass("Field Marketing Manager", "Filed. Incidents the same day."),
          pass("Customer Experience", "Do not send the stall log — send the lead list.")
        ]
      }
    ],
    fpo: [
      {
        id: "FPO-R1",
        name: "Signed first-provider file",
        when: "The same week as the signature.",
        owner: "First Provider Onboarding.",
        objective: "Hand Provider Experience a complete signed pack file so they can put the person on the roster. You do not assign the first job.",
        collect: [
          field("Name, phone, services they can do, towns they can cover, start date", "Incomplete is not signed. Do not pretend they can work with us yet."),
          field("Signed first-worker papers", "A verbal yes is not a signed file."),
          field("Provider Experience received? date", "If they did not receive it, it did not happen.")
        ],
        mustHave: ["Date", "Town", "Complete signed file", "Received date"],
        passTo: [
          pass("Provider Experience", "The signed file the same week."),
          pass("Field Marketing Manager", "The count.")
        ]
      },
      {
        id: "FPO-R2",
        name: "Weekly first-worker note",
        when: "The day before Field Marketing Manager’s weekly field report.",
        owner: "First Provider Onboarding.",
        objective: "Show visits vs calendar, files signed, files handed over, and towns with a gap.",
        collect: [
          field("Visits vs calendar", "A missed visit is a missed town."),
          field("Files signed and handed to Provider Experience", "A private list is not a system."),
          field("Towns with a gap", "Field Marketing Manager must stop the stall if nobody will sign.")
        ],
        mustHave: ["Week", "Visits", "Signed", "Handed", "Gaps", "Asks"],
        passTo: [
          pass("Field Marketing Manager", "This is the first-provider page of the weekly field report.")
        ]
      }
    ],
    flc: [
      {
        id: "OSC-R1",
        name: "Signed contract file",
        when: "The same week as the signature.",
        owner: "Office and Society Contracts.",
        objective: "File the contract and tell Operations they can plan the jobs. Finance invoices. You do not take cash.",
        collect: [
          field("Named building, town, services, signed price, start date, who signed", "A WhatsApp yes is not a contract."),
          field("Operations told? date", "If Operations does not know, the jobs will not happen.")
        ],
        mustHave: ["Date", "Building", "Town", "Signed pack file", "Operations told?"],
        passTo: [
          pass("File", "The signed contract."),
          pass("Operations", "The building is live."),
          pass("Field Marketing Manager", "The count.")
        ]
      },
      {
        id: "OSC-R2",
        name: "Weekly contract note",
        when: "The day before Field Marketing Manager’s weekly field report.",
        owner: "Office and Society Contracts.",
        objective: "Show visits vs calendar, contracts signed, waiting, or failed, and buildings Operations can now serve.",
        collect: [
          field("Visits vs calendar", "A missed visit is a missed building."),
          field("Contracts signed, waiting, or failed", "A verbal yes is not a contract."),
          field("Buildings Operations can now serve", "A signed file nobody told Operations about is not live.")
        ],
        mustHave: ["Week", "Visits", "Signed", "Waiting", "Failed", "Asks"],
        passTo: [
          pass("Field Marketing Manager", "This is the contracts page of the weekly field report.")
        ]
      }
    ],
    mim: [
      {
        id: "MI-R1",
        name: "Weekly intelligence report",
        when: "Same weekday every week, built from the registers — not from chat.",
        owner: "Market Intelligence Manager.",
        objective: "Give Head of Growth a true picture of demand, competitors, and opportunities, with source and date on every fact.",
        collect: [
          field("Enquiries and bookings by service and area from the CRM extract", "Chat is not a tracker."),
          field("Lost-lead reasons that repeat", "A pattern is a signal to Offer or Marketing — a one-off is noise."),
          field("Competitor changes this week, with source and date", "A rumour is not a competitor move."),
          field("Opportunity funnel: new, validate, go, no-go, research-with-date", "Never leave a row as ‘interesting’."),
          field("Same-day alerts already sent", "Do not hide a move that can steal bookings this week.")
        ],
        mustHave: ["Week", "Demand table by service × area", "Competitor change log", "Opportunity counts by stage", "High items + age", "Source on every new fact"],
        passTo: [
          pass("Head of Growth", "This is the Intelligence page of the weekly Growth review."),
          pass("Offer / Expansion / Partnerships", "Only the newly validated go packs that belong in their job.")
        ]
      },
      {
        id: "MI-R2",
        name: "Opportunity validation pack",
        when: "When a row is in validate. Close in 10 working days unless Head of Growth dates a delay.",
        owner: "Market Intelligence Manager. Head of Growth watches the 10-working-day limit.",
        objective: "Decide go, no-go, or more research with a date — with evidence, not opinion.",
        collect: [
          field("The idea in one sentence, typed as service, area, or channel", "One idea, one pack."),
          field("Demand evidence with source and date", "No fact without a source."),
          field("Competition evidence", "What they actually offer, not what someone heard."),
          field("Whether we could operate (ask Operations or Expansion — do not guess)", "A go we cannot finish is a complaint machine."),
          field("Decision and who gets the file next if go", "You discover. You do not write the service or enter the town.")
        ],
        mustHave: ["Idea", "Type", "Evidence list", "Sources + dates", "Decision", "Who gets the file next", "Date closed"],
        passTo: [
          pass("Head of Growth", "In the weekly pack, and the same day if it is High."),
          pass("Offer or Expansion or Partnerships", "Only if the decision is go.")
        ]
      },
      {
        id: "MI-R3",
        name: "Monthly decision pack",
        when: "Last week of the month, before the Growth plan is signed.",
        owner: "Market Intelligence Manager.",
        objective: "Four-week demand vs the four weeks before, a full competitor pass, and a clean opportunity funnel — so next month’s plan is not a guess.",
        collect: [
          field("Four-week demand vs prior four weeks, by service and area", "Direction matters more than one noisy week."),
          field("Full competitor pass", "Prices, towns, new offers — source and date."),
          field("Opportunity funnel with no silent rows", "Silent means you missed the clock."),
          field("What Marketing must not keep guessing about", "Stop briefing ads on a hunch.")
        ],
        mustHave: ["Month", "Demand comparison", "Competitor summary", "Funnel", "Stop-guessing list"],
        passTo: [
          pass("Head of Growth", "Before they sit with Marketing to sign next month."),
          pass("Marketing Manager", "The stop-guessing list only — not a licence to invent services.")
        ]
      },
      {
        id: "MI-R4",
        name: "Competitor same-day alert",
        when: "The same working day a move can steal bookings this week.",
        owner: "Market Intelligence Manager.",
        objective: "Get a fact to Head of Growth while there is still time to pause or answer — not in next week’s report.",
        collect: [
          field("What changed, source, date, likely hit on our bookings", "Speed with evidence. Not a panic.")
        ],
        mustHave: ["Time sent", "What changed", "Source", "Likely hit", "Suggested owner (Marketing / Offer / Expansion)"],
        passTo: [
          pass("Head of Growth", "Same working day. They decide who acts.")
        ]
      }
    ],
    osd: [
      {
        id: "OSD-R1",
        name: "Service sheet",
        when: "When Intelligence validates a need, or Head of Growth asks. Before Finance prices and Operations says they can deliver.",
        owner: "Offer & Service Development Manager.",
        objective: "Write what Panun Kaergar can sell and deliver: name, who it is for, in, out, job steps, quality bar. A name without a sheet is not a service.",
        collect: [
          field("Name and who it is for", "Customer Experience must not pick from a ghost menu."),
          field("What we do, and what we do not do", "Inclusions and exclusions stop fights later."),
          field("What the customer must prepare", "Jobs fail when the house is not ready and nobody said so."),
          field("What the provider must be able to do", "Operations cannot guess the product."),
          field("Job steps and quality bar", "This is the franchise prototype for the work."),
          field("Questions still open for Finance and Operations", "Do not hide gaps. Send the sheet with the gaps named.")
        ],
        mustHave: ["Service name", "Customer", "In", "Out", "Customer prep", "Provider skills", "Job steps", "Quality bar", "Open questions", "Version + date"],
        passTo: [
          pass("Finance", "To price. You recommend. They sign."),
          pass("Operations", "Can we deliver? Which towns, which providers."),
          pass("Head of Growth", "Only when price and operations answers are on the sheet — then they say yes or no.")
        ]
      },
      {
        id: "OSD-R2",
        name: "Launch checklist",
        when: "Before Head of Growth says Marketing may talk. 100% complete or it does not launch.",
        owner: "Offer & Service Development Manager, with Operations.",
        objective: "Prove we can finish the first jobs before anyone advertises.",
        collect: [
          field("Sheet complete, price signed, operations ‘can deliver’ signed", "Selling a job we cannot finish creates complaints."),
          field("First towns and provider types named", "A national promise with no one in Srinagar is a lie."),
          field("The only claims Marketing may use", "They may not invent a sentence."),
          field("Review date after the first jobs", "A launch without a review never ends.")
        ],
        mustHave: ["Service", "Price signed", "Ops signed", "Towns", "Claims list", "Review date", "Head of Growth yes"],
        passTo: [
          pass("Head of Growth", "To sign yes."),
          pass("Marketing", "Claims list only after yes."),
          pass("Customer Experience and Operations", "Live catalogue line.")
        ]
      },
      {
        id: "OSD-R3",
        name: "Monthly catalogue pass",
        when: "Once a month, before the Growth plan is signed.",
        owner: "Offer & Service Development Manager.",
        objective: "One live list of what we sell. Take off the live list any service name that has no written sheet. Recommend keep, fix, or stop with numbers.",
        collect: [
          field("Every live service: sheet complete? yes/no", "100% is the standard."),
          field("Demand, complaints, cancellations", "A dead service still in ads wastes money."),
          field("Recommend keep, fix, or stop", "A complaint pattern is a design problem, not only a provider problem.")
        ],
        mustHave: ["Month", "Live list", "Sheet gaps", "Keep/fix/stop", "Evidence"],
        passTo: [
          pass("Head of Growth", "Before they sign next month’s plan."),
          pass("Marketing", "Only after Head of Growth signs a stop — then they must pull the ads.")
        ]
      }
    ],
    mem: [
      {
        id: "ME-R1",
        name: "Feasibility pack (enter / wait / no)",
        when: "Within 15 working days once Intelligence hands a named town.",
        owner: "Market Expansion Manager. Provider Experience must answer capacity. Head of Growth signs the decision.",
        objective: "A written enter, wait, or no — so we do not advertise a town circled on a map with no written yes.",
        collect: [
          field("Named town, demand evidence, competition", "Hope is not a plan."),
          field("Can we finish the first services there? (Provider Experience)", "If we cannot finish jobs, we do not advertise."),
          field("Rough cost to win a customer", "Cheap ads in a town we cannot serve are still waste."),
          field("Decision: enter, wait (with date), or no", "Research with no decision is a delay, not your job done.")
        ],
        mustHave: ["Town", "Demand", "Competition", "Capacity yes/no", "Rough cost", "Enter/wait/no", "Wait date if wait", "Head of Growth signature"],
        passTo: [
          pass("Head of Growth", "To sign."),
          pass("Marketing and Partnerships", "Only after enter is signed — the towns they may spend in."),
          pass("Provider Experience", "The capacity they already confirmed, so they can staff.")
        ]
      },
      {
        id: "ME-R2",
        name: "Launch plan",
        when: "After enter is signed and capacity is real. Before any marketing spend in that town.",
        owner: "Market Expansion Manager.",
        objective: "Name first services, what marketing may do, launch week, and the review date.",
        collect: [
          field("First services list (approved catalogue only)", "Do not invent a local menu."),
          field("Marketing may-spend list", "Ads, stalls, and partners do not invent the town."),
          field("Launch week and review date", "A launch without a review date never ends."),
          field("What ‘good’ looks like in the first period (enquiries, bookings, finished jobs)", "Bookings vs finished jobs — not pins on a map.")
        ],
        mustHave: ["Town", "First services", "Spend list", "Launch week", "Review date", "First-period KPIs", "Head of Growth yes"],
        passTo: [
          pass("Head of Growth", "To sign before spend."),
          pass("Marketing and Partnerships", "Working launch list."),
          pass("Customer Experience and Operations", "Launch week and first services.")
        ]
      },
      {
        id: "ME-R3",
        name: "Post-launch review",
        when: "On the dated review. Not ‘when we get time’.",
        owner: "Market Expansion Manager.",
        objective: "Recommend scale, fix, pause, or leave — with numbers. Staying forever in a dead town is waste.",
        collect: [
          field("Enquiries, bookings, finished jobs vs the launch bar", "Jobs failing while ads run is how brands die in public."),
          field("Provider capacity then vs now", "Do not keep a launch date when nobody can staff."),
          field("Written pause or leave if the bar is missed", "Exit is written, not whispered.")
        ],
        mustHave: ["Town", "Review date", "Plan vs actual", "Recommendation", "Stop-marketing? yes/no"],
        passTo: [
          pass("Head of Growth", "To sign scale / fix / pause / leave."),
          pass("Marketing, Customer Experience, Operations", "The same day if the answer is pause or leave.")
        ]
      }
    ],
    pcm: [
      {
        id: "PC-R1",
        name: "Partner qualify sheet",
        when: "When a new name appears. Before anyone talks money.",
        owner: "Partnerships & Channels Manager.",
        objective: "Mark fit, wait, or no. Do not add your uncle or a neighbour who says they know people. That is not a partner. A loud partner with bad jobs hurts the brand.",
        collect: [
          field("Who they already serve, and where", "Fit with our customer, or it is vanity."),
          field("What we want from them (leads, not cash side deals)", "You find the hotel or shop. Customer Experience talks to the customer. Customer Experience owns the customer."),
          field("Reputation and whether we can work with them", "Stop a partner who would sell a job we do not do."),
          field("Decision: fit / wait / no", "A maybe is a wait with a date, or it is no.")
        ],
        mustHave: ["Partner name", "Type", "Who they serve", "What we want", "Fit/wait/no", "Date"],
        passTo: [
          pass("Head of Growth", "Only fit names, when you need yes on the kind of hotel desk or shop and how we pay them, if we pay them."),
          pass("Intelligence", "If a new type keeps appearing — that is a channel-type signal.")
        ]
      },
      {
        id: "PC-R2",
        name: "Partner term sheet and live file",
        when: "Before you call them live. After Head of Growth agrees the type and terms. Handshake-only deals are forbidden.",
        owner: "Partnerships & Channels Manager.",
        objective: "Written terms, how a lead is sent, what we pay if we pay. Test one lead into Customer Experience before go-live.",
        collect: [
          field("How a lead is sent (form, WhatsApp group with Customer Experience, desk card)", "You never book the customer yourself. Send them to Customer Experience. You never hand a lead to a provider."),
          field("What we pay, if we pay — Finance must see money we did not plan", "Do not promise payment yourself."),
          field("Approved materials only", "They must not invent a price or a service."),
          field("Test lead into Customer Experience — pass/fail", "Only then call them live.")
        ],
        mustHave: ["Partner", "Lead path", "Terms", "Materials", "Test lead", "Live date", "Head of Growth yes on type"],
        passTo: [
          pass("Customer Experience", "How their leads will arrive, before go-live."),
          pass("Finance", "Anything that needs money we did not plan — as an ask, not a promise."),
          pass("Head of Growth", "The live file after the test lead passes.")
        ]
      },
      {
        id: "PC-R3",
        name: "Same-day partner lead log",
        when: "Every partner enquiry, the same day.",
        owner: "Partnerships & Channels Manager.",
        objective: "Tagged leads to Customer Experience: partner name, service, town. Never to a provider. Never closed by you.",
        collect: [
          field("Source = partner + partner name", "People with no hotel or shop name written cannot be scored."),
          field("Service and town", "Customer Experience cannot convert a nameless job."),
          field("Whether it booked, later, from Customer Experience", "A silent partner is a closed partner — you will need this in the monthly review.")
        ],
        mustHave: ["Date", "Partner", "Phone/name", "Service", "Town", "Customer Experience received", "Booked? (updated)"],
        passTo: [
          pass("Customer Experience", "The lead the same day."),
          pass("Head of Growth", "Only the monthly roll-up, unless a partner is taking money in our name — then the same day.")
        ]
      },
      {
        id: "PC-R4",
        name: "Monthly partner review",
        when: "Once a month, every live partner. 100%.",
        owner: "Partnerships & Channels Manager.",
        objective: "Keep, fix, or close. A silent partner is a closed partner — write it down.",
        collect: [
          field("Leads, bookings, junk-lead rate by partner", "Fix a partner who sends junk."),
          field("Written terms still in force?", "No live partner without a term sheet."),
          field("Keep / fix / close", "Closing is a file, not a mood.")
        ],
        mustHave: ["Month", "Partner table", "Keep/fix/close", "Actions"],
        passTo: [
          pass("Head of Growth", "The keep / fix / close list."),
          pass("Customer Experience", "Partners you will close, so they are not waiting on a hotel that sends nobody."),
          pass("Finance", "Any close that still has money attached.")
        ]
      }
    ]
  };

  const owns = {
    hog: [
      bullet("You keep one written picture of who we sell to, and why they buy", "If this is fuzzy, Marketing, Field, and partners invent a different company. You write this picture. They work from it."),
      bullet("You keep the sentences we may say this month", "Offer & Service Development Manager writes what is in and out of a service. You sign. Marketing Manager copies only those sentences into the instruction book of words, prices, towns, and faces. Nobody invents a new promise in an ad or at a stall."),
      bullet("You sign the monthly Growth plan and the money before any spend starts", "Marketing Manager drafts who, services, towns, ads or stalls or partners, money, and targets. You sign. They may not change it without a new signature. Spend without a signature is guessing, not a plan."),
      bullet("You say yes, no, or send back on a new service", "Offer & Service Development Manager brings a complete sheet. Finance has priced it. Operations has said they can do the first jobs. Yes means Marketing Manager may talk. You do not write the sheet yourself."),
      bullet("You say yes, no, or send back on a new town, and on a new kind of hotel desk or shop", "Market Expansion Manager writes enter, wait, or no. Provider Experience must be able to finish the first jobs. Partnerships & Channels Manager waits for your yes on that kind of place and how we pay them, if we pay them. You do not hire workers and you do not sign the hotel desk yourself."),
      bullet("You hold the five people to one result each, and you send honest numbers to the CEO", "Marketing Manager, Market Intelligence Manager, Offer & Service Development Manager, Market Expansion Manager, and Partnerships & Channels Manager. Every week: money, enquiries, bookings, cost, why people did not book. If a job has nobody in it, you do that work and write that you are doing it today.")
    ],
    mkm: [
      bullet("You write the monthly marketing plan and the day-by-day calendar", "Head of Growth signs this plan before the month starts. It names which services we may talk about, which towns we may work in, which days ads and stalls run, how much money each gets, which website pages Digital must keep true for search (SEO), and what we will not advertise. Digital and Field work only from this one written plan. They do not write a different month for themselves."),
      bullet("You write the marketing kit — the instruction book for ads, pages, and stalls", "This book holds the logo, colours, sentences, prices, towns, and faces that may appear this month. You put your name and the month on it. Digital and Field may only use what is in this book. If they want a new sentence, price, town, or face, they stop and ask you first."),
      bullet("You split the money: ads money for Digital, stall money for Field", "You decide how much Digital Marketing Manager may spend on paid ads, and how much Field Marketing Manager may spend on stalls and printed boards. They cannot take each other’s money. Search (SEO) is also Digital’s work, but people who found us on Google without a paid ad are counted on their own line — not mixed into ads spend."),
      bullet("You make sure Operations asked every new person how they found us, and wrote the answer", "Operations asks on the call, WhatsApp, app, website form, or any other way the person arrived: ‘How did you find us?’ and writes one name from a short list you gave them: paid ad, search, stall, WhatsApp, app, form, or other. You do not write that answer yourself. You check it is there the same day. Digital cannot see it from the phone number. Digital reads what Operations wrote. If the answer is missing, Operations asks today. If Field met the person at a stall, Field also writes the town and the date."),
      bullet("You give Digital Marketing Manager their written work for the month", "You give them which days, how much ads money, the marketing kit, which website pages search (SEO) must cover this month, and where a call, WhatsApp, app, or form must land. Paid ads and search are both in that job. If nobody sits in Digital, you do that work yourself and write: you are doing it today because nobody is in that job."),
      bullet("You give Field Marketing Manager their written work for the month", "You give them which towns, which days, which boards and flyers, how much stall money, which towns need first local workers signed onto us, which offices and housing societies, and the signed papers they may use. If nobody sits in Field, you do that work yourself and write: you are doing it today because nobody is in that job.")
    ],
    hom: [
      bullet("You run the paid ads that are on this month’s plan", "Facebook/Instagram (Meta), Google ads, and any other paid ads the plan named. Only this job spends ads money. A live ad with nobody watching it is wasted money."),
      bullet("You run unpaid online work: website search, the app store, social posts that are not paid, Reddit, Quora, and other sites", "These pages and posts must use the same words as the ads. People who found us this way are counted on their own line. They are not mixed into ads spend."),
      bullet("You keep the company folder of videos and posts that you have said yes to", "Content Maker makes and edits the files. You write yes. Only then may a file go live on ads, social, search, or other sites. A file only on a phone is not the company copy."),
      bullet("You give Content Maker a written list of files to make, and you say yes or no on each finished file", "If nobody sits in Content Maker, you make the files yourself and write: you are doing that job because nobody is in it."),
      bullet("You read the answers Operations wrote: how many people said they found us from your ads or search pages", "You do not ask the customer. Operations asks and writes paid ad or search. You read those answers. If a person has no answer, that is Marketing Manager’s missing-answer list. You do not guess."),
      bullet("You send Marketing Manager a daily note when something moved, and a weekly written report", "The weekly report names money spent, how many people enquired, what you paused, three things that worked, three that failed, and one written request for next week. A report with only good news hides wasted spend.")
    ],
    cmc: [
      bullet("You make AI videos and still images from this month’s instruction book", "These are generated videos (.mp4) and still images (.jpg or .png) using only the allowed words, look, and scenes. They are not live footage from a job."),
      bullet("You make company films from this month’s instruction book", "These films show who Panun Kaergar is, what a customer can expect, and how a job is done. The founder does not have to appear."),
      bullet("You make films with the founder or another named person only when that face is already allowed this month", "If a new face is needed, that is a change to the instruction book. Stop. The Digital Marketing Manager asks Marketing Manager."),
      bullet("You collect customer and provider videos from Operations, then edit them to the instruction book", "Operations records those videos on the job. You do not go on the job to film. You collect the raw files, cut them, and send them for approval."),
      bullet("You name every finished file and send it for approval the same working day", "Put the finished video or still in the review folder the Digital Marketing Manager named. Only a written yes puts it in the company folder."),
      bullet("You keep a production list so Digital Marketing Manager can see each file without a meeting", "Each line is waiting, sent back, approved, in the company folder, or waiting on Operations footage. ‘Waiting on Operations’ means the raw job video has not arrived yet.")
    ],
    fmm: [
      bullet("You write which town, which day, and which of the three jobs goes", "Marketing Manager writes the month. Head of Growth signs it. You turn that into days for Field Visitor, First Provider Onboarding, and Office and Society Contracts. You do not pick a new town yourself."),
      bullet("You give Field Visitor their towns and days, and you check they actually went", "Field Visitor is the person who stands at a stall or walks the street. They may be a full-time employee, or an outside person paid on a written contract. The work is the same. A verbal yes with no written contract is not a Field Visitor. If nobody sits in that job, you go yourself and write that you are doing that job."),
      bullet("You send First Provider Onboarding to sign the first local workers in a named town", "Those workers are the first plumbers, electricians, or other service people in that town who will do jobs for Panun Kaergar. After they sign, the file goes to Provider Experience, who puts them on customer jobs. You do not keep those people as your own private list."),
      bullet("You send Office and Society Contracts to write maintenance contracts with named offices and housing societies", "They use only the signed contract papers. A verbal yes is not a contract. Hotel desks and shops that send us their customers belong to Partnerships, not this job."),
      bullet("You give out only the signed first-worker papers and the signed office-contract papers", "Offer and Finance have already signed those papers. Nobody on the ground invents a price or a payment. If a price is missing, you stop and tell Marketing Manager."),
      bullet("You send Marketing Manager a weekly field report that names visits, first local workers, and office contracts", "Write which planned days happened, how many names went to Customer Experience, how many first local workers signed and were sent to Provider Experience, how many office or society contracts signed, which days were missed, which towns produced nothing, and one or two written requests. Name what failed, not only what worked.")
    ],
    fve: [
      bullet("You stand in the named town on the named day, with only this month’s boards and flyers", "The stall place on that street is the place Field Marketing Manager named. Use only the words, prices, and faces in this month’s instruction book. A new sentence, price, town, or face waits for a written yes from Field Marketing Manager."),
      bullet("You write a list of every person you met: name, phone, service wanted, and town", "Because you met them, you also write stall, plus the town, plus the date. Send this list to Customer Experience before you leave the town. A name with no phone is not an enquiry. Customer Experience will call. You do not."),
      bullet("You write a stall log before you leave: where you stood, the times, the boards you used, how many names you collected, and any incident", "Write that you took no cash and took no booking. If it is not written, the company cannot prove you stood there."),
      bullet("You send that list to Customer Experience the same day, before you leave the town", "Confirm Customer Experience received it. Field Marketing Manager gets the count of names, not the phone numbers."),
      bullet("You send Field Marketing Manager a weekly visit note", "Write which planned days happened, how many people enquired in each town, which days you missed, which town produced no names after three visits, which boards need reprinting, and one or two written requests."),
      bullet("If you cannot go that day, you write it the same day", "If you write nothing, the company will think the stall happened.")
    ],
    fpo: [
      bullet("You go in person to the first local workers in a named town", "These are the first plumbers, electricians, or other service people in that town who will do jobs for Panun Kaergar. You meet them at their shop or workplace. You do not add people from a WhatsApp chat, a forwarded list, or a group you never visited. If you did not sit with that person, they are not signed onto us."),
      bullet("You take only the signed first-worker papers with you", "Those papers already say who Panun Kaergar is, what work this person may do, what they must not do, and what we pay them. Offer, Finance, and Provider Experience have already signed that pack. You do not invent a new payment or a new service on the spot. If a price is missing, you stop and tell Field Marketing Manager."),
      bullet("You get a signed paper from that person before you leave", "The paper must have their name, phone, which services they can do, which towns they can cover, the start date, and both signatures. A verbal yes or a WhatsApp “yes” is not enough. If a line is missing, they are not yet with us."),
      bullet("You send that signed paper to Provider Experience the same week", "Provider Experience is the team that puts them on jobs for customers. You do not give them a customer job yourself. You do not keep their name as your own private list. You write the date Provider Experience received the file."),
      bullet("You send Field Marketing Manager a weekly note of visits, signed papers, and towns where nobody signed", "Write which planned visits happened, how many papers were signed, how many were sent to Provider Experience, which towns had nobody who would sign, and one or two written requests."),
      bullet("If a town on this month’s list has nobody who will sign, you write that down", "Write the town, the dates you visited, who you met, and why they said no. Send that to Field Marketing Manager. Do not keep visiting just to look busy. Do not pick a different town yourself.")
    ],
    flc: [
      bullet("You go to the named office or housing society and meet the person who is allowed to sign", "That person is the office manager, the society secretary, or the named owner. Talking to the watchman at the gate is not a contract. You are there to get a written maintenance contract, not a verbal yes."),
      bullet("You take only the signed contract papers with you", "Those papers already name which services are included, which are not, the signed price, and how we invoice. Offer and Finance have already signed them. If the office or society wants a different price, you stop and tell Field Marketing Manager. You do not write a new number yourself."),
      bullet("You get a signed maintenance contract before you call the building done", "The contract must name the building, the town, the start date, the services, the price, who signs for them, and who signs for us. A WhatsApp “yes” is not a contract."),
      bullet("After both sides sign, you file the contract and tell Operations they can start sending workers to that building", "Write the start date, which services are included, and which are not. Finance sends the invoice. You do not take cash in the office."),
      bullet("You send Field Marketing Manager a weekly note of visits, signed contracts, waiting buildings, and buildings that said no", "Write which planned visits happened, which contracts signed, which are still waiting, which failed, which buildings Operations can now serve, and one or two written requests."),
      bullet("If the named building will not sign, you write that down the same week", "Write the building, the dates you visited, who you met, and why they said no. Do not keep visiting just to look busy. Do not pick a different building yourself.")
    ],
    mim: [
      bullet("You count what people ask for and what we sell, by service and town", "Pull the numbers from the system. Chat is not a tracker. Do not mix competitor prices into our demand counts."),
      bullet("You keep a named competitor list, and you write what actually changed", "A rumour is not a competitor move. Every change has a source and a date. If they can steal bookings this week, tell Head of Growth the same day."),
      bullet("You keep one row per idea: a missing service, a town, or a kind of hotel desk or shop", "One idea, one row. Link the evidence. Do not mark go yet."),
      bullet("You close each idea as go, no, or more research with a date", "Never leave a row as “we might look at this later”. Head of Growth watches the 10-working-day limit: 10 working days for a High idea. You own the written pack."),
      bullet("You hand a go to the right next job — Offer & Service Development Manager, Market Expansion Manager, or Partnerships & Channels Manager", "A missing service goes to Offer & Service Development Manager. A town goes to Market Expansion Manager. A kind of hotel desk or shop goes to Partnerships & Channels Manager. You do not write the service, open the town, or sign the partner."),
      bullet("You send Head of Growth a weekly intelligence report built from the registers", "Demand table, competitor changes, idea counts, High ideas plus age. Not from chat. Name what you could not prove.")
    ],
    osd: [
      bullet("You keep one live list of what we sell", "Take off the live list any service name that has no written sheet. A name without a sheet is not a service. Customer Experience picks only from this list."),
      bullet("You write the service sheet: what is in, what is out, how the job should run", "Name, who it is for, what we do, what we do not do, job steps, quality bar. Market Intelligence Manager already marked the need as go, or Head of Growth asked."),
      bullet("You send the sheet to Finance for price, and to Operations for a written yes that they can do it", "You recommend. Finance signs the price. You do not set commission. You do not do the first jobs yourself."),
      bullet("You get the launch checklist complete before Marketing talks", "Head of Growth says yes. Then you give Marketing the only sentences they may use. A draft is not a product."),
      bullet("You watch live services and recommend keep, fix, or stop in writing", "A complaint pattern is a design problem, not only a worker problem. Head of Growth signs a stop. Then Marketing stops talking."),
      bullet("You send Head of Growth a weekly note of what is in design vs live", "Sheets waiting on Finance or Operations must be named. A list of only live services hides the blocked sheet.")
    ],
    mem: [
      bullet("You keep a written list of towns with evidence", "A mood is not a queue. Log demand from towns we do not cover. Do not pick a town because someone was nearby."),
      bullet("You write enter, wait, or no for one named town", "Demand, competition, whether we can finish the first jobs, rough cost. Ask Provider Experience. Head of Growth signs. Research with no decision is a delay, not your result."),
      bullet("You write the launch only after enter is signed and Provider Experience can finish the first jobs", "First services, what Marketing may spend, launch week, review date. Marketing and Partnerships may spend only after this. You do not hire the workers."),
      bullet("You watch the launch: enquiries, bookings, and unfinished jobs", "If jobs are failing, recommend pause the same day. Do not keep the launch date if nobody can staff the town."),
      bullet("You recommend grow, fix, pause, or leave — in writing, on the review date you wrote", "Pause and leave are written, not whispered. Head of Growth signs. Then Marketing stops spending in that town."),
      bullet("You send Head of Growth a weekly town-list note", "What is in research, what is live, what is past its review date. Name the stuck town.")
    ],
    pcm: [
      bullet("You find hotel desks, shops, and other local businesses that already meet our customers", "Write the real kind of place Head of Growth has said yes to — hotel desk, appliance shop, and so on. Do not add your uncle or a neighbour who says they know people. That is not a partner. Market Intelligence Manager may send a written note that hotel desks are worth trying. That note is not permission. Head of Growth must write yes before you sign anyone."),
      bullet("You check who they serve, reputation, and whether we can work with them", "Write fit, wait, or no. A loud partner with bad jobs hurts the brand. A verbal yes is not a partner."),
      bullet("You write the terms, then test one lead into Customer Experience before you call them live", "How a lead is sent, what we pay if we pay, approved materials only. Handshake-only deals become fights. Finance must see unplanned money — do not promise payment yourself."),
      bullet("You send every partner enquiry to Customer Experience the same day, with the partner name written", "You find the hotel or shop. Customer Experience talks to the person they sent. You do not book. You do not send the person to a plumber."),
      bullet("You write keep, fix, or close for every live partner each month", "A silent partner is a closed partner — write it down. Tell Customer Experience if you will close, so they are not waiting on a hotel that sends nobody."),
      bullet("You send Head of Growth a weekly partner note", "Live partners, leads, bookings, junk leads, silent partners. Name what failed.")
    ]
  };

  const mustNot = {
    hog: [
      bullet("Do not run the ads or post as the Digital person", "Then nobody owns Digital, and you have no one to hold. If nobody is in that job, you do that job and write that you are doing it today because nobody is in that job."),
      bullet("Do not stand at stalls as the Field person", "Same problem. The job must have a named person. If Field is empty, you do that job in writing."),
      bullet("Do not close customer chats or take bookings", "That is Customer Experience. Mixing it hides why people did not book."),
      bullet("Do not write the service sheet as if that were this job", "That is Offer. You say yes or no on their sheet."),
      bullet("Do not sign hotel desks or hire workers", "Partnerships signs the hotel desk. Provider Experience owns the worker list. First Provider Onboarding signs the first local workers after Expansion opens the town.")
    ],
    mkm: [
      bullet("Do not invent a new service, a new price, or a new town", "Offer writes what we sell. Finance signs the price. Expansion opens a town. Head of Growth says yes. You may ask them in writing. You may not put a new service or town on the marketing calendar yourself."),
      bullet("Do not turn the enquiry into a booking", "Your job is to bring the person to Customer Experience. Customer Experience books the job and finishes it. You do not take the booking on a chat, a comment, or a stall."),
      bullet("Do not change what Intelligence found, or advertise a guess", "Market Intelligence writes facts with a source and a date. You may not put a rumour on the calendar as if it were a real service or town."),
      bullet("Do not spend more than the signed plan allows", "If the month must change, Head of Growth signs the change first. You do not let ads or stalls keep spending while you wait for a meeting."),
      bullet("Do not run the ads or stand at the stall as if that were your main job", "Those jobs belong to Digital Marketing Manager and Field Marketing Manager. If nobody sits there, you do that work for now and write: you are doing it today because nobody is in that job. Otherwise nobody owns that result."),
      bullet("Do not let Operations skip asking where they found us, and do not let Digital or Field write their own month", "Then Head of Growth cannot tell which paid ads, search pages, or stalls work, and the weekly report is not true.")
    ],
    hom: [
      bullet("Do not invent a service, a price, or a new town", "Those come down on the signed plan from Marketing Manager. You do not go around them to Head of Growth."),
      bullet("Do not publish a new sentence that is not already in this month’s instruction book", "Write what you want to say and why. Ask Marketing Manager. Wait for a written yes. Then publish."),
      bullet("Do not publish a customer or provider video that did not come from Operations through the company folder", "Operations records those videos on the job. Content Maker edits them. You say yes. Then they may go live."),
      bullet("Do not book the customer, and do not keep talking to them in a comment or chat yourself", "Send the person to Customer Experience the same day. Customer Experience books the job and finishes it."),
      bullet("Do not run paid ads when you cannot prove a test enquiry reached Operations", "Spend with no path to Operations is wasted money. Pause that ad the same day."),
      bullet("Do not take Field’s stall money without Marketing Manager", "Ads money and stall money are two different lines on one plan.")
    ],
    cmc: [
      bullet("Do not publish, schedule, or go live with a file", "You make and edit. The Digital Marketing Manager says yes, then publishes."),
      bullet("Do not spend ads money or boost a post", "Spend sits with Digital Marketing Manager. This job has no ads budget."),
      bullet("Do not go on the job to film customers or providers", "Operations records those videos. You collect the files and edit them."),
      bullet("Do not add a sentence, price, town, or face that is not in this month’s instruction book", "That is a change to the book. Stop. The Digital Marketing Manager asks Marketing Manager."),
      bullet("Do not put a file in the company folder before a written yes", "The company folder holds only files the Digital Marketing Manager has said yes to. A draft on a laptop is not the company copy."),
      bullet("Do not keep master files only on a personal phone or private chat", "If it is not in the review folder or the company folder, it did not happen for the company."),
      bullet("Do not book the customer or keep talking to them in a comment or chat yourself", "Customer Experience talks to the customer and books the job.")
    ],
    fmm: [
      bullet("Do not take a booking or money at a stall, and do not let Field Visitor do that", "Field Visitor collects names. Customer Experience books the job and finishes it. Field Visitor says Customer Experience will call. Money at the stall has no owner in the books."),
      bullet("Do not invent a price, a service, or a town", "A wrong price on a board or a contract becomes a fight later. Stop and ask Marketing Manager."),
      bullet("Do not keep the first local workers as your own team, and do not skip Provider Experience", "First Provider Onboarding gets them to sign. Provider Experience puts them on customer jobs. You do not give them a job yourself."),
      bullet("Do not sign hotel desks or shops that send us their customers", "That is Partnerships. Offices and housing societies that buy ongoing maintenance are Office and Society Contracts."),
      bullet("Do not run digital ads", "That is Digital Marketing Manager. You run the ground jobs. You do not boost a post."),
      bullet("Do not let Field Visitor skip the same-day list to Customer Experience, and do not let them visit a town that is not on the plan", "A list that arrives tomorrow is a person nobody called. A random Saturday stall cannot be measured.")
    ],
    fve: [
      bullet("Do not take a booking or money at the stall", "You collect names. Customer Experience books the job and finishes it. You say Customer Experience will call. Money at the stall has no owner in the books."),
      bullet("Do not promise a price that is not in this month’s instruction book", "A wrong price on a board becomes a fight later. Stop and ask Field Marketing Manager."),
      bullet("Do not pick a new town yourself", "If the town has no workers who can do the jobs, you should not stand there. Field Marketing Manager changes the calendar. You do not."),
      bullet("Do not sign hotel-desk partners, first local workers, or office maintenance contracts", "Those are other jobs under Field Marketing Manager. Your job is to visit the market and collect names for Customer Experience."),
      bullet("Do not run digital ads", "That is Digital Marketing Manager. You stand at the stall. You do not boost a post."),
      bullet("Do not skip sending the list to Customer Experience the same day, and do not visit a town that is not on your written list", "A list that arrives tomorrow is a person nobody called. A random Saturday stall cannot be measured.")
    ],
    fpo: [
      bullet("Do not keep the first local workers as your own list, and do not skip Provider Experience", "You get them to sign the papers. Provider Experience puts them on customer jobs."),
      bullet("Do not give them a customer job yourself", "That is Provider Experience. A private first job is not how the company runs."),
      bullet("Do not invent a payment, a service, or a town", "The signed papers are the rule. If a price is missing, stop and tell Field Marketing Manager."),
      bullet("Do not hire them as company employees", "People & HR owns employees. You sign the first local workers onto Panun Kaergar so they can do jobs as providers, not as office staff."),
      bullet("Do not start work in a town Expansion has not opened", "Expansion already decided we can serve that town. You do not pick a new town yourself."),
      bullet("Do not stand at a stall, and do not write an office maintenance contract, as if that were this job", "Standing at stalls is Field Visitor. Office and society contracts is Office and Society Contracts.")
    ],
    flc: [
      bullet("Do not invent a price, a service, or a town", "The signed contract papers are the rule. If they want a different price, stop and tell Field Marketing Manager."),
      bullet("Do not take cash in the office or society", "Finance sends the invoice. Cash you collect on site has no owner in the company books."),
      bullet("Do not sign hotel desks or shops that send us their customers", "That is Partnerships. This job is the office or housing society that buys ongoing maintenance from us."),
      bullet("Do not book a one-off job for a person in that building yourself", "If someone in the building wants a single job that the maintenance contract does not cover, that person goes to Customer Experience. Customer Experience books one-off work and finishes it."),
      bullet("Do not do the maintenance jobs yourself", "That is Operations. You write the contract. Operations sends the workers."),
      bullet("Do not visit a building that is not on this month’s written list", "Field Marketing Manager named the buildings. You do not pick a new one because you were nearby.")
    ],
    mim: [
      bullet("Do not run ads or brief Marketing to advertise a guess", "You discover. Marketing generates. Unmet demand is a signal, not a new ad."),
      bullet("Do not write the service sheet", "Hand a go pack to Offer. You do not design the job."),
      bullet("Do not open a town", "Hand a go pack to Expansion. You do not write enter, wait, or no."),
      bullet("Do not hire workers", "Provider Experience owns the roster. First Provider Onboarding signs the first local workers."),
      bullet("Do not book the customer", "Customer Experience books the job and finishes it. You write facts.")
    ],
    osd: [
      bullet("Do not advertise the service", "Marketing talks only after Head of Growth says yes. A draft is not a product."),
      bullet("Do not do the first jobs yourself", "Operations delivers. You write the sheet."),
      bullet("Do not set the final price without Finance", "You recommend. Finance signs. You do not set commission."),
      bullet("Do not invent demand", "Intelligence marks a need as go. You do not guess a product into the list."),
      bullet("Do not let Marketing talk before Operations is ready", "Selling a job we cannot finish creates complaints, not Growth.")
    ],
    mem: [
      bullet("Do not research with no decision", "Enter, wait, or no — in writing. More research forever is a delay, not your result."),
      bullet("Do not let Marketing spend before Operations can finish the first jobs", "Ads in a town with nobody who can do the jobs kill the brand in public."),
      bullet("Do not hire workers", "Provider Experience owns the roster. First Provider Onboarding goes in person after the town is open."),
      bullet("Do not change what we sell", "That is Offer. You decide the town, not the service sheet.")
    ],
    pcm: [
      bullet("Do not book the partner’s customer yourself", "You find the hotel or shop. Customer Experience talks to the person they sent."),
      bullet("Do not send a lead straight to a plumber", "You skip the booking system and the brand. Send the person to Customer Experience."),
      bullet("Do not promise money Finance did not see", "Unplanned money is an ask, not a handshake."),
      bullet("Do not promise a service we do not sell", "Stop them. Write it. The live list is Offer’s list."),
      bullet("Do not write an office or society maintenance contract", "That is Office and Society Contracts under Field. A hotel desk that sends us guests is this job.")
    ]
  };

  Object.keys(packs).forEach(function (id) {
    if (!G.roles[id]) return;
    G.roles[id].reportPacks = packs[id];
    if (owns[id]) G.roles[id].owns = owns[id];
    if (mustNot[id]) G.roles[id].mustNot = mustNot[id];
  });
})();

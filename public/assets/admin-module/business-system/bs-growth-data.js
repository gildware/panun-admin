window.PK_GROWTH = (function () {
  "use strict";

  function role(spec) {
    return spec;
  }

  const roles = {
    hog: role({
      id: "hog",
      name: "Head of Growth",
      reportsTo: "CEO",
      hero: "role-hog.png",
      result: "Enough of the right enquiries, at a cost we can afford, for work we can actually finish.",
      what: "You own who our customer is, what we promise them, and the numbers that prove Growth is working. You hold five people to a result: Marketing, Intelligence, Offer, Expansion, and Partnerships.",
      why: "If Growth is a pile of ads and stalls, the company chases noise. This seat turns Panun Kaergar into a system that can grow without the CEO living in WhatsApp.",
      how: "You do not run the ads, stand at the stall, or close the chat. You set the plan, say yes or no, and look at cost per enquiry and cost per booking every week.",
      owns: [
        "Who we sell to, and why they buy",
        "The promise we are allowed to make",
        "The monthly Growth plan and budget",
        "Yes or no on a new service, a new area, and a new partner type",
        "The five Growth function owners and their weekly numbers"
      ],
      mustNot: [
        "Run ads or post as the Digital person",
        "Stand at stalls as the Field person",
        "Close customer chats or take bookings",
        "Write the service definition as the technician",
        "Sign partners or hire providers"
      ],
      responsibilities: [
        { title: "Customer and promise", what: "Keep one clear picture of who we serve and what we may say.", why: "Channels must not invent a different company." },
        { title: "Plan and money", what: "Approve the monthly plan: who, offer, town, channel, budget, target.", why: "Spend without a plan is technician work, not Growth." },
        { title: "Yes and no", what: "Sign off new services, area launches, and partner types. Stop what is not working.", why: "Only this seat may turn research into market action." },
        { title: "Numbers", what: "Read enquiries, qualified enquiries, bookings, and cost by channel every week.", why: "Reach and likes are not a result." },
        { title: "People", what: "Coach the five function owners. Do their job only if the box is empty — and write it down as acting owner.", why: "The org chart is the business. People fill boxes. Boxes do not follow people." }
      ],
      when: {
        daily: ["Look at yesterday’s tagged enquiries and spend vs plan.", "Unblock a yes/no that is blocking a function owner.", "Escalate to the CEO only if money, brand, or a launch is at risk."],
        weekly: ["Run the Growth review: plan vs actual, cost per enquiry, cost per booking, lost-lead reasons from Sales.", "Clear High opportunities that have sat more than 10 working days.", "Confirm Marketing is only promoting approved services in approved towns."],
        monthly: ["Set next month’s plan with Marketing, using Intelligence.", "Decide grow / stop / research for each live offer and area.", "Review each function owner against their one result."]
      },
      standards: [
        "Every enquiry Growth creates has a source: digital, field, partner, or other.",
        "Marketing may promote only an approved service in an approved area.",
        "No Growth spend without a written monthly plan.",
        "Lost-lead reasons from Sales are read in the weekly review — not ignored.",
        "A High opportunity is decided in 10 working days: go, no-go, or more research with a date."
      ],
      procedures: [
        { id: "HOG-01", title: "Approve the monthly Growth plan", when: "Last week of each month, before spend starts.", steps: ["Read last month’s numbers and Intelligence go/no-go.", "Sit with Marketing. Choose who, offer, towns, channels, budget, and targets.", "Check Offer and Expansion: only approved items go on the plan.", "Sign the plan. Marketing may not change it without a new sign-off.", "File the plan. Send a one-page copy to the CEO."] },
        { id: "HOG-02", title: "Approve a new or changed service", when: "Offer is ready and Finance has priced it.", steps: ["Read the service sheet: what is in, what is out, where we can deliver.", "Confirm Operations can actually do the first jobs.", "Say yes, no, or send back. Yes means Marketing may talk about it.", "Tell Marketing what they may claim. Tell them what they must not say."] },
        { id: "HOG-03", title: "Approve a new area", when: "Expansion has a written enter / wait / no pack, and Provider Operations says we can fulfil.", steps: ["Read demand, competition, and capacity.", "If we cannot finish jobs there, the answer is no — even if ads would be cheap.", "If yes, name the launch services, budget, and review date.", "Marketing and Partnerships may spend in that area only after this yes."] },
        { id: "HOG-04", title: "Weekly Growth review", when: "Same weekday every week.", steps: ["Enquiries, qualified enquiries, bookings, spend — by channel.", "Cost per enquiry and cost per booking vs plan.", "Lost-lead reasons from Sales. Hand patterns to Intelligence and Marketing.", "One action each for any channel that is off plan.", "Write the review. Do not keep it in chat."] }
      ],
      kpis: [
        { name: "Monthly Growth plan signed", target: "100% before month starts", why: "No plan means no system." },
        { name: "Tagged enquiries", target: "Every Growth enquiry has a source", why: "Untagged leads cannot be improved." },
        { name: "Cost per enquiry", target: "Inside the signed plan", why: "Cheap noise is still noise." },
        { name: "Cost per booking from Growth", target: "Inside the signed plan", why: "Enquiries that never book are not Growth." },
        { name: "High opportunities decided", target: "Within 10 working days", why: "Parking lots are not decisions." },
        { name: "Weekly review on time", target: "100%", why: "The numbers must be a habit." }
      ],
      rules: [
        "Growth creates the enquiry. Sales books it. Operations does the job. Do not mix these.",
        "You may act as a function owner if that box has no person — say so in writing.",
        "Do not change price, payout, or catalogue without Offer and Finance.",
        "Do not hire providers. That is Provider Operations.",
        "If a channel cannot name its source, stop spending on it."
      ],
      escalate: [
        { when: "Brand, legal, or a false claim", to: "CEO the same day", how: "Stop the content. Write what went out and where." },
        { when: "Spend will break the monthly budget", to: "CEO before more money goes out", how: "Show plan vs actual and the ask." },
        { when: "A function owner is missing the one result for two weeks", to: "CEO in the weekly pack", how: "Numbers, what you tried, what you need." },
        { when: "Operations cannot fulfil what we are selling", to: "Head of Operations first, then CEO if it is not fixed", how: "Pause marketing in that offer or town until work can be done." }
      ]
    }),
    mkm: role({
      id: "mkm",
      name: "Marketing Manager",
      reportsTo: "Head of Growth",
      hero: "role-mkm.png",
      result: "A signed monthly plan that turns approved offers into tagged enquiries — digital and field — inside budget.",
      what: "You own the marketing system: plan, brand, message, calendar, budget, and source tracking. Digital and Field report to you. They run channels. You run the plan.",
      why: "Without one owner, Digital and Field become two companies. Customers hear two promises. Money has no owner.",
      how: "You turn Head of Growth priorities into a calendar. You approve creatives. You track every enquiry’s source. You do not invent services or close bookings.",
      owns: [
        "Monthly marketing plan and calendar",
        "Brand look, words, and claims that are allowed",
        "Budget split across digital and field",
        "Source on every marketing enquiry",
        "Digital Marketing Manager and Field Marketing Manager"
      ],
      mustNot: [
        "Invent a service, a price, or a new town",
        "Convert the enquiry into a booking",
        "Change Intelligence findings",
        "Spend off the signed plan",
        "Let Digital or Field skip source tags"
      ],
      responsibilities: [
        { title: "Planning", what: "Write who, offer, town, channel, budget, and KPI for the month.", why: "A calendar without a target is decoration." },
        { title: "Brand and message", what: "Keep one look and one promise. Stop unapproved claims.", why: "Trust dies when ads promise what the job cannot do." },
        { title: "Channel mix", what: "Give Digital and Field clear work, budget, and weekly targets.", why: "Two channels without an owner fight each other." },
        { title: "Lead handoff", what: "Every marketing enquiry goes to Sales with source, service, and town.", why: "Sales cannot convert a nameless lead." },
        { title: "Reporting", what: "Weekly: spend, enquiries, cost per enquiry, bookings from marketing.", why: "Head of Growth needs numbers, not stories." }
      ],
      when: {
        daily: ["Check spend vs plan.", "Check that new marketing leads have a source.", "Unblock Digital or Field if a creative or town is stuck."],
        weekly: ["Review both channels against the calendar.", "Pause what is wasting money. Push what is working — without changing the promise.", "Send the weekly marketing report."],
        monthly: ["Build next month’s plan from Intelligence and last month’s numbers.", "Get Head of Growth to sign it.", "Reset the calendar and budgets."]
      },
      standards: [
        "Plan signed before the month starts.",
        "95% or more of marketing enquiries have a source.",
        "No live creative without brand and fact check.",
        "Budget variance within 10% unless Head of Growth signs a change.",
        "Digital and Field work from the same calendar."
      ],
      procedures: [
        { id: "MKT-01", title: "Make the monthly plan", when: "Last week of the month.", steps: ["Read Intelligence and last month’s report.", "Pick approved services and approved towns only.", "Split budget and targets across digital and field.", "List what we will not do this month.", "Get Head of Growth to sign.", "Brief Digital and Field the same day."] },
        { id: "MKT-02", title: "Approve a creative", when: "Before anything goes live.", steps: ["Check it matches the signed offer and town.", "Check brand: logo, colours, tone.", "Check facts: no invented price or promise.", "Add a clear next step for the customer.", "Store the final file in the library.", "Only then may Digital or Field publish."] },
        { id: "MKT-03", title: "Tag every enquiry", when: "Every working day.", steps: ["Every marketing lead must show channel and campaign.", "If Sales received an untagged lead, find the source the same day.", "If you cannot tag it, stop that campaign until tracking works.", "Use the tags in the weekly report."] },
        { id: "MKT-04", title: "Weekly marketing report", when: "Same weekday every week.", steps: ["Spend vs plan.", "Enquiries and qualified enquiries by channel.", "Bookings from marketing, if Sales has closed them.", "What we will change next week — one or two moves, not ten."] }
      ],
      kpis: [
        { name: "Plan signed on time", target: "100% monthly" },
        { name: "Source on marketing leads", target: "95% or more" },
        { name: "Spend vs plan", target: "Within 10%" },
        { name: "Cost per enquiry", target: "Inside signed target" },
        { name: "Weekly report", target: "100% on time" }
      ],
      rules: [
        "Only approved services in approved towns.",
        "Digital and Field do not set their own offers.",
        "If tracking is broken, pause the campaign.",
        "Partner leads belong to Partnerships, then Sales — not to you to convert."
      ],
      escalate: [
        { when: "A claim might be false or off brand", to: "Head of Growth before it stays live", how: "Take it down. Show the file." },
        { when: "Spend will go past the plan", to: "Head of Growth before more spend", how: "Plan vs actual and the reason." },
        { when: "Sales says marketing leads are junk two weeks running", to: "Head of Growth", how: "Samples, sources, and what you will change." }
      ]
    }),
    hom: role({
      id: "hom",
      name: "Digital Marketing Manager",
      reportsTo: "Marketing Manager",
      hero: "role-hom.png",
      result: "All online work of Panun Kaergar follows the signed plan, and every digital lead that lands in Sales has a source.",
      what: "You own everything online: paid ads (Meta, Google, and any other paid ads), website search, app store, social, Reddit, Quora, and other sites. You also own the content library — videos and posts — and you hold the Content Maker to that result. You do not invent the offer, the town, or the price. You do not book the customer.",
      why: "If ads, posts, and search each invent their own company, the customer hears three promises and Sales cannot learn. One seat must own all digital, and send the truth back: what worked and what failed.",
      how: "Marketing Manager gives you the month: towns, services, ads money, words you may use, words you must not use, and where a lead must land. You run three lanes — paid ads, organic, and content. You publish from the signed kit. A new claim, new price, new town, or new face goes back to Marketing Manager first.",
      lanes: [
        { kicker: "Lane 1", title: "Paid ads", work: "Meta, Google, and any other paid ads on the plan.", result: "Tagged enquiries inside the ads budget.", who: "You. There is no extra ads manager." },
        { kicker: "Lane 2", title: "Organic", work: "Website search, app store, social that is not paid, Reddit, Quora, and other sites.", result: "Tagged enquiries and a real presence — counted on their own line, not mixed into ads spend.", who: "You." },
        { kicker: "Lane 3", title: "Content", work: "AI video, AI posts, brand films, customer films, real job films, personal-brand films. File every file. Hand them to ads and social.", result: "A dated library. If it is not filed, it did not happen.", who: "Content Maker. If that box is empty, you are the acting owner — write it down." }
      ],
      given: [
        { title: "Towns that are open this month", why: "Do not advertise a town we cannot serve." },
        { title: "Services we may talk about", why: "A draft is not a product." },
        { title: "Ads budget and a cost ceiling", why: "The plan is the boss, not the ad account." },
        { title: "Words we may say, and words we must not say", why: "This is the signed kit. You may publish from it without asking again." },
        { title: "Brand look — logo, colours, tone", why: "One look. Not a new look every post." },
        { title: "Where the lead must land (form, WhatsApp, or app that Sales already uses)", why: "A lead that never reaches Sales is wasted spend." },
        { title: "What we will not do this month", why: "No ‘let’s just test it’ off the plan." }
      ],
      sentBack: [
        { title: "Tagged lead the same day", why: "Sales books. You do not." },
        { title: "Daily note: spend, new leads, anything you paused", why: "Catch a leak the same day." },
        { title: "Weekly pack by pipe: Meta, Google, search, social, other", why: "Show spend, enquiries, cost — and 3 things that worked plus 3 that failed." },
        { title: "Content log: what went live, where, which service and town", why: "The library is how we learn, not a phone album." },
        { title: "One ask for next week", why: "More money, a new video, or pause a town — not ten wishes." },
        { title: "‘People keep asking for X’", why: "Send to Intelligence as a signal. Do not invent a new ad." }
      ],
      graph: {
        hub: "Content library",
        hubWhy: "One folder. Dated files. Service, town, and type on every file. Ads, social, search, and other sites all pull from here.",
        fromTitle: "Where files come from",
        from: [
          { from: "Operations", what: "Customer-feedback and provider job videos", rule: "Operations captures them on the job. Content Maker collects and edits. You approve. Then the file enters the library." },
          { from: "CEO / founder", what: "Personal brand films", rule: "They are the face. Content Maker edits. You publish." },
          { from: "Marketing Manager", what: "Brand kit and allowed words", rule: "Signed once. You may publish from it without a new signature each time." },
          { from: "Digital / Content Maker", what: "AI video and AI posts", rule: "Only from the kit. A new claim, price, town, or face needs Marketing Manager first." }
        ],
        toTitle: "Where files go",
        to: [
          { to: "Paid ads", what: "Meta, Google, other paid", rule: "Same tagging as every digital lead." },
          { to: "Social", what: "Upload and schedule", rule: "From the library, not from a personal phone." },
          { to: "Website / app store", what: "Search pages and store listing", rule: "Same promise as the ads." },
          { to: "Reddit, Quora, other", what: "Answers and posts", rule: "Same promise. Do not book in the thread. Hand the person to Sales." },
          { to: "Sales", what: "Every tagged digital lead", rule: "Same day. Source + campaign or post + service + town." }
        ],
        note: "New claim, new price, new town, or new face: stop and ask Marketing Manager. Everything else in the kit: publish, file, report."
      },
      owns: [
        "All paid ads — Meta, Google, and any other paid ads",
        "Website search and app store listing",
        "Social, Reddit, Quora, and other online pipes",
        "The content library and the Content Maker box",
        "Digital lead tags — every lead has a source"
      ],
      mustNot: [
        "Invent a service, a price, or a new town",
        "Publish a new claim that is not in the kit",
        "Publish a customer or provider video that did not come from Operations through the library",
        "Book the customer or sit on a chat",
        "Run ads with no tracking",
        "Skip Marketing Manager and go to Head of Growth for a new town or service"
      ],
      responsibilities: [
        { title: "Paid ads", what: "Build, watch, and pause Meta, Google, and other paid ads that are on the plan.", why: "A live ad with no owner is a leak.", how: "Only what is on the signed calendar. Test a dummy lead into Sales before you spend. If tracking dies, pause the same day." },
        { title: "Organic — search, app, other sites", what: "Keep the website and app store true to the kit. Post and answer on social, Reddit, and Quora using the same words.", why: "Search and other sites are not ‘free ads’. They still need a name, a tag, and a lead path to Sales.", how: "Same promise as the ads. Count them on their own line in the weekly pack. Do not book in a comment or a thread." },
        { title: "Content library", what: "Approve Content Maker files against the kit, then file them. Hand the right file to ads, social, and search.", why: "If it lives on a phone, it is not a system.", how: "Content Maker makes and edits. You approve each file. Only then does it enter the library. Every library file: name, type, service, town, date, where it went live, result." },
        { title: "Operations footage", what: "Ask Operations for customer-feedback and provider videos the file list needs. Hand the files to Content Maker to edit.", why: "Those videos are captured on the job. Content Maker cuts them.", how: "Write the need. Collect the files from Operations. Content Maker edits from the kit and files. Then ads and social may use them." },
        { title: "Leads to Sales", what: "Every digital lead goes to Sales the same day, tagged.", why: "You generate. Sales books.", how: "Source = digital + pipe (Meta / Google / search / social / Reddit or other) + campaign or post name. Service and town if you have them. Do not start the sales chat yourself." },
        { title: "Honest results", what: "Send back what worked and what failed.", why: "A report with only wins is how bad spend hides.", how: "Weekly: 3 things that worked, 3 that failed, 1 ask. By pipe, not as one lump called ‘digital’." }
      ],
      when: {
        daily: ["Check spend vs the ads line.", "Send new tagged leads to Sales.", "Fix broken tracking the same day.", "Approve Content Maker files waiting, so they can enter the library.", "File anything that went live today.", "Write the daily note to Marketing Manager if spend jumped or you paused something."],
        weekly: ["Write the weekly pack by pipe: spend, enquiries, cost, 3 wins, 3 fails, 1 ask.", "Check the library holds only files you approved.", "Coach the Content Maker against their one result — or write that you are acting owner.", "Send ‘people keep asking for X’ to Intelligence, not as a new ad."],
        monthly: ["Help Marketing Manager write next month’s digital slice.", "Archive dead ads and dead posts.", "List films you still need (jobs, customers, founder) so Operations can send footage and the founder can plan time."]
      },
      standards: [
        "No paid ad live without a test lead into Sales.",
        "Spend stays inside the ads line of the plan.",
        "95% or more of digital leads have a source.",
        "Every live file is in the library the same day.",
        "Customer and provider films in the library are edited from Operations footage.",
        "New claim, price, town, or face waits for Marketing Manager.",
        "Sales chats in comments go to Sales — you do not close them."
      ],
      procedures: [
        { id: "DIG-01", title: "Start a paid ad", when: "When it is on the signed calendar.", steps: ["Check town, service, words, and budget against the kit.", "Pick an approved file from the library — or ask Content Maker to make it from the kit, then approve it before it is filed.", "Set tracking. Send one dummy lead into Sales.", "Tell Sales the ad is live and how leads will arrive.", "Launch only after that check.", "File the launch record."] },
        { id: "DIG-02", title: "Watch paid ads each day", when: "Every working day.", steps: ["Spend, enquiries, cost.", "If spend is running away, pause and tell Marketing Manager.", "If spend is on and enquiries are zero, pause and check tracking and the file.", "Write what you did."] },
        { id: "DIG-03", title: "Publish from the kit", when: "Any post, AI video, search page, Reddit or Quora answer that uses only the signed words.", steps: ["Use an approved file from the library, or ask Content Maker to make one from the kit and approve it first.", "No new claim, price, town, or face.", "Publish.", "File: where it went live, service, town, date.", "If someone starts a sales chat, hand it to Sales."] },
        { id: "DIG-04", title: "Ask before a new claim", when: "When you need words, a price, a town, or a face that is not in the kit.", steps: ["Stop. Do not publish.", "Write what you want to say and why.", "Send to Marketing Manager.", "Wait for a yes. If it is a new service or town, they take it to Head of Growth.", "Only then make the file and file it in the kit."] },
        { id: "DIG-05", title: "Take in Operations footage", when: "When ads or social need a customer-feedback or provider video.", steps: ["Write the file-list need: type, service, town.", "Ask Operations for the raw file.", "Hand the files to Content Maker.", "Content Maker edits from the kit and sends the cut to you.", "Approve it. Then it enters the library. Then ads and social may use it."] },
        { id: "DIG-06", title: "Hand a digital lead to Sales", when: "As soon as it arrives.", steps: ["Tag: digital + pipe + campaign or post.", "Add service and town if you have them.", "Do not sell.", "If they are already chatting you, pass the thread to Sales."] },
        { id: "DIG-07", title: "Weekly digital pack", when: "The day before the marketing weekly report.", steps: ["Split the numbers by pipe: Meta, Google, search, social, other.", "Spend, enquiries, cost, tag rate.", "3 things that worked. 3 that failed. 1 ask.", "Content log for the week.", "Send to Marketing Manager. Not a voice note."] }
      ],
      kpis: [
        { name: "Test lead before a paid ad goes live", target: "100%", why: "Spend with no path to Sales is waste." },
        { name: "Digital leads tagged", target: "95% or more", why: "Sales cannot work a nameless lead." },
        { name: "Ads spend vs plan", target: "Inside the ads line", why: "The plan is the boss." },
        { name: "Cost per digital enquiry", target: "Inside the signed ceiling", why: "If cost blows, pause." },
        { name: "Live files in the library same day", target: "100%", why: "A phone album is not a system." },
        { name: "Weekly pack with wins and fails", target: "100% on time", why: "Wins-only reports hide bad spend." },
        { name: "Customer and provider films sourced from Operations", target: "100%", why: "Those videos are captured on the job. Content Maker edits them." }
      ],
      rules: [
        "The signed kit is the boss. You may publish from it. You may not add a new claim.",
        "No private boosts from a personal page.",
        "No prices Finance has not signed.",
        "Do not mix search results into ads spend.",
        "Do not skip Sales.",
        "If the Content Maker box is empty, say so in writing and act in it."
      ],
      escalate: [
        { when: "Ad account hacked, ad rejected, or tracking dead", to: "Marketing Manager the same day", how: "Pause. Screenshot. Write the time." },
        { when: "A customer is angry in comments about a job", to: "Sales / CX, copy Marketing Manager", how: "Do not fight in public. Hand it over." },
        { when: "You need a new town, service, or price", to: "Marketing Manager — not Head of Growth", how: "Write the ask. They take it up if needed." },
        { when: "Operations has not sent footage the file list needs", to: "Marketing Manager", how: "Name the job or customer. Keep Content Maker on AI and brand work until the files arrive." }
      ]
    }),
    cmc: role({
      id: "cmc",
      name: "Content Maker",
      reportsTo: "Digital Marketing Manager",
      hero: "role-cmc.png",
      layout: "detailed",
      result: "Finished, kit-faithful videos and posts, approved by the Digital Marketing Manager, then filed in the company content library the same working day.",
      what: "The Content Maker produces Panun Kaergar’s marketing videos and posts for the Digital Marketing Manager. It makes AI, brand, and founder files from the signed kit, and collects customer and provider footage from Operations to edit. Finished files go for approval the same working day.",
      why: "Digital channels cannot publish without finished, on-brand files. This role exists so production is a named job, and the Digital Marketing Manager can approve and go live from a company library instead of making every file.",
      how: "The Marketing Manager writes and signs the kit. The Digital Marketing Manager gives this seat that kit and a file list of the videos and posts needed. For AI, brand, and founder films, the Content Maker produces from those two sources. For customer feedback and provider videos, the Content Maker collects the raw files from Operations and edits them to the kit. Named files go to the Digital Marketing Manager for approval. Only a yes puts a file in the library.",
      owns: [
        "Making AI, brand, and founder videos and posts from the kit",
        "Collecting customer-feedback and provider videos from Operations, then editing them",
        "Sending every finished file to the Digital Marketing Manager for approval the same day",
        "A simple list of what is waiting, what the Digital Marketing Manager approved, and what is in the library"
      ],
      mustNot: [],
      responsibilities: [
        { title: "Make the file", what: "Turn each line on the Digital Marketing Manager's file list into a video or post that matches the kit.", why: "The Digital Marketing Manager approves from finished files, then publishes.", how: "Use the signed words and look. If you need a new claim, send the line back through the Digital Marketing Manager." },
        { title: "Send it for approval", what: "Same day: name, type, service, town, date. Send the named file to the Digital Marketing Manager.", why: "A file enters the library only after the Digital Marketing Manager says yes.", how: "Put the named file in the review path the Digital Marketing Manager named. Tell the Digital Marketing Manager it is ready to approve." },
        { title: "Edit Operations footage", what: "Collect customer-feedback and provider videos from Operations. Edit them to the kit.", why: "Those videos are captured on the job. Operations sends the files. This seat cuts them.", how: "Take the files from Operations. Edit to the kit look and words. Name them and send them to the Digital Marketing Manager for approval the same working day." }
      ],
      when: {
        daily: ["Make or edit what is on today’s file list.", "Collect any new Operations footage. Edit it. Send finished files to the Digital Marketing Manager for approval.", "Tell the Digital Marketing Manager what is waiting for approval, what they approved into the library, and what is waiting on Operations."],
        weekly: ["Show the Digital Marketing Manager the week’s files vs the file list: sent, approved, blocked, missed.", "List customer and provider films still waiting on footage from Operations."],
        monthly: ["Help the Digital Marketing Manager list next month’s films so Operations can send footage in time."]
      },
      standards: [
        "A file enters the library only after the Digital Marketing Manager approves it.",
        "Every file on the signed kit.",
        "Customer and provider files are edited from Operations footage."
      ],
      procedures: [
        { id: "CMC-01", title: "Make a file from the list and the kit", when: "When the Digital Marketing Manager gives a file list.", steps: ["Check the file list and the signed kit.", "Make or edit that line from the list, using only the kit.", "Name it and send it for approval.", "If yes, put it in the library.", "If no, change it from the note and send it again."] },
        { id: "CMC-02", title: "Stop for a new claim", when: "If the file list needs words or a face that is not in the kit.", steps: ["Stop that line.", "Tell the Digital Marketing Manager.", "Wait. The Digital Marketing Manager asks the Marketing Manager."] },
        { id: "CMC-03", title: "Edit Operations footage", when: "When Operations sends a customer-feedback or provider video.", steps: ["Collect the raw file.", "Edit it to the kit look and words.", "Name it: type, service, town, date.", "Send it to the Digital Marketing Manager for approval.", "File it in the library only after the Digital Marketing Manager says yes."] }
      ],
      kpis: [
        { name: "Files approved before they enter the library", target: "100%", why: "The library must hold only files the Digital Marketing Manager has said yes to." },
        { name: "First-pass approval rate", target: "90% or more", why: "A no from the Digital Marketing Manager that is not a kit gap means the file was unfinished or off-kit." },
        { name: "Eventual approval rate of decided files", target: "100%, except kit-gap stops", why: "A no from the Digital Marketing Manager is a remake. The week must not end with a decided no left unfixed." },
        { name: "Files on kit", target: "100%", why: "Off-kit files invent a second company." },
        { name: "File list vs delivered", target: "Track weekly", why: "The Digital Marketing Manager must know what is missing." }
      ],
      rules: [
        "You make and edit. The Digital Marketing Manager approves, then publishes and spends.",
        "Sales owns the customer.",
        "Customer feedback and provider videos come from Operations. This seat collects, edits, and sends them to the Digital Marketing Manager for approval."
      ],
      escalate: [
        { when: "Operations footage for a file-list line has not arrived", to: "Digital Marketing Manager", how: "Name the line. They ask Operations." },
        { when: "The kit does not cover the file list", to: "Digital Marketing Manager", how: "Stop. They ask Marketing Manager." },
        { when: "The Digital Marketing Manager sends a file back, or has not approved it the same day", to: "Digital Marketing Manager", how: "Keep the named file. Remake from the Digital Marketing Manager's note, or age the wait on the daily note." }
      ]
    }),
    fmm: role({
      id: "fmm",
      name: "Field Marketing Manager",
      reportsTo: "Marketing Manager",
      hero: "role-fmm.png",
      result: "Tagged local enquiries from the named towns on the calendar.",
      what: "You run on-ground demand: stalls, local presence, flyers, and face-to-face work in towns we already said yes to.",
      why: "Kashmir still buys from people they can see. Field work must be a system, not a random Saturday.",
      how: "You work the calendar. You collect enquiries and tag them field. You do not take the booking at the stall.",
      owns: [
        "Field calendar for approved towns",
        "Stalls, local materials, and local activity",
        "Field enquiry volume and source tags",
        "A simple log of where we stood and what we collected"
      ],
      mustNot: [
        "Take bookings or money at the stall",
        "Promise a price that is not approved",
        "Pick a new town that Expansion has not opened",
        "Sign partners (that is Partnerships)",
        "Run digital ads"
      ],
      responsibilities: [
        { title: "Show up where the plan says", what: "Be in the named town on the named day, with approved materials.", why: "Random stalls cannot be measured." },
        { title: "Collect, do not close", what: "Take name, phone, service, town. Hand to Sales the same day.", why: "Closing at the stall skips the booking system." },
        { title: "Materials", what: "Use only approved leaflets and boards.", why: "A wrong price on a flyer becomes a fight later." },
        { title: "Report", what: "How many people, how many enquiries, which town.", why: "If it is not written, it did not happen." }
      ],
      when: {
        daily: ["If you are in field: collect, tag, hand to Sales before end of day.", "If you are in office: restock materials and confirm the next stall."],
        weekly: ["Review which towns produced enquiries.", "Plan next week with Marketing Manager.", "Flag a town that is dead three visits in a row."],
        monthly: ["Help set next month’s field towns and dates.", "Return unused budget truthfully."]
      },
      standards: [
        "Every field enquiry tagged the same day.",
        "No stall in a town that is not on the plan.",
        "No cash, no booking, no job promise on the spot.",
        "Materials match the current approved offer."
      ],
      procedures: [
        { id: "FLD-01", title: "Run a stall day", when: "On a calendar field day.", steps: ["Confirm town, pitch, and materials the day before.", "Set up with approved boards only.", "Write each enquiry: name, phone, service, town, source = field.", "Do not book. Say Sales will call.", "Send the list to Sales the same day.", "Note anything people kept asking — send that to Intelligence, not as a new offer."] },
        { id: "FLD-02", title: "Handoff to Sales", when: "Same day as the stall.", steps: ["One list, complete.", "Mark source field + town + date.", "If a number is missing, do not pretend you have an enquiry.", "Confirm Sales received it."] }
      ],
      kpis: [
        { name: "Field days vs calendar", target: "95% or more completed" },
        { name: "Field leads tagged same day", target: "95% or more" },
        { name: "Enquiries per planned field day", target: "Track and improve vs last month" }
      ],
      rules: [
        "The stall is a door, not a shop.",
        "You represent the brand. No private side deals.",
        "If Operations cannot serve that town yet, you should not be there."
      ],
      escalate: [
        { when: "A fight, a false claim, or someone taking bookings in our name", to: "Marketing Manager same day", how: "Stop. Write what happened." },
        { when: "The town is not ready (no providers)", to: "Marketing Manager, who tells Head of Growth", how: "Do not keep standing there to look busy." }
      ]
    }),
    mim: role({
      id: "mim",
      name: "Market Intelligence Manager",
      reportsTo: "Head of Growth",
      hero: "role-mim.png",
      result: "A go / no-go pack with evidence — not opinion.",
      what: "You keep one true picture of demand, competitors, and chances to grow. You decide whether an idea is real enough to hand on. You do not run ads, design services, or enter towns.",
      why: "Without this seat, Growth guesses. Guessing is expensive in Kashmir’s small towns.",
      how: "You write facts with a source and a date. You close opportunities as go, no-go, or more research. You never leave them as ‘interesting’.",
      owns: [
        "Demand trackers (what people ask, what we sell, where)",
        "Competitor file and change log",
        "Opportunity register and validation",
        "Weekly and monthly Intelligence reports"
      ],
      mustNot: [
        "Run campaigns",
        "Write the service as Offer",
        "Launch a town",
        "Recruit providers",
        "Convert leads"
      ],
      responsibilities: [
        { title: "Demand truth", what: "Count enquiries and bookings by service and area from the system, not from memory.", why: "Chat is not a tracker." },
        { title: "Competitors", what: "Watch named competitors. Log what actually changed.", why: "A rumour is not a competitor move." },
        { title: "Opportunities", what: "One row per idea. Then validate or kill it.", why: "A list of dreams is not Intelligence." },
        { title: "Handoff", what: "Validated service → Offer. Validated area → Expansion. Validated channel type → Partnerships.", why: "You discover. You do not do their job." }
      ],
      when: {
        daily: ["File new facts with source and date.", "If a competitor can steal bookings this week, tell Head of Growth the same day."],
        weekly: ["Close the week’s demand trackers.", "Submit the weekly report from the registers.", "Hand newly validated items to the right seat."],
        monthly: ["Four-week demand vs the four weeks before.", "Full competitor pass.", "Opportunity funnel with no silent rows.", "Submit the monthly decision pack."]
      },
      standards: [
        "No fact without a source and a date.",
        "High opportunities decided in 10 working days (Head of Growth owns the clock; you own the pack).",
        "Never close as ‘interesting’.",
        "Weekly report on time, built from registers."
      ],
      procedures: [
        { id: "MI-01", title: "File a fact", when: "Whenever a new extract, listing, or field note arrives.", steps: ["Accept only a real source.", "Write source, date, owner.", "Put it in the right tracker.", "Do not mix competitor prices into demand counts."] },
        { id: "MI-02", title: "Open an opportunity", when: "A gap repeats, a competitor moves, or a trend can change what we sell.", steps: ["One idea, one row.", "Link the evidence.", "Set type: service, area, channel, or stop.", "Assign for validation. Do not mark go yet."] },
        { id: "MI-03", title: "Validate", when: "A row is in validate.", steps: ["Test demand, competition, and whether we could operate.", "Close as go, no-go, or more research with a date.", "If go, name the receiving seat: Offer, Expansion, or Partnerships.", "Tell Head of Growth in the weekly pack."] }
      ],
      kpis: [
        { name: "Weekly report on time", target: "100%" },
        { name: "Facts with source and date", target: "100% of new rows" },
        { name: "High opportunities with a decision", target: "None sitting over 10 working days without a date" }
      ],
      rules: [
        "You do not brief Marketing to advertise a guess.",
        "You do not enter a town.",
        "Unmet demand is a signal to Offer — not a new ad."
      ],
      escalate: [
        { when: "A competitor move can steal bookings this week", to: "Head of Growth same working day", how: "What changed, source, likely hit." },
        { when: "You cannot get a CRM extract", to: "Head of Growth, then Technology if the tool is broken", how: "Which file, which day." }
      ]
    }),
    osd: role({
      id: "osd",
      name: "Offer & Service Development Manager",
      reportsTo: "Head of Growth",
      hero: "role-osd.png",
      result: "An approved service sheet Panun Kaergar can sell and deliver.",
      what: "You own what we sell: names, what is in, what is out, how the job should run, and when to retire a dead service.",
      why: "Marketing cannot invent the product. Operations cannot guess the product. This seat writes the product.",
      how: "You turn a validated need into a service sheet, get Finance to price it, get Operations to say they can do it, then Head of Growth says marketing may talk.",
      owns: [
        "Service catalogue and service sheets",
        "Inclusions and exclusions",
        "Launch checklist with Operations",
        "A written recommend to keep, fix, or stop a service"
      ],
      mustNot: [
        "Advertise the service",
        "Do the first jobs yourself",
        "Set final price without Finance",
        "Invent demand",
        "Launch without Operations ready"
      ],
      responsibilities: [
        { title: "Catalogue", what: "Keep one live list of what we sell. Kill ghost names.", why: "Sales must not pick from a messy menu." },
        { title: "Design", what: "Write scope, in, out, and the job steps.", why: "A name without a sheet is not a service." },
        { title: "Ready to sell", what: "Providers, tools, and area must be possible before launch.", why: "Selling a job we cannot finish creates complaints." },
        { title: "After launch", what: "Watch demand, complaints, and cancellations. Recommend keep, fix, or stop.", why: "A dead service that still sits in ads wastes money." }
      ],
      when: {
        daily: ["Catch unmet requests and complaints that look like a missing service.", "Update sheets that Sales is already using."],
        weekly: ["Move opportunities. Unblock a sheet waiting on Finance or Operations.", "Report what is in design vs live."],
        monthly: ["Full catalogue pass.", "Recommend retire or fix.", "Plan next month’s design work with Head of Growth."]
      },
      standards: [
        "Every live service has a complete sheet.",
        "Validation of a new service in 15 working days unless Head of Growth dates a delay.",
        "Launch checklist 100% before Marketing talks.",
        "No live service without inclusions and exclusions."
      ],
      procedures: [
        { id: "OSD-01", title: "Write a service sheet", when: "A need is validated by Intelligence, or Head of Growth asks.", steps: ["Name, who it is for, what we do, what we do not do.", "What the customer must prepare.", "What the provider must be able to do.", "Job steps and quality bar.", "Send to Finance for price options.", "Send to Operations for ‘can we deliver?’"] },
        { id: "OSD-02", title: "Launch a service", when: "Price and operations are signed.", steps: ["Head of Growth says yes.", "Update the catalogue.", "Give Marketing the only claims they may use.", "Watch the first jobs with Operations.", "Review after the first period."] },
        { id: "OSD-03", title: "Retire a service", when: "Demand is dead or we cannot deliver well.", steps: ["Write why.", "Head of Growth signs stop.", "Marketing stops promoting.", "Catalogue marked retired.", "Keep the file. Do not delete history."] }
      ],
      kpis: [
        { name: "Live services with a full sheet", target: "100%" },
        { name: "Launch checklist complete before talk", target: "100%" },
        { name: "New service validation", target: "Within 15 working days" }
      ],
      rules: [
        "Marketing may not sell a draft.",
        "You do not set commission. You recommend. Finance signs.",
        "A complaint pattern is a design problem, not only a provider problem."
      ],
      escalate: [
        { when: "Operations cannot deliver a live service", to: "Head of Growth — pause marketing", how: "Which service, which town, what is missing." },
        { when: "Finance will not price", to: "Head of Growth", how: "The sheet and what is blocked." }
      ]
    }),
    mem: role({
      id: "mem",
      name: "Market Expansion Manager",
      reportsTo: "Head of Growth",
      hero: "role-mem.png",
      result: "A written enter, wait, or leave for one named area.",
      what: "You decide where we operate next. You do not research for fun, and you do not market before we can finish jobs.",
      why: "A new town with ads and no providers is how brands die in public.",
      how: "Intelligence finds a place. You test if we should enter. Provider Operations must be ready. Head of Growth says yes. Then Marketing may spend there.",
      owns: [
        "Area pipeline and feasibility packs",
        "Launch plan for a named area",
        "Post-launch enter / improve / pause / exit recommendation"
      ],
      mustNot: [
        "Research without a decision (that is a delay, not your job done)",
        "Market before Operations is ready",
        "Hire providers (Provider Operations does that)",
        "Change the service catalogue"
      ],
      responsibilities: [
        { title: "Pipeline", what: "Keep a list of possible towns with evidence.", why: "Expansion is a queue, not a mood." },
        { title: "Feasibility", what: "Demand, competition, capacity, rough cost to win a customer.", why: "Hope is not a plan." },
        { title: "Launch", what: "Write what we will sell there first, what providers we need, what marketing may do, when we review.", why: "A launch without a review date never ends." },
        { title: "After", what: "Recommend scale, fix, pause, or leave — with numbers.", why: "Staying forever in a dead town is not loyalty. It is waste." }
      ],
      when: {
        daily: ["Log demand from towns we do not cover.", "Track live launch tasks."],
        weekly: ["Move the pipeline. Unblock Provider Operations on capacity.", "Report to Head of Growth."],
        monthly: ["Compare launched areas to their launch KPIs.", "Pick the next candidate or recommend none."]
      },
      standards: [
        "No marketing spend in a town without Head of Growth yes and provider readiness.",
        "Feasibility in 15 working days once a town is in research.",
        "Every launch has a review date.",
        "Exit and pause are written, not whispered."
      ],
      procedures: [
        { id: "ME-01", title: "Test a town", when: "Intelligence hands a named area.", steps: ["Demand and competition.", "Can we fulfil the first services? Ask Provider Operations.", "Rough cost to get a customer.", "Write enter, wait, or no.", "Head of Growth signs."] },
        { id: "ME-02", title: "Launch a town", when: "Enter is signed and capacity is real.", steps: ["First services list.", "Marketing may-spend list.", "Launch week.", "Watch enquiries, bookings, and unfinished jobs.", "Review on the date you wrote."] },
        { id: "ME-03", title: "Pause or leave", when: "Numbers miss the launch bar and will not recover soon.", steps: ["Write why.", "Stop new marketing there.", "Head of Growth signs.", "Tell Marketing, Sales, and Operations."] }
      ],
      kpis: [
        { name: "Feasibility on time", target: "Within 15 working days" },
        { name: "Launch checklist", target: "100% before marketing spend" },
        { name: "Post-launch review", target: "100% on the dated review" }
      ],
      rules: [
        "A pin on a map is not a launch.",
        "If we cannot finish jobs, we do not advertise.",
        "You do not own the provider roster."
      ],
      escalate: [
        { when: "A launch is live and jobs are failing", to: "Head of Growth same day — recommend pause", how: "Bookings vs finished jobs." },
        { when: "Provider Operations cannot staff the town", to: "Head of Growth", how: "Do not keep the launch date anyway." }
      ]
    }),
    pcm: role({
      id: "pcm",
      name: "Partnerships & Channels Manager",
      reportsTo: "Head of Growth",
      hero: "role-pcm.png",
      result: "An active partner or channel that sends tagged leads to Sales — not to a provider, and not to you to close.",
      what: "You build extra pipes: shops, hotels, local businesses, and other people who already meet our customers.",
      why: "We cannot stand on every street. Good partners bring work we would miss. Bad partners bring fights and unpaid promises.",
      how: "You find, qualify, agree terms, onboard, and track leads. Sales converts. You never skip Sales.",
      owns: [
        "Partner pipeline and terms",
        "Onboarding and partner file",
        "Partner lead tracker (source = partner)",
        "Keep / fix / close recommendation"
      ],
      mustNot: [
        "Convert the partner’s customer yourself",
        "Hand a lead straight to a provider",
        "Pay outside agreed terms",
        "Promise a service we do not have"
      ],
      responsibilities: [
        { title: "Find", what: "Name real partner types we miss (hotel desk, appliance shop, and so on).", why: "Random uncles are not a channel." },
        { title: "Qualify", what: "Who they serve, reputation, whether we can work with them.", why: "A loud partner with bad jobs hurts the brand." },
        { title: "Agree and start", what: "Written terms, how a lead is sent, what we pay if we pay.", why: "Handshake-only deals become arguments." },
        { title: "Track", what: "Leads, bookings, and whether they are still sending work.", why: "A silent partner is a closed partner. Write it down." }
      ],
      when: {
        daily: ["Follow live partner chats. Log new leads to Sales the same day.", "Chase one stuck onboarding."],
        weekly: ["Pipeline and live partner numbers.", "Fix a partner who sends junk leads."],
        monthly: ["Keep / fix / close each live partner.", "Plan new types with Intelligence and Head of Growth."]
      },
      standards: [
        "100% of partner leads tagged with partner name.",
        "No live partner without a written term sheet.",
        "Leads to Sales the same day.",
        "Monthly review of every live partner."
      ],
      procedures: [
        { id: "PC-01", title: "Qualify a partner", when: "A new name appears.", steps: ["Who they already serve.", "What we want from them.", "Reputation and fit.", "Mark fit / wait / no."] },
        { id: "PC-02", title: "Start a partner", when: "Head of Growth has agreed the type and terms.", steps: ["Write how a lead is sent.", "Give them only approved materials.", "Test one lead into Sales.", "Only then call them live."] },
        { id: "PC-03", title: "Partner lead to Sales", when: "Every partner enquiry.", steps: ["Source = partner + name.", "Service and town.", "Assign to Sales. Do not assign to a provider.", "Track whether it booked."] }
      ],
      kpis: [
        { name: "Partner leads tagged", target: "100%" },
        { name: "Live partners with written terms", target: "100%" },
        { name: "Monthly partner review", target: "100%" }
      ],
      rules: [
        "Sales owns the customer. You own the pipe.",
        "No cash side deals.",
        "If they sell a job we do not do, stop them."
      ],
      escalate: [
        { when: "A partner is taking money in our name", to: "Head of Growth same day", how: "Stop the channel. Write facts." },
        { when: "Terms need money we did not plan", to: "Head of Growth, then Finance", how: "Do not promise payment yourself." }
      ]
    })
  };

  const workflows = {
    growth: {
      id: "growth",
      name: "Growth workflow",
      kicker: "How Growth works",
      lede: "Growth makes something the market can buy, then creates tagged enquiries. Sales books. Operations does the job. These three must not mix.",
      hero: "wf-growth.png",
      rules: [
        "Marketing generates the enquiry. Sales converts it. Operations finishes the job.",
        "Only approved services in approved towns.",
        "Every enquiry has a source: digital, field, partner, or other.",
        "Head of Growth says yes or no. Channels do not invent the company.",
        "If we cannot finish the job, we do not advertise it."
      ],
      paths: [
        {
          id: "service",
          title: "New or improved service",
          art: "wf-service.png",
          why: "Use this when people keep asking for something we do not sell well, or a live service is failing.",
          steps: [
            { who: "Market Intelligence", does: "Names the gap with evidence." },
            { who: "Offer & Service Development", does: "Writes the service sheet. Finance prices. Operations says if we can deliver." },
            { who: "Head of Growth", does: "Says yes. Marketing may talk about it." },
            { who: "Marketing (Digital + Field)", does: "Creates tagged enquiries." },
            { who: "Sales", does: "Books the job." },
            { who: "Operations", does: "Does the job. Results go back to Intelligence and Offer." }
          ]
        },
        {
          id: "area",
          title: "New area",
          art: "wf-area.png",
          why: "Use this when we want to work in a town we do not cover yet.",
          steps: [
            { who: "Market Intelligence", does: "Names the town with evidence." },
            { who: "Market Expansion", does: "Writes enter, wait, or no." },
            { who: "Provider Operations", does: "Confirms we can finish the first jobs there." },
            { who: "Head of Growth", does: "Says yes to launch." },
            { who: "Marketing and Partnerships", does: "Create tagged enquiries in that town." },
            { who: "Sales and Operations", does: "Book and deliver. Expansion then says scale, fix, pause, or leave." }
          ]
        },
        {
          id: "engine",
          title: "Weekly demand engine",
          art: "wf-engine.png",
          why: "Use this every week for services and towns we already sell.",
          steps: [
            { who: "Marketing Manager", does: "Runs the signed plan: who, offer, town, channel, budget." },
            { who: "Digital Marketing Manager", does: "Runs the online part. Hands tagged digital enquiries to Sales." },
            { who: "Field Marketing Manager", does: "Runs the town part. Hands tagged field enquiries to Sales." },
            { who: "Partnerships", does: "Adds extra pipes. Hands tagged partner enquiries to Sales." },
            { who: "Sales", does: "Books, or writes why it was lost so Growth can learn." }
          ]
        }
      ]
    }
  };

  const extra = {
    hog: {
      story: "In The E-Myth Revisited, Growth is a function, not a person who does a bit of everything. This seat owns the franchise prototype for demand: who we sell to, what we may promise, and whether the numbers say keep going, stop, or research. You hold five function owners. You do not become them.",
      receives: [
        { from: "CEO", what: "Company priorities, budget ceiling, and what Panun Kaergar will not do." },
        { from: "Market Intelligence", what: "Go / no-go packs with source and date. Opportunity funnel." },
        { from: "Marketing", what: "Weekly spend, tagged enquiries, cost per enquiry, cost per booking." },
        { from: "Sales", what: "Lost-lead reasons. Which sources actually book." },
        { from: "Operations", what: "Whether we can finish the jobs we are selling, by service and town." }
      ],
      gives: [
        { to: "Marketing Manager", what: "A signed monthly plan: who, offer, towns, channels, budget, targets." },
        { to: "Offer & Expansion", what: "Yes, no, or send back on a service or a town." },
        { to: "Partnerships", what: "Yes or no on a partner type and commercial shape." },
        { to: "CEO", what: "Weekly Growth numbers and anything that risks money, brand, or a launch." }
      ],
      records: ["Monthly Growth plan", "Weekly Growth review", "Opportunity clock (High items)", "Approved service list", "Approved town list", "Budget vs spend"],
      good: ["Every Growth enquiry has a source.", "Marketing only talks about approved services in approved towns.", "A High opportunity is decided in 10 working days.", "Cost per booking is inside the signed plan.", "Function owners can name their one result without looking at a slide."],
      bad: ["Ads running off-plan because ‘it might work’.", "A town launched with no providers.", "Lost leads sitting in chat with no reason code.", "You writing the ads yourself while five boxes sit empty and unnamed.", "Spend stories instead of a written review."]
    },
    mkm: {
      story: "Marketing is the engine that turns an approved offer into tagged demand. You own the plan, the brand, the calendar, the budget split, and the source on every marketing enquiry. Digital and Field report to you. They run channels. You run the system.",
      receives: [
        { from: "Head of Growth", what: "Signed monthly plan and any mid-month change." },
        { from: "Market Intelligence", what: "What is working in the market, and what to stop guessing about." },
        { from: "Offer", what: "The only claims Marketing may use for a live service." },
        { from: "Expansion", what: "Which towns are open for spend." },
        { from: "Digital and Field", what: "Daily spend, enquiries, and problems." }
      ],
      gives: [
        { to: "Digital Marketing Manager", what: "Calendar slice, digital budget, approved creatives, weekly targets." },
        { to: "Field Marketing Manager", what: "Town days, materials, field budget, weekly targets." },
        { to: "Sales", what: "Tagged marketing enquiries the same day." },
        { to: "Head of Growth", what: "Weekly marketing report: spend, enquiries, cost, bookings." }
      ],
      records: ["Monthly marketing plan", "Marketing calendar", "Content calendar", "Creative library", "Campaign register", "Budget tracker", "Lead source tracker", "Weekly marketing report"],
      good: ["Plan signed before the month starts.", "95% of marketing leads have a source.", "Digital and Field work from the same calendar.", "No live creative without a brand and fact check.", "Budget variance within 10% unless Head of Growth signed a change."],
      bad: ["Two different promises on digital and field.", "A campaign with spend and no tag.", "A flyer with a price Finance never signed.", "Sales asking ‘where did this lead come from?’ and nobody knows.", "Boosting a post from a personal page."]
    },
    hom: {
      story: "You own all of Panun Kaergar online — ads, search, the app, social, other sites, and the videos that feed them. Marketing Manager gives you the month. You send back tagged leads and an honest pack: what worked and what failed. You do not invent the offer. You do not book the customer.",
      receives: [
        { from: "Marketing Manager", what: "Towns, services, ads money, the signed kit (words and look), where a lead must land, and what we will not do this month." },
        { from: "Sales", what: "Which digital leads booked, and why the rest were lost." },
        { from: "Operations", what: "Raw customer-feedback and provider videos for the Content Maker to edit." },
        { from: "CEO / founder", what: "Personal brand films, when they are the face." },
        { from: "Technology", what: "Access to the website and app so search work can happen, and tracking that a test lead really lands in Sales." },
        { from: "Content Maker", what: "Finished files, filed the same day." }
      ],
      gives: [
        { to: "Sales", what: "Tagged digital leads the same day: pipe, campaign or post, service, town." },
        { to: "Marketing Manager", what: "Daily note if spend jumped or you paused. Weekly pack by pipe, with 3 wins, 3 fails, and 1 ask." },
        { to: "Intelligence", what: "What people keep asking that we do not sell — as a signal, not as a new ad." },
        { to: "Content Maker", what: "File list, the kit, Operations footage when it arrives, and which files ads and social need next." },
        { to: "Sales / CX", what: "Angry comments about a job — do not fight in public." }
      ],
      records: ["Signed kit", "Ads calendar", "Spend log", "Tracking checklist", "Content library", "File lists and Operations footage log", "Digital lead tags", "Daily notes", "Weekly digital pack"],
      good: ["Every paid ad had a test lead into Sales.", "Leads are tagged the same day.", "The library has today’s approved files.", "The weekly pack names fails, not only wins.", "Customer and provider films were edited from Operations footage.", "New claims waited for Marketing Manager."],
      bad: ["A private boost.", "A price that Finance did not sign.", "Sitting on a chat because you ‘already started it’.", "Ads for a town with no providers.", "A customer film that never came through Operations.", "A weekly report with only good news."]
    },
    cmc: {
      story: "The Content Maker produces Panun Kaergar’s marketing videos and posts for the Digital Marketing Manager: AI, brand, and founder files from the signed kit, plus customer and provider films collected from Operations and edited. Finished files go for approval the same working day, and enter the company library only after a yes.",
      brief: "Produces Panun Kaergar’s marketing videos and posts for the Digital Marketing Manager — AI, brand, and founder files from the signed kit, plus customer and provider films collected from Operations and edited. Finished files go for approval the same working day.",
      receives: [
        { from: "Digital Marketing Manager", what: "The day's file list, the signed kit, the review path, the library path and naming rule, and a yes or no on each finished file." },
        { from: "Operations", what: "Raw customer-feedback videos and provider job videos to collect, edit, and send for approval." }
      ],
      gives: [
        { to: "Digital Marketing Manager", what: "Named files ready for approval the same working day, a same-day file note, and a blocked list if Operations footage is still waiting." }
      ],
      records: ["File lists", "Signed kit copy in use", "Operations footage log", "Review folder", "Content library log", "Same-day file notes", "Blocked list (waiting on Operations footage)"],
      good: ["Every finished file is sent to the Digital Marketing Manager for approval the same working day.", "A file enters the library only after the Digital Marketing Manager says yes.", "Every file matches the signed kit.", "Customer and provider files are edited from Operations footage."],
      bad: ["Files only on a personal phone or private chat.", "A file placed in the library before the Digital Marketing Manager approved it.", "A new claim, price, town, or face added without Marketing Manager.", "Raw Operations footage sitting unedited at the end of the week."],
      detailed: {
        definition: {
          what: [
            { title: "Makes Panun Kaergar’s marketing videos and posts", why: "This role reports to the Digital Marketing Manager. Each working day it turns a written file list into finished videos and posts, using only the signed marketing kit." },
            { title: "Produces AI, brand, and founder files; edits footage from Operations", why: "AI videos, AI posts, brand films, and founder films are made from the signed kit. Customer-feedback and provider job videos are collected from Operations, then edited to the same kit." },
            { title: "Hands finished files to the Digital Marketing Manager for approval", why: "Every file is named, dated, and sent the same working day. It enters the company library only after the Digital Marketing Manager approves it." }
          ],
          why: [
            { title: "Digital channels cannot go live without finished files", why: "Paid ads, social, website search, the app store, Reddit, and Quora all publish from approved videos and posts in the company library." },
            { title: "Making content and running digital marketing are two jobs", why: "The Digital Marketing Manager owns ads, search, social, and publishing. This role exists so production has a named owner, and those channels always have files ready to approve." },
            { title: "The company needs one on-brand library, not files on a phone", why: "Named, dated files that match the signed kit can be reused, measured, and handed to the next person. That only happens when one role is responsible for making them." }
          ]
        },
        glossary: [
          {
            id: "file-list",
            term: "File list",
            aliases: ["file lists", "file list", "file-lists", "file-list"],
            also: "Today's list, the written list, the Digital Marketing Manager's list",
            meaning: "Today's work order from the Digital Marketing Manager. Each line is one video or one post to make or edit: the type (AI post, AI video, brand, customer, provider, or founder), the service, the town if there is one, where it will be used, and the source (you make it from the kit, you edit a founder film, or you collect and edit an Operations video). Work starts only from this written list, not from a chat."
          },
          {
            id: "signed-kit",
            term: "Signed kit",
            aliases: ["signed marketing kit", "marketing kit", "signed kit", "kit-approved", "kit-faithful", "on-kit", "off-kit", "the kit", "kit-true", "kit"],
            also: "Signed marketing kit, the kit",
            meaning: "The instruction book for every video and post. The Marketing Manager writes it and puts their name and the month on it. The Digital Marketing Manager gives you the current signed copy. It holds the logo, colours, tone, sentences you may put on screen, live services, signed prices, open towns, and allowed faces. Copy those words and that look. Do not invent a new sentence, price, town, or face."
          },
          {
            id: "blocked-list",
            term: "Blocked list",
            aliases: ["blocked list", "blocked items", "blocked item"],
            also: "Blocked items, lines waiting on Operations footage",
            meaning: "A named list of customer-feedback and provider films that cannot be edited yet because Operations has not sent the raw files. Each row names who, which service, which town, and since when. It travels with the same-day file note and is rolled up in the weekly production pack. Keep other ready lines moving while a line is blocked."
          },
          {
            id: "dmm",
            term: "Digital Marketing Manager",
            aliases: ["Digital Marketing Manager"],
            also: "This seat's manager",
            meaning: "The seat this role reports to. Owns all online work: paid ads, website and app search, social, other sites, videos and posts. Writes the file list, hands over the signed kit, names the review path and the company library, approves finished files, and publishes from the library. Sends tagged digital leads to Sales."
          },
          {
            id: "mkm",
            term: "Marketing Manager",
            aliases: ["Marketing Manager"],
            meaning: "Owns the monthly marketing plan, brand, and the signed kit. Writes and signs the kit. Digital Marketing Manager and Field Marketing Manager report here. A new claim, price, town, or face is a kit change that must come from this seat."
          },
          {
            id: "fmm",
            term: "Field Marketing Manager",
            aliases: ["Field Marketing Manager"],
            meaning: "Owns towns, stalls and local presence. Reports to the Marketing Manager. Delivers tagged local enquiries from named towns on the calendar. This is not the Content Maker’s manager."
          },
          {
            id: "content-maker",
            term: "Content Maker",
            aliases: ["Content Maker"],
            also: "This seat",
            meaning: "This seat. Produces Panun Kaergar’s marketing videos and posts for the Digital Marketing Manager to approve and publish. Makes AI, brand, and founder files from the signed kit, and collects customer and provider footage from Operations to edit."
          },
          {
            id: "library",
            term: "Company library",
            aliases: ["company library", "content library", "the library", "library"],
            also: "Content library, the library folder",
            meaning: "The shared folder on the company drive that holds the actual videos (.mp4) and still posts (.jpg or .png) the Digital Marketing Manager has approved. A file enters this folder only after a written yes. It is not a set of Word documents, and it is not a draft on a laptop."
          },
          {
            id: "review-path",
            term: "Review path",
            aliases: ["review path", "review folder"],
            also: "Review folder",
            meaning: "The folder the Digital Marketing Manager named for files waiting for a yes or a no. Place the named finished file here the same working day. Do not put it in the company library until there is a written yes."
          },
          {
            id: "file-note",
            term: "Same-day file note",
            aliases: ["same-day file note", "same-day note", "file note"],
            also: "Daily file note",
            meaning: "The daily written pack this seat sends to the Digital Marketing Manager. It lists files sent for approval today, files approved into the company library today, files sent back, and file-list lines that are blocked. It is how the Digital Marketing Manager approves and plans without a meeting."
          },
          {
            id: "operations",
            term: "Operations",
            aliases: ["Operations"],
            meaning: "The function that owns fulfilment and the job on site. Operations captures customer-feedback videos and provider work videos, then sends the raw files. This seat does not capture those videos; it collects them, edits them to the kit, and sends the cut for approval."
          },
          {
            id: "ops-footage",
            term: "Operations footage",
            aliases: ["Operations footage", "Operations files", "Operations file", "Operations video", "Operations videos"],
            also: "Raw Operations footage, customer and provider videos",
            meaning: "The raw customer-feedback and provider job videos Operations captured on site. This seat collects those files, edits them to the signed kit, and sends the finished cut to the Digital Marketing Manager for approval. If the footage has not arrived, the file-list line goes on the blocked list."
          },
          {
            id: "kit-gap",
            term: "Kit gap",
            aliases: ["kit-gap", "kit gap"],
            meaning: "When a file-list line asks for a sentence, price, town, or face that is not in the signed kit. Stop that line. Write the gap. Send it to the Digital Marketing Manager, who asks the Marketing Manager. Keep other on-kit lines moving."
          },
          {
            id: "first-pass",
            term: "First-pass approval",
            aliases: ["first-pass approval", "first-pass yes", "first-pass rate", "First-pass"],
            meaning: "A written yes from the Digital Marketing Manager on the first version of a file, with no remake. The target is 90 percent or more of decided files. A no that is not a kit gap means the file was unfinished or off-kit."
          },
          {
            id: "hog",
            term: "Head of Growth",
            aliases: ["Head of Growth"],
            meaning: "Owns who the customer is, the promise, and the Growth numbers. The Marketing Manager reports here. A new service or a new town must be signed by this seat before it can go on the file list or into the kit."
          },
          {
            id: "weekly-pack",
            term: "Weekly production pack",
            aliases: ["weekly production pack", "weekly pack"],
            meaning: "The weekly report this seat writes for the Digital Marketing Manager. It shows file list versus delivered — sent, approved, blocked, or missed — plus first-pass and eventual approval rates, and Operations footage still outstanding. Misses and blocks must be named, not only finished work."
          },
          {
            id: "naming-rule",
            term: "Naming rule",
            aliases: ["naming rule", "naming pattern"],
            also: "File-name pattern",
            meaning: "The file-name pattern the Digital Marketing Manager issued. A working shape is type_service_town_date_version plus the real extension, for example aivideo_plumbing_srinagar_20260921_v01.mp4. Version starts at v01. A remake uses the next version."
          }
        ],
        responsibilities: [
          {
            kicker: "Responsibility 1",
            title: "Make or edit the files on the Digital Marketing Manager's list",
            lead: "The Digital Marketing Manager writes a list of the videos and posts needed. This seat makes or edits each one using only the signed kit, then sends the finished file to the Digital Marketing Manager for approval.",
            art: "cmc-r-produce.png",
            body: [
              "The file list is today's work. The Digital Marketing Manager writes it. Each line is one video or one post: the type (AI post, AI video, brand, customer, provider, or founder), the service, the town if there is one, and where the raw file comes from (you make it from the kit, you edit an Operations video, or you edit a founder film).",
              "The signed kit is the instruction book from the Marketing Manager. It holds the words, prices, colours, logo, towns, and faces you may use. Read the list and the kit before you open an editor. A file is ready for approval when the Digital Marketing Manager can open it and check it against the kit. If a line asks for a price, town, sentence, or face that is not in the kit, send that line back to the Digital Marketing Manager. They ask the Marketing Manager to update the kit. Then you make the file."
            ],
            points: [
              { title: "Read the Digital Marketing Manager's file list", why: "Each line is one video or post to make or edit today." },
              { title: "Use the kit for look and words", why: "Logo, navy, gold, cream, approved sentences, approved services and towns." },
              { title: "Send a finished file to the Digital Marketing Manager for approval", why: "A complete video or post the Digital Marketing Manager can check. Then they say yes or send it back." }
            ],
            meta: [
              ["Takes from", "File list and signed kit, given by the Digital Marketing Manager"],
              ["Hands to", "The Digital Marketing Manager, as a named file ready for approval, then a same-day note"]
            ]
          },
          {
            kicker: "Responsibility 2",
            title: "Create AI video and AI posts",
            lead: "Use AI tools to produce videos and still posts from kit words, kit look, and kit-approved scenes.",
            art: "cmc-r-ai.png",
            body: [
              "AI is a production tool. Prompts are built from the signed kit: approved services, approved towns, approved sentences, and the approved visual world (Kashmir homes, mountains, navy and gold). If the model writes a cheaper price, a new town, a medical claim, or a guarantee the kit does not carry, that output is discarded and generated again from the kit.",
              "Every AI file is still a company file. It is named, dated, and sent to the Digital Marketing Manager for approval the same working day, with type marked as AI video or AI post so the Digital Marketing Manager and Marketing Manager can see what is generated and what is edited from Operations footage."
            ],
            points: [
              { title: "Prompt from the kit", why: "Copy the allowed words into the prompt." },
              { title: "Watch the output for invented facts", why: "If the model names a town or service that is not in the kit, discard that file and generate again." },
              { title: "Send AI the same way as edited work", why: "Name it with type, service, town, and date, and send it to the Digital Marketing Manager for approval the same working day." }
            ],
            meta: [
              ["Takes from", "Kit words and look; file-list lines marked AI video or AI post"],
              ["Hands to", "The Digital Marketing Manager for approval, type = AI video or AI post"],
              ["Stops when", "The model introduces a claim, price, town, or face that is not in the kit"]
            ]
          },
          {
            kicker: "Responsibility 3",
            title: "Create brand films and personal-brand films",
            lead: "Produce films that show Panun Kaergar as one company, and films that put the founder on camera when they are the approved face, using the kit script and look.",
            art: "cmc-r-brand.png",
            body: [
              "Brand films explain who Panun Kaergar is, what a customer can expect, and how a job is done, using approved services and approved towns. They are library assets the Digital Marketing Manager may later put on social, the website, or a paid pipe. Personal-brand films use the founder or another named face that Marketing Manager has already put in the kit.",
              "The Content Maker prepares the file list with the Digital Marketing Manager, confirms the face is in the kit, records or edits to the approved length and look, and sends the master plus any cut-downs the Digital Marketing Manager asked for. A new face, a new origin story, or a new guarantee is a kit change. Kit changes go to the Marketing Manager through the Digital Marketing Manager, then production resumes from the updated kit."
            ],
            points: [
              { title: "Confirm the face is in the kit before recording", why: "Use only the faces Marketing Manager has already signed." },
              { title: "Keep the script inside approved sentences", why: "If the founder ad-libs a price or a town, note it and send that line back through the Digital Marketing Manager." },
              { title: "Send the master and the cut-downs for approval", why: "The Digital Marketing Manager should be able to check the file without a recut." }
            ],
            meta: [
              ["Takes from", "Kit (brand look and allowed faces); the Digital Marketing Manager file list; founder time if it is a personal-brand film"],
              ["Hands to", "The Digital Marketing Manager for approval as brand film or personal-brand film, same day"]
            ]
          },
          {
            kicker: "Responsibility 4",
            title: "Collect customer and provider videos from Operations, then edit them",
            lead: "Customer-feedback films and provider job films are captured by Operations. This seat collects those files, edits them to the kit, and sends the finished versions to the Digital Marketing Manager for approval.",
            art: "cmc-r-consent.png",
            body: [
              "Operations owns the job, the provider, and the customer on site. They capture customer feedback videos and provider work videos. The Content Maker's work on those files starts when Operations sends the raw footage: who, which service, which town, and which date.",
              "Edit that footage to the kit look and the kit words. Cut to the lengths the Digital Marketing Manager named on the file list. Name the finished file, note that the source is Operations, and send it to the Digital Marketing Manager for approval the same working day."
            ],
            points: [
              { title: "Collect from Operations", why: "Ask the Digital Marketing Manager if the files have not arrived. Keep a line on the blocked list until the footage is in hand." },
              { title: "Edit to the kit", why: "Navy, gold, cream, approved sentences, approved services and towns." },
              { title: "Send the finished cut for approval", why: "The Digital Marketing Manager publishes from the approved library copy, not from a raw phone clip." }
            ],
            meta: [
              ["Takes from", "Raw customer-feedback and provider videos from Operations; kit; file-list lengths"],
              ["Hands to", "The Digital Marketing Manager for approval as customer film or job film, with Operations named as the source"],
              ["Escalates to", "Digital Marketing Manager if the footage has not arrived"]
            ]
          },
          {
            kicker: "Responsibility 5",
            title: "Name, send for approval, and keep the library true",
            lead: "The company library is a shared folder of videos and still posts the Digital Marketing Manager has approved. Every finished file is named and sent for that yes the same working day.",
            art: "cmc-r-library.png",
            body: [
              "The library is not a set of Word documents. It is the folder the Digital Marketing Manager named on the company drive: the actual .mp4 videos and the actual still posts (.jpg or .png) that ads and social will use. A file enters that folder only after the Digital Marketing Manager writes a yes. Until then, the named file sits in the review path the Digital Marketing Manager named.",
              "Send the finished file for approval before the day ends. Keep the log in step: what the Digital Marketing Manager asked for, what is waiting for a yes, what is now in the library, and what is still waiting on Operations."
            ],
            points: [
              { title: "Name with type, service, town, date, version", why: "Example: aivideo_plumbing_srinagar_20260921_v01.mp4." },
              { title: "Send for approval the same working day", why: "The company copy is the approved file in the library, not a draft on a laptop." },
              { title: "Tell the Digital Marketing Manager it is ready to approve, or tell the Digital Marketing Manager it is waiting", why: "The note is part of the job, so the Digital Marketing Manager can approve, publish, and plan." }
            ],
            meta: [
              ["Takes from", "The finished file; the review path, library path, and naming rule from the Digital Marketing Manager"],
              ["Hands to", "The Digital Marketing Manager for approval, then the library after a yes, plus the same-day file note"]
            ]
          }
        ],
        handoffs: [
          {
            side: "in",
            kicker: "You receive",
            title: "The Digital Marketing Manager's file list",
            lead: "A written list of the videos and posts the Digital Marketing Manager needs this day or this week. Each line is one file to make or edit.",
            art: "cmc-h-shot.png",
            body: [
              "The Digital Marketing Manager writes this list. It is the day's work, not a camera script. Each line names the type (AI post, AI video, brand film, customer film, provider film, founder film), the service, the town if there is one, where it will be used (ads, social, website), and the source: you make it from the kit, you edit a founder film, or you collect and edit an Operations video.",
              "If a customer or provider line has no Operations file yet, mark it waiting and keep the rest of the list moving."
            ],
            points: [
              { title: "Each line holds", why: "Type, service, town or 'no town', where it will be used, and source (kit/AI, founder, or Operations)." },
              { title: "If it arrives as a chat", why: "Ask the Digital Marketing Manager to put it on the written file list." }
            ],
            meta: [
              ["From", "Digital Marketing Manager"],
              ["When", "Before work starts on that line"],
              ["You do with it", "Plan the day's files from the written lines."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "The signed marketing kit",
            lead: "The Marketing Manager writes this pack and puts their name and the month on it. The Digital Marketing Manager gives you the current copy. It is the instruction book for every video and post.",
            art: "cmc-h-kit.png",
            body: [
              "The kit is a dated written pack — usually one document plus logo files in a folder named for the month, for example 'Marketing kit — September 2026'. The Marketing Manager writes it and signs it. The Digital Marketing Manager passes that same pack to this seat. Keep it open next to the editor.",
              "Inside the pack: the logo files you may place on a video; the colours (navy, gold, cream); the tone; the headlines and sentences you may put on screen; the live services; the prices Finance has signed; the towns that are open this month; and the faces that may appear (for example the founder). Use those words and that look. If a file needs a new sentence, a new price, a new town, or a new face, send the line to the Digital Marketing Manager. The Digital Marketing Manager asks the Marketing Manager. Work resumes when the kit is updated in writing."
            ],
            points: [
              { title: "Who writes it", why: "Marketing Manager. The Digital Marketing Manager hands you the current signed copy." },
              { title: "What it looks like", why: "One dated pack for the month: a document plus logo files, with the Marketing Manager's name on it." },
              { title: "What you use from it", why: "Sentences, prices, towns, colours, logo, and allowed faces — copied into the video or post." }
            ],
            meta: [
              ["From", "Digital Marketing Manager (pack written and signed by Marketing Manager)"],
              ["When", "At the start of the month, and whenever Marketing Manager issues a change"],
              ["You do with it", "Every prompt, every on-screen word, every logo and colour"]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Customer-feedback and provider videos from Operations",
            lead: "Raw files captured on the job: customer feedback and provider work. Operations sends them. This seat collects, edits, and sends them for approval.",
            art: "cmc-h-yes.png",
            body: [
              "Operations captures customer feedback and provider videos while the job is being done. They send the raw files with who, which service, which town, and which date. The Content Maker collects those files as soon as they arrive.",
              "Edit them to the kit, then note on the log that the source is Operations, and send the cut to the Digital Marketing Manager for approval."
            ],
            points: [
              { title: "Provider videos", why: "Operations captures the work on site and sends the file." },
              { title: "Customer feedback videos", why: "Operations collects the feedback on the job and sends the file." },
              { title: "Founder face", why: "Personal-brand films stay a kit production with the Digital Marketing Manager, using a face already in the kit." }
            ],
            meta: [
              ["From", "Operations, asked for by the Digital Marketing Manager on the file list"],
              ["When", "As soon as the raw file is ready"],
              ["You do with it", "Collect it. Edit it to the kit. Send the finished cut to the Digital Marketing Manager for approval the same working day."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Review path, library folder, and naming rule",
            lead: "The folder where named files wait for the Digital Marketing Manager's yes, the shared folder where approved videos and posts live, and the file-name pattern the Digital Marketing Manager expects.",
            art: "cmc-r-library.png",
            body: [
              "The Digital Marketing Manager names two places on the company drive: a review path for files waiting for a yes, and the company library for files the Digital Marketing Manager has approved. The library holds the finished videos (.mp4) and still posts (.jpg or .png). It does not hold the marketing kit, and it does not hold Word documents about the work.",
              "The Digital Marketing Manager also writes the naming pattern. Use that pattern on every file you send for approval. If either path changes, the Digital Marketing Manager writes the new path."
            ],
            points: [
              { title: "What lives in the library", why: "Only videos and still posts the Digital Marketing Manager has approved and can put live." },
              { title: "Pattern", why: "type_service_town_date_version, plus the real extension — for example .mp4 or .jpg." }
            ],
            meta: [
              ["From", "Digital Marketing Manager"],
              ["When", "Once, then again only if the system changes"],
              ["You do with it", "Name every finished file, send it for approval, and place it in the library only after a yes"]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "The Digital Marketing Manager's yes or no",
            lead: "A written yes puts the named file in the library. A written no comes with what to change. Until then the file stays in review.",
            art: "cmc-h-note.png",
            body: [
              "The Digital Marketing Manager checks the named file against the signed kit: words, prices, towns, faces, look, and name. A yes is written. Then this seat places the file in the library folder and marks the log.",
              "A no is also written: what is wrong and what to remake. Keep the named file. Remake from the Digital Marketing Manager's note. Send the new version the same working day if you can."
            ],
            points: [
              { title: "A yes", why: "Place the file in the library the same working day and mark the log." },
              { title: "A no", why: "Remake from the Digital Marketing Manager's note. Do not put the old file in the library." }
            ],
            meta: [
              ["From", "Digital Marketing Manager"],
              ["When", "The same working day the file was sent, or the next working morning if it arrived late"],
              ["You do with it", "File on a yes. Remake on a no."]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Named files sent for the Digital Marketing Manager approval",
            lead: "The actual videos and still posts — named, ready to check — in the review path the Digital Marketing Manager named, the same working day they are finished.",
            art: "cmc-h-files.png",
            body: [
              "This is the output of the seat. The Digital Marketing Manager opens the review path, checks the file against the kit, and writes a yes or a no. A yes is what lets the file enter the library.",
              "Place the named file in the review path the same working day, then send the same-day file note so the Digital Marketing Manager knows it is waiting."
            ],
            points: [
              { title: "Put in review", why: "The finished video or still, and any shorter cut the Digital Marketing Manager asked for on the file list." },
              { title: "Mark the type on the log", why: "AI, brand, customer, provider, or founder, so the sheet matches the file." }
            ],
            meta: [
              ["To", "Digital Marketing Manager, in the review path they named"],
              ["When", "The same working day the file is finished"],
              ["Then", "Wait for a written yes or no"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Approved files in the company library",
            lead: "After the Digital Marketing Manager writes a yes, the actual videos and still posts — .mp4, .jpg, .png — go in the named library folder, named correctly, the same working day.",
            art: "cmc-r-library.png",
            body: [
              "The library is those media files, not a report, and not a draft. The Digital Marketing Manager opens the folder and picks a video or a post for Meta, Google, social, the website, the app store, Reddit, or Quora.",
              "Place the named file there the same working day the Digital Marketing Manager says yes, then mark the same-day file note so the Digital Marketing Manager knows it is in the library."
            ],
            points: [
              { title: "Put in the folder only after a yes", why: "The approved video or still, and any shorter cut the Digital Marketing Manager asked for on the file list." },
              { title: "Mark the type on the log", why: "AI, brand, customer, provider, or founder, so the sheet matches the folder." }
            ],
            meta: [
              ["To", "The content library owned with the Digital Marketing Manager"],
              ["When", "The same working day the Digital Marketing Manager approves the file"],
              ["Then", "Send or update the same-day file note so the Digital Marketing Manager knows it is there"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Same-day file note",
            lead: "A short written pack that tells the Digital Marketing Manager what is waiting for a yes, what is now in the library, and what is still blocked, so they can approve, publish, and plan without chasing.",
            art: "cmc-h-note.png",
            body: [
              "Every working day on which a file is finished, or on which a file-list line cannot move, the Content Maker sends the same-day file note to the Digital Marketing Manager. The Digital Marketing Manager uses it to know what to approve, what they may put live tomorrow, and what is still waiting.",
              "The note lists the files sent for approval today, the files the Digital Marketing Manager approved into the library today (name, type, service, town, date), and the file-list lines that are blocked (what is waiting, usually Operations footage).",
            ],
            points: [
              { title: "Must contain", why: "Date, files waiting for approval, files now in the library, blocked items, what footage is still waiting." }
            ],
            meta: [
              ["To", "Digital Marketing Manager"],
              ["When", "Same working day, after files are sent for approval or placed in the library"],
              ["Copy", "Nobody else unless the Digital Marketing Manager asks"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Blocked list — waiting on Operations footage",
            lead: "A named list of customer-feedback and provider films that cannot be edited yet because Operations has not sent the raw files.",
            art: "cmc-h-block.png",
            body: [
              "When a file-list line needs a customer or provider video and the file has not arrived, add the line to the blocked list: who, service, town, since when. That list travels with the same-day note and is rolled up in the weekly pack.",
              "The Digital Marketing Manager holds Operations for the files. This seat keeps producing on-kit AI and brand work that is ready, and edits footage as soon as it arrives."
            ],
            points: [
              { title: "Name the gap", why: "Which Operations file, which service, which town. The Digital Marketing Manager must know what to ask for." },
              { title: "Keep other lines moving", why: "Edit AI, brand, and any Operations files that have already arrived." }
            ],
            meta: [
              ["To", "Digital Marketing Manager"],
              ["When", "Same day the block appears, and again on the weekly list"]
            ]
          }
        ],
        workflow: [
          {
            kicker: "Step 1",
            title: "Check the file list and the signed kit",
            lead: "Open both before you make anything. The list says which file. The kit says how it must look and what it may say.",
            art: "cmc-h-shot.png",
            body: [
              "At the start of the working day, open the current file list and the current signed kit. Walk each line: type, service, town, where it will be used, source. Mark lines that are ready to make, and lines that are waiting because Operations footage or a kit word is missing.",
              "If the file list has not arrived, write that on the daily note and wait. Work only the written lines once they arrive. If a line is only in a chat, ask the Digital Marketing Manager to put it on the list."
            ],
            points: [
              { title: "File list", why: "Which file to make or edit today." },
              { title: "Signed kit", why: "The words, prices, towns, colours, logo, and faces you may use." },
              { title: "You create", why: "A marked list: ready / blocked. Not a file yet." }
            ]
          },
          {
            kicker: "Step 2",
            title: "Make or edit the file on that line, using the kit",
            lead: "Create the content the list asked for, using only the signed kit. How you make it depends on the line — not on a later step.",
            art: "cmc-r-produce.png",
            body: [
              "Take the next ready line. The line tells you what kind of file it is. An AI post or AI video is made from the kit. A brand film or founder film is made or edited from the kit. A customer-feedback or provider film is collected from Operations, then edited to the kit. Those are kinds of work on the same list. They are not steps that wait for each other.",
              "If a customer or provider line has no Operations file yet, mark it blocked and take the next ready line. Keep AI and brand work moving. If the line asks for a sentence, price, town, or face that is not in the kit, stop that line and send it back to the Digital Marketing Manager. Then make the next ready file."
            ],
            points: [
              { title: "AI line", why: "Make the post or video from kit words and look." },
              { title: "Brand or founder line", why: "Make or edit from the kit. Use only a face already in the kit." },
              { title: "Customer or provider line", why: "Collect the raw file from Operations, then edit it to the kit. If it has not arrived, mark blocked and move on." }
            ]
          },
          {
            kicker: "Step 3",
            title: "Name the file and send it for approval",
            lead: "Put the named file in the review path. Do not put it in the library yet.",
            art: "cmc-w-name.png",
            body: [
              "Use the naming pattern the Digital Marketing Manager issued. A working shape is type_service_town_date_version plus the real extension. Version starts at v01. If this is a remake, use the next version.",
              "Place the named file in the review path the Digital Marketing Manager named. Send the same-day file note so they know a file is waiting for a yes."
            ],
            points: [
              { title: "You create", why: "A correctly named file, ready to check against the kit." },
              { title: "You submit", why: "To the Digital Marketing Manager, in the review path. Naming is not filing." }
            ]
          },
          {
            kicker: "If yes",
            fork: "yes",
            title: "Put the file in the library",
            lead: "A written yes is what lets the named file enter the company library.",
            art: "cmc-r-library.png",
            body: [
              "When the Digital Marketing Manager writes a yes, copy or move the named file into the company library path. Confirm it opens. Confirm the log row exists: name, type, service, town, date, source, approved by the Digital Marketing Manager.",
              "Update the same-day file note so they know it is in the library. Then take the next ready line on the file list."
            ],
            points: [
              { title: "From", why: "A written yes, and the named file in the review path." },
              { title: "You create", why: "A library copy plus the updated same-day note." }
            ]
          },
          {
            kicker: "If no",
            fork: "no",
            title: "Change it and send it again for approval",
            lead: "A written no comes with what to change. Remake from that note. Do not put the old file in the library.",
            art: "cmc-e-stop.png",
            body: [
              "Keep the named file out of the library. Read the Digital Marketing Manager's note. Change only what they asked for, still using the signed kit. Name the new version (v02, then v03) and put it back in the review path.",
              "Send the new version the same working day if you can. You are now waiting for a yes again — this is the same approval step, not a new kind of work. Mark the remake on the same-day note."
            ],
            points: [
              { title: "You do", why: "Remake from the written note. Keep the old file out of the library." },
              { title: "You submit", why: "The new version, back into the review path, for approval again." }
            ]
          }
        ],
        reporting: [
          {
            period: "Daily",
            kicker: "CMC-R1",
            title: "Same-day file note",
            art: "cmc-h-note.png",
            when: "Every working day on which you finish a file, or on which a file-list line cannot move.",
            lead: "A short written pack so the Digital Marketing Manager always knows what is waiting for a yes, what they approved into the library, and what is waiting on Operations footage.",
            body: [
              "This report exists so the Digital Marketing Manager always knows what to approve, what entered the library today, and which films are blocked, the same day. File it after files are sent for approval, and update it when a yes puts a file in the library."
            ],
            contents: [
              { title: "Date of the note", why: "So the pack can be filed against the working day." },
              { title: "Files sent for approval today, with names", why: "Type, service, town, date, version. The Digital Marketing Manager must be able to open the review file." },
              { title: "Files the Digital Marketing Manager approved into the library today", why: "The library copy is the company copy." },
              { title: "Files the Digital Marketing Manager sent back today", why: "A no is how first-pass approval is counted. Name the file, the reason, and whether a remake went out the same day." },
              { title: "File-list lines still waiting", why: "Name the film, the service, the town, and what is waiting — usually Operations footage, or a no from the Digital Marketing Manager." },
              { title: "Anything that went off-kit and was stopped", why: "The Digital Marketing Manager must see the stop, not only the success." }
            ],
            mustHave: ["Date", "Waiting for approval", "Approved into library", "Sent back or 'none'", "Blocked items", "What is waiting", "Stopped items or 'none'"],
            submit: [
              { to: "Digital Marketing Manager", why: "Same working day. This is how they approve and plan tomorrow's publishing without a meeting." }
            ]
          },
          {
            period: "Weekly",
            kicker: "CMC-R2",
            title: "Weekly production pack",
            art: "cmc-p-weekly.png",
            when: "The working day before the Digital Marketing Manager writes the weekly digital pack for the Marketing Manager.",
            lead: "A true picture of what the file list asked for, what the Digital Marketing Manager approved into the library, the week's approval rates, and what is still waiting — including misses, not only finished work.",
            body: [
              "The weekly pack is how the Digital Marketing Manager coaches this seat and how Marketing Manager sees whether content is the constraint on ads and social. A week of only highlights hides a blocked customer film that ads needed. The Content Maker lists every file-list line: sent for approval, approved into the library, blocked, or missed, with a reason, plus the first-pass and eventual approval rates."
            ],
            contents: [
              { title: "File list versus delivered", why: "Each line: asked, sent for approval, approved into library, blocked, or missed. Missed needs a reason (illness, Operations footage late, kit gap, the Digital Marketing Manager no, tool failure)." },
              { title: "Approval rates", why: "First-pass yes versus files the Digital Marketing Manager decided, and eventual yes after remakes. A falling first-pass rate means production is unfinished or off-kit." },
              { title: "Counts by type", why: "AI posts, AI video, brand, customer, job, personal-brand. The Digital Marketing Manager must see which well is dry." },
              { title: "Operations footage still outstanding", why: "Name the service, town, and how many working days it has been waiting." },
              { title: "Library gaps the Digital Marketing Manager asked to fill next", why: "So the next file list is written from evidence, not from memory." },
              { title: "One production risk", why: "For example 'customer films have been waiting 10 working days on Operations footage'." }
            ],
            mustHave: ["Week dates", "Asked vs delivered table", "First-pass approval rate", "Eventual approval rate", "Counts by type", "Outstanding Operations footage", "One risk", "Owner of the ask to the Digital Marketing Manager"],
            submit: [
              { to: "Digital Marketing Manager", why: "This is the content page of the weekly digital pack. They send numbers up. You send production truth." }
            ]
          },
          {
            period: "Monthly",
            kicker: "CMC-R3",
            title: "Next-month film plan support",
            art: "cmc-p-month.png",
            when: "In the last week of the month, in time for the Digital Marketing Manager to help the Marketing Manager write next month's plan.",
            lead: "A list of films and posts next month will need, and which Operations files must be sent in advance, so week one has footage to edit.",
            body: [
              "Monthly production is a list of files, not a hope. The Content Maker, from the library gaps and from the Digital Marketing Manager's draft calendar, lists the assets next month requires: type, service, town, and whether the raw file comes from Operations. The Digital Marketing Manager uses this to ask Operations before the month starts.",
              "This report says what files would be needed if the signed plan looks like the draft the Digital Marketing Manager showed you. If the plan later drops a town, those lines are dropped. If the plan adds a town, the Digital Marketing Manager issues a new file-list need and, if needed, a kit change through Marketing Manager."
            ],
            contents: [
              { title: "Assets needed next month, by type and service", why: "So the Digital Marketing Manager can write a real file list, not a hope." },
              { title: "Operations footage to collect before week one", why: "Customer feedback and provider videos, named, with a latest date." },
              { title: "Library leftovers that can be reused", why: "Reuse files that already exist and are still on kit." },
              { title: "Tools or access that failed this month", why: "If AI, editor, or the library path broke, write it so it can be fixed before next month." }
            ],
            mustHave: ["Month", "Needed assets table", "Reuse list", "Operations footage to collect", "Access issues or 'none'"],
            submit: [
              { to: "Digital Marketing Manager", why: "They fold it into next month's digital slice of the marketing plan." }
            ]
          }
        ],
        standards: [
          {
            title: "A file enters the library only after the Digital Marketing Manager approves it",
            lead: "Finished work is named and sent for a written yes the same working day. The library holds only files the Digital Marketing Manager has approved.",
            art: "cmc-r-library.png",
            body: [
              "The standard is 100 percent of finished files named and sent to the Digital Marketing Manager for approval the same working day, and 100 percent of library files carrying a written yes. If the Digital Marketing Manager has not answered before the day ends, the file stays in review and the wait is named on the same-day note."
            ],
            points: [
              { title: "What good looks like", why: "The Digital Marketing Manager can open today's review path and today's library and see every file the file list marked done, with a yes or a wait." }
            ]
          },
          {
            title: "Every file is on the signed kit",
            lead: "Every finished asset uses the claims, prices, towns, faces, and look in the current kit.",
            art: "cmc-h-kit.png",
            body: [
              "On-kit is a check: would Marketing Manager sign this sentence and this picture today? If the answer is no, remake the file from the kit or send the line back through the Digital Marketing Manager. AI output and Operations edits are held to the same standard. The Digital Marketing Manager uses this check when they approve."
            ],
            points: [
              { title: "What good looks like", why: "A stranger could publish the approved library file tomorrow and stay inside the signed promise." }
            ]
          },
          {
            title: "Customer and provider files are edited from Operations footage",
            lead: "Every customer-feedback film and provider job film in the library is a kit edit of a file Operations sent, then approved by the Digital Marketing Manager.",
            art: "cmc-r-consent.png",
            body: [
              "The standard is 100 percent of customer and job films logged with Operations as the source. Collect the raw file, edit it to the kit, send the finished cut to the Digital Marketing Manager for approval, and file it the same working day the Digital Marketing Manager says yes."
            ],
            points: [
              { title: "What good looks like", why: "Every job film and customer film names Operations as the source and sits in the library as an approved edit." }
            ]
          },
          {
            title: "The Digital Marketing Manager publishes from approved library files",
            lead: "Once the Digital Marketing Manager has approved the named file into the library, the Digital Marketing Manager owns going live.",
            art: "cmc-e-stop.png",
            body: [
              "The Content Maker's job on a file ends when it is named, sent for approval, and — after a yes — filed and reported. The Digital Marketing Manager publishes, spends, and handles comments. If someone asks this seat to put a file live because it is faster, tell the Digital Marketing Manager so they can publish from the library."
            ],
            points: [
              { title: "What good looks like", why: "Every live post can be traced to the Digital Marketing Manager publishing an approved library file." }
            ]
          }
        ],
        kpis: [
          {
            name: "Files approved before they enter the library",
            target: "100%",
            why: "The library must hold only files the Digital Marketing Manager has said yes to.",
            how: "Count library files added that calendar day. A file counts when the log shows a written yes from the Digital Marketing Manager dated on or before the library date."
          },
          {
            name: "First-pass approval rate",
            target: "90% or more",
            why: "A no from the Digital Marketing Manager that is not a kit gap means the file was unfinished or off-kit. Repeat send-backs eat the week the Digital Marketing Manager needs for publishing.",
            how: "Count files the Digital Marketing Manager decided this week with a written yes or no. First-pass yes = the Digital Marketing Manager wrote yes on the first version sent. Rate = first-pass yes ÷ files decided. Files still waiting for an answer are not in the rate. A kit-gap stop is a blocked line, not a no from the Digital Marketing Manager."
          },
          {
            name: "Eventual approval rate of decided files",
            target: "100%, except kit-gap stops",
            why: "A no from the Digital Marketing Manager is a remake, not a lost file. The week must not end with a decided no sitting unfixed.",
            how: "Of files the Digital Marketing Manager decided this week, count those that ended in a written yes, including remakes. Kit-gap stops stay on the blocked list and are not in this rate. A decided no with no remake sent is a miss."
          },
          {
            name: "Finished files sent for approval the same working day",
            target: "100%",
            why: "Same-day review is how production becomes an asset the Digital Marketing Manager can approve, publish, reuse, and report.",
            how: "Count files the file list marked finished that calendar day. A file counts when it is named and sitting in the review path the Digital Marketing Manager named."
          },
          {
            name: "Files on the signed kit",
            target: "100%",
            why: "On-kit files keep the signed promise so Sales inherits one company.",
            how: "The Digital Marketing Manager spot-checks approved library files against the current kit. A file counts when its claims, prices, towns, faces, and look match the kit, including AI output."
          },
          {
            name: "Customer and provider files edited from Operations footage",
            target: "100% of customer films and job films",
            why: "Those videos are captured on the job. This seat's result is a kit-true edit the Digital Marketing Manager can approve.",
            how: "Every library row of type customer or jobfilm names Operations as the source and shows the date the Digital Marketing Manager approved the finished edit."
          },
          {
            name: "File list versus delivered",
            target: "Tracked every week; missed lines named with a reason",
            why: "The Digital Marketing Manager plans ads and social from what was approved: which types are ready, which Operations files are late, and which lines this seat still owes.",
            how: "Weekly production pack: each file-list line is sent, approved, blocked, or missed. Blocked stays with Operations when footage is waiting. Missed stays with this seat."
          },
          {
            name: "Same-day notice to the Digital Marketing Manager",
            target: "100% of days with finished or blocked work",
            why: "The note is how approval, publishing, weekly reporting, and next week's file list stay on time.",
            how: "A same-day file note exists for every working day on which a file was finished, approved, or a line was blocked."
          }
        ],
        escalations: [
          {
            title: "The Digital Marketing Manager sends a file back, or has not approved it the same day",
            lead: "A finished file is sitting in review. The Digital Marketing Manager said no, or has not written a yes yet.",
            art: "cmc-h-note.png",
            body: [
              "If the Digital Marketing Manager wrote a no, remake from that note. Keep the old file out of the library. Send the new version the same working day if you can.",
              "If the Digital Marketing Manager has not answered, keep the named file in review. Age the wait on the same-day note: which file, since when. Keep other ready lines moving."
            ],
            points: [
              { title: "You receive", why: "A written no, or a file still waiting for a yes." },
              { title: "You do", why: "Remake from the note, or age the wait. Do not put the file in the library yourself." },
              { title: "You send to", why: "Digital Marketing Manager, on the same-day note." }
            ]
          },
          {
            title: "The file list needs words, a price, a town, or a face that is not in the kit",
            lead: "Production would have to invent a promise the Marketing Manager has not signed.",
            art: "cmc-e-stop.png",
            body: [
              "Stop that line. Write what the file list asked for, what the kit currently allows, and why they do not match. Send that to the Digital Marketing Manager. The Digital Marketing Manager asks the Marketing Manager. If it is a new service or a new town, Marketing Manager takes it to Head of Growth.",
              "Keep other on-kit lines moving while you wait."
            ],
            points: [
              { title: "You receive", why: "A file-list line the current kit cannot support." },
              { title: "You do", why: "Stop that line. Write the gap. Keep other on-kit lines moving." },
              { title: "You send to", why: "Digital Marketing Manager." }
            ],
            meta: [
              ["Resume when", "The kit is updated in writing, or the file-list line is changed"]
            ]
          },
          {
            title: "Operations footage is missing for so long that the week's file list is blocked",
            lead: "Customer films or provider films have sat on the blocked list because Operations has not sent the raw files.",
            art: "cmc-p-weekly.png",
            body: [
              "Put the age of the block on the weekly production pack: how many working days, which service and town, which file is missing. The Digital Marketing Manager escalates to Operations, and to the Marketing Manager if the files still do not arrive.",
              "Meanwhile, fill the week with AI posts, AI video, and brand films that the kit already allows, so the Digital Marketing Manager still has files in the library."
            ],
            points: [
              { title: "You receive", why: "A block that has sat beyond the week." },
              { title: "You do", why: "Age it on the weekly pack. Produce on-kit work that is ready." },
              { title: "You send to", why: "Digital Marketing Manager, on the weekly pack." }
            ]
          },
          {
            title: "A file is corrupt, the library path is down, or a tool fails",
            lead: "Production cannot send a file for approval, or cannot open the editor, or the only copy is at risk.",
            art: "cmc-r-produce.png",
            body: [
              "Write what failed (export, disk, login, library permission), what copies you still have, and which file-list lines are at risk. Send that to the Digital Marketing Manager the same day. If the only copy is on a machine that is failing, say so immediately — this is a same-day escalation.",
              "Ask the Digital Marketing Manager for the approved backup path, then follow it."
            ],
            points: [
              { title: "You receive", why: "A broken export, a full disk, a library you cannot write to, or a lost login." },
              { title: "You do", why: "Protect the copy you have. Write the failure. Ask for a path." },
              { title: "You send to", why: "Digital Marketing Manager the same day. They pull Technology if access is the issue." }
            ]
          }
        ]
      }
    },
    fmm: {
      story: "Kashmir still buys from people they can see. Field work is a system: named town, named day, approved materials, tagged enquiries, same-day handoff to Sales. The stall is a door, not a shop. You do not take money or bookings on the spot.",
      receives: [
        { from: "Marketing Manager", what: "Field calendar, towns, materials, budget." },
        { from: "Expansion", what: "Which towns are actually open to serve." }
      ],
      gives: [
        { to: "Sales", what: "A complete field list the same day: name, phone, service, town, source = field." },
        { to: "Marketing Manager", what: "Enquiries per day, dead towns, material needs." },
        { to: "Intelligence", what: "What people kept asking for that we do not sell — as a signal, not as a new offer." }
      ],
      records: ["Field calendar", "Stall log", "Material stock", "Same-day lead lists", "Town performance"],
      good: ["Every field enquiry tagged the same day.", "No stall in a town that is not on the plan.", "No cash, no booking, no job promise on the spot.", "Materials match the current approved offer."],
      bad: ["A random Saturday stall ‘because we were nearby’.", "Taking a booking to look busy.", "A flyer with last year’s price.", "Standing in a town with no providers."]
    },
    mim: {
      story: "Without Intelligence, Growth guesses. Guessing is expensive in small Kashmir towns. You keep one true picture of demand, competitors, and chances. You close opportunities as go, no-go, or more research with a date. You never leave a row as ‘interesting’. You discover. You do not run ads, write services, or enter towns.",
      receives: [
        { from: "CRM / Sales", what: "Enquiry and booking extracts by service and area. Lost-lead reasons." },
        { from: "Field and CX", what: "Dated notes: what people asked, what failed." },
        { from: "Public listings", what: "Competitor prices, offers, new towns — with source and date." }
      ],
      gives: [
        { to: "Offer", what: "A validated service gap with evidence." },
        { to: "Expansion", what: "A validated named area with evidence." },
        { to: "Partnerships", what: "A validated channel type with evidence." },
        { to: "Head of Growth", what: "Weekly report and same-day alert if a competitor can steal bookings this week." }
      ],
      records: ["Demand trackers", "Competitor file", "Change log", "Opportunity register", "Validation reports", "Weekly MI report", "Monthly decision pack"],
      good: ["No fact without source and date.", "High opportunities decided in 10 working days.", "Never closed as ‘interesting’.", "Weekly report built from registers, not from chat."],
      bad: ["A rumour treated as a competitor move.", "Briefing Marketing to advertise a guess.", "A parking-lot of High ideas with no date.", "Mixing competitor prices into our demand counts."]
    },
    osd: {
      story: "Marketing cannot invent the product. Operations cannot guess the product. This seat writes what Panun Kaergar can sell and deliver: name, in, out, job steps, quality bar. Finance prices. Operations says if they can do it. Head of Growth says Marketing may talk. A name without a sheet is not a service.",
      receives: [
        { from: "Intelligence", what: "Validated service needs and unmet-demand patterns." },
        { from: "Sales and CX", what: "Requests we cannot fulfil, complaint patterns that look like a missing or wrong service." },
        { from: "Operations", what: "Whether we can actually do the job, with which providers and tools." },
        { from: "Finance", what: "Price options and commission impact." }
      ],
      gives: [
        { to: "Head of Growth", what: "A complete sheet plus launch checklist, ready for yes or no." },
        { to: "Marketing", what: "The only claims they may use after yes." },
        { to: "Sales and Operations", what: "The live catalogue: what is in, what is out." }
      ],
      records: ["Service catalogue", "Service sheets", "Opportunity register", "Pricing recommendation", "Launch checklist", "Performance tracker", "Retirement file"],
      good: ["Every live service has a full sheet.", "Launch checklist 100% before Marketing talks.", "New service validation in 15 working days.", "Inclusions and exclusions written, not implied."],
      bad: ["Sales picking from a messy menu of ghost names.", "Marketing selling a draft.", "A dead service still in ads.", "You setting commission yourself."]
    },
    mem: {
      story: "A new town with ads and no providers is how brands die in public. You decide where we operate next: enter, wait, or no — in writing. Intelligence names a place. You test it. Provider Operations must be ready. Head of Growth says yes. Then Marketing may spend. A pin on a map is not a launch.",
      receives: [
        { from: "Intelligence", what: "A named area with evidence." },
        { from: "Provider Operations", what: "Whether we can finish the first jobs there." },
        { from: "Sales", what: "Demand from towns we do not cover." }
      ],
      gives: [
        { to: "Head of Growth", what: "Enter / wait / no, then a launch plan with a review date." },
        { to: "Marketing and Partnerships", what: "The towns they may spend in, and when." },
        { to: "Sales and Operations", what: "Launch week, first services, pause or leave if you call it." }
      ],
      records: ["Area pipeline", "Feasibility pack", "Launch plan", "Launch checklist", "Post-launch review", "Pause / exit file"],
      good: ["No marketing spend in a town without yes and provider readiness.", "Feasibility in 15 working days.", "Every launch has a review date.", "Exit and pause are written."],
      bad: ["Keeping a launch date when nobody can staff the town.", "Staying forever in a dead town.", "Marketing spend before Operations is ready.", "Research with no decision."]
    },
    pcm: {
      story: "We cannot stand on every street. Good partners — hotel desks, shops, local businesses — bring work we would miss. Bad partners bring fights and unpaid promises. You own the pipe: find, qualify, written terms, onboard, tagged leads to Sales. You never skip Sales. You never hand a lead to a provider.",
      receives: [
        { from: "Intelligence", what: "Validated channel types worth trying." },
        { from: "Head of Growth", what: "Yes on partner type and commercial shape." },
        { from: "Partners", what: "Enquiries from their customers." }
      ],
      gives: [
        { to: "Sales", what: "Tagged partner leads the same day: partner name, service, town." },
        { to: "Head of Growth", what: "Keep / fix / close each month, with numbers." },
        { to: "Finance", what: "Anything that needs money we did not plan — as an ask, not a promise." }
      ],
      records: ["Partner pipeline", "Term sheets", "Partner file", "Lead tracker", "Monthly review", "Closure file"],
      good: ["100% of partner leads tagged with partner name.", "No live partner without a written term sheet.", "Leads to Sales the same day.", "Monthly review of every live partner."],
      bad: ["Handshake-only deals.", "Cash side deals.", "Converting the partner’s customer yourself.", "A partner selling a job we do not do."]
    }
  };

  Object.keys(extra).forEach(function (id) {
    if (roles[id]) Object.assign(roles[id], extra[id]);
  });

  const dutyHow = {
    hog: [
      "Sit with Intelligence. Write one customer picture and one promise sheet. Marketing may not change it without you.",
      "Last week of the month, sit with Marketing. Sign who, offer, town, channel, budget, and target. File it.",
      "Read the pack. Check Operations can finish the jobs. Write yes, no, or send back with a date.",
      "Every week, open the same sheet: enquiries, qualified, bookings, cost. One action per channel that is off plan.",
      "Weekly 1:1 against their one result. If you act in their box, write ‘acting owner’."
    ],
    mkm: [
      "Pull Intelligence and last month. Draft the calendar. Head of Growth signs before any spend.",
      "Check every creative against the promise sheet before it goes live. Store the final file.",
      "Split budget and weekly targets. Digital and Field work from the same calendar — not two plans.",
      "Same day into Sales: source, service, town. Chase an untagged lead the same day.",
      "Same weekday every week: spend, enquiries, cost, bookings. Numbers, not a story."
    ],
    hom: [
      "Only what is on the signed calendar. Test a dummy lead into Sales before you spend. If tracking dies, pause the same day.",
      "Same promise as the ads. Count organic on its own line. Do not book in a comment or a thread.",
      "Every file: name, type, service, town, date, where it went live. Content Maker makes and edits. You approve. Only then does it enter the library.",
      "Write the need. Collect the files from Operations. Content Maker edits from the kit and sends the cut to you for approval.",
      "Source = digital + pipe + campaign or post. Service and town if you have them. Do not sell.",
      "Weekly: 3 things that worked, 3 that failed, 1 ask. By pipe, not as one lump called digital."
    ],
    cmc: [
      "Use the signed words and look. Stop if you need a new claim.",
      "Name it. Send it to the Digital Marketing Manager for approval. File it in the library only after the Digital Marketing Manager says yes.",
      "Take the files from Operations. Edit to the kit. Name them and send them to the Digital Marketing Manager for approval the same working day."
    ],
    fmm: [
      "Confirm pitch and materials the day before. Be in the named town on the named day.",
      "Write name, phone, service, town. Say Sales will call. Do not book and do not take money.",
      "Only the current approved pack. Count stock when you pack up.",
      "Write the stall log before you leave town. Dead towns get named, not hidden."
    ],
    mim: [
      "Pull CRM extracts. Count by service and area. Chat is not a tracker.",
      "Named list only. Log what actually changed, with source and date.",
      "One row per idea. Close as go, no-go, or more research with a date — never ‘interesting’.",
      "Go packs to Offer, Expansion, or Partnerships. Do not brief Marketing to advertise a guess."
    ],
    osd: [
      "One live list. Ghost names go to retired — they are not deleted.",
      "Write in, out, job steps, and the quality bar. Send to Finance and Operations.",
      "Launch checklist 100% before Head of Growth says yes.",
      "Watch demand, complaints, and cancellations. Recommend keep, fix, or stop in writing."
    ],
    mem: [
      "Named towns with evidence. A mood is not a queue.",
      "Demand, competition, capacity, rough cost. Write enter, wait, or no.",
      "First services, spend list, review date. Head of Growth signs before Marketing spends.",
      "Bookings vs finished jobs. Scale, fix, pause, or leave — in writing."
    ],
    pcm: [
      "Name real types (hotel desk, shop). Random uncles are not a channel.",
      "Who they serve, reputation, fit / wait / no. A loud partner with bad jobs hurts the brand.",
      "Written terms, how a lead is sent, what we pay. Test one lead into Sales before calling them live.",
      "Leads, bookings, silent partners. Monthly keep / fix / close."
    ]
  };
  Object.keys(dutyHow).forEach(function (id) {
    (roles[id].responsibilities || []).forEach(function (item, i) {
      if (dutyHow[id][i]) item.how = dutyHow[id][i];
    });
  });

  const kpiWhy = {
    mkm: [
      "No plan means Digital and Field invent the month.",
      "Untagged leads cannot be improved.",
      "Variance without a signature is technician spend.",
      "Cheap noise is still noise.",
      "Head of Growth needs numbers, not stories."
    ],
    hom: [
      "Spend with no path to Sales is waste.",
      "Sales cannot work a nameless lead.",
      "The plan is the boss.",
      "If cost blows, pause.",
      "A phone album is not a system.",
      "Wins-only reports hide bad spend.",
      "No surprise videos."
    ],
    cmc: [
      "The library holds only files the Digital Marketing Manager has approved.",
      "Off-kit files invent a second company.",
      "The Digital Marketing Manager must know what is missing."
    ],
    fmm: [
      "A missed field day is a missed town.",
      "If it is not tagged the same day, it did not happen.",
      "Dead towns must be named so the plan can change."
    ],
    mim: [
      "The company cannot wait for a mood.",
      "A rumour is not Intelligence.",
      "A parking lot of ideas is not a decision."
    ],
    osd: [
      "A name without a sheet is not a service.",
      "Selling a job we cannot finish creates complaints.",
      "Delay without a date is hiding."
    ],
    mem: [
      "Hope is not a plan.",
      "Ads before capacity kill the brand in public.",
      "A launch without a review never ends."
    ],
    pcm: [
      "Untagged partner leads cannot be scored.",
      "Handshake deals become fights.",
      "A silent partner is a closed partner — write it down."
    ]
  };
  Object.keys(kpiWhy).forEach(function (id) {
    (roles[id].kpis || []).forEach(function (item, i) {
      if (kpiWhy[id][i] && !item.why) item.why = kpiWhy[id][i];
    });
  });

  workflows.growth.story = "E-Myth says the technician does the work, the manager runs the system, the entrepreneur sees the whole. Growth here is the system that makes something the market can buy, then creates tagged enquiries. Sales books. Operations finishes. If those three mix, nobody owns a result.";
  workflows.growth.calendar = [
    { when: "Every working day", what: "Digital, Field and Partnerships hand tagged enquiries to Sales. Tracking and spend are checked. Nothing sits in a personal chat." },
    { when: "Same weekday every week", what: "Growth review: plan vs actual, cost per enquiry, cost per booking, lost-lead reasons. One action per channel that is off plan." },
    { when: "Last week of the month", what: "Head of Growth signs next month’s plan with Marketing, using Intelligence. Offer and Expansion only put approved items on it." }
  ];
  workflows.growth.boundaries = [
    { who: "Growth", does: "Creates the offer the market can buy, and the tagged enquiry." },
    { who: "Sales / CX", does: "Converts the enquiry into a booking, or writes why it was lost." },
    { who: "Operations", does: "Assigns and finishes the job. Does not generate demand." },
    { who: "Finance", does: "Prices and pays. Does not invent offers." }
  ];
  (workflows.growth.paths || []).forEach(function (path) {
    if (path.id === "service") {
      path.when = "People keep asking for something we do not sell well, or a live service is failing.";
      path.input = "A repeated unmet request, a complaint pattern, or an Intelligence go on a service gap.";
      path.output = "An approved service sheet, a price, operations ready, and Marketing allowed to talk.";
      path.fail = "Do not advertise a draft. Do not launch if Operations cannot do the first jobs.";
    }
    if (path.id === "area") {
      path.when = "We want to work in a town we do not cover yet.";
      path.input = "A named town with evidence from Intelligence.";
      path.output = "Enter, wait, or no — then a launch with a review date, or a written pause/exit.";
      path.fail = "Do not spend on ads in a town with no providers. A pin on a map is not a launch.";
    }
    if (path.id === "engine") {
      path.when = "Every week, for services and towns we already sell.";
      path.input = "A signed monthly plan: who, offer, town, channel, budget, KPI.";
      path.output = "Tagged digital, field and partner enquiries in Sales the same day, plus lost-lead reasons back to Growth.";
      path.fail = "Do not convert the enquiry in Marketing. Do not run off-plan campaigns. Do not skip source tags.";
    }
  });

  return {
    roleIds: Object.keys(roles),
    roles: roles,
    workflows: workflows,
    workflowTabs: [
      { id: "growth", name: "Growth workflow" },
      { id: "operations", name: "Operations workflow", soon: true },
      { id: "control", name: "Control workflow", soon: true }
    ]
  };
})();

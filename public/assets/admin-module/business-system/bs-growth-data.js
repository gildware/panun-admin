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
      layout: "detailed",
      result: "People who found us through Growth reach Sales as a named enquiry, at a cost the CEO signed, for work Operations can actually finish.",
      what: "You own who Panun Kaergar sells to, what we may promise, and the numbers that prove Growth is working. Five people report to you: Marketing Manager, Market Intelligence Manager, Offer & Service Development Manager, Market Expansion Manager, and Partnerships & Channels Manager. You write the monthly Growth plan they work from, and you say yes or no on a new service, a new town, and a new kind of partner. They do the daily work. You run the plan they work from.",
      why: "If nobody owns who we sell to and what we may say, Marketing, Intelligence, Offer, Expansion, and Partnerships start acting like five companies. Money is spent with no owner. The CEO cannot tell what is working.",
      how: "The CEO tells you the company’s priorities and the money ceiling. You turn that into one monthly Growth plan. The five people work only from that written plan. You do not run the ads, stand at a stall, write the service sheet, open a town, or sign a hotel desk unless that job is empty — and then you write that you are doing it today because nobody is in that job.",
      lanes: [
        { kicker: "Part 1", art: "lane-who.png", title: "Who we sell to, and what we may say", work: "Write who the customer is. Write the sentences ads, stalls, and hotels may use. Marketing Manager may not invent a different customer or different sentences.", result: "Ads, stalls, and hotels talk about the same company.", who: "You." },
        { kicker: "Part 2", art: "lane-plan.png", title: "The monthly plan, the money, and yes or no", work: "Sign the month. Sign how much money Growth may spend. Sign which services, which towns, ads, stalls, and hotels. Say yes, no, or send back on a new service, a new town, and a new kind of hotel or shop.", result: "Nobody spends Growth money, and nobody opens a new service or town, without your signature.", who: "You. Marketing Manager writes the marketing page of the plan. You sign." },
        { kicker: "Part 3", art: "lane-team.png", title: "The five people who report to you", work: "Check each person hits their one result. Marketing Manager brings people in. Market Intelligence Manager writes facts. Offer & Service Development Manager writes what we sell. Market Expansion Manager opens or waits on a town. Partnerships & Channels Manager signs hotels and shops that send us customers.", result: "Each of those five jobs has a named person — or you have written that you are doing that work today because the job is empty.", who: "Those five people. If a job is empty, you do that work today. Write that down." }
      ],
      acting: "If one of those five jobs has nobody in it, you do that work and write that you are doing it today because nobody is in that job. You do not hide it.",
      owns: [
        "Who we sell to, and why they buy",
        "The promise we are allowed to make",
        "The monthly Growth plan and budget",
        "Yes or no on a new service, a new town, and a new kind of hotel desk or shop",
        "The five Growth people who report to you and their weekly numbers"
      ],
      mustNot: [
        "Run ads or post as the Digital person",
        "Stand at stalls as the Field person",
        "Close customer chats or take bookings",
        "Write the service definition as the technician",
        "Sign partners or hire providers"
      ],
      responsibilities: [
        { title: "Customer and promise", what: "Keep one clear picture of who we serve and what we may say.", why: "Ads, stalls, and partners must not invent a different company." },
        { title: "Plan and money", what: "Approve the monthly plan: who, offer, town, channel, budget, target.", why: "Spend without a plan is guessing instead of following a plan, not Growth." },
        { title: "Yes and no", what: "Sign off new services, town launches, and kinds of hotel desk or shop. Stop what is not working.", why: "Only this job may turn research into market action." },
        { title: "Numbers", what: "Read enquiries, qualified enquiries, bookings, and cost by ads, stalls, or partners every week.", why: "Reach and likes are not a result." },
        { title: "People", what: "Coach the five people who report to you. Do their job only if the job is empty — and write it down as acting owner.", why: "The org chart is the business. Jobs stay. People sit in them. If a job has nobody in it, write who is doing it today." }
      ],
      when: {
        daily: ["Look at yesterday’s enquiries with a written answer for how they found us and spend vs plan.", "Unblock a yes/no that is blocking someone who reports to you.", "Escalate to the CEO only if money, brand, or a launch is at risk."],
        weekly: ["Run the Growth review: plan vs actual, cost per enquiry, cost per booking, lost-lead reasons from Sales.", "Clear High opportunities that have sat more than 10 working days.", "Confirm Marketing is only promoting approved services in approved towns."],
        monthly: ["Set next month’s plan with Marketing, using Intelligence.", "Decide grow / stop / research for each live offer and town.", "Review each of the five people against their one result."]
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
        { id: "HOG-03", title: "Approve a new area", when: "Expansion has a written enter / wait / no pack, and Provider Operations says we can finish.", steps: ["Read demand, competition, and capacity.", "If we cannot finish jobs there, the answer is no — even if ads would be cheap.", "If yes, name the launch services, budget, and review date.", "Marketing and Partnerships may spend in that area only after this yes."] },
        { id: "HOG-04", title: "Weekly Growth review", when: "Same weekday every week.", steps: ["Enquiries, qualified enquiries, bookings, spend — by ads, stalls, or partners.", "Cost per enquiry and cost per booking vs plan.", "Lost-lead reasons from Sales. Hand patterns to Intelligence and Marketing.", "One action each for any channel that is off plan.", "Write the review. Do not keep it in chat."] }
      ],
      kpis: [
        { name: "Monthly Growth plan signed", target: "100% before month starts", why: "No plan means no system." },
        { name: "Enquiries with a written answer for how they found us", target: "Every Growth enquiry has a source", why: "People with no written answer for how they found us cannot be improved." },
        { name: "Cost per enquiry", target: "Inside the signed plan", why: "Cheap noise is still noise." },
        { name: "Cost per booking from Growth", target: "Inside the signed plan", why: "Enquiries that never book are not Growth." },
        { name: "High opportunities decided", target: "Within 10 working days", why: "A list of ideas nobody decided is not a decision." },
        { name: "Weekly review on time", target: "100%", why: "The numbers must be a habit." }
      ],
      rules: [
        "Growth creates the enquiry. Sales books it. Operations does the job. Do not mix these.",
        "You may do one of those five jobs if it has nobody in it — say so in writing.",
        "Do not change price, payout, or catalogue without Offer and Finance.",
        "Do not hire providers. That is Provider Operations.",
        "If a channel cannot name its source, stop spending on it."
      ],
      escalate: [
        { when: "Brand, legal, or a false claim", to: "CEO the same day", how: "Stop the content. Write what went out and where." },
        { when: "Spend will break the monthly budget", to: "CEO before more money goes out", how: "Show plan vs actual and the ask." },
        { when: "One of the five people is missing their one result for two weeks", to: "CEO in the weekly written report", how: "Numbers, what you tried, what you need." },
        { when: "Operations cannot finish what we are selling", to: "Head of Operations first, then CEO if it is not fixed", how: "Pause marketing in that offer or town until work can be done." }
      ]
    }),
    mkm: role({
      id: "mkm",
      name: "Marketing Manager",
      reportsTo: "Head of Growth",
      hero: "role-mkm.png",
      layout: "detailed",
      result: "People who saw an ad, found us on Google, or stopped at a stall should reach Operations. Operations asks each person how they found us and writes the answer. Stay inside the money Head of Growth signed.",
      what: "You run Panun Kaergar’s marketing. That means you write the monthly plan, you write the marketing kit (the instruction book of words, look, prices, towns, and faces), you split the money between paid ads and stalls, and you make sure Operations asked every new person where they found us and wrote the answer. That is how we know the source — not the phone number, and not Digital guessing later. Two people report to you: Digital Marketing Manager (paid ads, SEO / search, social, and videos) and Field Marketing Manager (people who visit the market, first local workers on site, and office and society contracts). They do the daily work. You run the plan they work from.",
      why: "If nobody owns the plan, Digital and Field start acting like two different companies. A customer sees one promise in an ad and a different promise on a flyer. Money is spent with no owner. Head of Growth cannot tell what is working.",
      how: "Head of Growth tells you the company’s priorities for the month. You turn that into one calendar and one marketing kit. Digital and Field work only from that written plan. If they want a new sentence, a new price, a new town, or a new face that is not in this month’s marketing kit, they stop and ask you. You do not invent new services. You do not run the ads yourself. You do not stand at the stall. You do not book the customer.",
      lanes: [
        { kicker: "Part 1", art: "lane-kit.png", title: "The plan and the instruction book", work: "Write the monthly plan: which services, which towns, which days, how much money for paid ads, how much money for stalls, which website pages Digital must keep true so people can find us on Google without a paid ad, and what we will not advertise. Also write the instruction book: logo, colours, sentences, prices, towns, and faces that ads, search pages, and stalls may use.", result: "Digital and Field can do their work without inventing a different company.", who: "You." },
        { kicker: "Part 2", art: "lane-ads.png", title: "Online marketing", work: "Digital Marketing Manager runs paid ads (Meta, Google, and others), unpaid website and Google search, the app store, social media, videos, and posts.", result: "People who saw an ad or found us on Google reach Operations. Operations asks how they found us and writes paid ad or search. Search numbers are not mixed into the ads money.", who: "Digital Marketing Manager. If that job is empty, you do this work today. Write that down." },
        { kicker: "Part 3", art: "lane-stall.png", title: "Field marketing", work: "Field Marketing Manager runs three ground jobs: visits to stalls and streets, first local workers signed in person, and maintenance contracts with offices and housing societies.", result: "Sales has the stall names the same day. The town has first local workers signed onto us and sent to Provider Operations. Offices and societies have a signed maintenance contract, not a spoken yes.", who: "Field Marketing Manager. If that job is empty, you do this work today. Write that down." }
      ],
      acting: "If nobody sits in the Marketing Manager job, Head of Growth does that work and writes that they are doing it today because nobody is in that job. If nobody sits in Digital or Field, you do that work and write on the daily note: you are doing it today because nobody is in that job.",
      owns: [
        "The monthly marketing plan and day-by-day calendar",
        "The marketing kit — logo, colours, sentences, prices, towns, and faces",
        "How the money is split — ads budget and stall budget",
        "A note of where every marketing enquiry came from",
        "The Digital Marketing Manager job",
        "The Field Marketing Manager job"
      ],
      mustNot: [
        "Invent a new service, a new price, or a new town",
        "Turn the enquiry into a booking",
        "Change what Intelligence found, or advertise a guess",
        "Spend more than the signed plan allows",
        "Run the ads or stand at the stall as if that were your main job",
        "Let Operations skip asking where they found us, or let Digital or Field make their own month"
      ],
      responsibilities: [
        { title: "Monthly plan and calendar", what: "Write which services, which towns, which days, how much money for paid ads, how much money for stalls, which website pages SEO must cover, and what we will not advertise. Get Head of Growth to sign before any money is spent or any stall is set up.", why: "Without a signed plan, Digital and Field invent their own month." },
        { title: "Marketing kit", what: "Keep one look and one promise. If Digital or Field wants a new sentence, price, town, or face, they stop until you say yes in writing.", why: "Trust dies when an ad or a flyer promises something the job cannot do." },
        { title: "How the money is split", what: "Give Digital an ads budget for paid ads, and Field a stall budget, with a weekly target for each. SEO (search) is Digital’s work too, but those enquiries are counted on their own line — not mixed into ads spend.", why: "If money has no owner, each side takes from the other. If search hides inside ads, you cannot tell what is working." },
        { title: "Where each enquiry came from", what: "Operations asks every new person — call, WhatsApp, app, website form, or any other way they arrived — ‘How did you find us?’ and writes the answer from a short list you gave them: paid ad, search, stall, WhatsApp, app, form, or other. You check every enquiry has that answer the same day. If it is blank, Operations asks today. Digital does not ask the customer, and Digital cannot see this from the phone number. Digital reads what Operations wrote, so we know which ads and pages work. Field also writes town and date when they met the person at a stall.", why: "If Operations does not ask, nobody knows which ads, search pages, or stalls work." },
        { title: "Digital and Field jobs", what: "Give both managers their written work. Check they hit their one result. If nobody sits in a job, do that work yourself and write that you are doing it today because nobody is in that job.", why: "A job with no name has no owner." },
        { title: "Honest weekly numbers", what: "Each week write spend, enquiries by paid ads, by search (SEO), and by stalls, cost, bookings from marketing, three things that worked, three that failed, and one or two changes.", why: "Head of Growth needs numbers, not a story with only good news." }
      ],
      when: {
        daily: ["Check money spent against the signed plan — ads budget and stall budget shown separately.", "Check that Operations asked every new person where they found us and wrote the answer. Keep a list of any that do not, and close that list the same day.", "Unblock Digital or Field if they are waiting on a yes, a town, or a file."],
        weekly: ["Read Digital’s weekly numbers and Field’s weekly numbers. Write the weekly marketing report.", "Pause what is wasting money. Put more effort on what is working — without changing the promise.", "Name a town that produced no enquiries after three visits, a paid ad that produced none for two weeks, or a search page that produced none."],
        monthly: ["Build next month’s plan from Intelligence and last month’s numbers.", "Get Head of Growth to sign it before any spend starts.", "Give Digital and Field their written work the same day. Reset the calendar and both budgets."]
      },
      standards: [
        "Head of Growth has signed the plan before the month starts.",
        "95% or more of marketing enquiries have an answer Operations wrote: where the person found us.",
        "No live ad or flyer uses a sentence you have not put in the marketing kit.",
        "Money spent stays within 10% of the plan unless Head of Growth signs a change.",
        "Digital and Field work from the same calendar.",
        "If Digital or Field has nobody sitting in the job, that is written down as you doing that job."
      ],
      procedures: [
        { id: "MKT-01", title: "Write next month’s marketing plan", when: "In the last week of the month, before any money is spent in the new month.", steps: ["Read what Intelligence found, and last month’s marketing report.", "Choose only services Head of Growth has already approved, and only towns Expansion has already opened.", "Write how much money paid ads may spend, how much stalls may spend, and which website pages Digital must keep true for search (SEO).", "Write what ‘good’ looks like for ads, for search, and for stalls.", "Write or confirm the marketing kit for the month.", "Write a list of what we will not advertise this month.", "Get Head of Growth to sign.", "Give Digital and Field their written work the same day (see MKT-05)."] },
        { id: "MKT-02", title: "Allow or refuse a new sentence, price, town, or face", when: "When Digital or Field wants to say something that is not already in the marketing kit.", steps: ["Tell them to stop. That ad or flyer must not go live yet.", "Ask them to write what they want to say and why.", "Check Offer, Finance, and Expansion: is this already approved?", "If it is a brand-new service or town, take it to Head of Growth.", "If the answer is yes, add it to the marketing kit, put the date on it, and tell both managers.", "If the answer is no, write the no. They keep doing the other work that is already allowed."] },
        { id: "MKT-03", title: "Make sure Operations asked where each person found us", when: "Every working day.", steps: ["Give Operations a short written list of answers they may write: paid ad, search, stall, WhatsApp, app, website form, or other.", "Every new person — call, WhatsApp, app, form, or any other way — Operations asks: ‘How did you find us?’ and writes one of those names.", "You check new enquiries the same day. If the answer is missing, Operations asks today.", "If they still cannot write it, put that person on your missing-source list. The goal is an empty list.", "Digital reads these answers to count ads vs search. Digital does not ask the customer.", "If Field met the person at a stall, Field also writes town and date."] },
        { id: "MKT-04", title: "Write the weekly marketing report", when: "The same weekday every week, the day before Head of Growth’s Growth review.", steps: ["Take Digital’s weekly numbers and Field’s weekly numbers the day before.", "Write money spent against the plan, ads and stalls separately.", "Write how many people enquired, by paid ads, by search (SEO), and by stalls. Do not mix search into ads.", "Write how many of those enquiries named their source. Write how many became bookings, if Sales has closed them.", "Write three things that worked, three that failed, and one or two changes — not ten.", "Send it to Head of Growth in writing. Not a voice note."] },
        { id: "MKT-05", title: "Give Digital and Field their written work for the month", when: "The same day Head of Growth signs the month, and whenever the plan changes.", steps: ["Give Digital: their days, the ads budget, the marketing kit, which website pages and towns SEO must cover this month, where a call, WhatsApp, app, or form must land, and what we will not advertise.", "Give Field: which towns, which days, which boards and flyers, the stall budget, which first-worker towns, which offices and societies, the signed papers, and what we will not advertise.", "Give Operations the list of source names they may write this month: paid ad, search, stall, WhatsApp, app, form, or other. They ask every person. They write one of those names.", "Save this brief as a file. A chat message is not the brief."] },
        { id: "MKT-06", title: "Pause an ad campaign or stall days", when: "When enquiries have no source, money is running too fast, or a sentence might be untrue.", steps: ["Stop that ad or those stall days the same day.", "Write why, the time, and what is still allowed to run.", "Tell Digital or Field, and tell Sales if enquiries will stop arriving.", "If money will go past the plan, or a sentence might be untrue, tell Head of Growth before more money goes out or the file stays live."] },
        { id: "MKT-07", title: "Change the plan in the middle of the month", when: "When the calendar must change before the month ends.", steps: ["Write the change: which line, why, money added or taken away.", "Do not let Digital or Field start the new line yet.", "Get Head of Growth to sign.", "Then give both managers the new written brief (see MKT-05). Save the new plan."] }
      ],
      kpis: [
        { name: "Monthly plan signed before spend starts", target: "100% of months" },
        { name: "Marketing enquiries that name where they came from", target: "95% or more" },
        { name: "Money spent vs the signed plan", target: "Within 10%" },
        { name: "Cost of each enquiry", target: "Inside the number on the signed plan" },
        { name: "Weekly report that names what failed, not only what worked", target: "100% on time" },
        { name: "Days when Digital or Field was empty, written as you doing that work", target: "100%" }
      ],
      rules: [
        "Only talk about services Head of Growth has approved, in towns Expansion has opened.",
        "Digital and Field do not invent their own offers, their own marketing kit, or their own month.",
        "You write the marketing kit. Digital checks each video, post, and search page against it, then publishes. You do not re-check every post.",
        "Operations asks every new person where they found us — call, WhatsApp, app, form, or any other way — and writes the answer. Digital does not ask the customer.",
        "Enquiries from partners belong to Partnerships, then Sales. You do not book those people.",
        "If Digital or Field has nobody sitting in the job, say so in writing and do that job."
      ],
      escalate: [
        { when: "An ad or flyer might be untrue or off brand", to: "Head of Growth before it stays live", how: "Take it down. Show the file." },
        { when: "Spend will go past the plan", to: "Head of Growth before more money goes out", how: "Show the plan, what has been spent, and why you need a change." },
        { when: "Sales says marketing enquiries are poor quality for two weeks in a row", to: "Head of Growth", how: "Show sample enquiries, where they came from, and what you will change." },
        { when: "Digital or Field has missed their result for two weeks, or the job is empty and nobody wrote that down", to: "Head of Growth in the weekly report", how: "Numbers, what you tried, and whether you are doing that job today." }
      ]
    }),
    hom: role({
      id: "hom",
      name: "Digital Marketing Manager",
      reportsTo: "Marketing Manager",
      hero: "role-hom.png",
      layout: "detailed",
      result: "All online work of Panun Kaergar follows the signed plan. Every digital enquiry that reaches Sales has a written answer from Operations for how they found us.",
      what: "You own everything online: paid ads (Meta, Google, and any other paid ads), website search, app store, social, Reddit, Quora, and other sites. You also own the content library — videos and posts — and you give Content Maker their written work and check that result. You do not invent the offer, the town, or the price. You do not book the customer.",
      why: "If ads, posts, and search each invent their own company, the customer hears three promises and Sales cannot learn. One job must own all digital, and send the truth back: what worked and what failed.",
      how: "Marketing Manager gives you the month: towns, services, ads money, words you may use, words you must not use, and where a lead must land. You do three kinds of online work — paid ads, unpaid website and search work, and content. You publish from this month’s marketing kit (the instruction book of words, prices, towns, and faces). A new claim, new price, new town, or new face goes back to Marketing Manager first.",
      lanes: [
        { kicker: "Part 1", art: "lane-ads.png", title: "Paid ads", work: "Run the paid ads on this month’s plan — Meta, Google, and any other paid ads. Stay inside the ads money.", result: "People reach Operations from those ads. Operations wrote how they found us. Spend is inside the ads money.", who: "You. There is no extra ads person." },
        { kicker: "Part 2", art: "lane-search.png", title: "Website, Google search, and unpaid posts", work: "Keep the website and app store true. Post on social, Reddit, Quora, and other sites without paying. Count this work on its own line. Do not mix it into ads money.", result: "People reach Operations from the website or a post. Operations wrote how they found us. These numbers sit on their own line.", who: "You." },
        { kicker: "Part 3", art: "lane-content.png", title: "Videos and posts", work: "Content Maker makes the videos and still posts. You say yes or no. Then you file the file and hand it to ads and social. If it is not filed, it did not happen.", result: "The company folder holds only files you approved, with a date.", who: "Content Maker makes the file. You say yes. If Content Maker is empty, you do that work today. Write that down." }
      ],
      given: [
        { title: "Towns that are open this month", why: "Do not advertise a town we cannot serve." },
        { title: "Services we may talk about", why: "A draft is not a product." },
        { title: "Ads budget and a cost ceiling", why: "The signed plan is the rule, not the ad account." },
        { title: "Words we may say, and words we must not say", why: "This is this month’s marketing kit. You may publish from it without asking again." },
        { title: "Brand look — logo, colours, tone", why: "One look. Not a new look every post." },
        { title: "Where the lead must land (form, WhatsApp, or app that Sales already uses)", why: "A lead that never reaches Sales is wasted spend." },
        { title: "What we will not do this month", why: "No ‘let’s just test it’ off the plan." }
      ],
      sentBack: [
        { title: "An enquiry the same day, where Operations wrote how they found us", why: "Sales books. You do not." },
        { title: "Daily note: spend, new leads, anything you paused", why: "Catch a leak the same day." },
        { title: "Weekly written report by named online place (Meta, Google, search, social, other)", why: "Show spend, enquiries, cost — and 3 things that worked plus 3 that failed." },
        { title: "Content log: what went live, where, which service and town", why: "The library is how we learn, not a folder that lives only on a personal phone." },
        { title: "One written request for next week", why: "More money, a new video, or pause a town — not ten wishes." },
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
          { from: "Digital / Content Maker", what: "AI video and AI posts", rule: "Only from this month’s marketing kit. A new claim, price, town, or face needs Marketing Manager first." }
        ],
        toTitle: "Where files go",
        to: [
          { to: "Paid ads", what: "Meta, Google, other paid", rule: "Operations writes how they found us, same as every other digital enquiry." },
          { to: "Social", what: "Upload and schedule", rule: "From the library, not from a personal phone." },
          { to: "Website / app store", what: "Search pages and store listing", rule: "Same promise as the ads." },
          { to: "Reddit, Quora, other", what: "Answers and posts", rule: "Same promise. Do not book in the thread. Hand the person to Sales." },
          { to: "Sales", what: "The person. Operations already asked where they found us.", rule: "Same day. You do not book." }
        ],
        note: "New claim, new price, new town, or new face: stop and ask Marketing Manager. Everything else in this month’s marketing kit: publish, file, report."
      },
      acting: "If nobody is in this job, Marketing Manager does this work and must write that they are doing it today because nobody is in that job. If Content Maker is empty, you do the production work yourself and write on the daily note that you are doing it today because nobody is in that job.",
      owns: [
        "Paid ads — Meta, Google, and any other paid ads on the plan",
        "Unpaid online work — website search, app store, unpaid social, Reddit, Quora, other sites",
        "The company library — files this job has approved",
        "The Content Maker job — file list, marketing kit, review path, and writing it down if you are doing it today because nobody is in that job",
        "Answers Operations wrote — how many people said they found us from your ads or search pages",
        "Daily and weekly written reports to Marketing Manager"
      ],
      mustNot: [
        "Invent a service, a price, or a new town",
        "Publish a new claim that is not in this month’s marketing kit",
        "Publish a customer or provider video that did not come from Operations through the library",
        "Book the customer or keep talking to the customer in a comment or chat yourself",
        "Run ads with no tracking",
        "Skip Marketing Manager and go to Head of Growth for a new town or service"
      ],
      responsibilities: [
        { title: "Paid ads", what: "Build, watch, and pause Meta, Google, and other paid ads that are on the plan.", why: "A live ad with no owner is a leak.", how: "Only what is on the signed calendar. Test a dummy enquiry so Operations actually receives it before you spend. If tracking dies, pause the same day." },
        { title: "Website, search, and unpaid posts", what: "Keep the website and app store true to this month’s marketing kit. Post and answer on social, Reddit, and Quora using the same words.", why: "Search and other sites are not ‘free ads’. They still need a name, Operations writing how they found us, and a path that reaches Sales.", how: "Same promise as the ads. Count them on their own line in the weekly written report. Do not book in a comment or a thread." },
        { title: "Content library", what: "Approve Content Maker files against this month’s marketing kit, then file them. Hand the right file to ads, social, and search.", why: "If a file lives only on a personal phone, it is not the company copy.", how: "Content Maker makes and edits. You approve each file. Only then does it enter the library. Every library file: name, type, service, town, date, where it went live, result." },
        { title: "Operations footage", what: "Ask Operations for customer-feedback and provider videos the file list needs. Hand the files to Content Maker to edit.", why: "Those videos are captured on the job. Content Maker cuts them.", how: "Write the need. Collect the files from Operations. Content Maker edits from this month’s marketing kit and files. Then ads and social may use them." },
        { title: "People reach Operations. Operations asks where they found us", what: "You do not ask the customer. Operations asks — call, WhatsApp, app, form, or any other way — and writes the answer. You read those answers to see which ads and pages work.", why: "Digital cannot see the source from the phone number.", how: "Tell Operations when an ad or page is live. Each week count how many people Operations marked as paid ad vs search. Do not start the sales chat yourself." },
        { title: "Honest results", what: "Send back what worked and what failed.", why: "A report with only wins is how bad spend hides.", how: "Weekly: 3 things that worked, 3 that failed, one written request. By named online place (Meta, Google, search, social, or other), not as one lump called ‘digital’." }
      ],
      when: {
        daily: ["Check spend vs the ads line.", "Read how many new people Operations marked as coming from your ads or search pages.", "Fix broken tracking the same day.", "Approve Content Maker files waiting, so they can enter the library.", "File anything that went live today.", "Write the daily note to Marketing Manager if spend jumped or you paused something."],
        weekly: ["Write the weekly written report by named online place (Meta, Google, search, social, other): spend, enquiries, cost, 3 wins, 3 fails, one written request.", "Check the library holds only files you approved.", "Coach the Content Maker against their one result — or write that you are doing it today because nobody is in that job.", "Send ‘people keep asking for X’ to Intelligence, not as a new ad."],
        monthly: ["Help Marketing Manager write next month’s digital slice.", "Archive dead ads and dead posts.", "List films you still need (jobs, customers, founder) so Operations can send footage and the founder can plan time."]
      },
      standards: [
        "No paid ad live until a dummy enquiry has reached Operations.",
        "Spend stays inside the ads line of the plan.",
        "95% or more of digital leads have a source.",
        "Every live file is in the library the same day.",
        "Customer and provider films in the library are edited from Operations footage.",
        "New claim, price, town, or face waits for Marketing Manager.",
        "Sales chats in comments go to Sales — you do not close them."
      ],
      procedures: [
        { id: "DIG-01", title: "Start a paid ad", when: "When it is on the signed calendar.", steps: ["Check town, service, words, and budget against this month’s marketing kit.", "Pick an approved file from the library — or ask Content Maker to make it from this month’s marketing kit, then approve it before it is filed.", "Send one dummy enquiry so Operations actually receives it. If it does not arrive, do not launch.", "Tell Operations the ad is live. They ask every person where they found us. You do not.", "Launch only after that check.", "File the launch record."] },
        { id: "DIG-02", title: "Watch paid ads each day", when: "Every working day.", steps: ["Spend, enquiries, cost.", "If spend is running away, pause and tell Marketing Manager.", "If spend is on and enquiries are zero, pause and check tracking and the file.", "Write what you did."] },
        { id: "DIG-03", title: "Publish from this month’s marketing kit", when: "Any post, AI video, search page, Reddit or Quora answer that uses only the signed words.", steps: ["Use an approved file from the library, or ask Content Maker to make one from this month’s marketing kit and approve it first.", "No new claim, price, town, or face.", "Publish.", "File: where it went live, service, town, date.", "If someone starts a sales chat, hand it to Sales."] },
        { id: "DIG-04", title: "Ask before a new claim", when: "When you need words, a price, a town, or a face that is not in this month’s marketing kit.", steps: ["Stop. Do not publish.", "Write what you want to say and why.", "Send to Marketing Manager.", "Wait for a yes. If it is a new service or town, they take it to Head of Growth.", "Only then make the file and file it in the kit."] },
        { id: "DIG-05", title: "Take in Operations footage", when: "When ads or social need a customer-feedback or provider video.", steps: ["Write the file-list need: type, service, town.", "Ask Operations for the raw file.", "Hand the files to Content Maker.", "Content Maker edits from this month’s marketing kit and sends the cut to you.", "Approve it. Then it enters the library. Then ads and social may use it."] },
        { id: "DIG-06", title: "Hand a digital lead to Sales", when: "As soon as it arrives.", steps: ["Operations already asked how they found us and wrote the answer. You copy that answer: paid ad or search, plus Facebook, Google, or the page name if they said it. You do not ask the customer.", "Add service and town if you have them.", "Do not book the job.", "If they are already chatting you, pass the thread to Sales."] },
        { id: "DIG-07", title: "Weekly digital pack", when: "The day before the marketing weekly report.", steps: ["Split the numbers by named online place (Meta, Google, search, social, or other): Meta, Google, search, social, other.", "Spend, enquiries, cost, share of people with a written answer for how they found us.", "3 things that worked. 3 that failed. one written request.", "Content log for the week.", "Send to Marketing Manager. Not a voice note."] }
      ],
      kpis: [
        { name: "Test lead before a paid ad goes live", target: "100%", why: "Spend with no path to Sales is waste." },
        { name: "Digital enquiries with Operations’ written answer", target: "95% or more", why: "Sales cannot work a nameless lead." },
        { name: "Ads spend vs plan", target: "Inside the ads line", why: "The signed plan is the rule." },
        { name: "Cost per digital enquiry", target: "Inside the signed ceiling", why: "If cost blows, pause." },
        { name: "Live files in the library same day", target: "100%", why: "A file that lives only on a personal phone is not the company copy." },
        { name: "Weekly written report with wins and fails", target: "100% on time", why: "Wins-only reports hide bad spend." },
        { name: "Customer and provider films sourced from Operations", target: "100%", why: "Those videos are captured on the job. Content Maker edits them." }
      ],
      rules: [
        "The signed kit is the boss. You may publish from it. You may not add a new claim.",
        "No private boosts from a personal page.",
        "No prices Finance has not signed.",
        "Do not mix search results into ads spend.",
        "Do not book the customer yourself. Send them to Sales.",
        "If nobody sits in the Content Maker job, say so in writing and act in it."
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
      result: "Finished videos and still posts that match this month’s marketing kit, approved by the Digital Marketing Manager, then filed in the company content library the same working day.",
      what: "You make Panun Kaergar’s marketing files for the Digital Marketing Manager: AI videos and still images, company films, and films with the founder when that face is already allowed this month, plus customer and provider videos collected from Operations and edited. You send each finished file for approval the same working day.",
      why: "Digital channels cannot publish without finished, on-brand files. This role exists so production is a named job, and the Digital Marketing Manager can approve and go live from a company library instead of making every file.",
      how: "The Marketing Manager writes and signs the kit. The Digital Marketing Manager gives this job that kit and a file list. AI videos, AI still posts, brand films, and founder films are made from those two sources. Customer-feedback and provider job films are collected from Operations and edited to the kit. Named files go to the Digital Marketing Manager for approval. Only a yes puts a file in the library.",
      acting: "If nobody is in this job, Digital Marketing Manager does this work and must write that they are doing it today because nobody is in that job. If you are doing this job because nobody is in it, write that on the same-day file note.",
      owns: [
        "AI videos and AI still posts from the signed kit",
        "Brand films from the signed kit",
        "Founder films when the face is already in the kit",
        "Customer-feedback and provider job films, edited from Operations footage",
        "Every finished file named and sent for approval the same working day",
        "The production list: waiting, sent back, approved, in the library, blocked"
      ],
      mustNot: [
        "Publish, schedule, or go live with a file",
        "Spend ads money or boost a post",
        "Capture customer or provider footage on the job",
        "Add a claim, price, town, or face that is not in the signed kit",
        "Put a file in the library before a written yes",
        "Keep masters only on a personal phone or private chat",
        "Book the customer or keep talking to them in a comment or chat yourself"
      ],
      responsibilities: [
        { title: "Make the file", what: "Turn each line on the Digital Marketing Manager's file list into a video or post that matches the kit.", why: "The Digital Marketing Manager approves from finished files, then publishes.", how: "Use the signed words and look. If you need a new claim, send the line back through the Digital Marketing Manager." },
        { title: "Send it for approval", what: "Same day: name, type, service, town, date. Send the named file to the Digital Marketing Manager.", why: "A file enters the library only after the Digital Marketing Manager says yes.", how: "Put the named file in the review path the Digital Marketing Manager named. Tell the Digital Marketing Manager it is ready to approve." },
        { title: "Edit Operations footage", what: "Collect customer-feedback and provider videos from Operations. Edit them to the kit.", why: "Those videos are captured on the job. Operations sends the files. This job cuts them.", how: "Take the files from Operations. Edit to the kit look and words. Name them and send them to the Digital Marketing Manager for approval the same working day." }
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
        { id: "CMC-02", title: "Stop for a new claim", when: "If the file list needs words or a face that is not in this month’s marketing kit.", steps: ["Stop that line.", "Tell the Digital Marketing Manager.", "Wait. The Digital Marketing Manager asks the Marketing Manager."] },
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
        "Customer feedback and provider videos come from Operations. This job collects, edits, and sends them to the Digital Marketing Manager for approval."
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
      layout: "detailed",
      result: "Give Field Visitor their towns and days so they actually visit the market. Send First Provider Onboarding to get the first local workers in a town to sign the papers. Send Office and Society Contracts to get a signed maintenance contract from named offices and housing societies.",
      what: "You run Panun Kaergar on the ground. Three jobs report to you. Field Visitor actually visits the market — a stall, a street, or a follow-up. That person may be a full-time employee, or someone we pay on a written contract. The job is the same either way. First Provider Onboarding goes in person to the first local workers (plumbers, electricians, and others) and gets them to sign the papers so they can work with Panun Kaergar. Office and Society Contracts writes maintenance contracts with offices and housing societies. You write which town, which day, and which of the three jobs goes. You check that each job hits its one result. You send the weekly field report to Marketing Manager. You do not do those three jobs yourself unless that job has nobody in it — and then you write that you are doing it today because nobody is in that job.",
      why: "Kashmir still buys from people they can see. Stalls alone are not enough. A town also needs the first local workers signed onto us, and offices and housing societies on a written maintenance contract. If one person tries to do all of that without naming who visits, nobody owns the result.",
      how: "Marketing Manager writes the month: towns, the marketing kit (the instruction book of words, prices, towns, and faces), stall money, and what we will not advertise. Expansion has already opened the town. You turn that into written work for the three jobs. Field Visitor walks the market and collects names for Sales. First Provider Onboarding gets the first local workers to sign the papers, then sends the signed file to Provider Operations so Provider Operations can put them on customer jobs. Office and Society Contracts uses only the signed contract papers Offer and Finance already approved — they do not invent a price. One-off jobs still go to Sales. You do not run ads. You do not pick a new town.",
      lanes: [
        { kicker: "Part 1", art: "lane-stall.png", title: "Stalls and street visits", work: "Send Field Visitor to a named town on a named day. They collect names for Sales. They do not take a booking or money.", result: "Someone actually visited the market that day, and Sales has the list the same day.", who: "Field Visitor. They may be a full-time employee, or someone we pay on a written contract. If that job is empty, you do this work today. Write that down." },
        { kicker: "Part 2", art: "lane-workers.png", title: "First local workers", work: "Send First Provider Onboarding in person to the first plumbers, electricians, and other local workers in a named town. They get those people to sign the papers, then send the signed file to Provider Operations.", result: "The town has named first local workers who have signed with us, before we keep standing at a stall there.", who: "First Provider Onboarding. If that job is empty, you do this work today. Write that down." },
        { kicker: "Part 3", art: "lane-society.png", title: "Offices and housing societies", work: "Send Office and Society Contracts to write a maintenance contract with a named office or housing society. They use only the signed contract papers Offer and Finance already approved. Named building, named services, signed price.", result: "There is a signed contract file, not a spoken yes. Operations may then start sending workers to that building.", who: "Office and Society Contracts. If that job is empty, you do this work today. Write that down." }
      ],
      acting: "If nobody sits in the Field Marketing Manager job, Marketing Manager does that work and writes that they are doing it today because nobody is in that job. If nobody sits in Field Visitor, First Provider Onboarding, or Office and Society Contracts, you do that work and write it on the daily note: you are doing it today because nobody is in that job. A Field Visitor may be a full-time employee or someone we pay on a written contract — the job is the same either way.",
      owns: [
        "You write which town, which day, and which of the three jobs goes",
        "You give Field Visitor their towns and days. They may be a full-time employee or someone we pay on a written contract",
        "You send First Provider Onboarding to get the first local workers to sign the papers",
        "You send Office and Society Contracts to write maintenance contracts with named offices and housing societies",
        "You give out only the signed first-worker papers and the signed office-contract papers",
        "You send Marketing Manager a weekly field report: visits, first local workers, and contracts"
      ],
      mustNot: [
        "Do not take a booking or money at a stall, and do not let Field Visitor do that",
        "Do not invent a price, a service, or a town",
        "Do not keep the first local workers as your own team, and do not skip Provider Operations",
        "Do not sign hotel desks or shops that send us their customers — that is Partnerships",
        "Do not run digital ads",
        "Do not let Field Visitor skip the same-day list to Sales, and do not let them visit a town that is not on the plan"
      ],
      responsibilities: [
        { title: "Give Field Visitor their written work and check the result", what: "Give them which towns, which days, and which boards. They visit the market — stall or street. They may be a full-time employee or someone we pay on a written contract. If nobody sits there, you visit and write that you are doing it today because nobody is in that job.", why: "A calendar with nobody walking it is decoration." },
        { title: "Give First Provider Onboarding their written work and check the result", what: "Send them in person to the first local workers in a named town. They take only the signed first-worker papers. Then they send the signed file to Provider Operations so Provider Operations can put those people on customer jobs.", why: "A stall in a town with nobody who can do the jobs is how the brand dies in public." },
        { title: "Give Office and Society Contracts their written work and check the result", what: "Give them the signed contract papers Offer and Finance already approved. They write maintenance contracts with named offices and housing societies. They do not invent a price. One-off jobs still go to Sales.", why: "A verbal yes from a society is not a contract. Operations cannot plan jobs from a verbal yes." },
        { title: "One calendar, three jobs", what: "Write which days are stall visits, which days are first-worker visits, and which days are office and society visits. Nobody invents their own week.", why: "Otherwise three people act like three companies." },
        { title: "Written papers, not a chat message", what: "Each job gets a written brief the day the month is signed: towns, the marketing kit, the first-worker papers or office-contract papers, and what we will not advertise. A chat message is not the brief.", why: "Someone we pay on a written contract who only has a voice note will invent the company." },
        { title: "Honest weekly numbers", what: "Each week write: visits vs calendar, names to Sales, first local workers who signed, office and society contracts signed, days that did not happen, towns that produced nothing, and one or two written requests.", why: "Marketing Manager cannot run Field without numbers they can act on." }
      ],
      when: {
        daily: ["Check that today’s stall visit, first-worker visit, or contract visit is named on the calendar.", "If a job has nobody in it, write that you are doing it today because nobody is in that job, then do that job’s same-day checks.", "Check stall lists reached Sales. Check first-worker files went to Provider Operations. Check signed contracts are filed."],
        weekly: ["Read the three weekly notes. Write the weekly field report.", "Name a town with no visits, no first local workers who signed, or no contract movement.", "Tell Marketing Manager what to keep, pause, or change."],
        monthly: ["Help Marketing Manager write next month’s field towns and days — visits, first local workers, offices and societies.", "Return unused stall money honestly.", "Renew or close written contracts with people we pay to visit, in writing."]
      },
      standards: [
        "Every stall visit, first-worker visit, and contract visit is a named town on the signed plan.",
        "If Field Visitor, First Provider Onboarding, or Office and Society Contracts is empty, that is written as you doing it today because nobody is in that job.",
        "A Field Visitor who is not a full-time employee has a written contract. A verbal yes is not a visitor.",
        "First local workers are sent to Provider Operations after they sign. They are not kept as a private team.",
        "Office and society contracts use only the signed contract papers Offer and Finance already approved. No invented price.",
        "The weekly field report names visits, first local workers, and contracts — not only stalls."
      ],
      procedures: [
        { id: "FLD-01", title: "Give the three jobs their written work for the month", when: "The same day Marketing Manager gives you the signed month, and whenever the plan changes.", steps: ["Give Field Visitor: towns, days, boards, flyers, where a stall list must land in Sales, and what we will not advertise.", "Give First Provider Onboarding: which towns, the signed first-worker papers, and who in Provider Operations receives the signed file.", "Give Office and Society Contracts: which towns, the signed contract papers, and where a signed contract must be filed.", "If a job is filled by someone we pay on a written contract, attach that contract. A chat is not the contract.", "Save the briefs as files."] },
        { id: "FLD-02", title: "Check Field Visitor on a stall or market day", when: "On a named visit day.", steps: ["Confirm they have town, pitch, and boards.", "They collect names and send the list to Sales the same day (stall plus town plus date).", "They do not book. They do not take money.", "If nobody sits in that job, you visit yourself and write that you are doing it today because nobody is in that job."] },
        { id: "FLD-03", title: "Send First Provider Onboarding in person", when: "A named town on the plan needs its first local workers.", steps: ["Check Expansion has opened the town.", "Give the signed first-worker papers: what we pay, what they must do, what they must not do.", "They go in person. They do not sign people up from a company WhatsApp group without visiting them.", "Signed file goes to Provider Operations the same week. You keep a copy.", "If the papers are missing a price, stop. Finance signs price. You do not invent it."] },
        { id: "FLD-04", title: "Send Office and Society Contracts to write a maintenance contract", when: "A named office or housing society is on this month’s field plan.", steps: ["Give Office and Society Contracts only the signed contract papers: services, what is in, what is out, signed price.", "They name the building, the town, the start date, and who signs for them. You do not write the contract yourself unless that job has nobody in it — and then you write that you are doing it today.", "They do not take cash in the office. Finance sends the invoice.", "They file the signed contract. They tell Operations workers may start going to that building.", "One-off jobs from a person in that building still go to Sales unless the contract already covers them.", "If the building only wants to send flat owners and does not want to buy maintenance, pass that name to Partnerships & Channels Manager."] },
        { id: "FLD-05", title: "Name a town that produced nothing", when: "A named town had three planned visits with no names, nobody who signed as a first local worker, and no contract movement.", steps: ["Open the three logs.", "Write town, dates, what was tried.", "Send it to Marketing Manager in the weekly report.", "Do not keep visiting to look busy. Do not pick a different town yourself."] },
        { id: "FLD-06", title: "Stop an incident on the ground", when: "A fight, a false claim, someone taking bookings or money in our name, or a board or contract that might be untrue.", steps: ["Stop that visit. Take down a board that might be untrue.", "Write what happened.", "Tell Marketing Manager the same day.", "Do not take a booking to calm someone down."] },
        { id: "FLD-07", title: "Write the weekly field report", when: "The day before Marketing Manager’s weekly marketing report.", steps: ["Visits vs calendar, names to Sales, by town.", "First local workers who signed this week, and whose files went to Provider Operations.", "Office and society contracts signed, waiting, or failed.", "Days that did not happen. Towns that produced nothing. Materials that need reprinting.", "One or two written requests — not ten.", "Send it to Marketing Manager in writing."] }
      ],
      kpis: [
        { name: "Visit days vs the calendar", target: "95% or more completed, or a written note the same day that the visit did not happen" },
        { name: "Stall names that reach Sales the same day", target: "95% or more" },
        { name: "First local workers who signed and whose files went to Provider Operations", target: "Every named town on the plan has a dated file, or a written note that nobody signed" },
        { name: "Office and society contracts from the signed papers only", target: "100%" },
        { name: "Empty visitor / first-worker / contracts job written as you doing it today because nobody is in that job", target: "100%" },
        { name: "Weekly field report on time — visits, first local workers, contracts", target: "100%" }
      ],
      rules: [
        "Field Visitor collects names at the stall. Sales books the job. Field Visitor does not take money or book the job. Offices and societies need a signed contract, not a verbal yes.",
        "Field Visitor may be a full-time employee or someone we pay on a written contract. The job is the same either way. A verbal yes is not a visitor.",
        "First local workers go to Provider Operations after they sign. You do not give them a customer job yourself.",
        "Hotel desks and shops that send us their customers are Partnerships. Offices and societies that buy ongoing maintenance are Office and Society Contracts.",
        "You do not run ads. That is Digital Marketing Manager.",
        "If this job is empty, Marketing Manager does that work and writes that they are doing it today because nobody is in that job."
      ],
      escalate: [
        { when: "A fight, a false claim, or someone taking bookings in our name", to: "Marketing Manager the same day", how: "Stop. Write what happened." },
        { when: "The town is not ready (nobody will sign as a first local worker)", to: "Marketing Manager, who tells Head of Growth", how: "Do not keep standing there to look busy." },
        { when: "A board, flyer, or contract might be untrue or shows a price that is not approved", to: "Marketing Manager the same day", how: "Take it down. Show the file." },
        { when: "Someone we pay to visit is working with no written contract", to: "Marketing Manager the same day", how: "Stop the visit. Write that there is no written contract." }
      ]
    }),
    fve: role({
      id: "fve",
      name: "Field Visitor",
      reportsTo: "Field Marketing Manager",
      hero: "role-fve.png",
      layout: "detailed",
      result: "Be in the named town on the named day. Collect names for Sales the same day. Do not take a booking or money.",
      what: "You actually visit the market — a stall, a street, or a follow-up. Field Marketing Manager writes which towns, which days, and which boards. You show up. You collect name, phone, service, and town. You send that list to Sales the same day. You write stall, plus the town and the date, because you met the person. You may be a full-time employee, or someone we pay on a written contract. The job is the same either way. You do not take the booking. You do not take money. You do not pick a new town.",
      why: "Kashmir still buys from people they can see. If nobody walks the market, the calendar is decoration. If you take the booking at the stall, Sales has no list and Operations cannot finish the job as a system.",
      how: "You work only from the written brief Field Marketing Manager gave you. On a visit day you stand in the named town with the approved boards. On an office day you restock, confirm the next visit, and write the weekly note. If you want a new sentence, price, town, or face, you stop and ask Field Marketing Manager. If nobody sits in this job, Field Marketing Manager does that work and writes that they are doing it today because nobody is in that job.",
      lanes: [
        { kicker: "Part 1", art: "lane-stall.png", title: "Go to the town", work: "Go to the named town on the named day. Stand where the brief says. Use only the approved boards and flyers.", result: "We stood where the plan said. If the visit did not happen, you write that the same day. You do not hide it.", who: "You. You may be a full-time employee, or someone we pay on a written contract." },
        { kicker: "Part 2", art: "lane-names.png", title: "Collect names", work: "Write name, phone, service, and town. Write stall, plus the town and the date. Tell the person Sales will call. Do not take a booking or money.", result: "Sales has a complete list the same day.", who: "You." },
        { kicker: "Part 3", art: "lane-restock.png", title: "Office work", work: "Restock boards and flyers. Write the stall log. Write the weekly visit note to Field Marketing Manager.", result: "Field Marketing Manager can see which towns worked. Unused boards and flyers are named.", who: "You." }
      ],
      acting: "If nobody sits in this job, Field Marketing Manager does that work and writes that they are doing it today because nobody is in that job. You do not get first local workers to sign papers. You do not write office and society contracts. You do not become Sales.",
      owns: [
        "You stand in the named town on the named day, with the approved boards and flyers",
        "You write a list of every person you met: name, phone, service, town, stall plus town plus date",
        "You write a stall log before you leave: where you stood and what you collected",
        "You send that list to Sales the same day",
        "You send Field Marketing Manager a weekly visit note",
        "If you cannot go that day, you write it the same day"
      ],
      mustNot: [
        "Do not take a booking or money at the stall",
        "Do not promise a price that is not in this month’s marketing kit",
        "Do not pick a new town",
        "Do not sign hotel-desk partners, first local workers, or office maintenance contracts",
        "Do not run digital ads",
        "Do not skip sending the list to Sales the same day, and do not visit a town that is not on your written list"
      ],
      responsibilities: [
        { title: "Show up where the plan says", what: "Be in the named town on the named day, with the approved boards and flyers. Confirm the pitch the day before.", why: "A random Saturday stall cannot be measured." },
        { title: "Collect names. Do not book.", what: "Write name, phone, service, and town. Write stall, plus the town and the date. Say Sales will call. Do not take money. Do not promise a job on the spot.", why: "You collect names at the stall. Sales books the job. Operations does the job. You do not take money or book the job yourself." },
        { title: "Boards and flyers", what: "Use only what is in this month’s marketing kit (the instruction book of words, prices, towns, and faces). If you want a new sentence, price, town, or face, stop and ask Field Marketing Manager.", why: "A wrong price on a board becomes a fight later." },
        { title: "Same-day list to Sales", what: "Send one complete list before you leave the town. Confirm Sales received it. A missing phone is not an enquiry.", why: "A list that arrives tomorrow is a person nobody called." },
        { title: "Stall log", what: "Write where you stood, start and end, materials used, how many names you collected, and any incident. Take no cash and no booking.", why: "If it is not written, it did not happen." },
        { title: "Honest weekly note", what: "Each week write days completed vs the calendar, enquiries by town, days that did not happen, towns that produced nothing after three visits, materials that need reprinting, and one or two written requests.", why: "Field Marketing Manager cannot run this job without numbers they can act on." }
      ],
      when: {
        daily: ["If it is a visit day: stand in the named town, collect names, send the list to Sales, write the stall log before you leave.", "If it is an office day: restock boards and flyers, confirm the next visit, and close any list that is still sitting.", "If someone later calls Operations, Operations asks where they found us. You do not own the phone."],
        weekly: ["Write the weekly visit note: days vs calendar, enquiries by town, days that did not happen, towns that produced nothing, materials that need reprinting.", "Name a town that produced no enquiries after three visits.", "Tell Field Marketing Manager what to keep, pause, or reprint."],
        monthly: ["Help Field Marketing Manager write next month’s visit towns and days.", "Do not start a town that is not on the new signed plan."]
      },
      standards: [
        "Every visit is a named town on the signed plan.",
        "95% or more of visit days on the calendar actually happen, or you write the same day that the visit did not happen.",
        "95% or more of stall names reach Sales the same day, with phone, town, and stall as the source.",
        "No cash, no booking, and no job promise at the stall.",
        "Boards and flyers match this month’s marketing kit.",
        "A town with no enquiries after three visits is named in the weekly note."
      ],
      procedures: [
        { id: "VIS-01", title: "Run a visit day", when: "On a named visit day on the signed calendar.", steps: ["The day before: confirm town, pitch, boards, flyers, and that Expansion still serves that town.", "Set up with approved boards only. No extra handwritten price.", "Write each person: name, phone, service, town, source = stall plus town plus date.", "Do not book. Do not take money. Say Sales will call.", "If a phone number is missing, that row is not an enquiry. Do not pretend.", "Send the list to Sales before you leave the town (VIS-02).", "Write the stall log: times, materials, count, incidents.", "Write anything people kept asking that we do not sell. Send that to Field Marketing Manager, not as a new offer."] },
        { id: "VIS-02", title: "Send the stall list to Sales", when: "The same day as the visit, before you leave the town.", steps: ["One list. Complete rows only: name, phone, service, town, stall plus town plus date.", "Send it to Sales. Write the time they received it.", "Send Field Marketing Manager the count, not the phone numbers.", "If Sales did not receive it, send it again the same day. Do not wait until morning."] },
        { id: "VIS-03", title: "Office day — restock and confirm the next visit", when: "On a working day that is not a visit day.", steps: ["Count boards and flyers. What is torn, outdated, or missing?", "If a board or flyer is not in this month’s marketing kit, take it out of the bag.", "Confirm next visit: town, day, pitch, materials.", "If a stall list from yesterday is still sitting, send it now and write that it was late.", "Do not put up a stall because you were nearby."] },
        { id: "VIS-04", title: "Ask to change a sentence, price, town, or face", when: "When you want to say something that is not already on the approved board or flyer.", steps: ["Stop. Do not write it on the board yourself.", "Write what you want to say and why.", "Send it to Field Marketing Manager. Wait for a written yes or no.", "If it is a brand-new town or service, they take it to Marketing Manager. You do not."] },
        { id: "VIS-05", title: "Name a town that produced nothing", when: "A named town produced no real enquiries after three visits.", steps: ["Open the stall logs for those three days.", "Write: town, dates, how many people stopped, how many names you collected.", "Send it to Field Marketing Manager in the weekly note.", "Do not keep standing there to look busy. Do not pick a different town yourself."] },
        { id: "VIS-06", title: "Stop an incident at the stall", when: "A fight, a false claim, someone taking bookings or money in our name, or a board that might be untrue.", steps: ["Stop that visit. Take down a board that might be untrue.", "Write what happened, the time, and who was there.", "Tell Field Marketing Manager the same day.", "Do not argue it in the street. Do not take the booking to calm someone down."] },
        { id: "VIS-07", title: "Write the weekly visit note", when: "The day before Field Marketing Manager’s weekly field report.", steps: ["Write visit days completed vs the calendar, and any day that did not happen.", "Write how many people enquired, by town.", "Name a town that produced nothing after three visits.", "Write materials that need reprinting.", "Write one or two written requests — not ten.", "Send it to Field Marketing Manager in writing. Not a voice note."] }
      ],
      kpis: [
        { name: "Visit days vs the calendar", target: "95% or more completed, or a written note the same day that the visit did not happen" },
        { name: "Stall names that reach Sales the same day", target: "95% or more" },
        { name: "Enquiries per planned visit day", target: "Track by town, and improve vs last month" },
        { name: "Visits only in towns on the plan", target: "100%" },
        { name: "Weekly visit note on time", target: "100%" },
        { name: "No cash and no booking at the stall", target: "100%" }
      ],
      rules: [
        "You collect names at the stall. Sales books the job. You do not take money or book the job yourself.",
        "You may be a full-time employee or someone we pay on a written contract. The job is the same either way. A verbal yes is not this job.",
        "You represent the brand. No private side deals.",
        "If Operations cannot serve that town yet, you should not be there.",
        "You write stall plus town plus date because you met the person. If they later call, WhatsApp, or use the app, Operations asks where they found us. You do not own that call.",
        "You do not get first local workers to sign papers. You do not write office and society contracts. You do not run ads."
      ],
      escalate: [
        { when: "A fight, a false claim, or someone taking bookings in our name", to: "Field Marketing Manager the same day", how: "Stop. Write what happened." },
        { when: "The town is not ready (no local workers who can do the jobs)", to: "Field Marketing Manager the same day", how: "Do not keep standing there to look busy." },
        { when: "A board or flyer might be untrue or shows a price that is not in this month’s marketing kit", to: "Field Marketing Manager the same day", how: "Take it down. Show the file." },
        { when: "You are paid on a written contract but do not have that contract in your hand", to: "Field Marketing Manager before you visit", how: "Do not visit. Write that there is no written contract." }
      ]
    }),
    fpo: role({
      id: "fpo",
      name: "First Provider Onboarding",
      reportsTo: "Field Marketing Manager",
      hero: "role-fpo.png",
      layout: "detailed",
      result: "Go in person. Get the first local workers in a named town to sign the papers so they can work with Panun Kaergar. Send the signed file to Provider Operations the same week.",
      what: "You go to the first local workers in a named town (plumbers, electricians, and others who will do the jobs) and get them to sign the papers so they can work with Panun Kaergar. You take only the signed first-worker papers: who we are, what they will do, what they will not do, what we pay. You do not hire them as a private team. You do not give them a customer job yourself. After they sign, you send the signed file to Provider Operations. Provider Operations puts them on customer jobs.",
      why: "A stall in a town with nobody who can do the jobs is how the brand dies in public. Ads and stalls promise a job. If nobody can do that job, the town learns to hate us. This job exists so the first local workers are signed in person, in writing, before we keep standing at a stall there.",
      how: "Field Marketing Manager names the town and gives you the signed first-worker papers. Expansion has already opened the town. You go in person. You meet the plumber, electrician, or other local worker at their shop or house. You do not sign them up from a company WhatsApp group without visiting them. If the papers are missing a price, you stop — Finance signs price. If nobody sits in this job, Field Marketing Manager does that work and writes that they are doing it today because nobody is in that job.",
      lanes: [
        { kicker: "Part 1", art: "lane-workers.png", title: "Go in person", work: "Go to the named town. Meet the plumber, electrician, or other local worker at their shop or house. Do not sign them up from a company WhatsApp group without visiting them.", result: "The visit happened — or you wrote the same day that it did not happen.", who: "You." },
        { kicker: "Part 2", art: "lane-sign.png", title: "Get them to sign", work: "Use only the signed first-worker papers. Write their name, phone, services they can do, towns they can cover, what we pay, and what they must not do.", result: "There is a signed file, not a spoken yes.", who: "You." },
        { kicker: "Part 3", art: "lane-handoff.png", title: "Send the file to Provider Operations", work: "Send the signed file the same week. Keep a copy. Do not give them a customer job yourself.", result: "Provider Operations can put them on the list of workers and put them on customer jobs.", who: "You send the file. Provider Operations puts them on the jobs." }
      ],
      acting: "If nobody sits in this job, Field Marketing Manager does that work and writes that they are doing it today because nobody is in that job. You do not stand at stalls as Field Visitor. You do not write office and society customer contracts. You do not become Provider Operations.",
      owns: [
        "You go in person to the first local workers in a named town",
        "You take only the signed first-worker papers with you",
        "You get a signed paper from that person before you leave",
        "You send that signed paper to Provider Operations the same week",
        "You send Field Marketing Manager a weekly note: visits, files signed, files sent, towns where nobody signed",
        "If a town on this month’s list has nobody who will sign, you write that down"
      ],
      mustNot: [
        "Do not keep the first local workers as your own list, and do not skip Provider Operations",
        "Do not give them a customer job yourself",
        "Do not invent a payment, a service, or a town",
        "Do not hire them as company employees — that is People & HR",
        "Do not start work in a town Expansion has not opened",
        "Do not stand at a stall, and do not write an office maintenance contract, as if that were this job"
      ],
      responsibilities: [
        { title: "Go in person to the first local workers", what: "Be in the named town. Meet the plumber, electrician, or other local worker at their shop or house. Do not sign them up from a company WhatsApp group without visiting them.", why: "Getting people to sign from an office, without visiting them, is not this job." },
        { title: "Use only the signed first-worker papers", what: "The papers name who we are, what they will do, what they will not do, and what we pay. A verbal yes is not enough.", why: "A made-up payment becomes a fight." },
        { title: "Get a signed file", what: "Name, phone, services they can do, towns they can cover, start date, signed papers. Incomplete is not signed. Do not pretend they can work with us yet.", why: "Provider Operations cannot put a nameless person on a customer job." },
        { title: "Send the file to Provider Operations the same week", what: "Send the signed file. Write the day they received it. Keep a copy. Do not give them a customer job yourself.", why: "You get them to sign the papers. Provider Operations puts them on customer jobs." },
        { title: "Name a town that will not sign", what: "If a named town has nobody who will sign after the planned visits, write it. Do not keep visiting to look busy.", why: "Field Marketing Manager must stop the stall if the town has nobody who can do the jobs." },
        { title: "Honest weekly note", what: "Each week: visits vs calendar, files signed, files sent to Provider Operations, towns where nobody signed, one or two written requests.", why: "Field Marketing Manager cannot run this job without numbers." }
      ],
      when: {
        daily: ["If it is a first-worker visit day: go in person with the signed papers, write the visit log, do not invent a payment.", "If a file is signed, send it to Provider Operations the same week — do not sit on it.", "If the papers are missing a price, stop and tell Field Marketing Manager."],
        weekly: ["Write the weekly note: visits, signed files, files sent to Provider Operations, towns where nobody signed.", "Name a town that will not sign.", "Tell Field Marketing Manager what to keep, pause, or change."],
        monthly: ["Help Field Marketing Manager name next month’s first-worker towns.", "Do not start a town that is not on the new signed plan."]
      },
      standards: [
        "Every first-worker visit is a named town on the signed plan.",
        "Every person you mark as able to work with us has a signed paper file.",
        "100% of signed files reach Provider Operations the same week.",
        "No invented payment, service, or town.",
        "A town that will not sign is named in the weekly note.",
        "You do not give people customer jobs."
      ],
      procedures: [
        { id: "FPO-01", title: "Go in person with the signed first-worker papers", when: "On a named first-worker visit day.", steps: ["Confirm the town is on the signed plan and Expansion has opened it.", "Take only the signed first-worker papers. If a price is missing, stop.", "Meet the plumber, electrician, or other local worker at their shop or house. Do not sign them up from a chat list without visiting them.", "Write the visit log: who, where, what they can do, whether they will sign.", "Do not promise a customer job today."] },
        { id: "FPO-02", title: "Get a first local worker to sign onto Panun Kaergar", when: "The person is ready to sign.", steps: ["Read the papers with them: what they will do, what they will not do, what we pay.", "Write name, phone, services, towns, start date.", "They sign. You sign. Date it.", "Incomplete is not signed. Do not pretend they can work with us yet."] },
        { id: "FPO-03", title: "Send the signed file to Provider Operations", when: "The same week as the signature. Do not wait for month-end.", steps: ["Send the signed file to the person Field Marketing Manager named in Provider Operations.", "Write the day they received it.", "Keep a copy.", "Do not give them a customer job. Do not keep them as a private list."] },
        { id: "FPO-04", title: "Name a town that will not sign", when: "Planned visits are done and nobody signed.", steps: ["Open the visit logs.", "Write town, dates, who you met, why they would not sign.", "Send it to Field Marketing Manager in the weekly note.", "Do not keep visiting to look busy. Do not pick a different town yourself."] },
        { id: "FPO-05", title: "Write the weekly first-worker note", when: "The day before Field Marketing Manager’s weekly field report.", steps: ["Visits vs calendar.", "Files signed this week.", "Files sent to Provider Operations.", "Towns where nobody signed.", "One or two written requests.", "Send it to Field Marketing Manager in writing."] }
      ],
      kpis: [
        { name: "In-person visits vs the calendar", target: "95% or more completed, or a written note the same day that the visit did not happen" },
        { name: "People marked as able to work with us who have a signed paper file", target: "100%" },
        { name: "Signed files sent to Provider Operations the same week", target: "100%" },
        { name: "No invented payment or town", target: "100%" },
        { name: "Named towns with a written note that nobody signed, or a signed file", target: "100% of towns on the plan" },
        { name: "Weekly first-worker note on time", target: "100%" }
      ],
      rules: [
        "You get them to sign the papers. Provider Operations puts them on customer jobs.",
        "A verbal yes is not a first local worker. Provider Operations can only put them on jobs after they have signed.",
        "You do not open towns. Expansion already opened the town.",
        "You do not hire company employees. People & HR owns employees.",
        "Hotel desks and shops that send us their customers are Partnerships. This job is the people who will do the jobs.",
        "If this job is empty, Field Marketing Manager does that work and writes that they are doing it today because nobody is in that job."
      ],
      escalate: [
        { when: "The papers are missing a price or a service", to: "Field Marketing Manager the same day", how: "Stop. Do not invent it." },
        { when: "A named town will not sign", to: "Field Marketing Manager in the weekly note, same day if the stall is still standing", how: "Write who you met and why they said no." },
        { when: "Someone wants to be paid cash on the side", to: "Field Marketing Manager the same day", how: "Stop. Write it. Do not pay." },
        { when: "Provider Operations did not receive a signed file", to: "Field Marketing Manager the same day", how: "Send it again. Write that they did not receive it." }
      ]
    }),
    flc: role({
      id: "flc",
      name: "Office and Society Contracts",
      reportsTo: "Field Marketing Manager",
      hero: "role-flc.png",
      layout: "detailed",
      result: "A named office or housing society has signed a maintenance contract from the papers Offer and Finance already approved, so Operations can send workers there. A verbal yes is not a contract.",
      what: "You write a maintenance contract with a named office or housing society so Operations can send workers there. You go to that building, meet the person who is allowed to sign, and use only the signed contract papers Offer and Finance already approved. After both sides sign, you file the contract and tell Operations they can start.",
      why: "Offices and societies buy a written contract, not a stall flyer. A verbal yes from a secretary is not a contract. Operations cannot plan jobs from a verbal yes. Partnerships signs hotel desks and shops that send us their customers. This job is the office or housing society that buys ongoing maintenance from us.",
      how: "Field Marketing Manager names the buildings and towns and gives you the signed contract papers. You go to the building. You write the contract. Finance sends the invoice. You file the signed contract. If nobody sits in this job, Field Marketing Manager does that work and writes that they are doing it today because nobody is in that job.",
      lanes: [
        { kicker: "Part 1", art: "lane-society.png", title: "Go to the building", work: "Go to the named office or housing society on this month’s plan. Meet the person who is allowed to sign.", result: "The visit happened — or you wrote the same day that it did not happen.", who: "You." },
        { kicker: "Part 2", art: "lane-sign.png", title: "Write the contract", work: "Use only the signed contract papers: services in, services out, signed price, start date, who signs for them.", result: "There is a signed contract file. Not a WhatsApp yes.", who: "You." },
        { kicker: "Part 3", art: "lane-handoff.png", title: "File it so Operations can work", work: "File the signed contract. Tell Operations they may start sending workers. Do not take cash. Finance sends the invoice.", result: "Operations has a building they can serve. You did not become the person who does the jobs.", who: "You file it. Operations does the jobs." }
      ],
      acting: "If nobody sits in this job, Field Marketing Manager does that work and writes that they are doing it today because nobody is in that job. You do not stand at stalls as Field Visitor. You do not get first local workers to sign papers. You do not become Sales or Operations.",
      owns: [
        "You go to the named office or housing society and meet the person who is allowed to sign",
        "You take only the signed contract papers with you",
        "You get a signed maintenance contract before you call the building done",
        "After both sides sign, you file the contract and tell Operations they can start sending workers",
        "You send Field Marketing Manager a weekly note: visits, contracts signed, waiting, or failed",
        "If the named building will not sign, you write that down the same week"
      ],
      mustNot: [
        "Do not invent a price, a service, or a town",
        "Do not take cash in the office or society",
        "Do not sign hotel desks or shops that send us their customers — that is Partnerships",
        "Do not book a one-off job for a person in that building yourself — that is Sales",
        "Do not do the maintenance jobs — that is Operations",
        "Do not visit a building that is not on this month’s written list"
      ],
      responsibilities: [
        { title: "Go to the named building", what: "Meet the person who is allowed to sign — office manager, society secretary, or named owner. Do not pitch a watchman and call it a contract.", why: "A contract with nobody who can sign is paper." },
        { title: "Use only the signed contract papers", what: "Services in, services out, signed price, how we invoice. If they want a different price, stop and ask Field Marketing Manager.", why: "A made-up price becomes a fight." },
        { title: "Get a signed contract", what: "Named building, town, start date, services, price, who signs for them, who signs for us. A WhatsApp yes is not a contract.", why: "Operations cannot plan jobs from a chat." },
        { title: "File it. Do not take cash.", what: "File the signed contract. Tell Operations they may start sending workers. Finance sends the invoice. You do not collect money in the office.", why: "Cash you collect on site has no owner in the company books." },
        { title: "One-off jobs still go to Sales", what: "A person in that building who wants a one-off job that the contract does not cover goes to Sales. You do not book them at the gate.", why: "The stall collects names. The contract is a file. Sales still books one-off work." },
        { title: "Honest weekly note", what: "Each week: visits vs calendar, contracts signed, waiting, or failed, buildings Operations can now serve, one or two written requests.", why: "Field Marketing Manager cannot run this job without numbers." }
      ],
      when: {
        daily: ["If it is a contract-visit day: go with the signed papers, meet the person who is allowed to sign, do not invent a price.", "If a contract is signed, file it and tell Operations the same week.", "If they want a price that is not in the papers, stop."],
        weekly: ["Write the weekly contract note: visits, signed, waiting, failed.", "Name a building that will not sign.", "Tell Field Marketing Manager what to keep, pause, or change."],
        monthly: ["Help Field Marketing Manager name next month’s offices and societies.", "Do not start a building that is not on the new signed plan."]
      },
      standards: [
        "Every visit is a named office or society on the signed plan.",
        "Every building Operations may send workers to has a signed contract from the signed papers.",
        "No cash collected in the office.",
        "No invented price.",
        "Operations is told the same week a contract is signed.",
        "Hotel desks and shops are not this job — that is Partnerships."
      ],
      procedures: [
        { id: "OSC-01", title: "Visit a named office or society", when: "On a named contract-visit day.", steps: ["Confirm the building is on this month’s plan.", "Take only the signed contract papers. If a price is missing, stop.", "Meet the person who is allowed to sign. Write their name and role.", "Do not pitch a watchman and call it done.", "Write the visit log: building, who you met, whether they will sign."] },
        { id: "OSC-02", title: "Write the maintenance contract", when: "The person who is allowed to sign is ready.", steps: ["Use only the signed papers: services in, services out, price, how we invoice, start date.", "Name the building, the town, and who signs for them.", "They sign. You sign. Date it.", "A WhatsApp yes is not a contract. Do not pretend."] },
        { id: "OSC-03", title: "File the contract and tell Operations", when: "The same week as the signature.", steps: ["File the signed contract where Field Marketing Manager named.", "Tell Operations they may start sending workers: services, start date, what is in, what is out.", "Do not take cash. Finance sends the invoice.", "One-off jobs the contract does not cover still go to Sales."] },
        { id: "OSC-04", title: "Name a building that will not sign", when: "Planned visits are done and they will not sign.", steps: ["Open the visit logs.", "Write building, dates, who you met, why they said no.", "Send it to Field Marketing Manager in the weekly note.", "Do not keep visiting to look busy. Do not pick a different building yourself."] },
        { id: "OSC-05", title: "Write the weekly contract note", when: "The day before Field Marketing Manager’s weekly field report.", steps: ["Visits vs calendar.", "Contracts signed, waiting, or failed.", "Buildings Operations can now serve.", "One or two written requests.", "Send it to Field Marketing Manager in writing."] }
      ],
      kpis: [
        { name: "Contract visits vs the calendar", target: "95% or more completed, or a written note the same day that the visit did not happen" },
        { name: "Buildings Operations may send workers to that have a signed contract from the signed papers", target: "100%" },
        { name: "Contracts from the signed papers only", target: "100%" },
        { name: "No cash collected on site", target: "100%" },
        { name: "Operations told the same week a contract is signed", target: "100%" },
        { name: "Weekly contract note on time", target: "100%" }
      ],
      rules: [
        "A verbal yes from a society is not a contract. Operations can only plan jobs from a signed paper.",
        "Hotel desks and shops that send us their customers are Partnerships. Offices and societies that buy ongoing maintenance are this job.",
        "You write the contract. Finance sends the invoice. Operations does the jobs. Sales books one-off work.",
        "You do not invent a price.",
        "You do not stand at stalls or get first local workers to sign papers as if that were this job.",
        "If this job is empty, Field Marketing Manager does that work and writes that they are doing it today because nobody is in that job."
      ],
      escalate: [
        { when: "They want a price or a service that is not in the signed papers", to: "Field Marketing Manager the same day", how: "Stop. Do not invent it." },
        { when: "Someone wants to pay cash in the office", to: "Field Marketing Manager the same day", how: "Do not take it. Finance sends the invoice." },
        { when: "A building will not sign", to: "Field Marketing Manager in the weekly note", how: "Write who you met and why they said no." },
        { when: "The contract might be untrue or shows a price that is not approved", to: "Field Marketing Manager the same day", how: "Do not leave it signed. Show the file." }
      ]
    }),
    mim: role({
      id: "mim",
      name: "Market Intelligence Manager",
      reportsTo: "Head of Growth",
      hero: "role-mim.png",
      layout: "detailed",
      result: "Every idea is closed as go, no-go, or more research with a date, using facts that have a source and a date. Not a guess, and not a row left as “we might look at this later”.",
      what: "You keep one true written picture of what people ask for, what competitors do, and which ideas are real enough to hand on. Every fact has a source and a date. If an idea is a go, you name who receives it: Offer (a service), Expansion (a town), or Partnerships (a kind of partner). You do not run ads, write the service, open a town, or book the customer.",
      why: "Without this job, Growth guesses. Guessing is expensive in Kashmir’s small towns. A rumour is not a plan.",
      how: "You pull numbers from the system, not from chat. You write facts with a source and a date. You close each idea as go, no-go, or more research with a date. You never leave a row as “we might look at this later”. If nobody sits in this job, Head of Growth does that work and writes that they are doing it today because nobody is in that job.",
      lanes: [
        { kicker: "Part 1", art: "lane-demand.png", title: "What people ask for, and what we sell", work: "Count enquiries and bookings by service and town from the system. A chat message is not a count.", result: "Head of Growth can see what is real, not what someone remembers.", who: "You." },
        { kicker: "Part 2", art: "lane-competitors.png", title: "Competitors", work: "Keep a named list of competitors. Write what actually changed, with a source and a date. A rumour is not a competitor move.", result: "We know what a named competitor did, not what someone heard.", who: "You." },
        { kicker: "Part 3", art: "lane-ideas.png", title: "Close each idea", work: "One idea, one row. Close it as go, no, or more research with a date. If it is a go, name who receives the file: Offer & Service Development Manager, Market Expansion Manager, or Partnerships & Channels Manager. Do not write the service or open the town yourself.", result: "No row is left as “we might look at this later”.", who: "You write the file. Head of Growth watches that a High idea does not sit more than 10 working days." }
      ],
      acting: "If nobody is in this job, Head of Growth does this work and must write that they are doing it today because nobody is in that job.",
      owns: [
        "Demand trackers (what people ask, what we sell, where)",
        "Competitor file and change log",
        "Opportunity register and validation",
        "Weekly and monthly Intelligence reports"
      ],
      mustNot: [
        "Run ads",
        "Write the service as Offer",
        "Open a town",
        "Hire workers",
        "Book the customer"
      ],
      responsibilities: [
        { title: "Demand truth", what: "Count enquiries and bookings by service and town from the system, not from memory.", why: "Chat is not a tracker." },
        { title: "Competitors", what: "Watch named competitors. Log what actually changed.", why: "A rumour is not a competitor move." },
        { title: "Opportunities", what: "One row per idea. Then validate or kill it.", why: "A list of dreams is not Intelligence." },
        { title: "Handoff", what: "Validated service → Offer. Validated area → Expansion. Validated kind of hotel desk or shop → Partnerships.", why: "You discover. You do not do their job." }
      ],
      when: {
        daily: ["File new facts with source and date.", "If a competitor can steal bookings this week, tell Head of Growth the same day."],
        weekly: ["Close the week’s demand trackers.", "Submit the weekly report from the registers.", "Hand newly validated items to the right seat."],
        monthly: ["Four-week demand vs the four weeks before.", "Full competitor pass.", "Opportunity funnel with no silent rows.", "Submit the monthly decision pack."]
      },
      standards: [
        "No fact without a source and a date.",
        "High opportunities decided in 10 working days (Head of Growth watches the 10-working-day limit; you own the pack).",
        "Never leave an idea as “we might look at this later”.",
        "Weekly report on time, built from registers."
      ],
      procedures: [
        { id: "MI-01", title: "File a fact", when: "Whenever a new extract, listing, or field note arrives.", steps: ["Accept only a real source.", "Write source, date, owner.", "Put it in the right tracker.", "Do not mix competitor prices into demand counts."] },
        { id: "MI-02", title: "Open an opportunity", when: "A gap repeats, a competitor moves, or a trend can change what we sell.", steps: ["One idea, one row.", "Link the evidence.", "Set kind: service, town, hotel desk or shop, or stop.", "Assign for validation. Do not mark go yet."] },
        { id: "MI-03", title: "Validate", when: "A row is in validate.", steps: ["Test demand, competition, and whether we could operate.", "Close as go, no-go, or more research with a date.", "If go, name the who gets the file next: Offer, Expansion, or Partnerships.", "Tell Head of Growth in the weekly written report."] }
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
      layout: "detailed",
      result: "A named service sheet Panun Kaergar can sell and finish: what is in, what is out, how the job should run, with a price Finance signed and Operations able to do the work.",
      what: "You write what Panun Kaergar can sell and finish. That means the name of the service, what is included, what is not included, how the job should run, and when to stop a dead service. Intelligence hands you a real need. Finance signs the price. Operations says they can do it. Head of Growth says Marketing may talk. You do not invent demand, run ads, or do the first jobs yourself.",
      why: "Marketing cannot invent the product. Operations cannot guess the product. If nobody writes what is in and what is out, Sales promises one thing and the worker does another.",
      how: "You turn a need Intelligence already marked as go into a service sheet. You send it to Finance for price options and to Operations for a written yes that they can do it. Head of Growth signs. Then Marketing may use only the sentences you wrote. If nobody sits in this job, Head of Growth does that work and writes that they are doing it today because nobody is in that job.",
      lanes: [
        { kicker: "Part 1", art: "lane-catalog.png", title: "The live list of what we sell", work: "Keep one live list. Take off any service name that has no written sheet. A name without a sheet is not a service.", result: "Sales picks from a true list, not from a messy menu.", who: "You." },
        { kicker: "Part 2", art: "lane-sheet.png", title: "Write the service sheet", work: "Write the name, who it is for, what we do, what we do not do, what the customer must prepare, what the worker must be able to do, the job steps, and the quality bar. Finance writes the price. Operations writes that they can finish the job.", result: "There is a complete sheet, not a slogan.", who: "You write the sheet. Finance signs the price. Operations signs that they can finish the job." },
        { kicker: "Part 3", art: "lane-review.png", title: "After it is live: keep, fix, or stop", work: "Watch how many people ask, complaints, and cancellations. Write keep, fix, or stop. Head of Growth signs a stop.", result: "A dead service does not sit in ads.", who: "You write the recommend. Marketing Manager stops talking only after Head of Growth signs stop." }
      ],
      acting: "If nobody is in this job, Head of Growth does this work and must write that they are doing it today because nobody is in that job.",
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
        { title: "Catalogue", what: "Keep one live list of what we sell. Take off the live list any service name that has no written sheet.", why: "Sales must not pick from a messy menu." },
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
      layout: "detailed",
      result: "A written enter, wait, or leave for one named town, after Provider Operations can finish the first jobs there. Not a town circled on a map with no written yes, and not ads before we can do the work.",
      what: "You decide where Panun Kaergar may work next. Intelligence names a town with evidence. You test whether we should enter, wait, or leave — in writing. Provider Operations must be able to finish the first jobs. Head of Growth signs. Then Marketing and Partnerships may spend there. You do not hire the workers, change what we sell, or run ads. First Provider Onboarding goes in person after the town is open. You do not do that job yourself.",
      why: "A new town with ads and nobody who can do the jobs is how the brand dies in public. Hope is not a plan.",
      how: "Intelligence hands you a named town with facts. You write demand, competition, whether we can finish jobs, and a rough cost to get a customer. You write enter, wait, or no. Head of Growth signs. If enter, you write the first services, what Marketing may spend, and the review date. If nobody sits in this job, Head of Growth does that work and writes that they are doing it today because nobody is in that job.",
      lanes: [
        { kicker: "Part 1", art: "lane-towns.png", title: "The list of towns", work: "Keep a written list of possible towns with facts. A feeling is not a list.", result: "Head of Growth can see what is next, or that nothing is next.", who: "You." },
        { kicker: "Part 2", art: "lane-enter.png", title: "Enter, wait, or no", work: "Write demand, competition, whether we can finish the first jobs, and a rough cost. Write enter, wait, or no. Head of Growth signs.", result: "There is a written decision, not research with no end.", who: "You write the decision. Provider Operations says if they can finish the jobs. You do not hire them." },
        { kicker: "Part 3", art: "lane-review.png", title: "After launch: grow, fix, pause, or leave", work: "Watch enquiries, bookings, and unfinished jobs. On the review date you wrote, write grow, fix, pause, or leave.", result: "We do not stay forever in a dead town.", who: "You write the recommend. Marketing Manager stops spending in that town only after Head of Growth signs pause or leave." }
      ],
      acting: "If nobody is in this job, Head of Growth does this work and must write that they are doing it today because nobody is in that job.",
      owns: [
        "Town list and enter / wait / no packs",
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
        weekly: ["Move the town list. Unblock Provider Operations on capacity.", "Report to Head of Growth."],
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
        { id: "ME-02", title: "Open a town", when: "Enter is signed and capacity is real.", steps: ["First services list.", "Marketing may-spend list.", "Launch week.", "Watch enquiries, bookings, and unfinished jobs.", "Review on the date you wrote."] },
        { id: "ME-03", title: "Pause or leave", when: "Numbers miss the launch bar and will not recover soon.", steps: ["Write why.", "Stop new marketing there.", "Head of Growth signs.", "Tell Marketing, Sales, and Operations."] }
      ],
      kpis: [
        { name: "Feasibility on time", target: "Within 15 working days" },
        { name: "Launch checklist", target: "100% before marketing spend" },
        { name: "Post-launch review", target: "100% on the dated review" }
      ],
      rules: [
        "Circling a town on a map is not opening it.",
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
      layout: "detailed",
      result: "A named hotel desk, shop, or other local business sends their customers to Sales the same day, with the partner name written. You do not book the customer, and you do not send the person to a plumber yourself.",
      what: "You find hotels and shops that already meet people we want as customers. Those places send us a name. Sales calls them. You check they fit, write the terms, and send their customers to Sales the same day with the hotel or shop name written. You do not book the customer. You do not send the person to a plumber. You do not write an office or society maintenance contract — that is Office and Society Contracts under Field.",
      why: "We cannot stand on every street. Good hotels and shops bring work we would miss. A handshake with no paper becomes a fight. A name sent straight to a plumber skips Sales and the brand.",
      how: "Market Intelligence Manager may send a written note that hotel desks, or another kind of place, are worth trying. That note is not permission. Head of Growth must write yes to that kind of place, and to how we pay them if we pay them, before you sign anyone. You write how a name is sent. You test one person into Sales. Only then do you call them live. Each month you write keep, fix, or close. If nobody sits in this job, Head of Growth does that work and writes that they are doing it today.",
      lanes: [
        { kicker: "Part 1", art: "lane-hotel.png", title: "Find and check the hotel or shop", work: "Write the real kind of place we miss — a hotel desk, an appliance shop, and so on. Write who they already serve, their reputation, and fit, wait, or no. Do not add your uncle or a neighbour who says they know people.", result: "There is a written fit, wait, or no — not a spoken yes.", who: "You." },
        { kicker: "Part 2", art: "lane-terms.png", title: "Write the terms, then start", work: "Write how they send us a name, and what we pay if we pay. Use only approved materials. Test one name into Sales. Only then call them live.", result: "No hotel or shop is live without a written terms page.", who: "You. Head of Growth has already said yes to that kind of place and how we pay them, if we pay them. Finance sees money that was not in the plan." },
        { kicker: "Part 3", art: "lane-review.png", title: "Keep, fix, or close", work: "Send every name to Sales the same day. Each month write keep, fix, or close. A hotel or shop that sends nobody is closed, in writing.", result: "A silent hotel or shop is a closed one, in writing.", who: "You find the hotel or shop. Sales books the customer." }
      ],
      acting: "If nobody is in this job, Head of Growth does this work and must write that they are doing it today because nobody is in that job.",
      owns: [
        "Hotel and shop list and written terms",
        "Getting a hotel or shop started, and the partner file",
        "Partner lead tracker (how they found us = partner)",
        "Keep / fix / close recommendation"
      ],
      mustNot: [
        "Book the partner’s customer yourself",
        "Send a person straight to a plumber",
        "Promise money Finance did not see",
        "Promise a service we do not sell"
      ],
      responsibilities: [
        { title: "Find", what: "Write the real kind of place we miss (hotel desk, appliance shop, and so on).", why: "Do not add your uncle or a neighbour who says they know people. That is not a partner." },
        { title: "Qualify", what: "Who they serve, reputation, whether we can work with them.", why: "A loud partner with bad jobs hurts the brand." },
        { title: "Agree and start", what: "Written terms, how a lead is sent, what we pay if we pay.", why: "Handshake-only deals become arguments." },
        { title: "Track", what: "Leads, bookings, and whether they are still sending work.", why: "A silent partner is a closed partner. Write it down." }
      ],
      when: {
        daily: ["Follow live partner chats. Log new leads to Sales the same day.", "Chase one hotel or shop that has not finished signing."],
        weekly: ["Hotel and shop list, and live partner numbers.", "Fix a partner who sends junk leads."],
        monthly: ["Keep / fix / close each live partner.", "Plan new kinds of hotel desk or shop with Market Intelligence Manager and Head of Growth."]
      },
      standards: [
        "100% of partner leads with the hotel or shop name written.",
        "No live partner without a written term sheet.",
        "Leads to Sales the same day.",
        "Monthly review of every live partner."
      ],
      procedures: [
        { id: "PC-01", title: "Qualify a partner", when: "A new name appears.", steps: ["Who they already serve.", "What we want from them.", "Reputation and fit.", "Mark fit / wait / no."] },
        { id: "PC-02", title: "Start a partner", when: "Head of Growth has agreed the kind of place and the terms.", steps: ["Write how a lead is sent.", "Give them only approved materials.", "Test one lead into Sales.", "Only then call them live."] },
        { id: "PC-03", title: "Partner lead to Sales", when: "Every partner enquiry.", steps: ["Write partner, plus the hotel or shop name.", "Service and town.", "Assign to Sales. Do not send them to a plumber.", "Track whether it booked."] }
      ],
      kpis: [
        { name: "Partner names written on every person they sent", target: "100%" },
        { name: "Live partners with written terms", target: "100%" },
        { name: "Monthly partner review", target: "100%" }
      ],
      rules: [
        "Sales talks to the customer. You do not.",
        "No cash side deals.",
        "If they sell a job we do not do, stop them."
      ],
      escalate: [
        { when: "A partner is taking money in our name", to: "Head of Growth same day", how: "Stop that hotel or shop. Write facts." },
        { when: "Terms need money we did not plan", to: "Head of Growth, then Finance", how: "Do not promise payment yourself." }
      ]
    })
  };

  const workflows = {
    growth: {
      id: "growth",
      name: "Growth workflow",
      kicker: "How Growth works",
      lede: "Growth makes something the market can buy, then creates enquiries with a written answer for how they found us. Sales books. Operations does the job. These three must not mix.",
      hero: "wf-growth.png",
      rules: [
        "Marketing generates the enquiry. Sales converts it. Operations finishes the job.",
        "Only approved services in approved towns.",
        "Every enquiry has a source: digital, field, partner, or other.",
        "Head of Growth says yes or no. Ads, stalls, and partners do not invent the company.",
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
            { who: "Marketing (Digital + Field)", does: "Creates enquiries with a written answer for how they found us." },
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
            { who: "Marketing and Partnerships", does: "Create enquiries with a written answer for how they found us in that town." },
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
            { who: "Digital Marketing Manager", does: "Runs the online part. Sends digital enquiries to Sales." },
            { who: "Field Marketing Manager", does: "Holds visits, first providers on site, and office and society contracts. Hands stall names to Sales." },
            { who: "Partnerships", does: "Adds hotels and shops that send us customers. Sends partner enquiries to Sales." },
            { who: "Sales", does: "Books, or writes why it was lost so Growth can learn." }
          ]
        }
      ]
    }
  };

  const extra = {
    hog: {
      story: "You decide who Panun Kaergar sells to, which sentences we may say, and whether Growth is working. Five people report to you. You write the monthly plan they work from. You say yes or no. You do not become them unless their job has nobody in it — and then you write that down.",
      receives: [
        { from: "CEO", what: "Company priorities, budget ceiling, and what Panun Kaergar will not do." },
        { from: "Market Intelligence", what: "Go / no-go packs with source and date. Opportunity funnel." },
        { from: "Marketing", what: "Weekly spend, enquiries with a written answer for how they found us, cost per enquiry, cost per booking." },
        { from: "Sales", what: "Lost-lead reasons. Which sources actually book." },
        { from: "Operations", what: "Whether we can finish the jobs we are selling, by service and town." }
      ],
      gives: [
        { to: "Marketing Manager", what: "A signed monthly plan: who, offer, towns, channels, budget, targets." },
        { to: "Offer & Expansion", what: "Yes, no, or send back on a service or a town." },
        { to: "Partnerships", what: "Yes or no on a kind of hotel desk or shop and how we pay them, if we pay them." },
        { to: "CEO", what: "Weekly Growth numbers and anything that risks money, brand, or a launch." }
      ],
      records: ["Monthly Growth plan you signed before any money was spent", "Weekly Growth review sent to the CEO", "List of important ideas for High ideas", "Approved service list Offer keeps", "Approved town list Expansion keeps", "Money spent vs the signed plan, by source", "Notes of days you covered an empty job because the job was empty"],
      good: ["You signed next month’s Growth plan before any Growth money was spent.", "Every Growth enquiry has a written source: digital, field, partner, or other.", "Marketing only talks about services and towns you have signed.", "Every High idea is go, no-go, or more research with a date, inside 10 working days.", "Cost per booking sits inside the signed plan.", "If one of those five jobs has nobody in it, you wrote that you are doing it today because nobody is in that job."],
      bad: ["Ads are running because someone thought they might work, and you did not sign that month.", "A town went live and Provider Operations cannot finish the first jobs there.", "Why people did not book sits only in a chat, with no reason written.", "You are writing the ads or standing at a stall while five boxes sit empty and nobody wrote that down.", "The weekly review lists only good news and hides what failed.", "A High idea still says “we might look at this later” after 10 working days."]
    },
    mkm: {
      story: "You run Panun Kaergar’s marketing. You write the monthly plan Head of Growth signs, you write the marketing kit, you split the money between paid ads and stalls, and you make sure Operations asked every new person where they found us and wrote the answer. Digital Marketing Manager runs paid ads, SEO (search), social, and videos. Field Marketing Manager runs the people who visit the market, get the first local workers to sign the papers on site, and write office and society contracts. You do not invent new services. You do not book the customer.",
      receives: [
        { from: "Head of Growth", what: "The signed monthly Growth plan, and any change in the middle of the month." },
        { from: "Market Intelligence", what: "What is working in the market, and what to stop guessing about — each fact with a source and a date." },
        { from: "Offer", what: "The only sentences Marketing may use for a live service." },
        { from: "Expansion", what: "Which towns we can actually serve this month." },
        { from: "Digital Marketing Manager", what: "A daily note when something moved, and a weekly report: money spent, enquiries, what worked, what failed." },
        { from: "Field Marketing Manager", what: "A weekly report: visits vs calendar, names to Sales, first local workers who signed, office and society contracts signed, towns that produced nothing, materials that need reprinting." }
      ],
      gives: [
        { to: "Digital Marketing Manager", what: "Their days, the ads budget, the marketing kit, which website pages SEO must cover this month, where a call, WhatsApp, app, or form must land, and what we will not advertise." },
        { to: "Field Marketing Manager", what: "Which towns, which days, which boards and flyers, the stall budget, which first-worker towns, which offices and societies, the signed papers, and what we will not advertise." },
        { to: "Operations", what: "The short list of source names they may write this month. They ask every new person where they found us — call, WhatsApp, app, form, or any other way — and write one of those names." },
        { to: "Sales", what: "What will arrive this week. Operations has already asked where each person found us." },
        { to: "Head of Growth", what: "The weekly marketing report: money spent, enquiries, cost, bookings, what worked, and what failed." }
      ],
      records: ["Monthly marketing plan signed by Head of Growth", "Day-by-day marketing calendar", "Marketing kit for the month", "Money tracker (ads budget and stall budget)", "SEO / search page list for the month", "List of source names Operations may write", "List of enquiries that arrived with no source", "List of live ads, search pages, stall days, first-worker towns, and office contracts", "Weekly marketing report", "Notes of days you covered Digital or Field because the job was empty"],
      good: ["Head of Growth signed the plan before the month started.", "95% of marketing enquiries name where they came from.", "Digital and Field work from the same calendar and the same marketing kit.", "No live ad or flyer uses a sentence you have not put in the kit.", "Money spent stays within 10% of the plan unless Head of Growth signed a change.", "If Digital or Field has nobody sitting in the job, that is written down as you doing that job."],
      bad: ["An ad says one thing and a flyer says another.", "Money is spent and Operations did not ask where the person found us.", "A flyer shows a price Finance never signed.", "A person has no written answer for how they found us.", "You are running the ads or standing at the stall while those jobs sit empty and nobody wrote that down.", "A weekly report that only lists good news."],
      detailed: {
        copy: {
          heroKicker: "What this job is",
          defTitle: "What this job is",
          lanesTitle: "This job has three parts",
          definition: "You run Panun Kaergar’s marketing. You write the monthly plan Head of Growth signs, you write the marketing kit (the instruction book of words, look, prices, towns, and faces), and you split the money between paid ads and stalls. You make sure Operations asked every new person how they found us and wrote the answer — that is how we know the source, not the phone number. Two people report to you: Digital Marketing Manager (paid ads, search, social, and videos) and Field Marketing Manager (stalls, first local workers, and office and society contracts). They do the daily work. You run the plan they work from.",
          owns: "These are the six things this job owns. Do not do Sales’ job, Digital’s job, or Field’s job unless that job is empty and you have written that you are doing that job.",
          lanes: "This job has one result. The work is in three parts. Part 1 is work you do yourself — the plan and the instruction book. Part 2 is Digital Marketing Manager. Part 3 is Field Marketing Manager. If Digital or Field is empty, you do that work today. Write that down.",
          responsibilities: "These are the six parts of the job. Click a row to open the full card.",
          handoffs: "Work arrives as a written file and leaves as a written file. A chat message is not the file. The chart shows the flow. Click a row to open the full card.",
          workflow: "Every working day, do these three things in order: open the signed plan, the marketing kit, the money spent, and the list of enquiries with no source; unblock Digital and Field if they are waiting on a yes; check that Operations asked every new person where they found us. In the last week of the month, write next month’s plan (MKT-01). If an ad or stall is broken, pause it the same day (MKT-06). Click a step to open the full card.",
          reporting: "You write three reports. Daily: money spent, new enquiries, missing sources, anything you paused, and whether you are doing Digital or Field work today because nobody is in that job. Weekly: paid ads, search (SEO), and field (visits, first local workers, contracts) shown separately — money, enquiries, cost, three things that worked, three that failed, one or two changes. Monthly: next month’s plan and calendar, for Head of Growth to sign. Click a row to open the full report.",
          standards: "The bar this job is measured against. Click a card to read the full standard.",
          kpis: "How Head of Growth knows this job is working. Each number has a target, a reason it matters, and a counting rule so two people cannot argue about the number.",
          escalations: "What to do when an ad might be untrue, money will go past the plan, Sales says the enquiries are poor quality, or Digital or Field has nobody in the job. Click a row to open the full steps."
        },
        hub: {
          icon: "calendar_month",
          line: "You write one monthly plan and one marketing kit. Digital runs paid ads, SEO, and social. Field runs visits, first local workers, and office and society contracts. Operations asks every person where they found us and writes the answer."
        },
        definition: {
          what: [
            { title: "You write the monthly plan and the marketing kit, and you split the money", why: "The plan names which services we may talk about, which towns we may work in, which days ads and stalls run, how much money each gets, which website pages Digital must keep true for search (SEO), and what we will not advertise. The marketing kit is the instruction book: logo, colours, sentences, prices, towns, and faces. Head of Growth signs the plan. You write it and run it." },
            { title: "You give Digital Marketing Manager and Field Marketing Manager one calendar and check they follow it", why: "Digital runs paid ads, SEO (the website and Google search so people find us without a paid ad), social media, and the videos. Field runs the people who visit the market, get the first local workers to sign the papers on site, and write office and society contracts. You do not become them. If nobody sits in one of those jobs, you do that job for now and write: you are doing it today because nobody is in that job." },
            { title: "You make sure Operations asked where each person found us", why: "Every new person — call, WhatsApp, app, website form, or any other way — is asked by Operations: How did you find us? Operations writes paid ad, search, stall, WhatsApp, app, form, or other, from a short list you gave them. That is how we know. Digital cannot see it from the phone number. Digital reads what Operations wrote. If Field met the person at a stall, Field also writes town and date. You bring the person in. Sales books the job." }
          ],
          why: [
            { title: "If nobody owns the plan, Digital and Field start acting like two companies", why: "A customer sees one promise in an ad and a different promise on a flyer. Money is spent with no owner. Head of Growth cannot tell what is working." },
            { title: "Writing the allowed words and going live are two different jobs", why: "You decide which sentences are allowed this month. Digital checks each video or post against that list, then publishes. Field uses only the boards and flyers you approved. If one person does both jobs without saying so, nobody owns the ads or the stalls." },
            { title: "The signed plan is the rule — not the ad account, and not a random Saturday stall", why: "We only advertise approved services, in towns we can actually serve, with money Head of Growth has signed. A new sentence, price, town, or face must wait for you. A change to the calendar in the middle of the month must wait for Head of Growth’s signature." }
          ]
        },
        glossary: [
          {
            id: "signed-kit",
            term: "Marketing kit",
            aliases: ["marketing kit", "signed marketing kit", "signed kit"],
            also: "The instruction book for ads, search pages, and stalls",
            meaning: "The instruction book for every ad, post, search page, flyer, and stall board this month. You write it and put your name and the month on it. It holds the logo, colours, tone, sentences that may appear, live services, signed prices, open towns, and faces that may appear. Digital and Field may use anything in this book without asking again. They may not add a new sentence, price, town, or face until you say yes in writing."
          },
          {
            id: "kit-change",
            term: "Change to the marketing kit",
            aliases: ["change to the marketing kit", "kit change", "kit changes"],
            meaning: "A new sentence, price, town, or face that is not already in this month’s marketing kit. Digital and Field must stop and write to you. You say yes or no in writing. If it is a brand-new service or town, you take it to Head of Growth first. The steps are in MKT-02."
          },
          {
            id: "signed-calendar",
            term: "Monthly marketing plan",
            aliases: ["monthly marketing plan", "signed calendar", "marketing calendar", "day-by-day calendar"],
            also: "The calendar for the month",
            meaning: "The written plan for the month, broken into days: which town, which service, paid ads or stall, which website pages SEO must cover, and how much money. You write it. Head of Growth signs it before any money is spent. Digital and Field do not write their own month. If the plan must change before the month ends, that is MKT-07."
          },
          {
            id: "ads-line",
            term: "Ads budget",
            aliases: ["ads budget", "ads line", "ads money"],
            meaning: "The amount of money on the monthly plan that Digital Marketing Manager may spend on paid ads (Meta, Google ads, and any other paid ads). Field stall money is not part of this number. SEO (search) is Digital’s work too, but search enquiries are counted on their own line — they are not mixed into this ads number. Spend must stay inside this budget."
          },
          {
            id: "field-line",
            term: "Stall budget",
            aliases: ["stall budget", "field budget", "field line", "field money"],
            meaning: "The amount of money on the monthly plan that Field Marketing Manager may spend on stall days, boards, and flyers. Digital may not take this money for ads."
          },
          {
            id: "tagged-lead",
            term: "Enquiry with a source",
            aliases: ["enquiry with a source", "enquiries with a source", "enquiry where Operations wrote how they found us", "enquiry where Operations wrote how they found us", "source tag", "source tags", "marketing enquiry", "marketing enquiries"],
            also: "Where the enquiry came from",
            meaning: "A person who asked about a service, and Operations asked them where they found us — call, WhatsApp, app, form, stall, or any other way — and wrote the answer: paid ad, search, stall, WhatsApp, app, form, or other. That written answer is the source. Digital reads it. Digital does not ask the customer. If Field met the person at a stall, Field also writes town and date. If Operations did not ask, that person goes on the missing-source list until they do."
          },
          {
            id: "exception-list",
            term: "List of enquiries with no source",
            aliases: ["list of enquiries with no source", "untagged-lead exception list", "exception list", "enquiry with no source", "enquiry with no source"],
            meaning: "The daily list of people Operations has not yet asked, or has not yet written an answer for. You own this list. Operations asks today. The goal is an empty list. The steps are in MKT-03."
          },
          {
            id: "will-not-do",
            term: "What we will not advertise this month",
            aliases: ["what we will not advertise this month", "will-not-do", "will not do this month"],
            meaning: "A written list on the monthly plan of services, towns, sentences, and tests that are not allowed this month. This stops Digital from ‘just testing’ an unapproved service or search page, and Field from putting up a stall on a random Saturday."
          },
          {
            id: "dmm",
            term: "Digital Marketing Manager",
            aliases: ["Digital Marketing Manager"],
            meaning: "The person who reports to you and runs all online work: paid ads, SEO (the website and Google search so people can find us without a paid ad), app store, social media, other sites, and the videos and posts. They do not ask the customer where they came from. Operations asks and writes the answer. Digital reads those answers to see which ads and pages work. They do not invent the marketing kit. They do not book the customer."
          },
          {
            id: "source-ask",
            term: "Operations asks where they found us",
            aliases: ["Operations asks where they found us", "How did you find us", "source question", "ask the source"],
            meaning: "Every new person — call, WhatsApp, app, website form, or any other way they arrived — is asked by Operations: How did you find us? Operations writes one name from the list you gave them: paid ad, search, stall, WhatsApp, app, form, or other. That is how we know the source. Digital cannot see it from the phone number. Digital reads what Operations wrote."
          },
          {
            id: "seo",
            term: "SEO (search)",
            aliases: ["SEO", "seo", "search", "organic search", "Google search"],
            meaning: "Website and Google search work so people can find Panun Kaergar without a paid ad. Digital Marketing Manager does this work. You put which services, towns, and website pages they must cover on the monthly plan. Search enquiries are counted on their own line. They are not mixed into the ads budget. Google Ads is paid ads, not SEO."
          },
          {
            id: "fmm",
            term: "Field Marketing Manager",
            aliases: ["Field Marketing Manager"],
            meaning: "The person who reports to you and holds three ground jobs: Field Visitor (full-time or someone we pay on a written contract) actually visits the market; First Provider Onboarding goes on site and signs the first service providers onto us, then hands them to Provider Operations; Office and Society Contracts writes maintenance contracts with offices and housing societies from the signed papers. They do not take bookings at a stall. They do not pick a new town. They do not sign hotel-desk referral partners — that is Partnerships."
          },
          {
            id: "hog",
            term: "Head of Growth",
            aliases: ["Head of Growth"],
            meaning: "The person you report to. They sign the monthly Growth plan. They say yes or no on a new service, a new town, and a new kind of hotel desk or shop. They read cost per enquiry and cost per booking. They do not run ads. They do not stand at stalls."
          },
          {
            id: "mkm",
            term: "Marketing Manager",
            aliases: ["Marketing Manager"],
            also: "This job — you",
            meaning: "This job. You write the monthly plan, the marketing kit, and the split of money. You give Digital and Field their written work and check the result. You make sure Operations asked every new person where they found us and wrote the answer. You do not book the customer."
          },
          {
            id: "acting-owner",
            term: "Doing that job today",
            aliases: ["doing that job today", "acting owner", "acting-owner"],
            also: "Sitting in for an empty job",
            meaning: "When nobody is sitting in Digital or Field, you do that job yourself for now. You write it on the daily note: you are doing it today because nobody is in that job. If you do the work without writing that down, Head of Growth thinks the job is filled, and nobody owns the result."
          }
        ],
        responsibilities: [
          {
            kicker: "Responsibility 1",
            title: "Write the monthly plan and the day-by-day calendar",
            lead: "Name which services, which towns, which days, how much money for ads, how much money for stalls, and what we will not advertise. Head of Growth signs before any money is spent or any stall is set up. Digital and Field do not write their own month.",
            art: "cmc-h-kit.png",
            body: [
              "In the last week of the month, read what Intelligence found and last month’s marketing report. Choose only services Head of Growth has already approved, and only towns Expansion has already opened. Write how much ads may spend and how much stalls may spend. Write the calendar. Write a list of what we will not advertise. Get Head of Growth’s signature. Give both managers their written work the same day.",
              "A calendar with no target is decoration. A plan that says yes to everything is not a plan. If the month must change later, that is a signed change (MKT-07) — not a chat to Digital saying ‘just try it’."
            ],
            points: [
              { title: "Signed before money moves", why: "No ad spend and no stall until Head of Growth has signed." },
              { title: "Write what we will not advertise", why: "This stops a ‘test’ that invents a second company." },
              { title: "One calendar for both", why: "If Digital and Field work different months, customers hear two companies." }
            ],
            meta: [
              ["You take from", "Head of Growth’s signed Growth plan; Intelligence; last month’s marketing report; Offer’s allowed sentences; Expansion’s open towns"],
              ["You give to", "Digital and Field (their written work); Head of Growth (the plan to sign); Sales (how enquiries will arrive)"]
            ]
          },
          {
            kicker: "Responsibility 2",
            title: "Own the marketing kit — logo, colours, sentences, prices, towns, and faces",
            lead: "You write the instruction book. If Digital or Field wants a new sentence, price, town, or face, they stop until you say yes in writing. Digital then checks each video or post against that book. You do not re-check every post.",
            art: "cmc-h-yes.png",
            body: [
              "The marketing kit is the instruction book. Digital and Field may use anything in it without asking again. A new sentence, price, town, or face must wait (MKT-02). Check Offer, Finance, and Expansion. If it is a brand-new service or town, take it to Head of Growth. Then add it to the kit, put the date on it, and tell both managers.",
              "You do not sit in the ads account approving every AI post. That yes belongs to Digital Marketing Manager. You own whether the sentence is allowed this month."
            ],
            points: [
              { title: "One promise", why: "A flyer and an ad must not disagree." },
              { title: "A no is written", why: "Silence is not a no. They keep doing the other work that is already allowed." },
              { title: "Do not take Digital’s yes", why: "A video or post enters the company library when Digital says yes against this kit." }
            ],
            meta: [
              ["You take from", "Offer’s sentences; Finance’s prices; Expansion’s towns; a written ask from Digital or Field"],
              ["You give to", "The dated marketing kit; both managers; Head of Growth if it is a new service or town"]
            ]
          },
          {
            kicker: "Responsibility 3",
            title: "Split the money — ads budget and stall budget — and watch it",
            lead: "Money needs one owner. Digital spends the ads budget on paid ads. Field spends the stall budget. They cannot take each other’s money. SEO (search) is Digital’s work too, but those enquiries sit on their own line — not inside ads spend.",
            art: "cmc-h-shot.png",
            body: [
              "Give each manager a number and a weekly target. Every working day, look at money spent against the plan. If spend is running too fast, pause that work the same day (MKT-06) and tell Head of Growth before it breaks the month.",
              "Staying within 10% of the plan is the bar, unless Head of Growth signs a change. Search (SEO) results are not mixed into the ads budget. A missed stall is not ‘saved money’ unless you write it down."
            ],
            points: [
              { title: "Two budgets, one owner", why: "Otherwise each side takes from the other." },
              { title: "Pause before the money is gone", why: "Asking after the money is spent is too late." },
              { title: "Return unused money honestly", why: "Field must not hide unused budget for a later stall that is not on the plan." }
            ],
            meta: [
              ["You take from", "The signed plan; Digital’s daily spend; Field’s stall and print costs"],
              ["You give to", "Each manager their budget; Head of Growth if a change is needed"]
            ]
          },
          {
            kicker: "Responsibility 4",
            title: "Make sure Operations asked where each person found us",
            lead: "Operations asks every new person — call, WhatsApp, app, form, or any other way — ‘How did you find us?’ and writes the answer from a short list you gave them. Digital does not ask the customer. Digital cannot see this from the phone number. Digital reads what Operations wrote.",
            art: "cmc-h-note.png",
            body: [
              "Give Operations a short list of names they may write: paid ad, search, stall, WhatsApp, app, form, or other. Every new person is asked by Operations, and the answer is written the same day. You own the list of people with no answer (MKT-03). If it is blank, Operations asks today. Digital uses these answers to count ads vs search. Digital does not ask the customer. If Field met the person at a stall, Field also writes town and date.",
              "Enquiries from partners are not yours to label as marketing. Partnerships owns that path. You bring people to Sales. You do not book them."
            ],
            points: [
              { title: "Operations asks. Operations writes.", why: "That is how we know. Not the phone number, and not Digital guessing later." },
              { title: "If it is blank, Operations asks today", why: "A missing answer makes the weekly report untrue." },
              { title: "Do not close the chat", why: "Hand the person to Sales." }
            ],
            meta: [
              ["You take from", "Sales (enquiries with no source); Digital and Field (their source notes)"],
              ["You give to", "Sales (the corrected source); Head of Growth (only if an ad, search page, or stall had to pause)"]
            ]
          },
          {
            kicker: "Responsibility 5",
            title: "Hold the Digital job and the Field job",
            lead: "Coach both managers against their one result. If nobody sits in a job, you do that work and write that you are doing it today because nobody is in that job. You do not quietly become the ads person or the stall person.",
            art: "cmc-r-library.png",
            body: [
              "Digital’s result: paid ads, SEO (search), and the rest of online follow the signed plan. Operations asks people from those ads and pages where they found us. Digital reads those answers — paid ad or search, not mixed together. Field’s result: people who enquire at named towns on the calendar reach Sales the same day. Give them their written work the day the plan is signed (MKT-05). Unblock a waiting yes or a stuck town the same day.",
              "If you run the ads or stand at the stall because nobody is in that job, write on the daily note that you are doing that job today. Two weeks of a missed result, or an empty job that nobody named, goes to Head of Growth."
            ],
            points: [
              { title: "The brief is a written file", why: "Days, budget, marketing kit, which website pages SEO must cover, where calls, WhatsApp, app, or forms land, and what we will not advertise. A chat message is not the brief." },
              { title: "Doing that job today is written", why: "A job with no name has no owner." },
              { title: "Do not skip to Content Maker", why: "Content Maker reports to Digital Marketing Manager. You give Digital their written work and check the result." }
            ],
            meta: [
              ["You take from", "Each manager’s daily note and weekly report"],
              ["You give to", "Each manager (brief, yes or no, pause); Head of Growth (empty job or two-week miss)"]
            ]
          },
          {
            kicker: "Responsibility 6",
            title: "Send honest numbers up — what worked and what failed, for paid ads, search, and stalls",
            lead: "A report with only good news is how bad spend hides. Each week write: money spent, enquiries by paid ads, by search (SEO), and by stalls, cost, how many named their source, bookings from marketing, three things that worked, three that failed, and one or two changes.",
            art: "cmc-p-weekly.png",
            body: [
              "Take Digital’s weekly report and Field’s weekly report the day before. Do not hide a dead ad inside ‘marketing did fine’. Do not hide a town that produced nothing inside ‘field was busy’. Head of Growth needs this page for the Growth review.",
              "If people keep asking for something we do not sell, send that to Intelligence as a signal. Do not add it to the calendar yourself. Partner numbers do not belong in this report as if they were yours."
            ],
            points: [
              { title: "Paid ads, search, and stalls shown separately", why: "A dead Google ad must not hide inside a good stall week. A search enquiry must not hide inside ads spend." },
              { title: "Name what failed", why: "A report with only wins is not a management system." },
              { title: "One or two changes", why: "A list of ten wishes is not a plan." }
            ],
            meta: [
              ["You take from", "Digital’s weekly report; Field’s weekly report; Sales bookings from marketing enquiries"],
              ["You give to", "Head of Growth; Digital and Field (what to keep, pause, or change); Sales (what will arrive next week)"]
            ]
          }
        ],
        handoffs: [
          {
            side: "in",
            kicker: "You receive",
            title: "The signed Growth plan from Head of Growth",
            lead: "Who we sell to, which services are approved, which towns are open, how money is split, the cost limits, and what we will not do. No money is spent until this is signed.",
            art: "cmc-h-kit.png",
            body: [
              "This file makes the month legal. You turn it into the marketing calendar and the marketing kit. You do not add a service or a town that is not on it.",
              "If it arrives as a chat message, ask Head of Growth to put it in the signed plan."
            ],
            points: [
              { title: "It must name", why: "Who we sell to, the service list, the town list, ads vs stalls with money and targets, cost limits, what we will not do, signature, and date." },
              { title: "If it has not arrived", why: "Do not invent the month. Write what is missing. Wait. Spend nothing." }
            ],
            meta: [
              ["From", "Head of Growth"],
              ["When", "Last week of the previous month, and whenever they issue a change"],
              ["What you do", "Write the marketing calendar and the marketing kit. Give Digital and Field their written work the same day."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "What we may say, and where we may say it",
            lead: "Offer & Service Development Manager writes the only sentences for a live service. Market Expansion Manager writes which towns we can serve. Market Intelligence Manager writes what to stop guessing about.",
            art: "cmc-h-files.png",
            body: [
              "A draft service is not something we can advertise. Circling a town on a map is not opening it. A rumour is not a calendar line. Put only cleared items on the plan. If people keep asking for something we do not sell, send that back to Intelligence as a signal. Do not invent an ad from it."
            ],
            points: [
              { title: "From Offer and Finance", why: "The sentences and prices the marketing kit may use." },
              { title: "From Expansion", why: "Towns that have providers. No stall and no ad in a town we cannot serve." }
            ],
            meta: [
              ["From", "Offer, Expansion, Intelligence"],
              ["When", "Before you write the month, and when someone written requests for a new sentence, price, town, or face"],
              ["What you do", "Build the marketing kit and the calendar. Take a new service or town to Head of Growth."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Digital Marketing Manager’s daily note and weekly report",
            lead: "A daily note when spend jumped, tracking broke, they paused an ad, or files went live. A weekly report split by Meta, Google, search, social, and other: money spent, enquiries, three things that worked, three that failed, one written request.",
            art: "cmc-p-weekly.png",
            body: [
              "This becomes the online page of your weekly report. What failed must be named. If nobody sits in the Digital job, you write that you are doing that job, and you pull these numbers from the ads account yourself — or Head of Growth cannot see the result."
            ],
            points: [
              { title: "Each online place on its own row", why: "Meta, Google, search, social, other — not one lump called ‘digital’." },
              { title: "If the report is late", why: "Write how many days it is late. Do not invent Digital’s numbers." }
            ],
            meta: [
              ["From", "Digital Marketing Manager"],
              ["When", "Daily when something moved; weekly the day before your report"],
              ["What you do", "Keep, pause, or change that ad. Put the numbers into your weekly marketing report."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Field Marketing Manager’s weekly field report",
            lead: "Weekly: visit days vs the calendar, names to Sales, first local workers who signed and handed to Provider Operations, office and society contracts signed, towns that produced nothing, materials that need reprinting.",
            art: "cmc-h-note.png",
            body: [
              "This becomes the field page of your weekly report. A missed visit is a missed town. A town with no names, no first local workers, and no contract after three visits must be named so next month can change. Phone numbers go to Sales, not to you."
            ],
            points: [
              { title: "The count, not the phone list", why: "Sales owns the customer. You own whether the town worked." },
              { title: "A town that produces nothing", why: "Do not keep standing there to look busy. Take it off next month’s calendar, or take it to Head of Growth." }
            ],
            meta: [
              ["From", "Field Marketing Manager"],
              ["When", "Same day (the count); weekly the day before your report"],
              ["What you do", "Keep, pause, or drop a town. Put the numbers into your weekly marketing report."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Which marketing enquiries became bookings, and why the rest did not",
            lead: "Sales sends back results on the people Digital and Field sent them, so next week’s calendar is not a guess.",
            art: "cmc-h-shot.png",
            body: [
              "An enquiry is not the result. A booking is. The reasons people did not book tell you which ad or which town to pause. Two weeks of poor-quality enquiries is a problem to escalate, not a shrug. Do not invent a new service from a lost enquiry — send the pattern to Intelligence."
            ],
            points: [
              { title: "Use it in the weekly report", why: "Bookings from marketing enquiries, if Sales has closed them." }
            ],
            meta: [
              ["From", "Sales"],
              ["When", "As they close bookings, rolled up for the weekly report"],
              ["What you do", "Keep, pause, or change an ad or a stall. Do not book the customer yourself. Send them to Sales and book the person yourself."]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "The month’s written work to Digital Marketing Manager",
            lead: "Their days, the ads budget, the marketing kit, which website pages SEO must cover this month, where a call, WhatsApp, app, or form must land, and what we will not advertise. Same day the plan is signed.",
            art: "cmc-r-library.png",
            body: [
              "This is MKT-05 for online work. Digital may use anything in the marketing kit without asking again. A new sentence comes back to you as a request to change the kit. They do not write their own month."
            ],
            points: [
              { title: "The file must name", why: "Towns, services, ads budget and limit, marketing kit, which website pages SEO must cover, where calls, WhatsApp, app, or forms land, what we will not advertise." }
            ],
            meta: [
              ["To", "Digital Marketing Manager"],
              ["When", "The same day Head of Growth signs, and whenever the plan changes"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "The month’s written work to Field Marketing Manager",
            lead: "Which towns, which days, which boards and flyers, the stall budget, which first-worker towns, which offices and societies, the signed papers, and what we will not advertise. Same day the plan is signed.",
            art: "cmc-r-consent.png",
            body: [
              "This is MKT-05 for field work. Field does not pick a new town. Boards, first-worker papers, and office contracts must match the current kit and the signed papers. Stalls collect names for Sales. First local workers go to Provider Operations. Offices and societies get a written contract. Field does not take bookings or money at a stall. Hotel desks and shops stay with Partnerships."
            ],
            points: [
              { title: "The file must name", why: "Town, day, pitch, current boards and flyers, stall budget, first-worker towns and pack, offices and societies and contract pack, what we will not advertise." }
            ],
            meta: [
              ["To", "Field Marketing Manager"],
              ["When", "The same day Head of Growth signs, and whenever the plan changes"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "The source names Operations may write this month",
            lead: "Paid ad, search, stall, WhatsApp, app, form, or other. Operations asks every new person where they found us and writes one of these names. Same day the plan is signed.",
            art: "cmc-h-note.png",
            body: [
              "This is how we know the source. Not the phone number. Not Digital guessing later. If a new person arrives with no answer, Operations asks today."
            ],
            points: [
              { title: "The file must name", why: "The short list of answers Operations may write, and that they ask on every call, WhatsApp, app, form, or other way a person arrives." }
            ],
            meta: [
              ["To", "Operations"],
              ["When", "The same day Head of Growth signs, and whenever the list of names changes"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Daily marketing note and the list of enquiries with no source",
            lead: "When spend jumped, an ad, search page, or stall paused, a source is missing, or you are doing Digital or Field work today because nobody is in that job. Short. Written. The goal is an empty missing-source list.",
            art: "cmc-h-note.png",
            body: [
              "Catch a leak the same day — not in next week’s meeting. The note must have: date, money spent against the plan (ads and stalls separately), new enquiries from paid ads, from search (SEO), and from stalls, missing sources still open, anything paused, and whether you are doing Digital or Field work today because nobody is in that job."
            ],
            points: [
              { title: "No note is fine if nothing moved", why: "If nothing moved and every enquiry has a source, you do not invent a report. If something moved, you do not skip it." }
            ],
            meta: [
              ["To", "File it. Send to Head of Growth only if an ad, search page, or stall paused, or spend will go past the plan."],
              ["When", "Every working day when something moved"],
              ["Copy", "Sales gets only the corrected source notes — not the commentary"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Weekly marketing report to Head of Growth",
            lead: "Money spent against the plan, ads and stalls shown separately. Enquiries by paid ads, by search (SEO), and by stalls. How many named their source, bookings from marketing, three things that worked, three that failed, one or two changes. The day before the Growth review.",
            art: "cmc-p-weekly.png",
            body: [
              "This is the marketing page of Head of Growth’s weekly review. Digital’s report is the online page. Field’s report is visits, first local workers, and contracts. What failed must be named. Not a voice note."
            ],
            points: [
              { title: "The report must name", why: "Week dates, money vs plan by ads and stalls, enquiries by paid ads, by search (SEO), and by stalls, percent with a source, bookings, anything off plan, next week’s changes, who owns each change." }
            ],
            meta: [
              ["To", "Head of Growth"],
              ["When", "The same weekday every week, the day before the Growth review"],
              ["Also", "Digital and Field get what to keep, pause, or change. Sales gets what will arrive next week."]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Yes or no on a new sentence, price, town, or face",
            lead: "A written yes updates the marketing kit. A written no keeps that ad or flyer stopped. Other work that is already allowed keeps going.",
            art: "cmc-e-stop.png",
            body: [
              "Do not leave this ask unanswered overnight without writing that it is still waiting. If it is a brand-new service or town, the yes comes from Head of Growth through you — not from Digital going around you."
            ],
            points: [
              { title: "A yes", why: "Update the marketing kit with the date. Tell both managers." },
              { title: "A no", why: "That ad or flyer stays paused. Write the reason." }
            ],
            meta: [
              ["To", "The manager who asked. Copy the other manager if the words they use would also change."],
              ["When", "The same working day if you can; the same week always"]
            ]
          }
        ],
        workflow: [
          {
            kicker: "MKT-03 · Step 1",
            title: "Open the signed plan, the marketing kit, the money spent, and the list of enquiries with no source",
            lead: "Before you unblock anyone, know what the month allows: which towns, which services, which sentences, how much ads money, how much stall money, and which enquiries still have no source.",
            art: "cmc-h-kit.png",
            body: [
              "At the start of the working day, open this month’s marketing kit, the signed calendar, money spent against both budgets, and the list of enquiries that arrived with no source. Mark which ads and stall days may run today, and which must pause.",
              "If Head of Growth has not signed the month, write that to them and wait. Do not invent a town, a service, or a budget."
            ],
            points: [
              { title: "Plan and marketing kit", why: "Sentences, prices, towns, faces, look, and what we will not advertise." },
              { title: "Money spent", why: "Ads budget and stall budget, shown separately." },
              { title: "What you produce", why: "A marked day: run or pause. Not a new campaign yet." }
            ]
          },
          {
            kicker: "MKT-05 · Step 2",
            title: "Unblock Digital and Field",
            lead: "Answer their asks for a new sentence, price, town, or face. Confirm today’s calendar. Do not let a waiting yes sit in chat.",
            art: "cmc-h-yes.png",
            body: [
              "Open Digital’s daily note and Field’s stall or office note. Allow or refuse a change to the marketing kit (MKT-02). If a town is not ready, take those stall days off. If Content Maker is blocking Digital, you hold Digital — you do not skip to production.",
              "If nobody sits in Digital or Field today, write that you are doing that job today, then do that job’s same-day checks yourself."
            ],
            points: [
              { title: "Ask to change the kit", why: "Yes updates the kit. No keeps that ad or flyer stopped." },
              { title: "Empty job", why: "Write that you are doing that job, then do the work of that job." },
              { title: "Stop and take it up when", why: "The ask is a brand-new service or town — that is Head of Growth, through you." }
            ]
          },
          {
            kicker: "MKT-03 · Step 3",
            title: "Check that Operations asked where each new person found us",
            lead: "Same day. Call, WhatsApp, app, form, stall, or any other way. Operations asked ‘How did you find us?’ and wrote paid ad, search, stall, WhatsApp, app, form, or other. If the answer is missing, Operations asks today.",
            art: "cmc-h-note.png",
            body: [
              "Look at new people who called, messaged on WhatsApp, used the app, or filled a form. Operations must have asked where they found us and written the answer. Close yesterday’s missing-source rows. If spend jumped or you paused something, write the daily note before the day ends.",
              "Do not book the person. Do not keep talking to the customer in a comment or chat yourself Digital or Field started."
            ],
            points: [
              { title: "What you produce", why: "Every new person has an answer Operations wrote, and a daily note if something moved." },
              { title: "What you do not produce", why: "A booking. That is Sales." }
            ]
          },
          {
            kicker: "MKT-01 · If it is the last week of the month",
            fork: "yes",
            title: "Write next month. Get Head of Growth to sign. Give both managers their written work",
            lead: "No money is spent in the new month until this is signed. Digital and Field do not write their own calendar.",
            art: "cmc-p-month.png",
            body: [
              "Read Intelligence and this month’s wins and fails. Choose approved services and towns. Split ads budget and stall budget. Write which website pages SEO must cover. Write the marketing kit, or confirm last month’s kit still stands. Write what we will not advertise. Get the signature. Give Digital and Field their written work the same day (MKT-05). Give Operations the list of source names they may write."
            ],
            points: [
              { title: "When", why: "Last week of the month, before spend starts." },
              { title: "What you produce", why: "A signed plan, a marketing kit, two written briefs — or a wait if Head of Growth has not signed." }
            ]
          },
          {
            kicker: "MKT-06 · If a source is missing, money is running too fast, or a sentence might be untrue",
            fork: "no",
            title: "Pause that ad, search page, or those stall days the same day",
            lead: "Do not keep spending while you ‘just look at it’. Head of Growth hears before the plan breaks or an untrue sentence stays live.",
            art: "cmc-e-stop.png",
            body: [
              "Stop that ad, search page, or those stall days. Write why and the time. Tell the manager, and tell Sales if enquiries will stop arriving. If money will go past the plan, or a sentence might be untrue, take the file down and tell Head of Growth before more money goes out or the file stays live."
            ],
            points: [
              { title: "You do", why: "Pause only that work. Keep the rest of the signed plan running." },
              { title: "You send", why: "The written pause, to the manager, and — if money or brand is at risk — to Head of Growth." }
            ]
          }
        ],
        reporting: [
          {
            period: "Daily",
            kicker: "MKT-R3",
            title: "Daily marketing note and list of enquiries with no source",
            art: "cmc-h-note.png",
            when: "Every working day if spend jumped, an ad, search page, or stall paused, a source is missing, or you are doing Digital or Field work today because nobody is in that job. Short. Written. The goal is an empty missing-source list.",
            lead: "Catch a leak the same day — not in next week’s meeting.",
            body: [
              "This note exists so Head of Growth never discovers a pause by accident, and so Sales never sits overnight on a marketing enquiry with no source."
            ],
            contents: [
              { title: "Money spent today and this month, against ads budget and stall budget", why: "The signed plan is the rule, not the ad account and not a stall." },
              { title: "New people, and the answer Operations wrote: paid ad, search, stall, WhatsApp, app, form, or other", why: "If Operations did not ask, that person goes on the missing-source list today." },
              { title: "Missing-source rows still open, who will fix them, by when today", why: "Same day or it did not happen." },
              { title: "Anything paused, and why", why: "A pause is a written decision, not a mood." },
              { title: "Are you doing Digital or Field work today because nobody is in that job? yes or no", why: "A job with no name has no owner." }
            ],
            mustHave: ["Date", "Money vs plan for ads and for stalls", "New enquiries from paid ads / search / stalls", "Missing sources still open", "Pauses", "Covering Digital or Field, or both jobs filled"],
            submit: [
              { to: "File", why: "Every working day when something moved." },
              { to: "Sales", why: "Only the corrected source notes — not the commentary." },
              { to: "Head of Growth", why: "Only if an ad, search page, or stall had to pause, or spend will go past the plan." }
            ]
          },
          {
            period: "Weekly",
            kicker: "MKT-R1",
            title: "Weekly marketing report",
            art: "cmc-p-weekly.png",
            when: "The same weekday every week, the day before Head of Growth’s Growth review. Must name what failed, not only what worked.",
            lead: "Show whether the signed calendar produced enquiries inside budget — with a source on each — and name one or two changes for next week.",
            body: [
              "Digital’s weekly report is the online page. Field’s weekly report is visits, first local workers, and contracts. Do not hide a dead ad, a dead search page, or a town that produced nothing inside one lump called marketing."
            ],
            contents: [
              { title: "Money spent vs plan, ads and stalls shown separately", why: "A gap with no name is spend nobody owns." },
              { title: "Enquiries by paid ads, by search (SEO), and by stalls", why: "So we can stop what is dead. Do not mix search into ads." },
              { title: "Percent of enquiries that name their source (target 95% or more)", why: "Sales cannot work a nameless lead." },
              { title: "Bookings from marketing enquiries, if Sales has closed them", why: "Enquiries are not the result. Bookings are." },
              { title: "Three things that worked, three that failed, one or two changes", why: "A list of ten wishes is not a plan." }
            ],
            mustHave: ["Week dates", "Money vs plan for ads and stalls", "Enquiries by paid ads / search / stalls", "Percent with a source", "Bookings from marketing", "Anything off plan", "Next week’s changes", "Who owns each change"],
            submit: [
              { to: "Head of Growth", why: "This is the marketing page of the weekly Growth review." },
              { to: "Digital Marketing Manager", why: "What to keep, pause, or change in paid ads and in SEO (search)." },
              { to: "Field Marketing Manager", why: "Which towns and which days." },
              { to: "Sales", why: "What will arrive next week and how it will be labelled." }
            ]
          },
          {
            period: "Monthly",
            kicker: "MKT-R2",
            title: "Monthly marketing plan and calendar",
            art: "cmc-p-month.png",
            when: "Last week of the month, before Head of Growth signs. No money is spent until this is signed.",
            lead: "Turn Head of Growth’s signed Growth plan into days, a marketing kit, budgets, and targets Digital and Field can run without inventing a different company.",
            body: [
              "Use Digital’s next-month list and Field’s town list. Close dead ads, dead posts, and town that produced nothing. Do not start spend until the new month is signed. Give both managers their written work the same day (MKT-05)."
            ],
            contents: [
              { title: "For each line: who we sell to, which service, which town, paid ads, search (SEO), or stall, money, and what ‘good’ looks like", why: "A calendar with no target is decoration. Search pages sit on their own line, not inside ads." },
              { title: "Marketing kit for the month, or confirmation last month’s kit still stands", why: "No live ad or flyer without sentences you have signed." },
              { title: "What we will not advertise", why: "Stops Digital ‘testing’ an unapproved service." },
              { title: "Stall days named: town, date, boards and flyers", why: "Stalls on random Saturdays that are not on the plan cannot be measured." },
              { title: "Check that every online line lands in a place Sales already uses", why: "Spend with no path to Sales is wasted money." }
            ],
            mustHave: ["Month", "Day-by-day calendar", "Ads budget and stall budget", "SEO / search pages for the month", "Kit date", "What we will not advertise", "Where online enquiries land", "Head of Growth signature"],
            submit: [
              { to: "Head of Growth", why: "To sign. Spend does not start without this." },
              { to: "Digital and Field", why: "Their working calendar the same day it is signed." },
              { to: "Sales", why: "How enquiries will arrive and how they will be labelled." }
            ]
          }
        ],
        standards: [
          {
            title: "Head of Growth has signed the plan before the month starts",
            lead: "Every month has a signature before any money is spent or any stall is set up.",
            art: "cmc-h-kit.png",
            body: [
              "If the signature is late, Digital and Field wait. You do not invent a week ‘to keep momentum’."
            ],
            points: [
              { title: "What good looks like", why: "A stranger can open the plan and see which services, which towns, paid ads, search (SEO), or stall, money, what ‘good’ looks like, what we will not advertise, and the date it was signed." }
            ]
          },
          {
            title: "95 percent or more of marketing enquiries have an answer Operations wrote",
            lead: "If Operations did not ask where they found us, that person stays on the missing-source list until they do.",
            art: "cmc-h-note.png",
            body: [
              "Call, WhatsApp, app, form, stall, or any other way: Operations asked ‘How did you find us?’ and wrote paid ad, search, stall, WhatsApp, app, form, or other. The missing-source list should be empty."
            ],
            points: [
              { title: "What good looks like", why: "Every new person has a written answer from Operations. Nobody is guessing from the phone number." }
            ]
          },
          {
            title: "No live ad or flyer uses a sentence you have not put in the marketing kit",
            lead: "You write the kit. Digital checks each video or post against it. Field uses only the approved boards and flyers. One promise.",
            art: "cmc-h-yes.png",
            body: [
              "A new sentence, price, town, or face waits for MKT-02. A flyer with last year’s price is a brand problem, not a print problem."
            ],
            points: [
              { title: "What good looks like", why: "Every live ad and every board traces to a dated line in the marketing kit." }
            ]
          },
          {
            title: "Money spent stays within 10 percent of the signed plan unless Head of Growth signs a change",
            lead: "The signed month is the rule. Pause before the money is gone (MKT-06). A change in the middle of the month is MKT-07.",
            art: "cmc-h-shot.png",
            body: [
              "Ads budget and stall budget are separate. Website and social results are not mixed into ads. Unused stall budget is returned in writing."
            ],
            points: [
              { title: "What good looks like", why: "Money spent this month vs the plan is on the daily note whenever spend moved." }
            ]
          },
          {
            title: "Digital and Field work from the same calendar, and an empty job is named",
            lead: "If two managers have no owner, they fight each other. If a job is empty and nobody writes that down, you become the whole company by accident.",
            art: "cmc-r-library.png",
            body: [
              "If you run ads or stand at a stall, write that day that you are doing that job today. Content Maker reports to Digital. You give Digital their written work and check the result."
            ],
            points: [
              { title: "What good looks like", why: "A new person can name who owns Digital and who owns Field, or can see on the daily note that you are doing one of those jobs today." }
            ]
          }
        ],
        kpis: [
          {
            name: "Monthly plan signed before spend starts",
            target: "100% of months",
            why: "With no signed plan, Digital and Field invent their own month.",
            how: "A marketing plan file exists with Head of Growth’s signature dated before the first spend or first stall of that month."
          },
          {
            name: "Marketing enquiries that name where they came from",
            target: "95% or more",
            why: "Enquiries with no source cannot be improved, and Sales cannot work them well.",
            how: "Count people Operations asked and wrote an answer for, divided by all new marketing people that week. A blank answer that Operations has not yet asked counts against the rate. Partner enquiries are not in this number."
          },
          {
            name: "Money spent vs the signed plan",
            target: "Within 10%",
            why: "Spending more than the plan without a new signature is spend nobody authorised.",
            how: "Money spent this month on ads vs the ads budget, and on stalls vs the stall budget, reported separately and as a total. A change Head of Growth signed resets the number."
          },
          {
            name: "Cost of each enquiry",
            target: "Inside the number on the signed plan",
            why: "Cheap enquiries that never book are still waste.",
            how: "Money spent on paid ads divided by enquiries from paid ads, and money spent on stalls divided by enquiries from stalls, compared with the limit on the signed plan. Search (SEO) enquiries are counted, but they are not mixed into the ads cost."
          },
          {
            name: "Weekly report that names what failed, not only what worked",
            target: "100% on time",
            why: "Head of Growth needs numbers, not a story. A report with only good news hides leaks.",
            how: "A written report exists the working day before the Growth review, with three things that worked, three that failed, one or two changes, and paid ads, search (SEO), and stalls shown separately."
          },
          {
            name: "Days with no Digital or Field person written as you doing that job",
            target: "100%",
            why: "A job with no name has no owner. Then you have no one whose result you can check.",
            how: "Each working day a Digital or Field job has no person, the daily note says you are doing that job. Missing that line is a miss."
          }
        ],
        escalations: [
          {
            title: "An ad or flyer might be untrue or off brand",
            lead: "Trust dies when an ad or a flyer promises something the job cannot do.",
            art: "cmc-e-stop.png",
            body: [
              "Take it down. Show the file. Tell Head of Growth before it stays live. Do not argue it in comments. Digital or Field pauses that work (MKT-06)."
            ],
            points: [
              { title: "You do", why: "Take down the live ad or board. Write what went out and where." },
              { title: "You send to", why: "Head of Growth the same day, with the file." }
            ]
          },
          {
            title: "Spend will go past the plan",
            lead: "Asking after the money is gone is too late.",
            art: "cmc-h-kit.png",
            body: [
              "Pause that ad or those stall days (MKT-06). Show the plan, what has been spent, and the ask. Do not let Digital or Field keep spending while you wait for a meeting."
            ],
            points: [
              { title: "You do", why: "Pause. Write that it did not happen." },
              { title: "You send to", why: "Head of Growth before more money goes out." }
            ]
          },
          {
            title: "Sales says marketing enquiries are poor quality for two weeks in a row",
            lead: "Enquiries that never book are not growth. Two weeks is a pattern, not a bad day.",
            art: "cmc-h-note.png",
            body: [
              "Pull sample enquiries, where they came from, and why Sales lost them. Pause the dead ad or town that produced nothing. Hand the pattern to Intelligence. Tell Head of Growth what you will change — one or two changes, not ten."
            ],
            points: [
              { title: "You do", why: "Pause the dead work. Do not invent a new service." },
              { title: "You send to", why: "Head of Growth, with samples and the change. Intelligence gets the pattern." }
            ]
          },
          {
            title: "Digital or Field has missed their result for two weeks, or the job is empty and nobody wrote that down",
            lead: "The organisation chart is the business. People sit in jobs. Jobs do not follow people.",
            art: "cmc-p-weekly.png",
            body: [
              "If the job is empty, write that you are doing that job. Do the same-day checks yourself. Put the miss in the weekly report: numbers, what you tried, what you need. Do not quietly become the ads person or the stall person without saying so."
            ],
            points: [
              { title: "You do", why: "Name the job. Cover it if empty. Keep the other manager running." },
              { title: "You send to", why: "Head of Growth in the weekly report." }
            ]
          }
        ]
      }
    },
    hom: {
      story: "You own all of Panun Kaergar online — ads, search, the app, social, other sites, and the videos that feed them. Marketing Manager gives you the month. You send back a written report of what worked and what failed, and you count how many people Operations marked as coming from your ads or search pages. You do not invent the offer. You do not book the customer.",
      receives: [
        { from: "Marketing Manager", what: "Towns, services, ads money, the signed kit (words and look), where a lead must land, and what we will not do this month." },
        { from: "Sales", what: "Which digital leads booked, and why the rest were lost." },
        { from: "Operations", what: "Raw customer-feedback and provider videos for the Content Maker to edit." },
        { from: "CEO / founder", what: "Personal brand films, when they are the face." },
        { from: "Technology", what: "Access to the website and app so search work can happen, and tracking that a test lead really lands in Sales." },
        { from: "Content Maker", what: "Finished files, filed the same day." }
      ],
      gives: [
        { to: "Sales", what: "Digital enquiries the same day: named online place, campaign or post, service, town. Operations has already written how they found us." },
        { to: "Marketing Manager", what: "Daily note if spend jumped or you paused. Weekly written report by named online place (Meta, Google, search, social, other), with 3 wins, 3 fails, and one written request." },
        { to: "Intelligence", what: "What people keep asking that we do not sell — as a signal, not as a new ad." },
        { to: "Content Maker", what: "File list, the kit, Operations footage when it arrives, and which files ads and social need next." },
        { to: "Sales / CX", what: "Angry comments about a job — do not fight in public." }
      ],
      records: ["This month’s marketing kit", "Ads calendar", "Spend log", "Tracking checklist", "Content library", "File lists and Operations footage log", "Digital enquiry sources", "Daily notes", "Weekly digital report"],
      good: ["Every paid ad had a dummy enquiry reach Operations.", "Operations asked people where they found us. You counted those answers.", "The library has today’s approved files.", "The weekly written report names fails, not only wins.", "Customer and provider films were edited from Operations footage.", "New claims waited for Marketing Manager.", "If Content Maker is empty, it is written that you are doing it today because nobody is in that job."],
      bad: ["A private boost.", "A price that Finance did not sign.", "Sitting on a chat because you ‘already started it’.", "Ads for a town with no providers.", "A customer film that never came through Operations.", "A weekly report with only good news.", "A live file that this job never approved."],
      detailed: {
        copy: {
          heroKicker: "What this job is",
          defTitle: "What this job is",
          lanesTitle: "This job has three parts",
          definition: "You own everything Panun Kaergar does online: paid ads, website and Google search, the app store, social, Reddit, Quora, and other sites, plus the company folder of videos and posts. Content Maker makes the files. You say yes or no on each finished file, then you publish. You do not ask the customer how they found us — Operations asks and writes the answer, and you read those answers so you know which ads and pages work. You do not invent a new service, price, or town, and you do not book the customer.",
          owns: "These are the six things this job owns. Do not invent a new service, price, or town, and do not book the customer.",
          lanes: "This job has one result. The work is in three parts. Parts 1 and 2 are work you do yourself — paid ads, and unpaid website and search work. Part 3 is Content Maker, who makes the files. If Content Maker is empty, you do that work today. Write that down.",
          responsibilities: "These are the six pieces of online work this job owns: paid ads, unpaid website and search work (app store, unpaid social, Reddit, Quora, other sites), the company folder of videos and posts, the Content Maker job, reading the answers Operations wrote, and honest written reports back to Marketing Manager. Click a row to open the full card.",
          handoffs: "Work arrives as a written file and leaves as a written file. A chat message is not the file. The chart shows the flow. Click a row for the full file.",
          workflow: "Follow these steps every working day: open the instruction book and the calendar, approve waiting files and publish only from the company folder, read the answers Operations wrote for people from your ads and pages. DIG-01 is how a paid ad goes live — only after a dummy enquiry has reached Operations. DIG-04 is the stop when the words are not in the instruction book. Click a step to open the full card.",
          reporting: "Three written reports this job must write. Daily: spend, new enquiry where Operations wrote how they found us, pauses, library same day. Weekly: by named online place (Meta, Google, search, social, or other) — spend, enquiries, cost, 3 wins, 3 fails, one written request, content log. Monthly: next month’s digital slice for Marketing Manager. Click a row to open the full report.",
          standards: "Bars this job is measured against. Click a card for the full standard.",
          kpis: "How Marketing Manager knows this job is working. Each measure has a target, a reason it matters to Panun Kaergar, and a counting rule so two people cannot argue about the number.",
          escalations: "What to do when tracking dies, an ad is rejected, a customer is angry in comments, a kit change is needed, or Operations footage is late. Click a row to open the full steps."
        },
        hub: {
          icon: "campaign",
          line: "You run paid ads and unpaid website and search work. Content Maker makes the files. You say yes, then publish. Sales books the customer."
        },
        definition: {
          what: [
            { title: "You own all online work of Panun Kaergar", why: "Paid ads (Meta, Google, and any other paid ads), website search, the app store, unpaid social, Reddit, Quora, and other sites. One job, one promise. Operations writes how each digital enquiry found us." },
            { title: "You say yes or no on Content Maker files, then you publish from the company folder", why: "Content Maker makes AI videos, AI still posts, brand films, founder films, and edits Operations footage. You write the yes. Only then a file may go live on ads, social, search, or other sites." },
            { title: "You send the person to Sales the same day. Operations has already written how they found us", why: "You bring the person in. Sales books the job. If Operations did not write how they found us, pause that ad or page the same day." }
          ],
          why: [
            { title: "If ads, search, and social invent three companies, Sales cannot learn", why: "One job must own every named online place and send the truth back: what worked and what failed, by named online place (Meta, Google, search, social, or other), not as one lump called “digital”." },
            { title: "Making files and going live are two jobs", why: "Content Maker produces. You approve, file, publish, and spend. If one person does both without naming it, nobody owns the company folder." },
            { title: "The signed plan is the rule, not the ad account", why: "Towns, services, words, ads money, and the landing path come down from Marketing Manager. A new claim, price, town, or face goes back up before it goes live." }
          ]
        },
        glossary: [
          {
            id: "signed-kit",
            term: "Signed kit",
            aliases: ["signed marketing kit", "marketing kit", "signed kit", "on-kit", "off-kit", "the kit", "kit"],
            also: "Signed marketing kit, the kit",
            meaning: "The instruction book for every ad, post, search page, and library file this month. Marketing Manager writes it and signs it. It holds the logo, colours, tone, sentences you may use, live services, signed prices, open towns, and allowed faces. You may publish from it without asking again. A new claim, price, town, or face is a kit change."
          },
          {
            id: "file-list",
            term: "File list",
            aliases: ["file lists", "file list", "file-list"],
            meaning: "The written work order this job gives Content Maker. Each line is one video or still to make or edit: type (AI post, AI video, brand film, customer, provider, founder), service, town if there is one, where it will be used, and the source. Work starts only from this list, not from a chat."
          },
          {
            id: "library",
            term: "Company library",
            aliases: ["company library", "content library", "the library", "library"],
            meaning: "The shared folder of videos (.mp4) and still posts (.jpg or .png) this job has approved. Ads, social, search, and other sites pull only from here. A file enters only after a written yes. It is not a folder that lives only on a personal phone."
          },
          {
            id: "review-path",
            term: "Review path",
            aliases: ["review path", "review folder"],
            meaning: "The folder Content Maker puts named finished files into, waiting for this job’s yes or no. Do not publish from review. Publish from the library after a yes."
          },
          {
            id: "pipe",
            term: "Named online place",
            aliases: ["pipes", "pipe", "named online place"],
            also: "Named online place",
            meaning: "One named digital channel: Meta, Google, search, social, Reddit, or other. Weekly numbers are split by named online place (Meta, Google, search, social, or other). Do not hide an ad or page that sends nobody inside one lump called “digital”."
          },
          {
            id: "tagged-lead",
            term: "Enquiry where Operations wrote how they found us",
            aliases: ["enquiry where Operations wrote how they found us", "enquiry where Operations wrote how they found us", "source tag", "lead tags", "the answers Operations wrote for people from your ads and pages"],
            meaning: "A person Operations asked where they found us, and Operations wrote paid ad or search (plus which ad or page if the person said it). You do not ask the customer. You read what Operations wrote."
          },
          {
            id: "test-lead",
            term: "Test lead",
            aliases: ["test lead", "dummy lead", "test leads"],
            meaning: "A dummy enquiry you send before a paid ad goes live, to prove Operations actually receives it. If it does not arrive, do not launch. Target is 100 percent of paid ads."
          },
          {
            id: "ads-line",
            term: "Ads line",
            aliases: ["ads line", "ads budget", "ads money"],
            meaning: "The paid-ads budget on the signed monthly plan. Spend stays inside this line. People who found us without a paid ad are counted separately and must not be mixed into ads spend."
          },
          {
            id: "tracking",
            term: "Tracking",
            aliases: ["tracking"],
            meaning: "The path that proves a person from an ad or a page actually reaches Operations — phone, WhatsApp, app, or form. If that path dies, pause the paid ad the same day and tell Marketing Manager. Tracking does not tell you the source. Operations asks the person and writes the answer."
          },
          {
            id: "calendar",
            term: "Signed calendar",
            aliases: ["signed calendar", "ads calendar", "the calendar", "calendar"],
            meaning: "The month broken into days: which town, which service, which ad or page, which file, which budget. Digital does not set its own month. Marketing Manager writes it. Head of Growth signs it."
          },
          {
            id: "content-maker",
            term: "Content Maker",
            aliases: ["Content Maker"],
            meaning: "The production seat that reports here. Makes AI videos and AI still posts, brand films, and founder films from this month’s marketing kit, and edits customer and provider films from Operations. Does not publish, spend, or book the customer."
          },
          {
            id: "mkm",
            term: "Marketing Manager",
            aliases: ["Marketing Manager"],
            meaning: "The seat this role reports to. Owns the monthly plan, brand, and the signed kit. Digital and Field report here. A new town, service, price, or claim goes here first — not to Head of Growth."
          },
          {
            id: "dmm",
            term: "Digital Marketing Manager",
            aliases: ["Digital Marketing Manager"],
            also: "This job",
            meaning: "This job. Owns paid ads, unpaid website and search work, the company folder of videos and posts, the Content Maker job, and the answers Operations wrote for people from your ads and pages. Publishes from the library. Sends enquiry where Operations wrote how they found us to Sales."
          }
        ],
        responsibilities: [
          {
            kicker: "Responsibility 1",
            title: "Run paid ads on the signed plan",
            lead: "Build, watch, and pause Meta, Google, and any other paid ads that are on the calendar, inside the ads line, with tracking that a test lead can prove.",
            art: "cmc-h-shot.png",
            body: [
              "There is no extra ads manager. This job spends the ads money. A live ad with no owner is a leak. Before launch: town, service, and words match the kit; the file is an approved library file; a dummy lead has landed in Sales; Sales has been told the ad is live.",
              "Every working day: spend vs the ads line, enquiries, cost. If spend is running away, pause and tell Marketing Manager. If spend is on and enquiries are zero, pause and check tracking and the file. Write what you did."
            ],
            points: [
              { title: "Test lead before spend", why: "If the dummy does not reach Sales, do not launch." },
              { title: "Library file only", why: "No file from a personal phone. Content Maker makes it. You approve it first." },
              { title: "Pause the same day tracking dies", why: "Spend with no path to Sales is waste." }
            ],
            meta: [
              ["Takes from", "Signed calendar, kit, ads line, approved library file"],
              ["Hands to", "Sales (enquiry where Operations wrote how they found us); Marketing Manager (daily note if spend jumped or you paused)"]
            ]
          },
          {
            kicker: "Responsibility 2",
            title: "Run unpaid online work: website search, the app store, unpaid social, Reddit, Quora, other sites",
            lead: "Keep the website and app store true to this month’s marketing kit. Post and answer on unpaid social and other sites using the same words. Count them on their own line.",
            art: "cmc-h-yes.png",
            body: [
              "Unpaid pages and posts are not free ads. It still needs the same promise, a name, a tag, and a path to Sales. Do not mix search results into ads spend. Do not book in a comment or a thread.",
              "Use approved library files. A new claim, price, town, or face waits for Marketing Manager. If someone starts a sales chat, hand it to Sales the same day."
            ],
            points: [
              { title: "Same promise as the ads", why: "The customer must not hear a second company on Reddit or the website." },
              { title: "Own line in the weekly written report", why: "So unpaid pages that send nobody cannot hide inside ads numbers." },
              { title: "Do not close the chat", why: "You generate. Sales books." }
            ],
            meta: [
              ["Takes from", "Kit; approved library files; signed calendar for which towns and services may be talked about"],
              ["Hands to", "Sales (people Operations marked as search or unpaid posts, and comment threads); weekly written report by named online place (Meta, Google, search, social, other)"]
            ]
          },
          {
            kicker: "Responsibility 3",
            title: "Own the company library and the Content Maker job",
            lead: "Write the file list. Hand over the signed kit, the review path, and the naming rule. Approve each finished file. Only a yes puts it in the library. Then ads and social may use it.",
            art: "cmc-r-library.png",
            body: [
              "Content Maker makes AI videos, AI still posts, brand films, founder films, and edits Operations footage. This job does not sit in the editor unless Content Maker is empty — and then it is written that you are doing it today because nobody is in that job.",
              "Every library file: name, type, service, town, date, source, where it went live, result or ‘none yet’. If a file lives only on a personal phone, it is not the company copy. Coach Content Maker against their one result: files that match this month’s marketing kit, approved, in the library the same working day."
            ],
            points: [
              { title: "Approve against the kit", why: "A yes is a check: would Marketing Manager sign this sentence and this picture today?" },
              { title: "Send a no with what to change", why: "A silent wait is not a no. First-pass rate is how you coach production." },
              { title: "Write that you are doing Content Maker work because nobody is in that job, and you wrote that down", why: "An empty job still needs a named person covering it." }
            ],
            meta: [
              ["Takes from", "Content Maker named files in review; kit; file-list needs"],
              ["Hands to", "Library after a yes; Content Maker (file list, kit, yes/no); ads and social (approved files only)"]
            ]
          },
          {
            kicker: "Responsibility 4",
            title: "Ask Operations for customer and provider footage the file list needs",
            lead: "Those videos are captured on the job. This job writes the need, collects the raw files, and hands them to Content Maker to edit. Then this job approves the cut.",
            art: "cmc-r-consent.png",
            body: [
              "Do not capture footage yourself. Do not publish a customer or provider video that skipped Operations and the library. Write type, service, town. Ask Operations. Hand the raw file to Content Maker. Approve the kit cut. Then ads and social may use it.",
              "If footage does not arrive, keep Content Maker on AI and brand work that is ready, and put the wait on the daily note and the weekly written report."
            ],
            points: [
              { title: "Write the need", why: "Type, service, town. Chat is not a need." },
              { title: "Content Maker edits", why: "This job approves. Operations captured. Three boxes, three jobs." },
              { title: "Escalate age", why: "A block that sits a week is a Marketing Manager problem, not a hope." }
            ],
            meta: [
              ["Takes from", "File-list lines that need Operations footage"],
              ["Hands to", "Operations (the ask); Content Maker (raw files); library (after you approve the cut)"],
              ["Escalates to", "Marketing Manager if the files still do not arrive"]
            ]
          },
          {
            kicker: "Responsibility 5",
            title: "People from your ads and pages reach Operations. Operations asks where they found us",
            lead: "You do not ask the customer. Operations asks — call, WhatsApp, app, form, or any other way — and writes the answer. You read those answers. You do not start the sales chat.",
            art: "cmc-h-note.png",
            body: [
              "A person with no answer from Operations cannot be counted. Tell Operations when an ad or page is live. Each day, read the answers they wrote. If someone is chatting you in comments, pass the thread to Sales.",
              "Operations or Sales sends back which digital leads booked and why the rest were lost. That is how next week’s calendar gets smarter. You do not close."
            ],
            points: [
              { title: "Operations asks. You read.", why: "You cannot see the source from the phone number." },
              { title: "Dummy enquiry reaches Operations, or do not launch", why: "If Operations never received the person, the ad is wasted." },
              { title: "Comments are not yours to close", why: "Hand the person to Sales. Angry job comments go to Sales / CX." }
            ],
            meta: [
              ["Takes from", "The answers Operations wrote"],
              ["Hands to", "Marketing Manager (counts); Sales (the person, not a booking you closed)"]
            ]
          },
          {
            kicker: "Responsibility 6",
            title: "Send honest results up — wins and fails, by named online place (Meta, Google, search, social, or other)",
            lead: "A report with only wins is how bad spend hides. Weekly: 3 things that worked, 3 that failed, one written request. Split Meta, Google, search, social, other.",
            art: "cmc-p-weekly.png",
            body: [
              "Marketing Manager cannot run this job without numbers they can act on. Daily: spend, new enquiry where Operations wrote how they found us, anything paused, library same day — when something moved. Weekly: the written report. Monthly: help write next month’s digital slice, archive dead ads and dead posts, list films still needed so Operations and the founder can plan.",
              "People keep asking for X is a signal to Intelligence, not a new ad you invent."
            ],
            points: [
              { title: "By named online place (Meta, Google, search, social, or other), not one lump", why: "A dead Google line must not hide inside ‘digital did fine’." },
              { title: "One written request", why: "More money, a new video, or pause a town — not ten wishes." },
              { title: "Fails named", why: "Wins-only packs are not a management system." }
            ],
            meta: [
              ["Takes from", "Spend log, lead tags, library log, Content Maker weekly written report"],
              ["Hands to", "Marketing Manager; Intelligence (signals only); Sales (what will arrive)"]
            ]
          }
        ],
        handoffs: [
          {
            side: "in",
            kicker: "You receive",
            title: "The signed month from Marketing Manager",
            lead: "Towns that are open, services you may talk about, the ads line, the signed kit, where a lead must land, and what we will not do this month.",
            art: "cmc-h-kit.png",
            body: [
              "This is the written month that Digital must follow. Do not advertise a town we cannot serve. Do not talk about a draft service. Do not spend past the ads line. You may publish from this month’s marketing kit without asking again.",
              "If it arrives as a chat, ask Marketing Manager to put it in the signed month."
            ],
            points: [
              { title: "Must contain", why: "Towns, services, ads budget and ceiling, kit, landing path, will-not-do." },
              { title: "If it is missing", why: "Do not invent the month. Write that it did not happen. Wait." }
            ],
            meta: [
              ["From", "Marketing Manager"],
              ["When", "Before the month starts, and whenever they issue a change"],
              ["You do with it", "Do only paid ads, unpaid online work, and content from this pack."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Named files from Content Maker, waiting in review",
            lead: "Finished videos and stills, named, in the review path, plus the same-day file note: waiting, approved, sent back, blocked.",
            art: "cmc-h-files.png",
            body: [
              "Check each file against this month’s marketing kit. Write yes or no the same working day if you can. A yes puts it in the library. A no says what to change. Do not leave a finished file silent overnight without aging the wait on your daily note.",
              "If Content Maker is empty, write that you are doing Content Maker work because nobody is in that job, and you wrote that down, and make the files yourself from this month’s marketing kit — or the library dries up."
            ],
            points: [
              { title: "A yes", why: "Move the named file into the library. Mark the log." },
              { title: "A no", why: "Keep it out of the library. Content Maker remakes." }
            ],
            meta: [
              ["From", "Content Maker"],
              ["When", "The same working day a file is finished"],
              ["You do with it", "Approve, send back, or age the wait. Then publish from the library, not from review."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Raw Operations footage for the file list",
            lead: "Customer-feedback and provider job videos captured on site, asked for by this seat, edited by Content Maker.",
            art: "cmc-r-consent.png",
            body: [
              "You asked for them on the file list. Operations sends who, which service, which town, which date. Hand the raw files to Content Maker. Do not publish the raw clip."
            ],
            points: [
              { title: "If they have not arrived", why: "Name the line on the blocked list. Keep AI and brand work moving." }
            ],
            meta: [
              ["From", "Operations, asked for by this seat"],
              ["When", "As soon as the raw file is ready"],
              ["You do with it", "Hand to Content Maker. Approve the cut. Then library."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Which digital leads booked, and why the rest were lost",
            lead: "Sales sends back results on the leads Operations wrote a source for, so next week’s calendar is not a guess.",
            art: "cmc-h-note.png",
            body: [
              "Enquiries are not the result. Bookings are. Lost-lead reasons tell you which ad, page, or file to pause. Do not invent a new offer from a lost lead — send ‘people keep asking for X’ to Intelligence."
            ],
            points: [
              { title: "Use it in the weekly written report", why: "Bookings from digital leads, if Sales has closed them." }
            ],
            meta: [
              ["From", "Sales"],
              ["When", "As they close, rolled up for the weekly written report"],
              ["You do with it", "Keep, pause, or change an ad or page. Do not book the customer yourself. Send them to Sales and close the person yourself."]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Tracking and site access from Technology",
            lead: "Proof that a test lead really lands in Sales, and access to the website and app store listing so search work can happen.",
            art: "cmc-r-produce.png",
            body: [
              "If tracking dies, pause paid ads the same day. If the website cannot take a lead, pause the pipes that land there. Write the time. Tell Marketing Manager."
            ],
            points: [
              { title: "No tracking, no spend", why: "A live ad with a dead path is a leak." }
            ],
            meta: [
              ["From", "Technology"],
              ["When", "At setup, and the same day it breaks"],
              ["You do with it", "Test before launch. Pause when it dies."]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Tagged digital leads to Sales",
            lead: "Every digital enquiry the same working day: digital + pipe + campaign or post, service and town if you have them.",
            art: "cmc-h-note.png",
            body: [
              "Do not book the job. Do not sit on the chat. If they started in comments, pass the thread."
            ],
            points: [
              { title: "Must contain", why: "Source tag. Destination Sales already uses." }
            ],
            meta: [
              ["To", "Sales"],
              ["When", "The same working day the lead arrives"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "File list, kit, and yes or no to Content Maker",
            lead: "Today’s written list, the current signed kit, the review path and naming rule, and a written yes or no on each finished file.",
            art: "cmc-h-shot.png",
            body: [
              "If a line needs Operations footage, say so on the list. If a line is only in chat, put it on the list first. Content Maker does not invent work."
            ],
            points: [
              { title: "Each line holds", why: "Type, service, town or ‘no town’, where it will be used, source." }
            ],
            meta: [
              ["To", "Content Maker"],
              ["When", "Before work starts on that line; yes or no the same working day a file is waiting"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Daily digital note to Marketing Manager",
            lead: "When spend jumped, tracking broke, you paused something, or files went live. Short. Written.",
            art: "cmc-h-note.png",
            body: [
              "Catch a leak the same day — not in next week’s meeting. Must have: date, spend today, spend vs plan, new enquiry where Operations wrote how they found us by named online place (Meta, Google, search, social, or other), pauses, tracking broken?, library same day?"
            ],
            points: [
              { title: "Empty is allowed", why: "If nothing moved, you do not invent a note. If something moved, you do not skip it." }
            ],
            meta: [
              ["To", "Marketing Manager"],
              ["When", "Every working day when something moved"],
              ["Copy", "Sales gets only the new enquiry where Operations wrote how they found us — not the commentary"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Weekly digital pack to Marketing Manager",
            lead: "By named online place (Meta, Google, search, social, or other): spend, enquiries, cost, share of people with a written answer for how they found us, 3 wins, 3 fails, one written request, content log. The day before the marketing weekly report.",
            art: "cmc-p-weekly.png",
            body: [
              "This is the digital page of the marketing weekly. Fails must be named. One written request, not ten. Content Maker’s production pack is the content page of this pack."
            ],
            points: [
              { title: "Must contain", why: "Week dates, table by named online place (Meta, Google, search, social, or other), spend vs plan, cost vs ceiling, share of people with a written answer for how they found us, 3 wins, 3 fails, one written request, content log, owner of the ask." }
            ],
            meta: [
              ["To", "Marketing Manager"],
              ["When", "The day before the marketing weekly report"],
              ["Also", "Intelligence gets only ‘people keep asking for X’. Sales gets anything still sitting in a thread."]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Live files from the library to ads, social, search, other",
            lead: "Upload and schedule only approved library files. File where each one went live, which service, which town, which date.",
            art: "cmc-r-library.png",
            body: [
              "New claim, price, town, or face: stop. Everything else in this month’s marketing kit: publish, file, report."
            ],
            points: [
              { title: "From the library, not a phone", why: "If it is not filed, it did not happen." }
            ],
            meta: [
              ["To", "The live pipe (Meta, Google, social, website, app store, Reddit, other)"],
              ["When", "When it is on the signed calendar"]
            ]
          }
        ],
        workflow: [
          {
            kicker: "DIG-02 · Step 1",
            title: "Open the signed kit, the calendar, and spend",
            lead: "Before you publish or spend, know what the month allows: towns, services, words, ads money, and where a lead must land.",
            art: "cmc-h-kit.png",
            body: [
              "At the start of the working day, open the current kit, the signed calendar, and spend vs the ads line. Check tracking with a glance at yesterday’s leads. Mark pipes that may run, and pipes that must pause.",
              "If the signed month has not arrived, write that to Marketing Manager and wait. Do not invent a town, a service, or a budget."
            ],
            points: [
              { title: "Kit", why: "Words, prices, towns, faces, look you may use." },
              { title: "Calendar", why: "Which paid ad or unpaid page is allowed today." },
              { title: "You create", why: "A marked day: run / pause. Not a live ad yet." }
            ]
          },
          {
            kicker: "DIG-03 · Step 2",
            title: "Approve waiting files, then publish only from the library",
            lead: "Clear the review path. A yes files the asset. Then ads, social, search, and other sites pull from the library — not from review, and not from a phone.",
            art: "cmc-r-library.png",
            body: [
              "Open Content Maker’s same-day note and the review path. Check each file against this month’s marketing kit. Write yes or no. On a yes, put it in the library and mark the log. On a no, write what to change.",
              "Publish only what the calendar asked for, using only library files. File where it went live. If someone starts a sales chat, hand it to Sales."
            ],
            points: [
              { title: "Approve", why: "Kit check. Same working day if you can." },
              { title: "Publish", why: "From the library. Log the live placement." },
              { title: "Stops when", why: "The words, price, town, or face are not in the kit — that is DIG-04." }
            ]
          },
          {
            kicker: "DIG-06 · Step 3",
            title: "Read the answers Operations wrote for people from your ads and pages",
            lead: "Same day. Operations asked ‘How did you find us?’ You count paid ad vs search. Do not book the job. Do not ask the customer yourself.",
            art: "cmc-h-note.png",
            body: [
              "Look at the answers Operations wrote today. Count paid ad vs search. If Operations has people with no answer, tell Marketing Manager. Do not invent a source.",
              "If spend jumped or you paused something, write the daily note to Marketing Manager before the day ends."
            ],
            points: [
              { title: "You create", why: "Counts from what Operations wrote, and a daily note if something moved." },
              { title: "You do not create", why: "A booking. That is Sales." }
            ]
          },
          {
            kicker: "DIG-01 · If a paid ad is on the calendar",
            fork: "yes",
            title: "Test a dummy lead, then launch",
            lead: "A paid ad goes live only after kit, library file, a dummy enquiry has reached Operations, and Operations has been told the ad is live so they ask where people found us.",
            art: "cmc-h-shot.png",
            body: [
              "Check town, service, words, and budget against the kit and calendar. Pick an approved library file — or ask Content Maker to make it, then approve it. Send one dummy enquiry so Operations actually receives it. If it does not arrive, do not launch. Tell Operations the ad is live. They ask every person where they found us. You do not. File the launch record."
            ],
            points: [
              { title: "From", why: "A calendar line that is paid." },
              { title: "You create", why: "A live ad with a launch record, or a pause if the test lead fails." }
            ]
          },
          {
            kicker: "DIG-04 · If it is not in the kit",
            fork: "no",
            title: "Stop. Do not publish. Ask Marketing Manager",
            lead: "A new claim, price, town, or face is a kit change. This job does not skip to Head of Growth.",
            art: "cmc-e-stop.png",
            body: [
              "Stop. Write what you want to say and why. Send it to Marketing Manager. Wait for a yes. If it is a new service or town, they take it to Head of Growth. Only then make or approve the file and put it in the kit."
            ],
            points: [
              { title: "You do", why: "Pause that line. Keep other on-kit work moving." },
              { title: "You send", why: "The written ask, to Marketing Manager." }
            ]
          }
        ],
        reporting: [
          {
            period: "Daily",
            kicker: "DIG-R1",
            title: "Daily digital note",
            art: "cmc-h-note.png",
            when: "Every working day if spend jumped, tracking broke, you paused something, or files went live. Short. Written.",
            lead: "Catch a leak the same day — not in next week’s meeting.",
            body: [
              "This note exists so Marketing Manager always knows spend vs the ads line, which named online places sent enquiry where Operations wrote how they found us, what you paused, and whether today’s live files are in the library."
            ],
            contents: [
              { title: "Spend today and month-to-date vs the ads line", why: "The signed plan is the rule, not the ad account." },
              { title: "New digital leads, tagged by named online place (Meta, Google, search, social, or other)", why: "If Operations did not write how they found us, pause that online place." },
              { title: "Anything paused, and why", why: "Marketing Manager must not discover a pause by accident." },
              { title: "Files that went live today, filed in the library?", why: "If it is not filed, it did not happen." },
              { title: "Are you doing Content Maker work because nobody is in that job, and you wrote that down? yes/no", why: "Empty production still has a name." }
            ],
            mustHave: ["Date", "Spend today", "Spend vs plan", "New enquiry where Operations wrote how they found us by named online place (Meta, Google, search, social, or other)", "Pauses", "Tracking broken?", "Library same day?", "Covering Content Maker because nobody is in that job? yes/no"],
            submit: [
              { to: "Marketing Manager", why: "Every working day when something moved." },
              { to: "Sales", why: "Only the new enquiry where Operations wrote how they found us — not the commentary." }
            ]
          },
          {
            period: "Weekly",
            kicker: "DIG-R2",
            title: "Weekly digital pack",
            art: "cmc-p-weekly.png",
            when: "The day before the marketing weekly report. Must include fails, not only wins.",
            lead: "Show, by named online place (Meta, Google, search, social, or other), whether the signed plan produced enquiry where Operations wrote how they found us — and name 3 things that worked, 3 that failed, and one written request.",
            body: [
              "Do not hide an ad or page that sends nobody inside one lump called “digital”. Fold in Content Maker’s weekly production pack as the content page. Bookings from digital leads, if Sales has closed them. Sales chats handed over from comments."
            ],
            contents: [
              { title: "Table by named online place (Meta, Google, search, social, or other)", why: "Meta, Google, search, social, other — spend, enquiries, cost, share of people with a written answer for how they found us." },
              { title: "3 wins and 3 fails", why: "A report with only wins hides bad spend." },
              { title: "one written request", why: "More money, a new video, or pause a town." },
              { title: "Content log", why: "What went live, where, service, town." },
              { title: "Operations footage still outstanding", why: "So Marketing Manager can hold Operations." }
            ],
            mustHave: ["Week dates", "Table by named online place (Meta, Google, search, social, or other)", "Spend vs plan", "Cost vs ceiling", "Share of people with a written answer for how they found us", "3 wins", "3 fails", "one written request", "Content log", "Owner of the written request"],
            submit: [
              { to: "Marketing Manager", why: "This is the digital page of the weekly marketing report." },
              { to: "Intelligence", why: "Only the ‘people keep asking for X’ lines." }
            ]
          },
          {
            period: "Monthly",
            kicker: "DIG-R5",
            title: "Next-month digital slice",
            art: "cmc-p-month.png",
            when: "Last week of the month, in time for Marketing Manager to draft next month’s plan for Head of Growth to sign.",
            lead: "Which pipes, which towns, which files, which ads money — and which Operations films must be collected before week one.",
            body: [
              "Archive dead ads and dead posts. List films still needed (jobs, customers, founder) so Operations can send footage and the founder can plan time. Use Content Maker’s next-month film list. Do not start spend until the new month is signed."
            ],
            contents: [
              { title: "Pipes to keep, pause, or change", why: "From this month’s wins and fails, not from mood." },
              { title: "Ads line ask", why: "Same, more, or less — with a reason." },
              { title: "Library gaps and Operations footage to collect", why: "So week one has files." },
              { title: "Will-not-do", why: "Stops a ‘just test it’ off the plan." }
            ],
            mustHave: ["Month", "Pipe table", "Ads line ask", "File needs", "Operations footage to collect", "Will-not-do", "Owner"],
            submit: [
              { to: "Marketing Manager", why: "They fold it into the signed monthly marketing plan. You do not send this to Head of Growth yourself." }
            ]
          }
        ],
        standards: [
          {
            title: "No paid ad live until a dummy enquiry has reached Operations",
            lead: "100 percent of paid ads prove Operations actually receives the person before spend starts.",
            art: "cmc-h-shot.png",
            body: [
              "If Operations does not receive the dummy, do not launch. File the launch record: calendar line, library file, Operations received the dummy, Operations told the ad is live, launch time."
            ],
            points: [
              { title: "What good looks like", why: "A stranger can open the launch record and see the test lead before the first rupee." }
            ]
          },
          {
            title: "Spend stays inside the ads line of the plan",
            lead: "The signed month is the boss, not the ad account.",
            art: "cmc-h-kit.png",
            body: [
              "If spend is running away, pause the same day and tell Marketing Manager. People who found us without a paid ad are not mixed into this ads number."
            ],
            points: [
              { title: "What good looks like", why: "Month-to-date spend vs plan is on the daily note whenever spend moved." }
            ]
          },
          {
            title: "95 percent or more of digital enquiries have Operations’ written answer",
            lead: "If Operations has no answer, pause that ad or page until they ask.",
            art: "cmc-h-note.png",
            body: [
              "You do not ask the customer. Operations asks how they found us and writes paid ad or search. You copy that answer, plus Facebook, Google, or the page name if they said it. Service and town if you have them. Same day into Sales."
            ],
            points: [
              { title: "What good looks like", why: "Sales never asks ‘where did this come from?’ about a digital lead." }
            ]
          },
          {
            title: "Every live file is an approved library file, filed the same day",
            lead: "Ads, social, search, and other sites pull from the library. A file that lives only on a personal phone is not the company copy.",
            art: "cmc-r-library.png",
            body: [
              "Customer and provider films in the library are kit edits of Operations footage, approved by this job. New claim, price, town, or face waits for Marketing Manager."
            ],
            points: [
              { title: "What good looks like", why: "Every live post traces to a written yes and a library path." }
            ]
          },
          {
            title: "Sales chats in comments go to Sales — you do not close them",
            lead: "This job generates. Sales books. Angry job comments go to Sales / CX.",
            art: "cmc-e-stop.png",
            body: [
              "Do not fight in public. Do not ‘already start it’. Pass the thread the same day."
            ],
            points: [
              { title: "What good looks like", why: "The weekly written report can name chats handed over. None were closed here." }
            ]
          }
        ],
        kpis: [
          {
            name: "Test lead before a paid ad goes live",
            target: "100%",
            why: "Spend with no path to Sales is waste.",
            how: "Count paid ads launched this week. A launch counts when the launch record shows a dummy enquiry reached Operations before launch time."
          },
          {
            name: "Digital enquiries with Operations’ written answer",
            target: "95% or more",
            why: "Sales cannot work a nameless lead.",
            how: "People Operations marked as paid ad or search, divided by all digital enquiries that week. People with no answer that could not be fixed the same day pause that ad or page."
          },
          {
            name: "Ads spend vs plan",
            target: "Inside the ads line",
            why: "The signed plan is the rule.",
            how: "Month-to-date paid spend vs the signed ads line. Organic spend is not in this number."
          },
          {
            name: "Cost per digital enquiry",
            target: "Inside the signed ceiling",
            why: "If cost blows, pause.",
            how: "Paid spend ÷ digital enquiries where Operations wrote paid ad or search, by named online place (Meta, Google, search, social, or other) and as a total. Report both in the weekly written report."
          },
          {
            name: "Live files in the library same day",
            target: "100%",
            why: "A file that lives only on a personal phone is not the company copy.",
            how: "Files that went live that calendar day with a library row dated the same day, approved by this job."
          },
          {
            name: "Weekly written report with wins and fails",
            target: "100% on time",
            why: "Wins-only reports hide bad spend.",
            how: "A written pack exists the working day before the marketing weekly, with 3 wins, 3 fails, one written request, and a table by named online place (Meta, Google, search, social, or other)."
          },
          {
            name: "Customer and provider films sourced from Operations",
            target: "100%",
            why: "Those videos are captured on the job. Content Maker edits. This job approves.",
            how: "Every library row of type customer or jobfilm names Operations as the source and shows this job’s yes."
          }
        ],
        escalations: [
          {
            title: "Ad account hacked, ad rejected, or tracking dead",
            lead: "Paid spend is at risk, or the path to Sales is broken.",
            art: "cmc-e-stop.png",
            body: [
              "Pause. Screenshot. Write the time. Tell Marketing Manager the same day. Do not keep spending while you ‘just look at it’."
            ],
            points: [
              { title: "You do", why: "Pause the pipe. Protect the account." },
              { title: "You send to", why: "Marketing Manager the same day, with screenshot and time." }
            ]
          },
          {
            title: "A customer is angry in comments about a job",
            lead: "This is not a marketing argument. It is a job complaint in public.",
            art: "cmc-h-note.png",
            body: [
              "Do not fight in public. Hand the thread to Sales / CX. Copy Marketing Manager. Keep the live file up only if Marketing Manager says the claim is still true."
            ],
            points: [
              { title: "You do", why: "Pass the thread. Do not promise a refund or a rework." },
              { title: "You send to", why: "Sales / CX, copy Marketing Manager." }
            ]
          },
          {
            title: "You need a new town, service, price, or claim",
            lead: "That is a kit change. This job does not skip to Head of Growth.",
            art: "cmc-h-kit.png",
            body: [
              "Stop that line. Write the ask. Send it to Marketing Manager. They take it up if it is a new service or town. Keep other on-kit pipes running."
            ],
            points: [
              { title: "You do", why: "Pause that line only. Do not invent the words." },
              { title: "You send to", why: "Marketing Manager — not Head of Growth." }
            ]
          },
          {
            title: "Operations has not sent footage the file list needs",
            lead: "Customer or provider films are blocked. Ads or social wanted them.",
            art: "cmc-p-weekly.png",
            body: [
              "Name the job or customer, service, town, and working days waiting. Tell Marketing Manager. Keep Content Maker on AI videos, AI still posts, and brand films that are ready."
            ],
            points: [
              { title: "You do", why: "Age the block. Do not publish a skip-Operations clip." },
              { title: "You send to", why: "Marketing Manager, on the daily note and the weekly written report." }
            ]
          }
        ]
      }
    },
    cmc: {
      story: "The Content Maker produces Panun Kaergar’s marketing files for the Digital Marketing Manager: AI videos and AI still posts, brand films, and founder films from the signed kit, plus customer-feedback and provider job films collected from Operations and edited. Finished files go for approval the same working day, and enter the company library only after a yes.",
      brief: "Produces Panun Kaergar’s marketing files for the Digital Marketing Manager — AI videos and AI still posts, brand films, and founder films from the signed kit, plus customer-feedback and provider job films collected from Operations and edited. Finished files go for approval the same working day.",
      receives: [
        { from: "Digital Marketing Manager", what: "The day's file list, the signed kit, the review path, the library path and naming rule, and a yes or no on each finished file." },
        { from: "Operations", what: "Raw customer-feedback videos and provider job videos to collect, edit, and send for approval." }
      ],
      gives: [
        { to: "Digital Marketing Manager", what: "Named files ready for approval the same working day, a same-day file note, and a blocked list if Operations footage is still waiting." }
      ],
      records: ["File lists", "Signed kit copy in use", "Operations footage log", "Review folder", "Content library log", "Same-day file notes", "Blocked list (waiting on Operations footage)"],
      good: ["Every finished file is sent to the Digital Marketing Manager for approval the same working day.", "A file enters the library only after the Digital Marketing Manager says yes.", "Every file matches the signed kit.", "Customer and provider files are edited from Operations footage.", "Nothing went live, and no ads money moved, from this seat.", "Masters sit in review or the library — not only on a phone."],
      bad: ["Files only on a personal phone or private chat.", "A file placed in the library before the Digital Marketing Manager approved it.", "A new claim, price, town, or face added without Marketing Manager.", "Raw Operations footage sitting unedited at the end of the week.", "This job published, scheduled, or boosted a file.", "This job captured customer or provider footage on the job."],
      detailed: {
        copy: {
          heroKicker: "What this job is",
          defTitle: "What this job is",
          definition: "You make Panun Kaergar’s marketing files for the Digital Marketing Manager: AI videos and still images, company films, and films with the founder when that face is already allowed this month, plus customer and provider videos collected from Operations and edited. You send each finished file for approval the same working day. The Digital Marketing Manager says yes, then publishes. You do not go live, spend ads money, or book the customer.",
          owns: "These are the five things this job owns. Do not go live, spend ads money, or book the customer.",
          responsibilities: "These are the five pieces of production this job owns: every line on the Digital Marketing Manager's file list, AI videos and AI still posts, brand films, founder films, customer and provider films collected from Operations then edited, and sending finished work for approval into the library. Click a row to open the full card.",
          handoffs: "Work arrives as a written pack and leaves as a written pack. The chart shows the flow. Click a row for the full pack.",
          workflow: "Follow these steps in order for every file-list line that is ready: check the list and the kit, make the file, send it for approval. If it is accepted, put it in the library. If it is rejected, change it and send it again. CMC-02 is the kit-gap stop. CMC-03 is how you edit Operations footage. Click a step to open the full card.",
          reporting: "Three written reports this job must write. Daily: what was sent for approval, what the Digital Marketing Manager approved into the library, what they sent back, and what is waiting on Operations. Weekly: file list versus delivered, including misses and approval rates. Monthly: the films next month will need and which Operations files must be sent first. Click a row to open the full report.",
          standards: "Four bars this job is measured against. Click a card for the full standard.",
          kpis: "How the Digital Marketing Manager knows this job is working. Each measure has a target, a reason it matters to Panun Kaergar, and a counting rule so two people cannot argue about the number.",
          escalations: "What to do when the Digital Marketing Manager sends a file back, when the kit cannot cover a line, when Operations footage is late, or when a file or the library fails. Click a row to open the full steps."
        },
        hub: {
          icon: "movie",
          line: "Collect, make, edit. The Digital Marketing Manager approves."
        },
        definition: {
          what: [
            { title: "You make Panun Kaergar’s marketing videos and still posts", why: "This role reports to the Digital Marketing Manager. Each working day you turn a written file list into finished videos and still images, using only this month’s marketing kit." },
            { title: "You make AI videos, still posts, company films, and founder films, and you edit footage from Operations", why: "AI videos, still posts, company films, and founder films are made from this month’s marketing kit. Customer-feedback and provider job films are collected from Operations, then edited to the same kit." },
            { title: "You send finished files to the Digital Marketing Manager for approval", why: "Every file is named, dated, and sent the same working day. It enters the company folder only after the Digital Marketing Manager says yes." }
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
            meaning: "Today's work order from the Digital Marketing Manager. Each line is one video or one post to make or edit: the type (AI post, AI video, brand, customer, provider, or founder), the service, the town if there is one, where it will be used, and the source (you make it from this month’s marketing kit, you edit a founder film, or you collect and edit an Operations video). Work starts only from this written list, not from a chat."
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
            also: "This job's manager",
            meaning: "The seat this role reports to. Owns all online work: paid ads, website and app search, social, other sites, videos and posts. Writes the file list, hands over this month’s marketing kit, names the review path and the company library, approves finished files, and publishes from the library. Sends digital enquiries to Sales after Operations has written how they found us."
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
            meaning: "Runs the people who visit the market, get the first local workers to sign the papers on site, and write office and society contracts. Reports to the Marketing Manager. This is not the Content Maker’s manager."
          },
          {
            id: "content-maker",
            term: "Content Maker",
            aliases: ["Content Maker"],
            also: "This job",
            meaning: "This job. Produces Panun Kaergar’s marketing files for the Digital Marketing Manager to approve and publish: AI videos and AI still posts, brand films, and founder films from the signed kit, plus customer-feedback and provider job films collected from Operations and edited."
          },
          {
            id: "ai-files",
            term: "AI video and AI still post",
            aliases: ["AI videos and AI still posts", "AI still posts", "AI still post", "AI videos", "AI video", "AI posts", "AI post"],
            also: "AI still images",
            meaning: "Files generated with AI tools from the signed kit: moving videos (.mp4) and still images (.jpg or .png) for ads and social. Not live footage from a job. Mark the type as AI video or AI post on the file list and the log."
          },
          {
            id: "brand-film",
            term: "Brand film",
            aliases: ["Brand films", "brand films", "brand film"],
            meaning: "A company film that shows who Panun Kaergar is, what a customer can expect, and how a job is done. Made from the signed kit. The founder does not have to appear. The Digital Marketing Manager may later put it on social, the website, or a paid ad."
          },
          {
            id: "founder-film",
            term: "Founder film",
            aliases: ["Founder films", "founder films", "founder film", "personal-brand films", "personal-brand film"],
            also: "Personal-brand film",
            meaning: "A film with the founder or another named face from this month’s marketing kit on camera. Confirm the face is in the kit before recording. A new face is a kit change through the Digital Marketing Manager."
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
            meaning: "The daily written pack this job sends to the Digital Marketing Manager. It lists files sent for approval today, files approved into the company library today, files sent back, and file-list lines that are blocked. It is how the Digital Marketing Manager approves and plans without a meeting."
          },
          {
            id: "operations",
            term: "Operations",
            aliases: ["Operations"],
            meaning: "The function that owns finishing the job and the job on site. Operations captures customer-feedback videos and provider work videos, then sends the raw files. This job does not capture those videos; it collects them, edits them to the kit, and sends the cut for approval."
          },
          {
            id: "ops-footage",
            term: "Operations footage",
            aliases: ["Operations footage", "Operations files", "Operations file", "Operations video", "Operations videos"],
            also: "Raw Operations footage, customer and provider videos",
            meaning: "The raw customer-feedback and provider job videos Operations captured on site. This job collects those files, edits them to the signed kit, and sends the finished cut to the Digital Marketing Manager for approval. If the footage has not arrived, the file-list line goes on the blocked list."
          },
          {
            id: "kit-gap",
            term: "Kit gap",
            aliases: ["kit-gap", "kit gap"],
            meaning: "When a file-list line asks for a sentence, price, town, or face that is not in the signed kit. Stop that line. Write that it did not happen. Send it to the Digital Marketing Manager, who asks the Marketing Manager. Keep other on-kit lines moving."
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
            meaning: "Owns who the customer is, the promise, and the Growth numbers. The Marketing Manager reports here. A new service or a new town must be signed by this job before it can go on the file list or into the kit."
          },
          {
            id: "weekly-pack",
            term: "Weekly production pack",
            aliases: ["weekly production pack", "weekly written report"],
            meaning: "The weekly report this job writes for the Digital Marketing Manager. It shows file list versus delivered — sent, approved, blocked, or missed — plus first-pass and eventual approval rates, and Operations footage still outstanding. Misses and blocks must be named, not only finished work."
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
            lead: "The Digital Marketing Manager writes a list of the videos and posts needed. This job makes or edits each one using only the signed kit, then sends the finished file to the Digital Marketing Manager for approval.",
            art: "cmc-r-produce.png",
            body: [
              "The file list is today's work. The Digital Marketing Manager writes it. Each line is one video or one post: the type (AI post, AI video, brand, customer, provider, or founder), the service, the town if there is one, and where the raw file comes from (you make it from this month’s marketing kit, you edit an Operations video, or you edit a founder film).",
              "The signed kit is the instruction book from the Marketing Manager. It holds the words, prices, colours, logo, towns, and faces you may use. Read the list and the kit before you open an editor. A file is ready for approval when the Digital Marketing Manager can open it and check it against this month’s marketing kit. If a line asks for a price, town, sentence, or face that is not in this month’s marketing kit, send that line back to the Digital Marketing Manager. They ask the Marketing Manager to update the kit. Then you make the file."
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
              "AI is a production tool. Prompts are built from the signed kit: approved services, approved towns, approved sentences, and the approved visual world (Kashmir homes, mountains, navy and gold). If the model writes a cheaper price, a new town, a medical claim, or a guarantee the kit does not carry, that output is discarded and generated again from this month’s marketing kit.",
              "Every AI file is still a company file. It is named, dated, and sent to the Digital Marketing Manager for approval the same working day, with type marked as AI video or AI post so the Digital Marketing Manager and Marketing Manager can see what is generated and what is edited from Operations footage."
            ],
            points: [
              { title: "Prompt from this month’s marketing kit", why: "Copy the allowed words into the prompt." },
              { title: "Watch the output for invented facts", why: "If the model names a town or service that is not in this month’s marketing kit, discard that file and generate again." },
              { title: "Send AI the same way as edited work", why: "Name it with type, service, town, and date, and send it to the Digital Marketing Manager for approval the same working day." }
            ],
            meta: [
              ["Takes from", "Kit words and look; file-list lines marked AI video or AI post"],
              ["Hands to", "The Digital Marketing Manager for approval, type = AI video or AI post"],
              ["Stops when", "The model introduces a claim, price, town, or face that is not in this month’s marketing kit"]
            ]
          },
          {
            kicker: "Responsibility 3",
            title: "Create brand films and personal-brand films",
            lead: "Produce films that show Panun Kaergar as one company, and films that put the founder on camera when they are the approved face, using the kit script and look.",
            art: "cmc-r-brand.png",
            body: [
              "Brand films explain who Panun Kaergar is, what a customer can expect, and how a job is done, using approved services and approved towns. They are library assets the Digital Marketing Manager may later put on social, the website, or a paid ad. Personal-brand films use the founder or another named face that Marketing Manager has already put in the kit.",
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
            lead: "Customer-feedback films and provider job films are captured by Operations. This job collects those files, edits them to the kit, and sends the finished versions to the Digital Marketing Manager for approval.",
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
              "The Digital Marketing Manager writes this list. It is the day's work, not a camera script. Each line names the type (AI post, AI video, brand film, customer film, provider film, founder film), the service, the town if there is one, where it will be used (ads, social, website), and the source: you make it from this month’s marketing kit, you edit a founder film, or you collect and edit an Operations video.",
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
              "Inside this month’s marketing kit: the logo files you may place on a video; the colours (navy, gold, cream); the tone; the headlines and sentences you may put on screen; the live services; the prices Finance has signed; the towns that are open this month; and the faces that may appear (for example the founder). Use those words and that look. If a file needs a new sentence, a new price, a new town, or a new face, send the line to the Digital Marketing Manager. The Digital Marketing Manager asks the Marketing Manager. Work resumes when the kit is updated in writing."
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
            lead: "Raw files captured on the job: customer feedback and provider work. Operations sends them. This job collects, edits, and sends them for approval.",
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
              "The Digital Marketing Manager checks the named file against the signed kit: words, prices, towns, faces, look, and name. A yes is written. Then this job places the file in the library folder and marks the log.",
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
              "This is the output of the seat. The Digital Marketing Manager opens the review path, checks the file against this month’s marketing kit, and writes a yes or a no. A yes is what lets the file enter the library.",
              "Place the named file in the review path the same working day, then send the same-day file note so the Digital Marketing Manager knows it is waiting."
            ],
            points: [
              { title: "Put in review", why: "The finished video or still, and any shorter cut the Digital Marketing Manager asked for on the file list." },
              { title: "Mark the type on the log", why: "AI video, AI post, brand film, customer, provider, or founder, so the sheet matches the file." }
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
              { title: "Mark the type on the log", why: "AI video, AI post, brand film, customer, provider, or founder, so the sheet matches the folder." }
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
              "When a file-list line needs a customer or provider video and the file has not arrived, add the line to the blocked list: who, service, town, since when. That list travels with the same-day note and is rolled up in the weekly written report.",
              "The Digital Marketing Manager holds Operations for the files. This job keeps producing on-kit AI and brand work that is ready, and edits footage as soon as it arrives."
            ],
            points: [
              { title: "Name the gap", why: "Which Operations file, which service, which town. The Digital Marketing Manager must know what to ask for." },
              { title: "Keep other lines moving", why: "Keep AI videos, AI still posts, and brand films moving, and edit any Operations files that have already arrived." }
            ],
            meta: [
              ["To", "Digital Marketing Manager"],
              ["When", "Same day the block appears, and again on the weekly list"]
            ]
          }
        ],
        workflow: [
          {
            kicker: "CMC-01 · Step 1",
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
            kicker: "CMC-01 · Step 2",
            title: "Make or edit the file on that line, using the kit",
            lead: "Create the content the list asked for, using only the signed kit. How you make it depends on the line — not on a later step.",
            art: "cmc-r-produce.png",
            body: [
              "Take the next ready line. The line tells you what kind of file it is. An AI post or AI video is made from this month’s marketing kit. A brand film or founder film is made or edited from this month’s marketing kit. A customer-feedback or provider film is collected from Operations, then edited to the kit (CMC-03). Those are kinds of work on the same list. They are not steps that wait for each other.",
              "If a customer or provider line has no Operations file yet, mark it blocked and take the next ready line. Keep AI and brand work moving. If the line asks for a sentence, price, town, or face that is not in this month’s marketing kit, stop that line (CMC-02) and send it back to the Digital Marketing Manager. Then make the next ready file."
            ],
            points: [
              { title: "AI line", why: "Make the post or video from kit words and look." },
              { title: "Brand or founder line", why: "Make or edit from this month’s marketing kit. Use only a face already in the kit." },
              { title: "Customer or provider line", why: "Collect the raw file from Operations, then edit it to the kit. If it has not arrived, mark blocked and move on." }
            ]
          },
          {
            kicker: "CMC-01 · Step 3",
            title: "Name the file and send it for approval",
            lead: "Put the named file in the review path. Do not put it in the library yet.",
            art: "cmc-w-name.png",
            body: [
              "Use the naming pattern the Digital Marketing Manager issued. A working shape is type_service_town_date_version plus the real extension. Version starts at v01. If this is a remake, use the next version.",
              "Place the named file in the review path the Digital Marketing Manager named. Send the same-day file note so they know a file is waiting for a yes."
            ],
            points: [
              { title: "You create", why: "A correctly named file, ready to check against this month’s marketing kit." },
              { title: "You submit", why: "To the Digital Marketing Manager, in the review path. Naming is not filing." }
            ]
          },
          {
            kicker: "CMC-01 · If yes",
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
            kicker: "CMC-01 · If no",
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
              "The weekly written report is how the Digital Marketing Manager coaches this job and how Marketing Manager sees whether content is the constraint on ads and social. A week of only highlights hides a blocked customer film that ads needed. The Content Maker lists every file-list line: sent for approval, approved into the library, blocked, or missed, with a reason, plus the first-pass and eventual approval rates."
            ],
            contents: [
              { title: "File list versus delivered", why: "Each line: asked, sent for approval, approved into library, blocked, or missed. Missed needs a reason (illness, Operations footage late, kit gap, the Digital Marketing Manager no, tool failure)." },
              { title: "Approval rates", why: "First-pass yes versus files the Digital Marketing Manager decided, and eventual yes after remakes. A falling first-pass rate means production is unfinished or off-kit." },
              { title: "Counts by type", why: "AI posts, AI video, brand, customer, job, personal-brand. The Digital Marketing Manager must see which well is dry." },
              { title: "Operations footage still outstanding", why: "Name the service, town, and how many working days it has been waiting." },
              { title: "Library gaps the Digital Marketing Manager asked to fill next", why: "So the next file list is written from evidence, not from memory." },
              { title: "One production risk", why: "For example 'customer films have been waiting 10 working days on Operations footage'." }
            ],
            mustHave: ["Week dates", "Asked vs delivered table", "First-pass approval rate", "Eventual approval rate", "Counts by type", "Outstanding Operations footage", "One risk", "Owner of the written request to the Digital Marketing Manager"],
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
              "On-kit is a check: would Marketing Manager sign this sentence and this picture today? If the answer is no, remake the file from this month’s marketing kit or send the line back through the Digital Marketing Manager. AI output and Operations edits are held to the same standard. The Digital Marketing Manager uses this check when they approve."
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
              "The Content Maker's job on a file ends when it is named, sent for approval, and — after a yes — filed and reported. The Digital Marketing Manager publishes, spends, and handles comments. If someone written requests this job to put a file live because it is faster, tell the Digital Marketing Manager so they can publish from the library."
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
            why: "Those videos are captured on the job. This job's result is a kit-true edit the Digital Marketing Manager can approve.",
            how: "Every library row of type customer or jobfilm names Operations as the source and shows the date the Digital Marketing Manager approved the finished edit."
          },
          {
            name: "File list versus delivered",
            target: "Tracked every week; missed lines named with a reason",
            why: "The Digital Marketing Manager plans ads and social from what was approved: which types are ready, which Operations files are late, and which lines this job still owes.",
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
            title: "The file list needs words, a price, a town, or a face that is not in this month’s marketing kit",
            lead: "Production would have to invent a promise the Marketing Manager has not signed.",
            art: "cmc-e-stop.png",
            body: [
              "Stop that line. Write what the file list asked for, what the kit currently allows, and why they do not match. Send that to the Digital Marketing Manager. The Digital Marketing Manager asks the Marketing Manager. If it is a new service or a new town, Marketing Manager takes it to Head of Growth.",
              "Keep other on-kit lines moving while you wait."
            ],
            points: [
              { title: "You receive", why: "A file-list line the current kit cannot support." },
              { title: "You do", why: "Stop that line. Write that it did not happen. Keep other on-kit lines moving." },
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
              { title: "You do", why: "Age it on the weekly written report. Produce on-kit work that is ready." },
              { title: "You send to", why: "Digital Marketing Manager, on the weekly written report." }
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
      story: "You run Panun Kaergar on the ground. Three jobs report to you. Field Visitor actually visits the market — full-time or someone we pay on a written contract. First Provider Onboarding goes on site and signs the first service providers onto us, then hands them to Provider Operations. Office and Society Contracts writes maintenance contracts with offices and housing societies. You write their days, you check that each job hits its one result, and you send the truth to Marketing Manager.",
      receives: [
        { from: "Marketing Manager", what: "Which towns, which days, which boards, the stall budget, which first-worker towns, which offices and societies, the signed papers, and what we will not advertise." },
        { from: "Expansion", what: "Which towns we can actually serve — only through the signed plan." },
        { from: "Field Visitor", what: "Same-day stall counts, stall logs, and the weekly visit note." },
        { from: "First Provider Onboarding", what: "Signed first-worker files and the weekly first-worker note." },
        { from: "Office and Society Contracts", what: "Signed contract files and the weekly contract note." }
      ],
      gives: [
        { to: "Field Visitor", what: "Towns, days, boards, flyers, where a stall list must land in Sales, and what we will not advertise. If they are someone we pay on a written contract, a written contract." },
        { to: "First Provider Onboarding", what: "Which towns, the signed first-worker papers, and who in Provider Operations receives the signed file." },
        { to: "Office and Society Contracts", what: "Which towns, which buildings, the signed contract papers, and where a signed contract must be filed." },
        { to: "Marketing Manager", what: "The weekly field report: visits, first local workers, contracts, days that did not happen, town that produced nothing." },
        { to: "Intelligence", what: "What people kept asking for that we do not sell — as a signal, not as a new offer." }
      ],
      records: ["Signed field calendar for all three jobs", "Written briefs to the three jobs", "Written contracts for people we pay to visit", "First-worker papers and office-contract papers", "Weekly field report", "Notes of days you covered an empty job"],
      good: ["Each of the three jobs had a written brief the day the month was signed.", "Someone we pay to visit had a written contract.", "First local workers were handed to Provider Operations the same week.", "Office and society contracts used the signed papers.", "If a seat was empty, you wrote that you were doing that job.", "The weekly report named visits, first local workers, and contracts — not only stalls."],
      bad: ["A visitor with only a verbal yes and no written contract.", "Keeping first local workers as a private team.", "A verbal yes from a society called a contract.", "Signing a hotel desk as if that were this job.", "Doing an empty job without writing it down.", "A weekly report that only lists good stalls."],
      detailed: {
        copy: {
          heroKicker: "What this job is",
          defTitle: "What this job is",
          lanesTitle: "This job has three parts",
          definition: "You run Panun Kaergar on the ground. Three jobs report to you. Field Visitor visits stalls and streets and collects names for Sales. First Provider Onboarding goes in person to the first local workers in a town and gets them to sign the papers, then sends the file to Provider Operations. Office and Society Contracts writes maintenance contracts with named offices and housing societies. You write which town, which day, and which of those three jobs goes. You check each job hits its result, and you send Marketing Manager a weekly field report. You do not do those three jobs yourself unless that job has nobody in it — and then you write that you are doing it today because nobody is in that job.",
          owns: "These are the six things this job owns. Do not do Sales’ job, Digital’s job, or Partnerships’ job. Do not keep first local workers as your own team.",
          lanes: "This job has one result. The work is in three parts. Each part is a job that reports to you. Field Visitor walks the market. First Provider Onboarding gets the first local workers to sign. Office and Society Contracts writes the maintenance contract. If a job is empty, you do that work today. Write that down.",
          responsibilities: "These are the six parts of the job. Click a row to open the full card.",
          handoffs: "Work arrives as a written file and leaves as a written file. A chat message is not the file. Click a row to open the full card.",
          workflow: "Every working day, open the signed calendar and check that today’s visitor, first-worker visit, or contract visit is named. Give the three jobs their written work when the month is signed (FLD-01). Check a visit day (FLD-02). Send First Provider Onboarding in person (FLD-03). Send Office and Society Contracts to write a maintenance contract from the signed papers (FLD-04). Name a town that produced nothing (FLD-05). Stop an incident the same day (FLD-06). Click a step to open the full card.",
          reporting: "You write the weekly field report to Marketing Manager. The three jobs write their same-day files and weekly notes to you. Click a row to open the full report.",
          standards: "The bar this job is measured against. Click a card to read the full standard.",
          kpis: "How Marketing Manager knows this job is working. Each number has a target, a reason it matters, and a counting rule so two people cannot argue about the number.",
          escalations: "What to do when there is a fight, a town that will not sign first local workers, a contract or board that might be untrue, or someone we pay to visit who has no written contract. Click a row to open the full steps."
        },
        hub: {
          icon: "storefront",
          line: "You give written work to the three ground jobs and check the result. They visit the market, get first local workers to sign, and write office contracts. You do not do those jobs yourself unless that job has nobody in it."
        },
        definition: {
          what: [
            { title: "You give Field Visitor their written work — the person who actually visits the market", why: "Named town, named day. Stall, street, or follow-up. They collect names for Sales. They may be full-time or someone we pay on a written contract. A verbal yes is not a visitor. Someone we pay to visit must have a written contract." },
            { title: "You give First Provider Onboarding and Office and Society Contracts their written work", why: "First Provider Onboarding goes in person to the first local workers, gets them to sign the papers, then sends the file to Provider Operations. Office and Society Contracts writes a maintenance contract from the signed papers Offer and Finance already approved. A verbal yes is not a contract." },
            { title: "You write one calendar and send honest numbers up", why: "Marketing Manager cannot run Field without visits, first local workers, and contracts shown separately. If a job has nobody in it, you do that work and write that you are doing it today because nobody is in that job." }
          ],
          why: [
            { title: "Kashmir still buys from people they can see", why: "Stalls alone are not enough. A town also needs the first local workers on site, and offices and societies on a written contract. If one person tries to do all of that without naming who visits, nobody owns the result." },
            { title: "Visiting the market, getting first local workers to sign, and writing office contracts are three jobs", why: "Field Visitor collects names at the stall. Sales books the job. Nobody takes money or books the job at the stall. First local workers are not a private team. A verbal yes from an office is not a contract. Three jobs, three results." },
            { title: "Hotel desks are Partnerships & Channels Manager. Jobs are Provider Operations. Towns are Market Expansion Manager", why: "You do not sign hotels that send us guests. You do not put workers on customer jobs. You do not pick a new town. You get the first local workers to sign the papers, then send the file to Provider Operations." }
          ]
        },
        glossary: [
          {
            id: "field-visitor",
            term: "Field Visitor",
            aliases: ["Field Visitor", "visitor", "someone we pay to visit", "someone we pay to visit, on a written contract"],
            meaning: "The person who actually visits the market — a stall, a street, a follow-up. They report to you. They may be full-time, or someone we pay on a written contract. The job is the same whether the person is a full-time employee or someone we pay on a written contract. A verbal yes is not a visitor. Someone we pay to visit must have a written contract. They collect names for Sales the same day. They do not take a booking or money."
          },
          {
            id: "first-provider-onboarding",
            term: "First Provider Onboarding",
            aliases: ["First Provider Onboarding", "first local workers", "first local worker"],
            meaning: "The person who goes in person to the first local workers in a named town and gets them to sign the papers so they can work with Panun Kaergar. They send the signed file to Provider Operations the same week. They do not give them a customer job. They do not keep a private team."
          },
          {
            id: "office-society-contracts",
            term: "Office and Society Contracts",
            aliases: ["Office and Society Contracts", "office contract", "society contract", "maintenance contract"],
            meaning: "The person who writes maintenance contracts with named offices and housing societies, from the signed contract papers. A verbal yes is not a contract. Hotel desks and shops that send us customers are Partnerships — not this job."
          },
          {
            id: "first-provider-pack",
            term: "First-worker papers",
            aliases: ["first-worker papers", "signed first-worker papers", "provider pack"],
            meaning: "The signed papers Offer, Finance, and Provider Operations already approved: who we are, what the first local worker will do, what they will not do, and what we pay. First Provider Onboarding may not invent a payment. If a price is missing, stop."
          },
          {
            id: "contract-pack",
            term: "Signed contract papers",
            aliases: ["signed contract papers", "contract pack", "maintenance pack"],
            meaning: "The signed papers Offer and Finance already approved for offices and societies: services in, services out, signed price, how we invoice. Office and Society Contracts may not invent a price. If they want a different price, stop."
          },
          {
            id: "stall-day",
            term: "Visit day",
            aliases: ["visit day", "visit days", "stall day", "stall days", "field day", "field days"],
            meaning: "A named day on the signed calendar when Field Visitor stands in a named town with approved boards and flyers. If it is not on the calendar, it is not a visit day. A random Saturday is not a visit day."
          },
          {
            id: "stall-list",
            term: "Stall list",
            aliases: ["stall list", "same-day stall list", "field list", "field lead list"],
            meaning: "The written list Field Visitor sends to Sales before they leave the town. Each row: name, phone, service, town, source = stall + town + date. A missing phone is not an enquiry. You get the count, not the phone numbers."
          },
          {
            id: "stall-budget",
            term: "Stall budget",
            aliases: ["stall budget", "field budget", "field money", "stall money"],
            meaning: "The amount of money on the monthly plan that Field may spend on visit days, boards, and flyers. Digital may not take this money for ads. You may not hide unused money for a later visit that is not on the plan."
          },
          {
            id: "dead-town",
            term: "Town that produced nothing",
            aliases: ["town that produced nothing", "town that produced nothing", "town that produced nothing"],
            meaning: "A named town on the calendar that had three planned visits with no names, no first local worker, and no contract movement. You name it in the weekly report. You do not keep visiting to look busy. You do not pick a different town yourself."
          },
          {
            id: "mkm",
            term: "Marketing Manager",
            aliases: ["Marketing Manager"],
            meaning: "The person you report to. They write the monthly plan, the marketing kit, and the stall budget. They give you written work and check the result and Digital. A new town, service, price, or face goes to them first — not to Head of Growth."
          },
          {
            id: "dmm",
            term: "Digital Marketing Manager",
            aliases: ["Digital Marketing Manager"],
            meaning: "The person who runs paid ads, SEO (search), social, and videos. They report to Marketing Manager, same as you. They do not stand at your stall. You do not run their ads."
          },
          {
            id: "fmm",
            term: "Field Marketing Manager",
            aliases: ["Field Marketing Manager"],
            also: "This job — you",
            meaning: "This job. You give Field Visitor their written work, First Provider Onboarding, and Office and Society Contracts. You do not do those jobs yourself unless that job has nobody in it — and then you write that you are doing it today because nobody is in that job."
          },
          {
            id: "sales",
            term: "Sales",
            aliases: ["Sales"],
            meaning: "The people who book the customer. Field Visitor sends them the stall list the same day. One-off jobs from a person in an office still go to Sales unless the maintenance contract already covers them."
          },
          {
            id: "provider-ops",
            term: "Provider Operations",
            aliases: ["Provider Operations", "Provider Operations Manager"],
            meaning: "The people who run providers on jobs. First Provider Onboarding hands them the signed file. You do not assign the first job. You do not keep the first local workers as a private team."
          },
          {
            id: "partnerships",
            term: "Partnerships",
            aliases: ["Partnerships", "Partnerships & Channels Manager"],
            meaning: "Hotel desks, shops, and other people who send us their customers. That is not this job. Offices and societies that buy maintenance are Office and Society Contracts."
          },
          {
            id: "operations-ask",
            term: "Operations asks where they found us",
            aliases: ["Operations asks where they found us", "How did you find us"],
            meaning: "If a person later calls, WhatsApps, uses the app, or fills a form, Operations asks how they found us and writes the answer. That is not the stall list. The stall list is only for people Field Visitor met."
          },
          {
            id: "acting-owner",
            term: "Doing that job today",
            aliases: ["doing that job today", "acting owner"],
            meaning: "When nobody sits in Field Visitor, First Provider Onboarding, or Office and Society Contracts, you do that job for now and write it down. When nobody sits in this job, Marketing Manager does that work and writes that they are doing it today because nobody is in that job. You do not cover Digital."
          }
        ],
        responsibilities: [
          {
            kicker: "Responsibility 1",
            title: "Give Field Visitor their written work and check the result",
            lead: "Give them which towns, which days, which boards. They visit the market. Full-time or someone we pay on a written contract. If nobody sits there, you visit and write that you are doing that job.",
            art: "cmc-h-kit.png",
            body: [
              "A calendar with nobody walking it is decoration. The day the month is signed, give Field Visitor a written brief. If they are someone we pay on a written contract, attach their written contract. A chat is not the contract.",
              "They collect names for Sales the same day. They do not book. They do not take money. You get the count, not the phone numbers."
            ],
            points: [
              { title: "Written brief, not a voice note", why: "Someone we pay to visit who only has a chat message will invent the company." },
              { title: "Empty box is written as you doing that job", why: "Silence looks like the visit happened." },
              { title: "They send the list to Sales", why: "You run this job. You do not sit on the phones." }
            ],
            meta: [
              ["You take from", "The signed month from Marketing Manager"],
              ["You give to", "Field Visitor (the brief); Sales gets the list from them"]
            ]
          },
          {
            kicker: "Responsibility 2",
            title: "Give First Provider Onboarding their written work and check the result",
            lead: "Send them on site to the first service providers in a named town. They use the signed papers. Then they hand the signed providers to Provider Operations to run on jobs.",
            art: "cmc-h-shot.png",
            body: [
              "A stall in a town with no first local workers is how the brand dies in public. Expansion has already opened the town. This job signs the first people who will do the jobs, on site, in writing.",
              "They do not hire in a WhatsApp group. They do not assign the first job. If the signed papers are missing a price, stop. Finance signs price."
            ],
            points: [
              { title: "On site, with the signed papers", why: "A verbal yes in a tea shop is not enough. They cannot work with us until they have signed the papers." },
              { title: "Hand to Provider Operations the same week", why: "A private list is not a system." },
              { title: "You do not run their jobs", why: "That is Provider Operations." }
            ],
            meta: [
              ["You take from", "The signed month; the signed first-worker papers"],
              ["You give to", "First Provider Onboarding (the brief); Provider Operations gets the signed file from them"]
            ]
          },
          {
            kicker: "Responsibility 3",
            title: "Give Office and Society Contracts their written work and check the result",
            lead: "Give them the signed contract papers. They write maintenance contracts with named offices and housing societies. They do not invent a price. One-off jobs still go to Sales.",
            art: "cmc-h-yes.png",
            body: [
              "A verbal yes from a society is not a contract. Operations cannot plan jobs from a verbal yes. Hotel desks and shops that send us customers are Partnerships. This job is the office or housing society that buys ongoing maintenance from us.",
              "They do not take cash in the office. Finance sends the invoice."
            ],
            points: [
              { title: "Approved pack only", why: "A made-up price becomes a fight." },
              { title: "Named building, named services, signed price", why: "A WhatsApp yes is not a contract." },
              { title: "Not Partnerships", why: "Referral partners stay with Partnerships." }
            ],
            meta: [
              ["You take from", "The signed month; the signed contract papers"],
              ["You give to", "Office and Society Contracts (the brief); Operations gets the live building from them"]
            ]
          },
          {
            kicker: "Responsibility 4",
            title: "One calendar, three jobs",
            lead: "Write which days are visits, which days are first-worker visits, which days are office and society visits. Nobody invents their own week.",
            art: "cmc-h-files.png",
            body: [
              "Otherwise three people act like three companies. The calendar is the boss. Expansion has already opened the town. Marketing Manager named the month. You turn that into days for the three jobs.",
              "If you cannot staff a day, write the miss the same day. Silence looks like we stood there."
            ],
            points: [
              { title: "Named day, named job", why: "A stall on a random Saturday that is not on the plan cannot be measured." },
              { title: "Write the same day that the visit did not happen", why: "Head of Growth must not think we visited." },
              { title: "Do not pick a new town", why: "Marketing Manager changes the calendar." }
            ],
            meta: [
              ["You take from", "The signed month"],
              ["You give to", "The three jobs (their days)"]
            ]
          },
          {
            kicker: "Responsibility 5",
            title: "Written packs, not chat",
            lead: "Each job gets a written brief the day the month is signed: towns, kit, pack, what we will not do. A chat message is not the brief.",
            art: "cmc-r-consent.png",
            body: [
              "This is FLD-01. If a job is a third-party person, attach their written contract. If the signed papers are missing a price, stop and tell Marketing Manager. Do not let them invent it on site."
            ],
            points: [
              { title: "Same day the month is signed", why: "A late brief is a week of invented work." },
              { title: "Save the briefs as files", why: "A new person opening the folder must find them." },
              { title: "Someone we pay on a written contract needs a written contract", why: "A visitor with only a verbal yes is not this company." }
            ],
            meta: [
              ["You take from", "Marketing Manager (the signed month and packs)"],
              ["You give to", "The three jobs (written briefs)"]
            ]
          },
          {
            kicker: "Responsibility 6",
            title: "Send honest numbers up — visits, first local workers, and contracts",
            lead: "Each week: visits vs calendar, names to Sales, first local workers who signed, office and society contracts signed, days that did not happen, town that produced nothing, one or two written requests.",
            art: "cmc-p-weekly.png",
            body: [
              "This is FLD-07. Marketing Manager needs this page for the weekly marketing report. Do not hide a town that produced nothing inside ‘field was busy’. Do not send only stall numbers if first local workers and contracts sat still.",
              "If you covered an empty job, write that too."
            ],
            points: [
              { title: "Three parts, not one lump", why: "A good stall week must not hide a town with no first local workers." },
              { title: "Name what failed", why: "A report with only wins is not a management system." },
              { title: "One or two written requests", why: "Reprint these boards, pause this town, or confirm next month’s days — not ten wishes." }
            ],
            meta: [
              ["You take from", "The three weekly notes"],
              ["You give to", "Marketing Manager; Intelligence (signals only)"]
            ]
          }
        ],
        handoffs: [
          {
            side: "in",
            kicker: "You receive",
            title: "The month’s written field work from Marketing Manager",
            lead: "Which towns, which days, which boards, the stall budget, which first-worker towns, which offices and societies, the signed papers, and what we will not advertise.",
            art: "cmc-h-kit.png",
            body: [
              "This is your working calendar. You do not write your own month. You do not pick a new town. You turn this file into written briefs for the three jobs the same day (FLD-01)."
            ],
            points: [
              { title: "The file must name", why: "Towns, days, boards, stall budget, first-worker papers, contract pack, what we will not advertise." }
            ],
            meta: [
              ["From", "Marketing Manager"],
              ["When", "The same day Head of Growth signs, and whenever the plan changes"]
            ]
          },
          {
            side: "in",
            kicker: "You receive",
            title: "Weekly notes from the three jobs",
            lead: "Field Visitor: visits vs calendar, names to Sales, town that produced nothing. First Provider Onboarding: signed files handed to Provider Operations. Office and Society Contracts: contracts signed, waiting, or failed.",
            art: "cmc-p-weekly.png",
            body: [
              "These become the three pages of your weekly field report. If a note is late, write how many days it is late. Do not invent their numbers. If a job has nobody in it, you wrote that you are doing that job, and you pull the numbers yourself."
            ],
            points: [
              { title: "Three notes, not one story", why: "A stall week must not hide a town with no first local workers." }
            ],
            meta: [
              ["From", "Field Visitor; First Provider Onboarding; Office and Society Contracts"],
              ["When", "The day before your weekly field report"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Written work to the three jobs",
            lead: "FLD-01. Towns, days, packs, where a file must land, what we will not do. If a visitor is someone we pay on a written contract, attach their written contract.",
            art: "cmc-r-library.png",
            body: [
              "A chat message is not the brief. Save the files. If the signed papers are missing a price, stop and tell Marketing Manager before they go on site."
            ],
            points: [
              { title: "The brief must name", why: "Which job, which towns, which days, which pack, who receives the signed file." }
            ],
            meta: [
              ["To", "Field Visitor; First Provider Onboarding; Office and Society Contracts"],
              ["When", "The same day Marketing Manager gives you the signed month"]
            ]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Weekly field report to Marketing Manager",
            lead: "Visits vs calendar, names to Sales by town, first local workers who signed and handed to Provider Operations, office and society contracts signed, days that did not happen, town that produced nothing, one or two written requests.",
            art: "cmc-p-weekly.png",
            body: [
              "This is the field page of Marketing Manager’s weekly report. What failed must be named. Not a voice note. Not only stalls."
            ],
            points: [
              { title: "The report must name", why: "Week dates, visits, first local workers, contracts, days that did not happen, town that produced nothing, covering-empty-job notes, who owns each ask." }
            ],
            meta: [
              ["To", "Marketing Manager"],
              ["When", "The day before the marketing weekly report"]
            ]
          }
        ],
        workflow: [
          {
            kicker: "FLD-01 · Step 1",
            title: "Give the three jobs their written work for the month",
            lead: "The same day Marketing Manager gives you the signed month. Towns, days, packs, where a file must land. If a visitor is someone we pay on a written contract, attach their written contract.",
            art: "cmc-h-kit.png",
            body: [
              "If a job is empty, write that you are doing that job. If Head of Growth has not signed the month, write that to Marketing Manager and wait. Do not invent a town."
            ],
            points: [
              { title: "What you produce", why: "Three written briefs, saved as files." },
              { title: "What you do not produce", why: "A chat that says ‘same as last month’." }
            ]
          },
          {
            kicker: "FLD-02 · If it is a visit day",
            fork: "yes",
            title: "Check Field Visitor on a stall or market day",
            lead: "Confirm they have town, pitch, and boards. They collect names and send the list to Sales the same day. They do not book. They do not take money.",
            art: "cmc-h-shot.png",
            body: [
              "If nobody sits in that job, you visit yourself and write that you are doing that job. You still do not take a booking or money."
            ],
            points: [
              { title: "When", why: "A named visit day on the signed calendar." },
              { title: "What you produce", why: "A visit that actually happened, or a written note that the visit did not happen, and a list in Sales." }
            ]
          },
          {
            kicker: "FLD-03 · If the town needs first local workers",
            fork: "no",
            title: "Send First Provider Onboarding on site",
            lead: "Approved pack. On site. Signed file to Provider Operations the same week. Do not assign the first job.",
            art: "cmc-h-files.png",
            body: [
              "If the signed papers are missing a price, stop. Finance signs price. You do not invent it. If nobody sits in that job, you go on site yourself and write that you are doing that job."
            ],
            points: [
              { title: "You do", why: "Send them with the signed papers, or go yourself if the job is empty." },
              { title: "You do not", why: "Keep the first local workers as a private team." }
            ]
          },
          {
            kicker: "FLD-04 · If it is an office or society day",
            fork: "no",
            title: "Send Office and Society Contracts to write a maintenance contract from the signed papers",
            lead: "They go to the named building with named services and a signed price. No cash in the office. They file it. They tell Operations they can plan the jobs. You do that work yourself only if that job has nobody in it, and you write that down.",
            art: "cmc-h-yes.png",
            body: [
              "One-off jobs from a person in that building still go to Sales unless the contract already covers them. Hotel desks stay with Partnerships."
            ],
            points: [
              { title: "What you produce", why: "A signed contract file, or a written note that the visit did not happen." },
              { title: "What you do not produce", why: "A verbal yes. A cash collection. A referral partner." }
            ]
          },
          {
            kicker: "FLD-05 · If a town produced nothing after three visits",
            fork: "no",
            title: "Name the town that produced nothing. Do not keep visiting to look busy",
            lead: "No names, no first local worker, and no contract movement. Write town, dates, what was tried. Send it in the weekly report.",
            art: "cmc-e-stop.png",
            body: [
              "You do not pick a different town yourself. Marketing Manager changes the calendar. Head of Growth signs if the month must change."
            ],
            points: [
              { title: "You do", why: "Name it in writing." },
              { title: "You send", why: "Marketing Manager, in the weekly report." }
            ]
          },
          {
            kicker: "FLD-06 · If there is a fight, a false claim, or someone taking bookings in our name",
            fork: "no",
            title: "Stop. Write it. Tell Marketing Manager the same day",
            lead: "Take down a board that might be untrue. Stop a contract that might be untrue. Do not take the booking to calm someone down.",
            art: "cmc-e-stop.png",
            body: [
              "Write what happened, the time, and who was there. Keep the rest of the signed plan running if it is still allowed."
            ],
            points: [
              { title: "You do", why: "Stop that visit. Write the incident." },
              { title: "You send", why: "Marketing Manager the same day." }
            ]
          }
        ],
        reporting: [
          {
            period: "Weekly",
            kicker: "FLD-R1",
            title: "Weekly field report",
            art: "cmc-p-weekly.png",
            when: "The day before the marketing weekly report. Must name what failed, not only what worked.",
            lead: "Show visits, first local workers, and contracts separately. Name towns that are dead three visits in a row.",
            body: [
              "This is the field page of Marketing Manager’s weekly report. Do not hide a town that produced nothing inside ‘field was busy’. Do not send only stall numbers."
            ],
            contents: [
              { title: "Visit days vs calendar (completed / missed)", why: "A day that did not happen is a missed town. Write it." },
              { title: "Names to Sales, by town", why: "Dead stall towns must be named so the plan can change." },
              { title: "First local workers who signed and handed to Provider Operations", why: "A town with a stall and no first local workers is a brand problem." },
              { title: "Office and society contracts signed, waiting, or failed", why: "A verbal yes is not a contract." },
              { title: "Empty jobs you covered this week", why: "A box with no name has no owner." }
            ],
            mustHave: ["Week", "Visits × town", "First local workers", "Contracts", "Days that did not happen", "Dead-town flag", "Covering-empty-job notes", "One or two written requests"],
            submit: [
              { to: "Marketing Manager", why: "This is the field page of the weekly marketing report." },
              { to: "Head of Growth", why: "Only if a town is dead three visits and still on next month’s draft — through Marketing Manager." }
            ]
          },
          {
            period: "Same day",
            kicker: "FLD-R2",
            title: "Covering-empty-job note",
            art: "cmc-h-note.png",
            when: "Any day Field Visitor, First Provider Onboarding, or Office and Society Contracts is empty, and you are doing that job.",
            lead: "Write which box is empty, that you are doing that job, and the same-day checks you did in that job’s name.",
            body: [
              "If you do that job without writing it, nobody owns the result. Marketing Manager must see this on the weekly report too."
            ],
            contents: [
              { title: "Which job is empty", why: "Visitor, first local workers, or contracts — name it." },
              { title: "What you did today in that job’s name", why: "The same-day list, the signed file, or the contract visit." }
            ],
            mustHave: ["Date", "Which job", "You are doing that job today", "Same-day checks"],
            submit: [
              { to: "File", why: "Every day the job is empty." },
              { to: "Marketing Manager", why: "On the weekly field report." }
            ]
          }
        ],
        standards: [
          {
            title: "Every visit, first-worker visit, and contract visit is a named town on the signed plan",
            lead: "If it is not on the calendar, nobody goes.",
            art: "cmc-h-kit.png",
            body: [
              "Expansion has opened the town. Marketing Manager named the month. You named the day and the job. A random Saturday is not this company."
            ],
            points: [
              { title: "What good looks like", why: "A stranger can open the calendar and see which town, which day, which job, and the date it was signed." }
            ]
          },
          {
            title: "If Field Visitor, First Provider Onboarding, or Office and Society Contracts is empty, that is written as you doing that job",
            lead: "Silence looks like the visit happened.",
            art: "cmc-h-shot.png",
            body: [
              "Cover the job. Write it. Do the same-day checks. Do not pretend the job is filled."
            ],
            points: [
              { title: "What good looks like", why: "Every empty day has a covering note dated the same day." }
            ]
          },
          {
            title: "A Field Visitor who is someone we pay on a written contract has a written contract",
            lead: "A verbal yes is not a visitor. Someone we pay to visit must have a written contract.",
            art: "cmc-h-yes.png",
            body: [
              "Full-time employee or someone we pay on a written contract — the job is the same either way. Someone we pay to visit needs a written contract before they visit. Stop the visit if they do not have one."
            ],
            points: [
              { title: "What good looks like", why: "Every third-party visit day traces to a dated written contract." }
            ]
          },
          {
            title: "First local workers are handed to Provider Operations after they sign",
            lead: "They are not kept as a private team.",
            art: "cmc-h-files.png",
            body: [
              "Signed file the same week. You do not assign the first job. You do not skip Provider Operations."
            ],
            points: [
              { title: "What good looks like", why: "Every signed first-provider file has a received date from Provider Operations." }
            ]
          },
          {
            title: "Office and society contracts use the signed papers only",
            lead: "No invented price. No cash in the office. Hotel desks stay with Partnerships.",
            art: "cmc-e-stop.png",
            body: [
              "A WhatsApp yes is not a contract. Finance sends the invoice. Operations does the jobs."
            ],
            points: [
              { title: "What good looks like", why: "Every live building traces to a signed pack file." }
            ]
          },
          {
            title: "The weekly field report names visits, first local workers, and contracts",
            lead: "Not only stalls.",
            art: "cmc-p-weekly.png",
            body: [
              "A good stall week must not hide a town with no first local workers or no contract movement. Town that produced nothing are named."
            ],
            points: [
              { title: "What good looks like", why: "The weekly report names visits, first local workers, and contracts — plus days that did not happen, towns that produced nothing, and notes of days you covered an empty job." }
            ]
          }
        ],
        kpis: [
          {
            name: "Visit days vs the calendar",
            target: "95% or more completed, or a written note the same day that the visit did not happen",
            why: "A missed field day is a missed town.",
            how: "Count visit days on the signed calendar this month. A day counts as done if Field Visitor’s stall log exists, or your covering note exists. A miss counts if it was written the same day. An unwritten miss counts against the rate."
          },
          {
            name: "Stall names that reach Sales the same day",
            target: "95% or more",
            why: "If it is not sent the same day, it did not happen.",
            how: "Count complete rows Sales received the same calendar day as the visit, divided by all complete rows written at visits that week. Incomplete rows (no phone) are not in either number."
          },
          {
            name: "First local workers who signed and handed to Provider Operations",
            target: "Every named town on the plan has a dated file, or a written note that nobody signed",
            why: "A stall with no first local workers trains people to hate the brand.",
            how: "Each named first-provider town this month has either a signed file Provider Operations received, or a written note that nobody signed dated the same week the visits finished."
          },
          {
            name: "Office and society contracts from the signed papers only",
            target: "100%",
            why: "A verbal yes is not a contract. A made-up price becomes a fight.",
            how: "Every live building this month traces to a signed pack file. Any cash collected on site, or any price not in the signed papers, is a miss."
          },
          {
            name: "Empty visitor / first-worker / contracts job written as you doing it today because nobody is in that job",
            target: "100%",
            why: "A box with no name has no owner.",
            how: "Every working day a child box is empty has a covering note dated that day. An empty day with no note counts against the rate."
          },
          {
            name: "Weekly field report on time — visits, providers, contracts",
            target: "100%",
            why: "Marketing Manager needs numbers, not a story with only good stalls.",
            how: "A written report exists the working day before the marketing weekly report, with visits, first local workers, contracts, days that did not happen, town that produced nothing, covering notes, and one or two written requests."
          }
        ],
        escalations: [
          {
            title: "A fight, a false claim, or someone taking bookings in our name",
            lead: "Trust dies in the street if we look like a shop that takes cash and argues.",
            art: "cmc-e-stop.png",
            body: [
              "Stop that visit. Write what happened. Tell Marketing Manager the same day. Do not take the booking to calm someone down."
            ],
            points: [
              { title: "You do", why: "Stop. Write the time and who was there." },
              { title: "You send to", why: "Marketing Manager the same day." }
            ]
          },
          {
            title: "The town is not ready — no first local workers and none will sign",
            lead: "Standing there to look busy trains people to hate the brand.",
            art: "cmc-h-kit.png",
            body: [
              "Do not keep the stall up. Write the town and what is missing. Marketing Manager tells Head of Growth."
            ],
            points: [
              { title: "You do", why: "Pack up. Write that it did not happen." },
              { title: "You send to", why: "Marketing Manager the same day." }
            ]
          },
          {
            title: "A board, flyer, or contract might be untrue, or shows a price that is not approved",
            lead: "A wrong price becomes a fight later.",
            art: "cmc-e-stop.png",
            body: [
              "Take it down. Do not leave a bad contract signed. Show the file. Tell Marketing Manager before it stays up."
            ],
            points: [
              { title: "You do", why: "Take it down. Write what went out." },
              { title: "You send to", why: "Marketing Manager the same day, with the file." }
            ]
          },
          {
            title: "Someone we pay to visit is working with no written contract",
            lead: "A verbal yes is not a visitor. Someone we pay to visit must have a written contract.",
            art: "cmc-h-yes.png",
            body: [
              "Stop the visit. Write that it did not happen. Tell Marketing Manager the same day. Do not send them out again until the contract is a file."
            ],
            points: [
              { title: "You do", why: "Stop. Write that it did not happen." },
              { title: "You send to", why: "Marketing Manager the same day." }
            ]
          }
        ]
      }
    },
    fve: {
      story: "You actually visit the market. Field Marketing Manager writes which towns, which days, which boards. You show up. You collect names for Sales the same day. You write stall, plus the town and the date, because you met the person. You may be full-time, or someone we pay on a written contract. The job is the same whether the person is a full-time employee or someone we pay on a written contract. You do not take the booking. You do not take money.",
      receives: [
        { from: "Field Marketing Manager", what: "Which towns, which days, which boards and flyers, where a stall list must land in Sales, and what we will not advertise. If you are someone we pay on a written contract, your written contract." }
      ],
      gives: [
        { to: "Sales", what: "A complete stall list the same day: name, phone, service, town, stall + town + date." },
        { to: "Field Marketing Manager", what: "The count, the stall log, days that did not happen, town that produced nothing, materials that need reprinting, and the weekly visit note." }
      ],
      records: ["Written brief for the month", "Stall list for each visit day", "Stall log", "Weekly visit note", "Notes of days that did not happen", "Your written contract if you are someone we pay on a written contract"],
      good: ["Every visit was a named town on the plan.", "Sales received the list the same day.", "No cash and no booking at the stall.", "Boards matched this month’s marketing kit.", "A day that did not happen was written the same day.", "If you are someone we pay on a written contract, you had a written contract in hand."],
      bad: ["A random Saturday stall because you were nearby.", "Taking a booking to look busy.", "A flyer with last year’s price.", "A list that arrived the next morning.", "Visiting with no written third-party contract.", "Onboarding first local workers or writing office contracts as if that were this job."],
      detailed: {
        copy: {
          heroKicker: "What this job is",
          defTitle: "What this job is",
          lanesTitle: "This job has three parts",
          definition: "You actually visit the market — a stall, a street, or a follow-up. Field Marketing Manager writes which town, which day, and which boards. You show up, collect name, phone, service, and town, and send that list to Sales the same day. You write stall, plus the town and the date, because you met the person. You do not take money or book the job. You may be a full-time employee or someone we pay on a written contract — the work is the same. You do not get first local workers to sign papers, and you do not write office contracts.",
          owns: "These are the six things this job owns. Do not do Sales’ job, First Provider Onboarding, or Office and Society Contracts.",
          lanes: "This job has one result. You do all three parts yourself. Go to the town. Collect names. Do the office work. If this job is empty, Field Marketing Manager does this work today. They write that down.",
          responsibilities: "These are the six parts of the job. Click a row to open the full card.",
          handoffs: "Work arrives as a written file and leaves as a written file. A chat message is not the file. Click a row to open the full card.",
          workflow: "Every working day, open the written brief, the marketing kit, and the materials bag. If it is a visit day, run VIS-01 then send the list (VIS-02). If it is an office day, restock and confirm the next visit (VIS-03). If a town produced nothing after three visits, name it (VIS-05). If something goes wrong at the stall, stop it the same day (VIS-06). Click a step to open the full card.",
          reporting: "You write three reports. Same-day stall list to Sales. Stall log. Weekly visit note to Field Marketing Manager. Click a row to open the full report.",
          standards: "The bar this job is measured against. Click a card to read the full standard.",
          kpis: "How Field Marketing Manager knows this job is working. Each number has a target, a reason it matters, and a counting rule.",
          escalations: "What to do when there is a fight, a town with no providers, a board that might be untrue, or you are someone we pay on a written contract with no written contract. Click a row to open the full steps."
        },
        hub: {
          icon: "storefront",
          line: "You stand at the stall. You collect names. Sales books. Operations does the job."
        },
        definition: {
          what: [
            { title: "You stand in the named town on the named day", why: "Field Marketing Manager writes the calendar. Expansion has already opened the town. You do not pick a new town because you were nearby." },
            { title: "You collect names for Sales. You do not book the job", why: "Name, phone, service, town. Stall, plus town and date, because you met the person. Say Sales will call. Do not take money." },
            { title: "You send the stall list to Sales the same day, with stall plus the town and the date", why: "Sales books the job. You do not book or take money. You may be a full-time employee or someone we pay on a written contract — the work is the same. If you are paid on a contract, that written contract must be in your hand before you visit." }
          ],
          why: [
            { title: "Kashmir still buys from people they can see", why: "If stalls are a mood, nobody can tell which towns work. Named town, named day, approved boards, same-day list." },
            { title: "You collect names at the stall. Sales books the job. You do not take money or book the job yourself", why: "If you take the booking at the stall, Sales has no list, Operations cannot finish the job as a system, and Head of Growth cannot see the truth." },
            { title: "Visiting is not getting first local workers to sign papers, and is not writing office contracts", why: "Those are two other jobs under Field Marketing Manager. You walk the market." }
          ]
        },
        glossary: [
          { id: "stall-day", term: "Visit day", aliases: ["visit day", "stall day", "stall days"], meaning: "A named day on the signed calendar when you stand in a named town with approved boards and flyers. If it is not on the calendar, it is not a visit day." },
          { id: "stall-list", term: "Stall list", aliases: ["stall list", "same-day stall list"], meaning: "The written list you send to Sales before you leave the town. Each row: name, phone, service, town, source = stall + town + date. A missing phone is not an enquiry. Field Marketing Manager gets the count, not the phone numbers." },
          { id: "stall-log", term: "Stall log", aliases: ["stall log", "field log"], meaning: "The written proof you stood where the plan said: town, pitch, start and end, materials used, how many names you collected, incidents, and that you took no cash and no booking." },
          { id: "signed-kit", term: "Marketing kit", aliases: ["marketing kit", "signed kit"], meaning: "The instruction book for this month: logo, colours, sentences, prices, towns, and faces. Your boards and flyers must match this book. A new sentence waits for a written yes from Field Marketing Manager (VIS-04)." },
          { id: "third-party", term: "Someone we pay to visit, on a written contract", aliases: ["third party", "third-party", "contract visitor"], meaning: "This job filled by a person who is not a full-time employee, on a written contract. The job is the same whether the person is a full-time employee or someone we pay on a written contract. A verbal yes is not this job. Do not visit until the written contract is a file. Do not visit until the contract is a file." },
          { id: "fmm", term: "Field Marketing Manager", aliases: ["Field Marketing Manager"], meaning: "The person you report to. They give you the brief. They hold First Provider Onboarding and Office and Society Contracts too. If nobody is in this job, they do that work and write that you are doing it today because nobody is in that job." },
          { id: "sales", term: "Sales", aliases: ["Sales"], meaning: "The people who book the customer. You send them the stall list the same day. You do not book at the stall." },
          { id: "operations-ask", term: "Operations asks where they found us", aliases: ["Operations asks where they found us", "How did you find us"], meaning: "If a person later calls, WhatsApps, uses the app, or fills a form, Operations asks how they found us and writes the answer. That is not your stall list. Your stall list is only for people you met." },
          { id: "fve", term: "Field Visitor", aliases: ["Field Visitor"], also: "This job — you", meaning: "This job. You visit the market. You collect names for Sales. You do not book. You do not take money." }
        ],
        responsibilities: [
          {
            kicker: "Responsibility 1",
            title: "Show up where the plan says",
            lead: "Named town. Named day. Named pitch. Approved boards. Confirm the day before. A random Saturday is not this job.",
            art: "cmc-h-kit.png",
            body: [
              "The written brief is the boss. Expansion has already opened the town. Field Marketing Manager has already named the day. The day before, confirm the pitch, the boards, and that the town is still on the plan.",
              "If you cannot go, write it the same day. If you are someone we pay on a written contract, do not go without a written contract in your hand."
            ],
            points: [
              { title: "On the plan, or do not stand", why: "Random stalls cannot be measured." },
              { title: "Confirm the day before", why: "A locked pitch and missing boards waste the day." },
              { title: "Write the same day that the visit did not happen", why: "Silence looks like you stood there." }
            ],
            meta: [
              ["You take from", "The written brief; the marketing kit"],
              ["You give to", "A visit that actually happened, or a written note that the visit did not happen"]
            ]
          },
          {
            kicker: "Responsibility 2",
            title: "Collect names. Do not book.",
            lead: "Name, phone, service, town. Stall + town + date. Say Sales will call. Do not take money. Do not promise a job on the spot.",
            art: "cmc-h-note.png",
            body: [
              "You collect names at the stall. Sales books the job. You do not take money or book the job yourself. You meet the person, so you write stall, plus the town and the date. If they later call Operations, Operations asks how they found us. You do not own that call.",
              "A missing phone is not an enquiry. Do not pad the list. Do not take a booking to look busy."
            ],
            points: [
              { title: "Complete row, or it is not an enquiry", why: "Sales cannot call a name with no phone." },
              { title: "Say Sales will call", why: "A job promise at the stall becomes a fight." },
              { title: "No cash", why: "Money at the stall has no owner in the books." }
            ],
            meta: [
              ["You take from", "People who stop at the stall"],
              ["You give to", "Sales (the list); Field Marketing Manager (the count)"]
            ]
          },
          {
            kicker: "Responsibility 3",
            title: "Use only this month’s boards and flyers",
            lead: "The marketing kit is the instruction book. A new sentence, price, town, or face waits for a written yes from Field Marketing Manager.",
            art: "cmc-h-yes.png",
            body: [
              "Pack only boards and flyers from this month’s marketing kit. Last year’s price is a brand problem. If you want to write something extra on the board, stop. That is VIS-04.",
              "When you pack up, count what is torn or gone. Tell Field Marketing Manager what needs reprinting. Do not print a new flyer yourself."
            ],
            points: [
              { title: "Kit only", why: "One promise. The flyer and the ad must not disagree." },
              { title: "Do not print your own", why: "A private print is a second company." }
            ],
            meta: [
              ["You take from", "This month’s marketing kit"],
              ["You give to", "Field Marketing Manager (what needs reprinting, or a written ask to change the kit)"]
            ]
          },
          {
            kicker: "Responsibility 4",
            title: "Send the stall list to Sales the same day",
            lead: "Before you leave the town. One list. Confirm they received it. Field Marketing Manager gets the count, not the phone numbers.",
            art: "cmc-r-consent.png",
            body: [
              "This is VIS-02. A list that arrives tomorrow is a person nobody called. If Sales did not receive it, send it again the same day."
            ],
            points: [
              { title: "Before you leave the town", why: "Morning-after lists are how names die." },
              { title: "Count to Field Marketing Manager", why: "They do not need the phone numbers. They need whether the town worked." }
            ],
            meta: [
              ["You take from", "The stall"],
              ["You give to", "Sales (the list); Field Marketing Manager (the count)"]
            ]
          },
          {
            kicker: "Responsibility 5",
            title: "Write the stall log",
            lead: "Town, pitch, times, materials, count, incidents, no cash, no booking. Packed with the same-day list.",
            art: "cmc-h-files.png",
            body: [
              "If it is not in the stall log, it did not happen. Incidents go to Field Marketing Manager the same day."
            ],
            points: [
              { title: "Log before you leave", why: "Memory is not a log." }
            ],
            meta: [
              ["You take from", "The visit day"],
              ["You give to", "The stall log; Field Marketing Manager if there was an incident"]
            ]
          },
          {
            kicker: "Responsibility 6",
            title: "Send honest numbers up",
            lead: "Each week: days vs calendar, enquiries by town, days that did not happen, town that produced nothing, materials that need reprinting, one or two written requests.",
            art: "cmc-p-weekly.png",
            body: [
              "This is VIS-07. Field Marketing Manager needs this page for the weekly field report. Do not hide a town that produced nothing inside ‘visits were busy’."
            ],
            points: [
              { title: "By town, not one lump", why: "A town that produced nothing must not hide inside a good stall week." },
              { title: "Name what failed", why: "A report with only wins is not a management system." }
            ],
            meta: [
              ["You take from", "Stall lists; stall logs; the calendar"],
              ["You give to", "Field Marketing Manager"]
            ]
          }
        ],
        handoffs: [
          {
            side: "in",
            kicker: "You receive",
            title: "The month’s written visit work from Field Marketing Manager",
            lead: "Which towns, which days, which boards, where a stall list must land in Sales, and what we will not advertise. If you are someone we pay on a written contract, your written contract.",
            art: "cmc-h-kit.png",
            body: ["You do not write your own month. You do not pick a new town. Boards must match the current marketing kit."],
            points: [{ title: "The file must name", why: "Town, day, pitch, current boards, where the list lands, what we will not advertise." }],
            meta: [["From", "Field Marketing Manager"], ["When", "The same day the month is signed"]]
          },
          {
            side: "out",
            kicker: "You give",
            title: "The stall list to Sales",
            lead: "Name, phone, service, town, stall + town + date. Before you leave the town. Confirm they received it.",
            art: "cmc-h-note.png",
            body: ["This is VIS-02. Field Marketing Manager gets the count, not the phone numbers."],
            points: [{ title: "The list must have", why: "Date, town, pitch, each complete row, count, time Sales received it." }],
            meta: [["To", "Sales"], ["When", "The same day, before you leave the town"], ["Copy", "Field Marketing Manager gets the count only"]]
          },
          {
            side: "out",
            kicker: "You give",
            title: "Weekly visit note to Field Marketing Manager",
            lead: "Days vs calendar, enquiries by town, days that did not happen, town that produced nothing, materials that need reprinting, one or two written requests.",
            art: "cmc-p-weekly.png",
            body: ["This is the stall page of Field Marketing Manager’s weekly report. What failed must be named. Not a voice note."],
            points: [{ title: "The note must name", why: "Week dates, town × day × enquiries, days that did not happen, dead-town flag, material needs, who owns each ask." }],
            meta: [["To", "Field Marketing Manager"], ["When", "The day before the weekly field report"]]
          }
        ],
        workflow: [
          {
            kicker: "VIS-03 · Step 1",
            title: "Open the written brief, the marketing kit, and the materials bag",
            lead: "Before you stand anywhere, know what the month allows: which town, which day, which boards.",
            art: "cmc-h-kit.png",
            body: ["If you are someone we pay on a written contract, confirm the written contract is in your hand. If it is not, stop and tell Field Marketing Manager."],
            points: [
              { title: "Brief and kit", why: "Town, day, sentences, prices, faces, and what we will not advertise." },
              { title: "What you produce", why: "A marked day: visit day or office day. Not a new town yet." }
            ]
          },
          {
            kicker: "VIS-01 · If it is a visit day",
            fork: "yes",
            title: "Stand in the named town. Collect names. Do not book.",
            lead: "Approved boards only. Write each person. Say Sales will call. Send the list before you leave (VIS-02). Write the stall log.",
            art: "cmc-h-shot.png",
            body: ["Confirm town, pitch, and boards. Write name, phone, service, town, stall + town + date. A missing phone is not an enquiry. Do not take money. Do not promise a job."],
            points: [
              { title: "When", why: "A named visit day on the signed calendar." },
              { title: "What you produce", why: "A visit that actually happened, a list, and a log — or a written note that the visit did not happen." }
            ]
          },
          {
            kicker: "VIS-03 · If it is an office day",
            fork: "no",
            title: "Restock. Confirm the next visit. Close any list still sitting",
            lead: "Count boards and flyers. Take out anything that is not in this month’s kit. Do not put up a stall because you were nearby.",
            art: "cmc-h-files.png",
            body: ["If yesterday’s list never reached Sales, send it now and write that it was late."],
            points: [
              { title: "You do", why: "Restock. Confirm. Close late lists." },
              { title: "You do not", why: "Invent a Saturday stall." }
            ]
          },
          {
            kicker: "VIS-02 · Step 3",
            title: "Send the stall list to Sales before you leave the town",
            lead: "One list. Complete rows. Confirm they received it. Field Marketing Manager gets the count.",
            art: "cmc-h-note.png",
            body: ["If Sales did not receive it, send it again the same day. Do not wait until morning."],
            points: [
              { title: "What you produce", why: "A received list, and a count for Field Marketing Manager." },
              { title: "What you do not produce", why: "A booking. That is Sales." }
            ]
          },
          {
            kicker: "VIS-06 · If there is a fight, a false claim, or someone taking bookings in our name",
            fork: "no",
            title: "Stop. Write it. Tell Field Marketing Manager the same day",
            lead: "Take down a board that might be untrue. Do not take the booking to calm someone down.",
            art: "cmc-e-stop.png",
            body: ["Write what happened, the time, and who was there."],
            points: [
              { title: "You do", why: "Stop that visit. Write the incident." },
              { title: "You send", why: "Field Marketing Manager the same day." }
            ]
          }
        ],
        reporting: [
          {
            period: "Same day",
            kicker: "VIS-R1",
            title: "Stall list to Sales",
            art: "cmc-h-note.png",
            when: "Before you leave the town. Same day as the visit. Not the next morning.",
            lead: "Hand Sales a complete list so they can call. You collect names at the stall. Sales books the job. You do not take money or book the job yourself.",
            body: ["Field Marketing Manager gets the count, not the phone numbers."],
            contents: [
              { title: "Name, phone, service wanted, town", why: "A missing phone is not an enquiry." },
              { title: "Source = stall + town + date", why: "So we can score the town, not just ‘marketing’." }
            ],
            mustHave: ["Date", "Town", "Pitch", "Each row: name, phone, service, town, stall + town + date", "Count of complete rows", "Sales received? time"],
            submit: [
              { to: "Sales", why: "The list the same day. Confirm they received it." },
              { to: "Field Marketing Manager", why: "The count, not the phone numbers." }
            ]
          },
          {
            period: "Weekly",
            kicker: "VIS-R2",
            title: "Weekly visit note",
            art: "cmc-p-weekly.png",
            when: "The day before Field Marketing Manager’s weekly field report.",
            lead: "Show which planned towns produced enquiries, and name towns that are dead three visits in a row.",
            body: ["Do not hide a town that produced nothing inside ‘visits were busy’."],
            contents: [
              { title: "Visit days vs calendar", why: "A day that did not happen is a missed town. Write it." },
              { title: "Enquiries by town", why: "Town that produced nothing must be named so the plan can change." }
            ],
            mustHave: ["Week", "Town × day × enquiries", "Days that did not happen", "Dead-town flag", "Material needs", "One or two written requests"],
            submit: [{ to: "Field Marketing Manager", why: "This is the stall page of the weekly field report." }]
          },
          {
            period: "Same day",
            kicker: "VIS-R3",
            title: "Stall log",
            art: "cmc-h-files.png",
            when: "Every visit day, packed with the same-day list.",
            lead: "Prove we stood where the plan said, with approved materials, and did not take money or bookings.",
            body: ["If it is not written, it did not happen. Do not send this log to Sales — they get the list."],
            contents: [
              { title: "Town, pitch, start and end", why: "If it is not written, it did not happen." },
              { title: "Incidents", why: "Stop. Write. Tell Field Marketing Manager the same day." }
            ],
            mustHave: ["Date", "Town", "Times", "Materials", "Enquiry count", "Incidents", "No-cash / no-booking confirmation"],
            submit: [
              { to: "File", why: "Every visit day." },
              { to: "Field Marketing Manager", why: "Incidents the same day." }
            ]
          }
        ],
        standards: [
          { title: "Every visit is a named town on the signed plan", lead: "If it is not on the calendar, you do not stand there.", art: "cmc-h-kit.png", body: ["You do not add a Saturday because you were nearby."], points: [{ title: "What good looks like", why: "A stranger can open the brief and see which town, which day, which boards." }] },
          { title: "95 percent or more of visit days actually happen, or the miss is written the same day", lead: "A day that did not happen that nobody wrote looks like we stood there.", art: "cmc-h-shot.png", body: ["Rain, a locked pitch, or illness is a written note that the visit did not happen. Silence is not a miss."], points: [{ title: "What good looks like", why: "Every calendar visit day has either a stall log or a written note that the visit did not happen dated the same day." }] },
          { title: "95 percent or more of stall names reach Sales the same day", lead: "Before you leave the town. Complete rows only.", art: "cmc-h-note.png", body: ["Name, phone, service, town, stall + town + date. A missing phone is not an enquiry."], points: [{ title: "What good looks like", why: "Sales can open the list the same evening and start calling." }] },
          { title: "No cash, no booking, and no job promise at the stall", lead: "You collect names at the stall. Sales books the job. You do not take money or book the job yourself.", art: "cmc-e-stop.png", body: ["Say Sales will call. Do not take money to hold a slot."], points: [{ title: "What good looks like", why: "The stall log says no cash and no booking, and Sales owns every person on the list." }] },
          { title: "Boards and flyers match this month’s marketing kit", lead: "Last year’s price is a brand problem.", art: "cmc-h-yes.png", body: ["Pack only the current kit. A new sentence waits for VIS-04."], points: [{ title: "What good looks like", why: "Every live board traces to a dated line in the marketing kit." }] },
          { title: "Someone we pay to visit has a written contract before they visit", lead: "A verbal yes is not this job.", art: "cmc-h-files.png", body: ["Full-time employee or someone we pay on a written contract — the job is the same either way. The contract must be a file."], points: [{ title: "What good looks like", why: "No visit day happens without the contract in hand if you are not full-time." }] }
        ],
        kpis: [
          { name: "Visit days vs the calendar", target: "95% or more completed, or a written note the same day that the visit did not happen", why: "A missed field day is a missed town.", how: "Count visit days on the written brief this month. A day counts as done if the stall log exists. A miss counts if it was written the same day." },
          { name: "Stall names that reach Sales the same day", target: "95% or more", why: "If it is not sent the same day, it did not happen.", how: "Complete rows Sales received the same calendar day, divided by all complete rows written that week." },
          { name: "Enquiries per planned visit day", target: "Track by town, and improve vs last month", why: "Town that produced nothing must be named so the plan can change.", how: "Complete stall-list rows that week, divided by planned visit days that week, shown by town." },
          { name: "Visits only in towns on the plan", target: "100%", why: "Stalls on random Saturdays that are not on the plan cannot be measured.", how: "Every stall log town must appear on the written brief for that date." },
          { name: "Weekly visit note on time", target: "100%", why: "Field Marketing Manager needs numbers, not a story with only good towns.", how: "A written note exists the working day before the weekly field report." },
          { name: "No cash and no booking at the stall", target: "100%", why: "You collect names at the stall. Sales books the job. You do not take money or book the job yourself.", how: "Every stall log says no cash and no booking. Any cash or booking is a miss." }
        ],
        escalations: [
          { title: "A fight, a false claim, or someone taking bookings in our name", lead: "Trust dies in the street if we look like a shop that takes cash and argues.", art: "cmc-e-stop.png", body: ["Stop that visit. Write what happened. Tell Field Marketing Manager the same day."], points: [{ title: "You do", why: "Stop. Write the time and who was there." }, { title: "You send to", why: "Field Marketing Manager the same day." }] },
          { title: "The town is not ready — no providers", lead: "Standing there to look busy trains people to hate the brand.", art: "cmc-h-kit.png", body: ["Do not keep the stall up. Write the town and what is missing."], points: [{ title: "You do", why: "Pack up. Write that it did not happen." }, { title: "You send to", why: "Field Marketing Manager the same day." }] },
          { title: "A board or flyer might be untrue, or shows a price that is not in this month’s marketing kit", lead: "A wrong price becomes a fight later.", art: "cmc-e-stop.png", body: ["Take it down. Show the file. Tell Field Marketing Manager before it stays up."], points: [{ title: "You do", why: "Take down the board. Write what went out." }, { title: "You send to", why: "Field Marketing Manager the same day." }] },
          { title: "You are someone we pay on a written contract with no written contract in your hand", lead: "A verbal yes is not this job.", art: "cmc-h-yes.png", body: ["Do not visit. Write that it did not happen. Tell Field Marketing Manager."], points: [{ title: "You do", why: "Stop. Write that it did not happen." }, { title: "You send to", why: "Field Marketing Manager before you visit." }] }
        ]
      }
    },
    fpo: {
      story: "You go in person to the first local workers in a named town (plumbers, electricians, and others) and get them to sign the papers so they can work with Panun Kaergar. You take only the signed first-worker papers. You send the signed file to Provider Operations the same week. You do not give them a customer job. You do not keep a private team.",
      receives: [
        { from: "Field Marketing Manager", what: "Which towns, the signed first-worker papers, and who in Provider Operations receives the signed file." }
      ],
      gives: [
        { to: "Provider Operations", what: "The signed first-worker file the same week: name, phone, services, towns, start date, signed papers." },
        { to: "Field Marketing Manager", what: "The weekly note: visits, files signed, files sent, towns where nobody signed." }
      ],
      records: ["Written brief for the month", "First-worker papers in use", "Visit logs", "Signed first-worker files", "Received dates from Provider Operations", "Weekly first-worker note"],
      good: ["Every visit was a named town on the plan.", "Every person you marked as able to work with us had a signed paper file.", "Provider Operations received the file the same week.", "No invented payment.", "A town that would not sign was named.", "You did not give anyone a customer job."],
      bad: ["Signing people up from a company WhatsApp group without visiting them.", "Calling a verbal yes a signed worker.", "Keeping the names as a private team.", "Inventing a payment on site.", "Giving them the first job yourself.", "Starting work in a town Expansion has not opened."],
      detailed: {
        copy: {
          heroKicker: "What this job is",
          defTitle: "What this job is",
          lanesTitle: "This job has three parts",
          definition: "You go in person to the first local workers in a named town — plumbers, electricians, and others who will do the jobs — and get them to sign the papers so they can work with Panun Kaergar. You take only the signed first-worker papers. After they sign, you send the file to Provider Operations the same week, so Provider Operations can put them on customer jobs. You do not hire them as your own team, and you do not give them a customer job yourself. Expansion has already opened the town. If nobody sits in this job, Field Marketing Manager does that work and writes that they are doing it today because nobody is in that job.",
          owns: "These are the six things this job owns. Do not do Provider Operations’ job, Field Visitor’s job, or People & HR’s job.",
          lanes: "This job has one result. You do all three parts yourself. Go in person. Get them to sign. Send the file to Provider Operations. If this job is empty, Field Marketing Manager does this work today. They write that down.",
          responsibilities: "These are the six parts of the job. Click a row to open the full card.",
          handoffs: "Work arrives as a written file and leaves as a written file. A chat message is not the file. Click a row to open the full card.",
          workflow: "On a named first-worker day, go in person with the signed papers (FPO-01). If they will sign, write the file (FPO-02). Send it to Provider Operations the same week (FPO-03). If the town will not sign, name it (FPO-04). Click a step to open the full card.",
          reporting: "You write the weekly first-worker note to Field Marketing Manager. The signed file goes to Provider Operations the same week. Click a row to open the full report.",
          standards: "The bar this job is measured against. Click a card to read the full standard.",
          kpis: "How Field Marketing Manager knows this job is working. Each number has a target, a reason it matters, and a counting rule.",
          escalations: "What to do when the papers are missing a price, a town will not sign, someone wants cash on the side, or Provider Operations did not receive the file. Click a row to open the full steps."
        },
        hub: {
          icon: "handshake",
          line: "You get the first local workers to sign the papers. Provider Operations puts them on customer jobs."
        },
        definition: {
          what: [
            { title: "You go in person to the first local workers", why: "Named town. Meet the plumber, electrician, or other local worker at their shop or house. Do not sign them up from a company WhatsApp group without visiting them." },
            { title: "You get them to sign onto Panun Kaergar with the signed first-worker papers", why: "Who we are, what they will do, what they will not do, what we pay. A verbal yes is not enough. They cannot work with us until they have signed." },
            { title: "You send the signed file to Provider Operations the same week", why: "You get them to sign the papers. Provider Operations puts them on customer jobs. You do not give them the first job yourself." }
          ],
          why: [
            { title: "A stall with nobody who can do the jobs is how the brand dies in public", why: "Ads and stalls promise a job. If nobody can do that job, the town learns to hate us." },
            { title: "Getting them to sign and putting them on jobs are two jobs", why: "If you keep a private team, Provider Operations has no list of workers and the customer has no system." },
            { title: "You do not open towns and you do not hire employees", why: "Expansion already opened the town. People & HR owns employees. You get the first local workers to sign so they can work with Panun Kaergar as providers, not as office staff." }
          ]
        },
        glossary: [
          { id: "first-provider-pack", term: "First-worker papers", aliases: ["first-worker papers", "approved pack", "first-worker papers", "signed first-worker papers"], meaning: "The signed papers Offer, Finance, and Provider Operations already approved: who we are, what they will do, what they will not do, what we pay. You may not invent a payment. If a price is missing, stop." },
          { id: "signed-file", term: "Signed first-worker file", aliases: ["signed file", "first-provider file", "first-worker file"], meaning: "Name, phone, services they can do, towns they can cover, start date, signed papers. Incomplete is not signed. They cannot work with us until this file is complete." },
          { id: "provider-ops", term: "Provider Operations", aliases: ["Provider Operations"], meaning: "The people who put providers on customer jobs. You send them the signed file. You do not give them the first job yourself." },
          { id: "fmm", term: "Field Marketing Manager", aliases: ["Field Marketing Manager"], meaning: "The person you report to. They give you the towns and the signed papers. If nobody is in this job, they do that work and write that they are doing it today because nobody is in that job." },
          { id: "fpo", term: "First Provider Onboarding", aliases: ["First Provider Onboarding"], also: "This job — you", meaning: "This job. You go in person. You get the first local workers to sign the papers. You send the file to Provider Operations." }
        ],
        responsibilities: [
          { kicker: "Responsibility 1", title: "Go in person to the first local workers", lead: "Be in the named town. Meet the plumber, electrician, or other local worker at their shop or house. Do not sign them up from a company WhatsApp group without visiting them.", art: "cmc-h-shot.png", body: ["Getting people to sign from an office, without visiting them, is not this job. Expansion has already opened the town."], points: [{ title: "In person", why: "A chat list is not a first local worker." }], meta: [["You take from", "The written brief"], ["You give to", "A visit log"]] },
          { kicker: "Responsibility 2", title: "Use only the signed first-worker papers", lead: "Who we are, what they will do, what they will not do, what we pay. A verbal yes is not enough.", art: "cmc-h-kit.png", body: ["If a price is missing, stop and tell Field Marketing Manager. Do not invent a payment on site."], points: [{ title: "Signed papers only", why: "A made-up payment becomes a fight." }], meta: [["You take from", "The signed first-worker papers"], ["You give to", "The signed file"]] },
          { kicker: "Responsibility 3", title: "Get a signed file", lead: "Name, phone, services, towns, start date, signed papers. Incomplete is not signed. Do not pretend they can work with us yet.", art: "cmc-h-yes.png", body: ["They sign. You sign. Date it. Do not pretend."], points: [{ title: "Complete, or they cannot work with us yet", why: "Provider Operations cannot put a nameless person on a customer job." }], meta: [["You take from", "The person you visited"], ["You give to", "The signed file"]] },
          { kicker: "Responsibility 4", title: "Send the file to Provider Operations the same week", lead: "Send the signed file. Write the day they received it. Keep a copy. Do not give them a customer job yourself.", art: "cmc-h-files.png", body: ["You get them to sign the papers. Provider Operations puts them on customer jobs. Do not keep a private list."], points: [{ title: "Same week", why: "A file that sits until month-end is a private team." }], meta: [["You take from", "The signed file"], ["You give to", "Provider Operations"]] },
          { kicker: "Responsibility 5", title: "Name a town that will not sign", lead: "If a named town has nobody who will sign after the planned visits, write it. Do not keep visiting to look busy.", art: "cmc-e-stop.png", body: ["Field Marketing Manager must stop the stall if the town has nobody who can do the jobs."], points: [{ title: "Write that nobody signed", why: "Silence looks like the town is ready." }], meta: [["You take from", "The visit logs"], ["You give to", "Field Marketing Manager"]] },
          { kicker: "Responsibility 6", title: "Send honest numbers up", lead: "Each week: visits vs calendar, files signed, files sent to Provider Operations, towns where nobody signed, one or two written requests.", art: "cmc-p-weekly.png", body: ["Field Marketing Manager cannot run this job without numbers."], points: [{ title: "Name what failed", why: "A report with only wins is not a management system." }], meta: [["You take from", "Visit logs; signed files"], ["You give to", "Field Marketing Manager"]] }
        ],
        handoffs: [
          { side: "in", kicker: "You receive", title: "The month’s written first-worker work", lead: "Which towns, the signed papers, and who in Provider Operations receives the signed file.", art: "cmc-h-kit.png", body: ["You do not pick a new town. You do not invent a payment."], points: [{ title: "The file must name", why: "Towns, signed papers, receiving person in Provider Operations." }], meta: [["From", "Field Marketing Manager"], ["When", "The same day the month is signed"]] },
          { side: "out", kicker: "You give", title: "Signed first-worker file to Provider Operations", lead: "Name, phone, services, towns, start date, signed papers. Same week as the signature.", art: "cmc-h-files.png", body: ["Write the day they received it. Keep a copy. Do not give them a customer job."], points: [{ title: "The file must have", why: "Complete rows, signed papers, received date." }], meta: [["To", "Provider Operations"], ["When", "The same week as the signature"]] },
          { side: "out", kicker: "You give", title: "Weekly first-worker note", lead: "Visits, files signed, files sent, towns where nobody signed, one or two written requests.", art: "cmc-p-weekly.png", body: ["Not a voice note. Name what failed."], points: [{ title: "The note must name", why: "Week, visits, signed, sent, towns where nobody signed, written requests." }], meta: [["To", "Field Marketing Manager"], ["When", "The day before the weekly field report"]] }
        ],
        workflow: [
          { kicker: "FPO-01 · Step 1", title: "Go in person with the signed first-worker papers", lead: "Confirm the town is on the plan. If a price is missing, stop. Meet the plumber, electrician, or other local worker at their shop or house.", art: "cmc-h-shot.png", body: ["Do not sign them up from a chat list without visiting them. Do not promise a customer job today."], points: [{ title: "What you produce", why: "A visit log: who, where, whether they will sign." }] },
          { kicker: "FPO-02 · If they will sign", fork: "yes", title: "Get them to sign onto Panun Kaergar", lead: "Read the papers with them. Write name, phone, services, towns, start date. They sign. You sign. Date it.", art: "cmc-h-yes.png", body: ["Incomplete is not signed. Do not pretend they can work with us yet."], points: [{ title: "What you produce", why: "A signed file." }] },
          { kicker: "FPO-03 · Step 3", title: "Send the signed file to Provider Operations", lead: "Same week. Write the received date. Keep a copy. Do not give them a customer job yourself.", art: "cmc-h-files.png", body: ["Do not keep them as a private list."], points: [{ title: "What you produce", why: "A received file in Provider Operations." }] },
          { kicker: "FPO-04 · If the town will not sign", fork: "no", title: "Write that nobody signed. Do not keep visiting to look busy", lead: "Write town, dates, who you met, why they said no. Send it in the weekly note.", art: "cmc-e-stop.png", body: ["Do not pick a different town yourself."], points: [{ title: "You send", why: "Field Marketing Manager." }] }
        ],
        reporting: [
          { period: "Same week", kicker: "FPO-R1", title: "Signed first-worker file", art: "cmc-h-files.png", when: "The same week as the signature.", lead: "Send Provider Operations a complete signed paper file so they can put the person on the list of workers.", body: ["You do not give them a customer job."], contents: [{ title: "Name, phone, services, towns, start date, signed papers", why: "Incomplete is not signed." }, { title: "Received date from Provider Operations", why: "If they did not receive it, it did not happen." }], mustHave: ["Date", "Town", "Complete signed file", "Received?"], submit: [{ to: "Provider Operations", why: "The signed file the same week." }, { to: "Field Marketing Manager", why: "The count, not a private copy of the phones unless they ask." }] },
          { period: "Weekly", kicker: "FPO-R2", title: "Weekly first-worker note", art: "cmc-p-weekly.png", when: "The day before Field Marketing Manager’s weekly field report.", lead: "Visits vs calendar, files signed, files sent, towns where nobody signed.", body: ["Name what failed."], contents: [{ title: "Visits vs calendar", why: "A missed visit is a missed town." }, { title: "Towns where nobody signed", why: "Field Marketing Manager must stop the stall if nobody will sign." }], mustHave: ["Week", "Visits", "Signed", "Sent", "Towns where nobody signed", "Written requests"], submit: [{ to: "Field Marketing Manager", why: "This is the first-worker page of the weekly field report." }] }
        ],
        standards: [
          { title: "Every first-worker visit is a named town on the signed plan", lead: "You do not open a town.", art: "cmc-h-kit.png", body: ["Expansion already opened the town."], points: [{ title: "What good looks like", why: "Every visit log town appears on the brief." }] },
          { title: "Every person you mark as able to work with us has a signed paper file", lead: "A verbal yes is not enough.", art: "cmc-h-yes.png", body: ["Incomplete is not signed."], points: [{ title: "What good looks like", why: "A stranger can open the file and see who signed, what they will do, and what we pay." }] },
          { title: "100 percent of signed files reach Provider Operations the same week", lead: "No private team.", art: "cmc-h-files.png", body: ["Write the received date."], points: [{ title: "What good looks like", why: "Every signed file has a received date from Provider Operations." }] },
          { title: "No invented payment, service, or town", lead: "If the papers are missing a price, stop.", art: "cmc-e-stop.png", body: ["Finance signs price. You do not."], points: [{ title: "What good looks like", why: "Every payment traces to the signed papers." }] },
          { title: "A town that will not sign is named", lead: "Do not keep visiting to look busy.", art: "cmc-p-weekly.png", body: ["Write it in the weekly note."], points: [{ title: "What good looks like", why: "The weekly note names the town, the dates, and why they said no." }] },
          { title: "You do not give people customer jobs", lead: "Provider Operations puts them on customer jobs.", art: "cmc-h-shot.png", body: ["Get them to sign. Send the file. Stop."], points: [{ title: "What good looks like", why: "No job in the system was created by this seat." }] }
        ],
        kpis: [
          { name: "In-person visits vs the calendar", target: "95% or more completed, or a written note the same day that the visit did not happen", why: "Getting people to sign from an office, without visiting them, is not this job.", how: "Visit days on the brief this month with a visit log, or a written note the same day that the visit did not happen." },
          { name: "People marked as able to work with us who have a signed paper file", target: "100%", why: "A verbal yes is not enough.", how: "Every person marked as able to work with us has a complete signed paper file." },
          { name: "Signed files sent to Provider Operations the same week", target: "100%", why: "A private list is not a system.", how: "Received date from Provider Operations is in the same calendar week as the signature." },
          { name: "No invented payment or town", target: "100%", why: "A made-up payment becomes a fight.", how: "Every payment traces to the signed papers. Any extra town is a miss." },
          { name: "Named towns with a written note that nobody signed, or a signed file", target: "100% of towns on the plan", why: "Silence looks like the town is ready.", how: "Each named town has either a received file or a dated note that nobody signed." },
          { name: "Weekly first-worker note on time", target: "100%", why: "Field Marketing Manager needs numbers.", how: "A written note exists the working day before the weekly field report." }
        ],
        escalations: [
          { title: "The papers are missing a price or a service", lead: "Do not invent it on site.", art: "cmc-h-kit.png", body: ["Stop. Tell Field Marketing Manager the same day."], points: [{ title: "You do", why: "Stop. Write that a price or service is missing." }, { title: "You send to", why: "Field Marketing Manager the same day." }] },
          { title: "A named town will not sign", lead: "The stall must not keep standing there.", art: "cmc-e-stop.png", body: ["Write who you met and why they said no."], points: [{ title: "You send to", why: "Field Marketing Manager in the weekly note, same day if the stall is still standing." }] },
          { title: "Someone wants to be paid cash on the side", lead: "Do not pay.", art: "cmc-e-stop.png", body: ["Stop. Write it. Tell Field Marketing Manager the same day."], points: [{ title: "You do", why: "Do not pay. Write it." }] },
          { title: "Provider Operations did not receive a signed file", lead: "Send it again the same day.", art: "cmc-h-files.png", body: ["Write that they did not receive it. Tell Field Marketing Manager."], points: [{ title: "You do", why: "Send it again. Write the received date." }] }
        ]
      }
    },
    flc: {
      story: "You write maintenance contracts with offices and housing societies. Named building, named services, signed price, from the signed contract papers Offer and Finance already approved. A verbal yes is not a contract. You do not take cash. Finance sends the invoice. Operations does the jobs. Hotel desks stay with Partnerships.",
      receives: [
        { from: "Field Marketing Manager", what: "Which towns, which buildings, the signed contract papers, and where a signed contract must be filed." }
      ],
      gives: [
        { to: "Operations", what: "The building they may start sending workers to: services, start date, what is in, what is out — after the contract is signed and filed." },
        { to: "Field Marketing Manager", what: "The weekly contract note: visits, signed, waiting, or failed." }
      ],
      records: ["Written brief for the month", "Signed contract papers in use", "Visit logs", "Signed contract files", "Notes that Operations was told", "Weekly contract note"],
      good: ["Every visit was a named building on the plan.", "Every building Operations may send workers to had a signed contract from the signed papers.", "No cash collected on site.", "No invented price.", "Operations was told the same week.", "Hotel desks were left to Partnerships."],
      bad: ["A verbal yes from a secretary called a contract.", "A WhatsApp yes.", "Cash in the office.", "A made-up price.", "Pitching a watchman and calling it done.", "Signing a hotel desk as if that were this job."],
      detailed: {
        copy: {
          heroKicker: "What this job is",
          defTitle: "What this job is",
          lanesTitle: "This job has three parts",
          definition: "You write a maintenance contract with a named office or housing society so Operations can send workers there. You go to that building, meet the person who is allowed to sign (the office manager, the society secretary, or the named owner), and use only the signed contract papers Offer and Finance already approved. After both sides sign, you file the contract and tell Operations they can start. You do not invent a price, take cash, or book a one-off job for someone in that building. Hotel desks and shops that send us their customers belong to Partnerships, not this job.",
          owns: "These are the six things this job owns. Do not do Sales’ job, Operations’ job, or Partnerships’ job.",
          lanes: "This job has one result. You do all three parts yourself. Go to the building. Write the contract. File it so Operations can work. If this job is empty, Field Marketing Manager does this work today. They write that down.",
          responsibilities: "These are the six parts of the job. Click a row to open the full card.",
          handoffs: "Work arrives as a written file and leaves as a written file. A chat message is not the file. Click a row to open the full card.",
          workflow: "On a named contract-visit day, go with the signed papers (OSC-01). If they will sign, write the contract (OSC-02). File it and tell Operations (OSC-03). If they will not sign, name it (OSC-04). Click a step to open the full card.",
          reporting: "You write the weekly contract note to Field Marketing Manager. The signed contract is filed and Operations is told the same week. Click a row to open the full report.",
          standards: "The bar this job is measured against. Click a card to read the full standard.",
          kpis: "How Field Marketing Manager knows this job is working. Each number has a target, a reason it matters, and a counting rule.",
          escalations: "What to do when they want a price that is not in the papers, someone wants to pay cash, a building will not sign, or a contract might be untrue. Click a row to open the full steps."
        },
        hub: {
          icon: "apartment",
          line: "You write the contract. Finance sends the invoice. Operations does the jobs."
        },
        definition: {
          what: [
            { title: "You go to the named office or housing society", why: "Meet the person who is allowed to sign — office manager, society secretary, or named owner. Do not pitch a watchman and call it a contract." },
            { title: "You write a maintenance contract from the signed papers", why: "Services in, services out, signed price, start date. A WhatsApp yes is not a contract." },
            { title: "You file it so Operations can start sending workers", why: "Finance sends the invoice. You do not take cash. One-off jobs the contract does not cover still go to Sales." }
          ],
          why: [
            { title: "Offices and societies buy a written contract, not a stall flyer", why: "Operations cannot plan jobs from a verbal yes from a secretary." },
            { title: "This job is the office or society that buys maintenance from us, not a hotel desk that sends us customers", why: "Hotel desks and shops that send us their customers belong to Partnerships. This job writes a maintenance contract with a named office or housing society." },
            { title: "Writing the contract and doing the jobs are two jobs", why: "You write the file. Operations does the maintenance. Sales still books one-off work." }
          ]
        },
        glossary: [
          { id: "contract-pack", term: "Signed contract papers", aliases: ["signed contract papers", "contract pack", "signed contract papers"], meaning: "The signed papers Offer and Finance already approved: services in, services out, signed price, how we invoice. You may not invent a price. If they want a different price, stop." },
          { id: "signed-contract", term: "Signed maintenance contract", aliases: ["signed contract", "maintenance contract"], meaning: "Named building, town, start date, services, price, who signs for them, who signs for us. A WhatsApp yes is not a contract." },
          { id: "partnerships", term: "Partnerships", aliases: ["Partnerships"], meaning: "Hotel desks, shops, and other people who send us their customers. That is not this job." },
          { id: "fmm", term: "Field Marketing Manager", aliases: ["Field Marketing Manager"], meaning: "The person you report to. They give you the buildings and the signed papers. If nobody is in this job, they do that work and write that they are doing it today because nobody is in that job." },
          { id: "flc", term: "Office and Society Contracts", aliases: ["Office and Society Contracts"], also: "This job — you", meaning: "This job. You write maintenance contracts with offices and housing societies from the signed papers Offer and Finance already approved." }
        ],
        responsibilities: [
          { kicker: "Responsibility 1", title: "Go to the named building", lead: "Meet the person who is allowed to sign. Do not pitch a watchman and call it a contract.", art: "cmc-h-shot.png", body: ["Write their name and role. A contract with nobody who can sign is paper."], points: [{ title: "The person who is allowed to sign", why: "Otherwise the file is theatre." }], meta: [["You take from", "The written brief"], ["You give to", "A visit log"]] },
          { kicker: "Responsibility 2", title: "Use only the signed contract papers", lead: "Services in, services out, signed price, how we invoice. If they want a different price, stop.", art: "cmc-h-kit.png", body: ["Tell Field Marketing Manager. Do not invent a price on site."], points: [{ title: "Signed papers only", why: "A made-up price becomes a fight." }], meta: [["You take from", "The signed contract papers"], ["You give to", "The draft contract"]] },
          { kicker: "Responsibility 3", title: "Get a signed contract", lead: "Named building, town, start date, services, price, who signs for them, who signs for us. A WhatsApp yes is not a contract.", art: "cmc-h-yes.png", body: ["They sign. You sign. Date it. Do not pretend."], points: [{ title: "Complete, or Operations may not send workers yet", why: "Operations cannot plan jobs from a chat." }], meta: [["You take from", "The person who is allowed to sign"], ["You give to", "The signed contract"]] },
          { kicker: "Responsibility 4", title: "File it. Do not take cash.", lead: "File the signed contract. Tell Operations they may start sending workers. Finance sends the invoice.", art: "cmc-h-files.png", body: ["Cash you collect on site has no owner in the company books."], points: [{ title: "Finance sends the invoice", why: "You do not collect money." }], meta: [["You take from", "The signed contract"], ["You give to", "File; Operations"]] },
          { kicker: "Responsibility 5", title: "One-off jobs still go to Sales", lead: "A person in that building who wants a one-off job that the contract does not cover goes to Sales. You do not book them at the gate.", art: "cmc-h-note.png", body: ["The contract is a file. Sales still books one-off work."], points: [{ title: "Do not become Sales", why: "Mixing it hides lost-lead reasons." }], meta: [["You take from", "A person in the building"], ["You give to", "Sales"]] },
          { kicker: "Responsibility 6", title: "Send honest numbers up", lead: "Each week: visits vs calendar, contracts signed, waiting, or failed, buildings Operations can now serve, one or two written requests.", art: "cmc-p-weekly.png", body: ["Field Marketing Manager cannot run this job without numbers."], points: [{ title: "Name what failed", why: "A report with only wins is not a management system." }], meta: [["You take from", "Visit logs; signed files"], ["You give to", "Field Marketing Manager"]] }
        ],
        handoffs: [
          { side: "in", kicker: "You receive", title: "The month’s written contract work", lead: "Which towns, which buildings, the signed papers, and where a signed contract must be filed.", art: "cmc-h-kit.png", body: ["You do not pick a new building. You do not invent a price."], points: [{ title: "The file must name", why: "Buildings, pack, filing path." }], meta: [["From", "Field Marketing Manager"], ["When", "The same day the month is signed"]] },
          { side: "in", kicker: "You receive", title: "A hotel or shop that wants to buy monthly maintenance", lead: "Partnerships & Channels Manager met a hotel or shop that does not only send guests. They want us to maintain their own building. That is a customer who buys work. You write the maintenance contract. They keep the partner file for guests the hotel still sends.", art: "cmc-h-shot.png", body: ["Same building can be both. That is two files, not one job. You do not sign them as a partner."], points: [{ title: "The file must name", why: "Building, town, who to call, that they asked to buy maintenance, date." }], meta: [["From", "Partnerships & Channels Manager"], ["When", "The same week they ask to buy maintenance"]] },
          { side: "out", kicker: "You give", title: "Signed contract, filed, and Operations told", lead: "Named building, services, start date, what is in, what is out. Same week as the signature. Finance sends the invoice.", art: "cmc-h-files.png", body: ["Do not take cash. One-off jobs the contract does not cover still go to Sales."], points: [{ title: "The file must have", why: "Complete signed pack, filing path, Operations told date." }], meta: [["To", "File; Operations"], ["When", "The same week as the signature"]] },
          { side: "out", kicker: "You give", title: "A society that only wants to send flat owners", lead: "They do not want to buy monthly maintenance. They only want people who live there to call us. That is Partnerships & Channels Manager’s job, not this one. Pass the name. Do not write a fake maintenance contract so it stays on your list.", art: "cmc-h-note.png", body: ["Head of Growth must already have said yes to housing societies as a kind of partner before Partnerships & Channels Manager signs anyone."], points: [{ title: "The file must name", why: "Building, town, who they may call, date you passed the name." }], meta: [["To", "Partnerships & Channels Manager"], ["When", "The same week you learn they will not buy maintenance"]] },
          { side: "out", kicker: "You give", title: "Weekly contract note", lead: "Visits, signed, waiting, or failed, buildings Operations can now serve, one or two written requests.", art: "cmc-p-weekly.png", body: ["Not a voice note. Name what failed."], points: [{ title: "The note must name", why: "Week, visits, signed, waiting, failed, asks." }], meta: [["To", "Field Marketing Manager"], ["When", "The day before the weekly field report"]] }
        ],
        workflow: [
          { kicker: "OSC-01 · Step 1", title: "Visit a named office or society", lead: "Confirm the building is on the plan. Take only the signed papers. Meet the person who can sign.", art: "cmc-h-shot.png", body: ["Do not pitch a watchman and call it done."], points: [{ title: "What you produce", why: "A visit log: building, who you met, whether they will sign." }] },
          { kicker: "OSC-02 · If they will sign", fork: "yes", title: "Write the maintenance contract", lead: "Approved pack only. Named building, town, start date, who signs. They sign. You sign. Date it.", art: "cmc-h-yes.png", body: ["A WhatsApp yes is not a contract."], points: [{ title: "What you produce", why: "A signed contract file." }] },
          { kicker: "OSC-03 · Step 3", title: "File the contract and tell Operations", lead: "Same week. Do not take cash. Finance sends the invoice. One-off jobs still go to Sales.", art: "cmc-h-files.png", body: ["Tell Operations they may start sending workers to that building: services, start date, what is in, what is out."], points: [{ title: "What you produce", why: "A filed contract and an Operations note." }] },
          { kicker: "OSC-04 · If they will not sign", fork: "no", title: "Name the building. Do not keep visiting to look busy", lead: "Write building, dates, who you met, why they said no.", art: "cmc-e-stop.png", body: ["Do not pick a different building yourself."], points: [{ title: "You send", why: "Field Marketing Manager." }] }
        ],
        reporting: [
          { period: "Same week", kicker: "OSC-R1", title: "Signed contract file", art: "cmc-h-files.png", when: "The same week as the signature.", lead: "File the contract and tell Operations they can plan the jobs.", body: ["Finance sends the invoice. You do not take cash."], contents: [{ title: "Named building, services, price, start date, who signed", why: "A WhatsApp yes is not a contract." }, { title: "Operations told date", why: "If Operations does not know, the jobs will not happen." }], mustHave: ["Date", "Building", "Town", "Signed pack file", "Operations told?"], submit: [{ to: "File", why: "The signed contract." }, { to: "Operations", why: "The building is live." }] },
          { period: "Weekly", kicker: "OSC-R2", title: "Weekly contract note", art: "cmc-p-weekly.png", when: "The day before Field Marketing Manager’s weekly field report.", lead: "Visits vs calendar, contracts signed, waiting, or failed.", body: ["Name what failed."], contents: [{ title: "Visits vs calendar", why: "A missed visit is a missed building." }, { title: "Buildings Operations can now serve", why: "A signed file nobody told Operations about is not live." }], mustHave: ["Week", "Visits", "Signed", "Waiting", "Failed", "Asks"], submit: [{ to: "Field Marketing Manager", why: "This is the contracts page of the weekly field report." }] }
        ],
        standards: [
          { title: "Every visit is a named office or society on the signed plan", lead: "You do not pick a building because you were nearby.", art: "cmc-h-kit.png", body: ["The brief is the boss."], points: [{ title: "What good looks like", why: "Every visit log building appears on the brief." }] },
          { title: "Every live building has a signed contract from the signed papers", lead: "A verbal yes is not a contract.", art: "cmc-h-yes.png", body: ["A WhatsApp yes is not a contract."], points: [{ title: "What good looks like", why: "A stranger can open the file and see the building, the services, and the signed price." }] },
          { title: "No cash collected in the office", lead: "Finance sends the invoice.", art: "cmc-e-stop.png", body: ["Cash in the office has no owner in the books."], points: [{ title: "What good looks like", why: "Every live contract has an invoice path, not a cash note." }] },
          { title: "No invented price", lead: "If they want a different price, stop.", art: "cmc-h-kit.png", body: ["Tell Field Marketing Manager. Do not write a new number."], points: [{ title: "What good looks like", why: "Every price traces to the signed papers." }] },
          { title: "Operations is told the same week a contract is signed", lead: "Otherwise the jobs will not happen.", art: "cmc-h-files.png", body: ["File it. Tell them what is in and what is out."], points: [{ title: "What good looks like", why: "Every signed contract has an Operations-told date the same week." }] },
          { title: "Hotel desks and shops are not this job", lead: "That is Partnerships.", art: "cmc-h-shot.png", body: ["Offices and societies that buy maintenance are this job. Referral partners are not."], points: [{ title: "What good looks like", why: "No hotel-desk or shop referral sits in this file." }] }
        ],
        kpis: [
          { name: "Contract visits vs the calendar", target: "95% or more completed, or a written note the same day that the visit did not happen", why: "A missed visit is a missed building.", how: "Visit days on the brief this month with a visit log, or a written note that the visit did not happen the same day." },
          { name: "Live buildings with a signed pack contract", target: "100%", why: "A verbal yes is not a contract.", how: "Every building marked live has a complete signed pack file." },
          { name: "Contracts from the signed papers only", target: "100%", why: "A made-up price becomes a fight.", how: "Every live price traces to the signed papers. Any extra price is a miss." },
          { name: "No cash collected on site", target: "100%", why: "Cash in the office has no owner in the books.", how: "Any cash collected on a contract visit is a miss." },
          { name: "Operations told the same week a contract is signed", target: "100%", why: "If Operations does not know, the jobs will not happen.", how: "Operations-told date is in the same calendar week as the signature." },
          { name: "Weekly contract note on time", target: "100%", why: "Field Marketing Manager needs numbers.", how: "A written note exists the working day before the weekly field report." }
        ],
        escalations: [
          { title: "They want a price or a service that is not in the signed papers", lead: "Do not invent it on site.", art: "cmc-h-kit.png", body: ["Stop. Tell Field Marketing Manager the same day."], points: [{ title: "You do", why: "Stop. Write that it did not happen." }, { title: "You send to", why: "Field Marketing Manager the same day." }] },
          { title: "Someone wants to pay cash in the office", lead: "Do not take it. Finance sends the invoice.", art: "cmc-e-stop.png", body: ["Write it. Tell Field Marketing Manager the same day."], points: [{ title: "You do", why: "Do not take the cash. Write it." }] },
          { title: "A building will not sign", lead: "Do not keep visiting to look busy.", art: "cmc-p-weekly.png", body: ["Write who you met and why they said no."], points: [{ title: "You send to", why: "Field Marketing Manager in the weekly note." }] },
          { title: "The contract might be untrue or shows a price that is not approved", lead: "Do not leave it signed.", art: "cmc-e-stop.png", body: ["Show the file. Tell Field Marketing Manager the same day."], points: [{ title: "You do", why: "Stop. Show the file." }] }
        ]
      }
    },
    mim: {
      story: "You keep one true written picture of what people ask for, what competitors do, and which ideas are real enough to hand on. Every fact has a source and a date. You close each idea as go, no-go, or more research with a date. You do not run ads, write the service, or open a town.",
      receives: [
        { from: "CRM / Sales", what: "Enquiry and booking extracts by service and area. Lost-lead reasons." },
        { from: "Field and CX", what: "Dated notes: what people asked, what failed." },
        { from: "Public listings", what: "Competitor prices, offers, new towns — with source and date." }
      ],
      gives: [
        { to: "Offer", what: "A validated service gap with evidence." },
        { to: "Expansion", what: "A validated named area with evidence." },
        { to: "Partnerships", what: "A kind of hotel desk or shop already marked go with evidence." },
        { to: "Head of Growth", what: "Weekly report and same-day alert if a competitor can steal bookings this week." }
      ],
      records: ["Demand tracker by service and town, pulled from the system", "Named competitor file with source and date on every change", "Idea list (one idea, one row)", "Go / no-go / more-research packs", "Same-day alerts when a competitor can steal bookings this week", "Weekly intelligence report", "Monthly decision pack used before the Growth plan is signed"],
      good: ["Every new fact names a source and a date — a file, a listing, or a dated note.", "Every High idea is go, no-go, or more research with a date, inside 10 working days.", "No row is left as “we might look at this later”.", "The weekly report is rebuilt from the trackers, not from memory.", "Every go pack names Offer, Expansion, or Partnerships — not Marketing as an ad.", "If a competitor can steal bookings this week, Head of Growth has a dated note the same working day."],
      bad: ["A rumour in a chat is treated as a competitor move.", "You briefed Marketing to advertise a guess.", "High ideas sit with no date and no decision.", "Competitor prices are mixed into our demand counts.", "You wrote a service sheet, opened a town, or ran an ad from this job.", "The weekly report is a story with only good news."]
    },
    osd: {
      story: "You write what Panun Kaergar can sell and finish: the name, what is in, what is out, how the job should run. Finance prices. Operations says they can do it. Head of Growth says Marketing may talk. You do not invent demand, run ads, or do the first jobs yourself.",
      receives: [
        { from: "Intelligence", what: "Validated service needs and unmet-demand patterns." },
        { from: "Sales and CX", what: "Requests we cannot finish, complaint patterns that look like a missing or wrong service." },
        { from: "Operations", what: "Whether we can actually do the job, with which providers and tools." },
        { from: "Finance", what: "Price options and commission impact." }
      ],
      gives: [
        { to: "Head of Growth", what: "A complete sheet plus launch checklist, ready for yes or no." },
        { to: "Marketing", what: "The only claims they may use after yes." },
        { to: "Sales and Operations", what: "The live catalogue: what is in, what is out." }
      ],
      records: ["Live list of services we may sell today", "Complete service sheet for every live name", "Launch checklist (Finance signed, Operations said yes, Head of Growth said Marketing may talk)", "Price recommendation you sent to Finance — not the signed price", "Keep / fix / stop note for the month", "Retired-service file (kept, not deleted)", "Weekly design-vs-live note"],
      good: ["Every live service has a complete sheet: what is in, what is out, how the job should run.", "Marketing talks about a service only after the launch checklist is 100 percent.", "A new need marked go has a sheet sent to Finance and Operations inside 15 working days.", "What is included and what is not is written on the sheet, not implied.", "A dead service is named keep, fix, or stop in the monthly note — not left in ads.", "Every live price traces to a Finance signature, not to you."],
      bad: ["Sales is picking from a messy menu of service names with no written sheet with no sheet.", "Marketing is selling a draft you have not finished.", "A dead service is still in ads.", "You set the final price or the commission yourself.", "You invented a service because someone asked, with no Intelligence go pack.", "You did the first customer jobs yourself instead of watching Operations do them."]
    },
    mem: {
      story: "You decide where Panun Kaergar may work next: enter, wait, or leave — in writing. Intelligence names a town. Provider Operations must be able to finish the first jobs. Head of Growth says yes. Then Marketing may spend. You do not hire workers. You do not run ads.",
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
      records: ["Town list with evidence on every row", "Enter / wait / no pack for each named town", "Launch plan with first services, spend list, and review date", "Provider Operations written yes that they can finish the first jobs", "Post-launch review on the date you wrote", "Pause or leave file", "Weekly town-list note"],
      good: ["No ad or stall runs in a town until Head of Growth signed enter and Provider Operations said they can finish the first jobs.", "A town in research has enter, wait, or no written inside 15 working days.", "Every live town has a review date on the launch plan.", "Pause and leave are written files, and Marketing, Sales, and Operations were told.", "You did not hire a worker and you did not change what we sell.", "The weekly note names the town that is stuck, not only the towns that look good."],
      bad: ["You kept a launch date when Provider Operations cannot staff the town.", "We stay forever in a dead town with no pause or leave file.", "Marketing spent in a town before enter was signed.", "A town sits in research with no decision and no dated delay.", "You hired the first plumber yourself, or you sent First Provider Onboarding in before the town was open.", "A town circled on a map with no written yes is being treated as open."]
    },
    pcm: {
      story: "You find hotels and shops that already meet people we want as customers. Those places send us a name. Sales calls them. You write the terms. You send their customers to Sales the same day with the hotel or shop name written. You do not book the customer. You do not send the person to a plumber. If a hotel wants to buy monthly maintenance for its own building, you pass that name to Office and Society Contracts. You do not write that contract.",
      receives: [
        { from: "Market Intelligence Manager", what: "A written note that hotel desks, or another kind of place, are worth trying. That note is not permission." },
        { from: "Head of Growth", what: "Yes on kind of hotel desk or shop and how we pay them, if we pay them." },
        { from: "Partners", what: "Enquiries from their customers." }
      ],
      gives: [
        { to: "Sales", what: "People a partner sent, with the hotel or shop name written the same day: partner name, service, town." },
        { to: "Head of Growth", what: "Keep / fix / close each month, with numbers." },
        { to: "Finance", what: "Anything that needs money we did not plan — as an ask, not a promise." }
      ],
      records: ["Partner list by kind (hotel desk, shop, and other types Head of Growth signed)", "Written terms for every live partner", "Partner file (who they are, towns, what they may say)", "Same-day lead tracker: partner name, service, town, Sales received", "Monthly keep / fix / close note", "Closure file, including any money still attached", "Weekly partner note"],
      good: ["Every partner enquiry names the partner, the service, and the town, and Sales received it the same day.", "Every live partner has written terms: how a lead is sent, and what we pay if we pay.", "You did not book the customer and you did not send the person to a plumber.", "Every live partner has keep, fix, or close written this month. A silent partner is written as closed.", "No office or society maintenance contract sits in this file — that is Field.", "You did not invent a new kind of partner, or a payment, without Head of Growth."],
      bad: ["A handshake with no paper is being treated as a live partner.", "You promised them cash or a payment Finance never saw.", "You booked the partner’s customer yourself, or you sent the person to a plumber.", "The partner is selling a job we do not do, and you have not stopped them.", "An office or society maintenance contract is sitting in the partner file.", "A silent partner is still listed as live with no monthly decision."]
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
      "Every week, open the same sheet: enquiries, qualified, bookings, cost. One action for ads, stalls, or partners that are off plan.",
      "Weekly 1:1 against their one result. If you act in their job, write ‘acting owner’."
    ],
    mkm: [
      "Read what Intelligence found and last month’s numbers. Write the calendar and the marketing kit. Head of Growth signs before any money is spent. Give Digital and Field their written work the same day.",
      "If Digital or Field wants a new sentence, price, town, or face, they stop until you say yes in writing. Digital then checks each video or post against this month’s marketing kit. You do not re-check every post.",
      "Give Digital an ads budget and Field a stall budget. They cannot take each other’s money. Pause before the money is gone.",
      "Operations asks every new person where they found us — call, WhatsApp, app, form, or any other way — and writes the answer. You check that happened. Digital reads those answers. Digital does not ask the customer.",
      "Give both managers their written work and check the result to their one result. If nobody sits in a job, write that you are doing that job and do the same-day checks.",
      "The same weekday every week: money spent, enquiries, cost, bookings, three things that worked, three that failed, one or two changes. Numbers, not a story."
    ],
    hom: [
      "Only what is on the signed calendar. Test a dummy enquiry so Operations actually receives it before you spend. If tracking dies, pause the same day.",
      "Same promise as the ads. Count people who found us without a paid ad on their own line. Do not book in a comment or a thread.",
      "Every file: name, type, service, town, date, where it went live. Content Maker makes and edits. You approve. Only then does it enter the library.",
      "Write the need. Collect the files from Operations. Content Maker edits from this month’s marketing kit and sends the cut to you for approval.",
      "You do not ask the customer where they came from. Operations asks and writes the answer. You read those answers. Do not book the job.",
      "Weekly: 3 things that worked, 3 that failed, one written request. By named online place (Meta, Google, search, social, or other), not as one lump called “digital”."
    ],
    cmc: [
      "Use the signed words and look. Stop if you need a new claim.",
      "Name it. Send it to the Digital Marketing Manager for approval. File it in the library only after the Digital Marketing Manager says yes.",
      "Take the files from Operations. Edit to the kit. Name them and send them to the Digital Marketing Manager for approval the same working day."
    ],
    fmm: [
      "Give Field Visitor which towns, which days, which boards. If they are someone we pay on a written contract, attach a written contract. If nobody sits there, you visit and write that you are doing that job.",
      "Send First Provider Onboarding on site with the signed papers. Signed files go to Provider Operations the same week. You do not assign jobs.",
      "Give Office and Society Contracts the signed papers. Named building, signed price. No cash. Hotel desks stay with Partnerships.",
      "Write which days are visits, which days are first-worker visits, which days are office and society visits. Write the same day that the visit did not happen.",
      "Each job gets a written brief the day the month is signed. A chat is not the brief. Save the files.",
      "The day before the marketing weekly: visits vs calendar, names to Sales, first local workers who signed, contracts signed, days that did not happen, town that produced nothing, covering notes, one or two written requests."
    ],
    fve: [
      "Confirm the pitch and the boards the day before. Be in the named town on the named day. Write the same day that the visit did not happen if you cannot go.",
      "Write name, phone, service, town, stall + town + date. Say Sales will call. Do not book and do not take money. A missing phone is not an enquiry.",
      "Only this month’s marketing kit. If you want a new sentence, price, town, or face, stop and ask Field Marketing Manager (VIS-04).",
      "Send one complete list to Sales before you leave the town. Confirm they received it. Field Marketing Manager gets the count, not the phone numbers.",
      "Write the stall log before you leave: times, materials, count, incidents, no cash, no booking.",
      "The day before the weekly field report: days vs calendar, enquiries by town, days that did not happen, town that produced nothing, materials that need reprinting, one or two written requests."
    ],
    fpo: [
      "Be in the named town. Meet the plumber, electrician, or other local worker at their shop or house. Do not sign them up from a company WhatsApp group without visiting them.",
      "Use only the signed papers. If a price is missing, stop and tell Field Marketing Manager.",
      "Write name, phone, services, towns, start date. They sign. You sign. Incomplete is not signed. Do not pretend they can work with us yet.",
      "Send the signed file to Provider Operations the same week. Write the received date. Do not assign the first job.",
      "If nobody will sign after the planned visits, write the town, dates, and why. Do not keep visiting to look busy.",
      "The day before the weekly field report: visits, files signed, files handed over, towns where nobody signed, one or two written requests."
    ],
    flc: [
      "Meet the person who can sign. Do not pitch a watchman and call it a contract.",
      "Use only the signed papers. If they want a different price, stop and tell Field Marketing Manager.",
      "Named building, town, start date, services, price, who signs. A WhatsApp yes is not a contract.",
      "File the signed contract. Tell Operations they can plan the jobs. Finance sends the invoice. Do not take cash.",
      "A one-off job the contract does not cover goes to Sales. You do not book them at the gate.",
      "The day before the weekly field report: visits, signed, waiting, or failed, buildings Operations can now serve, one or two written requests."
    ],
    mim: [
      "Pull CRM extracts. Count by service and area. Chat is not a tracker.",
      "Named list only. Log what actually changed, with source and date.",
      "One row per idea. Close as go, no-go, or more research with a date — never ‘interesting’.",
      "Go packs to Offer, Expansion, or Partnerships. Do not brief Marketing to advertise a guess."
    ],
    osd: [
      "One live list. Service names with no written sheet go to retired — they are not deleted.",
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
      "Name real types (hotel desk, shop). Do not add your uncle or a neighbour who says they know people. That is not a partner.",
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
      "With no signed plan, Digital and Field invent their own month.",
      "Enquiries with no source cannot be improved.",
      "Spending more than the plan without a new signature is spend nobody authorised.",
      "Cheap enquiries that never book are still waste.",
      "Head of Growth needs numbers, not a story. A report with only good news hides leaks.",
      "A job with no name has no owner. Then you have no one whose result you can check."
    ],
    hom: [
      "Spend with no path to Sales is waste.",
      "Sales cannot work a nameless lead.",
      "The signed plan is the rule.",
      "If cost blows, pause.",
      "A file that lives only on a personal phone is not the company copy.",
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
      "If the list is not with Sales the same day, it did not happen.",
      "A stall with no first local workers trains people to hate the brand.",
      "A verbal yes is not a contract. A made-up price becomes a fight.",
      "A box with no name has no owner.",
      "Marketing Manager needs numbers, not a story with only good stalls."
    ],
    fve: [
      "A missed field day is a missed town.",
      "If the list is not with Sales the same day, it did not happen.",
      "Town that produced nothing must be named so the plan can change.",
      "Stalls on random Saturdays that are not on the plan cannot be measured.",
      "Field Marketing Manager needs numbers, not a story with only good towns.",
      "You collect names at the stall. Sales books the job. You do not take money or book the job yourself."
    ],
    fpo: [
      "Getting people to sign from an office, without visiting them, is not this job.",
      "A verbal yes is not enough. They cannot work with us until they have signed.",
      "A private list is not a system.",
      "A made-up payout becomes a fight.",
      "Silence looks like the town is ready.",
      "Field Marketing Manager needs numbers."
    ],
    flc: [
      "A missed visit is a missed building.",
      "A verbal yes is not a contract.",
      "A made-up price becomes a fight.",
      "Cash in the office has no owner in the books.",
      "If Operations does not know, the jobs will not happen.",
      "Field Marketing Manager needs numbers."
    ],
    mim: [
      "The company cannot wait for a mood.",
      "A rumour is not Intelligence.",
      "A list of ideas nobody decided is not a decision."
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
      "People with no hotel or shop name written cannot be scored.",
      "Handshake deals become fights.",
      "A silent partner is a closed partner — write it down."
    ]
  };
  Object.keys(kpiWhy).forEach(function (id) {
    (roles[id].kpis || []).forEach(function (item, i) {
      if (kpiWhy[id][i] && !item.why) item.why = kpiWhy[id][i];
    });
  });

  workflows.growth.story = "E-Myth says the technician does the work, the manager runs the system, the entrepreneur sees the whole. Growth here is the system that makes something the market can buy, then creates enquiries with a written answer for how they found us. Sales books. Operations finishes. If those three mix, nobody owns a result.";
  workflows.growth.calendar = [
    { when: "Every working day", what: "Digital, Field and Partnerships hand enquiries with a written answer for how they found us to Sales. Tracking and spend are checked. Nothing sits in a personal chat." },
    { when: "Same weekday every week", what: "Growth review: plan vs actual, cost per enquiry, cost per booking, lost-lead reasons. One action for ads, stalls, or partners that are off plan." },
    { when: "Last week of the month", what: "Head of Growth signs next month’s plan with Marketing, using Intelligence. Offer and Expansion only put approved items on it." }
  ];
  workflows.growth.boundaries = [
    { who: "Growth", does: "Creates the offer the market can buy, and brings people in with a written answer for how they found us." },
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
      path.fail = "Do not spend on ads in a town with no providers. Circling a town on a map is not opening it.";
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

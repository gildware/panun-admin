window.PK_REPORTS = [
  {
    group: "Each week",
    id: "ceo-money",
    name: "Weekly money note",
    when: "The same weekday every week.",
    writer: "CEO",
    writerRole: "ceo",
    to: "Kept with the CEO. The four heads can be asked for the record behind a line the same day.",
    rule: "Three lines only. If a line has no record, leave it blank and send that gap back the same day. Do not type a remembered figure. A week with no note is unfinished. On each working day of a four-week run, also mark whether you touched a queue.",
    lines: [
      { field: "Week", hint: "Write the dates this note covers." },
      { field: "Jobs settled on both sides", hint: "Write the count of jobs finished with the customer payment and the provider settlement both in the admin panel. Copy the count from the Operations record." },
      { field: "Growth spend against the signed amount", hint: "Write the growth money spent this week, beside the amount already signed for the month on the Growth plan." },
      { field: "Cash not yet in the books", hint: "For each amount, write the customer, the booking, the amount, the provider, the date, and who holds the cash. Copy this from the cash notes Finance recorded." },
      { field: "Queue touch this week", hint: "For each working day, write none, or write that you assigned a lead, posted a payment, hired the day’s person, or took a ticket. A day with no mark does not count." }
    ]
  },
  {
    group: "Each week",
    id: "hog-review",
    name: "Weekly Growth review",
    when: "The same weekday every week, before 11:00, even if one seat is empty.",
    writer: "Head of Growth",
    writerRole: "hog",
    to: "CEO, and the five Growth people for the actions that sit in their jobs.",
    rule: "Do not cancel this review because a seat is empty. Do not keep it in a voice note. Name what failed.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Plan against what happened", hint: "Write spend, enquiries, and bookings against the signed monthly Growth plan." },
      { field: "Enquiries by how they found us", hint: "Write paid ads, search, stalls, and partners on separate lines. Do not mix them into one number." },
      { field: "Bookings", hint: "Write bookings that started as a Growth enquiry. Copy them from Customer Experience. Do not estimate." },
      { field: "Cost", hint: "Write cost per enquiry and cost per booking against the signed plan, by how people found us." },
      { field: "Why people did not book", hint: "Copy the reasons Customer Experience wrote. If they sent none, write that the reasons are missing." },
      { field: "High ideas still open", hint: "Write each important idea, how many working days it has waited, and whether it is go, no, or more research with a date." },
      { field: "One action for each way of finding us that is off plan", hint: "Write the action, the owner, and the date. One action, not a list of ten." },
      { field: "Empty seat", hint: "If you did an empty seat’s work, name the seat, what you did, and that you still read the other four results. If no seat was empty, write none." }
    ]
  },
  {
    group: "Each week",
    id: "mkt-weekly",
    name: "Weekly marketing report",
    when: "The day before the Growth review.",
    writer: "Marketing Manager",
    writerRole: "mkm",
    to: "Head of Growth. Digital and Field receive the changes that sit in their jobs.",
    rule: "Show paid ads, search, and stalls separately. Name what failed. A report of only good news is not finished.",
    lines: [
      { field: "Week dates", hint: "Write the Monday and the Sunday, or the dates your week uses." },
      { field: "Money against the plan", hint: "Write ads money and stall money on two lines, each against the signed plan." },
      { field: "Enquiries", hint: "Write how many came from paid ads, how many from search, and how many from stalls. Do not mix search into ads." },
      { field: "Enquiries that name where they came from", hint: "Write how many of those enquiries have the answer Operations wrote, and how many are still blank." },
      { field: "Bookings from marketing", hint: "Write how many Customer Experience has closed as bookings. If they have not closed them yet, write that the booking count is not in yet." },
      { field: "Off the plan", hint: "Write any ad, search page, or stall that spent money or ran outside the signed plan." },
      { field: "Three things that worked", hint: "Write three, from the records." },
      { field: "Three things that failed", hint: "Write three, from the records. Do not leave this blank to make the week look calm." },
      { field: "Changes for next week", hint: "Write one or two changes, and the name of the person who owns each change." }
    ]
  },
  {
    group: "Each week",
    id: "fld-weekly",
    name: "Weekly field report",
    when: "The day before the marketing report.",
    writer: "Field Marketing Manager",
    writerRole: "fmm",
    to: "Marketing Manager.",
    rule: "Visits, first local workers, and contracts are three separate parts. Do not send only stall numbers.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Visits by town", hint: "For each town on the calendar, write whether the visit happened or did not happen." },
      { field: "Names sent to Customer Experience", hint: "Write the count by town, and the date each list was sent." },
      { field: "First local workers", hint: "Write who signed, and whether the signed file went to Provider Experience." },
      { field: "Office and society contracts", hint: "Write each contract as signed, waiting, or failed. A spoken yes is not signed." },
      { field: "Days that did not happen", hint: "Write the town and the date. Do not leave a missed day off the page." },
      { field: "Town that produced nothing", hint: "Write the town if three planned visits produced no names, no signature, and no contract movement." },
      { field: "Empty job you covered", hint: "Write which of the three ground jobs was empty, and that you did it. If none was empty, write none." },
      { field: "Written requests", hint: "Write one or two requests for Marketing Manager. Not ten." }
    ]
  },
  {
    group: "Each week",
    id: "mi-weekly",
    name: "Weekly intelligence report",
    when: "The day before the Growth review.",
    writer: "Market Intelligence Manager",
    writerRole: "mim",
    to: "Head of Growth.",
    rule: "Build it from the registers. Every new fact needs a source and a date. Do not write a rumour.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Demand by service and town", hint: "Write what people asked for and what was sold, from the system. Chat is not a count." },
      { field: "Competitor changes", hint: "Write what changed, the source, and the date. If nothing changed, write none." },
      { field: "Ideas by stage", hint: "Write how many are open, how many are go, how many are no, and how many are more research with a date." },
      { field: "High ideas and their age", hint: "Write each important idea and how many working days it has waited." },
      { field: "What you could not prove", hint: "Write the fact you do not have. Do not fill it with a guess." }
    ]
  },
  {
    group: "Each week",
    id: "osd-weekly",
    name: "Design against live",
    when: "The day before the Growth review.",
    writer: "Offer and Service Development Manager",
    writerRole: "osd",
    to: "Head of Growth.",
    rule: "Every service is design, waiting, live, or retired. Silence looks like the live list is fine. You do not set the price.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Each service", hint: "Write the name, and whether it is in design, waiting, live, or retired." },
      { field: "Waiting on whom", hint: "If a sheet is waiting, write Finance, Operations, or Head of Growth, and what they still have to sign." },
      { field: "Written requests", hint: "Write one or two requests. Not a new service you have not been given." }
    ]
  },
  {
    group: "Each week",
    id: "mem-weekly",
    name: "Town list and decisions",
    when: "The day before the Growth review.",
    writer: "Market Expansion Manager",
    writerRole: "mem",
    to: "Head of Growth.",
    rule: "Each town is research, wait, enter, live, pause, or leave. Name the town that is stuck. Do not open a town before Provider Experience says the first jobs can be finished.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Each town", hint: "Write the town and one of these: research, wait, enter, live, pause, or leave." },
      { field: "How long it has waited", hint: "Write the age of each town that is still in research or waiting." },
      { field: "Stuck town", hint: "Write the town that did not move, and why." },
      { field: "Written requests", hint: "Write one or two requests for Head of Growth." }
    ]
  },
  {
    group: "Each week",
    id: "pcm-weekly",
    name: "Weekly partner note",
    when: "The day before the Growth review.",
    writer: "Partnerships and Channels Manager",
    writerRole: "pcm",
    to: "Head of Growth.",
    rule: "Write each hotel and shop on its own. A lump called partners hides a desk that sends nobody. Name what failed.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Partners", hint: "Write each live hotel or shop, and any desk that went silent." },
      { field: "Names sent", hint: "Write how many named people you sent to Customer Experience, by hotel or shop, and the date." },
      { field: "Bookings", hint: "Write how many of those names became bookings, if Customer Experience has closed them. If not, write that the booking count is not in yet." },
      { field: "Junk or silence", hint: "Write the hotel or shop that sent names that could not be booked, or sent none." },
      { field: "Written requests", hint: "Write one or two requests. Not a new price." }
    ]
  },
  {
    group: "Each week",
    id: "cxm-weekly",
    name: "Customer operations report",
    when: "The day the Head of Operations reviews the week.",
    writer: "Customer Experience Manager",
    writerRole: "cxm",
    to: "Head of Operations.",
    rule: "Put bookings taken next to those same bookings later finished and settled. A strong first count with a weak second count is a miss. Send those bookings back the same week. Do not estimate.",
    lines: [
      { field: "Period", hint: "Write the dates." },
      { field: "Leads by source, area, and service", hint: "Write the counts from the admin panel." },
      { field: "Valid, invalid, and unknown", hint: "Write how many leads can be booked, how many cannot, and how many you still cannot classify." },
      { field: "Eligible leads that became bookings", hint: "Leave out invalid, duplicate, out-of-area, impossible, and formally excluded leads. Write the count and the band: 40 or more out of 100 is excellent, 30 to 39 is acceptable, 20 to 29 needs improvement, below 20 is critical." },
      { field: "Those bookings finished and settled", hint: "Of that same set, write how many were later finished to the written standard, with the customer payment and the provider settlement both in the admin panel." },
      { field: "Bookings sent back this week", hint: "List the bookings that are still open past the visit, or marked finished while one side of the money is missing, and the executive who owns each one." },
      { field: "Cancellations and reasons", hint: "Write the count and the true reason for each." },
      { field: "Follow-up misses", hint: "Write which follow-ups were due and were not done." },
      { field: "Open complaints", hint: "Write each open complaint, the status, and the next action." },
      { field: "Future-customer conversion by employee", hint: "Write, for each person, how many eligible future customers they had and how many booked. The line is 50 out of 100 of the people they were given." },
      { field: "Booking advance", hint: "Write the advances taken, and that each amount matches the payment policy. If the policy amount is blank, write that the advance was not taken." },
      { field: "Customer payment", hint: "Write what the admin panel shows the customer paid. Do not add a line called revenue." },
      { field: "Provider settlement", hint: "Write what the admin panel shows was settled with the provider." },
      { field: "Cash still open", hint: "Write each open cash amount and who holds it." }
    ]
  },
  {
    group: "Each week",
    id: "pxm-cover",
    name: "Provider performance and coverage",
    when: "With the weekly operations review.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations.",
    rule: "Inactive and weak providers belong on the page. A list of only good providers hides the gap. Do not write a count you cannot open in the records.",
    lines: [
      { field: "Period", hint: "Write the dates." },
      { field: "Active, new, and inactive", hint: "Write how many providers are active, how many were onboarded, how many were activated, and how many are inactive." },
      { field: "Availability and response", hint: "Write who is free, who is not answering, and who is not accepting work." },
      { field: "Complaints, cancellations, and service issues", hint: "Write each open item, the provider, and the next action." },
      { field: "Coverage by area and by category", hint: "Write where there are enough providers, only one, none, a temporary, or demand with too few providers." },
      { field: "Training still pending", hint: "Write who was assigned training and has not finished it." }
    ]
  },
  {
    group: "Each week",
    id: "pxm-pay",
    name: "Provider payments",
    when: "On the cycle Accounts and Operations use.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations and Accounts.",
    rule: "Write due, completed, pending, and any difference from the records. Hiding a difference is a miss.",
    lines: [
      { field: "Period", hint: "Write the dates this payment report covers." },
      { field: "Provider", hint: "Write one block for each provider. Do not merge two providers into one line." },
      { field: "Amount due", hint: "Write the amount the records say is due. If you cannot open the record, leave the amount blank." },
      { field: "Amount completed", hint: "Write what has already been paid, from the records." },
      { field: "Pending", hint: "Write what is still unpaid, and who owns the next step." },
      { field: "Difference", hint: "Write any amount that does not match. If there is no difference, write none." }
    ]
  },
  {
    group: "Each week",
    id: "hoo-weekly",
    name: "Weekly operational review",
    when: "The same time each week, after both manager reports are in.",
    writer: "Head of Operations",
    writerRole: "hoo",
    to: "The two managers, and the CEO when a decision sits above Operations.",
    rule: "If a manager report is missing, that is the first line. Do not rewrite their report. Connect demand with coverage. Keep the four money lines separate.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Report status", hint: "Write whether the customer report and the provider report arrived, and whether you sent either back." },
      { field: "Exceptions", hint: "Write the checks that missed their line, and the owner of the correction." },
      { field: "Demand against coverage", hint: "Write areas or services with leads and not enough providers." },
      { field: "Four money lines", hint: "Copy the booking advance, the customer payment, the provider settlement, and cash still open. Do not add them into one revenue figure." },
      { field: "Open actions from last week", hint: "Write each action, whether it is done, and the date." },
      { field: "New actions", hint: "Write the action, one owner, and the date it is due." }
    ]
  },
  {
    group: "Each week",
    id: "fin-weekly",
    name: "Money and people note",
    when: "Each week, and the same day if money or a people file cannot wait.",
    writer: "Head of Finance and People",
    writerRole: "hof",
    to: "CEO.",
    rule: "Use the same four money lines as Customer Experience. Do not add a line called revenue. A line with no record is sent back, or marked unchecked.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Booking advance", hint: "Write the advances that match the payment policy. If the policy is blank for a job, write that the amount was held." },
      { field: "Customer payment", hint: "Write what the admin panel shows the customer paid." },
      { field: "Provider settlement", hint: "Write what the admin panel shows was settled with the provider." },
      { field: "Cash still open", hint: "Write each amount and who holds it. Write which cash notes are not yet in the books." },
      { field: "People files", hint: "Write which files are incomplete, who joined against a written gap, and any pay question you refused to invent." },
      { field: "Amounts you refused", hint: "Write what was asked, who asked, and that the policy was blank. Do not write a suggested rupee amount." }
    ]
  },
  {
    group: "Each week",
    id: "hrm-weekly",
    name: "People note",
    when: "Each week, and the same day if someone is working without a file.",
    writer: "HR Manager",
    writerRole: "hrm",
    to: "Head of Finance and People.",
    rule: "Point at the people files. Do not report a person as ready if you would not hand the file to a stranger. A pay question is named. It is not solved with a number you made up.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Who joined", hint: "Write the person, the seat, and the written gap they joined against. The gap must name the seat, the result, and proof that seat’s own work is already happening." },
      { field: "Missing papers", hint: "Write which file is incomplete, and which paper is missing." },
      { field: "Open requests", hint: "Write each open request and the one person who owns it." },
      { field: "Pay questions you stopped", hint: "Write what was asked and that the pay file does not contain the amount. Do not write a suggested salary." },
      { field: "Someone working without a file", hint: "Write the name the same day, or write none." }
    ]
  },
  {
    group: "Each week",
    id: "tech-weekly",
    name: "Technology week",
    when: "Each week, and the same day if a tool the seats need is down.",
    writer: "Head of Technology and Data",
    writerRole: "hot",
    to: "CEO.",
    rule: "Point at the ticket and the written ask. Do not report effort as a release. A blocked seat is not saved for the weekly note. It is written the same day.",
    lines: [
      { field: "Week", hint: "Write the dates." },
      { field: "Seats that were blocked", hint: "Write which seat could not do the next written step, and the one owner of the fix." },
      { field: "What shipped", hint: "Write what went out, and the written ask it matched. If it does not match the ask, write that it is not released." },
      { field: "What you sent back", hint: "Write the ask you returned because finished was not described." },
      { field: "Live records at risk", hint: "Write any change that could erase or hide a booking, a payment, a lead, or a provider file, and whether the head who owns that record wrote yes." }
    ]
  },
  {
    group: "Each month",
    id: "cxm-month",
    name: "Demand and the four money lines",
    when: "With the monthly close.",
    writer: "Customer Experience Manager",
    writerRole: "cxm",
    to: "Head of Operations.",
    rule: "Compare this month with the previous month using the same four money lines. Do not add a line called revenue.",
    lines: [
      { field: "Month", hint: "Write the month." },
      { field: "Areas", hint: "Write high-demand areas, low-demand areas, areas with the most leads, areas with the most bookings, areas with leads but low conversion, and areas with demand but not enough providers." },
      { field: "Services", hint: "Write high-demand services, low-demand services, services with the most leads, services with the most bookings, services with poor conversion, and services that need more providers." },
      { field: "Booking advance", hint: "Write this month and the previous month, only amounts that match the payment policy." },
      { field: "Customer payment", hint: "Write this month and the previous month from the admin panel." },
      { field: "Provider settlement", hint: "Write this month and the previous month from the admin panel." },
      { field: "Cash still open", hint: "Write what is still open at month end, and who holds it." },
      { field: "Areas and services that need an action", hint: "Write the action and who receives it. A coverage gap goes to Provider Experience, not only into a paragraph." }
    ]
  },
  {
    group: "Each month",
    id: "hoo-month",
    name: "Management operational report",
    when: "After the monthly review.",
    writer: "Head of Operations",
    writerRole: "hoo",
    to: "CEO.",
    rule: "The month is for causes, not only totals. Keep the four money lines separate. Name repeated problems and next month’s priorities.",
    lines: [
      { field: "Month", hint: "Write this month and the previous month." },
      { field: "Leads, bookings, and eligible conversion", hint: "Write both months. Write the band for eligible conversion." },
      { field: "Cancellations", hint: "Write the count and the repeated reasons." },
      { field: "Four money lines", hint: "Write booking advance, customer payment, provider settlement, and cash still open. Do not combine them." },
      { field: "Coverage", hint: "Write where demand has too few providers." },
      { field: "Repeated problems", hint: "Write the cause, the owner, and whether last month’s action finished." },
      { field: "Priorities for next month", hint: "Write the few priorities, each with one owner." }
    ]
  },
  {
    group: "Each month",
    id: "mi-month",
    name: "Monthly decision pack",
    when: "The last week of the month, before the Growth plan is signed.",
    writer: "Market Intelligence Manager",
    writerRole: "mim",
    to: "Head of Growth.",
    rule: "Head of Growth uses this to sign the month. A mood is not a pack. No idea row may stay silent.",
    lines: [
      { field: "Month", hint: "Write the month you are recommending for." },
      { field: "Demand against the previous four weeks", hint: "Write what rose and what fell, by service and town, from the system." },
      { field: "Competitors", hint: "Write the changes that matter, each with a source and a date." },
      { field: "What to keep, stop, or research", hint: "Write each item as keep, stop, or research with a date. Do not write that you might look at it later." }
    ]
  },
  {
    group: "The day something moves",
    id: "osd-launch",
    name: "Launch checklist",
    when: "Before Head of Growth says Marketing may talk about a service.",
    writer: "Offer and Service Development Manager",
    writerRole: "osd",
    to: "Head of Growth, with the yes pack.",
    rule: "All three lines must be yes, or the service does not launch. A spoken yes is not a signature.",
    lines: [
      { field: "Service", hint: "Write the service name as it will appear on the live list." },
      { field: "Sheet complete", hint: "Write yes only if a stranger can open the sheet and do the job. Otherwise write what is still missing." },
      { field: "Finance signed the price", hint: "Write the date Finance signed the price file. If they have not signed, write not signed. Do not write a price of your own." },
      { field: "Operations can do the first jobs", hint: "Write the date Operations wrote yes, or write that the yes is missing." }
    ]
  },
  {
    group: "The day something moves",
    id: "dig-daily",
    name: "Daily digital note",
    when: "Any working day spend jumped, tracking broke, something was paused, or files went live.",
    writer: "Digital Marketing Manager",
    writerRole: "hom",
    to: "Marketing Manager.",
    rule: "Write it the same day. Do not wait for the weekly report. If Operations did not write how people found you, say so.",
    lines: [
      { field: "Date", hint: "Write today’s date." },
      { field: "Spend today", hint: "Write what was spent today on paid ads." },
      { field: "Spend against the plan", hint: "Write month-to-date spend beside the ads amount on the signed plan." },
      { field: "New enquiries", hint: "Write the count by Meta, Google, search, social, or other, only where Operations wrote how the person found you." },
      { field: "Pauses", hint: "Write what you paused and why. If nothing was paused, write none." },
      { field: "Tracking", hint: "Write whether tracking is working. If it is broken, write what is broken." },
      { field: "Library", hint: "Write whether today’s live files are in the library. If a file is not filed, it did not happen." },
      { field: "Content Maker seat", hint: "Write yes if you are doing Content Maker work because that seat is empty, and that you wrote it down. Otherwise write no." }
    ]
  },
  {
    group: "The day something moves",
    id: "mkt-daily",
    name: "Daily marketing note",
    when: "Any working day spend moved, a source is missing, or an ad, search page, or stall was paused.",
    writer: "Marketing Manager",
    writerRole: "mkm",
    to: "The file. Head of Growth only if spend will pass the plan or something untrue had to come down.",
    rule: "The aim is an empty list of enquiries with no source. Write ads and stalls separately.",
    lines: [
      { field: "Date", hint: "Write today’s date." },
      { field: "Money against the plan", hint: "Write ads and stalls separately." },
      { field: "New enquiries", hint: "Write paid ads, search, and stalls separately." },
      { field: "Missing sources still open", hint: "Write each enquiry that still has no answer for how they found you." },
      { field: "Pauses", hint: "Write what was paused and why." },
      { field: "Empty Digital or Field seat", hint: "Write which seat you are doing today because it is empty. If both seats are filled, write both filled." }
    ]
  },
  {
    group: "The day something moves",
    id: "poe-exception",
    name: "Provider exception note",
    when: "The day you find a missing document, a confirmation that looks false, a payment difference, or a provider you cannot activate cleanly.",
    writer: "Provider Support",
    writerRole: "poe",
    to: "Provider Experience Manager, the same day.",
    rule: "Write the fact and the next action. A problem that stays in your head is hidden.",
    lines: [
      { field: "Date", hint: "Write the date you found it." },
      { field: "Provider", hint: "Write the provider’s name as it is in the file." },
      { field: "What is wrong", hint: "Write the missing document, the false-looking confirmation, the payment difference, or the reason you cannot activate them." },
      { field: "Next action", hint: "Write the next step and who does it." }
    ]
  },
  {
    group: "The day something moves",
    id: "fam-open",
    name: "Open money list",
    when: "Each working day, before the day ends.",
    writer: "Finance and Accounts Manager",
    writerRole: "fam",
    to: "Head of Finance and People.",
    rule: "Every open item has one owner. Today’s receipts, invoices, and cash notes are written, returned, held, or handed on. Do not turn an open item into a paid one to make the day look calm.",
    lines: [
      { field: "Date", hint: "Write today’s date." },
      { field: "Open item", hint: "Write one block for each receipt, invoice, cash note, or settlement that is still open." },
      { field: "Owner", hint: "Write the one person whose name is on that item." },
      { field: "What it is", hint: "Write whether it is a cash note, a receipt, an invoice, or a settlement." },
      { field: "Status", hint: "Write waiting, written, returned, or held because the policy is blank." },
      { field: "Next step", hint: "Write the next step. An item with no next step is not assigned." }
    ]
  }
];

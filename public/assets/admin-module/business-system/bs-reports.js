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
      { field: "Growth spend against the signed amount", hint: "Three lines copied from the weekly Growth review: paid ads, stalls, partners. On each line write spent this week, spent so far this month, the amount signed on the Growth plan, and over or under. If that review was not sent, write “Growth review missing”. Do not type a remembered figure." },
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
    to: "CEO. Marketing, Intelligence, Offer, Expansion, and Partnerships each get only the action that sits in their job.",
    rule: "This page decides keep, pause, or change for ads, stalls, and partners. One mixed total is not a review. Do not cancel it because a seat is empty. Do not keep it in a voice note.",
    lines: [
      { field: "Week dates", hint: "Write the Monday and the Sunday." },
      { field: "Spend against the signed plan", hint: "Three lines: paid ads, stalls, partners. On each line write spent this week, spent so far this month, the amount signed for the month, and over or under. Copy spend from the weekly marketing report and the partner note. Copy the signed amounts from the monthly Growth plan. If that plan is not signed, write “no signed plan”. Do not invent a ceiling." },
      { field: "Enquiries by source", hint: "Six counts, not one: paid ads, search, stalls, partners, other, and blank. Blank means Operations did not write how the person found us. Copy the counts from the admin panel. Do not put search inside ads, and do not put a blank into “other”." },
      { field: "Qualified enquiries by source", hint: "The same six lines. Qualified means Customer Experience marked the enquiry as one they can book. Copy their mark. Do not decide it yourself." },
      { field: "Bookings by source", hint: "The same six lines. A booking is one Customer Experience has closed. Copy their count. If they have not closed the week, write “booking count not in yet”. Do not estimate from chats." },
      { field: "Cost per enquiry and cost per booking", hint: "For ads, stalls, and partners: spend divided by enquiries, and spend divided by bookings, written next to the ceiling on the signed plan. If bookings are zero, write “no bookings”. Do not hide a zero by skipping the line." },
      { field: "Enquiries with a written source", hint: "Write how many enquiries have Operations’ answer, out of how many arrived. The line is 95 or more out of 100. If it is under that, name the ad, page, stall, or partner still blank." },
      { field: "Why people did not book", hint: "Write the three reasons Customer Experience recorded most often, with the count of each. If they sent none, write “reasons missing” and send that gap back the same day." },
      { field: "High ideas past 10 working days", hint: "Each idea, its age in working days, and one decision: go, no, or more research with a date. An idea with no decision is still open. Copy the age from the intelligence register." },
      { field: "One action for each channel off the ceiling", hint: "Ads, stalls, and partners. For each one over the cost ceiling or under the booking target: one action, one owner, and the date it is due. Not a list of ten." },
      { field: "Empty seat", hint: "Name the seat you covered, what you did, and that you still read the other four results. If no seat was empty, write none." }
    ]
  },
  {
    group: "Each week",
    id: "mkt-weekly",
    name: "Weekly marketing report",
    when: "The day before the Growth review.",
    writer: "Marketing Manager",
    writerRole: "mkm",
    to: "Head of Growth. Digital gets the ads and search changes. Field gets the stall, first-worker, and contract changes.",
    rule: "Head of Growth cannot review the week without this page. Paid ads, search, and stalls stay on separate lines. A report with no failed line is not finished.",
    lines: [
      { field: "Week dates", hint: "Write the Monday and the Sunday." },
      { field: "Ads spend against the signed plan", hint: "Write spent this week, spent so far this month, the ads amount on the signed plan, and over or under. Copy the spend from the ad accounts. Copy the signed amount from the plan Head of Growth signed. The line is within 10% of that amount." },
      { field: "Stall spend against the signed plan", hint: "The same four numbers for stall money. Copy the spend from Field’s weekly report. Do not take stall money from the ads line." },
      { field: "Enquiries", hint: "Three counts: paid ads, search, stalls. Copy them from Operations’ written answer for how the person found us. Search is not ads." },
      { field: "Enquiries with a written source", hint: "Write how many of those enquiries have Operations’ answer, and how many are still blank. The line is 95 or more out of 100. List each blank lead by the id or phone Operations already has." },
      { field: "Bookings from these enquiries", hint: "Three counts, same split: paid ads, search, stalls. Copy what Customer Experience has closed. If they have not closed the week, write “booking count not in yet”. Do not guess." },
      { field: "Cost per enquiry and cost per booking", hint: "For ads and for stalls: spend divided by enquiries, and spend divided by bookings, next to the ceiling on the signed plan. If a side has no bookings, write “no bookings”." },
      { field: "Off the signed plan", hint: "Name any ad, search page, or stall that spent money or ran for a service or town the signed plan does not allow. If none, write none." },
      { field: "What failed", hint: "Name the ad, page, or town that spent and brought no enquiry, or brought enquiries with no source. Copy it from Digital’s weekly pack and Field’s weekly report. A blank fail line means this report is not finished." },
      { field: "Changes for next week", hint: "One or two changes. Each line names the change, one owner (Digital or Field), and the date. Not ten." }
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
    rule: "Visits, first local workers, and office or society contracts are three results. Stall counts alone do not finish this report.",
    lines: [
      { field: "Week dates", hint: "Write the Monday and the Sunday." },
      { field: "Visits against the signed calendar", hint: "For each town and date on the signed calendar: happened, or did not happen. A missed day stays on the page. Copy the calendar from the marketing plan, not from memory." },
      { field: "Names sent to Customer Experience", hint: "For each town: how many complete rows (name, phone, service, town), the date the list was sent, and whether Customer Experience confirmed they received it. A row with no phone is not an enquiry." },
      { field: "First local workers", hint: "For each person: name, town, signed or not signed, and the date the signed file reached Provider Experience. If it was not sent, write “not sent”. A spoken yes is not signed." },
      { field: "Office and society contracts", hint: "For each building: name, town, and signed, waiting, or failed. A spoken yes is waiting, not signed. Write the date Operations was told, or “Operations not told”." },
      { field: "Towns with nothing after three planned visits", hint: "Name the town if three planned visits produced no names, no signature, and no contract movement. If none, write none." },
      { field: "Empty ground job", hint: "Write Field Visitor, First Provider Onboarding, or Office and Society Contracts if that seat was empty and you did the work. If none was empty, write none." },
      { field: "Requests for Marketing Manager", hint: "One or two requests. Each has a date. Not ten, and not a new price." }
    ]
  },
  {
    group: "Each week",
    id: "mi-weekly",
    name: "Weekly intelligence report",
    when: "The day before the Growth review.",
    writer: "Market Intelligence Manager",
    writerRole: "mim",
    to: "Head of Growth. Offer, Expansion, and Partnerships get a row only when you have marked it go.",
    rule: "Every new fact has a source and a date. Chat is not a count. A rumour is not a competitor change. Head of Growth uses this page in the weekly review.",
    lines: [
      { field: "Week dates", hint: "Write the Monday and the Sunday." },
      { field: "Demand by service and town", hint: "A table: service, town, enquiries, bookings. Copy both counts from the admin panel. Do not type a number you heard in chat." },
      { field: "Lost-lead reasons that repeat", hint: "Reason and count, copied from Customer Experience. A reason that appears once is not a pattern. If they sent no reasons, write “reasons missing”." },
      { field: "Competitor changes", hint: "What changed, the source, and the date. If nothing changed, write none. Do not write what someone heard." },
      { field: "Ideas by stage", hint: "Four counts from the register: open, go, no, and more research with a date. A row left as “interesting” is still open." },
      { field: "High ideas and their age", hint: "Each high idea and its age in working days. Any idea older than 10 working days with no go, no, or dated research is named here." },
      { field: "What you could not prove", hint: "The fact the registers do not contain. Leave it blank of guesses." }
    ]
  },
  {
    group: "Each week",
    id: "osd-weekly",
    name: "Design against live",
    when: "The day before the Growth review.",
    writer: "Offer and Service Development Manager",
    writerRole: "osd",
    to: "Head of Growth, before the weekly review.",
    rule: "Every name on the live list is design, waiting, live, or retired. A name with no sheet is not a service. You do not set the price.",
    lines: [
      { field: "Week dates", hint: "Write the Monday and the Sunday." },
      { field: "Each service", hint: "Name, then one status: design, waiting, live, or retired. Copy the list from the live catalogue, not from ads that are already running." },
      { field: "Sheet a stranger can follow", hint: "For each live service: yes or no. Yes only if the sheet has what is in, what is out, the job steps, and the quality bar. Name every live service that is no." },
      { field: "Waiting on whom", hint: "If a sheet is waiting: Finance (price not signed), Operations (they have not written that the first jobs can be finished), or Head of Growth (no yes). Write the signature that is still missing." },
      { field: "Requests", hint: "One or two requests for Head of Growth. Not a new service you were not given." }
    ]
  },
  {
    group: "Each week",
    id: "mem-weekly",
    name: "Town list and decisions",
    when: "The day before the Growth review.",
    writer: "Market Expansion Manager",
    writerRole: "mem",
    to: "Head of Growth. Marketing and Partnerships may spend in a town only after you have marked it enter and Head of Growth has signed.",
    rule: "Each town is research, wait, enter, live, pause, or leave. Do not mark enter until Provider Experience has written that the first jobs can be finished.",
    lines: [
      { field: "Week dates", hint: "Write the Monday and the Sunday." },
      { field: "Each town", hint: "Town name and one status: research, wait, enter, live, pause, or leave. Copy the list from the town register." },
      { field: "Age of towns still in research or wait", hint: "Working days since the town was opened in the register. A wait with no review date is not a decision — write “no date”." },
      { field: "Can the first jobs be finished", hint: "For any town you want to mark enter: Provider Experience yes or no, and the date they wrote it. No means the status stays wait. Do not write yes yourself." },
      { field: "Stuck town", hint: "The town that did not move this week, and the missing piece: demand evidence, capacity yes, or Head of Growth’s signature." },
      { field: "Requests", hint: "One or two requests for Head of Growth, each with a date." }
    ]
  },
  {
    group: "Each week",
    id: "pcm-weekly",
    name: "Weekly partner note",
    when: "The day before the Growth review.",
    writer: "Partnerships and Channels Manager",
    writerRole: "pcm",
    to: "Head of Growth. Customer Experience gets the leads the same day. They do not get this weekly note.",
    rule: "Each hotel and shop is its own line. A total called “partners” hides a desk that sent nobody. You do not book the customer.",
    lines: [
      { field: "Week dates", hint: "Write the Monday and the Sunday." },
      { field: "Each live partner", hint: "Hotel or shop name, how many named people you sent to Customer Experience, and the date sent. One partner per line. Copy the names from the same-day partner log." },
      { field: "Bookings from those names", hint: "For each partner: how many Customer Experience closed as bookings. If they have not closed them, write “booking count not in yet”. Do not estimate." },
      { field: "Junk or silence", hint: "Name the partner who sent people Customer Experience could not book, or who sent none. Write the count of junk leads if Customer Experience marked them." },
      { field: "Written terms", hint: "Name any live partner who has no signed term sheet. A handshake is not live." },
      { field: "Requests", hint: "One or two requests for Head of Growth. Not a price you invented." }
    ]
  },
  {
    group: "Each working day",
    id: "cxm-daily",
    name: "Daily close",
    when: "Every working day, before you leave.",
    writer: "Customer Experience Manager",
    writerRole: "cxm",
    to: "Head of Operations.",
    rule: "You assign the inbox. Then you copy only the stuck rows from the executives’ daily closes. Do not copy their whole lists.",
    lines: [
      { field: "The inbox", hint: "Every lead that arrived today, who owns it, and anything still with no owner." },
      { field: "The team", hint: "Which daily closes came in, and the stuck rows copied from those closes." }
    ],
    sections: [
      {
        title: "The inbox",
        rows: [
          {
            type: "stats",
            label: "Leads that arrived today",
            hint: "Count them from the customer inbox.",
            cells: [
              { label: "Arrived", example: "11" },
              { label: "Given an owner", example: "9" },
              { label: "Moved to the provider inbox", example: "2" }
            ]
          },
          {
            type: "table",
            label: "Each lead",
            hint: "One row for each lead. A service provider is moved to the provider inbox. Do not give that lead to a Customer Experience Executive.",
            columns: ["Lead", "Type", "Priority", "What you did"],
            example: [
              ["LD-8821", "Customer", "Hot", "Aisha"],
              ["LD-8835", "Customer", "Hot", "Aisha"],
              ["LD-8829", "Customer", "Warm", "Aisha"],
              ["LD-8841", "Customer", "Hot", "Aisha"],
              ["LD-8844", "Future customer", "Warm", "Aisha"],
              ["LD-8830", "Customer", "Cold", "Aisha"],
              ["LD-8824", "Customer", "Hot", "Imran"],
              ["LD-8838", "Customer", "Cold", "Imran"],
              ["LD-8826", "Future customer", "Warm", "Rafia"],
              ["LD-8822", "Service provider", "—", "Moved to the provider inbox"],
              ["LD-8828", "Service provider", "—", "Moved to the provider inbox"]
            ]
          },
          {
            type: "stats",
            label: "Still with no owner when you leave",
            hint: "These two counts must be zero before you leave.",
            cells: [
              { label: "Hot with no owner", example: "0" },
              { label: "Provider leads still here", example: "0" }
            ]
          }
        ]
      },
      {
        title: "The team",
        rows: [
          {
            type: "table",
            label: "Daily closes",
            hint: "Write in or not in. If a close is not in, do not fill that person’s rows yourself.",
            columns: ["Executive", "Close"],
            example: [
              ["Aisha", "In"],
              ["Imran", "In"],
              ["Rafia", "Not in"]
            ]
          },
          {
            type: "table",
            label: "Still open, copied from the closes that are in",
            hint: "Copy the stuck rows only. Keep the id and the owner.",
            columns: ["Owner", "Record", "What is stuck"],
            example: [
              ["Aisha", "LD-8841", "Hot. Not contacted"],
              ["Aisha", "LD-8844", "Warm. Not contacted"],
              ["Aisha", "LD-8830", "Cold lead called while hot LD-8841 was waiting"],
              ["Aisha", "LD-8790", "Follow-up due today, not done"],
              ["Aisha", "LD-8766", "Follow-up due today, not done"],
              ["Aisha", "LD-8810", "Service provider. Returned. Not onboarded"],
              ["Aisha", "BK-4411", "Advance is policy blank. No provider yes. Stays open"],
              ["Aisha", "BK-4390", "Visit today. Provider has not confirmed"],
              ["Aisha", "BK-4404", "Both payment lines blank. Cash ₹1,800. Aisha is holding it"],
              ["Aisha", "LD-8772", "Last message is not from Panun Kaergar"],
              ["Aisha", "LD-8801", "Last message is not from Panun Kaergar"],
              ["Aisha", "LD-8755", "No next time"],
              ["Imran", "BK-4377", "Cash ₹4,200. Imran is holding it"]
            ]
          },
          {
            type: "table",
            label: "Complaints you took",
            hint: "One row for each complaint you took yourself. If there was none, write none in the first row.",
            columns: ["Record", "What you decided", "Next action"],
            example: [
              ["LD-8788", "Visit time changed and the customer was not told", "Saturday 10:00. Aisha messages both sides"]
            ]
          },
          {
            type: "note",
            label: "Joined the queue",
            hint: "Write no, or the lead ids you worked yourself.",
            example: "No"
          }
        ]
      }
    ],
    example: {
      writer: "Customer Experience Manager",
      when: "Friday 26 Sep 2026",
      intro: "One finished day. The inbox was assigned. Rafia’s close was not in, so her rows are not on this page. Do not copy these ids onto your own day."
    }
  },
  {
    group: "Each week",
    id: "cxm-weekly",
    name: "Customer operations report",
    when: "The day the Head of Operations reviews the week.",
    writer: "Customer Experience Manager",
    writerRole: "cxm",
    to: "Head of Operations.",
    rule: "Put bookings taken next to bookings finished. If the first count is strong and the second is weak, send those bookings back the same week. Add the daily closes. Do not guess.",
    lines: [
      { field: "Leads and bookings", hint: "How many leads could be booked, how many became bookings, and how many of those bookings were finished with both payments." },
      { field: "Money and what failed", hint: "The four money lines, future customers, cancellations, and the bookings you sent back." }
    ],
    sections: [
      {
        title: "The week",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "stats",
            label: "Leads",
            hint: "Copy the counts from the admin panel.",
            cells: [
              { label: "All leads", example: "80" },
              { label: "Can be booked", example: "55" },
              { label: "Cannot be booked", example: "20" },
              { label: "Still unknown", example: "5" }
            ]
          },
          {
            type: "table",
            label: "Where the leads came from",
            hint: "One row for each source.",
            columns: ["Source", "Leads"],
            example: [
              ["Paid ads", "26"],
              ["Search", "11"],
              ["Stalls", "16"],
              ["Partners", "9"],
              ["Other", "6"],
              ["Source blank", "12"]
            ]
          }
        ]
      },
      {
        title: "Bookings taken and bookings finished",
        rows: [
          {
            type: "stats",
            label: "Eligible leads that became bookings",
            hint: "Leave out leads that cannot be booked. 40 or more out of 100 is excellent. 30 to 39 is acceptable. 20 to 29 needs improvement. Below 20 is critical.",
            cells: [
              { label: "Eligible leads", example: "55" },
              { label: "Bookings", example: "18" },
              { label: "Out of 100", example: "33, acceptable" }
            ]
          },
          {
            type: "stats",
            label: "Those same bookings finished",
            hint: "Finished means the service met the standard and both payments are in the admin panel.",
            cells: [
              { label: "Finished and settled", example: "11" },
              { label: "Not finished", example: "7" }
            ]
          },
          {
            type: "table",
            label: "By area and service",
            hint: "One row for every area we claim and every category we sell, including zero. Out of 100 is bookings divided by eligible leads. Rows add up to the company totals.",
            columns: ["Area", "Service", "Eligible leads", "Bookings", "Out of 100", "Finished", "Cancelled", "Top reason", "Outbounds", "Became a booking", "Complaints", "Provider did not attend"],
            example: [
              ["Srinagar", "Cleaning", "20", "10", "50", "8", "1", "Not at the address", "40", "10", "0", "0"],
              ["Srinagar", "Plumbing", "8", "2", "25", "1", "0", "—", "12", "2", "1", "0"],
              ["Budgam", "Cleaning", "8", "4", "50", "2", "0", "—", "16", "4", "0", "0"],
              ["Budgam", "Plumbing", "8", "1", "13", "0", "1", "Declined", "18", "1", "0", "1"],
              ["Baramulla", "Cleaning", "6", "0", "0", "0", "1", "No provider could attend", "12", "0", "1", "1"],
              ["Baramulla", "Plumbing", "5", "1", "20", "0", "0", "—", "8", "1", "0", "0"],
              ["Other categories", "All", "0", "0", "—", "0", "0", "—", "0", "0", "0", "0"]
            ]
          },
          {
            type: "table",
            label: "Same cells against last week",
            hint: "Same row order. A cell can fall while the company total rises.",
            columns: ["Area", "Service", "Eligible last week", "Eligible this week", "Bookings last week", "Bookings this week", "Finished last week", "Finished this week"],
            example: [
              ["Srinagar", "Cleaning", "16", "20", "6", "10", "7", "8"],
              ["Srinagar", "Plumbing", "6", "8", "2", "2", "2", "1"],
              ["Budgam", "Cleaning", "10", "8", "5", "4", "2", "2"],
              ["Budgam", "Plumbing", "4", "8", "2", "1", "1", "0"],
              ["Baramulla", "Cleaning", "4", "6", "0", "0", "0", "0"],
              ["Baramulla", "Plumbing", "6", "5", "2", "1", "0", "0"],
              ["Other categories", "All", "0", "0", "0", "0", "0", "0"]
            ]
          },
          {
            type: "table",
            label: "Bookings sent back",
            hint: "One row for each booking still open past the visit, or marked finished while a payment line is blank.",
            columns: ["Booking", "Owner", "Why it came back"],
            example: [
              ["BK-4412", "Imran", "Still open past the visit"],
              ["BK-4388", "Aisha", "Marked finished. Provider settlement blank"],
              ["BK-4401", "Rafia", "Advance is policy blank"]
            ]
          }
        ]
      },
      {
        title: "People, money, and what failed",
        rows: [
          {
            type: "table",
            label: "Future customers",
            hint: "The line is 50 bookings out of every 100 eligible future customers that person was given.",
            columns: ["Executive", "Given", "Booked", "Out of 100"],
            example: [
              ["Aisha", "12", "7", "58"],
              ["Imran", "10", "3", "30"],
              ["Rafia", "8", "5", "63"]
            ]
          },
          {
            type: "table",
            label: "Cancellations",
            hint: "One row for each cancellation, with the true reason.",
            columns: ["Booking", "Reason"],
            example: [
              ["BK-4366", "Customer was not at the address"],
              ["BK-4374", "No provider could attend"]
            ]
          },
          {
            type: "table",
            label: "Four money lines",
            hint: "Do not add a line called revenue.",
            columns: ["Line", "Amount"],
            example: [
              ["Booking advance, as the payment policy says", "₹8,600"],
              ["Customer payment in the panel", "₹86,400"],
              ["Provider settlement in the panel", "₹51,200"],
              ["Cash still open", "₹6,000"]
            ]
          },
          {
            type: "table",
            label: "Advance where the policy amount was blank",
            hint: "One row for each booking where the policy showed no amount.",
            columns: ["Booking", "Owner"],
            example: [
              ["BK-4390", "Aisha"],
              ["BK-4401", "Rafia"]
            ]
          },
          {
            type: "table",
            label: "Cash still open",
            hint: "One row for each amount, with who holds it.",
            columns: ["Booking", "Amount", "Who holds it"],
            example: [
              ["BK-4377", "₹4,200", "Imran"],
              ["BK-4404", "₹1,800", "Aisha"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Customer Experience Manager",
      when: "Week of 22 Sep 2026",
      intro: "This week 18 bookings were taken from 55 eligible leads. That is 33 out of 100, acceptable. Only 11 of those 18 were finished with both payments. The week is a miss until the other 7 are finished or sent back. Do not copy these figures onto another week."
    }
  },
  {
    group: "Each month",
    id: "cxm-month",
    name: "Demand and the four money lines",
    when: "With the monthly close.",
    writer: "Customer Experience Manager",
    writerRole: "cxm",
    to: "Head of Operations.",
    rule: "Show where demand has no provider. Use the same four money lines as the weekly report. This month against last month. Do not add a line called revenue.",
    lines: [
      { field: "Areas and services", hint: "Leads, bookings, finished jobs, and whether there are enough providers." },
      { field: "Four money lines", hint: "This month and last month, then the action for a gap." }
    ],
    sections: [
      {
        title: "Demand",
        rows: [
          { type: "note", label: "Month", hint: "The month you are closing.", example: "September 2026, against August 2026" },
          {
            type: "table",
            label: "Each area and service",
            hint: "One row for every area and every category, including zero. Out of 100 is bookings divided by eligible leads. Eligible on these rows adds to 155. Leads add to 224.",
            columns: ["Area", "Service", "Leads", "Eligible", "Bookings", "Out of 100", "Finished", "Cancelled", "Top reason", "Active providers"],
            example: [
              ["Srinagar", "Cleaning", "90", "62", "32", "52", "24", "3", "Price refused", "14"],
              ["Srinagar", "Plumbing", "30", "21", "10", "48", "6", "1", "Not at the address", "8"],
              ["Budgam", "Cleaning", "36", "25", "12", "48", "6", "2", "Price refused", "9"],
              ["Budgam", "Plumbing", "28", "20", "6", "30", "3", "2", "No provider could attend", "2"],
              ["Baramulla", "Cleaning", "24", "16", "2", "13", "0", "3", "No provider could attend", "0"],
              ["Baramulla", "Plumbing", "16", "11", "4", "36", "2", "1", "Declined", "0"],
              ["Other categories", "All", "0", "0", "0", "—", "0", "0", "—", "9"]
            ]
          },
          {
            type: "table",
            label: "Same cells against August",
            hint: "Same row order. Copy August from last month’s page.",
            columns: ["Area", "Service", "Leads August", "Leads September", "Bookings August", "Bookings September", "Finished August", "Finished September"],
            example: [
              ["Srinagar", "Cleaning", "70", "90", "24", "32", "20", "24"],
              ["Srinagar", "Plumbing", "24", "30", "8", "10", "5", "6"],
              ["Budgam", "Cleaning", "30", "36", "10", "12", "6", "6"],
              ["Budgam", "Plumbing", "22", "28", "6", "6", "3", "3"],
              ["Baramulla", "Cleaning", "28", "24", "2", "2", "0", "0"],
              ["Baramulla", "Plumbing", "16", "16", "2", "4", "2", "2"],
              ["Other categories", "All", "0", "0", "0", "0", "0", "0"]
            ]
          }
        ]
      },
      {
        title: "Money",
        rows: [
          {
            type: "table",
            label: "Four money lines",
            hint: "The same four lines as the weekly report. This month and last month.",
            columns: ["Line", "This month", "Last month"],
            example: [
              ["Booking advance, as the payment policy says", "₹32,400", "₹28,100"],
              ["Customer payment in the panel", "₹3,40,000", "₹2,95,000"],
              ["Provider settlement in the panel", "₹1,98,000", "₹1,76,000"],
              ["Cash still open", "₹6,000", "₹2,400"]
            ]
          },
          {
            type: "table",
            label: "Cash still open at month end",
            hint: "One row for each amount, with who holds it.",
            columns: ["Booking", "Amount", "Who holds it"],
            example: [
              ["BK-4377", "₹4,200", "Imran"],
              ["BK-4404", "₹1,800", "Aisha"]
            ]
          },
          {
            type: "table",
            label: "What needs an action",
            hint: "A gap in providers goes to the Provider Experience Manager. It is not a scolding for the executive.",
            columns: ["Gap", "Send to", "Action"],
            example: [
              ["Budgam plumbers", "Provider Experience Manager", "Add plumbers before more leads are promised"],
              ["Baramulla cleaning", "Provider Experience Manager", "No cleaning provider. Do not sell cleaning there until one is active"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Customer Experience Manager",
      when: "September 2026",
      intro: "September against August. Srinagar can take the work. Budgam and Baramulla cannot. The cash still open is the same two bookings as the weekly page. Do not copy these figures onto another month."
    }
  },
  {
    group: "Each working day",
    id: "pom-daily",
    name: "Daily close",
    when: "Every working day, before you leave.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations.",
    rule: "You assign the provider inbox. Onboard now comes before write down, do later. Then you copy only the stuck rows from Provider Support’s daily close. Do not copy their whole list.",
    lines: [
      { field: "The inbox", hint: "Every provider lead that arrived today, the priority, and the one owner." },
      { field: "The team", hint: "Whether the daily close came in, and the stuck rows copied from it." }
    ],
    sections: [
      {
        title: "The inbox",
        rows: [
          {
            type: "stats",
            label: "Provider leads that arrived today",
            hint: "Count them from the provider inbox.",
            cells: [
              { label: "Arrived", example: "4" },
              { label: "Onboard now", example: "3" },
              { label: "Write down, do later", example: "1" }
            ]
          },
          {
            type: "table",
            label: "Each lead",
            hint: "One row for each lead. One owner. Onboard now is not given to a person who still has an older onboard-now lead waiting.",
            columns: ["Lead", "Provider", "Priority", "Owner"],
            example: [
              ["PL-2204", "New plumber, Baramulla", "Onboard now", "Provider Support"],
              ["PL-2205", "Cleaner, Srinagar", "Onboard now", "Provider Support"],
              ["PL-2206", "Plumber, Budgam", "Onboard now", "Provider Support"],
              ["PL-2207", "Cleaner, outside the area", "Write down, do later", "Provider Support"]
            ]
          },
          {
            type: "stats",
            label: "Still with no owner when you leave",
            hint: "An onboard-now lead with no owner is a miss.",
            cells: [
              { label: "Onboard now with no owner", example: "0" }
            ]
          }
        ]
      },
      {
        title: "The team",
        rows: [
          {
            type: "table",
            label: "Daily closes",
            hint: "Write in or not in. If the close is not in, do not fill their rows yourself.",
            columns: ["Person", "Close"],
            example: [
              ["Provider Support", "In"]
            ]
          },
          {
            type: "table",
            label: "Still open, copied from the close that is in",
            hint: "Copy the stuck rows only.",
            columns: ["Record", "What is stuck"],
            example: [
              ["PL-2204", "Onboard now. Not contacted"],
              ["PL-2190", "Later lead was started while PL-2204 was waiting"],
              ["Shabir Ahmad", "Photo does not match. Not answering. Not activated"],
              ["Gulzar", "Due ₹4,200. Paid ₹4,800. Difference ₹600. No booking id"],
              ["BK-4390", "Farooq has not said yes. Customer Experience was told"],
              ["PL-2188", "Last message is not from Panun Kaergar"],
              ["PL-2204", "No next time"]
            ]
          },
          {
            type: "note",
            label: "Joined the queue",
            hint: "Write no, or the lead ids you worked yourself.",
            example: "No"
          }
        ]
      }
    ],
    example: {
      writer: "Provider Experience Manager",
      when: "Friday 26 Sep 2026",
      intro: "Four provider leads were assigned. Provider Support’s close was in. Shabir Ahmad stays inactive. Gulzar’s payment has no booking id. Do not copy these ids onto your own day."
    }
  },
  {
    group: "Each week",
    id: "pxm-cover",
    name: "Provider performance and coverage",
    when: "With the weekly operations review.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations.",
    rule: "Inactive providers and empty areas belong on the page. A list of only good providers hides the gap. Copy the counts from the records.",
    lines: [
      { field: "Providers", hint: "Active, new, activated, and inactive." },
      { field: "Coverage", hint: "Each area and service: enough, only one, none, or too few." }
    ],
    sections: [
      {
        title: "The week",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "stats",
            label: "Providers",
            hint: "Copy the counts from the provider files.",
            cells: [
              { label: "Active", example: "42" },
              { label: "Onboarded this week", example: "3" },
              { label: "Activated this week", example: "1" },
              { label: "Inactive", example: "7" }
            ]
          }
        ]
      },
      {
        title: "Who is not working",
        rows: [
          {
            type: "table",
            label: "Not answering, not accepting work, or not to be used",
            hint: "One row for each provider. A quiet list means you did not look.",
            columns: ["Provider", "Area and service", "What is wrong", "Next action"],
            example: [
              ["Shabir Ahmad", "Baramulla, plumbing", "Not answering. Photo does not match.", "Stays inactive"]
            ]
          },
          {
            type: "table",
            label: "Training still not finished",
            hint: "One row for each person who was assigned training and has not finished it.",
            columns: ["Provider", "Training", "Status"],
            example: [
              ["Shabir Ahmad", "Briefing", "Not started. File is not active"]
            ]
          }
        ]
      },
      {
        title: "Coverage",
        rows: [
          {
            type: "table",
            label: "Each area and service",
            hint: "Enough, only one, none, or too few. A temporary provider is named as temporary.",
            columns: ["Area", "Service", "Active", "Joined", "Suspended", "Blacklisted", "Provider leads", "Registered", "What it means"],
            example: [
              ["Srinagar", "Cleaning", "14", "1", "0", "0", "3", "1", "Can take the jobs"],
              ["Srinagar", "Plumbing", "8", "0", "0", "0", "1", "0", "Can take the jobs"],
              ["Budgam", "Cleaning", "9", "1", "0", "0", "2", "1", "Can take the jobs"],
              ["Budgam", "Plumbing", "2", "1", "0", "0", "2", "1", "Too few. Leads are waiting"],
              ["Baramulla", "Cleaning", "0", "0", "0", "0", "0", "0", "None. Do not sell cleaning here"],
              ["Baramulla", "Plumbing", "0", "0", "1", "0", "1", "0", "Shabir Ahmad is suspended. Not a real cover"],
              ["Other categories", "All", "9", "0", "0", "0", "0", "0", "9 active providers and no eligible lead. Split this row into the real categories"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Provider Experience Manager",
      when: "Week of 22 Sep 2026",
      intro: "42 providers are active. One was activated this week. Baramulla has no cleaner. The only Baramulla plumber is Shabir Ahmad, and he is not activated. Do not copy these figures onto another week."
    }
  },
  {
    group: "Each week",
    id: "pxm-pay",
    name: "Provider payments",
    when: "On the cycle Accounts and Operations use.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations and Accounts.",
    rule: "One row for each provider. Due, paid, still unpaid, and any difference. A hidden difference is a miss. If there is no booking id, write that.",
    lines: [
      { field: "Each provider", hint: "Due, paid, still unpaid, and any difference, from the records." }
    ],
    sections: [
      {
        title: "The period",
        rows: [
          { type: "note", label: "Dates", hint: "The dates this payment report covers.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "table",
            label: "Each provider",
            hint: "Do not merge two providers into one row. If you cannot open the record, leave the amount blank.",
            columns: ["Provider", "Booking", "Due", "Paid", "Still unpaid", "Difference"],
            example: [
              ["Farooq", "BK-4360", "₹12,000", "₹12,000", "₹0", "None"],
              ["Imtiyaz", "BK-4366", "₹8,500", "₹6,000", "₹2,500", "None"],
              ["Gulzar", "No booking id", "₹4,200", "₹4,800", "₹0", "₹600"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Provider Experience Manager",
      when: "Week of 22 Sep 2026",
      intro: "Farooq is clear. Imtiyaz is still owed ₹2,500 on BK-4366. Gulzar was paid ₹600 more than the record, and the row has no booking id. Do not copy these figures onto another week."
    }
  },
  {
    group: "Each working day",
    id: "hoo-daily",
    name: "Daily close",
    when: "Every working day, before you leave.",
    writer: "Head of Operations",
    writerRole: "hoo",
    to: "The two managers. The CEO only if a decision sits above Operations.",
    rule: "Read both manager closes. If one is missing, that is the first line. Do not rewrite their pages. Copy only what is still stuck, and the action you set.",
    lines: [
      { field: "Both closes", hint: "Customer Experience Manager and Provider Experience Manager: in, or not in." },
      { field: "What you did with the stuck rows", hint: "The rows you copied, and the action, the owner, and the date." }
    ],
    sections: [
      {
        title: "Both closes",
        rows: [
          {
            type: "table",
            label: "Did the daily close come in",
            hint: "In or not in. A missing close is not filled in by you.",
            columns: ["Manager", "Close"],
            example: [
              ["Customer Experience Manager", "In"],
              ["Provider Experience Manager", "In"]
            ]
          },
          {
            type: "note",
            label: "A manager away with no deputy",
            hint: "If a manager is away and no deputy was named, write the one name you put in the panel this morning. If both managers are in, write both in.",
            example: "Both in. No deputy was needed."
          }
        ]
      },
      {
        title: "Stuck rows you are holding",
        rows: [
          {
            type: "table",
            label: "Copied from the closes that are in",
            hint: "Do not copy a whole list. Copy the row that still needs a decision or is still open tonight.",
            columns: ["From", "Record", "What is stuck"],
            example: [
              ["Customer Experience", "Rafia", "Her daily close was not in"],
              ["Customer Experience", "BK-4404", "Cash ₹1,800. Aisha is holding it. Both payment lines blank"],
              ["Customer Experience", "BK-4377", "Cash ₹4,200. Imran is holding it"],
              ["Customer Experience", "BK-4390", "Visit today. Provider has not confirmed"],
              ["Provider Experience", "Shabir Ahmad", "Baramulla plumber. Photo does not match. Not activated"],
              ["Provider Experience", "Gulzar", "Paid ₹4,800 against ₹4,200 due. Difference ₹600. No booking id"]
            ]
          },
          {
            type: "table",
            label: "Action you set today",
            hint: "One owner and a date. An action with no owner is not set.",
            columns: ["Action", "Owner", "Due"],
            example: [
              ["Get Rafia’s close", "Customer Experience Manager", "Saturday 27 Sep"],
              ["Do not activate Shabir Ahmad", "Provider Experience Manager", "Already stopped"],
              ["Find Gulzar’s booking id", "Provider Experience Manager and Accounts", "Monday 29 Sep"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Head of Operations",
      when: "Friday 26 Sep 2026",
      intro: "Both manager closes were in. Rafia’s own close was not, so her rows are not treated as facts. Shabir Ahmad stays inactive. Gulzar’s extra ₹600 has no booking id. Do not copy these ids onto your own day."
    }
  },
  {
    group: "Each week",
    id: "hoo-weekly",
    name: "Weekly operational review",
    when: "The same time each week, after both manager reports are in.",
    writer: "Head of Operations",
    writerRole: "hoo",
    to: "The two managers, and the CEO when a decision sits above Operations.",
    rule: "If a manager report is missing, that is the first line. Do not rewrite their report. Put bookings taken next to bookings finished. Put demand next to coverage. Keep the four money lines separate.",
    lines: [
      { field: "Did the reports arrive", hint: "Customer report, provider coverage, and provider payments." },
      { field: "The week in one view", hint: "Bookings taken and finished, demand against coverage, the four money lines, and the actions." }
    ],
    sections: [
      {
        title: "The reports",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "table",
            label: "Did each report arrive",
            hint: "In, not in, or sent back. You do not patch a missing report.",
            columns: ["Report", "Status"],
            example: [
              ["Customer operations report", "In. Sent back on the unfinished bookings"],
              ["Provider performance and coverage", "In"],
              ["Provider payments", "In. Gulzar’s row has no booking id"]
            ]
          }
        ]
      },
      {
        title: "Bookings and coverage",
        rows: [
          {
            type: "stats",
            label: "Copied from the customer report",
            hint: "Do not type a new count. Copy it.",
            cells: [
              { label: "Eligible leads", example: "55" },
              { label: "Bookings taken", example: "18" },
              { label: "Out of 100", example: "33, acceptable" },
              { label: "Finished and settled", example: "11" }
            ]
          },
          {
            type: "table",
            label: "Each area and service",
            hint: "Copy every cell, including zeros. Out of 100, cancellations, and outbounds are on the customer report. Providers are on the roster. Money is on the finance report. This row is the same order.",
            columns: ["Area", "Service", "Eligible", "Bookings", "Out of 100", "Finished", "Cancelled", "Active providers", "Bookings last week"],
            example: [
              ["Srinagar", "Cleaning", "20", "10", "50", "8", "1", "14", "6"],
              ["Srinagar", "Plumbing", "8", "2", "25", "1", "0", "8", "2"],
              ["Budgam", "Cleaning", "8", "4", "50", "2", "0", "9", "5"],
              ["Budgam", "Plumbing", "8", "1", "13", "0", "1", "2", "2"],
              ["Baramulla", "Cleaning", "6", "0", "0", "0", "1", "0", "0"],
              ["Baramulla", "Plumbing", "5", "1", "20", "0", "0", "0", "2"],
              ["Other categories", "All", "0", "0", "—", "0", "0", "9", "0"]
            ]
          }
        ]
      },
      {
        title: "Money and actions",
        rows: [
          {
            type: "table",
            label: "Four money lines",
            hint: "Copy them. Do not add them into one figure. Do not add a line called revenue.",
            columns: ["Line", "Amount"],
            example: [
              ["Booking advance, as the payment policy says", "₹8,600"],
              ["Customer payment in the panel", "₹86,400"],
              ["Provider settlement in the panel", "₹51,200"],
              ["Cash still open", "₹6,000"]
            ]
          },
          {
            type: "table",
            label: "Actions from last week",
            hint: "Done or not done, and the date.",
            columns: ["Action", "Owner", "Result"],
            example: [
              ["Name a Baramulla cleaner", "Provider Experience Manager", "Not done"]
            ]
          },
          {
            type: "table",
            label: "New actions",
            hint: "One owner and a due date.",
            columns: ["Action", "Owner", "Due"],
            example: [
              ["Finish or send back the 7 bookings that are not settled", "Customer Experience Manager", "Friday 3 Oct"],
              ["Find Gulzar’s booking id before the payment is closed", "Provider Experience Manager", "Monday 29 Sep"],
              ["Do not sell cleaning in Baramulla until a provider is active", "Provider Experience Manager", "This week"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Head of Operations",
      when: "Week of 22 Sep 2026",
      intro: "All three reports came in. 18 bookings were taken and 11 were finished with both payments, so the customer report was sent back on the other 7. Baramulla still has no cleaner. Do not copy these figures onto another week."
    }
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
    id: "hoo-month",
    name: "Management operational report",
    when: "After the monthly review.",
    writer: "Head of Operations",
    writerRole: "hoo",
    to: "CEO.",
    rule: "The month is for causes, not only totals. Copy the customer month and the provider month. Keep the four money lines separate. Name what repeated, and who owns next month.",
    lines: [
      { field: "This month against last month", hint: "Leads, bookings, finished jobs, and the four money lines." },
      { field: "Causes and next month", hint: "Coverage gaps, repeated problems, and the few priorities." }
    ],
    sections: [
      {
        title: "This month against last month",
        rows: [
          { type: "note", label: "Month", hint: "This month and the previous month.", example: "September 2026, against August 2026" },
          {
            type: "table",
            label: "Copied from the customer month page",
            hint: "Do not type a second set of figures.",
            columns: ["Line", "September", "August"],
            example: [
              ["Leads", "224", "190"],
              ["Bookings", "66", "52"],
              ["Finished with both payments", "41", "36"]
            ]
          },
          {
            type: "table",
            label: "Four money lines",
            hint: "The same four lines as the weekly page. Do not add a line called revenue.",
            columns: ["Line", "September", "August"],
            example: [
              ["Booking advance, as the payment policy says", "₹32,400", "₹28,100"],
              ["Customer payment in the panel", "₹3,40,000", "₹2,95,000"],
              ["Provider settlement in the panel", "₹1,98,000", "₹1,76,000"],
              ["Cash still open", "₹6,000", "₹2,400"]
            ]
          },
          {
            type: "table",
            label: "Cash still open at month end",
            hint: "Copy the rows. One booking on each row.",
            columns: ["Booking", "Amount", "Who holds it"],
            example: [
              ["BK-4377", "₹4,200", "Imran"],
              ["BK-4404", "₹1,800", "Aisha"]
            ]
          }
        ]
      },
      {
        title: "Causes and next month",
        rows: [
          {
            type: "table",
            label: "Each area and service against August",
            hint: "Copy the customer month page. Same row order. A cell can stay empty while the company total rises.",
            columns: ["Area", "Service", "Leads August", "Leads September", "Bookings August", "Bookings September", "Finished September", "Active providers"],
            example: [
              ["Srinagar", "Cleaning", "70", "90", "24", "32", "24", "14"],
              ["Srinagar", "Plumbing", "24", "30", "8", "10", "6", "8"],
              ["Budgam", "Cleaning", "30", "36", "10", "12", "6", "9"],
              ["Budgam", "Plumbing", "22", "28", "6", "6", "3", "2"],
              ["Baramulla", "Cleaning", "28", "24", "2", "2", "0", "0"],
              ["Baramulla", "Plumbing", "16", "16", "2", "4", "2", "0"],
              ["Other categories", "All", "0", "0", "0", "0", "0", "9"]
            ]
          },
          {
            type: "table",
            label: "Repeated problems",
            hint: "The cause, the owner, and whether it is still open.",
            columns: ["Problem", "Cause", "Owner", "Still open"],
            example: [
              ["Bookings taken but not finished", "Payment lines left blank", "Customer Experience Manager", "Yes. 7 in the last week"],
              ["A payment with no booking", "The row was closed without an id", "Provider Experience Manager", "Yes. Gulzar, ₹600"]
            ]
          },
          {
            type: "table",
            label: "Priorities for next month",
            hint: "A few only. Each one has one owner.",
            columns: ["Priority", "Owner"],
            example: [
              ["Do not sell cleaning in Baramulla until a provider is active", "Provider Experience Manager"],
              ["Add plumbers in Budgam", "Provider Experience Manager"],
              ["Close every booking that still has a blank payment line", "Customer Experience Manager"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Head of Operations",
      when: "September 2026",
      intro: "September against August. More bookings were taken. Baramulla cleaning is the same gap as last month. The two cash rows are the same bookings as the weekly page. Do not copy these figures onto another month."
    }
  },
  {
    group: "Each month",
    id: "mi-month",
    name: "Monthly decision pack",
    when: "The last week of the month, before the Growth plan is signed.",
    writer: "Market Intelligence Manager",
    writerRole: "mim",
    to: "Head of Growth, before they sign next month’s Growth plan. Marketing gets only the list of what they must not guess.",
    rule: "This pack is what next month’s spend is signed against. A mood is not a pack. No idea row may stay as “later”.",
    lines: [
      { field: "Month", hint: "Write the month Head of Growth is about to sign." },
      { field: "Demand, these four weeks against the four before", hint: "For each service and town: enquiries and bookings in both periods, and up, flat, or down. Copy both periods from the admin panel." },
      { field: "Competitor changes that should change the plan", hint: "What changed, the source, and the date. Leave out rumours. If none should change the plan, write none." },
      { field: "Each open idea", hint: "One decision: keep, stop, or research with a date. Copy the idea from the register. A row with no decision is not in the pack." },
      { field: "What Marketing must not guess", hint: "Services or towns that have no demand count and no competitor source. Head of Growth does not put those on the signed plan." }
    ]
  },
  {
    group: "The day something moves",
    id: "osd-launch",
    name: "Launch checklist",
    when: "Before Head of Growth says Marketing may talk about a service.",
    writer: "Offer and Service Development Manager",
    writerRole: "osd",
    to: "Head of Growth. Marketing does not receive this until Head of Growth has written yes.",
    rule: "The service does not launch until the sheet, the price, and Operations’ yes are all dated. A spoken yes is not a signature.",
    lines: [
      { field: "Service", hint: "The name as it will appear on the live list." },
      { field: "Sheet a stranger can follow", hint: "Yes only if the sheet has what is in, what is out, the job steps, and the quality bar. Otherwise list what is missing." },
      { field: "Finance signed the price", hint: "The date on the price file, or “not signed”. Do not write a price of your own." },
      { field: "Operations can finish the first jobs", hint: "The date Operations wrote yes, and the towns they named. If the yes is missing, write “yes missing”." },
      { field: "Claims Marketing may use", hint: "The sentences copied from the sheet. If Head of Growth has not signed, write “not released”." },
      { field: "Head of Growth yes", hint: "The date they signed, or “not signed”. Marketing does not talk about the service until this date exists." }
    ]
  },
  {
    group: "The day something moves",
    id: "dig-daily",
    name: "Daily digital note",
    when: "Any working day spend jumped, tracking broke, something was paused, or files went live.",
    writer: "Digital Marketing Manager",
    writerRole: "hom",
    to: "Marketing Manager, the same day something moved.",
    rule: "This note catches a leak today. It is not the weekly pack. If Operations did not write how the person found us, that enquiry is not counted as yours.",
    lines: [
      { field: "Date", hint: "Today’s date." },
      { field: "Spend today", hint: "Paid-ads spend today, split by Meta, Google, and any other paid place. Do not write one lump called digital. Copy the figures from the ad accounts." },
      { field: "Spend against the signed plan", hint: "Ads spend so far this month, next to the ads amount on the signed plan, and over or under." },
      { field: "New enquiries Operations marked", hint: "Counts only where Operations wrote the source: Meta, Google, search, social, or other. Do not guess the source from the phone number." },
      { field: "Enquiries with no source", hint: "How many arrived that Operations left blank. Those are not your enquiries until the answer is written." },
      { field: "Pauses", hint: "Campaign name and why you paused it, or none." },
      { field: "Tracking", hint: "Working, or name what is broken: pixel, form, WhatsApp, or app." },
      { field: "Library", hint: "Each file that went live today, with its library name. A file with no library name did not happen." },
      { field: "Content Maker seat", hint: "Yes if that seat is empty and you did the work, and name the files you filed. Otherwise no." }
    ]
  },
  {
    group: "Each week",
    id: "dig-weekly",
    name: "Weekly digital pack",
    when: "The day before the weekly marketing report.",
    writer: "Digital Marketing Manager",
    writerRole: "hom",
    to: "Marketing Manager. This is the digital page of the weekly marketing report.",
    rule: "Meta, Google, search, social, and other each have their own line. A lump called digital hides the place that spent and sent nobody. Include what failed.",
    lines: [
      { field: "Week dates", hint: "Write the Monday and the Sunday." },
      { field: "Each online place", hint: "One line each for Meta, Google, search, social, and other. On each line: spend, enquiries Operations sourced to that place, cost per enquiry, and how many of those enquiries have a written source. Copy spend from the ad accounts. Copy enquiries from Operations’ answers. Do not guess a source from a phone number." },
      { field: "Spend against the signed ads line", hint: "Spent this week, spent so far this month, the signed ads amount, and over or under." },
      { field: "Bookings from digital enquiries", hint: "Count Customer Experience closed, split by the same places. If they have not closed the week, write “booking count not in yet”." },
      { field: "What failed", hint: "The place or campaign that spent and brought no enquiry, or brought enquiries with no source. A pack with no fail line is not finished." },
      { field: "Files that went live", hint: "Name, service, town, and library name. A file with no library name did not happen." },
      { field: "One request for next week", hint: "One request: more money, a new file, or pause a town. Name the date. Not ten wishes." }
    ]
  },
  {
    group: "The day something moves",
    id: "mkt-daily",
    name: "Daily marketing note",
    when: "Any working day spend moved, a source is missing, or an ad, search page, or stall was paused.",
    writer: "Marketing Manager",
    writerRole: "mkm",
    to: "The marketing file. Send it to Head of Growth the same day only if month-to-date spend will pass the signed amount, or a live sentence is not in the kit.",
    rule: "Ads and stalls stay separate. The aim is an empty list of enquiries Operations has not sourced.",
    lines: [
      { field: "Date", hint: "Today’s date." },
      { field: "Ads money against the plan", hint: "Spent today, spent so far this month, the signed ads amount, and over or under. Copy today’s spend from the daily digital note." },
      { field: "Stall money against the plan", hint: "Spent today, spent so far this month, the signed stall amount, and over or under. Copy today’s spend from Field." },
      { field: "New enquiries", hint: "Three counts for today: paid ads, search, stalls. Copy them from Operations’ written source. Do not mix search into ads." },
      { field: "Enquiries still missing a source", hint: "Each open one: lead id or phone, and the person in Operations who will ask today. An empty list is the finished day." },
      { field: "Pauses", hint: "What was paused, why, and what is still allowed to run. If nothing was paused, write none." },
      { field: "Empty Digital or Field seat", hint: "Name the seat you are doing today because it is empty. If both are filled, write both filled." }
    ]
  },
  {
    group: "Each working day",
    id: "cxe-daily",
    name: "Daily close",
    when: "Every working day, before you leave.",
    writer: "Customer Experience Executive",
    writerRole: "cxe",
    to: "Customer Experience Manager.",
    rule: "Submit this before you leave. The count is every lead still on your name. Each table is only what you did today, or what is still stuck when you stop.",
    lines: [
      { field: "What I did today", hint: "New leads, follow-ups, a service provider returned, bookings created, and jobs finished." },
      { field: "Still on my name when I leave", hint: "Visits today or the next working day, cash, a last message that is not from Panun Kaergar, a lead with no next time, and whether you are the deputy." }
    ],
    sections: [
      {
        title: "The list",
        rows: [
          { type: "stat", label: "Open leads on your name", hint: "The count only. Do not list all of them.", example: "40" }
        ]
      },
      {
        title: "What I did today",
        rows: [
          {
            type: "stats",
            label: "Leads put on your name today",
            hint: "Copy the counts from the leads the manager put on you today.",
            cells: [
              { label: "Put on my name", example: "6" },
              { label: "Contacted", example: "4" },
              { label: "Not contacted", example: "2" }
            ]
          },
          {
            type: "table",
            label: "Not contacted, and any lead worked out of order",
            hint: "One row for each lead you did not contact, and one row if you worked a cold lead while a hot one was waiting. If the table is empty, write none in the first row.",
            columns: ["Lead", "Priority", "What happened"],
            example: [
              ["LD-8841", "Hot", "Not contacted"],
              ["LD-8844", "Warm", "Not contacted"],
              ["LD-8830", "Cold", "Called while hot LD-8841 was still waiting"]
            ]
          },
          {
            type: "stats",
            label: "Follow-ups due today",
            hint: "Copy the counts from the follow-ups due on your name today.",
            cells: [
              { label: "Due", example: "11" },
              { label: "Done", example: "9" },
              { label: "Missed", example: "2" }
            ]
          },
          {
            type: "table",
            label: "Follow-ups missed",
            hint: "One row for each miss. If every follow-up was done, write none in the first row.",
            columns: ["Lead", "What was due"],
            example: [
              ["LD-8790", "Follow-up due today, not done"],
              ["LD-8766", "Follow-up due today, not done"]
            ]
          },
          {
            type: "table",
            label: "Service provider returned",
            hint: "One row for each service provider put on your name. You return them. You do not onboard them. If there was none, write none in the first row.",
            columns: ["Lead", "Returned", "Onboarding"],
            example: [
              ["LD-8810", "Yes, today", "Not started"]
            ]
          },
          {
            type: "table",
            label: "Bookings created",
            hint: "One row for each booking you created today. Advance is the payment-policy amount, or policy blank. If you created none, write none in the first row.",
            columns: ["Booking", "Job and area", "Advance", "Provider"],
            example: [
              ["BK-4410", "Cleaning, Srinagar", "₹500", "Farooq, yes"],
              ["BK-4411", "Plumbing, Budgam", "Policy blank", "No yes yet. Stays open"]
            ]
          },
          {
            type: "table",
            label: "Jobs finished today",
            hint: "One row for each job whose service finished today. If either payment line is blank, the booking stays open. If none finished, write none in the first row.",
            columns: ["Booking", "Customer payment", "Provider settlement"],
            example: [
              ["BK-4371", "Written", "Written"],
              ["BK-4404", "Blank. ₹1,800 cash", "Blank. Stays open"]
            ]
          }
        ]
      },
      {
        title: "Still on my name when I leave",
        rows: [
          {
            type: "table",
            label: "Visits today or the next working day",
            hint: "Only visits that start today or the next working day. One row each. If none start that soon, write none in the first row.",
            columns: ["Booking", "When", "Customer", "Provider", "Same charge"],
            example: [
              ["BK-4390", "Today", "Yes", "No", "Yes"],
              ["BK-4384", "Sat 27 Sep", "Yes", "Yes", "Yes"]
            ]
          },
          {
            type: "table",
            label: "Cash still held",
            hint: "One row for each amount still held. If you are holding nothing, write none in the first row.",
            columns: ["Booking", "Amount", "Who holds it"],
            example: [
              ["BK-4404", "₹1,800", "Aisha"]
            ]
          },
          {
            type: "table",
            label: "Last message is not from Panun Kaergar",
            hint: "One row for each open lead whose last message is not from Panun Kaergar. If every last message is from Panun Kaergar, write none in the first row.",
            columns: ["Lead"],
            example: [
              ["LD-8772"],
              ["LD-8801"]
            ]
          },
          {
            type: "table",
            label: "No next time",
            hint: "One row for each open lead with no next time in the admin panel. If every lead has a next time, write none in the first row.",
            columns: ["Lead"],
            example: [
              ["LD-8755"],
              ["LD-8841"]
            ]
          },
          {
            type: "note",
            label: "Deputy",
            hint: "Write “Not the deputy”, unless you are the named deputy today. Then list who you assigned.",
            example: "Not the deputy"
          }
        ]
      }
    ],
    example: {
      writer: "Aisha",
      when: "Friday 26 Sep 2026",
      intro: "One finished day. 40 leads stayed on her name. The tables are what she did, and what was still stuck when she left. Do not copy these ids onto your own day."
    }
  },
  {
    group: "Each working day",
    id: "poe-daily",
    name: "Daily close",
    when: "Every working day, before you leave.",
    writer: "Provider Support",
    writerRole: "poe",
    to: "Provider Experience Manager.",
    rule: "The Provider Experience Manager assigns the inbox. You write only the provider leads that already have your name. Write the count of the whole list. Write a row only for what you did today, or for what is stuck. Do not onboard a later lead while an onboard-now lead is waiting. Do not mark a provider active if a document is missing.",
    lines: [
      { field: "What I did today", hint: "New leads, follow-ups, onboarding, and booking confirmations." },
      { field: "Still stuck when I leave", hint: "A provider you cannot activate, a payment that does not match, a last message that is not from Panun Kaergar, and a lead with no next time." }
    ],
    sections: [
      {
        title: "The list",
        rows: [
          { type: "stat", label: "Open provider leads on your name", hint: "The count only. Do not list all of them.", example: "18" }
        ]
      },
      {
        title: "What I did today",
        rows: [
          {
            type: "stats",
            label: "Leads put on your name today",
            hint: "Onboard now comes before write down, do later.",
            cells: [
              { label: "Put on my name", example: "4" },
              { label: "Onboard now", example: "3" },
              { label: "Write down, do later", example: "1" },
              { label: "Contacted", example: "3" }
            ]
          },
          {
            type: "table",
            label: "Not contacted, and any lead worked out of order",
            hint: "One row for each lead you did not contact. One row if you onboarded a later lead while an onboard-now lead was waiting.",
            columns: ["Lead", "Priority", "What happened"],
            example: [
              ["PL-2204", "Onboard now", "Not contacted"],
              ["PL-2190", "Write down, do later", "I started this while onboard-now PL-2204 was waiting"]
            ]
          },
          {
            type: "stats",
            label: "Follow-ups due today",
            hint: "Copy the counts from the follow-ups due on your name today.",
            cells: [
              { label: "Due", example: "6" },
              { label: "Done", example: "5" },
              { label: "Missed", example: "1" }
            ]
          },
          {
            type: "table",
            label: "Follow-ups missed",
            hint: "One row for each miss.",
            columns: ["Provider", "What was due"],
            example: [
              ["Shabir Ahmad", "Document chase due today, not done"]
            ]
          },
          {
            type: "table",
            label: "Onboarding that moved today",
            hint: "One row for each provider whose file moved. A provider is not active until documents, agreement, briefing, and training are done.",
            columns: ["Provider", "Area and service", "Step today", "Still missing"],
            example: [
              ["Shabir Ahmad", "Baramulla, plumbing", "Photo checked", "Photo does not match the person. Not activated"]
            ]
          },
          {
            type: "table",
            label: "Bookings that needed a provider yes",
            hint: "One row for each booking. A guess is not a yes. Tell Customer Experience the same day.",
            columns: ["Booking", "Provider", "Yes or no", "Told Customer Experience"],
            example: [
              ["BK-4390", "Farooq", "No", "Yes. Told Aisha"],
              ["BK-4384", "Imtiyaz", "Yes", "Yes. Told Aisha"]
            ]
          }
        ]
      },
      {
        title: "Still stuck when I leave",
        rows: [
          {
            type: "table",
            label: "Cannot activate",
            hint: "A missing document, a confirmation that looks false, or any reason you stopped. If there is none, write none in the first row.",
            columns: ["Provider", "What is wrong", "Next action"],
            example: [
              ["Shabir Ahmad", "False-looking photo. Not answering.", "Do not activate. Provider Experience Manager decides the next step"]
            ]
          },
          {
            type: "table",
            label: "Payment that does not match",
            hint: "One row for each difference. Do not pick the number that makes the file look tidy.",
            columns: ["Provider", "Due", "Paid", "Difference", "Booking"],
            example: [
              ["Gulzar", "₹4,200", "₹4,800", "₹600", "No booking id"]
            ]
          },
          {
            type: "table",
            label: "Last message is not from Panun Kaergar",
            hint: "One row for each open lead whose last message is not from Panun Kaergar.",
            columns: ["Lead"],
            example: [
              ["PL-2188"]
            ]
          },
          {
            type: "table",
            label: "No next time",
            hint: "One row for each open lead with no next time in the admin panel.",
            columns: ["Lead"],
            example: [
              ["PL-2204"]
            ]
          },
          {
            type: "note",
            label: "Deputy",
            hint: "Write “Not the deputy”, unless you are the named deputy today. Then list who you assigned.",
            example: "Not the deputy"
          }
        ]
      }
    ],
    example: {
      writer: "Provider Support",
      when: "Friday 26 Sep 2026",
      intro: "18 provider leads stayed on this name. Shabir Ahmad was not activated. Gulzar’s payment does not match, and it has no booking id. Do not copy these ids onto your own day."
    }
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
  },
  {
    group: "Each week",
    id: "cxm-leads",
    name: "Lead and booking report",
    when: "The day the Head of Operations reviews the week.",
    writer: "Customer Experience Manager",
    writerRole: "cxm",
    to: "Head of Operations.",
    rule: "This is the path from a lead to a booking, and from a booking to a finished job. Split it by lead type, by area, and by service. Copy the counts from the admin panel.",
    lines: [
      { field: "The funnel", hint: "Leads, bookings, cancellations and the reason, and outbounds." },
      { field: "Area, service, and ticket", hint: "Where the bookings came from, and which ones are high or low in value." }
    ],
    sections: [
      {
        title: "From lead to booking",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "stats",
            label: "All customer leads",
            hint: "Provider leads are not on this page.",
            cells: [
              { label: "Leads", example: "80" },
              { label: "Can be booked", example: "55" },
              { label: "Bookings", example: "18" },
              { label: "Finished", example: "11" }
            ]
          },
          {
            type: "table",
            label: "Each lead type",
            hint: "One row for each type. Write the true reason for each cancellation.",
            columns: ["Lead type", "Leads", "Bookings", "Cancelled", "Why"],
            example: [
              ["Customer", "46", "14", "2", "Not at the address. No provider could attend"],
              ["Future customer", "29", "4", "1", "Declined"],
              ["Still unknown", "5", "0", "0", "Not classified yet"]
            ]
          },
          {
            type: "table",
            label: "Outbounds",
            hint: "An outbound is a call or message Panun Kaergar started. Count how many, and how many became a booking.",
            columns: ["Executive", "Outbounds", "Became a booking"],
            example: [
              ["Aisha", "48", "10"],
              ["Imran", "36", "5"],
              ["Rafia", "22", "3"]
            ]
          }
        ]
      },
      {
        title: "Area, service, and ticket",
        rows: [
          {
            type: "table",
            label: "Each area and service",
            hint: "Same row order as the customer operations report. Copy it. Out of 100 uses eligible leads.",
            columns: ["Area", "Service", "Eligible leads", "Bookings", "Out of 100", "Finished", "Cancelled", "Top reason", "Outbounds", "Became a booking"],
            example: [
              ["Srinagar", "Cleaning", "20", "10", "50", "8", "1", "Not at the address", "40", "10"],
              ["Srinagar", "Plumbing", "8", "2", "25", "1", "0", "—", "12", "2"],
              ["Budgam", "Cleaning", "8", "4", "50", "2", "0", "—", "16", "4"],
              ["Budgam", "Plumbing", "8", "1", "13", "0", "1", "Declined", "18", "1"],
              ["Baramulla", "Cleaning", "6", "0", "0", "0", "1", "No provider could attend", "12", "0"],
              ["Baramulla", "Plumbing", "5", "1", "20", "0", "0", "—", "8", "1"],
              ["Other categories", "All", "0", "0", "—", "0", "0", "—", "0", "0"]
            ]
          },
          {
            type: "table",
            label: "Ticket value",
            hint: "Average is the customer payment on finished bookings in that cell. Customer payment on this row is everything received, including jobs not yet finished. The customer-payment column adds to ₹86,400.",
            columns: ["Area", "Service", "Finished", "Average on finished jobs", "High or low", "Customer payment"],
            example: [
              ["Srinagar", "Cleaning", "8", "₹4,000", "Middle", "₹48,000"],
              ["Srinagar", "Plumbing", "1", "₹11,000", "High", "₹18,000"],
              ["Budgam", "Cleaning", "2", "₹3,200", "Low", "₹9,600"],
              ["Budgam", "Plumbing", "0", "—", "No finished job", "₹4,800"],
              ["Baramulla", "Cleaning", "0", "—", "No booking", "₹0"],
              ["Baramulla", "Plumbing", "0", "—", "Not finished", "₹6,000"],
              ["Other categories", "All", "0", "—", "No work", "₹0"]
            ]
          },
          {
            type: "table",
            label: "Same cells against last week",
            hint: "Same row order.",
            columns: ["Area", "Service", "Bookings last week", "Bookings this week", "Finished last week", "Finished this week"],
            example: [
              ["Srinagar", "Cleaning", "6", "10", "7", "8"],
              ["Srinagar", "Plumbing", "2", "2", "2", "1"],
              ["Budgam", "Cleaning", "5", "4", "2", "2"],
              ["Budgam", "Plumbing", "2", "1", "1", "0"],
              ["Baramulla", "Cleaning", "0", "0", "0", "0"],
              ["Baramulla", "Plumbing", "2", "1", "0", "0"],
              ["Other categories", "All", "0", "0", "0", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Customer Experience Manager",
      when: "Week of 22 Sep 2026",
      intro: "80 leads, 18 bookings, 11 finished. Srinagar cleaning carried the week. Baramulla cleaning had leads and no booking. Srinagar plumbing is the high ticket. Budgam cleaning is the low ticket. Do not copy these figures onto another week."
    }
  },
  {
    group: "Each month",
    id: "cxm-leads-month",
    name: "Lead and booking report",
    when: "With the monthly close.",
    writer: "Customer Experience Manager",
    writerRole: "cxm",
    to: "Head of Operations.",
    rule: "The same funnel as the week, for the whole month. Add the weekly pages. Do not type a new set of figures.",
    lines: [
      { field: "The month funnel", hint: "Lead type, area, service, and ticket value." }
    ],
    sections: [
      {
        title: "September",
        rows: [
          { type: "note", label: "Month", hint: "The month you are closing.", example: "September 2026" },
          {
            type: "table",
            label: "Each lead type",
            hint: "Add the weekly rows.",
            columns: ["Lead type", "Leads", "Bookings", "Cancelled", "Why"],
            example: [
              ["Customer", "140", "52", "8", "Not at the address. No provider. Price refused"],
              ["Future customer", "70", "14", "4", "Declined. No answer after three outbounds"],
              ["Still unknown", "14", "0", "0", "Never classified"]
            ]
          },
          {
            type: "table",
            label: "Each area and service",
            hint: "Leads, bookings, finished jobs.",
            columns: ["Area", "Service", "Leads", "Eligible", "Bookings", "Out of 100", "Finished", "Cancelled", "Top reason", "Outbounds", "Became a booking"],
            example: [
              ["Srinagar", "Cleaning", "90", "62", "32", "52", "24", "3", "Price refused", "90", "32"],
              ["Srinagar", "Plumbing", "30", "21", "10", "48", "6", "1", "Not at the address", "28", "10"],
              ["Budgam", "Cleaning", "36", "25", "12", "48", "6", "2", "Price refused", "36", "12"],
              ["Budgam", "Plumbing", "28", "20", "6", "30", "3", "2", "No provider could attend", "40", "6"],
              ["Baramulla", "Cleaning", "24", "16", "2", "13", "0", "3", "No provider could attend", "30", "2"],
              ["Baramulla", "Plumbing", "16", "11", "4", "36", "2", "1", "Declined", "22", "4"],
              ["Other categories", "All", "0", "0", "0", "—", "0", "0", "—", "0", "0"]
            ]
          },
          {
            type: "table",
            label: "Ticket value",
            hint: "Average is on finished jobs in that cell. Customer payment adds to ₹3,40,000.",
            columns: ["Area", "Service", "Finished", "Average on finished jobs", "High or low", "Customer payment"],
            example: [
              ["Srinagar", "Cleaning", "24", "₹4,200", "Middle", "₹1,60,000"],
              ["Srinagar", "Plumbing", "6", "₹11,400", "High", "₹70,000"],
              ["Budgam", "Cleaning", "6", "₹3,400", "Low", "₹42,000"],
              ["Budgam", "Plumbing", "3", "₹9,600", "High", "₹38,000"],
              ["Baramulla", "Cleaning", "0", "—", "No finished job", "₹8,000"],
              ["Baramulla", "Plumbing", "2", "₹8,800", "Middle", "₹22,000"],
              ["Other categories", "All", "0", "—", "No work", "₹0"]
            ]
          },
          {
            type: "table",
            label: "Same cells against August",
            hint: "Same row order.",
            columns: ["Area", "Service", "Bookings August", "Bookings September", "Finished August", "Finished September"],
            example: [
              ["Srinagar", "Cleaning", "24", "32", "20", "24"],
              ["Srinagar", "Plumbing", "8", "10", "5", "6"],
              ["Budgam", "Cleaning", "10", "12", "6", "6"],
              ["Budgam", "Plumbing", "6", "6", "3", "3"],
              ["Baramulla", "Cleaning", "2", "2", "0", "0"],
              ["Baramulla", "Plumbing", "2", "4", "2", "2"],
              ["Other categories", "All", "0", "0", "0", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Customer Experience Manager",
      when: "September 2026",
      intro: "224 leads and 66 bookings. Plumbing pays more per job. Cleaning fills the week. Baramulla cleaning still barely books. Do not copy these figures onto another month."
    }
  },
  {
    group: "Each week",
    id: "cxm-staff",
    name: "Customer team report",
    when: "The day the Head of Operations reviews the week.",
    writer: "Customer Experience Manager",
    writerRole: "cxm",
    to: "Head of Operations.",
    rule: "One row for each person, then one row for each person in each area and category. The cell rows add up to that person’s totals, and to the company grid. Copy absence from HR. If HR has not written it, write not in.",
    lines: [
      { field: "Each person", hint: "Company totals, then the same person in each area and category." }
    ],
    sections: [
      {
        title: "Each person",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "table",
            label: "Company total for each person",
            hint: "Out of 100 is bookings divided by eligible leads on that person’s name.",
            columns: ["Name", "Open leads", "Eligible leads", "Bookings", "Out of 100", "Complaints", "Missed follow-ups", "Uninformed leave", "Days absent"],
            example: [
              ["Aisha", "40", "22", "10", "45", "1", "2", "0", "0"],
              ["Imran", "28", "20", "5", "25", "0", "4", "0", "0"],
              ["Rafia", "18", "13", "3", "23", "1", "1", "0", "Not in"]
            ]
          },
          {
            type: "table",
            label: "Each person in each area and service",
            hint: "Same cells as the company grid. These rows add up to the person table and to the company grid.",
            columns: ["Name", "Area", "Service", "Eligible", "Bookings", "Out of 100", "Complaints", "Missed follow-ups", "Provider did not attend"],
            example: [
              ["Aisha", "Srinagar", "Cleaning", "10", "6", "60", "0", "1", "0"],
              ["Aisha", "Srinagar", "Plumbing", "4", "1", "25", "1", "0", "0"],
              ["Aisha", "Budgam", "Cleaning", "4", "2", "50", "0", "1", "0"],
              ["Aisha", "Budgam", "Plumbing", "2", "1", "50", "0", "0", "0"],
              ["Aisha", "Baramulla", "Cleaning", "1", "0", "0", "0", "0", "0"],
              ["Aisha", "Baramulla", "Plumbing", "1", "0", "0", "0", "0", "0"],
              ["Imran", "Srinagar", "Cleaning", "6", "2", "33", "0", "1", "0"],
              ["Imran", "Srinagar", "Plumbing", "3", "1", "33", "0", "0", "0"],
              ["Imran", "Budgam", "Cleaning", "2", "1", "50", "0", "0", "0"],
              ["Imran", "Budgam", "Plumbing", "4", "0", "0", "0", "2", "1"],
              ["Imran", "Baramulla", "Cleaning", "3", "0", "0", "0", "1", "0"],
              ["Imran", "Baramulla", "Plumbing", "2", "1", "50", "0", "0", "0"],
              ["Rafia", "Srinagar", "Cleaning", "4", "2", "50", "0", "0", "0"],
              ["Rafia", "Srinagar", "Plumbing", "1", "0", "0", "0", "0", "0"],
              ["Rafia", "Budgam", "Cleaning", "2", "1", "50", "0", "0", "0"],
              ["Rafia", "Budgam", "Plumbing", "2", "0", "0", "0", "0", "0"],
              ["Rafia", "Baramulla", "Cleaning", "2", "0", "0", "1", "1", "1"],
              ["Rafia", "Baramulla", "Plumbing", "2", "0", "0", "0", "0", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Customer Experience Manager",
      when: "Week of 22 Sep 2026",
      intro: "Aisha is at 45 out of 100. Imran is at 25. Rafia is at 23, and her absence line was not in from HR. Do not copy these rows onto another week."
    }
  },
  {
    group: "Each month",
    id: "cxm-staff-month",
    name: "Customer team report",
    when: "With the monthly close.",
    writer: "Customer Experience Manager",
    writerRole: "cxm",
    to: "Head of Operations.",
    rule: "Add the weekly team rows. Out of 100 uses eligible leads, not every lead. Then the same person in each area and category.",
    lines: [
      { field: "Each person this month", hint: "Company totals, then each area and category." }
    ],
    sections: [
      {
        title: "September",
        rows: [
          { type: "note", label: "Month", hint: "The month you are closing.", example: "September 2026" },
          {
            type: "table",
            label: "Each person",
            hint: "Leads add to 224. Eligible leads add to 155. Bookings add to 66. Out of 100 uses eligible leads.",
            columns: ["Name", "Leads", "Eligible", "Bookings", "Out of 100", "Complaints", "Missed follow-ups", "Uninformed leave", "Days absent"],
            example: [
              ["Aisha", "70", "50", "30", "60", "3", "6", "0", "0"],
              ["Imran", "80", "55", "20", "36", "2", "11", "1", "0"],
              ["Rafia", "74", "50", "16", "32", "1", "4", "0", "1"]
            ]
          },
          {
            type: "table",
            label: "Each person in each area and service",
            hint: "These rows add up to the person table and to the month grid.",
            columns: ["Name", "Area", "Service", "Eligible", "Bookings", "Out of 100", "Complaints", "Missed follow-ups", "Provider did not attend"],
            example: [
              ["Aisha", "Srinagar", "Cleaning", "22", "16", "73", "0", "2", "0"],
              ["Aisha", "Srinagar", "Plumbing", "8", "5", "63", "1", "1", "0"],
              ["Aisha", "Budgam", "Cleaning", "8", "5", "63", "1", "1", "0"],
              ["Aisha", "Budgam", "Plumbing", "6", "2", "33", "0", "1", "1"],
              ["Aisha", "Baramulla", "Cleaning", "3", "1", "33", "1", "1", "0"],
              ["Aisha", "Baramulla", "Plumbing", "3", "1", "33", "0", "0", "0"],
              ["Imran", "Srinagar", "Cleaning", "22", "10", "45", "0", "3", "0"],
              ["Imran", "Srinagar", "Plumbing", "7", "3", "43", "0", "2", "0"],
              ["Imran", "Budgam", "Cleaning", "9", "4", "44", "0", "2", "0"],
              ["Imran", "Budgam", "Plumbing", "8", "2", "25", "1", "2", "1"],
              ["Imran", "Baramulla", "Cleaning", "6", "0", "0", "0", "1", "1"],
              ["Imran", "Baramulla", "Plumbing", "3", "1", "33", "1", "1", "0"],
              ["Rafia", "Srinagar", "Cleaning", "18", "6", "33", "0", "1", "0"],
              ["Rafia", "Srinagar", "Plumbing", "6", "2", "33", "0", "0", "0"],
              ["Rafia", "Budgam", "Cleaning", "8", "3", "38", "0", "1", "0"],
              ["Rafia", "Budgam", "Plumbing", "6", "2", "33", "0", "0", "0"],
              ["Rafia", "Baramulla", "Cleaning", "7", "1", "14", "1", "1", "1"],
              ["Rafia", "Baramulla", "Plumbing", "5", "2", "40", "0", "1", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Customer Experience Manager",
      when: "September 2026",
      intro: "The three people add to 224 leads, 155 eligible leads, and 66 bookings. Aisha is at 60 out of 100 on eligible leads. Imran is at 36. Rafia is at 32. Imran took one uninformed leave. Rafia was absent one day. Do not copy these rows onto another month."
    }
  },
  {
    group: "Each week",
    id: "pom-roster",
    name: "Provider roster",
    when: "With the weekly operations review.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations.",
    rule: "How many providers we have, who joined, who was suspended, and who was blacklisted. Then the count in each area and service. A zero is a gap.",
    lines: [
      { field: "Joined, suspended, blacklisted", hint: "The counts, then one row for each person who was stopped." },
      { field: "Each area and service", hint: "How many active providers." }
    ],
    sections: [
      {
        title: "What changed",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "stats",
            label: "The book",
            hint: "Copy from the provider files.",
            cells: [
              { label: "Active", example: "42" },
              { label: "New files started", example: "3" },
              { label: "Activated", example: "1" },
              { label: "Suspended", example: "1" },
              { label: "Blacklisted", example: "0" },
              { label: "Inactive", example: "7" }
            ]
          },
          {
            type: "table",
            label: "Suspended or blacklisted",
            hint: "One row for each person. If there was none, write none in the first row.",
            columns: ["Provider", "What happened", "Area and service", "Why"],
            example: [
              ["Shabir Ahmad", "Suspended. Not activated", "Baramulla, plumbing", "Photo does not match. Not answering"]
            ]
          }
        ]
      },
      {
        title: "Where they work",
        rows: [
          {
            type: "table",
            label: "Active providers in each area and service",
            hint: "Write the count. Zero means we cannot serve that work there.",
            columns: ["Area", "Service", "Active", "Joined", "Suspended", "Blacklisted", "Provider leads", "Registered", "Activated"],
            example: [
              ["Srinagar", "Cleaning", "14", "1", "0", "0", "3", "1", "1"],
              ["Srinagar", "Plumbing", "8", "0", "0", "0", "1", "0", "0"],
              ["Budgam", "Cleaning", "9", "1", "0", "0", "2", "1", "0"],
              ["Budgam", "Plumbing", "2", "1", "0", "0", "2", "1", "0"],
              ["Baramulla", "Cleaning", "0", "0", "0", "0", "0", "0", "0"],
              ["Baramulla", "Plumbing", "0", "0", "1", "0", "1", "0", "0"],
              ["Other categories", "All", "9", "0", "0", "0", "0", "0", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Provider Experience Manager",
      when: "Week of 22 Sep 2026",
      intro: "42 are active. One new provider was activated. Shabir Ahmad was not. Baramulla cleaning and Baramulla plumbing have no active provider. Do not copy these counts onto another week."
    }
  },
  {
    group: "Each month",
    id: "pom-roster-month",
    name: "Provider roster",
    when: "With the monthly close.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations.",
    rule: "The same roster for the month. This month against last month.",
    lines: [
      { field: "The month", hint: "Joined, suspended, blacklisted, and the count in each area and service." }
    ],
    sections: [
      {
        title: "September",
        rows: [
          { type: "note", label: "Month", hint: "This month and the previous month.", example: "September 2026, against August 2026" },
          {
            type: "table",
            label: "The book",
            hint: "Copy both months from the files.",
            columns: ["Line", "September", "August"],
            example: [
              ["Active at month end", "42", "39"],
              ["New files started", "11", "8"],
              ["Activated", "4", "6"],
              ["Suspended", "1", "2"],
              ["Blacklisted", "0", "1"],
              ["Inactive", "7", "5"]
            ]
          },
          {
            type: "table",
            label: "Active providers now",
            hint: "The live count. Zero is a gap.",
            columns: ["Area", "Service", "Active now", "Joined this month", "Suspended", "Blacklisted", "Provider leads", "Registered", "Activated"],
            example: [
              ["Srinagar", "Cleaning", "14", "4", "0", "0", "8", "4", "2"],
              ["Srinagar", "Plumbing", "8", "2", "0", "0", "5", "2", "1"],
              ["Budgam", "Cleaning", "9", "2", "0", "0", "5", "2", "1"],
              ["Budgam", "Plumbing", "2", "2", "0", "0", "5", "2", "0"],
              ["Baramulla", "Cleaning", "0", "0", "0", "0", "2", "0", "0"],
              ["Baramulla", "Plumbing", "0", "1", "1", "0", "3", "1", "0"],
              ["Other categories", "All", "9", "0", "0", "0", "0", "0", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Provider Experience Manager",
      when: "September 2026",
      intro: "We ended with 42 active providers, three more than August. Baramulla is still empty for cleaning and plumbing. Do not copy these counts onto another month."
    }
  },
  {
    group: "Each week",
    id: "pom-leads",
    name: "Provider lead report",
    when: "With the weekly operations review.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations.",
    rule: "The path from a provider lead to a registered file, and from a file to an active provider. Write who was cancelled and why.",
    lines: [
      { field: "The funnel", hint: "Leads, registered, activated, stopped, and the reason." }
    ],
    sections: [
      {
        title: "This week",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "stats",
            label: "Provider leads",
            hint: "Onboard now is counted separately from write down, do later.",
            cells: [
              { label: "Leads", example: "9" },
              { label: "Onboard now", example: "6" },
              { label: "Registered", example: "3" },
              { label: "Activated", example: "1" },
              { label: "Stopped", example: "1" }
            ]
          },
          {
            type: "table",
            label: "Stopped or cancelled",
            hint: "One row for each. Write the true reason.",
            columns: ["Provider", "Area and service", "Why it stopped"],
            example: [
              ["Shabir Ahmad", "Baramulla, plumbing", "Photo does not match. Not answering. Not activated"]
            ]
          },
          {
            type: "table",
            label: "Each area and service",
            hint: "Same row order as the roster. These rows add up to the counts above.",
            columns: ["Area", "Service", "Leads", "Registered", "Activated", "Stopped"],
            example: [
              ["Srinagar", "Cleaning", "3", "1", "1", "0"],
              ["Srinagar", "Plumbing", "1", "0", "0", "0"],
              ["Budgam", "Cleaning", "2", "1", "0", "0"],
              ["Budgam", "Plumbing", "2", "1", "0", "0"],
              ["Baramulla", "Cleaning", "0", "0", "0", "0"],
              ["Baramulla", "Plumbing", "1", "0", "0", "1"],
              ["Other categories", "All", "0", "0", "0", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Provider Experience Manager",
      when: "Week of 22 Sep 2026",
      intro: "Nine provider leads. Three files were registered. One provider was activated. Shabir Ahmad was stopped in Baramulla plumbing. Baramulla cleaning had no provider lead at all. Do not copy these counts onto another week."
    }
  },
  {
    group: "Each week",
    id: "pom-staff",
    name: "Provider team report",
    when: "With the weekly operations review.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations.",
    rule: "One row for each Provider Support person. Work handled, missed follow-ups, complaints, uninformed leave, and days absent. Copy absence from HR. If HR has not written it, write not in.",
    lines: [
      { field: "Each person", hint: "Open leads, follow-ups, complaints, leave, and absence." }
    ],
    sections: [
      {
        title: "Each person",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "table",
            label: "Provider Support",
            hint: "One row each.",
            columns: ["Name", "Open leads", "Onboard-now contacted", "Missed follow-ups", "Complaints", "Uninformed leave", "Days absent"],
            example: [
              ["Provider Support", "18", "5", "1", "0", "0", "0"]
            ]
          },
          {
            type: "table",
            label: "Each area and service",
            hint: "The leads this person handled, in the same cells as the roster.",
            columns: ["Name", "Area", "Service", "Leads", "Registered", "Missed follow-ups", "Complaints"],
            example: [
              ["Provider Support", "Srinagar", "Cleaning", "3", "1", "0", "0"],
              ["Provider Support", "Srinagar", "Plumbing", "1", "0", "0", "0"],
              ["Provider Support", "Budgam", "Cleaning", "2", "1", "0", "0"],
              ["Provider Support", "Budgam", "Plumbing", "2", "1", "0", "0"],
              ["Provider Support", "Baramulla", "Cleaning", "0", "0", "0", "0"],
              ["Provider Support", "Baramulla", "Plumbing", "1", "0", "1", "0"],
              ["Provider Support", "Other categories", "All", "0", "0", "0", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Provider Experience Manager",
      when: "Week of 22 Sep 2026",
      intro: "One person holds 18 provider leads. One follow-up was missed, on Shabir Ahmad’s documents. Do not copy this row onto another week."
    }
  },
  {
    group: "Each month",
    id: "pom-leads-month",
    name: "Provider lead report",
    when: "With the monthly close.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations.",
    rule: "Add the weekly provider-lead pages. Registered files and activations must match the monthly roster.",
    lines: [
      { field: "The month", hint: "Leads, registered, activated, and who was stopped." }
    ],
    sections: [
      {
        title: "September",
        rows: [
          { type: "note", label: "Month", hint: "The month you are closing.", example: "September 2026" },
          {
            type: "stats",
            label: "Provider leads",
            hint: "Registered and activated must match the roster.",
            cells: [
              { label: "Leads", example: "28" },
              { label: "Registered", example: "11" },
              { label: "Activated", example: "4" },
              { label: "Stopped", example: "1" }
            ]
          },
          {
            type: "table",
            label: "Stopped or cancelled",
            hint: "One row for each.",
            columns: ["Provider", "Area and service", "Why it stopped"],
            example: [
              ["Shabir Ahmad", "Baramulla, plumbing", "Photo does not match. Not answering. Not activated"]
            ]
          },
          {
            type: "table",
            label: "Each area and service",
            hint: "Same row order as the roster. Registered and activated match the roster.",
            columns: ["Area", "Service", "Leads", "Registered", "Activated", "Stopped"],
            example: [
              ["Srinagar", "Cleaning", "8", "4", "2", "0"],
              ["Srinagar", "Plumbing", "5", "2", "1", "0"],
              ["Budgam", "Cleaning", "5", "2", "1", "0"],
              ["Budgam", "Plumbing", "5", "2", "0", "0"],
              ["Baramulla", "Cleaning", "2", "0", "0", "0"],
              ["Baramulla", "Plumbing", "3", "1", "0", "1"],
              ["Other categories", "All", "0", "0", "0", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Provider Experience Manager",
      when: "September 2026",
      intro: "28 provider leads. 11 files were registered. 4 providers were activated. Baramulla cleaning had 2 leads and none registered. Shabir Ahmad was stopped. These counts match the roster. Do not copy them onto another month."
    }
  },
  {
    group: "Each month",
    id: "pom-staff-month",
    name: "Provider team report",
    when: "With the monthly close.",
    writer: "Provider Experience Manager",
    writerRole: "pom",
    to: "Head of Operations.",
    rule: "Add the weekly team rows. Copy absence from HR. If HR has not written it, write not in.",
    lines: [
      { field: "Each person this month", hint: "Work handled, missed follow-ups, complaints, leave, and absence." }
    ],
    sections: [
      {
        title: "September",
        rows: [
          { type: "note", label: "Month", hint: "The month you are closing.", example: "September 2026" },
          {
            type: "table",
            label: "Provider Support",
            hint: "One row each.",
            columns: ["Name", "Leads handled", "Registered", "Missed follow-ups", "Complaints", "Uninformed leave", "Days absent"],
            example: [
              ["Provider Support", "28", "11", "4", "0", "0", "0"]
            ]
          },
          {
            type: "table",
            label: "Each area and service",
            hint: "Same cells as the monthly roster. Missed follow-ups add to 4.",
            columns: ["Name", "Area", "Service", "Leads", "Registered", "Missed follow-ups", "Complaints"],
            example: [
              ["Provider Support", "Srinagar", "Cleaning", "8", "4", "1", "0"],
              ["Provider Support", "Srinagar", "Plumbing", "5", "2", "1", "0"],
              ["Provider Support", "Budgam", "Cleaning", "5", "2", "0", "0"],
              ["Provider Support", "Budgam", "Plumbing", "5", "2", "1", "0"],
              ["Provider Support", "Baramulla", "Cleaning", "2", "0", "0", "0"],
              ["Provider Support", "Baramulla", "Plumbing", "3", "1", "1", "0"],
              ["Provider Support", "Other categories", "All", "0", "0", "0", "0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Provider Experience Manager",
      when: "September 2026",
      intro: "One person handled the 28 provider leads. Four follow-ups were missed in the month. No uninformed leave and no absence. Do not copy this row onto another month."
    }
  },
  {
    group: "Each week",
    id: "mkt-source",
    name: "Source and ads report",
    when: "The day before the Growth review.",
    writer: "Marketing Manager",
    writerRole: "mkm",
    to: "Head of Growth. Head of Operations gets the enquiry counts, not the ad account.",
    rule: "Each source is its own row. Each live ad is its own row. Copy enquiries from Operations. Copy spend from the ad account. Name the best ad and the ad that spent and brought nobody.",
    lines: [
      { field: "Sources", hint: "Enquiries and bookings by source." },
      { field: "Ads", hint: "Spend, enquiries, and the best and worst ad." }
    ],
    sections: [
      {
        title: "Where demand came from",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "table",
            label: "Each source",
            hint: "Copy enquiries and bookings from the customer lead report. Do not guess a source.",
            columns: ["Source", "Enquiries", "Bookings"],
            example: [
              ["Paid ads", "26", "8"],
              ["Search", "11", "3"],
              ["Stalls", "16", "4"],
              ["Partners", "9", "2"],
              ["Other", "6", "1"],
              ["Source blank", "12", "0"]
            ]
          },
          {
            type: "table",
            label: "Each source in each area and service",
            hint: "Every source, including zero. These rows add up to the source table and to 80 enquiries and 18 bookings.",
            columns: ["Area", "Service", "Source", "Enquiries", "Bookings"],
            example: [
              ["Srinagar", "Cleaning", "Paid ads", "14", "6"],
              ["Srinagar", "Cleaning", "Search", "4", "1"],
              ["Srinagar", "Cleaning", "Stalls", "6", "2"],
              ["Srinagar", "Cleaning", "Partners", "2", "1"],
              ["Srinagar", "Cleaning", "Other", "1", "0"],
              ["Srinagar", "Cleaning", "Source blank", "1", "0"],
              ["Srinagar", "Plumbing", "Paid ads", "6", "1"],
              ["Srinagar", "Plumbing", "Search", "2", "1"],
              ["Srinagar", "Plumbing", "Stalls", "2", "0"],
              ["Srinagar", "Plumbing", "Partners", "1", "0"],
              ["Srinagar", "Plumbing", "Other", "1", "0"],
              ["Srinagar", "Plumbing", "Source blank", "0", "0"],
              ["Budgam", "Cleaning", "Paid ads", "6", "1"],
              ["Budgam", "Cleaning", "Search", "2", "1"],
              ["Budgam", "Cleaning", "Stalls", "3", "1"],
              ["Budgam", "Cleaning", "Partners", "1", "1"],
              ["Budgam", "Cleaning", "Other", "0", "0"],
              ["Budgam", "Cleaning", "Source blank", "0", "0"],
              ["Budgam", "Plumbing", "Paid ads", "0", "0"],
              ["Budgam", "Plumbing", "Search", "1", "0"],
              ["Budgam", "Plumbing", "Stalls", "2", "1"],
              ["Budgam", "Plumbing", "Partners", "2", "0"],
              ["Budgam", "Plumbing", "Other", "2", "0"],
              ["Budgam", "Plumbing", "Source blank", "3", "0"],
              ["Baramulla", "Cleaning", "Paid ads", "0", "0"],
              ["Baramulla", "Cleaning", "Search", "1", "0"],
              ["Baramulla", "Cleaning", "Stalls", "2", "0"],
              ["Baramulla", "Cleaning", "Partners", "2", "0"],
              ["Baramulla", "Cleaning", "Other", "1", "0"],
              ["Baramulla", "Cleaning", "Source blank", "4", "0"],
              ["Baramulla", "Plumbing", "Paid ads", "0", "0"],
              ["Baramulla", "Plumbing", "Search", "1", "0"],
              ["Baramulla", "Plumbing", "Stalls", "1", "0"],
              ["Baramulla", "Plumbing", "Partners", "1", "0"],
              ["Baramulla", "Plumbing", "Other", "1", "1"],
              ["Baramulla", "Plumbing", "Source blank", "4", "0"]
            ]
          }
        ]
      },
      {
        title: "Ads",
        rows: [
          {
            type: "table",
            label: "Each ad in its area and service",
            hint: "Spend from the ad account. Enquiries from the source table. A cell with no ad is still a row.",
            columns: ["Area", "Service", "Ad", "Spend", "Enquiries", "Bookings", "Keep or pause"],
            example: [
              ["Srinagar", "Cleaning", "Srinagar cleaning", "₹9,000", "14", "6", "Best. Keep"],
              ["Srinagar", "Plumbing", "Srinagar plumbing", "₹4,200", "6", "1", "Keep"],
              ["Budgam", "Cleaning", "Budgam cleaning", "₹1,600", "6", "1", "Keep"],
              ["Budgam", "Plumbing", "No ad", "₹0", "0", "0", "No ad, and 10 enquiries came from somewhere else"],
              ["Baramulla", "Cleaning", "No ad", "₹0", "0", "0", "Do not start an ad. No provider"],
              ["Baramulla", "Plumbing", "Baramulla plumbing", "₹3,200", "0", "0", "Worst. Pause"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Marketing Manager",
      when: "Week of 22 Sep 2026",
      intro: "Paid ads sent 26 enquiries and 8 bookings. Srinagar cleaning is the best ad. Baramulla plumbing spent ₹3,200 and sent nobody. Twelve enquiries still have no source. Do not copy these figures onto another week."
    }
  },
  {
    group: "Each month",
    id: "mkt-source-month",
    name: "Source and ads report",
    when: "With the monthly close.",
    writer: "Marketing Manager",
    writerRole: "mkm",
    to: "Head of Growth.",
    rule: "Add the weekly source rows. Name the best ad of the month and the ad that should stay paused.",
    lines: [
      { field: "The month", hint: "Sources, and the ads to keep or pause." }
    ],
    sections: [
      {
        title: "September",
        rows: [
          { type: "note", label: "Month", hint: "The month you are closing.", example: "September 2026" },
          {
            type: "table",
            label: "Each source",
            hint: "Add the weekly rows.",
            columns: ["Source", "Enquiries", "Bookings"],
            example: [
              ["Paid ads", "98", "30"],
              ["Search", "36", "10"],
              ["Stalls", "42", "14"],
              ["Partners", "22", "8"],
              ["Other", "14", "4"],
              ["Source blank", "12", "0"]
            ]
          },
          {
            type: "table",
            label: "Each source in each area and service",
            hint: "These rows add up to the source table, to 224 enquiries, and to 66 bookings.",
            columns: ["Area", "Service", "Source", "Enquiries", "Bookings"],
            example: [
              ["Srinagar", "Cleaning", "Paid ads", "52", "18"],
              ["Srinagar", "Cleaning", "Search", "12", "4"],
              ["Srinagar", "Cleaning", "Stalls", "14", "6"],
              ["Srinagar", "Cleaning", "Partners", "8", "3"],
              ["Srinagar", "Cleaning", "Other", "3", "1"],
              ["Srinagar", "Cleaning", "Source blank", "1", "0"],
              ["Srinagar", "Plumbing", "Paid ads", "16", "5"],
              ["Srinagar", "Plumbing", "Search", "4", "2"],
              ["Srinagar", "Plumbing", "Stalls", "6", "2"],
              ["Srinagar", "Plumbing", "Partners", "2", "1"],
              ["Srinagar", "Plumbing", "Other", "1", "0"],
              ["Srinagar", "Plumbing", "Source blank", "1", "0"],
              ["Budgam", "Cleaning", "Paid ads", "18", "5"],
              ["Budgam", "Cleaning", "Search", "6", "2"],
              ["Budgam", "Cleaning", "Stalls", "8", "3"],
              ["Budgam", "Cleaning", "Partners", "3", "2"],
              ["Budgam", "Cleaning", "Other", "1", "0"],
              ["Budgam", "Cleaning", "Source blank", "0", "0"],
              ["Budgam", "Plumbing", "Paid ads", "8", "2"],
              ["Budgam", "Plumbing", "Search", "6", "1"],
              ["Budgam", "Plumbing", "Stalls", "6", "2"],
              ["Budgam", "Plumbing", "Partners", "4", "1"],
              ["Budgam", "Plumbing", "Other", "2", "0"],
              ["Budgam", "Plumbing", "Source blank", "2", "0"],
              ["Baramulla", "Cleaning", "Paid ads", "3", "0"],
              ["Baramulla", "Cleaning", "Search", "5", "0"],
              ["Baramulla", "Cleaning", "Stalls", "5", "0"],
              ["Baramulla", "Cleaning", "Partners", "3", "0"],
              ["Baramulla", "Cleaning", "Other", "4", "2"],
              ["Baramulla", "Cleaning", "Source blank", "4", "0"],
              ["Baramulla", "Plumbing", "Paid ads", "1", "0"],
              ["Baramulla", "Plumbing", "Search", "3", "1"],
              ["Baramulla", "Plumbing", "Stalls", "3", "1"],
              ["Baramulla", "Plumbing", "Partners", "2", "1"],
              ["Baramulla", "Plumbing", "Other", "3", "1"],
              ["Baramulla", "Plumbing", "Source blank", "4", "0"]
            ]
          },
          {
            type: "table",
            label: "Each ad in its area and service",
            hint: "The best, the paused, and the cells with no ad.",
            columns: ["Area", "Service", "Ad", "Spend", "Enquiries", "Decision"],
            example: [
              ["Srinagar", "Cleaning", "Srinagar cleaning", "₹36,000", "52", "Best. Keep"],
              ["Srinagar", "Plumbing", "Srinagar plumbing", "₹12,000", "16", "Keep"],
              ["Budgam", "Cleaning", "Budgam cleaning", "₹8,000", "18", "Keep"],
              ["Budgam", "Plumbing", "Budgam plumbing", "₹4,800", "8", "Keep. Providers are still too few"],
              ["Baramulla", "Cleaning", "No ad", "₹0", "3", "Do not start an ad. No provider"],
              ["Baramulla", "Plumbing", "Baramulla plumbing", "₹11,400", "1", "Pause. No active provider"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Marketing Manager",
      when: "September 2026",
      intro: "Ads, stalls, and search all sent bookings. Twelve enquiries in the last week still have a blank source. Baramulla plumbing should stay paused. Do not copy these figures onto another month."
    }
  },
  {
    group: "Each working day",
    id: "fam-day",
    name: "Money close",
    when: "Every working day, before you leave.",
    writer: "Finance and Accounts Manager",
    writerRole: "fam",
    to: "Head of Finance and People.",
    rule: "Keep each kind of money on its own line. Money received, refunds, money paid to providers, compensation, and any booking that cost more than it brought in. Do not add these into one figure.",
    lines: [
      { field: "Today’s money", hint: "Received, refunded, paid, compensation, and a loss-making booking." }
    ],
    sections: [
      {
        title: "Today",
        rows: [
          { type: "note", label: "Date", hint: "Today’s date.", example: "Friday 26 Sep 2026" },
          {
            type: "table",
            label: "Each line",
            hint: "Copy from the books. If the record is not in, write not in. Do not guess.",
            columns: ["Line", "Amount"],
            example: [
              ["Money received from customers", "₹14,200"],
              ["Refunds", "₹0"],
              ["Money paid to providers", "₹9,600"],
              ["Compensation", "₹0"],
              ["Cash still open", "₹6,000"]
            ]
          },
          {
            type: "table",
            label: "Bookings that cost more than they brought in",
            hint: "One row for each. If there was none, write none in the first row.",
            columns: ["Booking", "Received", "Paid out", "Loss", "Why"],
            example: [
              ["None", "—", "—", "—", "No loss-making booking today"]
            ]
          },
          {
            type: "table",
            label: "Cells that moved today",
            hint: "Only the area and service that had money today. These rows add up to today’s received and paid lines.",
            columns: ["Area", "Service", "Received", "Refund", "Paid to providers", "Compensation", "Loss"],
            example: [
              ["Srinagar", "Cleaning", "₹8,000", "₹0", "₹5,000", "₹0", "₹0"],
              ["Srinagar", "Plumbing", "₹4,200", "₹0", "₹2,600", "₹0", "₹0"],
              ["Budgam", "Cleaning", "₹2,000", "₹0", "₹0", "₹0", "₹0"],
              ["Budgam", "Plumbing", "₹0", "₹0", "₹2,000", "₹0", "₹0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Finance and Accounts Manager",
      when: "Friday 26 Sep 2026",
      intro: "₹14,200 came in. ₹9,600 went to providers. No refund and no compensation. Cash still open is the same ₹6,000 already on the operations pages. Do not copy these amounts onto another day."
    }
  },
  {
    group: "Each week",
    id: "fam-week",
    name: "Money report",
    when: "Each week, with the operations review.",
    writer: "Finance and Accounts Manager",
    writerRole: "fam",
    to: "Head of Finance and People and the CEO.",
    rule: "The same lines as the daily close, for the week. Customer payment on the operations page and money received here must match, or you write the difference.",
    lines: [
      { field: "The week", hint: "Received, refunds, paid, compensation, cash, and loss-making bookings." }
    ],
    sections: [
      {
        title: "The week",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "table",
            label: "Each line",
            hint: "Do not add the lines together.",
            columns: ["Line", "Amount", "Matches operations"],
            example: [
              ["Money received from customers", "₹86,400", "Yes. Same as customer payment"],
              ["Refunds", "₹1,200", "BK-4374"],
              ["Money paid to providers", "₹51,200", "Yes. Same as provider settlement"],
              ["Compensation", "₹0", "None"],
              ["Cash still open", "₹6,000", "Yes. Imran ₹4,200. Aisha ₹1,800"]
            ]
          },
          {
            type: "table",
            label: "Bookings that cost more than they brought in",
            hint: "One row for each.",
            columns: ["Booking", "Received", "Paid out", "Loss", "Why"],
            example: [
              ["BK-4374", "₹500 refunded back", "₹800 to the provider", "₹800", "Cancelled. No provider could attend. Travel was still paid"]
            ]
          },
          {
            type: "table",
            label: "A payment row with no booking",
            hint: "Copy it from the provider payment report.",
            columns: ["Provider", "Due", "Paid", "Difference", "Booking"],
            example: [
              ["Gulzar", "₹4,200", "₹4,800", "₹600", "No booking id"]
            ]
          },
          {
            type: "table",
            label: "Each area and service",
            hint: "Same row order as operations. Customer payment adds to ₹86,400. Paid to providers adds to ₹51,200. Refunds add to ₹1,200. Do not add the columns into one figure.",
            columns: ["Area", "Service", "Advance", "Customer payment", "Refund", "Paid to providers", "Compensation", "Loss", "Cash still open"],
            example: [
              ["Srinagar", "Cleaning", "₹5,000", "₹48,000", "₹0", "₹28,000", "₹0", "₹0", "₹4,200"],
              ["Srinagar", "Plumbing", "₹1,600", "₹18,000", "₹0", "₹9,000", "₹0", "₹0", "₹0"],
              ["Budgam", "Cleaning", "₹2,000", "₹9,600", "₹0", "₹6,400", "₹0", "₹0", "₹1,800"],
              ["Budgam", "Plumbing", "₹0", "₹4,800", "₹0", "₹4,200", "₹0", "₹0", "₹0"],
              ["Baramulla", "Cleaning", "₹0", "₹0", "₹1,200", "₹0", "₹0", "₹800", "₹0"],
              ["Baramulla", "Plumbing", "₹0", "₹6,000", "₹0", "₹3,600", "₹0", "₹0", "₹0"],
              ["Other categories", "All", "₹0", "₹0", "₹0", "₹0", "₹0", "₹0", "₹0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Finance and Accounts Manager",
      when: "Week of 22 Sep 2026",
      intro: "Money received matches the customer page. Money paid matches the provider page. BK-4374 lost ₹800. Gulzar’s ₹600 has no booking. Do not copy these amounts onto another week."
    }
  },
  {
    group: "Each month",
    id: "fam-month",
    name: "Money report",
    when: "With the monthly close.",
    writer: "Finance and Accounts Manager",
    writerRole: "fam",
    to: "Head of Finance and People and the CEO.",
    rule: "The same lines, this month against last month. Do not add a line called revenue.",
    lines: [
      { field: "The month", hint: "Received, refunds, paid, compensation, cash, and loss-making bookings." }
    ],
    sections: [
      {
        title: "September against August",
        rows: [
          { type: "note", label: "Month", hint: "This month and the previous month.", example: "September 2026, against August 2026" },
          {
            type: "table",
            label: "Each line",
            hint: "Both months, from the books.",
            columns: ["Line", "September", "August"],
            example: [
              ["Money received from customers", "₹3,40,000", "₹2,95,000"],
              ["Refunds", "₹8,400", "₹6,100"],
              ["Money paid to providers", "₹1,98,000", "₹1,76,000"],
              ["Compensation", "₹2,000", "₹0"],
              ["Cash still open at month end", "₹6,000", "₹2,400"]
            ]
          },
          {
            type: "table",
            label: "Bookings that cost more than they brought in",
            hint: "One row for each booking still open as a loss.",
            columns: ["Booking", "Loss", "Why"],
            example: [
              ["BK-4374", "₹800", "Provider travel paid after the job was cancelled"]
            ]
          },
          {
            type: "table",
            label: "Each area and service this month",
            hint: "Customer payment adds to ₹3,40,000. Paid to providers adds to ₹1,98,000. Refunds add to ₹8,400. Compensation adds to ₹2,000.",
            columns: ["Area", "Service", "Advance", "Customer payment", "Refund", "Paid to providers", "Compensation", "Loss"],
            example: [
              ["Srinagar", "Cleaning", "₹14,000", "₹1,60,000", "₹2,400", "₹90,000", "₹0", "₹0"],
              ["Srinagar", "Plumbing", "₹7,000", "₹70,000", "₹0", "₹40,000", "₹2,000", "₹0"],
              ["Budgam", "Cleaning", "₹5,000", "₹42,000", "₹0", "₹28,000", "₹0", "₹0"],
              ["Budgam", "Plumbing", "₹3,000", "₹38,000", "₹2,400", "₹22,000", "₹0", "₹0"],
              ["Baramulla", "Cleaning", "₹400", "₹8,000", "₹3,600", "₹4,000", "₹0", "₹800"],
              ["Baramulla", "Plumbing", "₹3,000", "₹22,000", "₹0", "₹14,000", "₹0", "₹0"],
              ["Other categories", "All", "₹0", "₹0", "₹0", "₹0", "₹0", "₹0"]
            ]
          },
          {
            type: "table",
            label: "Same cells in August",
            hint: "Customer payment adds to ₹2,95,000. Paid to providers adds to ₹1,76,000. Refunds add to ₹6,100.",
            columns: ["Area", "Service", "Customer payment", "Refund", "Paid to providers", "Compensation", "Loss"],
            example: [
              ["Srinagar", "Cleaning", "₹1,40,000", "₹2,000", "₹80,000", "₹0", "₹0"],
              ["Srinagar", "Plumbing", "₹60,000", "₹0", "₹36,000", "₹0", "₹0"],
              ["Budgam", "Cleaning", "₹38,000", "₹1,100", "₹24,000", "₹0", "₹0"],
              ["Budgam", "Plumbing", "₹30,000", "₹1,500", "₹20,000", "₹0", "₹0"],
              ["Baramulla", "Cleaning", "₹7,000", "₹1,500", "₹2,000", "₹0", "₹0"],
              ["Baramulla", "Plumbing", "₹20,000", "₹0", "₹14,000", "₹0", "₹0"],
              ["Other categories", "All", "₹0", "₹0", "₹0", "₹0", "₹0"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Finance and Accounts Manager",
      when: "September 2026",
      intro: "More money came in than in August. Refunds and provider pay also rose. One cancelled booking still sits as an ₹800 loss. Do not copy these amounts onto another month."
    }
  },
  {
    group: "Each week",
    id: "hoo-next",
    name: "Where to act next",
    when: "After the weekly reports are in.",
    writer: "Head of Operations",
    writerRole: "hoo",
    to: "CEO, and the two managers who own the actions.",
    rule: "This is the live picture. One row order on every table: every area and every category, including zero. Copy customer, provider, and finance. Do not type a second set of numbers. A cell can fall while the company total rises.",
    lines: [
      { field: "The cells", hint: "Work, supply, money, and last week, in the same row order." },
      { field: "Next priority", hint: "The few actions, each with one owner." }
    ],
    sections: [
      {
        title: "The live picture",
        rows: [
          { type: "note", label: "Dates", hint: "The Monday and the Sunday.", example: "Monday 22 Sep 2026 to Sunday 28 Sep 2026" },
          {
            type: "table",
            label: "Work",
            hint: "Copy the customer operations report.",
            columns: ["Area", "Service", "Eligible", "Bookings", "Out of 100", "Finished", "Cancelled", "Top reason", "Outbounds booked"],
            example: [
              ["Srinagar", "Cleaning", "20", "10", "50", "8", "1", "Not at the address", "10"],
              ["Srinagar", "Plumbing", "8", "2", "25", "1", "0", "—", "2"],
              ["Budgam", "Cleaning", "8", "4", "50", "2", "0", "—", "4"],
              ["Budgam", "Plumbing", "8", "1", "13", "0", "1", "Declined", "1"],
              ["Baramulla", "Cleaning", "6", "0", "0", "0", "1", "No provider could attend", "0"],
              ["Baramulla", "Plumbing", "5", "1", "20", "0", "0", "—", "1"],
              ["Other categories", "All", "0", "0", "—", "0", "0", "—", "0"]
            ]
          },
          {
            type: "table",
            label: "Supply",
            hint: "Copy the provider roster. Active providers on these rows add to 42.",
            columns: ["Area", "Service", "Active", "Joined", "Suspended", "Provider leads", "Registered"],
            example: [
              ["Srinagar", "Cleaning", "14", "1", "0", "3", "1"],
              ["Srinagar", "Plumbing", "8", "0", "0", "1", "0"],
              ["Budgam", "Cleaning", "9", "1", "0", "2", "1"],
              ["Budgam", "Plumbing", "2", "1", "0", "2", "1"],
              ["Baramulla", "Cleaning", "0", "0", "0", "0", "0"],
              ["Baramulla", "Plumbing", "0", "0", "1", "1", "0"],
              ["Other categories", "All", "9", "0", "0", "0", "0"]
            ]
          },
          {
            type: "table",
            label: "Money",
            hint: "Copy the finance week. Do not add the columns into one figure.",
            columns: ["Area", "Service", "Average ticket", "Customer payment", "Refund", "Paid to providers", "Loss"],
            example: [
              ["Srinagar", "Cleaning", "₹4,000", "₹48,000", "₹0", "₹28,000", "₹0"],
              ["Srinagar", "Plumbing", "₹11,000", "₹18,000", "₹0", "₹9,000", "₹0"],
              ["Budgam", "Cleaning", "₹3,200", "₹9,600", "₹0", "₹6,400", "₹0"],
              ["Budgam", "Plumbing", "—", "₹4,800", "₹0", "₹4,200", "₹0"],
              ["Baramulla", "Cleaning", "—", "₹0", "₹1,200", "₹0", "₹800"],
              ["Baramulla", "Plumbing", "—", "₹6,000", "₹0", "₹3,600", "₹0"],
              ["Other categories", "All", "—", "₹0", "₹0", "₹0", "₹0"]
            ]
          },
          {
            type: "table",
            label: "Against last week",
            hint: "Copy the customer report. Budgam plumbing fell. Baramulla cleaning stayed at zero while eligible leads rose.",
            columns: ["Area", "Service", "Bookings last week", "Bookings this week", "Finished last week", "Finished this week", "What to do"],
            example: [
              ["Srinagar", "Cleaning", "6", "10", "7", "8", "Performs well. Keep"],
              ["Srinagar", "Plumbing", "2", "2", "2", "1", "High ticket. Keep"],
              ["Budgam", "Cleaning", "5", "4", "2", "2", "Low ticket. Keep"],
              ["Budgam", "Plumbing", "2", "1", "1", "0", "Demand rose. Bookings fell. Add providers"],
              ["Baramulla", "Cleaning", "0", "0", "0", "0", "Cannot serve. Stop selling"],
              ["Baramulla", "Plumbing", "2", "1", "0", "0", "No active provider. Stop selling"],
              ["Other categories", "All", "0", "0", "0", "0", "Split this row into the real categories"]
            ]
          }
        ]
      },
      {
        title: "Next priority",
        rows: [
          {
            type: "table",
            label: "Do these first",
            hint: "One owner each. High demand we cannot serve comes before a new ad.",
            columns: ["Priority", "Owner", "Why"],
            example: [
              ["Stop Baramulla cleaning ads until a provider is active", "Marketing Manager", "6 eligible leads, 0 bookings, 0 providers"],
              ["Pause the Baramulla plumbing ad", "Marketing Manager", "₹3,200 spent, 0 enquiries"],
              ["Add plumbers in Budgam", "Provider Experience Manager", "8 eligible leads, 1 booking, 2 providers"],
              ["Finish the 7 bookings that are not settled", "Customer Experience Manager", "18 taken, 11 finished"],
              ["Find Gulzar’s booking id", "Provider Experience Manager", "₹600 paid with no booking"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Head of Operations",
      when: "Week of 22 Sep 2026",
      intro: "Srinagar cleaning is the week that worked. Baramulla is demand we cannot serve. The next spend should not go there. Do not copy this page onto another week."
    }
  },
  {
    group: "Each month",
    id: "hoo-next-month",
    name: "Where to act next",
    when: "After the monthly review.",
    writer: "Head of Operations",
    writerRole: "hoo",
    to: "CEO.",
    rule: "The same live picture for the month, on the same row order as the week. Copy the month pages. Do not type a second set of numbers.",
    lines: [
      { field: "The month", hint: "Work, supply, money, and August on the same cells." }
    ],
    sections: [
      {
        title: "September",
        rows: [
          { type: "note", label: "Month", hint: "The month you are closing.", example: "September 2026, against August 2026" },
          {
            type: "table",
            label: "Work",
            hint: "Copy the customer month page. Out of 100 uses eligible leads. Eligible adds to 155. Leads add to 224. Bookings add to 66.",
            columns: ["Area", "Service", "Leads", "Eligible", "Bookings", "Out of 100", "Finished", "Cancelled", "Top reason"],
            example: [
              ["Srinagar", "Cleaning", "90", "62", "32", "52", "24", "3", "Price refused"],
              ["Srinagar", "Plumbing", "30", "21", "10", "48", "6", "1", "Not at the address"],
              ["Budgam", "Cleaning", "36", "25", "12", "48", "6", "2", "Price refused"],
              ["Budgam", "Plumbing", "28", "20", "6", "30", "3", "2", "No provider could attend"],
              ["Baramulla", "Cleaning", "24", "16", "2", "13", "0", "3", "No provider could attend"],
              ["Baramulla", "Plumbing", "16", "11", "4", "36", "2", "1", "Declined"],
              ["Other categories", "All", "0", "0", "0", "—", "0", "0", "—"]
            ]
          },
          {
            type: "table",
            label: "Supply",
            hint: "Copy the monthly roster. Active providers add to 42.",
            columns: ["Area", "Service", "Active", "Joined", "Suspended", "Provider leads", "Registered", "Activated"],
            example: [
              ["Srinagar", "Cleaning", "14", "4", "0", "8", "4", "2"],
              ["Srinagar", "Plumbing", "8", "2", "0", "5", "2", "1"],
              ["Budgam", "Cleaning", "9", "2", "0", "5", "2", "1"],
              ["Budgam", "Plumbing", "2", "2", "0", "5", "2", "0"],
              ["Baramulla", "Cleaning", "0", "0", "0", "2", "0", "0"],
              ["Baramulla", "Plumbing", "0", "1", "1", "3", "1", "0"],
              ["Other categories", "All", "9", "0", "0", "0", "0", "0"]
            ]
          },
          {
            type: "table",
            label: "Money",
            hint: "Copy the finance month. Customer payment adds to ₹3,40,000.",
            columns: ["Area", "Service", "Average ticket", "Customer payment", "Refund", "Paid to providers", "Compensation", "Loss"],
            example: [
              ["Srinagar", "Cleaning", "₹4,200", "₹1,60,000", "₹2,400", "₹90,000", "₹0", "₹0"],
              ["Srinagar", "Plumbing", "₹11,400", "₹70,000", "₹0", "₹40,000", "₹2,000", "₹0"],
              ["Budgam", "Cleaning", "₹3,400", "₹42,000", "₹0", "₹28,000", "₹0", "₹0"],
              ["Budgam", "Plumbing", "₹9,600", "₹38,000", "₹2,400", "₹22,000", "₹0", "₹0"],
              ["Baramulla", "Cleaning", "—", "₹8,000", "₹3,600", "₹4,000", "₹0", "₹800"],
              ["Baramulla", "Plumbing", "₹8,800", "₹22,000", "₹0", "₹14,000", "₹0", "₹0"],
              ["Other categories", "All", "—", "₹0", "₹0", "₹0", "₹0", "₹0"]
            ]
          },
          {
            type: "table",
            label: "Against August",
            hint: "Same cells. September bookings rose in Srinagar. Baramulla cleaning stayed unable to finish a job.",
            columns: ["Area", "Service", "Bookings August", "Bookings September", "Finished August", "Finished September", "What it means"],
            example: [
              ["Srinagar", "Cleaning", "24", "32", "20", "24", "Best volume. Keep"],
              ["Srinagar", "Plumbing", "8", "10", "5", "6", "High ticket. Keep"],
              ["Budgam", "Cleaning", "10", "12", "6", "6", "Low ticket. Still books"],
              ["Budgam", "Plumbing", "6", "6", "3", "3", "Demand rose. Bookings did not. Add providers"],
              ["Baramulla", "Cleaning", "2", "2", "0", "0", "Cannot serve"],
              ["Baramulla", "Plumbing", "2", "4", "2", "2", "Bookings rose. Still no active provider"],
              ["Other categories", "All", "0", "0", "0", "0", "Split into the real categories"]
            ]
          },
          {
            type: "table",
            label: "Priorities for October",
            hint: "A few only.",
            columns: ["Priority", "Owner"],
            example: [
              ["Put an active cleaner in Baramulla before any new ad", "Provider Experience Manager"],
              ["Put an active plumber in Baramulla, or keep the ad paused", "Provider Experience Manager"],
              ["Add plumbers in Budgam", "Provider Experience Manager"],
              ["Keep spending on Srinagar cleaning", "Marketing Manager"],
              ["Clear bookings with a blank payment line", "Customer Experience Manager"]
            ]
          }
        ]
      }
    ],
    example: {
      writer: "Head of Operations",
      when: "September 2026",
      intro: "Srinagar cleaning is the work that performs. Plumbing is the high ticket. Baramulla is the demand we cannot serve. October starts there, not with a new town. Do not copy this page onto another month."
    }
  }
];

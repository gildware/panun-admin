window.PK_GROWTH = window.PK_GROWTH || {};
(function () {
  "use strict";
  const G = window.PK_GROWTH;
  if (!G.roles || !G.workflows) return;

  function card(kicker, title, lead, body, points, art) {
    const item = {
      kicker: kicker,
      title: title,
      lead: lead,
      body: Array.isArray(body) ? body : [body],
      points: points || [],
      meta: []
    };
    if (art) item.art = art;
    return item;
  }
  function kpi(name, target, why, how) {
    return { name: name, target: target, why: why, how: how };
  }
  function side(item, which) {
    item.side = which;
    return item;
  }
  function timed(item, when) {
    item.when = when;
    return item;
  }
  function addRole(spec, dept) {
    spec.dept = dept;
    spec.layout = "detailed";
    G.roles[spec.id] = spec;
    if (G.roleIds.indexOf(spec.id) === -1) G.roleIds.push(spec.id);
  }

  const glossMoney = [
    { id: "books", term: "Books", aliases: ["books", "the books"], meaning: "The books are the written record of money that came in and money that went out. Each line names who paid or was paid, the booking or the bill it belongs to, the amount, the date, and, if it is cash, who is holding that cash. A payment that exists only in a conversation, a chat, or someone’s memory is not in the books. The test is simple: another accounts person must be able to continue that line the next day without calling you." },
    { id: "same-day", term: "Same working day", aliases: ["same working day", "the same day"], meaning: "Same working day means before that working day ends. This seat does not use a minute clock, because no minute target was written in a company policy. Do not invent one. Write the item before you leave. If your shift ends and the item is still open, hand it to the person who is still on shift, with the next step written, so it is not left in your head overnight." },
    { id: "price-file", term: "Approved price file", aliases: ["approved price file", "approved price"], meaning: "The approved price file is the list of prices the company has already agreed. The written payment policy is where a booking advance, a discount, or a refund is allowed, and for how much. You may record an amount that is already in one of those writings. You may not invent a price, a discount, a refund, or a booking advance. If you cannot find the amount, stop the same working day and ask the manager. Do not type a temporary number while you wait." },
    { id: "four-lines", term: "Four money lines", aliases: ["four money lines", "four lines"], meaning: "The only money lines Customer Experience and Finance both write. Line one: the booking advance, exactly as the written payment policy says. Line two: the customer payment, as written in the admin panel. Line three: the provider settlement, as written in the admin panel. Line four: cash still open, with who holds it. Do not add a fifth line called revenue. If a line has no record, leave it blank and send it back the same day. Do not type a remembered total." },
    { id: "cash-note", term: "Cash note", aliases: ["cash note", "same-day settlement note"], meaning: "A cash note is the written note Operations sends when a customer paid a provider in cash. It must name the customer, the booking, the amount, the provider, the date, and who is holding the cash. Accounts writes that note into the books the same working day. The booking stays open until the customer side and the provider side are both settled in the admin panel. Cash in a pocket, with no note, is not yet a finished payment." }
  ];
  const glossPeople = [
    { id: "people-file", term: "People file", aliases: ["people file", "person file", "staff file"], meaning: "A people file is the written file for one person who works with Panun Kaergar. It contains the seat they were hired for, the result that seat must produce, the joining papers the written process asks for, the attendance, and any open request with a next step. One person has one file and one role. Another person must be able to continue that file without calling you. A chat message is not the file until the same facts are written in the file." },
    { id: "written-gap", term: "Written gap", aliases: ["written gap", "gap"], meaning: "A written gap is the note from the head who needs a person. It names the seat that is empty, the result that seat must produce, and proof that the seat’s own result is already happening. Hiring starts only from this note. A feeling that the office is busy, or a verbal request in a corridor, is not a gap. An empty box whose work is not yet happening is not a gap either. The person above that seat does the work and writes, the same day: I am doing [the name of the seat] today because the seat is empty. The box stays on the chart. For Field Marketing Manager, the note must show that stall visits, first signatures, and office and society contracts are each already happening. If any one of those three is not happening, send the hire back. If the seat or the result is missing, or the work is not yet happening, send the note back the same working day and do not open the hire." },
    { id: "deputy", term: "Deputy", aliases: ["deputy"], meaning: "A deputy is the one named person who keeps a daily list moving while the manager of that list is on leave. The manager writes the deputy’s name and the dates before the leave starts. The deputy assigns the work. The deputy does not keep the best items, and does not approve pay, a price, a discount, or a refund. If the manager is already away and no deputy was named, the head of that part writes one name that morning, before the list opens. Until that name is written, new work in that list waits. When the manager returns, the deputy stops, and the manager reads what happened and takes the list back." }
  ];
  const glossTech = [
    { id: "ticket", term: "Ticket", aliases: ["ticket", "tickets"], meaning: "A ticket is one written piece of work for the product. It says what is broken or what is needed, who asked, and what finished looks like. The Software Engineer Team Lead gives that ticket to one engineer. An engineer does not open the shared board and choose a ticket. If the ticket does not say what finished looks like, it goes back to the person who asked, the same working day, and nobody starts building it." },
    { id: "live-record", term: "Live record", aliases: ["live record", "live records"], meaning: "A live record is a booking, a payment, a lead, or a provider file that a real customer or a real provider depends on. A change that can erase one of these, hide it, or make the next person unable to continue it, does not go out. It is stopped the same working day and sent to the head who owns that record. It goes out only after that head has written yes." },
    { id: "deputy", term: "Deputy", aliases: ["deputy"], meaning: "Here, the deputy is the one named engineer who assigns tickets while the Software Engineer Team Lead is on leave. The team lead writes the name and the dates before the leave. The deputy assigns tickets and does not keep the interesting ones. The deputy does not approve a change to a live record alone. If the team lead is already away and no deputy was named, the Head of Technology writes one name that morning. Until that name is written, new tickets wait." }
  ];

  addRole({
    id: "ceo",
    name: "CEO",
    hero: "role-ceo.png",
    icon: "control",
    scene: "control",
    result: "Each of the four heads can show one written result from the records, any decision that changes what Panun Kaergar does is written the same working day, and each week the money note shows jobs settled on both sides, growth spend against the signed amount, and cash not yet in the books.",
    what: "You are the person the four heads answer to. The four heads are the Head of Growth, the Head of Operations, the Head of Finance and People, and the Head of Technology and Data. You write, in plain words, what Panun Kaergar will do and what it will not do: which services, which towns, and which work we refuse. You read the result each head brings, and you ask to see the record behind it. When two heads disagree, you decide. You do not answer customer leads, you do not post payments, you do not hire the day’s staff, and you do not write the product.",
    why: "If you spend the day doing a head’s daily work, that system stops having an owner, and the company starts to depend on your memory. If the direction is only something you said in a meeting, each head will build a slightly different company. If two heads disagree and you leave it as a conversation, a customer, a provider, or a payment waits while people talk.",
    how: "Keep one written note of the direction, where a new person can find it. When the direction changes, mark the old note as replaced, write the date, and send the new note to the four heads the same working day. On the same weekday every week, write the money note. It has three lines and nothing else. Line one: how many jobs were finished and settled on both the customer side and the provider side, taken from the Operations record. Line two: growth money spent that week, written next to the amount you already signed for the month, taken from the Growth plan and the books. Line three: each cash amount that is not yet in the books, with the customer, the booking, the amount, the provider, the date, and who is holding the cash, taken from the cash notes Finance has recorded. If a line has no record, leave it blank and send that gap back to the head who owns it the same day. Do not type a figure from memory. A week with no money note is an unfinished week. On the rhythm you have written, and the same day if a head says something cannot wait, read what each head wrote. If a number does not match the bookings, the books, the people files, or the tickets, ask that head to show the record before the working day ends. When two heads cannot agree, write the decision, the name of the person who will do the next step, and the date, and send it to both heads the same working day. Before you go on leave, name one of the four heads and the dates. That person moves only decisions that cannot wait, and writes the money note if a weekly day falls while you are away. They do not change the direction, the price file, or who the heads are.",
    acting: "If you are away and you already named one head, that person writes every urgent decision and why it could not wait. When you return, you read those decisions the same day and you take that work back. If you are already away and no name was written, the four heads do not invent a new direction. They keep working from the direction that is already written, and they do not change who the heads are. If a head’s own seat is empty, that head covers the day and writes that they are doing it because the seat is empty. You still read the result. You do not take their queue.",
    lanes: [
      { kicker: "Part 1", title: "Write what the company will do and will not do", work: "Write one note in plain words. Name the services Panun Kaergar sells, the towns it serves, and the work it refuses. Put the note where a new person in any seat can open it without asking you. When something changes, do not add a second note beside the old one. Mark the old note as replaced, write the date, and send the new note to the four heads before that working day ends. A sentence in a meeting is not a change until this note is updated.", result: "Any person can read what is in and what is out, and there is only one current version.", who: "You write the note. The four heads follow it. They do not each write their own version.", art: "role-ceo.png" },
      { kicker: "Part 2", title: "Hold each head to the one result of their seat", work: "Ask the Head of Growth to show who we sell to, what we promised, and whether Operations can finish that promise, and to show growth money spent this week next to the amount you signed for the month. Ask the Head of Operations to show that every lead has one owner, that hot work was done before cold work, and how many jobs were finished with both the customer side and the provider side settled. Ask the Head of Finance and People to show that the books match the money, that each person has one file, and to list cash that is not yet in the books. Ask the Head of Technology and Data to show that the tools were up, and that a change did not hide a booking, a payment, a lead, or a provider file. On the same weekday every week, copy those three money lines onto one note. If a line has no record, leave it blank and send it back the same day. If they tell you it is fine and cannot point at a record, send it back the same working day.", result: "You can see four results from records, and one weekly money note that a new person can read without adding the figures up in their head.", who: "You ask. Each head brings the record. You do not collect the raw leads, invoices, or tickets yourself.", art: "ctl-books.png" },
      { kicker: "Part 3", title: "Write the decision the same working day", work: "When two heads disagree, or when one head asks for a change that affects another seat, choose before that working day ends. Write what you decided, which two seats it affects, who will do the next step, and the date. Send that writing to both heads. If the decision changes a price, a town, or a service, the head who owns that file updates the file. Do not leave the choice in a chat, and do not wait for a later meeting to make it real.", result: "The next person can follow the decision without calling you to ask what was agreed.", who: "You decide. The named owner does the next step.", art: "tec-ticket.png" }
    ],
    owns: [
      "One current written note of what Panun Kaergar will do and will not do, including services, towns, and work we refuse",
      "The final yes or no when two heads disagree, written the same working day with an owner and a date",
      "Who the four heads are, written down, so a new person knows who answers for each part",
      "The name and the dates of the one head who may move urgent decisions while you are away",
      "The weekly money note: jobs settled on both sides, growth spend against the signed monthly amount, and cash not yet in the books"
    ],
    mustNot: [
      "Assign a customer lead or a provider lead, or open either inbox and start working it",
      "Post an invoice, a receipt, a cash note, or a salary, even to be helpful for one day",
      "Hire a person, or tell HR to hire, unless the head who needs that seat has already written the seat and the result",
      "Write the product, or take an engineer’s ticket from the board",
      "Invent a price, a booking advance, a discount, a refund, or a salary when the written policy is blank",
      "Fill a blank line on the weekly money note with a figure you remember",,
      "Treat a meeting, a phone call, or a chat as a decision if it was never written down"
    ],
    good: [
      "A new person opened the direction note and could say what the company sells, where, and what it refuses, without calling you.",
      "Each head showed their result by pointing at bookings, books, people files, or tickets.",
      "A disagreement between two heads was written the same day, with one owner for the next step.",
      "You were away, one named head moved only what could not wait, and you read those decisions when you returned.",
      "The weekly money note exists, and each of its three lines points at a record, or the missing line was sent back the same day."
    ],
    bad: [
      "You spent the day on leads, payments, hiring papers, or code, and a disagreement between heads was still open at the end of the day.",
      "Two versions of the direction exist, and people are following different ones.",
      "A new service, a new town, or a new head is real only because someone said so in a meeting.",
      "You accepted “it is fine” and never asked to see the record.",
      "The week ended with no money note, or a line on it was a number nobody could find in the bookings, the growth plan, or the books."
    ],
    records: [
      "The direction note: services we sell, towns we serve, work we refuse, and the date of the current version",
      "The previous direction note, marked replaced, with the date it was replaced",
      "Each decision: what was decided, the heads it affects, the owner of the next step, and the date",
      "Decisions still open, and the date you last asked the owner to show they are done",
      "The name of the head who holds urgent decisions while you are away, the dates, and the decisions they wrote",
      "The weekly money note: the count of jobs settled on both sides, growth spend that week beside the signed monthly amount, and each open cash item with customer, booking, amount, provider, date, and who holds the cash"
    ],
    rules: [
      "A decision that is not written did not happen. If you cannot find it later, it is not a decision, and the heads must not act on it.",
      "You do not take a head’s daily work. If their seat is empty, that head writes that they are covering the day. You still read the result.",
      "Do not invent a rupee amount or a number of minutes. If the figure is not in a written policy, ask the head who owns that policy, or stop.",
      "One current direction note. When it changes, the old one is marked replaced the same working day, and the four heads receive the new one.",
      "Before leave, write one head’s name and the dates. That person does not change the direction, the price file, or who the heads are. If the weekly money day falls while you are away, that person writes the money note from the records and does not invent a line.",
      "The weekly money note is written on the same weekday every week. A line with no record stays blank and goes back to the head who owns it the same day. You do not type a remembered figure, and you do not skip the note because the four reports sounded fine."
    ],
    detailed: {
      copy: {
        definition: "You set what Panun Kaergar will do and will not do, and you hold the four heads to one result each. You decide when they disagree. You do not run their daily queues.",
        lanes: "The work is in three parts: the written direction, the four results checked against records, and a decision written the same working day. If a head’s seat is empty, that head covers the day and writes that they did. You do not take the queue.",
        workflow: "Follow the four steps in order. Read the results first. Ask for any missing record the same day. Decide if two heads disagree. Leave the daily work in its own seat."
      },
      definition: {
        what: [
          "You are the person the Head of Growth, the Head of Operations, the Head of Finance and People, and the Head of Technology and Data answer to.",
          "You keep one written note of the services, the towns, and the work the company refuses, and you replace that note in writing when it changes.",
          "You decide, in writing, when two of those heads disagree or when one head’s request changes another head’s work.",
          "You name one head, with dates, before you go on leave, so urgent decisions still have an owner.",
          "You write the weekly money note on the same weekday, with three lines taken from the records, and you leave a line blank when the record is missing."
        ],
        why: [
          "Without one written direction, each head invents a slightly different company, and customers hear different promises.",
          "Without a written decision the same day, urgent work waits while people retell the meeting.",
          "If you do the daily leads, the payments, the hiring papers, or the code, those systems no longer have a checker, and they depend on you being in the room."
        ]
      },
      responsibilities: [
        card("Direction", "Keep one written note of what we will and will not do", "A new person should not have to ask you what the company is allowed to sell.", "Write the services Panun Kaergar sells, the towns it serves, and the work it refuses. Keep a single current note where any seat can open it. When the direction changes, mark the old note as replaced, write the date on both, and send the new note to the four heads before the working day ends. Do not announce the change only in a meeting and leave the old note in place.", ["A new person can find the note without calling you.", "There is never two current notes at the same time.", "The old note stays, clearly marked replaced, so someone can see what changed and when."], "role-ceo.png"),
        card("The four results", "Ask each head to show the record, not only the story", "A confident sentence in a meeting is not proof that the work was done.", "Each week, and the same day if a head says something cannot wait, read the written result from each head. Growth must show who we sell to and whether the promise matches work we can finish. Operations must show lead owners, the order of hot then warm then cold, and both sides of the money on a finished job. Finance must show the books and the people files. Technology must show whether the tools were up and whether a change touched a booking, a payment, a lead, or a provider file. If the result and the record do not match, ask for the record the same working day. Do not fill the gap yourself.", ["You do not accept “it is fine” when nothing is attached.", "You send the gap back to that head. You do not quietly correct their file.", "You still do this when you are busy. A skipped check is how a false result becomes the company’s story."], "ctl-books.png"),
        card("Money note", "Write the three money lines every week, from the records only", "Four healthy reports can still hide a company that spent more than it kept, or cash that never reached the books.", "On the same weekday every week, write one note with three lines. First, the number of jobs Operations shows as finished with the customer side and the provider side both settled. Second, the growth money spent that week, written beside the amount you already signed for the month. Third, each cash amount Finance shows as not yet in the books, naming the customer, the booking, the amount, the provider, the date, and who is holding the cash. If any line has no record, leave that line blank. Send the gap to the head who owns it the same day. Do not add a fourth line, and do not type a rupee figure you cannot point at.", ["The note is in the same place every week, so a new person can find last week’s note.", "A blank line is honest. A remembered number is a miss.", "You still do not post the payment or chase the cash yourself. Finance and Operations do that."], "ctl-receipt.png"),
        card("Decisions", "Close a disagreement before the working day ends", "An open disagreement stops a customer, a provider, a payment, or a release.", "When two heads cannot agree, you choose. Write what you chose, the two seats it affects, the name of the person who will do the next step, and the date. Send that writing to both heads the same working day. If the decision changes a price, a town, or a service, tell the head who owns that file to update the file, and check that they did. One decision has one owner. Do not leave two people both thinking they own the next step.", ["The writing is the decision. The meeting is not.", "If it can wait until the next planned read, say that in writing too, so nobody treats silence as a yes.", "Follow the open decision until the owner shows you it is done."], "tec-ticket.png"),
        card("Away", "Name one head before you leave, and take the work back when you return", "Urgent decisions still need one name, or people will invent a direction while you are gone.", "Before the leave starts, write one of the four heads and the dates they will cover. Tell them they may move only a decision that cannot wait. They must write what they decided and why it could not wait. They must not change the direction note, the approved price file, or who the heads are. When you return, read every decision from those dates the same day and take that work back. If you are already away and no name was written, the four heads keep the direction that is already written. They do not appoint a new head and they do not open a new service.", ["The name and the dates are written before you leave, not on the morning you are already gone.", "The covering head does not keep that power after you return.", "A decision they made is still a written decision. You review it. You do not pretend it did not happen."], "ctl-people.png")
      ],
      handoffs: [
        side(card("From the heads", "A written result that points at a record", "You cannot hold a head to a result that was never written down.", "Each head gives you their result in writing, and they point at the bookings, the books, the people files, or the tickets behind it. You do not go and collect the raw leads, the invoices, or the tickets yourself. If the result and the file do not match, you send the question back the same working day and you ask them to show the record. You do not guess which version is true.", ["The handoff is the writing plus the record, not a verbal update.", "A missing record is sent back. It is not stored as your private knowledge."], "ctl-books.png"), "in"),
        side(card("To the heads", "A written decision they can follow without calling you", "They need a yes or a no, the next step, and one name.", "Send the decision the same working day. Include what was decided, who does the next step, and the date. Send it to both heads if both are affected. A meeting with no note afterwards is not a handoff, and they must not act on it. If the decision changes their file, say which file they must update.", ["One owner is named. Two owners means nobody owns it.", "You can find the same note later. If you cannot, it was not handed over."], "role-ceo.png"), "out"),
        side(card("To Finance", "Send back any amount that is not already written", "You do not set a price, a booking advance, a discount, or a refund by saying a number.", "If someone asks you for an amount and you cannot find it in the approved price file or the written payment policy, do not fill it in. Write what was asked, who asked, and that the policy is blank. Send that note to the Head of Finance and People the same working day. The payment or the promise waits until a written rule exists.", ["You do not type a temporary figure to keep the work moving.", "You do not tell the customer a number while Finance is still checking."], "ctl-receipt.png"), "out"),
        side(card("While you are away", "One named head, with a written limit", "Someone must move only what cannot wait, and they must leave a record for you.", "The named head writes each urgent decision, why it could not wait, and the date. They do not change who the heads are, and they do not replace the direction note. When you return, that pile of notes is the handoff back to you. You read it before you take the work back.", ["If no name was written, there is no handoff of new direction. The old direction stands.", "They do not keep assigning your decisions after the dates end."], "ctl-people.png"), "out")
      ],
      workflow: [
        card("Step 1", "Read what each of the four heads wrote", "Start from the files, not from whoever speaks first in the room.", "Open the latest written result from Growth, Operations, Finance and People, and Technology. Note any line that has no booking, book entry, people file, or ticket behind it. On the weekly money day, also write the money note from those records: jobs settled on both sides, growth spend beside the signed monthly amount, and cash not yet in the books. If one of those three has no record, leave that line blank and ask for it the same day. Do this on the rhythm you have written. Also do it the same day if a head tells you something cannot wait until the next read. Do not let a loud problem skip the other three results.", ["Write down the gaps before you start deciding.", "If a head sent nothing, that silence is itself a gap. Ask for the result the same day."], "ctl-books.png"),
        card("Step 2", "Ask for the missing record the same working day", "A gap is a question for that head. It is not a chance for you to guess.", "Send the question to the head whose result does not match. Ask them to show the booking, the book entry, the people file, or the ticket. Do not fill the gap yourself, and do not ask another department to invent the missing line. Wait for their record, or send the result back as not accepted.", ["Name the exact line that is missing, so they are not guessing what you want.", "Do not accept a new story that still has no record."], "ctl-receipt.png"),
        card("Step 3", "Decide when two heads disagree, and write it down", "If nobody owns the next step, the work stops even if everyone felt the meeting went well.", "Write the decision, the two seats it affects, the one person who will do the next step, and the date. Send it to both heads before the working day ends. If the decision changes a live service, a town, or a price, the head who owns that file updates the written file, and you check that the file now matches the decision.", ["There is no minute clock. The line is before the working day ends.", "Saying you will decide tomorrow is allowed only if you write that, and the work is not blocked today."], "tec-ticket.png"),
        card("Step 4", "Leave the daily work in the seat that owns it", "Your job is the decision. Their job is the queue.", "Do not assign a lead, post a payment, hire for the day, or take a ticket, even if it would be faster. If a seat under a head is empty, that head covers the day and writes that the seat is empty. You still read their result afterwards. You do not become the substitute for the empty seat.", ["Helping once without writing it down trains the company to wait for you.", "If you did touch a queue because a head’s seat was empty, that head writes it the same day, and you stop the next day."], "role-ceo.png")
      ],
      reporting: [
        timed(card("What you keep", "The direction note, with one current version", "People in every seat need the same answer to what the company does.", "The note lists the services we sell, the towns we serve, and the work we refuse. It shows the date it became current. When you replace it, the old note stays in the file, marked replaced, with the date. You read this note at least once a month to see if it still matches what the four heads are actually doing. If the work and the note have drifted, you correct the note or you correct the work, in writing, the same day you notice.", ["A new person can open it without asking which version is current.", "You tell the four heads when it changes. You do not assume they heard it in a meeting."], "role-ceo.png"), "When the direction changes, and at the monthly read"),
        timed(card("What you keep", "The decision list, until the owner shows it is done", "A decision that is not listed will be remembered differently by each head.", "Each decision names what was decided, the heads it affects, the owner of the next step, and the date. You keep open decisions on the list until that owner shows you the work is done in the record. You do not cross one off because the meeting felt finished. Add the decision the same working day you make it, not at the end of the week from memory.", ["Review the open list each time you read the four results.", "If the owner has no record, the decision stays open and you ask again the same day."], "tec-ticket.png"), "The same working day as the decision"),
        timed(card("What you keep", "The weekly money note", "This is how you see whether the company kept pace with the work, without adding four reports together in your head.", "Write it on the same weekday every week. Line one is the count of jobs settled on both sides, from Operations. Line two is growth spend for that week beside the signed monthly amount, from the Growth plan and the books. Line three is every cash item not yet in the books, from Finance, with customer, booking, amount, provider, date, and who holds the cash. A line with no record stays blank, and you send that gap back the same day. Keep four of these notes in a row. On each working day of those four weeks, write whether you assigned a lead, posted a payment, hired the day’s person, or took a ticket. If you did none of those, write none. A day with no mark does not count. This check is not a new seat.", ["Keep the old notes. Do not overwrite last week.", "The person covering while you are away writes this note if the weekday falls in the leave, and does not invent a line."], "ctl-books.png"), "The same weekday every week")
      ],
      standards: [
        card("Direction", "Only one current written direction exists", "Two current versions means two companies, and customers will hear both.", "There is one note of what we do and what we do not do. When it changes, the old note is marked replaced the same working day, with the date, and the four heads receive the new note. A verbal announcement does not retire the old note. Until the file is updated, the old written note is still what people must follow.", [], "role-ceo.png"),
        card("Proof", "You do not accept a result that has no record behind it", "Stories drift from week to week. The file does not.", "Before you treat a head’s result as true, you see the booking, the book entry, the people file, or the ticket they point to. If they cannot show it, you send the result back the same working day and you do not repeat their sentence as the company’s position.", [], "ctl-books.png"),
        card("The weekly money note", "The same weekday, three lines, each one from a record", "A week with no note is a week you do not know whether the company kept the money.", "The note exists every week. Jobs settled on both sides come from Operations. Growth spend beside the signed monthly amount comes from the plan and the books. Cash not in the books comes from Finance, with the six facts on each item. A missing record is a blank line sent back the same day. You do not estimate it.", [], "ctl-receipt.png"),
        card("Four weeks out of the queues", "The money note stays true, and you mark any day you touched a queue", "A written rule that is only true for one week is still a habit you have not proved.", "Keep the weekly money note for four weeks in a row. Each filled line still points at a record. On every working day in those four weeks, mark whether you assigned a lead, posted a payment, hired the day’s person, or took a ticket. If you did none, write none for that day. If you did one, write which one, the same day. A day with no mark breaks the four weeks. This is a check of this seat. It does not create another seat.", [], "role-ceo.png"),
        card("A decision the same day", "A disagreement that reaches you is written before the working day ends", "Leaving it overnight is also a decision, and it leaves the work with no owner.", "You write the choice, the one owner of the next step, and the date before the day ends. There is no minute clock on this seat, and you do not invent one. If the matter truly can wait, you write that it waits, until when, and who will bring it back.", [], "tec-ticket.png"),
        card("Limits of the seat", "You do not do a head’s daily job", "If you do their job, that job no longer has an owner, and nobody is left to check it.", "Customer and provider leads stay with Operations. Money entries stay with Finance. People files stay with HR. Tickets stay with Technology. Your work is the direction, the check against records, and the written decision. Naming a missing deputy for a head who failed to name one is allowed. Running their queue afterwards is not.", [], "ctl-people.png")
      ],
      kpis: [
        kpi("One written direction a new person can read", "One current note", "Without one current note, each head describes a different company, and a new person does not know which description to follow.", "Open the direction file. There should be one note that is marked current. It should name the services, the towns, and the work we refuse. If two notes both look current, mark the older one replaced the same working day and tell the four heads which note to follow."),
        kpi("Every disagreement that reaches you is written the same working day", "Every disagreement that reaches you", "An unwritten choice leaves a customer, a provider, or a payment waiting, because nobody knows who may move next.", "Look at the disagreements that reached you that day. Each one should have a written decision, one owner for the next step, and a date, before that working day ends. A disagreement that is only in a chat does not count as written."),
        kpi("You accept a result only after you have seen the record", "Every result you accept", "A story can sound complete and still hide a missing booking, a missing payment, or a missing file.", "For each result you are about to accept, point at the record the head showed you. If you cannot point at it, you have not accepted the result. Send the gap back the same working day and leave the result off the accepted list."),
        kpi("The weekly money note is written, and every filled line has a record", "Every week", "Without this note, four calm reports can hide unfinished money.", "On the same weekday, the note has the jobs settled on both sides, the growth spend beside the signed monthly amount, and the cash not yet in the books. Any line you filled can be opened in the Operations record, the Growth plan, or the books. A line you could not open was left blank and sent back the same day. A week with no note is a miss."),
        kpi("Four weeks of money notes, with every queue touch marked", "Four weeks in a row", "One good week does not show that the CEO has stayed out of the queues.", "Count four weekly money notes in a row, each with lines that point at a record or were sent back blank the same day. For each working day in those four weeks, the file says none, or it names the lead, the payment, the hire, or the ticket you touched. A missing note, a remembered figure, or a day with no mark means the four weeks have not been completed. This check does not add a seat."),
        kpi("You did not take over a head’s daily queue", "None taken by you", "If you take the queue, the system stops, because the checker and the doer have become the same person.", "At the end of the day, check that you did not assign a lead, post a payment, hire the day’s person, or take a ticket. If a head’s seat was empty and you had to touch one item, that head must have written, the same day, that the seat was empty and what you touched. The next day, the work goes back to that head.")
      ],
      escalations: [
        timed(card("Stop and send it to Finance", "A price, a booking advance, or a refund has no written policy", "If you invent the number, the company has promised money it never agreed, and the books will not match the promise.", "Do not fill in an amount. Write what was asked, who asked, and that you looked in the approved price file and the payment policy and did not find it. Send that note to the Head of Finance and People before the working day ends. Tell the person who asked that the answer waits for a written rule. Do not give the customer, the provider, or the employee a number while you wait.", [], "ctl-receipt.png"), "The same working day"),
        timed(card("Hear it the same day, and do not take the ticket", "A tool the company needs is down, and customers cannot be served", "If Operations cannot open the next step of a job, the company cannot keep the promise it already made.", "The Head of Technology tells you the same working day what is broken, which seats cannot work, and who owns the fix. You do not take the ticket and you do not start directing the engineers line by line. You make sure one owner is written. If the outage forces a choice between two heads, you write that decision the same day.", [], "tec-screen.png"), "The same working day"),
        timed(card("Name one person, then stop", "A head is away and no deputy was named for their list", "If the list opens with no name, people will pick the easy work and the urgent work will wait.", "That morning, before the list opens, write one name for that part and the date. Tell them they assign only. They do not keep the best items, and they do not approve a price, a pay change, or a refund. After the name is written, you do not run the queue. If no name can be written, the list stays closed until it can.", [], "ctl-people.png"), "That morning, before the list opens")
      ],
      glossary: [
        { id: "four-heads", term: "Four heads", aliases: ["four heads", "the four heads"], meaning: "The four heads are the Head of Growth, the Head of Operations, the Head of Finance and People, and the Head of Technology and Data. Each one has one written result. You hold them to that result by asking for the record. You do not do their daily work, and you do not let them swap results between them without a written decision." },
        { id: "direction", term: "Direction", aliases: ["direction", "what the company will and will not do"], meaning: "The direction is the single written note of the services Panun Kaergar sells, the towns it serves, and the work it refuses. A conversation, a slide, or an old note that was not marked replaced is not the direction. When the note changes, the old one is marked replaced the same working day." },
        { id: "money-note", term: "Money note", aliases: ["money note", "weekly money note"], meaning: "The note the CEO writes on the same weekday every week. It has three lines only. Jobs finished and settled on both the customer side and the provider side, from Operations. Growth money spent that week, beside the amount already signed for the month. Cash not yet in the books, from Finance, with the customer, the booking, the amount, the provider, the date, and who holds the cash. A line with no record stays blank. The CEO does not invent the figure and does not post the payment. Four of these notes in a row, with every working day marked as none or as a queue the CEO touched, is the check that this seat stayed out of the daily work. That check is not a new seat." }
      ]
    }
  }, "company");

  addRole({
    id: "hof",
    name: "Head of Finance and People",
    reportsTo: "CEO",
    hero: "role-hof.png",
    icon: "fpc",
    scene: "fpc",
    result: "The books match the money that really moved, every person has one file that matches the truth, and the two managers run those systems so you are checking them instead of posting the day yourself.",
    what: "You look after two systems: money, and people. The Finance and Accounts Manager runs the books. The HR and Administration Manager runs hiring, attendance, and the people files. You read what they write and you open the records behind it. You stop a price, a pay change, or a refund when it is not in a written policy. You do not sit and post every invoice, and you do not hire every person yourself.",
    why: "If you do both daily jobs, there is nobody left to notice a wrong book entry or an empty people file. A payment that lives only in someone’s memory cannot be settled later. A person who started work with no file cannot be understood by the next HR person. A number you invent, because the policy is blank, becomes a promise the company did not make.",
    how: "Each week, and the same day when money or a people file cannot wait, read the Finance and Accounts Manager’s report and the HR and Administration Manager’s report. For anything late, missing, or different from the booking, open the record. A cash note from Operations must be in the books the same working day, and the booking stays open until both sides are settled. If a manager is on leave and did not name a deputy, write one name that morning, before their list opens, and then do not take the list. If an amount is not in the written policy, send the question to the CEO the same day. Do not type the amount.",
    acting: "If you are going away, name one of the two managers and the dates, before you leave. They may move only an item that cannot wait. They must write what they did. They must not change the pay policy or the approved price file. When you return, read those items the same day and take the work back. If a manager’s seat is empty, you may cover that day, and you write that you are doing it because the seat is empty. The next day, the work returns to that system. You do not keep it.",
    lanes: [
      { kicker: "Part 1", title: "Check that the books tell the same story as the money", work: "Ask the Finance and Accounts Manager to show money that came in, money that went out, and anything that is late or missing. Open the records behind those lines, including a cash note from Operations. The cash note must name the customer, the booking, the amount, the provider, the date, and who holds the cash. If the report says a booking is paid, both the customer side and the provider side must already be settled in the admin panel. If they are not, the booking stays open in the books as well.", result: "Nobody can treat a booking as paid unless the books and the admin panel both say so.", who: "The Finance and Accounts Manager runs the day’s entries. You check the result against the records.", art: "ctl-books.png" },
      { kicker: "Part 2", title: "Check that each person has one file and one role", work: "Ask the HR and Administration Manager to show each hire against a written gap. The gap must name the seat and the result that seat must produce. Then look at the people file: the role, the joining papers the written process asks for, the attendance, and any open request with a next step. Attendance must come from the written record of that day, not from what someone remembers in the evening. You do not send that person onto a customer job. Operations assigns customer and provider work.", result: "If the HR executive is away tomorrow, another person can continue the file without a phone call.", who: "The HR and Administration Manager runs the day. You check the files.", art: "ctl-people.png" },
      { kicker: "Part 3", title: "Decide the exceptions, and do not take the daily list", work: "Your own work is a report that does not match the records, a policy that is blank, and a deputy name that a manager forgot to write. You do not post the day’s invoices to stay busy, and you do not file the day’s joining papers as your main job. If you must cover an empty seat for one day, write that the seat is empty and what you touched. If a manager is away and no deputy was named, write one name that morning. After the name is written, you stop. You do not keep the clearest payments or the easiest files for yourself.", result: "Both systems still run when you are reading reports, not only when you are at the entry desk.", who: "You. The managers assign. The executives write.", art: "role-hof.png" }
    ],
    owns: [
      "The money system, run by the Finance and Accounts Manager, and the people system, run by the HR and Administration Manager",
      "A check that each manager’s report matches the books or the people files, with gaps sent back the same working day",
      "A stop on any price, booking advance, discount, refund, or pay change that is not already in a written policy",
      "The name of a deputy, written that morning, when a manager is away and left the name blank"
    ],
    mustNot: [
      "Make posting the day’s invoices and receipts your main job, so that nobody is left to check them",
      "Allow a hire because the office feels busy, when no head has written the seat and the result",
      "Invent a salary, a tax figure, a price, a discount, a refund, or a booking advance",
      "Keep running a manager’s list after you have already named the missing deputy",
      "Mark a booking paid in the books while a cash note is missing, or while either side of the settlement is still open",
      "Change the booking story so that the books look tidy"
    ],
    good: [
      "Both managers could show their result by opening the records, and you sent back anything that did not match.",
      "A cash note that Operations wrote today is in the books today, and the booking is still open if either side is unsettled.",
      "A person was hired only after a head wrote the seat and the result, and the file can be continued by someone else.",
      "A manager was away, you named one deputy that morning, and you did not keep their list."
    ],
    bad: [
      "You spent the day posting bills, and you never opened the people files.",
      "A payment is real only because someone remembers it. It is not in the books.",
      "Someone started work and there is no written gap and no people file.",
      "You typed an amount that you could not find in the written policy, so the work would not wait."
    ],
    records: [
      "The weekly money report and the weekly people report, and the records you opened behind them",
      "Each gap you sent back: what did not match, who you sent it to, and the date",
      "Each policy stop: what was asked, who asked, which file you checked, and whether it went to the CEO",
      "Cash notes received from Operations, and whether each one is in the books the same day",
      "Deputy names and dates, when a manager is away, and the morning you wrote a name that had been left blank"
    ],
    rules: [
      "If the amount is not in the approved price file, the payment policy, or the written pay rule, you stop. You do not invent a number to keep the day moving.",
      "Operations writes what happened with the customer and the provider. You make sure the money is recorded. You do not rewrite the booking so the books look cleaner.",
      "A people file is finished only when another person can continue it: the seat, the result, the papers the process requires, the attendance, and the next step on any open request.",
      "You name a missing deputy that morning, in writing, and then you stop. The deputy assigns. The deputy does not approve pay, a price, or a refund.",
      "A booking stays open in the books until the customer side and the provider side are both settled in the admin panel."
    ],
    detailed: {
      copy: {
        definition: "You own the money system and the people system. Two managers run the day. You check that their records are true, and you stop any amount that is not already written in a policy.",
        lanes: "Three parts: the books match the money, the people files match the people, and you stay out of the daily list except to name a missing deputy or to cover an empty seat for one written day.",
        workflow: "Read both reports, send gaps back the same day, stop a blank policy, and name a missing deputy without keeping the list."
      },
      definition: {
        what: [
          "You are the head for accounts and for HR and office administration, and both managers report to you.",
          "You check their reports against the books and the people files, and you send back anything that does not match.",
          "You stop a money or pay decision when you cannot find it in a written policy, and you send that question to the CEO.",
          "You write one deputy’s name that morning if a manager is away and forgot to name one."
        ],
        why: [
          "Money and people files are how the company keeps its promise after a job is done and after a person joins.",
          "A daily list with no second pair of eyes slowly fills with guesses.",
          "An amount that is not in a policy, once typed, becomes a debt or a pay promise the company did not agree."
        ]
      },
      responsibilities: [
        card("Books", "Check that money in and money out match the records, line by line where it matters", "A tidy sentence in a report can hide a missing receipt or a booking marked paid too early.", "Read the Finance and Accounts Manager’s report. For anything late, missing, or different from the booking, open the entry itself. The entry should show who paid or was paid, the booking or the bill, the amount, the date, and who holds the cash if it is cash. A cash note from Operations is recorded the same working day. You do not rewrite the booking to make the books match a wish. If the books and the admin panel disagree, the booking stays open and the manager corrects the entry or sends it back.", ["You look at the record, not only at the total.", "You do not accept a report that says paid while either side of the settlement is still open.", "A difference is corrected or marked open the same working day. It is not left for a monthly cleanup."], "ctl-books.png"),
        card("People", "Check that each person has one file, one role, and a hire that started from a written gap", "A missing file means the next person will guess the role, the pay, and whether the papers exist.", "Read the HR and Administration Manager’s report. Every person who joined should have a written gap from the head who needed them: the seat, and the result that seat must produce. The file should hold the role, the papers the written process asks for, the attendance, and any open request with a next step. Attendance is taken from the day’s record, not from memory. You do not place that person on a customer lead or a provider job. That assignment belongs to Operations, after the file is complete.", ["A busy feeling is not a reason to hire.", "One person, one file. A second informal role in a chat is not a second job.", "If the executive is away, you should still understand the person from the file."], "ctl-people.png"),
        card("Policy", "Stop the item when the written rule does not contain the amount", "A blank rule is not an invitation to remember a number from last time.", "When a price, a booking advance, a discount, a refund, or a pay change reaches you, look in the written policy first. If the amount is there, point the manager at that writing and let them record it. If it is not there, do not type a figure from memory. Write what was asked, who asked, and which file you checked. Send that question to the CEO the same working day. Hold the payment or the promise until a written rule exists. Do not tell the customer, the provider, or the employee a number while you wait.", ["There is no rupee amount in this seat beyond what the policy already says.", "A temporary number is still an invented number.", "You do not negotiate the amount in a side conversation."], "ctl-receipt.png"),
        card("Leave", "Name a deputy that morning if the manager did not, and then leave their list alone", "An open list with no name gets picked. People take the easy item and the cash note waits.", "If the Finance and Accounts Manager or the HR and Administration Manager is away, look for the deputy name and the dates they wrote before the leave. If the name is there, you do nothing with the list. If the name is blank, write one name that morning, before the list opens, and write the date. Tell that person they assign only. They do not keep the best items. They do not approve pay, a price, a discount, or a refund. After the name is written, you stop. When the manager returns, they read the urgent items from those days and take the list back.", ["Until the name is written, new work in that list waits. It does not get picked.", "You do not become the deputy yourself and then keep assigning for the rest of the leave.", "If you are the one going away, you name one of the two managers before you leave, with the same limits."], "role-hof.png")
      ],
      handoffs: [
        side(card("From Operations", "A cash note and the settlements, written so you do not have to guess", "The job is not closed in the books while cash is still open or a field is missing.", "You receive the cash note the same working day. It must name the customer, the booking, the amount, the provider, the date, and who is holding the cash. If a field is missing, it goes back to Operations the same day. You do not fill the field from a phone call you cannot attach. Accounts records a complete note. The booking stays open in the books until the customer side and the provider side are both settled in the admin panel.", ["An incomplete note is a return, not a partial entry that looks finished.", "You do not change the booking story to match the cash you wish had arrived."], "ctl-receipt.png"), "in"),
        side(card("From the two managers", "Reports that point at the actual records", "You cannot check a story, and you should not have to rebuild their day from chats.", "Each manager gives you what the records show: money in, money out, what is still open, hires, incomplete files, and open people requests. Each important line should point at an entry or a file. If it does not, you send that line back the same working day and you ask them to show the record.", ["Late and missing items are part of the report. A report with no bad news is not automatically a good report.", "You do not collect the raw invoices yourself unless their seat is empty, and then you write that."], "ctl-books.png"), "in"),
        side(card("To the CEO", "A question when no written rule exists", "Only the CEO can set a new company rule about money or pay.", "If you have checked the policy and the amount is not there, send the question the same working day. Say what was asked, who asked, and that the writing is blank. Do not recommend a number unless the CEO asks you to set out choices without picking one in the books. Do not post the amount while you wait.", ["The handoff is the written question, not a hallway comment.", "You tell the manager the item is held, so they do not post it underneath you."], "role-ceo.png"), "out"),
        side(card("To a manager or a deputy", "One named person for the list", "The list needs one name, written, before anyone starts.", "When you name a deputy, write the name, the dates, and the limit: they assign, they do not approve pay, a price, or a refund. Give that writing to them and to the team the same morning. When a manager returns, the handoff back is the list of items assigned while they were away, especially anything that could not wait.", ["One name. Not a group who will sort it out.", "You do not keep a private copy of the best items."], "ctl-people.png"), "out")
      ],
      workflow: [
        card("Step 1", "Open both reports and start with what is late or missing", "The totals can look calm while one cash note or one empty people file is sitting underneath.", "Open the money report and the people report. Make a short list of lines that have no record, lines that disagree with a booking, and files a new person could not continue. Do this every week, and the same day if a manager tells you something cannot wait. Do not start by posting a few invoices yourself.", ["Write the list down. A mental list disappears.", "Silence from a manager is a gap. Ask for the report the same day."], "ctl-books.png"),
        card("Step 2", "Send each gap back the same working day", "A gap you keep in your head becomes your private knowledge, and the system cannot run without you.", "Ask the manager to show the entry, the receipt, the people file, or the written gap. Name the missing field. Do not fill it yourself unless their seat is empty. If the seat is empty, do the day’s urgent items and write that you are covering because the seat is empty. The next day, stop covering.", ["Say which field is missing, so the return is usable.", "Do not accept a corrected story that still has no record."], "ctl-receipt.png"),
        card("Step 3", "Hold the item when the policy is blank", "No written amount means no payment and no promise.", "Point at the written rule if it exists, and let the manager record that amount. If it does not exist, hold the item and send the question to the CEO the same working day. Tell the manager not to post a temporary figure. Do not tell the outside person a number.", ["The hold is written, so the item does not vanish.", "You do not negotiate a middle figure."], "role-hof.png"),
        card("Step 4", "Write a missing deputy’s name that morning, then stop", "You are the checker. You are not the substitute queue for the rest of the leave.", "Look for the name before the list opens. If it is blank, write one name and the date. Tell them the limit: assign only, do not keep items, do not approve money. Then leave the list. When the manager is back, they read the urgent items and take the list. You read their next report as usual.", ["New work waits until the name exists.", "You do not pick the difficult items for yourself and leave the rest."], "ctl-people.png")
      ],
      reporting: [
        timed(card("To the CEO", "Money and people, each line pointing at a record", "The CEO should see the truth without opening every invoice and every people file.", "Each week, show the same four money lines Customer Experience writes. The booking advance, exactly as the written payment policy says. The customer payment, as written in the admin panel. The provider settlement, as written in the admin panel. Cash still open, with who holds it. Do not add a fifth line called revenue. Also write which cash notes are not yet in the books, which people files are incomplete, and any amount you refused to invent. Point each line at the entry or the file. Send this every week. If money or a people file cannot wait, send that part the same working day, and do not save it for the weekly note.", ["A line with no record is not ready to send. Send the gap back first, or mark it as unchecked.", "Include what you stopped. A clean report that hides a blank policy is a miss."], "ctl-books.png"), "Each week, and the same day if money or a people file cannot wait"),
        timed(card("For the system", "The log of deputies and policy stops", "Leave and blank rules are where these two systems break, so they need their own record.", "Write the deputy’s name and the dates whenever a manager is away. Write each policy stop: what was asked, who asked, the date, and whether it went to the CEO. Another person should be able to see why an amount was not posted, without calling you.", ["Write it the same working day, not at the end of the month.", "When the CEO answers, add the answer to the same note, and tell the manager."], "role-hof.png"), "The same working day")
      ],
      standards: [
        card("The report matches the records", "You do not pass on a report you have not been able to tie to the files", "A rounded story becomes the number everyone repeats.", "If the report and the books disagree, or the report and the people files disagree, the report goes back the same working day. You do not smooth the difference in your own note to the CEO. The corrected report, or a clearly open item, is what moves forward.", [], "ctl-books.png"),
        card("Cash is written the day it is noted", "A cash note from Operations is in the books before that working day ends", "Cash that is only in a pocket cannot be settled, and the provider or the customer will ask again.", "The books show the customer, the booking, the amount, the provider, the date, and who holds the cash. The booking stays open until both sides are settled in the admin panel. A note that arrives incomplete goes back the same day. It is not half-posted.", [], "ctl-receipt.png"),
        card("Hiring starts from a written gap", "Nobody joins because the week felt busy", "A person without a seat has no result, and the file will be invented later.", "The head who needs the person has written the seat and the result before HR opens the hire. You check that both are present. You do not waive the gap to be kind, and you do not let someone start the work of the seat while the file is empty.", [], "ctl-people.png"),
        card("No invented number", "An amount that is not written is not posted and not promised", "A guessed salary, discount, or refund becomes a debt.", "You do not write a price, a pay change, a discount, a refund, or a booking advance that is not already in a written policy. There is no minute clock and no private rupee figure on this seat. The same working day, the item is either recorded from the policy or held and sent to the CEO.", [], "role-hof.png")
      ],
      kpis: [
        kpi("You accept a manager’s report only when it matches the records", "Every report you accept", "A report that does not match the file will be used to make the next decision, and the decision will be wrong.", "Before you accept the money report or the people report, open the records behind the lines that matter. If you cannot point at the entry or the file, send that line back the same working day and do not include it as fact in your note to the CEO."),
        kpi("Every cash note is in the books the same working day", "Every cash note", "Unrecorded cash cannot be settled, and the booking will look finished when money is still loose.", "Take the cash notes Operations sent today. Each complete note is in the books before the working day ends. The booking remains open until both sides are settled. An incomplete note was sent back the same day, with the missing field named."),
        kpi("You did not invent an amount", "None", "A guessed figure is a promise the company did not make, and it is very hard to take back once someone has heard it.", "Look at what you wrote or approved. Every price, pay change, discount, refund, and booking advance is already in a written policy. If one is not, it should be on the held list, sent to the CEO, and not posted."),
        kpi("A missing deputy is named before the list opens", "Every leave where the name was blank", "A blank name means people pick their own work, and the urgent item waits.", "On a morning when a manager is away, check that one deputy and the dates are written. If they are not, you write one name before that list opens, and you do not keep the items. New work waits until the name exists."),
        kpi("A people file you check can be continued by someone else", "Every file you check", "The file is the system. If only one person understands it, the person is not really on the books.", "Open the file. You should see the role, the papers the written process requires, and the open requests with a next step. If a paper is missing, the file says so, and it was sent back the same day. You do not call it complete while a required paper is blank.")
      ],
      escalations: [
        timed(card("To the CEO", "There is no written policy for the amount", "You are not allowed to invent company money or company pay.", "Write what was asked, who asked, and that you checked the policy and it is blank. Send it before the working day ends. Hold the payment or the promise. Tell the manager so they do not post a temporary number underneath you. Do not suggest a figure in the books while you wait.", [], "role-ceo.png"), "The same working day"),
        timed(card("Back to Operations", "A booking is marked finished while money is still open", "The job is not finished, and closing it in the books would hide the debt.", "Send it back the same working day. Say whether the customer side, the provider side, or the cash note is open. Do not close it in the books to match a status someone typed too early.", [], "ctl-receipt.png"), "The same working day"),
        timed(card("Back to the manager", "The report has a line with no record", "You will not guess the missing line, and you will not pass it upward as fact.", "Name the line and the missing record. Send it back the same working day. Leave it out of the note you send to the CEO until the manager shows the record.", [], "ctl-books.png"), "The same working day")
      ],
      glossary: glossMoney.concat(glossPeople)
    }
  }, "control");


  addRole({
    id: "fam",
    name: "Finance & Accounts Manager",
    reportsTo: "Head of Finance and People",
    hero: "role-fam.png",
    icon: "fpc",
    scene: "fpc",
    result: "Every payment that happened has one owner and is written in the books, and a booking is treated as paid only when the record shows that both the customer side and the provider side are settled.",
    what: "You run the accounts list. Money items arrive as receipts, invoices, cash notes from Operations, and settlements. They sit in one list until you write one person’s name on each open item. The Accounts Executive then writes the entry. You check that the entry matches the booking or the bill. You do not invent a price, a discount, a refund, or a booking advance. If the amount is not in the written policy, you send it to the Head of Finance and People the same working day and you do not post it.",
    why: "If people pick their own payments, the easy receipt is written twice and the cash note waits. If the books say paid while the admin panel still shows one side open, the company will argue with a customer or a provider about money that is not finished. If you are the only person who can explain an entry, the books stop when you are away.",
    how: "At the start of the working day, open the list of money that is not yet fully written: receipts, invoices, cash notes, and settlements. Write one owner on each open item. Give the next item to the person on shift who has the fewest open items. Do not keep the clearest ones for yourself. The executive writes who paid or was paid, the booking or the bill, the amount, the date, and who holds the cash. You check those fields against the booking. If a field is missing, it goes back the same day. If the amount is not in the approved price file or the payment policy, you do not post a guessed number. Before you go on leave, name the Accounts Executive as deputy, with the dates, in writing.",
    acting: "The deputy assigns the day’s entries and writes that you are on leave. The deputy does not keep the items, and does not approve a price, a discount, a refund, or a pay change. If you are already away and no deputy was named, the Head of Finance and People writes one name that morning. Until that name is written, new entries wait. They are not picked from the pile. When you return, you read the open items from those days, especially anything held for a missing policy, and you take the list back.",
    lanes: [
      { kicker: "Part 1", title: "Give every open money item one owner", work: "New receipts, invoices, cash notes, and settlements sit in one list. They do not have an owner until you write a name. You do not ask who wants the next one. You write the name of the person on shift who has the fewest open items. Inside that, the older item is assigned before a newer one. The Accounts Executive works only the items that already have their name. They do not open the list to choose.", result: "Every open money item has one name before anyone starts writing it.", who: "You. If you are on leave, the one deputy you named before you left.", art: "ctl-books.png" },
      { kicker: "Part 2", title: "Check that the books match what really happened", work: "When the executive says an item is written, open it. You should see who paid or was paid, the booking or the bill, the amount, the date, and, if it is cash, who is holding it. Compare that with the booking in the admin panel and with the cash note if Operations sent one. A cash note is written the same working day. The booking stays open until the customer side and the provider side are both settled. If the entry and the booking disagree, send it back the same day or mark it open with the reason. Do not smooth the difference.", result: "Another accounts person can see what was paid, what is still open, and which booking it belongs to, without calling you.", who: "The executive writes the entry. You check it before it is treated as true.", art: "ctl-receipt.png" },
      { kicker: "Part 3", title: "Stop an amount that is not in the written policy", work: "If the price, the booking advance, the discount, or the refund is not in the approved price file or the written payment policy, do not post a number you remember. Write what was asked, who asked, and that you looked and did not find it. Send that note to the Head of Finance and People the same working day. Tell the executive not to post a temporary figure. The item stays open until a written rule exists.", result: "The books contain only amounts the company has already approved in writing.", who: "You stop the item. The head decides whether a written rule exists or whether it must go to the CEO.", art: "role-fam.png" }
    ],
    owns: [
      "The day’s money list, with one named owner on every open receipt, invoice, cash note, and settlement",
      "A check that each entry matches the booking or the bill, including who holds any cash",
      "A same-day stop when an amount is not in the approved price file or the payment policy",
      "The deputy’s name and the dates, written before your leave, so the list does not open unnamed"
    ],
    mustNot: [
      "Let the Accounts Executive, or anyone else, pick which payment to write from a shared pile",
      "Invent a price, a discount, a refund, or a booking advance because the policy line is blank",
      "Mark a booking paid while the customer side or the provider side is still open in the admin panel",
      "Approve a price, a discount, a refund, or a pay change while only a deputy is covering your leave",
      "Close the working day while a cash note from that day is still missing from the books",
      "Keep the easiest items for yourself and leave the cash notes for someone else"
    ],
    good: [
      "Each open payment had one name before work started on it, and the older open item was not skipped for a new easy receipt.",
      "A cash note from today is in the books today, with the booking still open if either side is unsettled.",
      "An amount that was not in the policy was sent to the head, and nobody posted a guess.",
      "You were away, one named deputy assigned the list, and you took it back by reading the open items."
    ],
    bad: [
      "Two people posted the same receipt, or a cash note has no name on it.",
      "The admin panel says the booking is paid and the books do not, or the books say paid and one side is still open.",
      "You typed a discount you could not find in the written policy.",
      "The next person cannot tell which booking a payment belongs to without calling you."
    ],
    records: [
      "The day’s list: each receipt, invoice, cash note, and settlement, the owner, and whether it is written, returned, or held",
      "The entry itself: who, the booking or the bill, the amount, the date, and who holds the cash",
      "The cash note fields as Operations wrote them, and the date you recorded them",
      "Items sent back because a field was missing, with the field named",
      "Items held because the amount is not in the written policy, and the date you sent them to the head",
      "The deputy’s name and the dates, written before leave"
    ],
    rules: [
      "One item, one name. The name is written by you, or by the deputy while you are on leave. Nobody else chooses.",
      "The executive writes the entry. You check it. You do not become the only person who can explain the books.",
      "Do not invent a minute clock or a rupee amount. Use the written policy. If it is blank, the item waits.",
      "A booking is not paid in the books until both sides are settled in the admin panel.",
      "Today’s money is written before the working day ends, or it is handed to the person still on shift with the next step written down."
    ],
    detailed: {
      copy: {
        definition: "You run the accounts list. You give each open money item to one person, and you check that the books match the booking. You do not invent amounts.",
        lanes: "Assign the day’s entries, check them against the booking, and stop any amount that is not already in a written policy.",
        workflow: "Open the list, write one owner, check what they wrote, and either leave the booking open, accept the entry, or send a blank policy to the head."
      },
      definition: {
        what: [
          "You own the list of receipts, invoices, cash notes, and settlements that are not yet finished in the books.",
          "You write one owner on each open item, and the Accounts Executive writes only those named items.",
          "You compare the entry with the booking, and you refuse a number that is not in the written policy.",
          "You name a deputy before leave so the list still has an assigner."
        ],
        why: [
          "Unassigned money is how a payment is lost or written twice.",
          "A guessed discount becomes a debt the company did not agree to.",
          "If the entry is only in your memory, the books cannot be closed when you are away."
        ]
      },
      responsibilities: [
        card("The list", "Write one owner on every open money item before anyone starts", "A shared pile means the easy receipt gets done and the cash note waits until someone feels like it.", "Each working day, list every receipt, invoice, cash note, and settlement that is still open. Write one name on each. Choose the person on shift who already has the fewest open items. When two people have the same number, give the older item first. Do not ask the team who wants it. Do not keep the clearest payments for yourself. If you are on leave, the deputy does this and does not keep the items either.", ["The name is written before the executive opens the item.", "An item with no name is not started.", "You can see, from the list alone, who has what."], "ctl-books.png"),
        card("The check", "Match the written entry to the booking or the bill", "An entry with no booking is a loose payment, and a later argument will have nowhere to land.", "Open what the executive wrote. You need the person who paid or was paid, the booking or the bill, the amount, the date, and who holds the cash if it is cash. If any of those is missing, send the item back the same working day and name the missing field. Do not fill it from a conversation you cannot attach to the file. If the amount does not match the booking or the cash note, the item stays open until it is corrected.", ["Checking is your work. Writing is the executive’s work.", "A complete-looking entry that names the wrong booking is not complete.", "The next person should not need to call the executive to understand the line."], "ctl-receipt.png"),
        card("Open money", "Leave the booking open until both sides are settled", "Paid on one side is not a finished job, and the books must not pretend that it is.", "Look at the admin panel before you treat a booking as paid. The customer payment and the provider settlement both need to be written there. If Operations has sent a cash note, that note is in the books, and it does not by itself close the booking. If either side is still open, the books stay open on that booking. Do not change the booking story so your report looks finished.", ["Cash in someone’s pocket is not settled until the note says who holds it and both sides are closed properly.", "You send a too-early “paid” status back to Operations the same day."], "role-fam.png"),
        card("A blank policy", "Stop and ask the head, and do not post a temporary number", "You are not allowed to invent the figure, even for one day.", "When the price, the advance, the discount, or the refund is not in the approved price file or the payment policy, write what was asked and who asked. Send it to the Head of Finance and People the same working day. Tell the executive the item is held. Do not post a round number to keep the customer quiet. When the head or the CEO points at a written rule, then the amount may be recorded.", ["The held item stays on the list with the reason.", "You do not negotiate the amount with the customer or the provider."], "role-hof.png")
      ],
      handoffs: [
        side(card("From Operations", "Settlements and cash notes that are complete enough to record", "You cannot record money you were not told about in writing, and you cannot invent the missing field.", "Take the written note. It should name the customer, the booking, the amount, the provider, the date, and who holds the cash. If one of those is missing, send it back the same working day and say which field. Do not guess it from a chat. A complete note is assigned like any other money item, and it is written the same day.", ["A verbal “they paid cash” is not a cash note.", "You do not change their booking to match a number you prefer."], "ctl-receipt.png"), "in"),
        side(card("To the Accounts Executive", "Only items that already have their name", "They must not fish in the list, or the urgent note will be left behind.", "Give them the item with the booking or the bill it belongs to, and with any cash note attached. If you do not yet know the booking, do not assign it as ready. Send it back to the person who raised it. The executive returns a finished entry or a named missing field the same working day.", ["One item, one name.", "You do not assign a pile and tell them to sort it."], "role-ace.png"), "out"),
        side(card("To the Head of Finance and People", "An amount the policy does not contain", "They decide whether a written rule exists. You do not decide the company’s money.", "Send the question the same working day. Include what was asked, who asked, and where you looked. Do not post a temporary number while you wait. Tell the executive the item is held so it is not written underneath the question.", ["The handoff is the written question.", "When the answer comes, you record the written amount, not a remembered version of the answer."], "role-hof.png"), "out"),
        side(card("While you are away", "One deputy, and a list they can hand back", "The day you return, you need to see what was assigned and what was held.", "Before leave, write the Accounts Executive’s name and the dates. They assign and they write that you are on leave. They do not approve money. The handoff back to you is the list of open items, returns, and policy holds from those dates. You read the holds first.", ["If the name was blank, the head writes one name that morning and new entries wait until then.", "The deputy stops assigning when you are back."], "ctl-people.png"), "out")
      ],
      workflow: [
        card("Step 1", "Open the day’s list before anyone writes", "Nothing is posted from memory, and nothing is chosen because it looks quick.", "Bring together the receipts, the invoices, the cash notes, and the settlements that are still open, including anything left from the previous day. This is one list. If an item is only in a chat, it is not on the list until someone writes the fields the record needs.", ["Older open items stay visible. They are not pushed down by new receipts.", "A missing list at the start of the day means people will pick."], "ctl-books.png"),
        card("Step 2", "Write one owner on each open item", "One item, one name, chosen by the count of open work, not by preference.", "The person on shift with the fewest open items is next. You do not keep the best ones. You write the name on the item. The executive then knows what is theirs. Items you have not named are not started.", ["Say the order: older first when the load is equal.", "If you are the one on leave, this step belongs to the deputy, with the same rule."], "role-fam.png"),
        card("Step 3", "Check what was written before you treat it as true", "Writing an entry is not the same as the entry being right.", "Match the amount, the date, the booking or the bill, and who holds the cash. If something does not match, send it back the same day with the reason. Do not correct it silently in a way the executive cannot see, unless you write what you changed and why.", ["A wrong booking number is a failed entry even if the amount looks familiar.", "The executive should see the return, so the same gap is not repeated in silence."], "ctl-receipt.png"),
        card("Step 4", "Close only what is really settled, and hold the rest", "Open stays open. A blank policy stays unposted.", "If both sides are settled in the admin panel and the entry matches, the record can show paid. If either side is open, it stays open. If the amount is not in the policy, it goes to the head and stays off the books. Before you finish the day, every item from today is written, returned, held, or handed to the person still on shift with the next step.", ["There is no minute clock. The line is before the working day ends.", "You do not leave a cash note for tomorrow because the receipt was easier."], "role-hof.png")
      ],
      reporting: [
        timed(card("To the head", "What came in, what went out, and what is still open", "The head should see the gap without opening every bill, and without hearing a smoothed story.", "Each working day, show the open items: who owns them, which are cash notes, and which are held for a missing policy. Each week, show the same four money lines Customer Experience writes. The booking advance, exactly as the written payment policy says. The customer payment, as written in the admin panel. The provider settlement, as written in the admin panel. Cash still open, with who holds it. Do not add a fifth line called revenue. Point at the records. If you could not check a line, say so. Do not turn an open item into a paid one to make the week look calm.", ["Open items are part of the report. Hiding them is a miss.", "A policy hold is named, not buried in a total."], "ctl-books.png"), "Each working day for open items, and each week for the full picture"),
        timed(card("The list itself", "Owner, status, and the next step", "This is what lets you be away without the books stopping.", "Each open item shows the owner, whether it is waiting, written, returned, or held, and the next step. The executive updates their items the same day. You can hand this list to a deputy or pick it up on your return without a briefing call.", ["An item with no next step is not really assigned.", "The list is the handover. A separate verbal handover is not enough."], "role-ace.png"), "Each working day")
      ],
      standards: [
        card("Assignment", "No shared pile, and no choosing", "Picking is how a cash note is skipped.", "Every open item has one name before work starts on it. The name is written by you or by the named deputy. The executive does not open the unassigned list to select work. You do not keep the easiest items.", [], "ctl-books.png"),
        card("The same working day", "Today’s money is written today, or handed on in writing", "There is no minute clock. A payment left only in memory until tomorrow gets lost.", "Before the working day ends, today’s receipts, invoices, and cash notes are in the books, returned with a missing field, held for policy, or passed to the person still on shift with the next step written. Leaving them in a chat does not count.", [], "ctl-receipt.png"),
        card("One story", "The booking and the books say the same thing", "Two stories means someone will pay twice, or will not be paid at all.", "Amount, date, and booking or bill match. A difference is corrected the same working day, or marked open with the reason. You do not force the booking to match the books, and you do not force the books to say paid early.", [], "role-fam.png"),
        card("Only written amounts", "Memory is not a price file", "A number you remember from last month may have been a one-time decision, or it may be wrong.", "If the amount is not in the approved price file or the payment policy, it is not posted. It is sent to the head the same day. A temporary posting is not allowed.", [], "role-hof.png")
      ],
      kpis: [
        kpi("Every open money item has one owner before work starts", "Every open item", "A pile has no owner, so the hard payment waits while two people do the easy one.", "Before anyone writes an entry, look at the list. Each receipt, invoice, cash note, and settlement that is still open has one name. An item without a name has not been started."),
        kpi("Today’s items are written, returned, or held before the day ends", "Every item that happened today", "A payment left to tomorrow, with no next step, is how money disappears from the record.", "Take what arrived today. Before the working day ends, each one is in the books, back with a missing field named, held because the policy is blank, or handed to the person still on shift with the next step written."),
        kpi("The books match the booking on every item you check", "Every item you check", "A mismatch becomes an argument with a customer or a provider about money.", "For each entry you check, the amount, the date, and the booking or bill match the admin panel or the bill. A mismatch is sent back or marked open the same day, with the reason written."),
        kpi("You posted no amount that you guessed", "None", "A guessed refund or discount is company money given away without a written rule.", "Every price, discount, refund, and booking advance in the books is already in the written policy. Anything else is on the held list and has been sent to the head. Nothing was posted as a temporary figure."),
        kpi("A deputy is named before your leave starts", "Every leave", "Otherwise the day’s money waits, or people start picking.", "Before the leave, one name and the dates are written. That person assigns and does not approve money. If the name was still blank on the morning, the head names one person before the list opens, and new entries wait until then.")
      ],
      escalations: [
        timed(card("To the head", "The amount is not in the policy", "You must not invent it, and the executive must not invent it under you.", "Write what was asked, who asked, and which file you checked. Send that note to the Head of Finance and People before the working day ends. Do not post the item, and do not let the executive post a temporary number. Tell them the item is held until a written rule comes back.", [], "role-hof.png"), "The same working day"),
        timed(card("Back to Operations", "The cash note is missing a field", "An incomplete note cannot be recorded as the truth, or the books will contain a guess.", "Send it back the same working day. Name the missing field: customer, booking, amount, provider, date, or who holds the cash. Do not fill it yourself.", [], "ctl-receipt.png"), "The same working day"),
        timed(card("To the head", "You are away and no deputy was named", "The list must not open with nobody assigned, or people will pick.", "The head writes one name that morning. Until that name is written, new entries wait. You do not expect the team to sort the pile among themselves.", [], "ctl-people.png"), "That morning, before the list opens")
      ],
      glossary: glossMoney.concat(glossPeople)
    }
  }, "control");

  addRole({
    id: "ace",
    name: "Accounts Executive",
    reportsTo: "Finance & Accounts Manager",
    hero: "role-ace.png",
    icon: "fpc",
    scene: "fpc",
    result: "Every money item that has your name is written in full in the books the same working day, or sent back with the missing field named, so another person can continue it without calling you.",
    what: "You write the receipts, invoices, cash notes, and settlements that the Finance and Accounts Manager has already put on your name. You do not open the shared list and choose the next payment. You do not decide a price, a discount, a refund, or a booking advance. You write what already happened, under an amount that is already in the written policy. If a field is missing, you send the item back the same working day.",
    why: "The books are how the company knows it was paid and what it still owes. If the entry lives in your head, or in a chat, the manager cannot close the day and the next person cannot continue. A guessed amount looks finished and creates a debt. An easy receipt done first, while an older cash note with your name sits open, is how cash gets lost.",
    how: "At the start of your work, open only the items that already have your name. Do the oldest open item first. For each one, write who paid or was paid, the booking or the bill, the amount, the date, and who is holding the cash if it is cash. Link it so the next person can find the booking from the entry and the entry from the booking. If any fact is missing, or the amount is not in the written policy, do not fill the gap. Send it back to the manager the same day and say what is missing. If you are the named deputy while the manager is on leave, you assign items to names. You do not keep them for yourself, and you do not approve money.",
    acting: "If nobody is in this seat, the Finance and Accounts Manager writes the day’s entries and writes that they are doing it because the seat is empty. If you are the deputy, you only assign for the written dates. When the manager returns, you stop assigning and you go back to writing the items that have your name.",
    lanes: [
      { kicker: "Part 1", title: "Write only the items that already have your name", work: "Do not open the shared list to choose a payment that looks quick. Open the items the manager assigned to you. Work the oldest open item first. Do not start a new receipt while an older cash note with your name is still open. If a new item appears on your name during the day, finish the entry you are in, then take the older open item before the new one.", result: "Nothing you wrote was a payment you picked for yourself, and older open items were not skipped.", who: "You. The manager, or the deputy, is the only person who puts your name on an item.", art: "role-ace.png" },
      { kicker: "Part 2", title: "Make the entry complete enough for the next person", work: "On every item, write who paid or was paid, the booking or the bill it belongs to, the amount, the date, and who holds the cash if the money is cash. Use the amount already written in the policy or on the cash note. Attach or link the note so the manager can open the booking and see this entry. When you finish, the test is this: another accounts person can continue or check the line without calling you.", result: "The entry can be checked, and the day can be continued, without you in the room.", who: "You write it. The manager checks it.", art: "ctl-receipt.png" },
      { kicker: "Part 3", title: "Send back anything you cannot truthfully write", work: "If the booking or the bill is missing, if the cash note is missing a field, or if you cannot find the amount in the written policy, stop. Write which fact is missing. Send the item back to the Finance and Accounts Manager the same working day. Do not type a likely amount. Do not ask the customer or the provider to agree a new figure. Do not mark the booking paid.", result: "The books do not contain a guess with your name on it.", who: "You send it back. The manager decides the next step. A blank policy goes to the head, not to you.", art: "ctl-books.png" }
    ],
    owns: [
      "The entries for money items that already have your name, oldest open item first",
      "A complete record on each of those entries: who, booking or bill, amount, date, and who holds cash",
      "A same-day return when the item is incomplete or the amount is not in the written policy",
      "While you are the named deputy: assignment only, with the manager’s leave written on the item, and no approval of money"
    ],
    mustNot: [
      "Pick an item from the shared list, or skip an older cash note on your name to do a new receipt",
      "Invent a price, a discount, a refund, or a booking advance",
      "Mark a booking paid while the customer side or the provider side is still open",
      "Keep items for yourself while you are the deputy, instead of writing another person’s name",
      "Approve a price, a discount, or a refund while the manager is on leave",
      "Leave the entry so incomplete that the next person has to call you"
    ],
    good: [
      "Every item with your name was written in full, or sent back with the missing field named, before the working day ended.",
      "A person who did not see you work can open the entry and see who, which booking, what amount, what date, and where the cash is.",
      "You did not guess a blank field, and you did not tell anyone a number that was not already written.",
      "As deputy, you assigned the list and did not keep the easy items."
    ],
    bad: [
      "You chose an easy receipt and left a cash note with your name unwritten.",
      "The amount in the books is not the amount in the policy or on the note.",
      "The booking is marked paid and one side is still open.",
      "The next person has to call you to understand the entry."
    ],
    records: [
      "Who paid or was paid",
      "The booking or the bill the money belongs to",
      "The amount, taken from the written policy, the invoice, or the cash note, not from memory",
      "The date the money moved",
      "Who is holding the cash, if it is cash",
      "The return note when you sent an item back: which field is missing, and the date"
    ],
    rules: [
      "If it does not already have your name, it is not yours to write. Do not open the pile.",
      "A blank field is a return the same working day. It is not a guess.",
      "You record money that already happened under a written rule. You do not decide money.",
      "Oldest open item with your name first. A new easy receipt waits.",
      "You do not mark a booking paid until both the customer side and the provider side are settled in the admin panel."
    ],
    detailed: {
      copy: {
        definition: "You write the day’s accounts for the items that already have your name. You make each entry complete, and you send back anything you would have to guess. The manager checks your work.",
        lanes: "Named items only, a complete entry, and an honest return the same day.",
        workflow: "Open your items, oldest first. Write the five facts. Return what is incomplete. Leave the booking open if either side of the money is open."
      },
      definition: {
        what: [
          "You post the receipts, invoices, cash notes, and settlements the manager has assigned to you.",
          "You write every fact the next person needs, and you link the entry to the booking or the bill.",
          "You stop and send the item back when a fact is missing or the amount is not in the written policy."
        ],
        why: [
          "The manager cannot close the books from your memory.",
          "A guessed entry is worse than a late entry, because it looks finished.",
          "If you pick your own work, the cash note waits."
        ]
      },
      responsibilities: [
        card("Your name", "Work the items you were given, oldest open item first", "Choosing your own work hides the hard payment, and skipping an old cash note loses the cash.", "Open only the list that already has your name. Sort it so the oldest open item is first. Finish that before you start a newer receipt. If you are halfway through an entry when a new item is assigned, finish the entry you are in, then go back to the oldest open item. Do not browse the unassigned pile, even if you can see it.", ["An item without your name is not started.", "You can explain, from the list, why you did this item before that one.", "If the manager is on leave and you are the deputy, you assign first, and you still do not keep the best items."], "role-ace.png"),
        card("The entry", "Write the five facts every time, in the books, not in a side note", "A missing booking number means the payment is loose, and the next person will not know what it was for.", "Write who paid or was paid, the booking or the bill, the amount, the date, and who holds the cash if it is cash. Take the amount from the policy, the invoice, or the cash note. Link the entry so someone can open the booking and find this line. When you have done that, hand it to the manager as written. Do not keep a private notebook that has facts the books do not have.", ["Five facts, every item. If one is unknown, you are in the return step, not the writing step.", "The next person is the test. If they must call you, the entry is not finished.", "Cash without a name for who holds it is not finished."], "ctl-receipt.png"),
        card("The return", "Send an incomplete item back the same working day, with the gap named", "Waiting until you remember the missing fact is how the item is lost.", "Write which field is missing, or write that the amount is not in the policy. Send that to the Finance and Accounts Manager before the working day ends. Do not fill the gap from a chat you cannot attach. Do not ask the customer to confirm a number you invented. Leave the item off the books until it comes back complete.", ["Name the field. “Incomplete” alone is not a return the manager can use.", "A return the same day is a completed step. Silence is not.", "You do not get into trouble for returning a guess. You get into trouble for posting one."], "ctl-books.png"),
        card("Deputy", "If you are named, assign the list and do not keep it", "The deputy who keeps the best items is picking, which is the thing this seat is not allowed to do.", "Write one owner on each open item. Write that it was assigned while the manager is on leave, and write the date. Give the next item to the person with the fewest open items. Do not put your own name on the easy ones. Do not approve a price, a discount, a refund, or a pay change. If one of those cannot wait, send it to the Head of Finance and People the same day. When the manager returns, you stop assigning.", ["The dates of the leave are the only dates you assign.", "You still write, in the normal way, any item the assignment rule actually gives you.", "You do not change the policy while you are deputy."], "ctl-people.png")
      ],
      handoffs: [
        side(card("From the manager", "An item that already has your name, and enough to write or to return", "You do not fish in the list, and you do not start a blank item as if it were ready.", "Each item arrives with your name. If it has no booking or bill, and no way to see which field is missing, send it back the same day and say so. If the cash note is attached, use those fields. You do not need a verbal briefing to begin, because the writing is the briefing.", ["No name, no work.", "A briefing that is not on the item does not count, because you may be away tomorrow."], "role-fam.png"), "in"),
        side(card("To the manager", "A finished entry, or a return that names the gap", "They cannot check silence, and they cannot close the day from a chat.", "The same working day, the item is either written with the five facts, or back on their list with the missing field named. You do not hold a half entry on your desk overnight without the next step written. If your shift ends first, hand the open item to the person still working, with the next step, and tell the manager you did that.", ["Finished means the five facts are in the books.", "Returned means the missing field is named. It does not mean you gave up quietly."], "ctl-receipt.png"), "out"),
        side(card("To the manager", "An amount that is not in the policy", "You must not type a guess, and you must not promise the figure to anyone.", "Stop. Write that you looked in the written policy and did not find the amount. Send it up the same working day. Do not post a temporary figure. Do not tell the customer, the provider, or a colleague a number while you wait.", ["The manager sends a blank policy to the head. You do not skip the manager and invent the answer.", "The item stays unposted."], "role-hof.png"), "out")
      ],
      workflow: [
        card("Step 1", "Open only the items with your name, oldest first", "The shared pile is not your queue.", "Ignore items that do not have your name. Put your items in order from the oldest open one. That is the item you write next, even if a new receipt looks simpler.", ["If you have no named items, you wait. You do not go and choose.", "If you are deputy, you assign first, under the deputy rules, and then you work only what the rule gave you."], "role-ace.png"),
        card("Step 2", "Write the five facts in the books", "Who, which booking or bill, what amount, what date, and who holds the cash.", "Take the amount from the writing you were given, not from memory. Link the entry to the booking or the bill. Read it once as if you were a new person tomorrow. If you would need to call yourself, it is not done.", ["Do not keep extra facts only in a notebook.", "Cash with nobody named as holding it is incomplete."], "ctl-receipt.png"),
        card("Step 3", "Stop when a fact is missing", "Do not invent it to make the line look finished.", "Name the missing field and return the item to the manager the same working day. This includes an amount you cannot find in the policy. Then move to the next oldest item that you can truthfully write.", ["A return is part of the job, not a failure to finish.", "Do not leave the incomplete item in your own list with no note."], "ctl-books.png"),
        card("Step 4", "Leave the booking open if the money is still open", "One side paid is not both sides settled.", "Look at the admin panel before you would mark anything paid. If the customer side or the provider side is still open, do not mark the booking paid in the books. Write the entry for the money that did move, and leave the booking open. Tell the manager if Operations has marked it finished too early.", ["You record the movement. You do not declare the job financially finished.", "The manager checks this. You still do not mark it early."], "role-fam.png")
      ],
      reporting: [
        timed(card("To the manager", "What you wrote today, and what you sent back", "They close the day from this list, not from asking you what you remember.", "Before the working day ends, list each item that had your name. Say whether it is written, with the booking or bill named, or returned, with the missing field named. If you handed an item to someone still on shift, say who and what the next step is. Do not wait to be asked.", ["An item missing from this list looks abandoned.", "The list matches the books. You do not report an entry you did not actually write."], "ctl-receipt.png"), "Before the working day ends")
      ],
      standards: [
        card("Order", "The oldest open item with your name comes first", "New easy work hides old cash, and old cash is where the company loses track.", "You do not jump the queue you were given. A newer receipt waits until the older open item on your name is written or returned. You can show the order from the dates on the items.", [], "role-ace.png"),
        card("Complete", "Another person can continue the entry without calling you", "That is the test of a finished entry.", "They can see who, what booking or bill, what amount, what date, and where the cash is. The link to the booking is there. If any of that is missing, the item was returned, not posted as finished.", [], "ctl-receipt.png"),
        card("No guess", "A blank field goes back the same working day", "Guessing makes the books look finished when they are not.", "You name the missing field and send the item to the manager before the day ends. You do not fill it from memory, from a chat you cannot attach, or from what seems likely.", [], "ctl-books.png"),
        card("You do not approve money", "You write what already happened under a written rule", "Approval of a new amount belongs to the manager, and a blank policy belongs to the head.", "You do not agree a discount, a refund, a price, or a booking advance. You do not promise one while the manager is on leave, even if you are the deputy. You record an amount only when it is already written.", [], "role-hof.png")
      ],
      kpis: [
        kpi("Every item with your name is written or returned before the day ends", "Every item with your name", "An item left in silence is lost, and the manager cannot see that it was yours.", "Before the working day ends, each item on your name is in the books with the five facts, or back with the manager with the missing field named, or handed to the person still on shift with the next step written."),
        kpi("Another person can continue every entry you write", "Every entry you write", "The file is the handover. A call from you tomorrow is not a handover.", "Who, booking or bill, amount, date, and who holds the cash are all in the books, and the booking can be found from the entry. If you would need to explain it out loud, it is not finished."),
        kpi("You did not invent an amount", "None", "You record. You do not decide. A typed guess becomes company money.", "Nothing you posted is a price, a discount, a refund, or a booking advance that was not already written in the policy, on the invoice, or on the cash note. Blank amounts were returned."),
        kpi("You left open bookings open", "Every booking you touch", "Paid on one side is not a finished settlement, and marking it paid hides what is still owed.", "You did not mark a booking paid while the customer side or the provider side was still open in the admin panel. The money that moved is recorded. The booking stays open.")
      ],
      escalations: [
        timed(card("To the manager", "A field you need is missing", "You cannot guess it, and you should not wait overnight hoping you will remember.", "Send the item back to the Finance and Accounts Manager before the working day ends. Name the missing field, such as the booking, the amount, or who holds the cash. Keep it out of the books until it comes back with that field filled from a real record, not from a guess.", [], "role-fam.png"), "The same working day"),
        timed(card("To the manager", "The amount is not in the written policy", "Posting it would invent company money, and telling someone the number would make the invention a promise.", "Stop and send it up the same working day. Do not post a temporary figure. Do not quote a number to the customer, the provider, or a colleague.", [], "role-hof.png"), "The same working day")
      ],
      glossary: glossMoney
    }
  }, "control");

  addRole({
    id: "hrm",
    name: "HR & Administration Manager",
    reportsTo: "Head of Finance and People",
    hero: "role-hrm.png",
    icon: "hr",
    scene: "hr",
    result: "Every person has one file and one role, every hire starts from a written gap, and the day’s people work has one owner so a new person can continue the file.",
    what: "You run hiring, attendance, and office administration as a written system. A head who needs a person writes the seat and the result that seat must produce. Only then do you open a hire. You give the day’s people tasks to the HR and Administration Executive. You check that each file can be continued by someone else. You do not set pay from memory. You do not put anyone on a customer lead or a provider job.",
    why: "If people are hired because the week feels busy, the company cannot say what they were hired to do. If attendance lives in memory, the next person will write a different day. If pay is promised in a conversation, it becomes a dispute. If you assign a person onto a booking, you have mixed the people file with Operations, and both systems become unclear.",
    how: "When a head asks for a person, read the note. If the seat or the result is missing, send it back the same working day and do not start. If both are there, open one file and assign the papers to one executive. Each working day, attendance gaps and open requests get one owner, oldest first. The executive does not pick. You check the file before you treat the person as ready. A pay question goes to the Head of Finance and People the same day if the amount is not in the written policy. Before leave, name the executive as deputy, with the dates.",
    acting: "The deputy assigns people tasks and does not change pay. If you are already away and no deputy was named, the Head of Finance and People writes one name that morning. Until then, new hiring waits. When you return, you read the open files and take the list back. If this description is being done because the executive seat is empty, you write that you are covering the day’s file because the seat is empty.",
    lanes: [
      { kicker: "Part 1", title: "Open a hire only when the gap is written", work: "The head who needs the person writes the seat that is empty, the result that seat must produce, and proof that the seat’s own result is already happening. You read all three. If any one is missing, you send the note back the same working day and you say which one is missing. You do not open a hire because the office feels busy, and you do not start from a verbal request in a corridor. You do not hire a person into a box whose work is not yet happening. The person above that seat keeps the work and writes, the same day: I am doing that seat today because the seat is empty. The box stays. For Field Marketing Manager, all three must already be happening: stall visits with names going to Customer Experience, first signatures with files going to Provider Experience, and office and society contracts being written. If one of those three is not happening, send that hire back. When the note is complete, you open one file for one seat. You do not hire two people against one unclear note.", result: "Every new person matches a seat that already exists in the written system.", who: "The asking head writes the gap. You accept it or send it back. The executive files the papers after you assign them.", art: "ctl-people.png" },
      { kicker: "Part 2", title: "Keep one file and one role for each person", work: "The file holds the seat, the result, the joining papers the written process asks for, the attendance, and any open request with a next step. One person does not have a second informal role living only in a chat. Attendance for the day is written from the record before the working day ends, not reconstructed from memory the next morning. You read the file as a new person would. If you would need to call the executive to understand it, it is not finished.", result: "If the executive is away tomorrow, the file still tells the truth.", who: "The executive writes the daily file. You check it before you call a hire or a request finished.", art: "role-hrm.png" },
      { kicker: "Part 3", title: "Assign the day’s people work, and stay out of customer jobs", work: "Open requests and attendance gaps sit on one list. You write one owner. The oldest open request is assigned before a new one. The executive does not pick. You do not send the person to do a customer visit, to take a lead, or to cover a provider job. When the file is complete, Operations uses its own rules to assign work. A pay amount that is not in the written policy is not yours to promise. You send it to the Head of Finance and People the same working day.", result: "People administration is finished in the file. Customer work stays in Operations.", who: "You assign. On leave, the named deputy assigns and does not change pay.", art: "role-hre.png" }
    ],
    owns: [
      "Hiring that starts only from a written gap: the seat and the result",
      "One people file and one role for each person, complete enough for someone else to continue",
      "Attendance and open requests, each with one owner, oldest first",
      "A named deputy, with dates, written before your leave"
    ],
    mustNot: [
      "Hire someone when the seat or the result was never written",
      "Invent a salary, a pay change, or a benefit",
      "Put a person on a customer lead, a booking, or a provider job",
      "Let the executive pick people tasks from a shared pile",
      "Change pay, or promise pay, while you are away and only a deputy is assigning",
      "Call a file finished when a required paper is missing and the file does not say so"
    ],
    good: [
      "A new person has a written seat, a written result, and the papers the process requires, all in one file.",
      "Today’s attendance is in the file before the day ends, taken from the record.",
      "An open people request has one owner and a next step.",
      "A pay question was sent to the head, and nobody was told a number that is not in the policy."
    ],
    bad: [
      "Someone started the work of a seat and the file is empty.",
      "Pay was promised in a conversation and never written in the policy.",
      "You assigned a person to cover a booking.",
      "Two people think they own the same request, or nobody does."
    ],
    records: [
      "The written gap: who asked, the seat, the result that seat must produce, and the date",
      "The people file: role, required papers, attendance, and open requests with the next step",
      "Returns you sent because the gap or a paper was missing, with the date",
      "Pay questions you sent to the Head of Finance and People, and the answer when it comes in writing",
      "The deputy’s name and the dates"
    ],
    rules: [
      "A person without a file that someone else can continue is not fully hired, even if they have started coming in.",
      "Pay that is not in a written policy stops with the Head of Finance and People. You do not fill the number.",
      "Operations decides who works a lead. You decide whether the employment file is complete.",
      "One request, one owner. The executive does not pick.",
      "Before leave, name one deputy and the dates. They assign. They do not change pay."
    ],
    detailed: {
      copy: {
        definition: "You own people and office administration. The executive does the daily file for the tasks you assign. You hire only from a written gap, and you do not set pay or assign customer work.",
        lanes: "A written gap before any hire, one file per person, and one owner for each day’s people task.",
        workflow: "Read the gap, open one file, assign the day’s tasks, and check that another person can continue the file."
      },
      definition: {
        what: [
          "You start a hire only after a head has written the empty seat and the result that seat must produce.",
          "You keep attendance, papers, and requests in one file per person, and you assign that daily work to one executive.",
          "You send pay questions upward when the amount is not already written, and you do not assign customer or provider work."
        ],
        why: [
          "The company must know who works here and what they were hired to do, even when you are away.",
          "A side promise on pay becomes a dispute.",
          "A person placed on a booking by HR makes two owners for the same job."
        ]
      },
      responsibilities: [
        card("The gap", "Do not open a hire until the seat and the result are both written", "A busy week is not a new seat, and a corridor request will be remembered differently by each person.", "Read the note from the head who needs the person. It must name the seat, the result that seat must produce, and proof that the seat’s own result is already happening. If any one is missing, send it back the same working day and say which one. Do not collect CVs, do not tell a candidate they are joining, and do not let them start, while the gap is incomplete. Do not hire into an empty seat whose work is not yet happening. The person above that seat does the work and writes: I am doing that seat today because the seat is empty. The box stays. For Field Marketing Manager, stall visits, first signatures, and office and society contracts must each already be happening. If one is not, send the hire back. One written gap is one seat. Do not split it into two hires because two people are available.", ["You can show the written gap for every person who joined.", "A verbal yes from a head is not a gap until it is written.", "You do not write the gap for them and pretend they asked."], "ctl-people.png"),
        card("The file", "One person, one file, one role, written so the next person can continue it", "Two roles mentioned in conversation means no role, and a missing paper means the hire is not finished.", "Put in the file the seat, the result, the joining papers the written process asks for, the attendance, and every open request with a next step. Do not keep a second version in a chat. If a required paper has not arrived, the file says it is missing and who is chasing it. You do not mark the person ready while that line is blank. Read the file as if you were new. If you would need to call someone, it goes back.", ["One file. Old notes that disagree are marked replaced.", "Attendance is part of the file, not a separate memory.", "Ready for Operations means the file is complete, not that the person is simply present."], "role-hrm.png"),
        card("The day", "Assign people tasks so the oldest open request is not buried", "A shared list gets the easy office favour done, and the joining file waits.", "Each working day, list attendance gaps and open requests. Write one owner. The person with the fewest open tasks is next. The oldest request comes before a new one. The executive does not choose. You check what they filed before you call the task done. If you are on leave, the deputy assigns in the same way and does not keep the tasks.", ["One task, one name, written before they start.", "A joining file that is older than a new stationery request comes first.", "You can see the list without asking who is doing what."], "role-hre.png"),
        card("Pay", "Do not invent it, and do not let the executive say a number", "Salary lives in the written policy. A number said out loud becomes the number people expect.", "When someone asks for a salary, a change, or a benefit, look at the written policy. If the amount is there, the file may record that written amount. If it is not, send the question to the Head of Finance and People the same working day. Tell the executive not to quote a figure. Do not negotiate with the person while you wait. When a written answer arrives, put that answer in the file.", ["A held pay question is written on the file, so it is not forgotten.", "You do not backdate a verbal promise.", "The deputy has the same limit. Leave does not create a power to set pay."], "ctl-receipt.png")
      ],
      handoffs: [
        side(card("From a head", "A written gap you can accept or return", "You cannot hire a feeling, and you should not have to interview the head to learn what the seat is.", "The note names the seat and the result. If it does, you open the file and assign the paper work. If it does not, you send it back the same day and say whether the seat, the result, or both are missing. You do not start a parallel conversation that becomes the real request while the note stays blank.", ["The written note is the handoff. A meeting is not.", "You keep the note with the file."], "role-ceo.png"), "in"),
        side(card("To the executive", "Named file work, oldest first", "They do not pick, and they should not need you to explain the task out loud.", "Give them the person, the task, and what finished looks like: which papers, which attendance, or which request. The oldest open task is assigned before a new favour. They return the file updated, or they return it with the missing paper named, the same working day.", ["One owner.", "The task is on the file, not only in a message to them."], "role-hre.png"), "out"),
        side(card("To the Head of Finance and People", "A pay question the policy does not answer", "You do not set pay, even when the person is waiting in front of you.", "Write what was asked, who asked, and that the written policy does not contain it. Send it the same working day. Tell the person that the answer will come in writing and that you cannot say a number now. Put the same note on their file.", ["You do not send a recommended salary as if it were decided.", "You update the file when the written answer arrives."], "role-hof.png"), "out"),
        side(card("To Operations", "A person whose file is complete, not a lead for them to work", "You confirm the file. They assign the work under their own rules.", "When the papers and the role are in the file, tell Operations the person is ready and point them at the file. You do not assign a customer, a booking, or a provider visit. If the file is not complete, you do not send them as ready.", ["Ready means the file can be continued by a stranger to the hire.", "Operations does not need a verbal briefing if the file is complete."], "ctl-people.png"), "out")
      ],
      workflow: [
        card("Step 1", "Read the written gap before you do anything else", "No seat and no result means there is no hire to open.", "Check that both are written, and that you know which head asked. If not, return the note the same day. Do not collect papers “just in case” while you wait, because that starts the hire in practice.", ["Write the date you accepted or returned it.", "One gap, one seat."], "ctl-people.png"),
        card("Step 2", "Open one file before the person starts the work of that seat", "Starting work with an empty file means the role will be invented later.", "Create the file with the seat, the result, and the list of papers the written process asks for. Assign that file work to one executive. The person is not treated as ready until those papers are in the file or the file clearly says which paper is still missing and who is collecting it.", ["Do not let them begin customer work while this is open.", "The file is opened even if they are already known to the company in another seat. The new seat still needs the gap."], "role-hrm.png"),
        card("Step 3", "Assign the day’s people tasks", "Attendance gaps and open requests need one owner, or they will be picked.", "Write the name. Oldest first. Fewest open tasks next. The executive files what happened and the next step. You look at that before the day ends if something was due today, including attendance.", ["A new office request does not jump an older joining file.", "If you are away, the deputy does this step and does not change pay."], "role-hre.png"),
        card("Step 4", "Check the file as a new person would", "If they cannot continue it, it is not done, even if the executive says it is filed.", "Open the file. Find the role, the papers, the attendance, and the next step. If you have to call someone, send it back the same day and say what is missing. Only then do you tell Operations a person is ready, or tell the head the request is finished.", ["Your check is written. A nod is not a check.", "Pay still follows the policy. Completing papers does not let you invent salary."], "role-hof.png")
      ],
      reporting: [
        timed(card("To the head", "People, from the files, including what is not finished", "A headcount in a sentence hides an empty file and a pay promise.", "Each week, write who joined, which gap they joined against, who is missing papers, which requests are open and who owns them, and any pay question you stopped. If someone is working without a file, send that the same day, not in the weekly note. Point at the files. Do not report a person as ready if you would not hand the file to a stranger.", ["Incomplete is part of the report.", "A pay question is named, not solved with a number you made up."], "ctl-people.png"), "Each week, and the same day if someone is working without a file")
      ],
      standards: [
        card("Hire", "The written gap comes before the person", "The seat exists in writing before anyone is told they are joining.", "No seat and no result means you do not open the hire, you do not collect papers as a real start, and you do not let them begin. You can show the gap for every joiner.", [], "ctl-people.png"),
        card("File", "Another person can continue it", "That is what finished means on this seat.", "The file shows the role, the papers the process requires, the attendance, and the next step on open requests. Missing papers are named in the file. A chat is not a second file.", [], "role-hrm.png"),
        card("Attendance", "It is written from the day’s record before the day ends", "Memory drifts by the next morning, and two people will remember two different days.", "The day’s attendance for the people in your system is in the file before the working day ends. It is not rebuilt later from guesswork. There is no minute clock. The line is the same working day.", [], "role-hre.png"),
        card("Boundary", "You do not assign customer or provider work", "That mixes the people system with Operations, and then nobody owns the lead.", "A ready person is handed over as a complete file. Operations assigns leads under its own rules. You do not cover a booking by sending a person yourself, even on a busy day.", [], "role-hof.png")
      ],
      kpis: [
        kpi("Every hire started from a written gap, and only after that seat’s own work was already happening", "Every hire", "A person without a seat has no result. A person hired into a quiet box is a title, and the file will be invented after they have already started.", "For each person who joined, you can show the written seat, the written result, and proof that the seat’s own result was already happening, dated before the hire was opened. For Field Marketing Manager, that proof shows stall visits, first signatures, and office and society contracts were each already happening. If you cannot show it, the hire was opened too soon. The person above that seat should still be writing: I am doing that seat today because the seat is empty."),
        kpi("Every file you accept can be continued by someone else", "Every person you check", "The file is the handover. A phone call is not.", "The role, the required papers, and the open requests with a next step are in the file. A blank required paper is named as missing and was sent back the same day. You do not mark the file complete while that paper is simply absent."),
        kpi("Attendance is written the same working day", "Every working day", "There is no minute clock. Leaving it to memory means the record will not match the day.", "Before the working day ends, the day’s attendance is in the people files, written from the record, not filled in the next day from what people think they remember."),
        kpi("You did not invent pay", "None", "A spoken salary is not a policy, and people will treat it as one.", "You did not promise an amount, a change, or a benefit that is not already in the written policy. Questions that were not in the policy were sent to the Head of Finance and People the same day, and the person was not told a number."),
        kpi("A deputy is named before your leave", "Every leave", "People tasks still need an owner, or the joining file waits while small requests get picked.", "One name and the dates are written before the leave. That person assigns and does not change pay. If the name was blank, the head writes one name that morning, and new hiring waits until then.")
      ],
      escalations: [
        timed(card("To the Head of Finance and People", "Pay is not in the written policy", "You must not invent it, and you must not let the conversation become the decision.", "Send what was asked, who asked, and that the policy is blank, the same working day. Do not tell the person a number. Write the hold on their file.", [], "role-hof.png"), "The same working day"),
        timed(card("Back to the asking head", "The gap does not name the seat or the result", "You cannot hire a mood, and starting anyway creates a person with no role.", "Send it back the same working day. Say whether the seat, the result, or both are missing. Do not open the file as a real hire while you wait.", [], "ctl-people.png"), "The same working day")
      ],
      glossary: glossPeople.concat(glossMoney)
    }
  }, "control");

  addRole({
    id: "hre",
    name: "HR & Administration Executive",
    reportsTo: "HR & Administration Manager",
    hero: "role-hre.png",
    icon: "hr",
    scene: "hr",
    result: "Every people task that has your name is written in the file the same working day, with the next step, so another person can continue it without calling you.",
    what: "You complete the joining papers, the attendance, and the office requests that the HR and Administration Manager has put on your name. You do not choose tasks from a shared pile. You do not hire someone because they seem needed. You do not set pay. You do not send anyone to a customer job. You write what happened in the people file, and you send back anything you would have to guess.",
    why: "The daily file is how the company knows who was here, which papers exist, and what was promised. If that stays in a chat with you, it disappears when you are away. If you do the easy request first, the joining file stays empty and someone starts work with no record. If you say a salary, that number becomes what the person expects, even when it was never company policy.",
    how: "Open only the tasks with your name. Do the oldest one first. In the people file, write the papers you received, the attendance you were given to record, what you did on the request, and the next step. If a paper, a gap, or a pay amount is missing, write what is missing and send the task back to the manager the same working day. Do not fill it in. If you are the named deputy, you assign tasks to other people, you do not keep the easy ones, and you do not change pay.",
    acting: "If this seat is empty, the HR and Administration Manager does the day’s file and writes that the seat is empty. If you are the deputy, you stop assigning when the manager returns, and you go back to the tasks that have your name.",
    lanes: [
      { kicker: "Part 1", title: "Do the tasks that already have your name", work: "Do not pick from a shared list of requests. Open the tasks the manager assigned. The oldest open task comes first. An older joining file comes before a new office favour, even if the favour is quicker. If a new task lands on your name while you are in the middle of one, finish the file note you are writing, then return to the oldest open task.", result: "The work you did was the work you were given, in the order of the oldest open task.", who: "You. Only the manager, or the deputy, puts your name on a task.", art: "role-hre.png" },
      { kicker: "Part 2", title: "Write it in the people file, including the next step", work: "For a joining task, write which papers you received and which papers are still missing. For attendance, write the day from the record you were given, not from what you think you remember later. For a request, write what you did and what happens next, and who owns that next step if it is not you. A note that says only “done” is not enough if a paper is still missing or a person is still waiting.", result: "The file matches what happened, and the next person can continue it.", who: "You write the file. The manager checks it.", art: "ctl-people.png" },
      { kicker: "Part 3", title: "Send back what you cannot complete without guessing", work: "If the written gap is missing, if a required paper is not in your hands, or if someone asks you for a salary number, stop. Write what is missing. Send it to the manager the same working day. Do not chase the person with a pay figure. Do not create a hire the manager has not opened. Do not tell Operations the person is ready.", result: "Nothing in the file is a guess, and nobody heard a salary from you.", who: "You return it to the HR and Administration Manager.", art: "role-hrm.png" }
    ],
    owns: [
      "People tasks that already have your name, oldest open task first",
      "A file entry another person can continue: papers, attendance, what happened, and the next step",
      "A same-day return when a paper, a gap, or a pay answer is missing",
      "While you are deputy: assignment only, with no pay change and no keeping of tasks"
    ],
    mustNot: [
      "Pick tasks from a shared pile, or jump an older joining file for a new easy request",
      "Promise pay, a pay change, or a benefit",
      "Hire someone, or tell them they have the job, when there is no written gap",
      "Put a person on a customer lead, a booking, or a provider job",
      "Keep tasks for yourself while you are the deputy",
      "Leave the update in a chat and not in the people file"
    ],
    good: [
      "Today’s attendance for the people you were given is in the file before the day ends.",
      "A joining file shows the papers received, the papers still missing, and the next step.",
      "A pay question was sent to the manager, and you did not say a number.",
      "A new person could continue your file tomorrow without calling you."
    ],
    bad: [
      "You chose an easy office request and left a joining file blank.",
      "Attendance is what you remember, written the next day.",
      "You told someone a salary.",
      "The manager has to ask you what happened, because the file is empty."
    ],
    records: [
      "Papers received, and papers still missing, on the people file",
      "Attendance for the day, taken from the record, for the people you were assigned",
      "What you did on each request, and the next step",
      "The return: what was missing, sent back the same day"
    ],
    rules: [
      "If it does not have your name, it is not yours. Do not choose from the pile.",
      "You file the truth that is already in front of you. You do not decide pay or open a new seat.",
      "A chat message is not the file until the same facts are written in the file.",
      "Oldest named task first. A joining file waits for nothing except a missing paper you have already reported.",
      "You never say a pay amount unless it is already in the written policy and the manager has asked you to record that written policy in the file."
    ],
    detailed: {
      copy: {
        definition: "You run the daily people file for the tasks that have your name. You write papers, attendance, and the next step. You send back anything you would have to guess, including pay.",
        lanes: "Named tasks, a complete file note, and a same-day return when something is missing.",
        workflow: "Open your tasks, oldest first. Write the file. Return what is incomplete. Do not say a salary."
      },
      definition: {
        what: [
          "You write joining papers, attendance, and people requests that the manager has assigned to you.",
          "You keep those facts in the one people file, with a next step.",
          "You stop when a paper, a gap, or a pay amount is missing, and you tell the manager the same day."
        ],
        why: [
          "Tomorrow’s person must continue the file without calling you.",
          "An easy task done first leaves a new joiner with an empty record.",
          "A salary you say out loud becomes the salary people expect."
        ]
      },
      responsibilities: [
        card("Named work", "Do the oldest task with your name before a newer one", "Easy tasks hide joining papers, and a person can start work while the file is still blank.", "Open only your named tasks. Put the oldest open one first. A joining file that has been waiting comes before a new request for office help. Do not browse the unassigned list. If you finish early, tell the manager. Do not go and pick.", ["You can show the dates that explain your order.", "A task without your name is not started.", "Halfway through a note, you finish that note before you switch, then you go back to the oldest item."], "role-hre.png"),
        card("The file", "Write what happened and what happens next", "A note that says only “done” leaves the next person guessing which paper arrived.", "In the people file, write the papers you actually have, the attendance from the record, what you did, and the next step. If a paper is still missing, write that it is missing and that you have told the manager. Do not keep the real detail in a chat. The file is what the manager will check.", ["The next person is the test.", "Attendance is written the same day, from the record you were given.", "One person, one file. Do not start a second informal note."], "ctl-people.png"),
        card("The return", "Missing papers and missing gaps go back the same day", "Waiting is how a person starts with an empty file, or how a request sits on your desk with no owner.", "Tell the manager which paper is missing, or that the written gap is not there, or that someone asked you for pay. Send that before the working day ends. Do not invent the paper, do not tell the person they are hired, and do not quote a salary while you wait.", ["Name the gap. “Not ready” is not enough.", "A return is a finished step for you. Silence is not.", "You do not chase Operations to place the person while the file is open."], "role-hrm.png"),
        card("Deputy", "Assign the tasks, and do not keep the ones you like", "Keeping the best tasks is the same as picking, and this seat is not allowed to pick.", "Write one owner on each open task. Write that the manager is on leave and the date. Give the next task to the person with the fewest open tasks. Oldest first. Do not put your name on the easy ones unless the count really gives them to you. Do not change pay, and do not tell anyone a salary. When the manager returns, stop assigning.", ["Only for the written dates.", "A pay question still goes to the Head of Finance and People if the manager is away and it cannot wait. You still do not say the number."], "ctl-books.png")
      ],
      handoffs: [
        side(card("From the manager", "A named task that tells you what finished looks like", "You do not choose, and you should not need a verbal briefing that disappears.", "The task names the person and what you are filing: papers, attendance, or a request. If it has no people file to write into, send it back the same day. If you cannot tell what finished looks like, send it back and ask which paper or which next step they want. Do not invent the task.", ["No name, no work.", "The file is where you write. The message that assigned you is not the record."], "role-hrm.png"), "in"),
        side(card("To the manager", "The file updated, or a return that names the gap", "They check the file. They cannot check a conversation.", "The same working day, each task on your name is either written in the file with the next step, or back with them with the missing paper or missing gap named. If your shift ends, hand the open task on in writing, with the next step, and say so in the file.", ["“Done” without the next step is not a handoff.", "Attendance you were given is included, not saved for tomorrow."], "ctl-people.png"), "out"),
        side(card("To the manager", "A pay question you must not answer", "You do not name an amount, even if you think you remember the policy.", "If someone asks you for a salary, a change, or a benefit, write down who asked and what they asked. Send it to the manager the same day. If the manager has already shown you the written policy and asked you to copy that written amount into the file, you may record that amount and no other. You do not negotiate.", ["You do not say “it is usually this much”.", "The person hears that the answer will come in writing."], "ctl-receipt.png"), "out")
      ],
      workflow: [
        card("Step 1", "Open the tasks with your name, oldest first", "The shared pile is not your work.", "Ignore everything that does not have your name. Start with the oldest open task. A joining file beats a new favour. If you are the deputy today, assign the list first under the deputy rules, then work only what those rules gave you.", ["No named tasks means you wait and tell the manager you are clear.", "You do not help yourself to a task to stay busy."], "role-hre.png"),
        card("Step 2", "Write the people file", "What happened, which papers, the attendance, and the next step.", "Use the record in front of you. Do not reconstruct attendance from memory. Do not mark a paper received if you do not have it. Write the next step even when the next step is “waiting for a paper”.", ["The file, not the chat.", "A stranger should understand it tomorrow."], "ctl-people.png"),
        card("Step 3", "Return what you cannot complete", "Do not guess a paper, a gap, or a salary.", "Send it to the manager the same working day. Name what is missing. Then continue with the next oldest task you can truthfully file. Do not tell the person they are hired, and do not tell Operations they are ready.", ["Returning a gap is correct work.", "Leaving it on your desk is not."], "role-hrm.png")
      ],
      reporting: [
        timed(card("To the manager", "What you filed today, and what you returned", "They cannot see your chat, and they should not have to ask.", "Before the working day ends, list each task that had your name. Say filed, and what the next step is, or returned, and what is missing. Include the attendance you were responsible for. If you handed a task on, name the person and the next step.", ["The report matches the file.", "A task you do not mention looks abandoned."], "ctl-people.png"), "Before the working day ends")
      ],
      standards: [
        card("Order", "The oldest named task comes first", "A new request feels urgent and hides the joining file that has been waiting.", "An older joining file comes before a new office favour. You do not reverse the order because someone asked you in person. If the manager reorders it in writing, you follow that writing.", [], "role-hre.png"),
        card("The file test", "Another person can continue it without calling you", "That is the standard for every task you touch.", "They can see the role, the papers you were given, the papers still missing, the attendance, and the next step. If they cannot, you have not finished, and the task goes back or the file is completed the same day.", [], "ctl-people.png"),
        card("Pay", "You do not say the amount", "Unless the manager has shown you the written policy and asked you to record that exact writing in the file.", "Any other pay question goes to the manager the same day. You do not negotiate, you do not say what is usual, and you do not whisper a number while the official answer is still open.", [], "ctl-receipt.png")
      ],
      kpis: [
        kpi("Every named task is filed or returned before the day ends", "Every task with your name", "Silence loses the request, and the manager cannot see that it was yours.", "Before the working day ends, each task on your name is in the people file with a next step, or back with the manager with the missing paper or gap named."),
        kpi("Attendance you were given is written today", "Every working day you are in this seat", "Memory is not attendance. The next morning it will already be wrong.", "The day’s attendance for the people you were assigned is in the file before the day ends, taken from the record, not filled in later from what you think happened."),
        kpi("You did not invent a pay amount", "None", "You do not set pay. A number you say becomes the number people expect.", "You did not tell anyone a salary, a change, or a benefit that is not already in the written policy. Any question was sent to the manager the same day, with no figure attached."),
        kpi("The next person can continue every file you touch", "Every file you touch", "The file is the job. You are not the job.", "The role, the papers you were given, and the next step are written. If a paper is missing, the file and the return both say so. A stranger does not need to call you.")
      ],
      escalations: [
        timed(card("To the manager", "A person is working and the file is empty", "They are not fully hired, and every day without a file makes the story harder to recover.", "Tell the manager the same working day. Write what you can see, and what is missing. Do not backfill a tidy story of papers you never received. Do not tell the person to ignore it.", [], "role-hrm.png"), "The same working day"),
        timed(card("To the manager", "Someone asks you for a salary number", "You must not invent it, and you must not repeat a number you overheard.", "Send the question up the same day. Tell the person the answer will be in writing. Do not give a range, a usual figure, or a personal guess.", [], "ctl-receipt.png"), "The same working day")
      ],
      glossary: glossPeople
    }
  }, "control");


  addRole({
    id: "hot",
    name: "Head of Technology & Data",
    reportsTo: "CEO",
    hero: "role-hot.png",
    icon: "td",
    scene: "td",
    result: "The tools the company needs are up, a change does not hide a booking, a payment, a lead, or a provider file, and the team lead runs the tickets so you are not writing the day’s code.",
    what: "You own the systems the company works in, the product those systems are, and the data inside them. The Software Engineer Team Lead turns written asks into tickets and gives each ticket to one engineer. You check that a seat can still do its next written step, and that a release did not break a live record. You do not pick the day’s tickets as your main job. You do not let an engineer invent a feature nobody asked for in writing.",
    why: "Operations books the customer inside these tools. Finance records the money inside them. If you spend the day writing code, nobody is left to notice that a release hid a booking. If engineers choose their own work, a new idea gets built while a seat that cannot open the next step keeps waiting. If a change goes out because it was interesting, the company has built something it did not ask for, and a live record may be gone.",
    how: "Each working day, find out whether Operations, Finance, and Growth can open the admin panel and do the next step of their written job. If they cannot, write what is blocked, which seat, and have the team lead assign one owner before the working day ends. Read what shipped. It must match what the ask said finished looks like. If a change can erase or hide a booking, a payment, a lead, or a provider file, it waits until the head who owns that record writes yes. If the team lead is away and no deputy was named, write one engineer’s name that morning and do not take the tickets. Tell the CEO the same day if a tool the seats need is down.",
    acting: "Before your own leave, name the team lead and the dates. They may move only what cannot wait. They do not change what the company sells, and they do not approve a live-record change alone. When you return, you read what shipped and what was held. If the team lead’s seat is empty, you may cover the assignment for that day and you write that the seat is empty. The next day you stop, unless you have named a deputy.",
    lanes: [
      { kicker: "Part 1", title: "See whether the seats can still do their next step", work: "A tool is up when a person in Operations, Finance, or Growth can open the admin panel and do the next step their playbook already describes. If they cannot, that is not a private bug for later. You write, the same working day, which seat is blocked, what they cannot do, and that the team lead must assign one owner. You do not take the ticket yourself unless the team lead’s seat is empty, and then you write that you are covering because the seat is empty.", result: "A blocked seat is written down, with one owner, before the working day ends.", who: "You see the block and write it. The team lead assigns the engineer.", art: "tec-screen.png" },
      { kicker: "Part 2", title: "Keep a release from breaking a live record", work: "A live record is a booking, a payment, a lead, or a provider file that a real customer or provider depends on. Before a change goes out, you need to know whether it can erase one of these, hide it, or make the next person unable to continue it. If it can, it does not go out. You ask the head who owns that record for a written yes. Operations owns the booking and the lead. Finance owns the payment record. You do not give that yes yourself just to keep a release moving.", result: "After a release, the records Operations and Finance trust are still there and can still be continued.", who: "The team lead checks the ticket. You stop a change that has no written yes from the head who owns the record.", art: "tec-ticket.png" },
      { kicker: "Part 3", title: "Order the asks, and stay off the daily ticket pile", work: "People ask for changes in writing, and the writing says what finished looks like. A seat that cannot do its next step comes before a new idea. When two departments disagree about what should be built first, you set the order in writing. If you and another head still disagree, the CEO decides, and that decision is written. You do not sit down and write the day’s code as your main job. If the team lead is away and no deputy was named, you write one name that morning, then you stop.", result: "The engineering system still runs when you are checking it, not only when you are building it.", who: "You order and check. The team lead assigns. The engineers build named tickets.", art: "role-hot.png" }
    ],
    owns: [
      "Whether the tools a working seat needs are up, written the same day when they are not",
      "A stop when a change would erase or hide a booking, a payment, a lead, or a provider file",
      "The team lead’s result: every ticket has one owner, a check, and a release that matches the ask",
      "One deputy’s name, written that morning, when the team lead is away and the name was blank"
    ],
    mustNot: [
      "Make writing the day’s code your main job, so that nobody is checking the release",
      "Let an engineer pick a ticket from the shared board",
      "Ship a change that hides or erases a live record without a written yes from the head who owns it",
      "Invent a feature, or allow one, when nobody wrote what finished looks like",
      "Keep running the ticket list after you have named the missing deputy",
      "Tell the CEO the tools are fine when a seat cannot do its next step and that fact is unwritten"
    ],
    good: [
      "Operations, Finance, and Growth could do the next step in the admin panel, or a block was written the same day with one owner.",
      "A release left the bookings, payments, leads, and provider files in place.",
      "What shipped matched what the ask said finished looks like.",
      "The team lead was away, you named one deputy that morning, and you did not keep the tickets."
    ],
    bad: [
      "A seat could not work, and nobody wrote it down.",
      "A change went out because it was interesting, not because a head wrote what finished looks like.",
      "A booking or a payment could not be found after a release.",
      "You spent the day coding and did not look at what shipped."
    ],
    records: [
      "Each block: which seat, what they cannot do, the date, and the one owner of the fix",
      "Each release: what the ask said finished looks like, what shipped, and whether a live record was touched",
      "Written yes from the head who owns a live record, before a risky change goes out",
      "The order you set when two asks compete, and any CEO decision on that order",
      "Deputy names and dates when the team lead is away"
    ],
    rules: [
      "A tool is up only when the person in that seat can do the next step their playbook already describes. “The server is on” is not the same thing.",
      "A change to a live record needs a written yes from the head who owns that record. You do not give that yes to keep a release on time.",
      "Do not invent a response time in minutes. A blocked seat is written, with an owner, before the working day ends.",
      "No written “what finished looks like” means the ask goes back. Nobody starts building it.",
      "You name a missing deputy that morning and then you stop. You do not take the tickets."
    ],
    detailed: {
      copy: {
        definition: "You own the tools and the data the company runs on. The team lead runs the engineers. You check that people can still work, and that a release did not break a live record.",
        lanes: "See that the seats can work, protect live records, and stay off the daily ticket pile except to name a missing deputy.",
        workflow: "Write a block the same day, order the written asks, refuse a danger to a live record, and read what actually shipped."
      },
      definition: {
        what: [
          "You hold the engineering system to one result: tickets have owners, releases match the ask, and live records survive.",
          "You write it down the same day when a seat cannot do its next step, and you make sure one owner is assigned.",
          "You stop a change that can erase or hide a booking, a payment, a lead, or a provider file until the owning head writes yes.",
          "You name one deputy that morning if the team lead is away and left the name blank."
        ],
        why: [
          "The rest of the company does its work inside these tools. If the tool blocks them, the promise to the customer stops.",
          "A clever change that hides a booking is a failed job, even if the new screen looks good.",
          "If you write the code yourself, there is no second person checking the release."
        ]
      },
      responsibilities: [
        card("Up", "Write a blocked seat the same working day, and get one owner on it", "A silent outage is repeated, because nobody owned it, and the seats invent workarounds that leave the records behind.", "Ask, or look at what the seats report: can Operations, Finance, and Growth do the next written step in the admin panel? If they cannot, write the seat, what is blocked, and the date, before the working day ends. Tell the team lead to assign one engineer. A blocked seat comes before a new idea. You do not collect a private list of bugs in your head. There is no minute clock. The line is the same working day.", ["“The server is on” does not count if the person cannot do the step.", "One owner is named. A team chat is not an owner.", "You tell the CEO the same day if customers cannot be served because of the block."], "tec-screen.png"),
        card("Live records", "Do not let a release erase or hide a booking, a payment, a lead, or a provider file", "Those records are the business. A prettier screen does not replace them.", "Before the change goes out, ask whether it can remove a live record, hide it, or stop the next person from continuing it. If it can, stop the release. Write what it would touch. Ask the head who owns that record for a yes in writing. Operations for a booking or a lead. Finance for a payment. You do not write that yes yourself. The change waits, even if the engineer is ready.", ["No written yes, no release.", "After it goes out, you still check that the records are there.", "A workaround that tells people to keep the booking in a chat is not a fix."], "tec-ticket.png"),
        card("The ask", "Build only what was written, including what finished looks like", "A private idea is not a ticket, and an engineer’s improvement can still break a record.", "The person who needs the change writes what is needed and what finished looks like. If that second part is missing, send the ask back the same working day and do not let work start. When two asks compete, a blocked seat comes first. If two heads still disagree after you set an order, you take it to the CEO and you follow the written decision. You do not add a feature because it would be useful.", ["Finished is described before anyone builds.", "You can point at the ask for everything that shipped.", "Effort and elegance are not the result. The written done is the result."], "role-hot.png"),
        card("Leave", "Name a deputy if the team lead did not, then leave the board alone", "Otherwise engineers will pick, and the blocked seat will wait behind an interesting ticket.", "If the team lead is away, look for the deputy name and the dates before tickets are taken. If the name is blank, write one engineer that morning. Tell them they assign, they do not keep the interesting tickets, and they do not approve a live-record change alone. Then stop. When the team lead returns, they read what was assigned and take the list back. If you yourself are going away, name the team lead before you leave, with the same kind of limit.", ["New tickets wait until a name exists.", "You do not become the deputy for the rest of the leave.", "A live-record yes still comes from the head who owns the record."], "ctl-people.png")
      ],
      handoffs: [
        side(card("From a head", "A written ask that says what finished looks like", "You cannot build a mood, and the team cannot guess what done means.", "The ask says what is needed, who asked, and what finished looks like. If finished is missing, you send it back the same day and nothing is assigned. You do not interview them into a ticket that only you understand. The written ask is what the team lead receives.", ["A meeting request is not an ask until it is written.", "You keep the ask with the ticket so the engineer can read it."], "role-ceo.png"), "in"),
        side(card("To the team lead", "An ordered list they can assign", "They give one ticket to one engineer. They should not have to guess which blocked seat matters.", "You pass the asks in order. A seat that cannot do its next step is ahead of a new idea. You say that in writing. If you have not ordered them, do not expect the team lead to invent the order. You do not also assign the engineer yourself, unless their seat is empty and you have written that.", ["One list, not two competing chats.", "The team lead can show the order to a new engineer."], "role-set.png"), "out"),
        side(card("To the head who owns the record", "A risky change, waiting for a written yes", "They must say yes. You must not ship while that yes is missing.", "Describe what the change would touch: which booking, payment, lead, or provider file, and what could go wrong. Send it before the release. Do not describe it so vaguely that the yes is meaningless. When they write yes, that writing stays with the ticket. When they write no, the change does not go out.", ["Same working day if a release is waiting on it.", "Silence is not a yes."], "tec-screen.png"), "out"),
        side(card("To the CEO", "The tool is down, with an owner already named", "They need to know the company cannot serve. They do not need to run the engineers.", "The same working day, say what is broken, which seats cannot do their next step, and who owns the fix. Say that you did not take the ticket yourself, unless the team lead’s seat is empty, in which case you write that. You do not ask the CEO to choose technical options you have not written down.", ["The note is short and factual.", "You update it when the seat can work again."], "role-hot.png"), "out")
      ],
      workflow: [
        card("Step 1", "Find out if the tools are actually usable", "Start from the seats, not from a dashboard that only says the machine is on.", "Check whether Operations, Finance, and Growth can do the next step in their playbook. Write any block the same day: seat, what they cannot do, date. Tell the team lead to assign one owner before anyone starts a new idea.", ["A block with no owner is not handled.", "You do this even on a quiet day. Quiet is when unwritten blocks hide."], "tec-screen.png"),
        card("Step 2", "Put the written asks in order", "A blocked seat comes before a new idea, unless the CEO has written a different order.", "Reject an ask that does not say what finished looks like. Send it back. Put the rest in one list. When two departments both say they are first, you write the order. If you cannot agree with the other head, the CEO decides in writing.", ["The team lead receives one order.", "You do not reorder it in a side message after they have assigned."], "tec-ticket.png"),
        card("Step 3", "Stop a change that threatens a live record", "Until the owning head writes yes, it is not ready, no matter how finished the code looks.", "Read what the change touches. If a booking, a payment, a lead, or a provider file could disappear or become unusable, hold it. Get the written yes. Put that yes on the ticket. If there is no yes, the team lead does not release it.", ["You look before the release, not only after a complaint.", "A backup plan that is not written does not count as protection."], "role-hot.png"),
        card("Step 4", "Read what shipped against the ask", "Busy engineers are not the result. The written done is the result.", "Compare what went out with what the ask said finished looks like. If it does not match, it goes back. It is not finished. Also confirm the live records you were worried about are still there. Tell the CEO in the weekly note, and the same day if a needed tool is down.", ["A release note that only the author understands is not accepted.", "You do not rewrite the ask after the fact to match what was built."], "role-set.png")
      ],
      reporting: [
        timed(card("To the CEO", "What was down, what shipped, and what was refused", "The company needs to know if it can work, not how hard the engineers worked.", "Each week, write which seats were blocked, who owned the fix, what shipped and which ask it matched, what you sent back because finished was not described, and any live record that was at risk. If a tool the seats need is down, send that part the same working day, with the owner. Do not report a release as done if it does not match the ask.", ["Point at the ticket and the ask.", "Include the noes. A report of only successes hides the risk."], "tec-screen.png"), "Each week, and the same day if a tool the seats need is down")
      ],
      standards: [
        card("A blocked seat", "It is written the same working day, with one owner", "Silence is how the same outage returns next week.", "There is no minute clock. Before the day ends, the stop, the seat, and one owner are written, and the team lead has been told to assign it ahead of a new idea. You do not leave it as a message that will scroll away.", [], "tec-screen.png"),
        card("The ask", "Nothing is built until finished is described", "Otherwise the engineer invents the company, one screen at a time.", "The ask says what finished looks like, in words a new engineer can read. If it does not, it goes back the same day and no ticket is started. What ships is compared with that description, not with the effort spent.", [], "tec-ticket.png"),
        card("Live records survive", "The booking is still the booking after the release", "Hiding it, or making it impossible to continue, is a failed release.", "If the change might erase or hide a booking, a payment, a lead, or a provider file, it waits for a written yes from the head who owns it. After release, you can still find those records.", [], "role-hot.png"),
        card("Your own seat", "You do not own the day’s tickets", "The team lead does. If you take them, the check disappears.", "You name a missing deputy and then stop. You write code for the day only when their seat is empty, and you write that down, and you stop the next day.", [], "ctl-people.png")
      ],
      kpis: [
        kpi("A blocked seat is written the same working day, with an owner", "Every time a seat cannot do the next step", "An unwritten outage gets repeated, and people start keeping bookings outside the tool.", "When a seat cannot do its next written step, the block, the seat, and one owner are written before that working day ends. A new idea is not started ahead of it unless the CEO wrote a different order."),
        kpi("No release hid or erased a live record without a written yes", "None", "A missing booking or payment is a broken promise, even if the new work looks finished.", "You did not let a change go out that erased or hid a booking, a payment, a lead, or a provider file, unless the head who owns that record had already written yes, and that yes is on the ticket."),
        kpi("What you accept as shipped matches the written ask", "Every build you accept", "Effort is not the result. A feature nobody described is not a success.", "For each release you accept, you can point at what the ask said finished looks like, and what shipped matches it. If it does not, it goes back the same day and is not reported as done."),
        kpi("A missing deputy is named before tickets are taken", "Every leave where the name was blank", "Otherwise the board gets picked, and the blocked seat waits.", "That morning, before anyone takes a ticket, one name is written. You do not keep the tickets after you have named them. New tickets wait until the name exists.")
      ],
      escalations: [
        timed(card("To the CEO", "A tool the seats need is down", "The company cannot keep the promise it already made to customers.", "Tell them the same working day what is broken, which seats cannot work, and who owns the fix. Do not take the ticket unless the team lead’s seat is empty, and then write that.", [], "role-ceo.png"), "The same working day"),
        timed(card("To the head who owns the record", "A change would touch a live booking, payment, lead, or provider file", "They decide in writing. Silence is not permission.", "Send what would be touched, before the change goes out. Do not ship while the yes is missing. Do not ask the engineer to decide.", [], "tec-screen.png"), "Before the change goes out")
      ],
      glossary: glossTech
    }
  }, "technology");

  addRole({
    id: "set",
    name: "Software Engineer Team Lead",
    reportsTo: "Head of Technology & Data",
    hero: "role-set.png",
    icon: "td",
    scene: "td",
    result: "Every ticket has one engineer, is checked against the ask before it goes out, and does not break a booking, a payment, a lead, or a provider file.",
    what: "You take the written asks the Head of Technology has ordered, and you turn each one into a ticket with one engineer’s name. A seat that cannot do its next step comes before a new idea. Before anything goes out, you check that it matches what finished looks like, that the note is clear, and that live records are still there. You do not let engineers pick from the board. You do not approve a risky change to a live record by yourself.",
    why: "If engineers choose their own work, the tool that is down waits while a new screen gets built. If two people build the same thing, or nobody owns the blocked seat, the company cannot work. If only the author understands the change, the next engineer cannot continue it, and a booking can disappear without anyone knowing how to put it back.",
    how: "Each working day, read the ordered asks. Send back any ask that does not say what finished looks like. Write one engineer’s name on each ticket you are ready to start. Give it to the person on shift with the fewest open tickets. Put a blocked seat ahead of a new idea. When they hand it back, read what changed, how they tested it, and what is left. Look at the live records it could touch. Release it or send it back the same day you finish the check. Before leave, name one engineer as deputy, with the dates. The deputy assigns and does not approve a live-record change alone.",
    acting: "If you are already away and no deputy was named, the Head of Technology writes one name that morning. Until then, new tickets wait. When you return, you read the tickets from those days and take the list back. If an engineer’s seat is empty, you may do a ticket that cannot wait, and you write that the seat is empty. You still do not pick a pile of interesting work.",
    lanes: [
      { kicker: "Part 1", title: "Put one engineer’s name on every ticket", work: "The board is not a pile people may browse. You write the owner. The next engineer is the one on shift with the fewest open tickets. When the count is equal, the older blocking ticket comes first. You do not keep the interesting tickets for yourself or for your favourite engineer. A blocked seat is assigned before a new idea is started. The engineer opens only the tickets that already have their name.", result: "Nobody is picking, and every open ticket has one name before work starts.", who: "You. On leave, the one deputy you named.", art: "tec-ticket.png" },
      { kicker: "Part 2", title: "Check the work before it goes out", work: "Read the ask again. What shipped must be what finished looked like. Read the engineer’s note: what changed, how they tested it, and what they did not do. Then look at bookings, payments, leads, and provider files the change could touch. If you cannot show they are still there and still usable, it does not go out. If the change might harm them and there is no written yes from the head who owns that record, send it to the Head of Technology and wait.", result: "A release is a checked file, not a hope that the author was careful.", who: "You check. The engineer does not quietly release it.", art: "tec-screen.png" },
      { kicker: "Part 3", title: "Refuse a ticket the next engineer could not continue", work: "The note must say what changed, how it was tested, and what is left. You read it as a new engineer. If you would need to call the author to continue the work or to undo it, you send it back the same day. You do not fill the note in for them from a conversation. The author writes it. You do not release a change that lives only in someone’s memory.", result: "The ticket is a file the next person can continue, including if the author is away tomorrow.", who: "The engineer writes the note. You refuse the ticket if the note fails this test.", art: "role-set.png" }
    ],
    owns: [
      "One named engineer on every open ticket, written before they start",
      "The order: a seat that cannot work, before a new idea",
      "The check before release: the ask, the note, and the live records",
      "A named deputy and the dates, written before your leave"
    ],
    mustNot: [
      "Let an engineer pick a ticket from the board",
      "Release a change that can hide a live record when there is no written yes",
      "Start an ask that does not say what finished looks like",
      "Keep the most interesting tickets while you are the person assigning",
      "Approve a live-record change alone, including when a deputy is covering",
      "Accept a note that only the author can explain"
    ],
    good: [
      "Every open ticket had one name before the engineer started.",
      "A down tool was assigned before a new idea was started.",
      "The release note says what changed, how it was tested, and what is left, and you could follow it.",
      "A risky change waited for a written yes, and the records were still there afterwards."
    ],
    bad: [
      "Two engineers built the same thing, or the blocked seat had no owner.",
      "A booking or a payment was missing after a release.",
      "Only the author understands the change, and you released it anyway.",
      "You kept the interesting tickets and assigned the rest."
    ],
    records: [
      "The ticket list: the ask, the owner, the order, and whether it is waiting, in progress, returned, or released",
      "What finished looked like, copied from the ask, so it is not rewritten later",
      "The engineer’s note: what changed, the test, and what is left",
      "The check you did on live records, and any written yes",
      "The deputy’s name and the dates"
    ],
    rules: [
      "One ticket, one name, written by you or by the deputy. The engineer does not choose.",
      "A seat that cannot do its next step is assigned before a new idea, unless the Head of Technology has written a different order.",
      "If the ask does not say what finished looks like, send it back the same day. Do not invent the feature.",
      "You do not release a change you could not continue from the note, or a change that threatens a live record without a written yes.",
      "Before leave, name one engineer and the dates. They assign. They do not approve a live-record change alone."
    ],
    detailed: {
      copy: {
        definition: "You lead the engineers by assigning one ticket to one person and checking it before it goes out. You do not let the board choose the work, and you do not ship a risk to a live record.",
        lanes: "One owner, a real check, and a note the next engineer can continue.",
        workflow: "Send back a blank ask, assign one owner, check the result and the records, then release it or return it the same day."
      },
      definition: {
        what: [
          "You assign every ticket the Head of Technology has accepted, one engineer each.",
          "You put a blocked seat ahead of a new idea.",
          "You check the note and the live records before anything is released.",
          "You name a deputy before you go on leave."
        ],
        why: [
          "Unassigned tickets mean the urgent fix waits behind whoever feels motivated.",
          "A release only the author understands cannot be undone safely.",
          "A hidden booking is a failed release even if the code was hard."
        ]
      },
      responsibilities: [
        card("Assign", "Write one name before any engineer starts", "A shared board is a pile. People will take what looks interesting.", "Look at who is on shift and how many open tickets they already have. Write the next ticket on the person with the fewest. If the numbers match, the older blocking ticket goes first. Do not keep the best work. Do not ask who wants it. The engineer should be able to open their name and see their work without browsing.", ["The name is on the ticket before the work starts.", "You can explain the order from the list.", "The deputy uses the same rule and does not keep tickets."], "tec-ticket.png"),
        card("Order", "A blocked seat is assigned before a new idea", "A new screen can wait. A seat that cannot book or record a payment cannot wait.", "If Operations, Finance, or Growth cannot do the next written step, that ticket is the next one you assign, ahead of new ideas. Follow the Head of Technology’s written order. Do not quietly swap in a more interesting ticket. If you think the order is wrong, say so in writing. Do not change it in secret.", ["You can show which ticket was blocking and that it was assigned first.", "A new idea stays unstarted while a block has no owner.", "Only a written order from the head or the CEO changes this."], "tec-screen.png"),
        card("Check", "Match the ask, read the note, and look at the live records", "Done means what the ask said, the next person can continue it, and the old records still exist.", "When the engineer says it is ready, read the ask first so you remember what finished looked like. Then read their note. Then open a booking, a payment, a lead, or a provider file the change could affect, or read the proof they wrote that those records are untouched and still usable. If any part fails, send it back the same day and say which part. Do not release it because the deadline feels close.", ["You do not rewrite their note for them.", "No written yes on a risky record means you escalate, you do not shrug.", "Released or returned. Not left in between overnight without a status."], "role-set.png"),
        card("Leave", "Name one engineer before you go, with a written limit", "The list still needs an assigner, or the board will be picked the morning you are gone.", "Write the name and the dates before the leave starts. They assign with the same rules: fewest open tickets, blocked seats first, no keeping the interesting ones. They do not approve a change to a live record alone. That still goes to the Head of Technology. When you return, read what they assigned and take the list back.", ["If you forget, the head writes one name that morning.", "Until a name exists, new tickets wait.", "You do not extend their power past the dates."], "ctl-people.png")
      ],
      handoffs: [
        side(card("From the Head of Technology", "An ordered ask that already says what finished looks like", "You do not invent the company’s wishes, and you do not start a vague request to be helpful.", "Read the order and the description of done. If done is missing, send the ask back the same day and do not assign it. If the order is missing, ask for it in writing before you let people start a new idea ahead of a block. The ask stays on the ticket so the engineer reads the same words you read.", ["You do not translate it into a private brief that replaces the ask.", "A verbal “just build something” is sent back."], "role-hot.png"), "in"),
        side(card("To an engineer", "One ticket, with their name and the ask attached", "They do not browse the board, and they should not need you to remember the requirements.", "Give them the ask, what finished looks like, and their name. Tell them if it is a blocked seat, so they do it before another ticket that also has their name. They return a note: what changed, how they tested, and what is left. They stop if a live record would be touched and the yes is not on the ticket.", ["One ticket in their hands at a time is the aim when a block is open.", "You do not assign a bundle and say sort it out."], "role-swe.png"), "out"),
        side(card("To the Head of Technology", "A live-record risk you will not approve alone", "You describe what would be touched. You wait.", "Write which booking, payment, lead, or provider file is at risk, and what the change would do. Send it before release. Do not tell the engineer to ship while you wait. When the written yes comes back, put it on the ticket. When it does not, the ticket stays unreleased.", ["Silence is not a yes.", "You do not soften the risk so the answer is easier."], "tec-screen.png"), "out"),
        side(card("Back to the person who asked", "It does not match what finished looked like", "Shipped is not the same as done, and they need to know what is missing.", "Say what was asked, what was built, and what is still missing. Send it the same working day after your check. Do not quietly change the ask so the build now matches. The engineer fixes the gap or the ask is rewritten properly by the head, not by you after the fact.", ["The return is specific.", "The ticket stays open until it matches or the ask is formally changed."], "tec-ticket.png"), "out")
      ],
      workflow: [
        card("Step 1", "Read the asks and reject a blank done", "If you cannot tell when it would be finished, the engineer cannot either.", "Send it back the same day. Do not assign a placeholder ticket. Do not let someone start “a spike” that changes a live record. Keep a short note that you returned it, so it does not vanish.", ["The head sees the return.", "You do not fill in what finished looks like from your own preference."], "role-hot.png"),
        card("Step 2", "Assign one owner, blocking work first", "Write the name on the ticket.", "Fewest open tickets. Older block first. The engineer can see it under their name. A new idea stays unassigned while a blocked seat has no owner, unless a written order says otherwise.", ["You do not assign by who is most interested.", "The deputy, on your leave, does this the same way."], "tec-ticket.png"),
        card("Step 3", "Check the result before anyone calls it released", "Ask, test note, and live records.", "Read all three. If the note fails the stranger test, send it back. If a live record is at risk and there is no written yes, escalate and hold. Do not release a partial surprise.", ["You write what you checked.", "The engineer sees the reason for a return."], "tec-screen.png"),
        card("Step 4", "Release it or return it the same day you finish the check", "An in-between status overnight means nobody owns the truth.", "Mark it released only when it matches the ask, the note is enough, and the records are safe. Otherwise mark it returned, with the reason, the same day. Tell the engineer and, if the ask was missed, tell the person who asked.", ["There is no minute clock. The line is the day you finished the check.", "You do not leave it “almost” in a chat."], "role-set.png")
      ],
      reporting: [
        timed(card("To the head", "What is blocked, what shipped, and what you returned", "They should not have to read every ticket to know if the company can work.", "Each working day that a seat is blocked, say so, with the owner. Each week, list what shipped against which ask, what you returned, and anything that touched or threatened a live record. Point at the tickets. Do not report effort as if it were a release.", ["A blocked seat is not saved for the weekly note.", "A release you are unsure about is reported as not released."], "tec-screen.png"), "Each working day for a blocked seat, and each week for the list")
      ],
      standards: [
        card("One name", "No ticket is started without an owner you wrote", "A board is not an owner. Interest is not an owner.", "The engineer’s name is on the ticket before they start. You can see every open ticket and its name in one list. Unnamed tickets are not in progress.", [], "tec-ticket.png"),
        card("Order", "A blocked seat comes before a new idea", "The company works in the tool. A new idea does not unblock a seat.", "You assign the block first. You do not start a new idea while that block has no owner, unless the Head of Technology wrote a different order and you are following that writing.", [], "tec-screen.png"),
        card("The note", "The next engineer can continue it", "That is part of the check, not a nice extra.", "What changed, how it was tested, and what is left are written in words you can follow without calling the author. If you cannot, you send it back. You do not release it and promise to document later.", [], "role-set.png"),
        card("Live records", "You looked before you released", "Hope is not a check.", "You can show that a booking, a payment, a lead, or a provider file the change could touch is still there, or that the change does not touch them. If you cannot show that, it does not go out.", [], "role-hot.png")
      ],
      kpis: [
        kpi("Every open ticket has one owner before work starts", "Every open ticket", "A pile hides the urgent fix and invites two people to build the same thing.", "Look at the open tickets. Each one has one engineer’s name, written by you or by the deputy, before that engineer started. A ticket without a name is not being worked."),
        kpi("A blocked seat is assigned before a new idea is started", "Every blocked seat", "The tool is how the company takes bookings and records money.", "You do not start a new idea while a seat cannot do its next written step, unless the Head of Technology wrote a different order. The block has an owner first."),
        kpi("Every release can be continued by the next engineer", "Every release", "The author will be away one day, and someone must be able to see what changed.", "On every release, the ticket says what changed, how it was tested, and what was not done. You were able to follow that note without calling the author. If you could not, it was not released."),
        kpi("You released no unchecked change to a live record", "None", "A hidden booking or payment is a failed release, whatever else the change did.", "You did not release a change that could erase or hide a booking, a payment, a lead, or a provider file unless a written yes from the owning head was already on the ticket."),
        kpi("A deputy is named before your leave", "Every leave", "Tickets still need an assigner the morning you are gone.", "One engineer, with the dates, is named before the leave. They assign and do not approve a live-record change alone. If the name was blank, the head names someone that morning, and new tickets wait until then.")
      ],
      escalations: [
        timed(card("To the Head of Technology", "The change would touch a live record", "You cannot approve that yes alone, and the engineer cannot either.", "Before the change goes out, write which booking, payment, lead, or provider file it would touch, and what could go wrong. Send that to the Head of Technology and hold the ticket. When their written answer arrives, put it on the ticket. If the answer is missing, the ticket stays unreleased.", [], "role-hot.png"), "Before it goes out"),
        timed(card("Back to the asker, through the head", "The ask does not say what finished looks like", "You will not invent the feature, and you will not let an engineer invent it.", "Send the ask back the same working day and say that it does not describe what finished looks like. Do not assign an engineer while you wait, and do not fill in the missing description from your own preference.", [], "tec-ticket.png"), "The same working day")
      ],
      glossary: glossTech
    }
  }, "technology");

  addRole({
    id: "swe",
    name: "Software Engineer",
    reportsTo: "Software Engineer Team Lead",
    hero: "role-swe.png",
    icon: "td",
    scene: "td",
    result: "The ticket that has your name is built to match what finished looks like, tested, and written so the next engineer can continue it, without changing a booking, a payment, a lead, or a provider file unless a written yes is already on the ticket.",
    what: "You build and fix the product only for tickets the Software Engineer Team Lead has put on your name. You do not open the board and choose. If one of your tickets is a blocked seat, you do that before a new idea that also has your name. You write what changed, how you tested it, and what is left. You do not quietly release the change. The team lead checks it. If the work would touch a live record and the ticket has no written yes, you stop the same day.",
    why: "The company books customers and records money inside this tool. A change only you understand cannot be fixed when you are away. A change that hides a booking breaks a promise that was already made. If you pick an interesting ticket, the seat that cannot work keeps waiting. If you add a feature nobody described, you have changed the company without a decision.",
    how: "Open the tickets with your name. If one of them is marked as a blocked seat, do that one before any new idea on your name. Read what finished looks like. Build that, and do not add extra behaviour that changes a booking, a payment, a lead, or a provider file. Test it. Write the note on the ticket: what changed, how you tested, and what is left. Hand it to the team lead. If finished is not described, or a live record would be touched without a written yes, send the ticket back the same working day and do not call it ready. If you are the deputy, you assign tickets and you do not keep the ones you want.",
    acting: "If this seat is empty, the team lead covers the tickets that cannot wait and writes that the seat is empty. If you are the deputy, you stop assigning when the team lead returns. You then go back to building only the tickets that have your name.",
    lanes: [
      { kicker: "Part 1", title: "Build the ticket that already has your name", work: "Do not take a ticket from the board, even if you can see it and it looks more useful. Open your name. If the team lead has marked one ticket as a blocked seat, do that before a new idea that also has your name. Read what finished looks like before you write anything. If you are already deep in a ticket when a blocked seat is assigned to you, finish the safe step you are in, write where you stopped, and move to the blocked seat. Do not quietly keep going on the new idea.", result: "The work you did was the work you were given, in the order the team lead wrote.", who: "You. The team lead, or the deputy, is the only person who puts a ticket on your name.", art: "role-swe.png" },
      { kicker: "Part 2", title: "Test it, and write a note the next engineer can use", work: "Show that the ask is met, not only that the code runs on your machine. Write, on the ticket, what you changed, how you tested it, and what you did not do. Include anything you are unsure about. The test of the note is a colleague who was not in the room. If they would need to call you to continue the work or to undo it, the note is not finished. Do not keep the real explanation in a private message.", result: "The next engineer can continue the ticket from the note if you are away tomorrow.", who: "You write the note. The team lead reads it before any release.", art: "tec-ticket.png" },
      { kicker: "Part 3", title: "Stop when a live record would change and there is no written yes", work: "If the change can erase or hide a booking, a payment, a lead, or a provider file, look on the ticket for a written yes from the head who owns that record. If the yes is not there, stop. Do not ship it to prove it is safe. Do not hide the risk in the note and call the ticket ready. Tell the team lead the same working day what the change would touch. Wait. Extra features that were not in the ask are a new ticket, not a surprise inside this one.", result: "You did not hand over a guess, and you did not change a live record on your own judgement.", who: "You stop and tell the team lead. They take it to the Head of Technology. You do not decide the yes.", art: "tec-screen.png" }
    ],
    owns: [
      "The tickets that already have your name, with a blocked seat done before a new idea",
      "A note on each ticket you hand back: what changed, how you tested it, and what is left",
      "A same-day stop when finished is not described, or when a live record has no written yes",
      "While you are deputy: assignment only, without keeping tickets and without approving a live-record change"
    ],
    mustNot: [
      "Pick a ticket from the board, or swap your assigned ticket for a more interesting one",
      "Build something the ask did not describe, and call it part of the same ticket",
      "Release a change yourself, or change a live record, when the written yes is missing",
      "Leave a ticket that only you can understand",
      "Keep the best tickets for yourself while you are the deputy",
      "Say it is ready when you have not written the test and the leftovers"
    ],
    good: [
      "The ticket matches what finished looked like, and you did not add a surprise that touches a live record.",
      "The note says what changed, how you tested it, and what is left, and a colleague could follow it.",
      "A risky live record was stopped and told to the team lead, not shipped.",
      "You worked the blocked seat on your name before the new idea on your name."
    ],
    bad: [
      "You chose an interesting ticket from the board and left a broken seat.",
      "The next person has to call you to know what changed.",
      "A booking or a payment is missing after your change.",
      "You called it ready, and the note does not say how you tested it."
    ],
    records: [
      "What you changed, written on the ticket in plain words",
      "How you tested it, including what you tried and what you saw",
      "What you did not do, and anything you are unsure about",
      "The stop, if a live record had no written yes: what it would have touched, and the date you told the team lead"
    ],
    rules: [
      "If the ticket does not already have your name, it is not yours. Do not take it from the board.",
      "Done is what the ask said finished looks like, plus a note the next person can read. It is not “it works on my machine”.",
      "No written yes on a live record means you stop the same day and you do not hand it over as ready.",
      "A blocked seat with your name comes before a new idea with your name.",
      "You hand the ticket to the team lead. You do not quietly release it."
    ],
    detailed: {
      copy: {
        definition: "You build the tickets that have your name. You leave a note the next engineer can use, and you stop if a live record is at risk or the ask never said what finished looks like.",
        lanes: "Named tickets in the written order, a test note, and a stop when a live record has no written yes.",
        workflow: "Open your tickets, build only what was described, write the note, and hand it to the team lead without releasing it yourself."
      },
      definition: {
        what: [
          "You write and fix the product for tickets the team lead has assigned to you.",
          "You test the work against what the ask said finished looks like, and you write that test on the ticket.",
          "You protect live records by stopping when a written yes is not already there."
        ],
        why: [
          "Operations and Finance work inside what you change. A hidden booking is their failed job as well as yours.",
          "The next engineer cannot continue a change that lives in your memory.",
          "Picking your own ticket is how a blocked seat waits."
        ]
      },
      responsibilities: [
        card("Your name", "Do the ticket you were given, and do a blocked seat first", "Picking hides the broken seat, and the company keeps working around the tool.", "Open only the tickets with your name. If one is a blocked seat, do it before a new idea that also has your name. Do not open the board to see if something else looks better. If you disagree with the order, tell the team lead in writing. Do not swap the tickets yourself. When you finish a safe step and a block arrives on your name, write where you stopped and move to the block.", ["You can show which ticket you did first and why.", "A ticket without your name is not started.", "Being deputy does not let you assign yourself the work you prefer."], "role-swe.png"),
        card("The note", "Write what changed, how you tested it, and what is left", "The next person is the test. If they must call you, you have not finished.", "On the ticket, in plain words, say what you changed, what you ran or tried, what you saw, and what you did not do. Mention a risk you know about. Do not write “fixed” and stop. Do not put the real explanation in a private chat. Read the note once as if you had not built it. If it is unclear, rewrite it before you hand the ticket over.", ["The note is on the ticket, not in your head.", "A test you did not write down did not happen, for the next person.", "What is left includes anything you are unsure about."], "tec-ticket.png"),
        card("The stop", "Do not touch a live record because you think it will be fine", "A hidden booking is not a side effect. It is a failed change.", "If your change can erase, hide, or make it impossible to continue a booking, a payment, a lead, or a provider file, look for a written yes on the ticket. If it is not there, stop the same working day. Tell the team lead what would be touched. Do not release it. Do not bury the risk at the bottom of the note and mark the ticket ready. Extra behaviour the ask did not describe is also a stop: ask for a new ticket, do not sneak it in.", ["No yes, no change.", "You do not decide that the risk is small.", "You do not ask a colleague to approve it in a chat."], "tec-screen.png"),
        card("Deputy", "Assign tickets, and do not keep the ones you want to build", "The deputy role is not a way to choose your favourite work.", "Write one owner on each open ticket. Write that the team lead is on leave and the date. Give the next ticket to the person with the fewest open tickets. A blocked seat comes first. Do not put your own name on the interesting ones unless the count gives them to you. Do not approve a live-record change alone. When the team lead returns, stop assigning.", ["Only for the dates that were written.", "A risky ticket still goes to the Head of Technology.", "You still build, properly, any ticket the rule actually gives you."], "ctl-people.png")
      ],
      handoffs: [
        side(card("From the team lead", "One named ticket, with finished described", "You do not browse, and you do not start a ticket that does not say what done is.", "The ticket has your name and the ask. Read what finished looks like before you build. If that description is missing, send the ticket back the same day and do not start. If it is marked as a blocked seat, it comes before your other tickets. You should not need a verbal briefing that is not on the ticket.", ["No name, no work.", "A missing description of done is a return, not a chance to invent the feature."], "role-set.png"), "in"),
        side(card("To the team lead", "The change and the note, not a quiet release", "They check before it goes out. You do not put it live because you are confident.", "Hand back what changed, how you tested it, and what is left. Say if you are unsure. Leave the release to them. If your shift ends first, the note must already be on the ticket so they are not waiting for you to remember it tomorrow.", ["Ready means the note is written, not that you feel finished.", "They can continue or undo it from the note."], "tec-ticket.png"), "out"),
        side(card("To the team lead", "A live-record risk, stopped", "You do not decide the yes, and you do not shop around for someone who will say yes.", "The same working day, write what the change would touch and that you stopped. Leave the ticket unreleased. Do not continue “just the safe part” if you cannot separate it from the live record. Wait for the written answer on the ticket.", ["The stop is visible on the ticket.", "You do not tell another department the change is done."], "tec-screen.png"), "out")
      ],
      workflow: [
        card("Step 1", "Open the tickets with your name", "A blocked seat on your name comes before a new idea on your name.", "Do not open the board to choose. Read the order the team lead wrote. If you are deputy, assign first under those rules, then build only what the rules gave you.", ["No tickets means you tell the team lead you are clear. You do not hunt for work.", "You do not start a personal improvement on a live record."], "role-swe.png"),
        card("Step 2", "Build what finished looks like, and nothing that surprises a live record", "An extra feature is a new ticket. A risky extra is a stop.", "Follow the ask. If you discover you need to change a booking, a payment, a lead, or a provider file, stop and ask. Do not decide it is small. If the ask itself is vague, go back to the return. Do not fill the vagueness with your own design and call it the ask.", ["You can point at the sentence in the ask that your change satisfies.", "You did not rewrite the ask to match what you felt like building."], "tec-ticket.png"),
        card("Step 3", "Test it and write the note before you say it is ready", "The next engineer is the reader.", "Write what changed, how you tested it, and what is left, including doubts. Read it as a stranger. If it fails, fix the note. Do not hand over a ticket that says only “done”.", ["The note is on the ticket the same day.", "A test that is not written is not part of the handoff."], "tec-screen.png"),
        card("Step 4", "Hand it to the team lead and wait for the check", "You do not quietly release it to production.", "They compare it with the ask and with the live records. If they send it back, read the reason and fix that reason. Do not argue it out in a way that leaves no written note. The ticket stays with its history.", ["Released is their mark, not yours.", "A return is part of the job. Hiding the return is not."], "role-set.png")
      ],
      reporting: [
        timed(card("On the ticket", "The note is the report", "A separate status message that is not on the ticket will be lost.", "Before you say the ticket is ready, the ticket itself contains what changed, how you tested it, and what is left. If you stopped because of a live record, that stop is on the ticket the same working day, with what it would have touched. You do not report ready in a chat while the ticket is blank.", ["The team lead can read it without you.", "What you report and what the ticket says are the same thing."], "tec-ticket.png"), "When you hand it back, the same working day you finish the work or the stop")
      ],
      standards: [
        card("Order", "The broken seat on your name comes first", "A new idea can wait. A seat that cannot book cannot wait.", "You do not reverse the order the team lead wrote. If a blocked seat and a new idea both have your name, the block is the one you build. You ask in writing if you think that order is wrong. You do not switch quietly.", [], "role-swe.png"),
        card("The ask", "You build what was written", "An extra feature is a new ticket, even if it seems small.", "If the ask does not say what finished looks like, send it back to the team lead the same working day and do not start. You do not invent the finished state. When you do build, you can point at the words in the ask that your change follows.", [], "tec-ticket.png"),
        card("The note", "The next engineer can continue", "That is what finished means for your part, before the team lead even checks the behaviour.", "What changed, the test, and what is left are on the ticket in plain words. You read them as a stranger before you hand the ticket over.", [], "role-set.png"),
        card("Live records", "They stay unless a written yes is already on the ticket", "You look before you hand it over as ready.", "No written yes means you stopped, you wrote what would have been touched, and you did not release it. You did not decide the risk was acceptable.", [], "tec-screen.png")
      ],
      kpis: [
        kpi("You did the named tickets in the written order", "Every ticket with your name", "A new idea must not jump a blocked seat, or the company keeps working around a broken tool.", "If a blocked-seat ticket and a new idea both had your name, you finished or properly handed on the blocked seat before you started the new idea. You did not take any ticket that did not have your name."),
        kpi("Every ticket you hand back has a note the next engineer can continue", "Every ticket you hand back", "The author will be away. The note is how the work survives.", "What changed, how you tested it, and what is left are on the ticket before you call it ready. A colleague could follow that note without calling you. If they could not, you did not hand it back as ready."),
        kpi("You did not change a live record without a written yes", "None", "A missing booking or payment is a failed change, even if the rest of the ticket looks good.", "You did not change a booking, a payment, a lead, or a provider file unless the yes was already written on the ticket. Where there was no yes, you stopped and told the team lead the same day."),
        kpi("You did not pick a ticket", "Every ticket you touch", "Picking is how urgent work waits behind interesting work.", "Each ticket you worked already had your name, written by the team lead or by the deputy under the assignment rule. None were taken from the open board.")
      ],
      escalations: [
        timed(card("To the team lead", "The ask does not say what finished looks like", "You must not invent the feature, or you will build the wrong company.", "Send the ticket back to the team lead the same working day. Say that you cannot tell what finished looks like, so you have not started. Do not build a private version while you wait for a clearer ask.", [], "role-set.png"), "The same working day"),
        timed(card("To the team lead", "The change would touch a live record and there is no yes", "You do not release it, and you do not decide that the touch is harmless.", "Stop before you hand it over as ready. Write what it would touch. Leave it unreleased until a written yes is on the ticket.", [], "tec-screen.png"), "Before you hand it over as ready")
      ],
      glossary: glossTech
    }
  }, "technology");

  ["ceo", "hof", "fam", "ace", "hrm", "hre", "hot", "set", "swe"].forEach(function (id) {
    const role = G.roles[id];
    ["responsibilities", "handoffs", "workflow", "reporting", "standards", "escalations"].forEach(function (key) {
      (role.detailed[key] || []).forEach(function (item) {
        if (!item.art) item.art = role.hero;
      });
    });
    (role.lanes || []).forEach(function (lane) {
      if (!lane.art) lane.art = role.hero;
    });
  });

  const controlTab = (G.workflowTabs || []).find(function (tab) { return tab.id === "control"; });
  if (controlTab) delete controlTab.soon;
  if (!(G.workflowTabs || []).some(function (tab) { return tab.id === "technology"; })) {
    G.workflowTabs.push({ id: "technology", name: "Technology workflow" });
  }

  G.workflows.control = {
    id: "control",
    kicker: "Finance and administration",
    title: "Control workflow",
    lede: "Money is written in the books the day it moves. Each person has one file. A manager writes one name on each open item before anyone starts. Nobody types an amount that is not already in a written policy.",
    hero: "role-hof.png",
    result: "The books match the money, every person has one file another person can continue, and a blank amount is stopped instead of guessed.",
    promise: "A new accounts person or a new HR person can continue the books or the people file the next day without calling the person who wrote them.",
    sectionHeads: {
      rules: { file: "sec-rules.png", kicker: "Do not mix", title: "Hard rules", lede: "Finance writes what happened to the money. HR writes what happened to the person. Neither one invents a price or a salary, and neither one assigns a customer lead." },
      calendar: { file: "sec-when.png", kicker: "Rhythm", title: "When this runs", lede: "Each working day, open items get one owner and today’s receipts, cash notes, and attendance are written before the day ends. Each week, the head reads both reports against the records." },
      boundaries: { file: "sec-owns.png", kicker: "Boundaries", title: "Who owns which result", lede: "The head checks. The managers assign. The executives write. The CEO decides a new money or pay rule. Operations writes the booking. Control does not rewrite it." },
      paths: { file: "sec-how.png", kicker: "The paths", title: "A payment, and a person", lede: "A payment is written in full, sent back with the missing field, or held because the policy is blank. A person is hired only after a head has written the seat and the result." },
      seats: { file: "sec-do.png", kicker: "The seats", title: "What each Control person does", lede: "Each seat has one result. Open the seat to read the full steps, the records, and what to do when something is missing." }
    },
    rules: [
      "Do not invent a price, a booking advance, a discount, a refund, or a salary. If you cannot find it in the written policy, stop the same working day and ask. Do not type a temporary number.",
      "Do not pick work from a shared pile. The manager writes one name on each open item before anyone starts, oldest item first when the load is equal.",
      "Do not mark a booking paid while the customer side or the provider side is still open in the admin panel. Record the money that moved, and leave the booking open.",
      "Do not hire unless the head who needs the person has written the seat and the result that seat must produce. A busy week is not a gap.",
      "Do not put a person on a customer lead, a booking, or a provider job. When the people file is complete, Operations assigns work under its own rules.",
      "If a manager is on leave and no deputy was named, the Head of Finance and People writes one name that morning, before the list opens, and does not take the list. Until that name is written, new work waits."
    ],
    calendar: [
      { when: "Every working day", what: "The Finance and Accounts Manager writes one owner on every open receipt, invoice, cash note, and settlement. The HR and Administration Manager writes one owner on every open people task. Today’s money and today’s attendance are written in the file before the working day ends, or they are handed on with the next step written down." },
      { when: "Every week", what: "The Head of Finance and People reads the money report and the people report against the records. Any line that does not match is sent back the same day it is found. Amounts that are not in a written policy are listed as held, not as paid." },
      { when: "Before leave", what: "The manager names one deputy and the dates, in writing, before the leave starts. The deputy assigns and does not approve pay, a price, or a refund. If that name is still blank on the morning, the head writes one name before the list opens, and then stops." }
    ],
    boundaries: [
      { who: "Head of Finance and People", does: "Checks that the books and the people files match the truth. Stops an amount that is not in a written policy and sends it to the CEO. Names a missing deputy that morning and does not keep the list. Does not post the day’s invoices or hire the day’s person as their main job." },
      { who: "Finance & Accounts Manager", does: "Assigns every open money item to one person and checks the entry against the booking. Does not invent a price. Names a deputy before leave." },
      { who: "Accounts Executive", does: "Writes only the money items that already have their name, oldest first, with who, the booking or bill, the amount, the date, and who holds the cash. Sends a blank field back the same day." },
      { who: "HR & Administration Manager", does: "Opens a hire only from a written gap, keeps one file per person, and assigns the day’s people tasks. Does not set pay and does not assign a customer lead." },
      { who: "HR & Administration Executive", does: "Files the tasks that have their name, including attendance and the next step. Does not say a salary. Returns a missing paper the same day." },
      { who: "Operations", does: "Writes the cash note and both settlements on the booking. Control records that money and does not change the booking story to make the books look tidy." },
      { who: "CEO", does: "Decides a new money rule or pay rule in writing. Control does not invent one while it waits." }
    ],
    paths: [
      {
        id: "money",
        title: "A payment",
        art: "ctl-receipt.png",
        why: "Use this whenever money moves, including a receipt, an invoice, a settlement, and a cash note from Operations. The point is that a new person can see what was paid and what is still open.",
        when: "A receipt, an invoice, a cash note, or a settlement arrives, or an older one is still open from a previous day.",
        input: "A written item that names a booking or a bill. A cash note also names the customer, the amount, the provider, the date, and who holds the cash.",
        output: "An entry in the books that another person can continue, or a return that names the missing field, or a hold because the amount is not in the written policy. The booking stays open until both sides are settled.",
        fail: "Do not pick the item from a pile. Do not invent an amount. Do not mark the booking paid while either side is open. Do not leave today’s cash note unwritten and unassigned.",
        steps: [
          { who: "Finance & Accounts Manager", does: "Writes one owner on the item the same working day, choosing the person with the fewest open items, older items first. If this manager is on leave, the named deputy does this and does not keep the item. If no deputy was named, the list waits until the head writes a name that morning." },
          { who: "Accounts Executive", does: "Opens only items with their name. Writes who paid or was paid, the booking or the bill, the amount from the written policy or the note, the date, and who holds the cash. If a field is missing, sends the item back the same day and names the field." },
          { who: "Finance & Accounts Manager", does: "Checks the entry against the booking. If they disagree, sends it back or marks it open with the reason. If the amount is not in the written policy, does not post it." },
          { who: "Head of Finance and People", does: "When there is no written policy, sends the question to the CEO the same working day and does not type an amount. The booking stays open in the books until both sides are settled in the admin panel." }
        ]
      },
      {
        id: "people",
        title: "A person",
        art: "ctl-people.png",
        why: "Use this when someone is hired, when attendance is written, or when a people request is open. The point is one file that a new person can continue.",
        when: "A head asks for a person, a joining paper is still missing, attendance is due, or an office request is open.",
        input: "A written gap that names the seat and the result, or a people task the manager has already given to one person.",
        output: "One file and one role, with papers, attendance, and a next step, or a return that says the gap or the paper is missing. Pay that is not in policy is held, not promised.",
        fail: "Do not hire from a feeling. Do not promise pay. Do not assign a customer lead. Do not leave attendance to be remembered tomorrow.",
        steps: [
          { who: "The head who needs the person", does: "Writes the seat that is empty and the result that seat must produce. A verbal request is not enough." },
          { who: "HR & Administration Manager", does: "Opens the hire only if both are written. Otherwise sends the note back the same day and says what is missing. Assigns the file work to one person, oldest open task first." },
          { who: "HR & Administration Executive", does: "Writes the papers received, the papers still missing, the attendance from the record, and the next step. Sends back a missing paper or a pay question the same day, without saying a salary." },
          { who: "Head of Finance and People", does: "Checks that another person could continue the file. Sends a pay question to the CEO if the written policy is blank. Does not place the person on a booking." }
        ]
      }
    ]
  };

  G.workflows.technology = {
    id: "technology",
    kicker: "Technology and data",
    title: "Technology workflow",
    lede: "A change starts as a written ask that says what finished looks like. One engineer owns the ticket. It does not go out if it would hide a booking, a payment, a lead, or a provider file.",
    hero: "role-hot.png",
    result: "The tools are up enough for each seat to do its next step, and a release matches the ask without breaking a live record.",
    promise: "The next engineer can continue the ticket from the note, without calling the person who built it.",
    sectionHeads: {
      rules: { file: "sec-rules.png", kicker: "Do not mix", title: "Hard rules", lede: "Technology builds what was asked in writing. It does not invent a feature, it does not decide a price, and it does not assign a lead." },
      calendar: { file: "sec-when.png", kicker: "Rhythm", title: "When this runs", lede: "A blocked seat is written and assigned the same working day, before a new idea. Nothing is released until the team lead has checked the note and the live records." },
      boundaries: { file: "sec-owns.png", kicker: "Boundaries", title: "Who owns which result", lede: "The head checks that the tools work and that records survive. The team lead assigns and checks. The engineer builds the named ticket and writes the note." },
      paths: { file: "sec-how.png", kicker: "The path", title: "A change", lede: "From a written ask, to one owner, to a note the next person can read, to a release or a return. A live record waits for a written yes." },
      seats: { file: "sec-do.png", kicker: "The seats", title: "What each Technology person does", lede: "Each seat has one result. Open the seat for the full steps, including what to do when the ask is vague or a booking would be touched." }
    },
    rules: [
      "Do not pick a ticket from the board. The team lead writes one name before the engineer starts.",
      "Do not build an ask that does not say what finished looks like. Send it back the same working day.",
      "Do not release a change that can erase or hide a booking, a payment, a lead, or a provider file unless the head who owns that record has said yes in writing, and that yes is on the ticket.",
      "A seat that cannot do its next written step is assigned before a new idea, unless the Head of Technology has a written order that says otherwise.",
      "If the team lead is on leave and no deputy was named, the Head of Technology writes one name that morning and does not take the tickets. Until that name is written, new tickets wait."
    ],
    calendar: [
      { when: "Every working day", what: "Open tickets have one owner. If a seat cannot do its next written step, that block is written down and assigned before a new idea is started. The engineer’s note is on the ticket before anyone calls the work ready." },
      { when: "Before a release", what: "The team lead checks the ask, the note of what changed and how it was tested, and the live records. If a record is at risk and there is no written yes, the release waits." },
      { when: "Before leave", what: "The team lead names one engineer and the dates. That person assigns and does not approve a live-record change alone. If the name is blank, the head writes it that morning before tickets are taken." }
    ],
    boundaries: [
      { who: "Head of Technology & Data", does: "Sees that the seats can do their next step, and that a release did not break a live record. Orders the asks. Names a missing deputy and does not keep the tickets. Does not write the day’s code as their main job." },
      { who: "Software Engineer Team Lead", does: "Assigns every ticket to one engineer, blocked seats first, and checks the note and the records before release." },
      { who: "Software Engineer", does: "Build only the tickets with their name, write what changed and how they tested it, and stop if a live record has no written yes." },
      { who: "The head who owns the record", does: "Writes yes or no before a live booking, payment, lead, or provider file is changed. Silence is not a yes." },
      { who: "CEO", does: "Hears the same working day if a tool the seats need is down, including what is broken and who owns the fix." }
    ],
    paths: [
      {
        id: "change",
        title: "A change",
        art: "tec-ticket.png",
        why: "Use this for a fix or for a new piece of the product. The point is that a new engineer can see what was asked, who built it, and whether a live record was protected.",
        when: "A seat cannot do the next step of its job, or a head asks for a change in writing.",
        input: "A written ask that says what is needed, who asked, and what finished looks like.",
        output: "A release that matches that description, with a note the next engineer can continue, and with bookings, payments, leads, and provider files still in place. Or a return that says what is missing.",
        fail: "Do not pick from the board. Do not invent the feature. Do not ship a risk to a live record. Do not call it done because the code runs on one machine.",
        steps: [
          { who: "Head of Technology & Data", does: "Writes the block the same working day if a seat cannot do its next step, and orders the asks so that block comes before a new idea. Sends back an ask that does not say what finished looks like. Does not take the ticket." },
          { who: "Software Engineer Team Lead", does: "Gives the ticket to one engineer, the person with the fewest open tickets. If this lead is on leave, the named deputy assigns and does not keep the ticket. If no deputy was named, new tickets wait until the head writes a name." },
          { who: "Software Engineer", does: "Builds what finished looks like and nothing that surprises a live record. Writes what changed, how it was tested, and what is left. Stops the same day if a live record has no written yes, and does not mark the ticket ready." },
          { who: "Software Engineer Team Lead", does: "Checks the note and the live records. Releases the change or sends it back the same day the check is finished, with the reason written." },
          { who: "Head of Technology & Data", does: "If the change touches a booking, a payment, a lead, or a provider file, gets a written yes from the head who owns that record before it goes out, and keeps that yes on the ticket." }
        ]
      }
    ]
  };
})();

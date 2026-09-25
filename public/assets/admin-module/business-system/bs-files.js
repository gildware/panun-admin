window.PK_FILES = [
  {
    id: "direction",
    name: "Direction note",
    kicker: "Company file 1",
    owner: "CEO",
    ownerRole: "ceo",
    art: "role-ceo.png",
    lede: "The one current page that says what Panun Kaergar sells, which towns it serves, and which work it refuses.",
    purpose: [
      "Every other company file waits on this note. A price can only be written for a service that is on this note. Growth can only spend in a town that is on this note. Marketing can only talk about a service and a town that are on this note.",
      "There is one current note. A meeting, a chat, or an old slide is not the direction. When the note changes, the old note is marked replaced the same working day, the date is written on both notes, and the four heads receive the new note."
    ],
    uses: [
      "Head of Growth signs a month only from the services and towns on the current note.",
      "Offer & Service Development does not add a service until the CEO has written it here.",
      "Market Expansion does not open a town until the CEO has written it here.",
      "Customer Experience does not book a job the note refuses."
    ],
    contains: [
      { title: "Services we sell now", body: "Each service by its name, in the words a customer would use. A draft service is not on this list." },
      { title: "Towns we serve now", body: "Each town by name. A town that is only circled on a map is not on this list." },
      { title: "Work we refuse", body: "The jobs, places, and promises the company will not take. If it is not written here, people will guess." },
      { title: "The date this version became current", body: "A new person uses this date to know which note is the one to follow." }
    ],
    current: [
      { field: "Services we sell now", value: "Not written yet", note: "The CEO writes each service. Do not add a service from memory or from an old ad." },
      { field: "Towns we serve now", value: "Not written yet", note: "The CEO writes each town. Do not add a town because a lead arrived from there." },
      { field: "Work we refuse", value: "Not written yet", note: "The CEO writes each refusal in a full sentence." },
      { field: "Date this version became current", value: "Not written yet", note: "Write the date on the day the CEO signs this note." }
    ],
    change: [
      "The CEO writes the new note. The CEO marks the old note replaced and writes the date on both.",
      "The CEO sends the new note to the Head of Growth, the Head of Operations, the Head of Finance and People, and the Head of Technology and Data the same working day.",
      "Until that note is sent, the old written note is still what people follow."
    ],
    blank: "If a service, a town, or a refusal is not on the current note, stop. Do not invent it. Ask the CEO the same working day."
  },
  {
    id: "price",
    name: "Approved price file",
    kicker: "Company file 2",
    owner: "Head of Finance and People",
    ownerRole: "hof",
    art: "ctl-books.png",
    lede: "The only prices Customer Experience may say, and the only prices Marketing may print.",
    purpose: [
      "A price that is not in this file is not a price. Customer Experience stops and asks the manager. Marketing does not put the number on an ad or a flyer.",
      "Each row is one service that is already on the current direction note. Finance writes the amount. The CEO does not invent a price in a meeting."
    ],
    uses: [
      "Customer Experience reads the row for that service before saying a number.",
      "Offer & Service Development does not tell Marketing a price. Finance writes it here first.",
      "A discount or a refund is not a price. Those amounts live in the payment policy, not in this file."
    ],
    contains: [
      { title: "The service name", body: "The same name as on the current direction note." },
      { title: "What the price includes", body: "The work the customer is paying for, in full sentences." },
      { title: "What the price does not include", body: "The work that would be a different job or a later visit." },
      { title: "The signed price", body: "The amount Finance has written. If this cell is empty, there is no price." },
      { title: "The date Finance signed it", body: "So a new person can see which row is current." }
    ],
    current: [
      { field: "Rows", value: "None yet", note: "No signed prices were supplied. Do not type a rupee amount from memory. Copy a price into this file only after Finance has signed it." },
      { field: "Who may add a row", value: "Head of Finance and People", note: "The service must already be on the current direction note. Offer writes what is in and what is out. Finance writes the amount." }
    ],
    change: [
      "Offer writes what the service includes and what it does not include.",
      "Finance writes the amount and the date, and the Head of Finance and People signs the row.",
      "The old row stays, marked replaced, with the date. Customer Experience and Marketing use only the current row."
    ],
    blank: "If the signed price cell is empty, do not say a number to the customer and do not print a number. Ask the Customer Experience Manager the same day. The manager asks Finance. Nobody types a temporary price."
  },
  {
    id: "payment",
    name: "Payment policy",
    kicker: "Company file 3",
    owner: "Head of Finance and People",
    ownerRole: "hof",
    art: "ctl-receipt.png",
    lede: "The booking advance, the discount, and the refund, written once, so nobody chooses an amount on a call.",
    purpose: [
      "When a customer agrees to book, Customer Experience collects the booking advance this policy already states for that service. They do not choose a different amount.",
      "A discount, a refund, or money back is allowed only when this policy names it. If the policy is silent, the amount is not promised and it is not posted."
    ],
    uses: [
      "Customer Experience reads the booking advance for that service before taking money.",
      "The Customer Experience Manager does not approve an amount that is not in this policy.",
      "Finance posts only an amount this policy, or the approved price file, already contains.",
      "The four money lines use this policy for the booking advance. The other three lines come from the admin panel: customer payment, provider settlement, and cash still open with who holds it."
    ],
    contains: [
      { title: "Booking advance for each service", body: "The service name from the direction note, and the advance amount Finance has written. If the amount is empty, no advance is collected." },
      { title: "When the rest of the customer payment is taken", body: "The written step, in a full sentence. Do not leave it as “later”." },
      { title: "Discount", body: "Who may allow it, for which service, and the amount. If this section is empty, nobody may offer a discount." },
      { title: "Refund", body: "Who may allow it, in which cases, and the amount. If this section is empty, nobody may promise a refund." }
    ],
    current: [
      { field: "Booking advance", value: "Not written yet", note: "No rupee amount was supplied. Leave this blank. Do not invent an advance." },
      { field: "When the rest is taken", value: "Not written yet", note: "Finance writes the step. Until it is written, Customer Experience asks the manager before taking more money." },
      { field: "Discount", value: "Not written yet", note: "Until a discount is written here, no discount is offered." },
      { field: "Refund", value: "Not written yet", note: "Until a refund is written here, no refund is promised." }
    ],
    change: [
      "The Head of Finance and People writes the new amount or the new rule, with the date.",
      "The old line is marked replaced. It is not deleted.",
      "Customer Experience and Accounts use the new line only after that date is on the file."
    ],
    blank: "If the booking advance for that service is blank, stop. Tell the customer you will confirm the amount. Ask the Customer Experience Manager the same day. Do not collect a number you chose."
  },
  {
    id: "growth-plan",
    name: "Monthly Growth plan",
    kicker: "Company file 4",
    owner: "Head of Growth",
    ownerRole: "hog",
    art: "role-hog.png",
    lede: "The signed month: who we sell to, which services, which towns, how much money, and what we will not advertise.",
    purpose: [
      "No Growth money is spent until this plan is signed for that month. Digital and Field do not write their own month.",
      "The services and towns on this plan must already be on the current direction note. The prices in any sentence must already be in the approved price file."
    ],
    uses: [
      "Marketing Manager drafts the marketing page. The Head of Growth signs the plan. The CEO has already signed the money ceiling.",
      "Digital Marketing Manager and Field Marketing Manager work only from the written brief given the day the plan is signed.",
      "The CEO’s weekly money note compares growth spend that week with the amount already signed on this plan."
    ],
    contains: [
      { title: "The month and the date it was signed", body: "Spend before this date is not allowed." },
      { title: "Who we sell to this month", body: "In full sentences, not a slogan." },
      { title: "Services and towns", body: "Copied from the current direction note. A service or town that is not on that note does not go on this plan." },
      { title: "Money", body: "The ads amount and the stall amount, written separately. The ceiling the CEO signed." },
      { title: "What good looks like", body: "The result for ads, for search, and for stalls, in words a new person can check." },
      { title: "What we will not advertise", body: "The list for this month. A test that is not on the plan does not go live." }
    ],
    current: [
      { field: "This month’s plan", value: "Not written yet", note: "No signed month was supplied. Do not spend Growth money, set a stall, or run an ad until the Head of Growth has signed this file." },
      { field: "Money ceiling", value: "Not written yet", note: "The CEO writes the ceiling. Do not type a remembered budget." }
    ],
    change: [
      "A change in the middle of the month is a new signature from the Head of Growth before anyone starts the new line.",
      "Marketing Manager then gives Digital and Field the new written brief the same day.",
      "The old plan stays, marked replaced, with the date."
    ],
    blank: "If this month’s plan is not signed, Growth does not spend. Marketing tells Digital and Field to wait. The weekly Growth review still happens, and it says the plan is not signed."
  },
  {
    id: "kit",
    name: "Marketing kit",
    kicker: "Company file 5",
    owner: "Marketing Manager",
    ownerRole: "mkm",
    art: "role-mkm.png",
    lede: "The only words, look, prices, towns, and faces an ad, a search page, or a stall may use this month.",
    purpose: [
      "Digital and Field copy from this kit. They do not invent a sentence, a price, a town, or a face.",
      "A price in the kit is copied from the approved price file. It is not typed again from memory. A town in the kit is copied from the current direction note and from the signed Growth plan."
    ],
    uses: [
      "Digital Marketing Manager checks each video, post, and search page against this kit before it is published.",
      "Field Marketing Manager gives Field Visitor only the boards and flyers that match this kit.",
      "If someone wants a new sentence, they stop. Marketing Manager says yes in writing, or no in writing. A new service or a new town goes to the Head of Growth first."
    ],
    contains: [
      { title: "Look", body: "The logo, the colours, and the faces that may appear. A face that is not listed is not used." },
      { title: "Sentences", body: "The exact sentences ads, search pages, and stalls may say. Each sentence matches a service on the direction note." },
      { title: "Prices", body: "Copied from the current row of the approved price file, with the date of that row." },
      { title: "Towns", body: "Copied from the signed Growth plan for this month." },
      { title: "What we will not say", body: "The claims that are forbidden this month, in full sentences." }
    ],
    current: [
      { field: "This month’s kit", value: "Not written yet", note: "No kit was supplied. Do not publish an ad, a post, or a flyer until Marketing Manager has written this file for the signed month." },
      { field: "Sentences, prices, towns, and faces", value: "Not written yet", note: "Leave them blank. Do not borrow last month’s flyer." }
    ],
    change: [
      "Marketing Manager adds the new sentence, price, town, or face, with the date.",
      "If it is a new service, a new town, or a new price, it is not added until the direction note, the Growth plan, or the price file already allows it.",
      "Digital and Field are told the same day. The old kit line is marked replaced."
    ],
    blank: "If the kit does not contain the sentence, stop. Do not publish. Ask Marketing Manager the same day. Do not write a temporary line on the ad while you wait."
  }
];

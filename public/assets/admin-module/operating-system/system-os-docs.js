window.PK_OS = {
  "title": "Master Operating System",
  "sections": [
    [
      "purpose",
      "01. Purpose"
    ],
    [
      "role",
      "02. Role"
    ],
    [
      "responsibilities",
      "03. Responsibilities"
    ],
    [
      "processes",
      "04. Processes"
    ],
    [
      "sops",
      "05. SOPs"
    ],
    [
      "daily",
      "06. Daily work"
    ],
    [
      "weekly",
      "07. Weekly work"
    ],
    [
      "monthly",
      "08. Monthly work"
    ],
    [
      "kpis",
      "09. KPIs"
    ],
    [
      "checklists",
      "10. Checklists"
    ],
    [
      "forms",
      "11. Forms & records"
    ],
    [
      "reports",
      "12. Reports"
    ],
    [
      "handoffs",
      "13. Handoffs"
    ],
    [
      "escalation",
      "14. Escalation"
    ],
    [
      "improvement",
      "15. Continuous improvement"
    ]
  ],
  "tree": [
    {
      "id": "growth",
      "name": "Growth",
      "purpose": "Where and how the business grows.",
      "modules": [
        "mi",
        "mkt",
        "sd",
        "me",
        "pc"
      ]
    },
    {
      "id": "operations",
      "name": "Operations",
      "purpose": "How demand is converted, fulfilled, billed and quality-checked.",
      "modules": [
        "lms",
        "co",
        "po",
        "rsm",
        "qm"
      ]
    },
    {
      "id": "control",
      "name": "Control",
      "purpose": "Supports every function with money, people and systems.",
      "modules": [
        "fpc",
        "hr",
        "td"
      ]
    }
  ],
  "cycle": [
    {
      "pack": "mi",
      "artifact": null,
      "label": "Research and recommend"
    },
    {
      "pack": "mkt",
      "artifact": null,
      "label": "Create demand"
    },
    {
      "pack": "lms",
      "artifact": null,
      "label": "Convert enquiry"
    },
    {
      "pack": null,
      "artifact": "Confirmed booking",
      "label": "Handoff object"
    },
    {
      "pack": "co",
      "artifact": null,
      "label": "Coordinate fulfilment"
    },
    {
      "pack": "po",
      "artifact": null,
      "label": "Deploy provider"
    },
    {
      "pack": null,
      "artifact": "Service delivery",
      "label": "Work done"
    },
    {
      "pack": "cx",
      "artifact": null,
      "label": "Support the customer"
    },
    {
      "pack": null,
      "artifact": "Service completion",
      "label": "Handoff object"
    },
    {
      "pack": "rsm",
      "artifact": null,
      "label": "Bill, collect, settle"
    },
    {
      "pack": "qm",
      "artifact": null,
      "label": "Check and correct"
    },
    {
      "pack": null,
      "artifact": "Feedback / corrective action",
      "label": "Learning object"
    },
    {
      "pack": "mi",
      "artifact": null,
      "label": "Feed the next growth cycle"
    }
  ],
  "controlSpan": {
    "title": "Control runs across the entire system",
    "note": "Finance, HR and Technology do not sit in the booking flow. They support every Growth and Operations module.",
    "arms": [
      {
        "pack": "fpc",
        "supports": "Budgets, cash, expense control and management accounts for every function."
      },
      {
        "pack": "hr",
        "supports": "Employees — not providers — so the company has the people to run the functions."
      },
      {
        "pack": "td",
        "supports": "Systems, access, data quality, backup and the reports every function uses."
      }
    ]
  },
  "handoffs": [
    {
      "id": "h01",
      "from": "mi",
      "to": "sd",
      "sop": "MI-SOP-06",
      "object": "New service we should sell",
      "plain": "Customers keep asking for a job we do not sell yet. Research has checked demand and competitors. Service Development must now design the actual offer — Marketing must not advertise it yet.",
      "trigger": "When the opportunity is marked Validated and the validation report is attached",
      "sendBack": "No count of real requests, no area, or marked Validated with no report.",
      "nextDoes": "Service Development writes the offer (what is included, what is not, how the job is done). They do not run ads.",
      "packet": [
        {
          "field": "Opportunity ID",
          "value": "OPP-019"
        },
        {
          "field": "What customers asked for",
          "value": "AC gas refill as its own visit — not bundled with full servicing"
        },
        {
          "field": "Area",
          "value": "Srinagar City"
        },
        {
          "field": "How many asks, when",
          "value": "28 unmet requests, 1–21 Sep"
        },
        {
          "field": "Who already sells it",
          "value": "CoolHome, ₹499–799"
        },
        {
          "field": "Can we deliver it?",
          "value": "Yes, if 6 gas-certified providers are active"
        },
        {
          "field": "Priority",
          "value": "High"
        },
        {
          "field": "Attached",
          "value": "Validation Report"
        }
      ],
      "mustInclude": [
        "Opportunity ID",
        "What customers asked for",
        "Area",
        "How many asks, when",
        "Who already sells it",
        "Can we deliver it?",
        "Priority",
        "Attached"
      ]
    },
    {
      "id": "h02",
      "from": "mi",
      "to": "me",
      "sop": "MI-SOP-05",
      "object": "Area we do not cover well",
      "plain": "People from a named place are asking, and we barely serve them. This is evidence for Expansion to study — it is not permission to launch ads there.",
      "trigger": "When a named area has uncovered or thin coverage and the demand weeks are attached",
      "sendBack": "A vague place like “south Kashmir”, or demand with no week and no enquiry count.",
      "nextDoes": "Market Expansion researches whether to enter. They do not tell Marketing to advertise.",
      "packet": [
        {
          "field": "Area",
          "value": "Pulwama town"
        },
        {
          "field": "Weeks checked",
          "value": "8–21 Sep"
        },
        {
          "field": "Enquiries vs bookings",
          "value": "14 enquiries, 2 bookings"
        },
        {
          "field": "Our coverage today",
          "value": "No — we do not serve Pulwama"
        },
        {
          "field": "Top asks",
          "value": "Plumbing leak, electrical fault"
        },
        {
          "field": "Local competitor",
          "value": "One electrician shop, no platform"
        }
      ],
      "mustInclude": [
        "Area",
        "Weeks checked",
        "Enquiries vs bookings",
        "Our coverage today",
        "Top asks",
        "Local competitor"
      ]
    },
    {
      "id": "h03",
      "from": "mi",
      "to": "mkt",
      "sop": "MI-SOP-08",
      "object": "This week's demand brief",
      "plain": "What Marketing may talk about this week, where, and which claims are forbidden. This is not a campaign plan.",
      "trigger": "When the weekly Market Intelligence report is submitted",
      "sendBack": "A dump of every tracker with no “push these / do not claim these”, or a request to “just make a campaign”.",
      "nextDoes": "Marketing plans posts and ads only on services already approved, in areas already live.",
      "packet": [
        {
          "field": "Week",
          "value": "15–21 Sep"
        },
        {
          "field": "Push these services",
          "value": "AC servicing, AC gas refill (once launched)"
        },
        {
          "field": "Push these areas",
          "value": "Srinagar City, Bemina"
        },
        {
          "field": "Do not advertise",
          "value": "Pulwama — we cannot fulfil there"
        },
        {
          "field": "Do not claim",
          "value": "₹499 AC service (CoolHome price — not ours unless Finance and Service Development agree)"
        },
        {
          "field": "Competitor change",
          "value": "CoolHome started same-day AC ads in Srinagar"
        }
      ],
      "mustInclude": [
        "Week",
        "Push these services",
        "Push these areas",
        "Do not advertise",
        "Do not claim",
        "Competitor change"
      ]
    },
    {
      "id": "h04",
      "from": "mi",
      "to": "pc",
      "sop": "MI-SOP-05",
      "object": "Customers we cannot reach ourselves",
      "plain": "A type of customer we miss unless someone else sends them (hotel desk, shop, platform). This is not a signed partner and not “my uncle knows someone”.",
      "trigger": "When a repeating customer gap is recorded with a suggested channel type",
      "sendBack": "A named person with no “which customers we miss”, or one random enquiry.",
      "nextDoes": "Partnerships finds and qualifies a real partner of that type. Leads still go to Sales later.",
      "packet": [
        {
          "field": "Who we miss",
          "value": "Gulmarg hotel guests who want same-day AC from the reception desk"
        },
        {
          "field": "Suggested channel",
          "value": "Hotel reception / concierge"
        },
        {
          "field": "Evidence",
          "value": "11 tourist enquiries in Aug–Sep named a hotel, no hotel partner on file"
        },
        {
          "field": "Not this",
          "value": "Do not treat a verbal introduction as a partner"
        }
      ],
      "mustInclude": [
        "Who we miss",
        "Suggested channel",
        "Evidence",
        "Not this"
      ]
    },
    {
      "id": "h05",
      "from": "sd",
      "to": "mkt",
      "sop": "SD-SOP-05",
      "object": "Service Marketing may advertise",
      "plain": "The offer is live. Ads may only use this name, these inclusions, and these approved sentences.",
      "trigger": "When the service is launched or approved to sell",
      "sendBack": "A draft name, or a promise Service Development has not signed.",
      "nextDoes": "Marketing builds campaigns from this sheet only. They do not add “free extra” lines.",
      "packet": [
        {
          "field": "Service name",
          "value": "AC gas refill"
        },
        {
          "field": "What the customer gets",
          "value": "Visit, leak check, refill to spec"
        },
        {
          "field": "What they do not get",
          "value": "Compressor repair, new AC install"
        },
        {
          "field": "Approved sentence",
          "value": "Same-day slots in Srinagar City where a certified provider is free"
        },
        {
          "field": "Forbidden sentence",
          "value": "Cheapest in Kashmir / any ₹499 claim"
        },
        {
          "field": "Live from",
          "value": "12 Sep"
        }
      ],
      "mustInclude": [
        "Service name",
        "What the customer gets",
        "What they do not get",
        "Approved sentence",
        "Forbidden sentence",
        "Live from"
      ]
    },
    {
      "id": "h06",
      "from": "sd",
      "to": "lms",
      "sop": "SD-SOP-03",
      "object": "What Sales may promise",
      "plain": "The script for converting an enquiry: what is in the job, what is not, so Sales does not invent scope on the call.",
      "trigger": "When the service catalogue is updated",
      "sendBack": "“We’ll figure it out on site” with no inclusions list.",
      "nextDoes": "Sales qualifies the enquiry against this sheet and either books it or closes it honestly.",
      "packet": [
        {
          "field": "Service name",
          "value": "Deep cleaning — 2BHK"
        },
        {
          "field": "Duration / team",
          "value": "4 hours, 2 people"
        },
        {
          "field": "Included",
          "value": "Floors, kitchen, bathrooms, chemicals"
        },
        {
          "field": "Do not promise",
          "value": "Sofa shampoo, carpet steam, or extra rooms unless that service is added to the booking"
        },
        {
          "field": "Price rule",
          "value": "Use the catalogue price. Do not discount at the desk."
        }
      ],
      "mustInclude": [
        "Service name",
        "Duration / team",
        "Included",
        "Do not promise",
        "Price rule"
      ]
    },
    {
      "id": "h07",
      "from": "sd",
      "to": "co",
      "sop": "SD-SOP-05",
      "object": "How this job must be done",
      "plain": "The steps and the pass/fail bar for the visit. Operations cannot invent a method on the day.",
      "trigger": "When the service is operationally ready",
      "sendBack": "A marketing blurb with no steps and no “done means”.",
      "nextDoes": "Customer Operations uses this to schedule, brief the provider, and close the job.",
      "packet": [
        {
          "field": "Service name",
          "value": "Plumbing — leaking tap"
        },
        {
          "field": "Steps on site",
          "value": "1 Isolate water  2 Find leak  3 Repair/replace  4 Test 5 minutes  5 Photo of dry joint"
        },
        {
          "field": "Done means",
          "value": "No drip after 10 minutes; photo on the job card"
        },
        {
          "field": "Customer must provide",
          "value": "Access to the tap and the stopcock"
        }
      ],
      "mustInclude": [
        "Service name",
        "Steps on site",
        "Done means",
        "Customer must provide"
      ]
    },
    {
      "id": "h08",
      "from": "sd",
      "to": "po",
      "sop": "SD-SOP-03",
      "object": "Providers this service needs",
      "plain": "Skills, tools and areas required before we sell this at volume. Service Development does not recruit technicians.",
      "trigger": "When a new or changed service needs skills or tools the network may not have",
      "sendBack": "“Need more people” with no skill, tool, or area.",
      "nextDoes": "Provider Operations finds, checks and activates people who match this list.",
      "packet": [
        {
          "field": "Service",
          "value": "AC gas refill"
        },
        {
          "field": "Skill required",
          "value": "Licensed refrigerant handling"
        },
        {
          "field": "Tools required",
          "value": "Gauges, vacuum pump, approved gas"
        },
        {
          "field": "Areas",
          "value": "Srinagar City, Bemina"
        },
        {
          "field": "Minimum active providers",
          "value": "6 before Marketing scales spend"
        }
      ],
      "mustInclude": [
        "Service",
        "Skill required",
        "Tools required",
        "Areas",
        "Minimum active providers"
      ]
    },
    {
      "id": "h09",
      "from": "me",
      "to": "po",
      "sop": "ME-SOP-04",
      "object": "How many providers a new area needs",
      "plain": "The expansion plan is approved. Provider Operations must build that bench before anyone advertises the area.",
      "trigger": "When the expansion plan is approved",
      "sendBack": "“Launch Pulwama” with no numbers or skills.",
      "nextDoes": "Provider Operations recruits and activates that coverage, then reports ready or not ready.",
      "packet": [
        {
          "field": "Area",
          "value": "Pulwama town"
        },
        {
          "field": "Launch services",
          "value": "Plumbing, electrical"
        },
        {
          "field": "Providers needed",
          "value": "8 verified: 4 plumbers, 4 electricians"
        },
        {
          "field": "Must also have",
          "value": "Live availability calendar before go-live"
        }
      ],
      "mustInclude": [
        "Area",
        "Launch services",
        "Providers needed",
        "Must also have"
      ]
    },
    {
      "id": "h10",
      "from": "me",
      "to": "mkt",
      "sop": "ME-SOP-05",
      "object": "You may advertise this area now",
      "plain": "Operations can take jobs here from this date, for these live services only.",
      "trigger": "When the area is ready for demand — after providers and Operations have signed ready",
      "sendBack": "A hoped-for date with no Operations go-live.",
      "nextDoes": "Marketing runs area ads from that date, tagged to the area. They do not add unlisted services.",
      "packet": [
        {
          "field": "Area",
          "value": "Bemina"
        },
        {
          "field": "Services you may advertise",
          "value": "Plumbing, electrical, AC servicing"
        },
        {
          "field": "Launch date",
          "value": "12 Oct"
        },
        {
          "field": "Do not mention yet",
          "value": "Masonry"
        }
      ],
      "mustInclude": [
        "Area",
        "Services you may advertise",
        "Launch date",
        "Do not mention yet"
      ]
    },
    {
      "id": "h11",
      "from": "me",
      "to": "lms",
      "sop": "ME-SOP-05",
      "object": "Sales may book this area",
      "plain": "The area is live on the booking map. Sales must not convert enquiries from places we still do not serve.",
      "trigger": "When the market is launched",
      "sendBack": "“We can try Pulwama” with no live flag.",
      "nextDoes": "Sales marks the area bookable. Out-of-area enquiries are closed or waitlisted — no fake slot.",
      "packet": [
        {
          "field": "Bookable areas",
          "value": "Srinagar City, Bemina"
        },
        {
          "field": "Not live",
          "value": "Pulwama — close or waitlist"
        },
        {
          "field": "Live from",
          "value": "12 Oct"
        }
      ],
      "mustInclude": [
        "Bookable areas",
        "Not live",
        "Live from"
      ]
    },
    {
      "id": "h12",
      "from": "me",
      "to": "co",
      "sop": "ME-SOP-05",
      "object": "Operations is ready for this area",
      "plain": "Customer Operations has signed that the board, scripts and backup desk can take jobs on day one.",
      "trigger": "Before Marketing is allowed to launch the area",
      "sendBack": "Marketing already live and Operations was never asked.",
      "nextDoes": "Customer Operations opens the area on the board and staffs the launch window.",
      "packet": [
        {
          "field": "Area",
          "value": "Bemina"
        },
        {
          "field": "Go-live signed by",
          "value": "Customer Operations"
        },
        {
          "field": "Assignment rule",
          "value": "A provider must be assigned within 2 hours"
        },
        {
          "field": "If it breaks",
          "value": "Named backup desk + exception route"
        },
        {
          "field": "CX scripts",
          "value": "Updated for Bemina"
        }
      ],
      "mustInclude": [
        "Area",
        "Go-live signed by",
        "Assignment rule",
        "If it breaks",
        "CX scripts"
      ]
    },
    {
      "id": "h13",
      "from": "mkt",
      "to": "lms",
      "sop": "MKT-SOP-06",
      "object": "New enquiry from our ads",
      "plain": "A person asked for a service because of our marketing. Every enquiry must say which ad, which service, which place — or Sales cannot work it and Marketing cannot be judged.",
      "trigger": "When a lead is captured with a source tag",
      "sendBack": "A screenshot of a comment with no phone, no area, or no campaign name.",
      "nextDoes": "Sales owns the lead until it is booked, lost, or marked invalid.",
      "packet": [
        {
          "field": "Customer",
          "value": "Farooq Ahmad, +91 9xxxx 4418"
        },
        {
          "field": "Service asked",
          "value": "AC servicing"
        },
        {
          "field": "Area",
          "value": "Rajbagh, Srinagar City"
        },
        {
          "field": "Source",
          "value": "Paid Facebook / Instagram"
        },
        {
          "field": "Campaign",
          "value": "AC-Sept-Cool"
        },
        {
          "field": "When they asked",
          "value": "18 Sep, 10:14"
        }
      ],
      "mustInclude": [
        "Customer",
        "Service asked",
        "Area",
        "Source",
        "Campaign",
        "When they asked"
      ]
    },
    {
      "id": "h14",
      "from": "pc",
      "to": "lms",
      "sop": "PC-SOP-05",
      "object": "New enquiry from a partner",
      "plain": "The same as an ad enquiry, but it arrived through a partner. Sales still converts it. The partner ID must travel so we know if that channel is any good.",
      "trigger": "When a partner lead is recorded",
      "sendBack": "“The hotel sent someone” with no partner ID, no customer need, no area.",
      "nextDoes": "Sales works it like any other lead, tagged to that partner.",
      "packet": [
        {
          "field": "Partner",
          "value": "Hotel Darbar reception (HT-012)"
        },
        {
          "field": "Customer need",
          "value": "Same-day plumber, leaking bathroom tap"
        },
        {
          "field": "Area",
          "value": "Lal Chowk"
        },
        {
          "field": "Guest / contact",
          "value": "Reception will share room-214 WhatsApp"
        },
        {
          "field": "Source tag",
          "value": "partner / HT-012"
        }
      ],
      "mustInclude": [
        "Partner",
        "Customer need",
        "Area",
        "Guest / contact",
        "Source tag"
      ]
    },
    {
      "id": "h15",
      "from": "mkt",
      "to": "fpc",
      "sop": "MKT-SOP-07",
      "object": "What we actually spent on ads",
      "plain": "Rupees out the door against the approved campaign budget — with a document. Finance cannot book “about 40 thousand”.",
      "trigger": "When spend is recorded against an approved budget line",
      "sendBack": "A round number with no campaign name, period, or invoice / platform export.",
      "nextDoes": "Finance books the actuals on that budget line.",
      "packet": [
        {
          "field": "Campaign",
          "value": "AC-Sept-Cool — Meta"
        },
        {
          "field": "Amount",
          "value": "₹38,400"
        },
        {
          "field": "Period",
          "value": "1–15 Sep"
        },
        {
          "field": "Budget line",
          "value": "Paid social — September"
        },
        {
          "field": "Attached",
          "value": "Invoice + Meta export"
        }
      ],
      "mustInclude": [
        "Campaign",
        "Amount",
        "Period",
        "Budget line",
        "Attached"
      ]
    },
    {
      "id": "h16",
      "from": "lms",
      "to": "co",
      "sop": "LMS-SOP-04",
      "object": "Confirmed booking",
      "plain": "The customer has agreed to a dated, priced job from the catalogue. This is the only object that starts fulfilment. A warm lead is not a booking.",
      "trigger": "When Sales has created and confirmed the booking with the customer",
      "sendBack": "No phone, no house address, no time window, or a service not in the catalogue.",
      "nextDoes": "Customer Operations checks the packet, puts it on the board, and asks for a provider.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4418"
        },
        {
          "field": "Customer",
          "value": "Farooq Ahmad, +91 9xxxx 4418"
        },
        {
          "field": "Service",
          "value": "AC servicing (catalogue)"
        },
        {
          "field": "Address / area",
          "value": "House 12, Rajbagh, Srinagar City — 2BHK"
        },
        {
          "field": "When",
          "value": "19 Sep, 11:00–13:00"
        },
        {
          "field": "Price / extras",
          "value": "Catalogue price + extra indoor unit ×2, agreed"
        },
        {
          "field": "Special instructions",
          "value": "Park on the inner lane; call 10 minutes before"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "Customer",
        "Service",
        "Address / area",
        "When",
        "Price / extras",
        "Special instructions"
      ]
    },
    {
      "id": "h17",
      "from": "lms",
      "to": "mkt",
      "sop": "LMS-SOP-08",
      "object": "What happened to the ad lead",
      "plain": "Booked, lost, or invalid — and the reason. Without this, Marketing’s “cost per booking” is a guess.",
      "trigger": "When a marketing-sourced lead is qualified, booked, lost, or marked invalid",
      "sendBack": "“Didn’t convert” with no reason.",
      "nextDoes": "Marketing stops or scales that campaign using the real reason.",
      "packet": [
        {
          "field": "Lead",
          "value": "Farooq’s neighbour, 17 Sep"
        },
        {
          "field": "Source / campaign",
          "value": "Paid social / AC-Sept-Cool"
        },
        {
          "field": "Outcome",
          "value": "Lost"
        },
        {
          "field": "Reason",
          "value": "Out of area — Pulwama (we do not serve it)"
        },
        {
          "field": "What Marketing should do",
          "value": "Stop Pulwama creative. Do not keep spending there."
        }
      ],
      "mustInclude": [
        "Lead",
        "Source / campaign",
        "Outcome",
        "Reason",
        "What Marketing should do"
      ]
    },
    {
      "id": "h18",
      "from": "lms",
      "to": "pc",
      "sop": "LMS-SOP-08",
      "object": "What happened to the partner's lead",
      "plain": "Did that partner’s enquiry become a booking, or was it junk? Partnerships cannot manage a hotel or shop without this.",
      "trigger": "When a partner-sourced lead is closed",
      "sendBack": "“They sent 6 this week” with no lead IDs and no outcomes.",
      "nextDoes": "Partnerships keeps, coaches, or cuts the partner.",
      "packet": [
        {
          "field": "Partner",
          "value": "Hotel Darbar (HT-012)"
        },
        {
          "field": "Week",
          "value": "15–21 Sep"
        },
        {
          "field": "Leads",
          "value": "6"
        },
        {
          "field": "Booked",
          "value": "2"
        },
        {
          "field": "Invalid",
          "value": "3 — no address"
        },
        {
          "field": "Lost",
          "value": "1 — price"
        }
      ],
      "mustInclude": [
        "Partner",
        "Week",
        "Leads",
        "Booked",
        "Invalid",
        "Lost"
      ]
    },
    {
      "id": "h19",
      "from": "co",
      "to": "po",
      "sop": "CO-SOP-02",
      "object": "Need a provider for this booking",
      "plain": "The job is verified. Customer Operations asks for a suitable free technician — not “send Altaf”. Provider Operations chooses who.",
      "trigger": "When a verified booking needs a provider assigned",
      "sendBack": "A favourite name with no service, area, or time window.",
      "nextDoes": "Provider Operations returns an accepted person with an arrival time — or says they cannot fill, quickly.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4418"
        },
        {
          "field": "Service",
          "value": "AC servicing"
        },
        {
          "field": "Area",
          "value": "Rajbagh"
        },
        {
          "field": "Time window",
          "value": "19 Sep, 11:00–13:00"
        },
        {
          "field": "Skill required",
          "value": "AC servicing / gas-handling certified"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "Service",
        "Area",
        "Time window",
        "Skill required"
      ]
    },
    {
      "id": "h20",
      "from": "po",
      "to": "co",
      "sop": "PO-SOP-05",
      "object": "This provider accepted the job",
      "plain": "A named person said yes, with an arrival time. Until this exists, nobody may tell the customer “someone is coming”.",
      "trigger": "When the provider has accepted the job",
      "sendBack": "A maybe, or a name with no accept time.",
      "nextDoes": "Customer Operations locks the assignment and asks Customer Experience to message the customer.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4418"
        },
        {
          "field": "Provider",
          "value": "Altaf (PR-208), AC certified"
        },
        {
          "field": "Accepted at",
          "value": "19 Sep, 09:14"
        },
        {
          "field": "Will arrive",
          "value": "11:20"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "Provider",
        "Accepted at",
        "Will arrive"
      ]
    },
    {
      "id": "h21",
      "from": "co",
      "to": "cx",
      "sop": "CO-SOP-03",
      "object": "Tell the customer this update",
      "plain": "Customer Experience is the voice. They may only send facts Operations has written: confirmed, assigned, delayed, rescheduled, or done.",
      "trigger": "When the booking is confirmed, assigned, delayed, rescheduled, or completed",
      "sendBack": "“Tell them something” with no facts.",
      "nextDoes": "Customer Experience sends the approved message and logs it.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4418"
        },
        {
          "field": "Message type",
          "value": "Assigned"
        },
        {
          "field": "Facts to send",
          "value": "Altaf is assigned. Expected at 11:20. He will call 10 minutes before."
        },
        {
          "field": "Do not say",
          "value": "Anything about price or extras Operations has not written"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "Message type",
        "Facts to send",
        "Do not say"
      ]
    },
    {
      "id": "h22",
      "from": "cx",
      "to": "co",
      "sop": "CX-SOP-01",
      "object": "Customer wants the job changed",
      "plain": "Reschedule, access problem, or delay complaint. Customer Experience does not move the board. Operations must.",
      "trigger": "When the customer asks to reschedule, change the booking, or reports a delay that affects the visit",
      "sendBack": "A chat paste with no request type.",
      "nextDoes": "Customer Operations changes the board, then sends a new “tell the customer” packet.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4418"
        },
        {
          "field": "Request type",
          "value": "Reschedule"
        },
        {
          "field": "Customer constraint",
          "value": "Blocked until 16:00 today"
        },
        {
          "field": "Preference",
          "value": "Keep the same provider if he can"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "Request type",
        "Customer constraint",
        "Preference"
      ]
    },
    {
      "id": "h23",
      "from": "co",
      "to": "rsm",
      "sop": "CO-SOP-07",
      "object": "Job done — ready to bill",
      "plain": "The visit happened. Money may start. Revenue does not invoice because Sales was optimistic or someone said “done” on WhatsApp.",
      "trigger": "When service completion is recorded on the job card",
      "sendBack": "No completion time, or the service on the card is not what was booked.",
      "nextDoes": "Revenue invoices the customer, collects, and starts provider payout.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4418"
        },
        {
          "field": "Service delivered",
          "value": "AC servicing + extra indoor unit ×2"
        },
        {
          "field": "Completed at",
          "value": "19 Sep, 12:08"
        },
        {
          "field": "Proof",
          "value": "Before/after photos on the job card"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "Service delivered",
        "Completed at",
        "Proof"
      ]
    },
    {
      "id": "h24",
      "from": "co",
      "to": "qm",
      "sop": "CO-SOP-08",
      "object": "This job went off the path",
      "plain": "A no-show, a late reassignment, or a scheduled quality sample. Quality must see broken jobs, not only angry customers.",
      "trigger": "When an exception is raised, or a job is pulled for a scheduled check",
      "sendBack": "A rant with no booking ID.",
      "nextDoes": "Quality logs it, samples it, or audits the process.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4401"
        },
        {
          "field": "What went wrong",
          "value": "Provider no-show"
        },
        {
          "field": "What we did",
          "value": "Reassigned after 70 minutes"
        },
        {
          "field": "Why Quality should look",
          "value": "Assignment time broke the 2-hour rule — sample for audit"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "What went wrong",
        "What we did",
        "Why Quality should look"
      ]
    },
    {
      "id": "h25",
      "from": "cx",
      "to": "qm",
      "sop": "CX-SOP-02",
      "object": "Quality complaint",
      "plain": "The work or the behaviour failed the standard. An apology is not a pass. This is not a billing ticket.",
      "trigger": "When a complaint is categorised as service quality or provider behaviour",
      "sendBack": "“Customer unhappy” with no booking, no severity, no description.",
      "nextDoes": "Quality investigates against the written standard.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4390"
        },
        {
          "field": "Severity",
          "value": "High"
        },
        {
          "field": "What the customer says",
          "value": "AC still warm after a ‘wet service’"
        },
        {
          "field": "Proof",
          "value": "Customer photos of indoor unit, 20 Sep 18:40"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "Severity",
        "What the customer says",
        "Proof"
      ]
    },
    {
      "id": "h26",
      "from": "cx",
      "to": "rsm",
      "sop": "CX-SOP-01",
      "object": "Customer money question",
      "plain": "Invoice, payment, refund, or dispute. Customer Experience must not invent a discount. Revenue owns booking money.",
      "trigger": "When the customer request is financial",
      "sendBack": "“Give them ₹200 off” with no Revenue packet.",
      "nextDoes": "Revenue applies the invoice / payment / refund rule and sends back the status that may be told to the customer.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4418"
        },
        {
          "field": "Request type",
          "value": "Dispute — extra indoor unit charge"
        },
        {
          "field": "Customer says",
          "value": "Only one extra unit was done, billed for two"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "Request type",
        "Customer says"
      ]
    },
    {
      "id": "h27",
      "from": "cx",
      "to": "po",
      "sop": "CX-SOP-02",
      "object": "Complaint about the provider's behaviour",
      "plain": "What the person did on the job, with facts. Provider Operations owns the technician. Customer Experience does not deactivate people.",
      "trigger": "When the complaint is about how the provider behaved",
      "sendBack": "A feeling with no booking and no facts.",
      "nextDoes": "Provider Operations records the event and may restrict the person while Quality looks.",
      "packet": [
        {
          "field": "Provider",
          "value": "Altaf (PR-208)"
        },
        {
          "field": "Booking ID",
          "value": "BK-4388"
        },
        {
          "field": "Facts",
          "value": "Arrived 55 minutes late, no call"
        },
        {
          "field": "Proof",
          "value": "Customer statement + CX log 18 Sep"
        }
      ],
      "mustInclude": [
        "Provider",
        "Booking ID",
        "Facts",
        "Proof"
      ]
    },
    {
      "id": "h28",
      "from": "po",
      "to": "qm",
      "sop": "PO-SOP-07",
      "object": "How this provider is performing",
      "plain": "Rejections, lates, ratings, status changes — as dated events, not a ranking screenshot in a group chat.",
      "trigger": "When rejections, issues, ratings, or status changes are recorded for the period",
      "sendBack": "A ranking image with no events.",
      "nextDoes": "Quality uses the events in audits and repeat-failure reviews.",
      "packet": [
        {
          "field": "Provider",
          "value": "PR-155"
        },
        {
          "field": "Period",
          "value": "September"
        },
        {
          "field": "Events",
          "value": "4 job rejections in Bemina; 2 late arrivals"
        },
        {
          "field": "Rating",
          "value": "3.6"
        }
      ],
      "mustInclude": [
        "Provider",
        "Period",
        "Events",
        "Rating"
      ]
    },
    {
      "id": "h29",
      "from": "qm",
      "to": "po",
      "sop": "QM-SOP-06",
      "object": "What must happen to this provider",
      "plain": "After repeat failure or an audit: coach, restrict, or deactivate — with a due date. Quality does not silently delete people.",
      "trigger": "When a repeat failure or audit finding is closed on a named provider",
      "sendBack": "“This guy is bad” with no finding and no required action.",
      "nextDoes": "Provider Operations does the action and records it.",
      "packet": [
        {
          "field": "Provider",
          "value": "PR-155"
        },
        {
          "field": "Finding",
          "value": "Late arrival 3 times in 14 days"
        },
        {
          "field": "Required action",
          "value": "14-day restriction + coaching"
        },
        {
          "field": "Due",
          "value": "25 Sep"
        }
      ],
      "mustInclude": [
        "Provider",
        "Finding",
        "Required action",
        "Due"
      ]
    },
    {
      "id": "h30",
      "from": "qm",
      "to": "co",
      "sop": "QM-SOP-05",
      "object": "Fix this process by this date",
      "plain": "The path broke (not just one person). Customer Operations must change how the desk works, by a date. An apology is not the fix.",
      "trigger": "When an SOP miss or fulfilment failure has a root cause in the process",
      "sendBack": "A lecture with no due date.",
      "nextDoes": "Customer Operations changes the practice and reports back.",
      "packet": [
        {
          "field": "Process",
          "value": "Provider assignment"
        },
        {
          "field": "What failed",
          "value": "Jobs sat unassigned more than 45 minutes with no alert"
        },
        {
          "field": "Cause",
          "value": "No escalation on the board"
        },
        {
          "field": "Fix",
          "value": "Board alert + named backup desk"
        },
        {
          "field": "Due",
          "value": "22 Sep"
        }
      ],
      "mustInclude": [
        "Process",
        "What failed",
        "Cause",
        "Fix",
        "Due"
      ]
    },
    {
      "id": "h31",
      "from": "qm",
      "to": "sd",
      "sop": "QM-SOP-08",
      "object": "This service keeps failing the same way",
      "plain": "Many jobs, same failure — likely the offer or the method, not one technician. Service Development must change the SKU or the steps.",
      "trigger": "When the monthly quality review finds a systemic service problem",
      "sendBack": "One bad job treated as a pattern.",
      "nextDoes": "Service Development opens an improve-or-retire review of that service.",
      "packet": [
        {
          "field": "Service",
          "value": "AC servicing"
        },
        {
          "field": "Pattern",
          "value": "‘Still warm’ after wet-service jobs"
        },
        {
          "field": "Count / where / when",
          "value": "11 cases, Srinagar, Aug–Sep"
        },
        {
          "field": "Ask of Service Development",
          "value": "Review method and what the offer includes"
        }
      ],
      "mustInclude": [
        "Service",
        "Pattern",
        "Count / where / when",
        "Ask of Service Development"
      ]
    },
    {
      "id": "h32",
      "from": "qm",
      "to": "mi",
      "sop": "QM-SOP-08",
      "object": "Quality problem that looks like a market problem",
      "plain": "The same complaint keeps coming. That is evidence for Research (stop, improve, or new offer) — not one ticket to re-investigate.",
      "trigger": "When a failure or complaint trend repeats — not a single job",
      "sendBack": "One complaint with no pattern.",
      "nextDoes": "Market Intelligence logs a trend and may open an opportunity. They do not run the audit.",
      "packet": [
        {
          "field": "Trend",
          "value": "AC not cooling after service"
        },
        {
          "field": "Service / area",
          "value": "AC servicing, Srinagar City"
        },
        {
          "field": "Period",
          "value": "3 consecutive weeks in September"
        },
        {
          "field": "Quality pointer",
          "value": "Monthly quality review, wet-service jobs"
        }
      ],
      "mustInclude": [
        "Trend",
        "Service / area",
        "Period",
        "Quality pointer"
      ]
    },
    {
      "id": "h33",
      "from": "cx",
      "to": "mi",
      "sop": "CX-SOP-07",
      "object": "What customers keep saying this month",
      "plain": "Ratings and repeated phrases after jobs (“came late”, “wanted evening”). That is research input, not a Sales conversion issue.",
      "trigger": "When the monthly Customer Experience report is issued with themed issues",
      "sendBack": "A folder of raw tickets with no theme.",
      "nextDoes": "Market Intelligence updates the trend list and may open an opportunity (hours, service, area).",
      "packet": [
        {
          "field": "Month",
          "value": "August"
        },
        {
          "field": "Rating summary",
          "value": "4.1 average; late arrival mentioned 22 times"
        },
        {
          "field": "Repeat theme",
          "value": "Wanted evening plumbing slot"
        },
        {
          "field": "Where",
          "value": "Srinagar City plumbing jobs"
        },
        {
          "field": "This is not",
          "value": "A Sales conversion problem"
        }
      ],
      "mustInclude": [
        "Month",
        "Rating summary",
        "Repeat theme",
        "Where",
        "This is not"
      ]
    },
    {
      "id": "h34",
      "from": "rsm",
      "to": "fpc",
      "sop": "RS-SOP-05",
      "object": "This week's job money",
      "plain": "What was billed, collected, paid to providers, and which jobs are stuck. The company plan uses this. Finance does not invoice customers.",
      "trigger": "When daily recon and the weekly revenue report are ready",
      "sendBack": "A cash-in-hand number with no exception list.",
      "nextDoes": "Finance updates cash, budget variance, and management accounts.",
      "packet": [
        {
          "field": "Week",
          "value": "15–21 Sep"
        },
        {
          "field": "Billed",
          "value": "₹4.2 lakh"
        },
        {
          "field": "Collected",
          "value": "₹3.7 lakh"
        },
        {
          "field": "Paid to providers",
          "value": "₹2.1 lakh"
        },
        {
          "field": "Stuck",
          "value": "6 completed jobs not billed; 2 disputes open"
        }
      ],
      "mustInclude": [
        "Week",
        "Billed",
        "Collected",
        "Paid to providers",
        "Stuck"
      ]
    },
    {
      "id": "h35",
      "from": "rsm",
      "to": "cx",
      "sop": "RS-SOP-02",
      "object": "Money status you may tell the customer",
      "plain": "Invoice, paid, refund, or dispute result — written by Revenue. Customer Experience must not guess “I think they paid”.",
      "trigger": "When invoice, payment, refund, or dispute status changes",
      "sendBack": "A verbal guess with no booking status.",
      "nextDoes": "Customer Experience messages only this status.",
      "packet": [
        {
          "field": "Booking ID",
          "value": "BK-4418"
        },
        {
          "field": "Status",
          "value": "Payable — no refund"
        },
        {
          "field": "Why",
          "value": "Job card shows 3 indoor units; extra-unit dispute rejected"
        },
        {
          "field": "What to tell the customer",
          "value": "The extra unit was recorded as done; the charge stands"
        }
      ],
      "mustInclude": [
        "Booking ID",
        "Status",
        "Why",
        "What to tell the customer"
      ]
    },
    {
      "id": "h36",
      "from": "hr",
      "to": "fpc",
      "sop": "HR-SOP-01",
      "object": "Staff join / leave / vacancy cost",
      "plain": "Employee headcount and cost — not provider payouts. Finance plans salary. HR owns the people facts.",
      "trigger": "When someone joins, leaves, attendance posts, or a vacancy is approved",
      "sendBack": "A rumour that someone resigned.",
      "nextDoes": "Finance updates people cost against the plan.",
      "packet": [
        {
          "field": "What changed",
          "value": "Customer Operations executive joined 1 Sep"
        },
        {
          "field": "Still open",
          "value": "Sales closer — 22 days vacant"
        },
        {
          "field": "Cost impact",
          "value": "New CO seat on plan from September"
        }
      ],
      "mustInclude": [
        "What changed",
        "Still open",
        "Cost impact"
      ]
    },
    {
      "id": "h37",
      "from": "hr",
      "to": "td",
      "sop": "HR-SOP-03",
      "object": "Give or remove this person's access",
      "plain": "Who needs which screens, from which date. Technology does not decide who works here.",
      "trigger": "When onboarding starts or an exit is approved",
      "sendBack": "“Give him WhatsApp admin” with no role and no date.",
      "nextDoes": "Technology grants or removes access and sends confirmation back.",
      "packet": [
        {
          "field": "Person",
          "value": "Ayaan, Customer Operations executive"
        },
        {
          "field": "From date",
          "value": "1 Sep"
        },
        {
          "field": "Give access to",
          "value": "Booking board, CX inbox"
        },
        {
          "field": "Do not give",
          "value": "Payout / settlement module"
        }
      ],
      "mustInclude": [
        "Person",
        "From date",
        "Give access to",
        "Do not give"
      ]
    },
    {
      "id": "h38",
      "from": "td",
      "to": "hr",
      "sop": "TD-SOP-01",
      "object": "Access was given or removed",
      "plain": "Proof, with a time. HR cannot close joining or exit without this.",
      "trigger": "When access has been granted or removed",
      "sendBack": "“Should be done” with no timestamp.",
      "nextDoes": "HR files it on the employee record.",
      "packet": [
        {
          "field": "Person",
          "value": "Ayaan"
        },
        {
          "field": "Done",
          "value": "Booking board + CX inbox granted"
        },
        {
          "field": "When",
          "value": "1 Sep, 09:40"
        },
        {
          "field": "Not assigned",
          "value": "Payout module"
        }
      ],
      "mustInclude": [
        "Person",
        "Done",
        "When",
        "Not assigned"
      ]
    },
    {
      "id": "h39",
      "from": "fpc",
      "to": "all",
      "sop": "FPC-SOP-02",
      "object": "What each desk may spend this month",
      "plain": "The approved limits. Nobody invents budget in a chat.",
      "trigger": "When the monthly budget is published",
      "sendBack": "“Keep it reasonable” with no numbers.",
      "nextDoes": "Every function plans inside the limit and sends expense packets for real spend.",
      "packet": [
        {
          "field": "Month",
          "value": "September"
        },
        {
          "field": "Marketing",
          "value": "₹2.4 lakh"
        },
        {
          "field": "Expansion",
          "value": "₹0.8 lakh"
        },
        {
          "field": "Not allowed",
          "value": "Unapproved tools spend"
        }
      ],
      "mustInclude": [
        "Month",
        "Marketing",
        "Expansion",
        "Not allowed"
      ]
    },
    {
      "id": "h40",
      "from": "all",
      "to": "fpc",
      "sop": "FPC-SOP-03",
      "object": "Spend this — or we already did",
      "plain": "A named amount on a named budget line, with who authorised it and a document. A UPI screenshot is not enough.",
      "trigger": "When spend is required or has been incurred",
      "sendBack": "A UPI screenshot with no budget line.",
      "nextDoes": "Finance approves or books it, or sends it back.",
      "packet": [
        {
          "field": "Amount",
          "value": "₹8,000"
        },
        {
          "field": "What for",
          "value": "Meta boost — AC-Sept-Cool"
        },
        {
          "field": "Budget line",
          "value": "Paid social, September"
        },
        {
          "field": "Authorised by",
          "value": "Growth Function"
        },
        {
          "field": "Attached",
          "value": "Invoice"
        }
      ],
      "mustInclude": [
        "Amount",
        "What for",
        "Budget line",
        "Authorised by",
        "Attached"
      ]
    },
    {
      "id": "h41",
      "from": "all",
      "to": "td",
      "sop": "TD-SOP-03",
      "object": "System / access / report request",
      "plain": "Who is asking, which system, how urgent. Technology cannot treat every WhatsApp as a ticket.",
      "trigger": "When a desk needs a system fix, access, a change, or a report",
      "sendBack": "“The app is broken” with no system and no who-is-stuck.",
      "nextDoes": "Technology triages, restores or changes, then sends back “you can use it”.",
      "packet": [
        {
          "field": "Who is asking",
          "value": "Customer Operations desk"
        },
        {
          "field": "System",
          "value": "Booking board"
        },
        {
          "field": "What is wrong",
          "value": "Bemina jobs not showing"
        },
        {
          "field": "Priority",
          "value": "High — today’s board is unusable for that area"
        }
      ],
      "mustInclude": [
        "Who is asking",
        "System",
        "What is wrong",
        "Priority"
      ]
    },
    {
      "id": "h42",
      "from": "td",
      "to": "all",
      "sop": "TD-SOP-09",
      "object": "You can use this again",
      "plain": "What changed, who can use it, from when. “Fixed” with no note is not a handoff.",
      "trigger": "When an incident is restored or a report is issued",
      "sendBack": "“Fixed” with no time and no who-can-use-it.",
      "nextDoes": "The owning desk continues work on the restored tool.",
      "packet": [
        {
          "field": "What changed",
          "value": "Bemina filter on the booking board restored"
        },
        {
          "field": "When",
          "value": "18 Sep, 14:10"
        },
        {
          "field": "Who can use it",
          "value": "Customer Operations and Customer Experience"
        }
      ],
      "mustInclude": [
        "What changed",
        "When",
        "Who can use it"
      ]
    },
    {
      "id": "h43",
      "from": "hr",
      "to": "all",
      "sop": "HR-SOP-08",
      "object": "This policy or training applies to these people",
      "plain": "Who must follow it, by when. A PDF in a drive with no audience is not a handoff.",
      "trigger": "When a policy is published or a review / training is scheduled",
      "sendBack": "A file with no names or roles.",
      "nextDoes": "Function leads apply it to their seats.",
      "packet": [
        {
          "field": "What",
          "value": "Assignment-time coaching"
        },
        {
          "field": "Who",
          "value": "All Customer Operations executives"
        },
        {
          "field": "Due",
          "value": "25 Sep"
        },
        {
          "field": "Attendance",
          "value": "Logged by People & HR"
        }
      ],
      "mustInclude": [
        "What",
        "Who",
        "Due",
        "Attendance"
      ]
    },
    {
      "id": "h44",
      "from": "fpc",
      "to": "hr",
      "sop": "FPC-SOP-02",
      "object": "You may / may not hire this role",
      "plain": "Money yes or no. HR must not recruit against a hope.",
      "trigger": "When a manpower request is decided",
      "sendBack": "“We probably need someone.”",
      "nextDoes": "HR opens the vacancy, or holds it.",
      "packet": [
        {
          "field": "Role",
          "value": "Sales closer"
        },
        {
          "field": "Decision",
          "value": "Approved — 1 person from October"
        },
        {
          "field": "Not approved",
          "value": "Second Customer Operations desk this quarter"
        }
      ],
      "mustInclude": [
        "Role",
        "Decision",
        "Not approved"
      ]
    }
  ],
  "modules": {
    "mi": {
      "processes": [
        {
          "name": "Collect",
          "sop": "MI-SOP-01",
          "outcome": "Incoming facts indexed in the Market Intelligence Database",
          "items": [
            "Accept only approved sources",
            "File source, date, owner",
            "Verify before any tracker is updated"
          ]
        },
        {
          "name": "Demand",
          "sop": "MI-SOP-02",
          "outcome": "Closed weekly Customer, Service and Area demand trackers",
          "items": [
            "Catalogue demand by service",
            "Demand by area and coverage",
            "Unmet requests in customer words"
          ]
        },
        {
          "name": "Watch",
          "sop": "MI-SOP-03",
          "outcome": "Competitor master + change log (SOP-03), external trends in SOP-04",
          "items": [
            "One row per named competitor",
            "One dated row per change",
            "Trends live in the Trend Tracker, not here"
          ]
        },
        {
          "name": "Opportunities",
          "sop": "MI-SOP-05",
          "outcome": "Named hypotheses, then Validated / Not validated / Further research",
          "items": [
            "Identify without duplicating",
            "Validate with evidence",
            "Hand the complete object to the owning module"
          ]
        },
        {
          "name": "Report",
          "sop": "MI-SOP-08",
          "outcome": "Weekly and monthly reports compiled from registers only",
          "items": [
            "No new fieldwork inside reporting",
            "Every recommendation cites a register ID"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Daily source gate",
          "when": "Before a fact is used",
          "purpose": "Nothing unofficial enters a tracker.",
          "items": [
            "Source is on the approved list",
            "MI Database row exists (MID-ID)",
            "Date of data, date collected, and owner are filled",
            "Verified only if the source can be re-opened"
          ]
        },
        {
          "name": "Weekly close",
          "when": "Last working day of the ISO week",
          "purpose": "The Weekly Report can be compiled without WhatsApp.",
          "items": [
            "Service Demand Tracker has every catalogue service (or explicit NA)",
            "Area Demand Tracker has every live area",
            "Customer Demand Tracker has unmet themes, not a copy of the service tracker",
            "Watch-list walked: change logged or dated ‘no change’",
            "Opportunity Register has no informal ideas sitting in chat",
            "Weekly report submitted with register IDs"
          ]
        },
        {
          "name": "Validation package",
          "when": "Before status = Validated",
          "purpose": "The receiving module can act without calling MI for missing facts.",
          "items": [
            "OPP-ID and one-sentence hypothesis",
            "Demand evidence IDs (not adjectives)",
            "Competitor note or explicit none found",
            "Feasibility Yes/No/Unknown with who was asked",
            "Decision + receiving module",
            "Opportunity Validation Report attached"
          ]
        },
        {
          "name": "Monthly close",
          "when": "Within 3 working days of month-end",
          "purpose": "Grow / stop / research is a decision, not a recap.",
          "items": [
            "Every Active competitor has Last full review this month",
            "Opportunity funnel counted (New / Validate / Validated / Rejected / Handed)",
            "High opportunities in Validate are inside 10 working days or dated Further research",
            "Monthly report submitted",
            "Next month’s collection list written"
          ]
        }
      ],
      "forms": [
        "Market Intelligence Database",
        "Customer Demand Tracker",
        "Service Demand Tracker",
        "Area Demand Tracker",
        "Competitor Database",
        "Competitor Monitoring Sheet",
        "Market Trend Tracker",
        "Opportunity Register"
      ],
      "reports": [
        "Opportunity Validation Report",
        "Weekly Market Intelligence Report",
        "Monthly Market Intelligence Report"
      ],
      "escalation": [
        {
          "trigger": "Critical market or competitor change",
          "route": "Growth Function immediately",
          "sla": "Within 1 working day",
          "record": "Weekly Market Intelligence Report / exception note"
        },
        {
          "trigger": "Validated opportunity blocked",
          "route": "Owning module (OSD, Expansion, Partnerships)",
          "sla": "Same weekly cycle",
          "record": "Opportunity Register"
        }
      ],
      "improvement": {
        "cadence": "Monthly market summary",
        "sources": [
          "Demand trends",
          "Lost/unfulfilled signals from Sales",
          "Quality and CX complaint patterns"
        ],
        "loopsTo": [
          "sd",
          "me",
          "pc",
          "mkt"
        ],
        "question": "What should we grow, stop, or research next?"
      }
    },
    "mkt": {
      "processes": [
        {
          "name": "Planning",
          "sop": "MKT-SOP-01",
          "outcome": "Approved monthly marketing plan and calendar",
          "items": [
            "Select services, audiences, areas, channels",
            "Set budget and KPIs",
            "Submit plan for approval"
          ]
        },
        {
          "name": "Content",
          "sop": "MKT-SOP-02",
          "outcome": "Approved creative ready to publish",
          "items": [
            "Brief",
            "Produce",
            "Check brand and facts",
            "Store in creative library"
          ]
        },
        {
          "name": "Campaigns",
          "sop": "MKT-SOP-03",
          "outcome": "Live, tracked campaigns",
          "items": [
            "Configure",
            "Launch",
            "Monitor",
            "Optimize"
          ]
        },
        {
          "name": "Lead Generation",
          "sop": "MKT-SOP-06",
          "outcome": "Source-tagged customer enquiry",
          "items": [
            "Generate enquiry",
            "Assign source",
            "Hand to Lead Management & Sales"
          ]
        },
        {
          "name": "Marketing Reporting",
          "sop": "MKT-SOP-08",
          "outcome": "Channel, cost and booking performance",
          "items": [
            "Calculate marketing KPIs",
            "Compare channels",
            "Submit weekly and monthly reports"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Campaign control",
          "when": "Daily",
          "items": [
            "Active campaigns checked",
            "Spend vs plan",
            "Incoming leads source-tagged",
            "Abnormal performance recorded"
          ]
        },
        {
          "name": "Weekly marketing close",
          "when": "Weekly",
          "items": [
            "Enquiries, qualified leads, bookings by channel",
            "Budget variance",
            "Underperforming campaigns actioned",
            "Weekly report submitted"
          ]
        }
      ],
      "forms": [
        "Annual Marketing Plan",
        "Monthly Marketing Plan",
        "Marketing Calendar",
        "Content Calendar",
        "Creative Library",
        "Campaign Register",
        "Campaign Performance Tracker",
        "Marketing Budget Tracker",
        "Lead Source Tracker",
        "Channel Performance Tracker"
      ],
      "reports": [
        "Weekly Marketing Report",
        "Monthly Marketing Report"
      ],
      "escalation": [
        {
          "trigger": "Campaign overspend risk",
          "route": "Financial Planning & Control",
          "sla": "Same day pause if unauthorized",
          "record": "Marketing Budget Tracker"
        },
        {
          "trigger": "Lead tracking broken",
          "route": "Technology & Data + Lead Management & Sales",
          "sla": "Same day",
          "record": "Campaign Register"
        }
      ],
      "improvement": {
        "cadence": "Monthly marketing report",
        "sources": [
          "Channel ROI",
          "Creative performance",
          "Lead quality from Sales"
        ],
        "loopsTo": [
          "mi",
          "sd",
          "lms"
        ],
        "question": "Which messages, offers and channels should be scaled or stopped?"
      }
    },
    "sd": {
      "processes": [
        {
          "name": "Service Portfolio",
          "sop": "SD-SOP-03",
          "outcome": "Accurate catalogue and service definitions",
          "items": [
            "Maintain catalogue",
            "Define scope, inclusions, exclusions",
            "Identify outdated services"
          ]
        },
        {
          "name": "New Services",
          "sop": "SD-SOP-01",
          "outcome": "Service opportunity ready for validation",
          "items": [
            "Convert unmet demand into a service hypothesis",
            "Record in Service Opportunity Register"
          ]
        },
        {
          "name": "Service Improvement",
          "sop": "SD-SOP-06",
          "outcome": "Documented process or definition change",
          "items": [
            "Review complaints, failures and feedback",
            "Improve definition or delivery steps"
          ]
        },
        {
          "name": "Validation",
          "sop": "SD-SOP-02",
          "outcome": "Approved for development, further research, or rejected",
          "items": [
            "Demand",
            "Competition",
            "Provider capability",
            "Economics",
            "Risk"
          ]
        },
        {
          "name": "Launch",
          "sop": "SD-SOP-05",
          "outcome": "Sellable, deliverable service in the catalogue",
          "items": [
            "Confirm pricing and readiness",
            "Update catalogue",
            "Brief Marketing, Sales, Operations and Providers"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Service gap capture",
          "when": "Daily",
          "items": [
            "Unfulfilled requests recorded as opportunities",
            "Service complaints relevant to definition reviewed"
          ]
        },
        {
          "name": "Launch readiness",
          "when": "Before launch",
          "items": [
            "Definition sheet complete",
            "Pricing approved",
            "Provider capability confirmed",
            "Catalogue updated",
            "Marketing and operations briefed"
          ]
        }
      ],
      "forms": [
        "Service Catalogue",
        "Service Definition Sheet",
        "Service Opportunity Register",
        "Service Development Register",
        "Service Pricing Analysis",
        "Service Launch Checklist",
        "Service Performance Tracker",
        "Service Improvement Register",
        "Service Retirement Register"
      ],
      "reports": [
        "Service Validation Report"
      ],
      "escalation": [
        {
          "trigger": "Service cannot be delivered as defined",
          "route": "Customer Operations + Provider Operations",
          "sla": "Before launch or immediately if live",
          "record": "Service Definition Sheet"
        },
        {
          "trigger": "Repeated service complaints",
          "route": "Quality Management",
          "sla": "Within weekly review",
          "record": "Service Improvement Register"
        }
      ],
      "improvement": {
        "cadence": "Monthly service portfolio review",
        "sources": [
          "Unfulfilled demand",
          "Complaints",
          "Provider capability",
          "Service economics"
        ],
        "loopsTo": [
          "mkt",
          "po",
          "co",
          "qm"
        ],
        "question": "What should we launch, improve, or retire?"
      }
    },
    "me": {
      "processes": [
        {
          "name": "Area Research",
          "sop": "ME-SOP-02",
          "outcome": "Area market research report",
          "items": [
            "Demand",
            "Competitors",
            "Pricing",
            "Customer behaviour"
          ]
        },
        {
          "name": "Feasibility",
          "sop": "ME-SOP-03",
          "outcome": "Go / no-go feasibility report",
          "items": [
            "Demand estimate",
            "Provider density",
            "Economics",
            "Risks"
          ]
        },
        {
          "name": "Expansion Planning",
          "sop": "ME-SOP-04",
          "outcome": "Approved expansion plan and budget",
          "items": [
            "Target area",
            "Launch services",
            "Provider, marketing and operations requirements"
          ]
        },
        {
          "name": "Market Launch",
          "sop": "ME-SOP-05",
          "outcome": "Controlled live market",
          "items": [
            "Confirm provider and operations readiness",
            "Coordinate marketing launch",
            "Monitor initial demand and fulfilment"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Area signal capture",
          "when": "Daily",
          "items": [
            "Demand from uncovered areas recorded",
            "Active expansion projects tracked"
          ]
        },
        {
          "name": "Launch readiness",
          "when": "Before market launch",
          "items": [
            "Feasibility approved",
            "Providers ready",
            "Customer Operations ready",
            "Marketing brief issued",
            "Launch KPIs set"
          ]
        }
      ],
      "forms": [
        "Expansion Opportunity Register",
        "Competitor Coverage Map",
        "Area Demand Tracker",
        "Provider Availability Assessment",
        "Expansion Plan",
        "Expansion Budget",
        "Market Launch Checklist",
        "Expansion Performance Tracker"
      ],
      "reports": [
        "Area Market Research Report",
        "Market Feasibility Report",
        "Market Launch Report",
        "Market Exit/Pause Report"
      ],
      "escalation": [
        {
          "trigger": "Launch without provider or operations readiness",
          "route": "Control Function / hold launch",
          "sla": "Before go-live",
          "record": "Market Launch Checklist"
        },
        {
          "trigger": "New area demand with no coverage",
          "route": "Provider Operations + Marketing hold",
          "sla": "Weekly",
          "record": "Expansion Opportunity Register"
        }
      ],
      "improvement": {
        "cadence": "Monthly expansion review",
        "sources": [
          "New-area demand",
          "Provider density",
          "Launch vs plan"
        ],
        "loopsTo": [
          "po",
          "mkt",
          "lms",
          "co"
        ],
        "question": "Scale, improve, pause, or exit the area?"
      }
    },
    "pc": {
      "processes": [
        {
          "name": "Partner Identification",
          "sop": "PC-SOP-01",
          "outcome": "Partnership opportunity recorded",
          "items": [
            "Identify channel types",
            "Record opportunity"
          ]
        },
        {
          "name": "Qualification",
          "sop": "PC-SOP-02",
          "outcome": "Qualified or rejected partner",
          "items": [
            "Fit",
            "Reputation",
            "Lead quality potential",
            "Commercial sense"
          ]
        },
        {
          "name": "Onboarding",
          "sop": "PC-SOP-04",
          "outcome": "Active partner able to send leads",
          "items": [
            "Agreement",
            "Lead process",
            "Source tagging",
            "Communication rules"
          ]
        },
        {
          "name": "Channel Management",
          "sop": "PC-SOP-07",
          "outcome": "Working partner relationship",
          "items": [
            "Communication",
            "Issue handling",
            "Lead flow monitoring"
          ]
        },
        {
          "name": "Partner Performance",
          "sop": "PC-SOP-06",
          "outcome": "Keep, improve, or close decision",
          "items": [
            "Lead volume",
            "Qualification rate",
            "Bookings",
            "Complaints"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Partner pipeline",
          "when": "Weekly",
          "items": [
            "Opportunities qualified",
            "Onboarding checklists closed",
            "Partner leads source-tagged into Sales"
          ]
        },
        {
          "name": "Partner performance",
          "when": "Monthly",
          "items": [
            "Lead quality reviewed",
            "Poor partners improved or closed"
          ]
        }
      ],
      "forms": [
        "Partnership Opportunity Register",
        "Partner Database",
        "Partner Qualification Form",
        "Partnership Proposal",
        "Partnership Agreement/Terms",
        "Partner Onboarding Checklist",
        "Partner Communication Log",
        "Partner Lead Tracker",
        "Channel Performance Tracker"
      ],
      "reports": [
        "Partner Performance Report",
        "Weekly Partnerships Report",
        "Monthly Partnerships Report"
      ],
      "escalation": [
        {
          "trigger": "Partner sending poor or untagged leads",
          "route": "Lead Management & Sales + partner owner",
          "sla": "Within one week of pattern",
          "record": "Partner Performance Report"
        },
        {
          "trigger": "Partner brand or customer-risk issue",
          "route": "Control Function",
          "sla": "Immediate",
          "record": "Partner Issue in communication log"
        }
      ],
      "improvement": {
        "cadence": "Monthly partnerships review",
        "sources": [
          "Partner lead quality",
          "Bookings",
          "Customer issues"
        ],
        "loopsTo": [
          "lms",
          "mkt"
        ],
        "question": "Which channels to grow, repair, or close?"
      }
    },
    "lms": {
      "processes": [
        {
          "name": "Lead Intake",
          "sop": "LMS-SOP-01",
          "outcome": "Lead in CRM with source and requirement",
          "items": [
            "Capture enquiry",
            "Identify source",
            "Create lead record"
          ]
        },
        {
          "name": "Qualification",
          "sop": "LMS-SOP-03",
          "outcome": "Qualified, invalid, or needs follow-up",
          "items": [
            "Confirm need, location, timing, budget fit",
            "Record qualification"
          ]
        },
        {
          "name": "Follow-up",
          "sop": "LMS-SOP-05",
          "outcome": "Next action completed or lead closed lost",
          "items": [
            "Work follow-up tracker",
            "No unattended qualified lead"
          ]
        },
        {
          "name": "Booking Conversion",
          "sop": "LMS-SOP-04",
          "outcome": "Confirmed booking handed to Customer Operations",
          "items": [
            "Agree service and schedule",
            "Create booking",
            "Hand off"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Lead desk",
          "when": "Daily",
          "items": [
            "New leads created same day",
            "First response within SLA",
            "Follow-ups actioned",
            "No confirmed booking left unhanded"
          ]
        },
        {
          "name": "Conversion close",
          "when": "On booking",
          "items": [
            "Requirement confirmed",
            "Booking record complete",
            "Handoff to Customer Operations recorded"
          ]
        }
      ],
      "forms": [
        "Lead Register / CRM",
        "Lead Source Tracker",
        "Lead Qualification Record",
        "Follow-Up Tracker",
        "Booking Register",
        "Lost Lead Register",
        "Invalid Lead Register",
        "Customer Requirement Record"
      ],
      "reports": [
        "Daily Sales Report",
        "Weekly Sales Report",
        "Monthly Sales Report"
      ],
      "escalation": [
        {
          "trigger": "Confirmed booking not accepted by Customer Operations",
          "route": "Customer Operations supervisor",
          "sla": "Same day",
          "record": "Booking Register"
        },
        {
          "trigger": "Lead SLA breach",
          "route": "Operations Function",
          "sla": "As per first-response SLA",
          "record": "Follow-Up Tracker"
        }
      ],
      "improvement": {
        "cadence": "Monthly sales report",
        "sources": [
          "Source conversion",
          "Lost reasons",
          "Response SLA"
        ],
        "loopsTo": [
          "mkt",
          "pc",
          "sd"
        ],
        "question": "Where are enquiries failing to become bookings?"
      }
    },
    "co": {
      "processes": [
        {
          "name": "Booking Intake",
          "sop": "CO-SOP-01",
          "outcome": "Verified booking ready to assign",
          "items": [
            "Receive confirmed booking",
            "Verify customer, service, location, schedule"
          ]
        },
        {
          "name": "Provider Assignment",
          "sop": "CO-SOP-02",
          "outcome": "Provider requirement sent; accepted provider returned",
          "items": [
            "Send requirement to Provider Operations",
            "Confirm acceptance"
          ]
        },
        {
          "name": "Scheduling",
          "sop": "CO-SOP-03",
          "outcome": "Customer-confirmed scheduled visit",
          "items": [
            "Confirm time with customer",
            "Record schedule"
          ]
        },
        {
          "name": "Service Monitoring",
          "sop": "CO-SOP-04",
          "outcome": "No booking left unattended",
          "items": [
            "Monitor pending, accepted, ongoing",
            "Handle delay, rejection, no-show"
          ]
        },
        {
          "name": "Completion",
          "sop": "CO-SOP-07",
          "outcome": "Operationally closed booking",
          "items": [
            "Confirm completion",
            "Trigger CX follow-up and Revenue billing"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Fulfilment desk",
          "when": "Daily",
          "items": [
            "New bookings verified",
            "Unassigned bookings actioned",
            "Today's visits monitored",
            "Exceptions recorded",
            "Completions closed"
          ]
        },
        {
          "name": "Completion close",
          "when": "On completion",
          "items": [
            "Status = Service Completed",
            "CX follow-up triggered",
            "Revenue billing triggered"
          ]
        }
      ],
      "forms": [
        "Booking Register",
        "Booking Verification Checklist",
        "Provider Assignment Log",
        "Provider Acceptance Record",
        "Customer Communication Log",
        "Booking Status Tracker",
        "Rescheduling Register",
        "Cancellation Register",
        "Operational Exception Register",
        "Service Completion Record"
      ],
      "reports": [
        "Daily Operations Report",
        "Weekly Operations Report",
        "Monthly Customer Operations Report"
      ],
      "escalation": [
        {
          "trigger": "Unassigned or delayed booking",
          "route": "Provider Operations, then Operations Function",
          "sla": "CO-SOP-08 operational escalation",
          "record": "Operational Exception Register"
        },
        {
          "trigger": "Provider rejection / no-show",
          "route": "Provider Operations for replacement",
          "sla": "Immediate",
          "record": "Provider Assignment Log"
        },
        {
          "trigger": "Customer impact",
          "route": "Customer Experience for communication",
          "sla": "Immediate",
          "record": "Customer Communication Log"
        }
      ],
      "improvement": {
        "cadence": "Monthly customer operations report",
        "sources": [
          "Assignment time",
          "Delays",
          "Exceptions",
          "Completion"
        ],
        "loopsTo": [
          "po",
          "cx",
          "qm",
          "sd"
        ],
        "question": "Where does fulfilment break?"
      }
    },
    "po": {
      "processes": [
        {
          "name": "Acquisition",
          "sop": "PO-SOP-01",
          "outcome": "Provider applicant in pipeline",
          "items": [
            "Source providers against required skills and areas"
          ]
        },
        {
          "name": "Registration",
          "sop": "PO-SOP-02",
          "outcome": "Complete application and documents",
          "items": [
            "Collect details",
            "Document checklist"
          ]
        },
        {
          "name": "Verification",
          "sop": "PO-SOP-03",
          "outcome": "Verified or rejected provider",
          "items": [
            "Check identity, skills, documents"
          ]
        },
        {
          "name": "Activation",
          "sop": "PO-SOP-04",
          "outcome": "Active provider in the network",
          "items": [
            "Set status Active",
            "Load skills and service areas"
          ]
        },
        {
          "name": "Availability",
          "sop": "PO-SOP-06",
          "outcome": "Current availability for matching",
          "items": [
            "Maintain availability tracker"
          ]
        },
        {
          "name": "Deployment",
          "sop": "PO-SOP-05",
          "outcome": "Suitable available provider accepted for a booking",
          "items": [
            "Match",
            "Offer",
            "Confirm acceptance back to Customer Operations"
          ]
        },
        {
          "name": "Performance",
          "sop": "PO-SOP-07",
          "outcome": "Keep, train, restrict, or exit decision",
          "items": [
            "Complaints",
            "Rejections",
            "Quality feedback",
            "Status change"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Network desk",
          "when": "Daily",
          "items": [
            "New applicants progressed",
            "Availability current",
            "Assignment requests answered",
            "Rejections/no-shows recorded"
          ]
        },
        {
          "name": "Activation gate",
          "when": "Before Active status",
          "items": [
            "Documents complete",
            "Verification passed",
            "Skills and areas loaded"
          ]
        }
      ],
      "forms": [
        "Provider Acquisition Register",
        "Provider Application",
        "Provider Registration Form",
        "Provider Document Checklist",
        "Provider Verification Record",
        "Provider Database",
        "Provider Skill Matrix",
        "Provider Service Area Matrix",
        "Provider Availability Tracker",
        "Provider Assignment Log",
        "Provider Performance Tracker",
        "Provider Issue Register",
        "Provider Training Register",
        "Provider Status History"
      ],
      "reports": [],
      "escalation": [
        {
          "trigger": "No suitable provider for a live booking",
          "route": "Customer Operations + Quality if repeat area/skill gap",
          "sla": "Immediate",
          "record": "Provider Assignment Log"
        },
        {
          "trigger": "Verification failure or document gap",
          "route": "Do not activate",
          "sla": "Before Active status",
          "record": "Provider Verification Record"
        },
        {
          "trigger": "Repeat provider failure",
          "route": "Quality Management + status change",
          "sla": "After pattern confirmed",
          "record": "Provider Issue Register"
        }
      ],
      "improvement": {
        "cadence": "Monthly provider performance",
        "sources": [
          "Fill rate",
          "Rejections",
          "Quality actions",
          "Area/skill gaps"
        ],
        "loopsTo": [
          "co",
          "qm",
          "me",
          "sd"
        ],
        "question": "Is the network able to deliver what we sell?"
      }
    },
    "rsm": {
      "processes": [
        {
          "name": "Customer Billing & Collections",
          "sop": "RS-SOP-01",
          "outcome": "Invoice raised and payment tracked",
          "items": [
            "Bill completed booking",
            "Collect",
            "Track receivables"
          ]
        },
        {
          "name": "Commission calculation",
          "sop": "RS-SOP-03",
          "outcome": "Correct Panun Kaergar charge and provider amount",
          "items": [
            "Apply commercial rules",
            "Record calculation"
          ]
        },
        {
          "name": "Provider Payouts & Settlements",
          "sop": "RS-SOP-04",
          "outcome": "Provider paid against reconciled jobs",
          "items": [
            "Prepare payout",
            "Reconcile",
            "Close settlement"
          ]
        },
        {
          "name": "Reconciliation & exceptions",
          "sop": "RS-SOP-05",
          "outcome": "Daily recon closed; refunds/disputes owned",
          "items": [
            "Daily reconciliation",
            "Refunds",
            "Disputes",
            "Month-end close"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Daily money desk",
          "when": "Daily",
          "items": [
            "Completed jobs billed",
            "Collections recorded",
            "Payouts due listed",
            "Daily reconciliation closed"
          ]
        },
        {
          "name": "Settlement close",
          "when": "On payout / month-end",
          "items": [
            "Commission calculated",
            "Provider statement issued",
            "Disputes parked with owner",
            "Month closed"
          ]
        }
      ],
      "forms": [
        "Billing Register",
        "Customer Invoice Register",
        "Customer Payment Register",
        "Outstanding Receivables Register",
        "Commission Calculation Register",
        "Provider Payout Register",
        "Refund Register",
        "Adjustment Register",
        "Dispute Transaction Register",
        "Daily Reconciliation"
      ],
      "reports": [
        "Provider Settlement Statement",
        "Weekly Revenue Report",
        "Monthly Revenue & Settlement Report"
      ],
      "escalation": [
        {
          "trigger": "Unbilled completed job",
          "route": "Customer Operations to confirm completion data",
          "sla": "Same day",
          "record": "Billing Register"
        },
        {
          "trigger": "Collection delay / dispute",
          "route": "Customer Experience for customer, Control if material cash risk",
          "sla": "Per collection SLA",
          "record": "Dispute Transaction Register"
        },
        {
          "trigger": "Payout vs commission mismatch",
          "route": "Hold payout until recon",
          "sla": "Before payout",
          "record": "Daily Reconciliation"
        }
      ],
      "improvement": {
        "cadence": "Monthly revenue & settlement report",
        "sources": [
          "Unbilled jobs",
          "Receivables",
          "Payout accuracy",
          "Disputes"
        ],
        "loopsTo": [
          "co",
          "fpc",
          "cx"
        ],
        "question": "Is every completed job billed, collected and settled correctly?"
      }
    },
    "qm": {
      "processes": [
        {
          "name": "Quality Standards",
          "sop": "QM-SOP-01",
          "outcome": "Published quality standard and checklist",
          "items": [
            "Define what good delivery looks like"
          ]
        },
        {
          "name": "Quality Monitoring",
          "sop": "QM-SOP-02",
          "outcome": "Scheduled checks completed",
          "items": [
            "Inspect sample jobs",
            "Record pass/fail"
          ]
        },
        {
          "name": "Audits",
          "sop": "QM-SOP-07",
          "outcome": "SOP compliance finding",
          "items": [
            "Sample records",
            "Compare with SOP",
            "Record deviation"
          ]
        },
        {
          "name": "Root Cause Analysis",
          "sop": "QM-SOP-04",
          "outcome": "Cause of failure identified",
          "items": [
            "Investigate material issues and repeat failures"
          ]
        },
        {
          "name": "Corrective Action",
          "sop": "QM-SOP-05",
          "outcome": "Action assigned, due, and verified",
          "items": [
            "Define action",
            "Track completion",
            "Check effectiveness"
          ]
        },
        {
          "name": "Continuous Improvement",
          "sop": "QM-SOP-08",
          "outcome": "Systemic improvement priority",
          "items": [
            "Monthly quality review",
            "Feed patterns back to Growth and Operations"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Quality desk",
          "when": "Daily",
          "items": [
            "Scheduled checks done",
            "New complaints reviewed for quality",
            "Critical issues escalated"
          ]
        },
        {
          "name": "Corrective action",
          "when": "On finding",
          "items": [
            "Root cause recorded",
            "Action owner and due date set",
            "Effectiveness checked before close"
          ]
        }
      ],
      "forms": [
        "Quality Standards Manual",
        "Quality Checklist",
        "Service Quality Inspection Register",
        "Quality Issue Register",
        "Complaint Quality Review",
        "Root Cause Analysis Record",
        "Corrective Action Register",
        "Provider Quality Review",
        "SOP Compliance Audit",
        "Quality Improvement Register"
      ],
      "reports": [
        "Weekly Quality Report",
        "Monthly Quality Report"
      ],
      "escalation": [
        {
          "trigger": "Critical quality issue / safety / abuse",
          "route": "Operations Function immediately; Provider status review",
          "sla": "Immediate",
          "record": "Quality Issue Register"
        },
        {
          "trigger": "Corrective action overdue",
          "route": "Owning function manager",
          "sla": "On due date",
          "record": "Corrective Action Register"
        },
        {
          "trigger": "Repeat failure after action",
          "route": "Growth (service) or Provider Operations (network)",
          "sla": "Monthly quality review or sooner",
          "record": "Quality Improvement Register"
        }
      ],
      "improvement": {
        "cadence": "Monthly quality review",
        "sources": [
          "Inspections",
          "Complaints",
          "SOP audits",
          "Corrective action effectiveness"
        ],
        "loopsTo": [
          "co",
          "po",
          "sd",
          "mi"
        ],
        "question": "What systemic failure must not repeat?"
      }
    },
    "cx": {
      "processes": [
        {
          "name": "Communication",
          "sop": "CX-SOP-04",
          "outcome": "Customer informed of booking status",
          "items": [
            "Confirmations",
            "Updates",
            "Delays",
            "Rescheduling"
          ]
        },
        {
          "name": "Support",
          "sop": "CX-SOP-01",
          "outcome": "Request answered or routed",
          "items": [
            "Categorize",
            "Give approved information",
            "Route if another function owns it"
          ]
        },
        {
          "name": "Complaints",
          "sop": "CX-SOP-02",
          "outcome": "Complaint recorded, owned, and tracked",
          "items": [
            "Record",
            "Assess severity",
            "Assign",
            "Track to close"
          ]
        },
        {
          "name": "Feedback",
          "sop": "CX-SOP-05",
          "outcome": "Rating and comments captured",
          "items": [
            "Request post-service feedback",
            "Forward negative cases"
          ]
        },
        {
          "name": "Service Recovery",
          "sop": "CX-SOP-06",
          "outcome": "Failed experience recovered or escalated",
          "items": [
            "Coordinate Operations/Quality",
            "Confirm customer outcome"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Customer desk",
          "when": "Daily",
          "items": [
            "New requests categorized",
            "Pending queries answered",
            "Open complaints updated",
            "Delay communications sent"
          ]
        },
        {
          "name": "Post-service",
          "when": "After completion",
          "items": [
            "Feedback requested",
            "Negative feedback routed",
            "Journey closed when no open case remains"
          ]
        }
      ],
      "forms": [
        "Customer Support Register",
        "Customer Communication Log",
        "Customer Complaint Register",
        "Customer Escalation Register",
        "Customer Feedback Register",
        "Customer Rating/Review Register",
        "Service Recovery Register",
        "Open Case Tracker",
        "Post-Service Follow-Up Register"
      ],
      "reports": [
        "Weekly CX Report",
        "Monthly CX Report"
      ],
      "escalation": [
        {
          "trigger": "Complaint severity high or SLA miss",
          "route": "Responsible function, then Operations Function",
          "sla": "CX-SOP-03 escalation SLA",
          "record": "Customer Escalation Register"
        },
        {
          "trigger": "Quality or provider behaviour complaint",
          "route": "Quality Management / Provider Operations",
          "sla": "Same day acknowledgement path",
          "record": "Customer Complaint Register"
        },
        {
          "trigger": "Billing / refund request",
          "route": "Revenue & Settlement",
          "sla": "Same day routing",
          "record": "Customer Support Register"
        }
      ],
      "improvement": {
        "cadence": "Monthly CX report",
        "sources": [
          "Complaints",
          "Ratings",
          "Repeat issues",
          "Recovery outcomes"
        ],
        "loopsTo": [
          "co",
          "qm",
          "po",
          "rsm",
          "mi"
        ],
        "question": "Where does the customer journey fail after booking?"
      }
    },
    "fpc": {
      "processes": [
        {
          "name": "Planning",
          "sop": "FPC-SOP-01",
          "outcome": "Approved annual/monthly financial plan",
          "items": [
            "Forecast revenue, expenses, cash",
            "Include Growth plan"
          ]
        },
        {
          "name": "Budgeting",
          "sop": "FPC-SOP-02",
          "outcome": "Approved limits published to departments",
          "items": [
            "Monthly revenue forecast",
            "Expense budget",
            "Publish controls"
          ]
        },
        {
          "name": "Cash Flow",
          "sop": "FPC-SOP-04",
          "outcome": "Projected closing cash and shortage/surplus",
          "items": [
            "Opening cash",
            "Collections",
            "Payments",
            "Provider settlements"
          ]
        },
        {
          "name": "Expense Control",
          "sop": "FPC-SOP-03",
          "outcome": "Only authorized in-budget spend recorded",
          "items": [
            "Document",
            "Budget check",
            "Authorization",
            "Escalate exceptions"
          ]
        },
        {
          "name": "Financial Reporting",
          "sop": "FPC-SOP-06",
          "outcome": "Management accounts and variance report",
          "items": [
            "Weekly control report",
            "Monthly accounts",
            "Escalate material risk"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Cash and control desk",
          "when": "Daily",
          "items": [
            "Cash position reviewed",
            "Collections vs forecast",
            "Major expenses authorized",
            "Unusual activity checked"
          ]
        },
        {
          "name": "Month-end close",
          "when": "Monthly",
          "items": [
            "Revenue, expenses, cash, liabilities reconciled",
            "Variances explained",
            "Management accounts issued",
            "Month locked"
          ]
        }
      ],
      "forms": [
        "Annual Financial Plan",
        "Monthly Budget",
        "Revenue Forecast",
        "Expense Budget",
        "Expense Register",
        "Cash Flow Forecast",
        "Payment Schedule",
        "Financial Reconciliation",
        "Financial Risk Register"
      ],
      "reports": [
        "Budget vs Actual Report",
        "Weekly Financial Control Report",
        "Monthly Financial Report"
      ],
      "escalation": [
        {
          "trigger": "Unauthorized or excess spend",
          "route": "Control Function; stop payment if required",
          "sla": "Immediate",
          "record": "Expense Register"
        },
        {
          "trigger": "Cash shortage vs obligations",
          "route": "Control Function",
          "sla": "Same day once identified",
          "record": "Cash Flow Forecast / Financial Risk Register"
        },
        {
          "trigger": "Material budget variance",
          "route": "Owning department + management report",
          "sla": "Within monthly close; weekly if large",
          "record": "Budget vs Actual Report"
        }
      ],
      "improvement": {
        "cadence": "Monthly management accounts",
        "sources": [
          "Budget variance",
          "Cash forecast accuracy",
          "Service economics",
          "Marketing spend vs business"
        ],
        "loopsTo": [
          "rsm",
          "mkt",
          "hr"
        ],
        "question": "Where is money leaking or the plan wrong?"
      }
    },
    "hr": {
      "processes": [
        {
          "name": "Workforce Planning",
          "sop": "HR-SOP-01",
          "outcome": "Approved vacancy against budget",
          "items": [
            "Structure",
            "Position",
            "Skills",
            "KPIs",
            "Budget confirmation"
          ]
        },
        {
          "name": "Recruitment",
          "sop": "HR-SOP-02",
          "outcome": "Selected candidate and offer",
          "items": [
            "JD",
            "Source",
            "Screen",
            "Interview",
            "Select"
          ]
        },
        {
          "name": "Onboarding",
          "sop": "HR-SOP-03",
          "outcome": "Active employee with records, access and induction",
          "items": [
            "Documents",
            "Employee record",
            "Policies",
            "Role/KPIs",
            "Access"
          ]
        },
        {
          "name": "Performance",
          "sop": "HR-SOP-05",
          "outcome": "Review recorded against role KPIs",
          "items": [
            "Collect data",
            "Review",
            "Improvement actions"
          ]
        },
        {
          "name": "Training",
          "sop": "HR-SOP-06",
          "outcome": "Required training completed and recorded",
          "items": [
            "Skill gap",
            "Schedule",
            "Evaluate"
          ]
        },
        {
          "name": "Employee Lifecycle",
          "sop": "HR-SOP-09",
          "outcome": "Complete join-to-exit file",
          "items": [
            "Attendance",
            "Issues",
            "Policies",
            "Exit and archive"
          ]
        }
      ],
      "checklists": [
        {
          "name": "People desk",
          "when": "Daily",
          "items": [
            "Attendance recorded",
            "Urgent HR requests processed",
            "Recruitment follow-ups done"
          ]
        },
        {
          "name": "Join / leave gate",
          "when": "On joining or exit",
          "items": [
            "Employee file complete",
            "Access requested or revoked with Technology",
            "Assets recovered on exit"
          ]
        }
      ],
      "forms": [
        "Organization Structure",
        "Position Register",
        "Manpower Plan",
        "Job Descriptions",
        "Candidate Register",
        "Interview Evaluation",
        "Employee Master Database",
        "Employee Personnel File",
        "Attendance Register",
        "Leave Register",
        "Training Register",
        "Performance Review Register",
        "Employee Issue Register",
        "Policy Register",
        "Employee Exit Checklist"
      ],
      "reports": [
        "Monthly HR Report"
      ],
      "escalation": [
        {
          "trigger": "Hiring without budget",
          "route": "Financial Planning & Control — do not open vacancy",
          "sla": "Before vacancy",
          "record": "Manpower Plan"
        },
        {
          "trigger": "Serious policy or conduct issue",
          "route": "Control Function",
          "sla": "Immediate",
          "record": "Employee Issue Register"
        },
        {
          "trigger": "Leaver still has system access",
          "route": "Technology & Data",
          "sla": "Same day as exit",
          "record": "Employee Exit Checklist"
        }
      ],
      "improvement": {
        "cadence": "Monthly HR report",
        "sources": [
          "Headcount vs plan",
          "Vacancies",
          "Turnover",
          "Training and reviews"
        ],
        "loopsTo": [
          "fpc",
          "td"
        ],
        "question": "Is the employee system able to run the operating functions?"
      }
    },
    "td": {
      "processes": [
        {
          "name": "Systems",
          "sop": "TD-SOP-03",
          "outcome": "Critical systems available",
          "items": [
            "Monitor availability",
            "Incidents",
            "Integrations"
          ]
        },
        {
          "name": "Applications",
          "sop": "TD-SOP-07",
          "outcome": "Approved change in production",
          "items": [
            "Customer, provider, CRM, booking, reporting apps"
          ]
        },
        {
          "name": "Data",
          "sop": "TD-SOP-04",
          "outcome": "Complete, accurate, non-duplicate master data",
          "items": [
            "Definitions",
            "Quality checks",
            "Approved corrections"
          ]
        },
        {
          "name": "Security & Access",
          "sop": "TD-SOP-01",
          "outcome": "Minimum required access only",
          "items": [
            "Grant",
            "Review",
            "Remove on status change"
          ]
        },
        {
          "name": "Backup & Recovery",
          "sop": "TD-SOP-05",
          "outcome": "Verified backups and tested recovery",
          "items": [
            "Monitor backup",
            "Integrity check",
            "Periodic recovery test"
          ]
        },
        {
          "name": "Reporting",
          "sop": "TD-SOP-09",
          "outcome": "Validated business report published",
          "items": [
            "Extract",
            "Validate",
            "Publish",
            "Archive"
          ]
        }
      ],
      "checklists": [
        {
          "name": "Reliability desk",
          "when": "Daily",
          "items": [
            "Critical systems up",
            "Errors and integrations checked",
            "Backups completed",
            "Priority incidents owned"
          ]
        },
        {
          "name": "Access gate",
          "when": "On join / leave / role change",
          "items": [
            "Approved request only",
            "Minimum access",
            "Removal confirmed",
            "Register updated"
          ]
        }
      ],
      "forms": [
        "Technology Asset Register",
        "System Register",
        "User Access Register",
        "Access Request Register",
        "Technology Incident Register",
        "System Change Register",
        "Backup Register",
        "Recovery Test Register",
        "Data Quality Register",
        "Software & Subscription Register",
        "Technology Vendor Register",
        "System Documentation",
        "Data Dictionary",
        "Technology Risk Register"
      ],
      "reports": [
        "Monthly Technology & Data Report"
      ],
      "escalation": [
        {
          "trigger": "Critical system down",
          "route": "Incident priority 1; notify affected functions",
          "sla": "Critical incident response SLA",
          "record": "Technology Incident Register"
        },
        {
          "trigger": "Failed backup",
          "route": "Investigate before close of day",
          "sla": "Same day",
          "record": "Backup Register"
        },
        {
          "trigger": "Unauthorized active account",
          "route": "Disable access",
          "sla": "Immediate",
          "record": "User Access Register"
        }
      ],
      "improvement": {
        "cadence": "Monthly technology & data report",
        "sources": [
          "Availability",
          "Incidents",
          "Data quality",
          "Access",
          "Backup tests"
        ],
        "loopsTo": [
          "all"
        ],
        "question": "Are systems and data fit for the operating cycle?"
      }
    }
  }
};

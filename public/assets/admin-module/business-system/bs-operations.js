window.PK_GROWTH = window.PK_GROWTH || {};
(function () {
  "use strict";
  const G = window.PK_GROWTH;
  if (!G.roles || !G.workflows) return;

  function card(kicker, title, lead, body, points, meta) {
    return {
      kicker: kicker,
      title: title,
      lead: lead,
      body: Array.isArray(body) ? body : [body],
      points: points || [],
      meta: meta || []
    };
  }
  function kpi(name, target, why, how) {
    return { name: name, target: target, why: why, how: how };
  }
  function side(item, which) {
    return Object.assign(item, { side: which });
  }

  const cxeKpis = [
    kpi("Leads worked in the written order", "Every lead with your name", "If you work a cold lead while a hot lead is waiting, you missed an urgent job.", "Handle the hot leads that have your name before the warm leads, and the warm leads before the cold leads. Inside the same group, the older lead comes first. It is a miss if you start a cold lead while a hot or warm lead is still waiting for a first contact or a follow-up that is due."),
    kpi("No lead taken from the inbox", "Every lead you handle", "Picking your own lead is how a hot lead gets missed.", "Count this: Leads you handled that already had your name written by the assigner. Compare that count with this: leads you handled. The target written beside this check is the line you must meet."),
    kpi("A provider lead on your name is returned the same day", "Every service provider lead", "A provider left on the customer list is never set up to take jobs.", "Count this: Service provider leads returned to the assigner the same day, with no onboarding started. Compare that count with this: service provider leads that had your name. The target written beside this check is the line you must meet."),
    kpi("Last message on the lead is from Panun Kaergar", "Every lead you handle", "If the customer, the future customer, or the provider spoke last, or a missed call has no message after it, the lead looks abandoned.", "Count this: Leads whose latest message is from Panun Kaergar. Compare that count with this: leads checked. The target written beside this check is the line you must meet."),
    kpi("Call brief sent after every call, including a call they did not answer", "Every call", "A phone call that is not written in a message cannot be seen by the next person.", "Count this: Calls that have a message brief on the lead. Compare that count with this: calls made. The target written beside this check is the line you must meet."),
    kpi("Customer and provider both confirmed before the visit", "Every booking", "A visit with only one side confirmed becomes a no-show.", "Count this: Bookings where both the customer and the provider confirmed they will be at the address, and both were told the same service charge. Compare that count with this: bookings due to start. The target written beside this check is the line you must meet."),
    kpi("Customer payment and provider settlement both written after the job", "Every finished job", "The job is not closed while either side of the money is still open.", "Count this: Jobs where the customer payment and the provider settlement are both written in the admin panel. Compare that count with this: jobs whose service is done. The target written beside this check is the line you must meet."),
    kpi("Bookings marked finished only when the work is actually finished", "Every finished booking", "A booking is finished only when the service meets the written standard, the call after the service is written, the customer has paid, and the provider side is settled. Both are in the admin panel.", "Count this: Finished bookings that meet all of those conditions. Compare that count with this: bookings marked finished. The target written beside this check is the line you must meet."),
    kpi("New leads contacted on the day they arrive", "Every new lead", "A lead that sits all day is a customer left waiting.", "Count this: Leads contacted on the day they arrived. Compare that count with this: leads that needed a contact. The target written beside this check is the line you must meet."),
    kpi("Lead records that match the truth", "At least 99 correct records out of every 100 checked", "A wrong address or a wrong requirement sends the wrong person to the wrong place.", "Count the lead records that match the truth. Compare that count with the lead records you checked. The line is at least 99 correct records out of every 100."),
    kpi("Leads with every required detail filled in", "Every lead", "A blank field means the next person has to guess.", "Count this: Leads with every required detail filled in. Compare that count with this: leads checked. The target written beside this check is the line you must meet."),
    kpi("Follow-ups done on the day and time they were due", "Every follow-up", "A missed follow-up is a customer who was forgotten.", "Count this: Follow-ups done on the due day and time. Compare that count with this: follow-ups that were due. The target written beside this check is the line you must meet."),
    kpi("Follow-ups missed even though they could have been done", "None", "Workload and a shift change are not reasons to skip a follow-up.", "Count this: Follow-ups that were missed and could have been done. Compare that count with this: follow-ups that were due. The target written beside this check is the line you must meet."),
    kpi("Leads left with nobody looking after them", "None", "Every lead assigned to you needs a contact, a status, and a next time.", "Count this: Leads with no contact and no next time. Compare that count with this: leads assigned to you. The target written beside this check is the line you must meet."),
    kpi("Required recordings stored and linked to the lead", "Every required recording", "A recording that is not linked to the lead cannot be found tomorrow.", "Count this: Required recordings stored and linked. Compare that count with this: recordings that were required. The target written beside this check is the line you must meet."),
    kpi("Tickets updated on the day with a status, a comment, and a next action", "At least 98 tickets out of every 100 that needed an update", "A ticket with no next action has no owner.", "Count this: Tickets updated correctly on the day. Compare that count with this: tickets that needed an update. The target written beside this check is the line you must meet."),
    kpi("Booking records that match the truth", "At least 99 correct booking records out of every 100 checked", "A wrong time or a wrong provider creates a failed visit.", "Count the booking records that match the truth. Compare that count with the booking records you checked. The line is at least 99 correct records out of every 100."),
    kpi("Provider names given to the customer only after a real yes", "Every provider you name", "The customer should hear a provider name only after that provider has said yes.", "Count this: Provider names that match a written yes. Compare that count with this: provider names checked. The target written beside this check is the line you must meet."),
    kpi("Booking status that matches what is actually happening", "Every booking", "The screen must show the real situation, including delays and problems.", "Count this: Booking statuses that match the real situation. Compare that count with this: bookings checked. The target written beside this check is the line you must meet."),
    kpi("Calls after the service", "Every finished service that needs this call", "The company learns whether the job and the bill were acceptable only if this call is written down.", "Count this: Calls after the service that were completed. Compare that count with this: services that needed the call. The target written beside this check is the line you must meet."),
    kpi("Payment status that matches the money", "Every payment record", "A booking must stay open while money is still unpaid or still with the provider.", "Count this: Payment statuses that match the money. Compare that count with this: payment records checked. The target written beside this check is the line you must meet."),
    kpi("Complaint follow-ups kept open until they are resolved or handed over", "Every complaint follow-up", "A complaint that is closed in words and still open in fact comes back worse.", "Count this: Complaint follow-ups done. Compare that count with this: complaint follow-ups due. The target written beside this check is the line you must meet."),
    kpi("Future-customer follow-ups done when they are due", "Every future-customer follow-up", "A future customer who is not called is a booking that never gets a chance.", "Count this: Future-customer follow-ups done. Compare that count with this: future-customer follow-ups due. The target written beside this check is the line you must meet."),
    kpi("Eligible future customers who book", "At least 50 out of every 100", "Count only eligible future customers assigned to you in the period. If you had 20, 50 out of 100 means 10 bookings. If you had 40, it means 20 bookings.", "Count this: Eligible future customers who booked. Compare that count with this: eligible future customers you could contact. The target written beside this check is the line you must meet."),
    kpi("Eligible leads that become bookings", "At least 40 out of every 100", "Leave out leads that are invalid, duplicate, outside the service area, impossible to do, or formally excluded.", "Count this: Eligible leads that became bookings. Compare that count with this: eligible leads. The target written beside this check is the line you must meet."),
    kpi("Cancellations written with the true reason", "Every cancellation", "A made-up reason hides the real problem.", "Count this: Cancellations with the correct status, reason, and details. Compare that count with this: cancellations checked. The target written beside this check is the line you must meet."),
    kpi("Customers told the truth about the booking", "At least 98 bookings out of every 100 checked", "The customer must hear the approved price, and must be told when the time, the provider, or the job changes.", "Count this: Bookings that included the required updates. Compare that count with this: bookings checked. The target written beside this check is the line you must meet."),
    kpi("Leads that followed the written steps", "At least 98 leads out of every 100 checked", "The steps on this page are the way the work is done.", "Count this: Leads that followed the written steps. Compare that count with this: leads checked. The target written beside this check is the line you must meet."),
    kpi("Comments written after each important step", "Every lead that needed a comment", "The comment is what the next person reads.", "Count this: Leads with the required comment. Compare that count with this: leads that needed a comment. The target written beside this check is the line you must meet.")
  ];

  const cxmKpis = [
    kpi("Customer leads assigned the same day", "Every customer and future-customer lead", "A lead in the inbox has no owner.", "Count this: Customer and future-customer leads with a type, a customer priority, and one owner written the same day. Compare that count with this: those leads received. The target written beside this check is the line you must meet. A service provider lead moved to the provider inbox the same day counts as assigned out of this queue."),
    kpi("Hot leads have an owner before a cold lead is started", "Every hot lead", "The team must not stay on cold leads while a hot lead has no owner.", "Count this: Hot leads that had an owner before any cold lead was started that day. Compare that count with this: hot leads received. The target written beside this check is the line you must meet."),
    kpi("A deputy is named before leave", "Every leave", "If the name is blank, the team starts picking.", "Count this: Leaves where one Customer Experience Executive was named, with the dates, in the admin panel before the leave started. Compare that count with this: your leaves. The target written beside this check is the line you must meet."),
    kpi("Team lead data accuracy", "At least 99 correct out of every 100 checked", "Shows whether the team is keeping reliable customer records.", "Count this: Correct lead records among the records you opened. Compare that count with this: total checked lead records. The target written beside this check is the line you must meet."),
    kpi("Team booking data accuracy", "At least 99 correct out of every 100 checked", "Prevents booking errors.", "Count this: Correct booking records. Compare that count with this: checked booking records. The target written beside this check is the line you must meet."),
    kpi("Team follow-up", "Every one", "The manager is accountable for follow-up discipline across the team.", "Count this: Completed required follow-ups. Compare that count with this: total follow-ups due. The target written beside this check is the line you must meet."),
    kpi("Avoidable missed follow-ups", "None", "Preventable follow-up failures are being controlled.", "Count this: Avoidable missed follow-ups. Compare that count with this: total follow-ups due. The target written beside this check is the line you must meet."),
    kpi("Missed or unattended leads", "No more than 1 out of every 100", "Every lead is attended to.", "Count this: Missed or unattended leads. Compare that count with this: total assigned leads. The target written beside this check is the line you must meet."),
    kpi("Required data completion", "Every one", "The team keeps complete operational information.", "Count this: Leads with all required information. Compare that count with this: checked leads. The target written beside this check is the line you must meet."),
    kpi("Team recording", "Every one", "Required customer interactions are recorded.", "Count this: Required recordings completed. Compare that count with this: recordings required. The target written beside this check is the line you must meet."),
    kpi("Team ticket update", "At least 98 out of every 100", "Team work stays visible.", "Count this: Correct on-time ticket updates. Compare that count with this: updates required. The target written beside this check is the line you must meet."),
    kpi("Team process", "At least 98 out of every 100", "The manager is enforcing the customer-support process.", "Count this: Cases following required steps. Compare that count with this: checked cases. The target written beside this check is the line you must meet."),
    kpi("Provider coordination accuracy", "At least 99 correct out of every 100 checked", "The customer team gives and receives correct provider information.", "Count this: Correct provider coordination cases. Compare that count with this: checked coordination cases. The target written beside this check is the line you must meet."),
    kpi("Booking status accuracy", "At least 99 correct out of every 100 checked", "Management sees the actual booking position.", "Count this: Correct booking statuses. Compare that count with this: checked bookings. The target written beside this check is the line you must meet."),
    kpi("Post-service follow-up", "Every one", "Completed services are followed up.", "Count this: Completed post-service follow-ups. Compare that count with this: follow-ups required. The target written beside this check is the line you must meet."),
    kpi("Payment status accuracy", "Every one", "Bookings are not closed or shown as paid when they are not.", "Count this: Correct payment statuses. Compare that count with this: checked payment records. The target written beside this check is the line you must meet."),
    kpi("Complaint follow-up", "Every one", "Complaints are monitored until resolution or handover.", "Count this: Complaint follow-ups completed. Compare that count with this: complaint follow-ups due. The target written beside this check is the line you must meet."),
    kpi("Future customer follow-up", "Every one", "The team does not lose potential future customers.", "Count this: Completed future-customer follow-ups. Compare that count with this: follow-ups due. The target written beside this check is the line you must meet."),
    kpi("Eligible future customer conversion", "At least 50 out of every 100", "How effectively the team converts eligible future customers.", "Count this: Eligible future customers converted. Compare that count with this: eligible future customers available. The target written beside this check is the line you must meet."),
    kpi("Eligible lead conversion", "At least 40 out of every 100", "Team conversion on leads that are genuinely eligible.", "Count this: Eligible leads converted. Compare that count with this: total eligible leads. The target written beside this check is the line you must meet."),
    kpi("Bookings taken and bookings finished are read together", "Every weekly report", "The same person takes the booking and finishes the job. A high booking count can hide a job that never finished, or a job where the customer payment or the provider settlement is still missing from the admin panel.", "On the weekly report, write two counts next to each other. The first count is eligible leads that became bookings. Use the written bands: 40 or more out of 100 is excellent, 30 to 39 is acceptable, 20 to 29 needs improvement, and below 20 is critical. The second count is those same bookings that were later finished to the written standard, with the customer payment and the provider settlement both written in the admin panel. If the first count is excellent or acceptable, and the second count is missing from the page, that week is a miss. It is also a miss when a booking in that set is marked finished while the service does not meet the written standard, the call after the service is missing, or either side of the money is not in the admin panel. It is also a miss when a booking in that set has already passed its visit date and is still not finished to that standard. Send each of those bookings back to the Customer Experience Executive who owns it, the same week. Do not add another seat. That executive stays the owner of the customer."),
    kpi("Complaint resolution or progress", "Every one", "Complaints are actively managed.", "Count this: Complaints with required action or progress within standard. Compare that count with this: complaints requiring action. The target written beside this check is the line you must meet."),
    kpi("Team training completion", "Every one", "Identified training is actually completed.", "Count this: Required training completed. Compare that count with this: training assigned. The target written beside this check is the line you must meet."),
    kpi("Training follow-up", "Every one", "Training is checked afterwards, not treated as finished after one session.", "Count this: Trained employees subsequently monitored. Compare that count with this: employees requiring post-training monitoring. The target written beside this check is the line you must meet."),
    kpi("Manager report accuracy", "At least 99 correct out of every 100 checked", "Management decisions depend on reliable reports.", "Count this: Accurate report items. Compare that count with this: total checked report items. The target written beside this check is the line you must meet."),
    kpi("Manager report submission", "Every one", "Management receives required information on time.", "Count this: Reports submitted on time. Compare that count with this: reports due. The target written beside this check is the line you must meet."),
    kpi("Corrective action completion", "Every one", "Identified team problems are actually corrected.", "Count this: Corrective actions completed by deadline. Compare that count with this: corrective actions due. The target written beside this check is the line you must meet."),
    kpi("Repeated issue action", "Every one", "Repeated problems are investigated.", "Count this: Repeated issues with a documented cause and action. Compare that count with this: total identified repeated issues. The target written beside this check is the line you must meet."),
    kpi("Attendance and shift coverage monitoring", "Every one", "Customer-support coverage is managed.", "Count this: Required coverage periods monitored. Compare that count with this: total required coverage periods. The target written beside this check is the line you must meet.")
  ];

  const poeKpis = [
    kpi("Onboard-now leads handled before later leads", "Every provider lead with your name", "A provider from outside the current area or category must not jump ahead of a provider you can use now.", "Days where onboard-now leads with your name were handled before write-down-later leads. A later lead onboarded while an onboard-now lead was waiting counts as a miss."),
    kpi("No provider lead taken from the inbox", "Every provider lead you handle", "Picking your own provider lead hides the ones that need onboarding now.", "Count this: Provider leads you handled that already had your name written by the assigner. Compare that count with this: provider leads you handled. The target written beside this check is the line you must meet."),
    kpi("Provider lead response", "Every one", "Provider leads are not ignored or delayed.", "Count this: Provider leads contacted within the required time. Compare that count with this: provider leads received. The target written beside this check is the line you must meet."),
    kpi("Provider lead data accuracy", "At least 99 correct out of every 100 checked", "Provider records stay reliable.", "Count this: Accurate provider records. Compare that count with this: records audited. The target written beside this check is the line you must meet."),
    kpi("Required provider data completion", "Every one", "Every required provider detail is recorded.", "Count this: Complete required fields. Compare that count with this: required fields required. The target written beside this check is the line you must meet."),
    kpi("Provider follow-up", "Every one", "Pending providers are not forgotten.", "Count this: Completed required follow-ups. Compare that count with this: follow-ups due. The target written beside this check is the line you must meet."),
    kpi("Missed or unattended provider leads", "None", "Every provider opportunity is handled.", "Count this: Unattended provider leads. Compare that count with this: provider leads received. The target written beside this check is the line you must meet."),
    kpi("Provider document", "Every one", "Required documents are collected and stored.", "Count this: Provider files with all required documents. Compare that count with this: provider files audited. The target written beside this check is the line you must meet."),
    kpi("Onboarding completion", "Every one", "Incomplete providers are not activated.", "Count this: Completed required onboarding steps. Compare that count with this: onboarding steps required. The target written beside this check is the line you must meet."),
    kpi("Provider activation accuracy", "Every one", "Unverified providers are not marked active.", "Count this: Correctly activated providers. Compare that count with this: providers activated. The target written beside this check is the line you must meet."),
    kpi("Provider availability update", "Every one", "Actual provider capacity stays visible.", "Count this: Availability updates completed. Compare that count with this: availability updates due. The target written beside this check is the line you must meet."),
    kpi("Provider information update accuracy", "At least 99 correct out of every 100 checked", "The official provider database stays current.", "Count this: Correct updates. Compare that count with this: updates audited. The target written beside this check is the line you must meet."),
    kpi("Provider booking coordination accuracy", "At least 99 correct out of every 100 checked", "Prevents booking errors and miscommunication.", "Count this: Correct provider coordination cases. Compare that count with this: cases audited. The target written beside this check is the line you must meet."),
    kpi("Provider confirmation", "Every one", "Providers confirm assigned work before they are treated as confirmed.", "Count this: Confirmed required bookings. Compare that count with this: bookings requiring confirmation. The target written beside this check is the line you must meet."),
    kpi("Provider issue follow-up", "Every one", "Provider problems do not sit unattended.", "Count this: Issues followed up. Compare that count with this: issues requiring follow-up. The target written beside this check is the line you must meet."),
    kpi("Provider complaint update", "Every one", "Complaints are actively managed.", "Count this: Complaints with required updates. Compare that count with this: complaints requiring updates. The target written beside this check is the line you must meet."),
    kpi("Provider payment data accuracy", "At least 99 correct out of every 100 checked", "Reduces payment disputes.", "Count this: Accurate payment records. Compare that count with this: payment records audited. The target written beside this check is the line you must meet."),
    kpi("Provider payment information completion", "Every one", "Accounts has the information it needs.", "Count this: Complete payment records. Compare that count with this: records requiring payment information. The target written beside this check is the line you must meet."),
    kpi("Training completion", "Every one", "Providers receive required process training.", "Count this: Providers completing required training. Compare that count with this: providers requiring training. The target written beside this check is the line you must meet."),
    kpi("Training record accuracy", "Every one", "Training records are not false or incomplete.", "Count this: Accurate training records. Compare that count with this: training records audited. The target written beside this check is the line you must meet.")
  ];

  const pomKpis = [
    kpi("Provider leads assigned the same day", "Every service provider lead", "A provider lead in the inbox has no owner.", "Count this: Service provider leads with a provider priority and one owner written the same day. Compare that count with this: service provider leads in the provider inbox. The target written beside this check is the line you must meet."),
    kpi("Onboard-now leads are assigned before later leads are worked", "Every onboard-now lead", "An outside-area provider must not be onboarded while a current-area provider is waiting.", "Count this: Onboard-now leads that had an owner before any write-down-later lead was onboarded that day. Compare that count with this: onboard-now leads received. The target written beside this check is the line you must meet."),
    kpi("A deputy is named before leave", "Every leave", "If the name is blank, Provider Support starts picking.", "Count this: Leaves where one Provider Support person was named, with the dates, in the admin panel before the leave started. Compare that count with this: your leaves. The target written beside this check is the line you must meet."),
    kpi("Team provider data accuracy", "At least 99 correct out of every 100 checked", "The provider database stays reliable.", "Count this: Accurate team records. Compare that count with this: records audited. The target written beside this check is the line you must meet."),
    kpi("Team required data completion", "Every one", "Required provider information is maintained.", "Count this: Complete required records. Compare that count with this: records audited. The target written beside this check is the line you must meet."),
    kpi("Team follow-up", "Every one", "Provider tasks are not missed.", "Count this: Completed required follow-ups. Compare that count with this: follow-ups due. The target written beside this check is the line you must meet."),
    kpi("Avoidable missed provider follow-ups", "None", "The manager prevents repeated follow-up failures.", "Count this: Avoidable missed follow-ups. Compare that count with this: follow-ups due. The target written beside this check is the line you must meet."),
    kpi("Missed or unattended provider leads", "No more than 1 out of every 100", "The team does not lose provider opportunities.", "Count this: Missed or unattended leads. Compare that count with this: provider leads received. The target written beside this check is the line you must meet."),
    kpi("Provider onboarding", "Every one", "Providers are onboarded before activation.", "Count this: Correct onboardings. Compare that count with this: onboardings completed. The target written beside this check is the line you must meet."),
    kpi("Provider documentation", "Every one", "Required provider files stay complete.", "Count this: Complete correct files. Compare that count with this: files audited. The target written beside this check is the line you must meet."),
    kpi("Provider availability monitoring", "Every one", "Provider capacity stays visible and current.", "Count this: Required availability checks completed. Compare that count with this: checks due. The target written beside this check is the line you must meet."),
    kpi("Provider coverage monitoring", "Every one", "Area and category gaps are identified.", "Count this: Coverage reviews completed. Compare that count with this: reviews due. The target written beside this check is the line you must meet."),
    kpi("Coverage gap action completion", "Every one", "Identified provider shortages are acted on.", "Count this: Coverage actions completed. Compare that count with this: actions due. The target written beside this check is the line you must meet."),
    kpi("Provider issue follow-up", "Every one", "Provider problems are not left pending.", "Count this: Issues with required follow-up. Compare that count with this: issues requiring follow-up. The target written beside this check is the line you must meet."),
    kpi("Provider complaint progress", "Every one", "Provider complaints are actively managed.", "Count this: Complaints with required action or progress. Compare that count with this: complaints requiring action. The target written beside this check is the line you must meet."),
    kpi("Provider booking coordination accuracy", "At least 99 correct out of every 100 checked", "Reduces provider-side booking errors.", "Count this: Accurate coordination cases. Compare that count with this: cases audited. The target written beside this check is the line you must meet."),
    kpi("Provider confirmation accuracy", "Every one", "Booking confirmation information is reliable.", "Count this: Correct confirmations. Compare that count with this: confirmations audited. The target written beside this check is the line you must meet."),
    kpi("Provider payment data accuracy", "At least 99 correct out of every 100 checked", "Reduces provider payment disputes.", "Count this: Accurate payment information. Compare that count with this: payment records audited. The target written beside this check is the line you must meet.")
  ];

  const hooKpis = [
    kpi("A deputy is named when a manager is away and the inbox has no assigner", "Every such morning", "Until the name is written, the inbox stays closed and hot leads sit.", "Count this: Mornings where a manager was away, no deputy had been named, and you wrote one name before the inbox was opened. Compare that count with this: those mornings. The target written beside this check is the line you must meet."),
    kpi("Manager report submission", "Every one", "Management receives required information on time.", "Count this: Required manager reports submitted on time. Compare that count with this: total reports due. The target written beside this check is the line you must meet."),
    kpi("Manager report accuracy", "At least 99 correct out of every 100 checked", "Decisions are based on reliable information.", "Count this: Accurate report items. Compare that count with this: total checked items. The target written beside this check is the line you must meet."),
    kpi("Manager KPI review", "Every one", "Managers are actually being monitored.", "Count this: Completed required manager KPI reviews. Compare that count with this: reviews due. The target written beside this check is the line you must meet."),
    kpi("Daily operational review", "Every one", "Critical issues stay under control.", "Count this: Completed daily reviews. Compare that count with this: required reviews. The target written beside this check is the line you must meet."),
    kpi("Weekly operational review", "Every one", "Operational control is regular.", "Count this: Completed weekly reviews. Compare that count with this: required reviews. The target written beside this check is the line you must meet."),
    kpi("Monthly operational review", "Every one", "Management gets a period view, not a pile of daily notes.", "Count this: Completed monthly reviews. Compare that count with this: required reviews. The target written beside this check is the line you must meet."),
    kpi("Action-item follow-up", "Every one", "Important actions are not forgotten.", "Count this: Action items followed up by due date. Compare that count with this: action items due. The target written beside this check is the line you must meet."),
    kpi("Corrective action tracking", "Every one", "Identified problems are actually addressed.", "Count this: Corrective actions with owner, status, and follow-up. Compare that count with this: total corrective actions. The target written beside this check is the line you must meet."),
    kpi("Operational report accuracy", "At least 99 correct out of every 100 checked", "Consolidated reports are reliable.", "Count this: Accurate checked report items. Compare that count with this: total checked items. The target written beside this check is the line you must meet."),
    kpi("Lead and booking analysis accuracy", "At least 99 correct out of every 100 checked", "Conversion and booking performance can be trusted.", "Count this: Accurate analysed figures. Compare that count with this: total checked figures. The target written beside this check is the line you must meet."),
    kpi("Revenue and collection report accuracy", "At least 99 correct out of every 100 checked", "Financial management information is not guessed.", "Count this: Accurate checked figures. Compare that count with this: total checked figures. The target written beside this check is the line you must meet."),
    kpi("Provider coverage reporting", "Every one", "Major coverage gaps are identified.", "Count this: Required coverage reports completed. Compare that count with this: reports due. The target written beside this check is the line you must meet."),
    kpi("Area and service analysis completion", "Every one", "Demand is analysed by area and by service.", "Count this: Required analyses completed. Compare that count with this: analyses due. The target written beside this check is the line you must meet."),
    kpi("Escalation", "Every one", "Serious issues reach the correct authority.", "Count this: Issues requiring escalation that were escalated correctly. Compare that count with this: total issues requiring escalation. The target written beside this check is the line you must meet."),
    kpi("Critical issue progress tracking", "Every one", "Major problems stay controlled until they are closed.", "Count this: Critical issues with a current owner, status, and action. Compare that count with this: total critical issues. The target written beside this check is the line you must meet.")
  ];

  const sharedGloss = [
    { id: "eligible-lead", term: "Eligible lead", aliases: ["eligible lead", "eligible leads", "qualified lead"], meaning: "A lead that can actually become a booking. Exclude invalid, duplicate, outside the service area, impossible to fulfil, or otherwise formally excluded under company rules. The line is at least 40 bookings out of every 100 eligible leads. 40 or more out of 100 is excellent. 30 to 39 is acceptable. 20 to 29 needs improvement. Below 20 is critical." },
    { id: "future-customer", term: "Future customer", aliases: ["future customer", "future customers", "eligible future customer"], meaning: "A customer who is not ready to book now, but is eligible later. There is no fixed number. The line is at least 50 bookings out of every 100 eligible future customers given to that person in the period. If they had 20 people, the line is 10 bookings. 50 or more out of 100 is excellent. 40 to 49 is acceptable. 30 to 39 needs improvement. Below 30 is critical." },
    { id: "follow-up", term: "Follow-up", aliases: ["follow-up", "follow-ups", "required follow-up"], meaning: "A dated next action with the action taken, the customer or provider response, and the next step. No response is not a completed follow-up. If they are unreachable, record the required attempt, the remark, the next action, and the next time. The line is every follow-up. A lower number on a check only shows how far the work has slipped. It does not lower the line." },
    { id: "approved-estimate", term: "Approved estimate", aliases: ["approved estimate", "approved pricing", "company-approved pricing"], meaning: "The price the company has already approved. You may say this price before booking. You may not invent a price, a discount, a refund, compensation, or a special offer." },
    { id: "booking-complete", term: "Fully completed", aliases: ["fully completed", "marked complete", "marked fully completed"], meaning: "A booking is fully completed only when the service meets the written standard for that job, the call after the service is written, the customer payment is written in the admin panel, and the provider settlement is written in the admin panel. If either side of the money is still open, the booking is not complete." },
    { id: "handover", term: "Handover", aliases: ["handover", "handed over", "proper handover"], meaning: "The written pass at the end of a shift: current status, work completed, pending work, next action, and the required follow-up time. Pending work without this file did not get handed over." },
    { id: "no-response", term: "No response", aliases: ["no response", "unreachable"], meaning: "The customer or provider did not answer. That is a result to record, not a completed follow-up. Write the attempt, the remark, the next action, and the next time. Still send a message that you called, why you called, and when you will try again. The last message on the lead is from Panun Kaergar." },
    { id: "inbox", term: "Inbox", aliases: ["inbox", "unassigned"], meaning: "The list of new leads that do not have an owner yet. Leads arrive by phone call, WhatsApp, the website, and social media. Nobody handles a lead from the inbox. The assigner marks the type, the priority, and one owner. Until an owner is written, the lead stays in the inbox. If the manager is on leave and no deputy has been named, the inbox stays closed until the Head of Operations writes that name." },
    { id: "customer-priority", term: "Customer priority", aliases: ["hot", "warm", "cold", "hot lead", "warm lead", "cold lead", "priority"], meaning: "The order for a customer or a future customer. Hot: they need the service now, or a booking already made is in trouble today. Warm: they want the service and will talk about price and a date, and it is not urgent today. Cold: they are only asking, or they are a future customer with no date. Inside the same group, the older lead comes first. A hot lead comes before a warm lead. A warm lead comes before a cold lead." },
    { id: "provider-priority", term: "Provider priority", aliases: ["onboard now", "write down, do later", "provider priority"], meaning: "The order for a service provider lead. Onboard now: they work in an area Panun Kaergar already serves and in a category Panun Kaergar already sells. Write down, do later: they are outside those areas or categories. Record them the same day and send the message. Do not onboard them while an onboard-now lead is waiting." },
    { id: "assigner", term: "Assigner", aliases: ["assigner", "assigns the lead"], meaning: "The person who marks the type, the priority, and one owner. The Customer Experience Manager assigns customer and future-customer leads, and moves a service provider lead into the provider inbox the same day. The Provider Experience Manager assigns service provider leads. They do not ask the team who wants the lead. The next owner is the person on shift with the fewest hot leads, then the fewest open leads. One lead has one name." },
    { id: "deputy", term: "Deputy", aliases: ["deputy", "on leave"], meaning: "The one named person who assigns while the manager is on leave. The Customer Experience Manager names one Customer Experience Executive before the leave, with the dates, in the admin panel. The Provider Experience Manager names one Provider Support person the same way. If the manager is already away and no deputy was named, the Head of Operations writes one name that morning, before anyone opens the inbox. Until that name is written, the inbox stays closed. The deputy only assigns. The deputy does not approve a price, a booking advance, a discount, or a refund. On each lead the deputy writes the priority, the owner, and that it was assigned while the manager was on leave. When the manager is back, the deputy stops assigning. The manager reads the hot leads from those days and takes the queue back." }
  ];

  function addRole(spec) {
    spec.dept = "operations";
    spec.layout = "detailed";
    G.roles[spec.id] = spec;
    if (G.roleIds.indexOf(spec.id) === -1) G.roleIds.push(spec.id);
  }

  addRole({
    id: "cxe",
    name: "Customer Experience Executive",
    reportsTo: "Customer Experience Manager",
    hero: "role-cxe.png",
    result: "Every lead with your name is handled in the written order and kept moving until it ends, and the last message on that lead is from Panun Kaergar, so another person can continue it from the admin panel.",
    what: "You do not take a lead from the inbox. The Customer Experience Manager, or the deputy named while that manager is on leave, writes your name on the lead. You then work hot leads before warm leads, and warm leads before cold leads. Inside the same band, the older lead comes first. A customer wants work now. A future customer may want work later. A service provider does not stay on your list. Your team lead is the Customer Experience Manager.",
    why: "If you pick your own lead, a hot lead waits while you stay on a cold one. If the last message is theirs, or a missed call has no message after it, the lead looks abandoned. If you are away tomorrow, the next person must continue that lead from the admin panel.",
    how: "At the start of the shift, open the leads and bookings that already have your name. Do the hot ones first, oldest first, then warm, then cold. Do not start a cold lead while a hot or warm lead with your name is waiting for a first contact or a follow-up that is due. If you are already on a call when a hot lead is assigned, finish that call, send the message, and the hot lead is next. Write the full details in the admin panel. If a service provider was put on your name, return it to the assigner the same day and do not onboard them. Keep a future customer on follow-up. For a customer, give the approved price, and if they agree, take the booking advance in the company payment policy, find a provider, and create the booking. Before the visit, confirm both sides will be there and both know the same service charge. After the work, settle the customer and the provider, and write both in the admin panel. After every call, send a message. The last message is always from Panun Kaergar.",
    lanes: [
      { kicker: "Part 1", title: "Work the leads that have your name, in order", work: "Open only the leads and bookings the assigner has given you. Hot first, then warm, then cold. Oldest first inside the same band. Confirm the type the assigner wrote. If it is wrong, tell the assigner the same day and stop that path. If the person is a service provider, return the lead the same day.", result: "A hot lead with your name is handled before a cold one, and the admin panel shows the true type.", who: "You. You do not open the inbox." },
      { kicker: "Part 2", title: "Send the lead down the right path", work: "A future customer: write the full details, set the next follow-up, and keep calling until they book, they decline, or the lead is closed with a reason. A customer: write every required detail in the admin panel, give the price from the approved price file, and if they agree to book, take the booking advance in the company payment policy, find a provider, and create the booking. A service provider is not your path.", result: "A future customer has a next time. A customer who agreed has a booking in the admin panel.", who: "You. Provider Support briefs a service provider after the Provider Experience Manager assigns that lead." },
      { kicker: "Part 3", title: "Stay with the booking until both sides are settled", work: "Check the leads and bookings you handle every working day and write what changed. Follow up on the date written on the lead. Before the work starts, call so the customer and the provider both confirm they will be at the address, and both have been told the same service charge. After the work, make sure the customer payment is settled and the provider side is settled, and write both in the admin panel. After every call, including a call they did not answer, send the call brief by message. The last message on the lead is from Panun Kaergar.", result: "The visit happens with both sides present, the service meets the written standard, both payments are written in the admin panel, and the file is complete for the next person.", who: "You." }
    ],
    acting: "If nobody is in this job, the Customer Experience Manager does the work for that day and writes that they are doing it because the job is empty. If you are the named deputy while the Customer Experience Manager is on leave, you assign for those dates and you do not keep the leads. You still follow these steps on any lead the assignment rule gives you. You do not approve a price, a booking advance, a discount, or a refund while you are the deputy. If one of those cannot wait, send it to the Head of Operations the same day.",
    owns: [
      "The leads and bookings that have your name, worked hot, then warm, then cold",
      "The full customer details in the admin panel",
      "A service provider lead returned the same day if it was put on your name",
      "The price you say, taken from the approved price file",
      "The booking advance, taken only as the company payment policy states",
      "The booking in the admin panel, the visit, and the follow-up before work starts",
      "The last message on every lead, which is from Panun Kaergar",
      "The customer payment and the provider settlement, both written in the admin panel"
    ],
    mustNot: [
      "Take a lead from the inbox, or choose the next lead yourself",
      "Start a cold lead while a hot or warm lead with your name is waiting for a first contact or a due follow-up",
      "Keep leads for yourself while you are the deputy, unless the written assignment rule gave you that lead",
      "Approve a price, a booking advance, a discount, or a refund while the Customer Experience Manager is on leave",
      "Start a customer booking for a person who is a service provider",
      "Register a service provider, collect their documents, or mark them active",
      "Invent a price, a booking advance, a discount, a refund, or money back",
      "Leave a phone call, including a missed call, without a message after it",
      "Leave their message as the last message on the lead",
      "Let the visit happen before both sides have confirmed they will be there and have heard the same service charge",
      "Mark the booking finished while the customer payment or the provider settlement is still open",
      "Leave the admin panel blank so the next person has to call you"
    ],
    good: [
      "You worked the hot leads with your name before any cold lead.",
      "A service provider that landed on your name was returned the same day, with no onboarding.",
      "A customer who agreed has an estimate, a booking advance as the policy states, a provider, and a booking in the admin panel.",
      "The message after your call, or after a missed call, is on the lead, and it is the latest message.",
      "Before the visit, the customer and the provider have both said they will be there, and both know the same service charge.",
      "After the work, the customer payment and the provider settlement are both written in the admin panel."
    ],
    bad: [
      "You opened the inbox and took a lead.",
      "A cold lead was started while a hot lead with your name was still waiting.",
      "A service provider was kept on your list.",
      "A price or a booking advance was made up.",
      "You called, they did not answer, and no message was sent.",
      "The customer wrote last, and you did not reply the same day.",
      "The provider reached the address and the customer was not there, or the customer waited and the provider did not come.",
      "The service is marked done while the provider has not been settled.",
      "The next person cannot see the lead in the admin panel."
    ],
    records: [
      "Where the lead came from: phone call, WhatsApp, website, or social media",
      "The type: customer, future customer, or service provider",
      "Customer name, phone number, exact job, full address and area, and the appliance or property, when the lead is a customer or a future customer",
      "Service provider name, phone, the work they do, and their area, when the lead is a provider",
      "The approved price you gave, and whether they accepted it",
      "The booking advance taken, and the policy amount it followed",
      "The provider on the booking, and their written yes",
      "The message you sent after each call, including a missed call",
      "The confirmation, before work starts, that the customer will be there, the provider will be there, and both know the same service charge",
      "The customer payment and the provider settlement, both in the admin panel",
      "If the customer paid the provider in cash: the customer, the booking, the amount, the provider, the date, and who is holding the cash",
      "The call recording, linked to the lead, when a recording is required",
      "The comment: what you did, what they said, the next action, and the next time"
    ],
    rules: [
      "Work only the leads that have your name. Hot before warm before cold. Oldest first inside the same band. Do not open the inbox.",
      "If you are the named deputy, assign for the written dates only. Write the priority, the owner, and that the manager is on leave. Do not keep the lead unless the assignment rule gives it to you. Do not approve a price, a booking advance, a discount, or a refund.",
      "A service provider lead does not stay with you. Return it to the assigner the same day. You do not brief them and you do not onboard them.",
      "A customer lead is written in full in the admin panel. The price comes from the approved price file. The booking advance comes from the company payment policy. If either is missing from those writings, stop and ask the Customer Experience Manager.",
      "After every phone call, send a message with the call brief. If they do not answer, still send the message. If they write last, reply the same day. The last message on the lead is from Panun Kaergar.",
      "Before the work starts, the customer and the provider have both confirmed they will be at the address, and both have been told the same service charge. Write that confirmation in the admin panel.",
      "After the work, the customer side and the provider side are both settled, and both are written in the admin panel. The booking stays open until that is true.",
      "Check every lead and booking assigned to you on every working day, and write what changed.",
      "Your team lead is the Customer Experience Manager. Follow their instruction on a lead."
    ],
    kpis: cxeKpis,
    detailed: {
      copy: {
        heroKicker: "What this job is",
        defTitle: "What this job is",
        lanesTitle: "This job has three parts",
        definition: "You are the Customer Experience Executive. You work the leads the assigner has put your name on, in order: hot, then warm, then cold. A future customer is followed up. A customer is given a price, and if they agree, you take the booking advance, find a provider, create the booking, keep both sides from missing the visit, and settle the customer and the provider after the work. A service provider does not stay on your list. The last message on every lead is from Panun Kaergar. Your team lead is the Customer Experience Manager. If you are the named deputy while that manager is on leave, you assign and you do not keep the leads.",
        owns: "You own the leads that have your name, from that moment until the lead ends or you hand it on in the admin panel. You do not own the inbox. You do not own provider onboarding. You do not own a price that is not in the approved price file.",
        lanes: "One result. Three parts. Work your list in order. Send the lead down the right path. Stay with a booking until both sides have settled and the admin panel shows it.",
        responsibilities: "The parts of the job. Open a row to read the full steps.",
        handoffs: "A lead arrives in writing or on a call, and it leaves as a complete record in the admin panel. A chat that is not copied into the admin panel did not happen.",
        workflow: "Open only the leads with your name. Hot, then warm, then cold. Then use the path for a future customer or a customer. Before you leave, the admin panel is up to date and the last message on each open lead is from Panun Kaergar.",
        reporting: "The admin panel is the report. The Customer Experience Manager should see the type, the last message, the booking, and both payments without calling you.",
        standards: "The line this job is held to. Open a card to read the full standard.",
        kpis: "These checks ask whether the lead was identified, whether the last message is from Panun Kaergar, whether both sides attended, and whether both payments are in the admin panel.",
        escalations: "What you send to your team lead. You do not choose a price, a booking advance, a discount, or a refund yourself."
      },
      hub: { icon: "support_agent", line: "You work the leads that have your name, hot before warm before cold. Provider Support takes a provider lead after the Provider Experience Manager assigns it. The Customer Experience Manager assigns the inbox." },
      definition: {
        what: [
          { title: "You work the leads that have your name, in the written order", why: "Hot before warm before cold. Oldest first inside the same band. You do not open the inbox." },
          { title: "You run the path that matches the type", why: "A future customer stays on follow-up. A customer gets a full record, a price, and a booking if they agree. A service provider is returned the same day." },
          { title: "You keep the last message, the visit, and both payments in the open", why: "After every call you send a message, even when they do not answer. Before the work, both sides confirm they will be there and both know the service charge. After the work, the customer and the provider are both settled in the admin panel." }
        ],
        why: [
          { title: "The wrong type sends the lead to the wrong team", why: "Provider Support cannot onboard a person who was never forwarded. A customer cannot be booked from a blank panel." },
          { title: "A silent missed call looks like nobody called", why: "The message after the call is how the next person, and the customer, can see what happened." },
          { title: "A visit with one side missing is a failed booking", why: "The call before the work exists so the customer is not left waiting and the provider is not left at an empty address." }
        ]
      },
      glossary: sharedGloss.concat([
        { id: "lead-type", term: "Lead type", aliases: ["lead type", "type of lead", "what type of lead"], meaning: "The first decision on a new lead. Write one of these in the admin panel: customer, future customer, or service provider. A customer wants work now. A future customer may want work later. A service provider wants to work with Panun Kaergar. If the person is none of these, write what they asked for and send it to the Customer Experience Manager the same day. Do not invent a type." },
        { id: "last-message", term: "Last message", aliases: ["last message", "latest message"], meaning: "The newest message on the lead. It must be from Panun Kaergar. If the other person wrote last, you reply the same day with the next step. If you called and they did not answer, you still send a message. A phone call without that message does not count as contact." },
        { id: "call-brief", term: "Call brief", aliases: ["call brief"], meaning: "The message you send after a phone call. It says you called from Panun Kaergar, what was discussed, the price if you gave one, and the next step. If they did not answer, the message says you called, why you called, and when you will try again." },
        { id: "booking-advance", term: "Booking advance", aliases: ["booking advance", "advance"], meaning: "The amount the company payment policy already tells you to collect when a customer agrees to book. You take that amount. You do not choose a different amount. If the policy does not show an amount for that job, you ask the Customer Experience Manager before you take any money." },
        { id: "no-show", term: "No-show", aliases: ["no-show", "no show"], meaning: "The customer is not at the address when the provider arrives, or the provider does not arrive when the customer is waiting. Before the work starts, you confirm both will be there. You write that confirmation in the admin panel." },
        { id: "both-sides", term: "Both sides settled", aliases: ["both sides", "provider side", "customer side"], meaning: "After the work, the customer has paid what is due, and the provider side has been settled under the company payment steps. Both are written in the admin panel. The booking stays open until both are written." },
        { id: "cxe", term: "Customer Experience Executive", aliases: ["Customer Experience Executive", "Customer Support Executive"], meaning: "This job. You handle the leads that have your name, in the written order, until they end. You do not take leads from the inbox. The Customer Experience Manager is your team lead, and the assigner, unless you are the named deputy for their leave." },
        { id: "team-lead", term: "Team lead", aliases: ["team lead", "Customer Team Lead"], meaning: "The Customer Experience Manager. There is no separate team-lead seat." }
      ]),
      responsibilities: [
        card("Responsibility 1", "Work your list in the written order", "Open only the leads and bookings that have your name.", ["Hot first. Then warm. Then cold.", "Inside the same band, the older lead comes first.", "Do not start a cold lead while a hot or warm lead with your name is waiting for a first contact or a follow-up that is due.", "If you are already on a call when a hot lead is assigned, finish that call, send the message, and the hot lead is next.", "Confirm the type the assigner wrote. If it is wrong, or it is none of customer, future customer, or service provider, tell the assigner the same day and stop.", "Do not open the inbox."], [{ title: "The order is the job", why: "A cold lead is easier. It is not next while a hot lead is waiting." }]),
        card("Responsibility 2", "Return a service provider the same day", "Do this if a service provider was put on your name.", ["Do not brief them, collect documents, train them, or mark them active.", "Tell the assigner the same day so the lead can go to the provider inbox.", "If you are the named deputy, you move that lead to the provider inbox yourself, the same day, and you do not assign it to a Customer Experience Executive."], [{ title: "Onboarding is not this job", why: "Provider Support briefs the provider after the Provider Experience Manager assigns an owner." }]),
        card("Responsibility 3", "Write a customer or a future customer in full in the admin panel", "Do this the same day, for every customer lead and every future customer.", ["Name and phone number.", "Where the lead came from.", "The exact job they want.", "The full address and the area.", "The appliance, product, or property.", "The price from the approved price file, once you have given it.", "The status, what you did, what they said, the next action, and the next time."], [{ title: "A blank field is a guess for the next person", why: "The admin panel is the only place the next executive looks." }]),
        card("Responsibility 4", "When a customer agrees, take the advance, find a provider, and create the booking", "Do this only after they accept the price from the approved price file.", ["Take the booking advance stated in the company payment policy. Write the amount in the admin panel.", "Choose the provider in this order: the right area, free at the customer's time, able to do that job, nearest, then the stronger record of quality.", "Write the provider's yes before you tell the customer the provider's name.", "Create the booking in the admin panel the same day: customer, address, job, price, advance, provider, date, and time.", "If no provider passes the list, start the search the same day, tell the customer the booking is still open, and tell Provider Support."], [{ title: "No booking without an advance and a provider yes", why: "A verbal plan is not a booking in the admin panel." }]),
        card("Responsibility 5", "Keep the last message from Panun Kaergar", "This applies to a customer, a future customer, and a service provider.", ["After a phone call, send this message and fill the brackets from the lead: Hello, this is Panun Kaergar. We spoke today about [the job]. The price we discussed is [the price from the approved price file]. The next step is [the next step], at [the time].", "If they do not answer, still send this message: Hello, this is Panun Kaergar. We called you today about [the job] and could not reach you. Please reply to this message. We will try again at [the next time].", "If they send a message after yours, reply the same day so the last message is yours. Say what you will do next and when.", "Copy the message onto the lead in the admin panel. Link the call recording when a recording is required."], [{ title: "A call that is not followed by a message did not finish", why: "The next person cannot hear the call. They can read the message." }]),
        card("Responsibility 6", "Before the work, confirm both sides. After the work, settle both sides", "Check every lead and booking assigned to you every working day. Write what changed.", ["Call before the work starts. Confirm the customer will be at the address. Confirm the provider will be at the address. Tell both the same service charge. Write all three confirmations in the admin panel.", "If either side is unsure, do not leave the visit as it is. Set a new time or find another provider, and tell both sides.", "When the work is done, check it against the written standard for that job. Write what was done and anything still missing. If something required is missing, the service is not done.", "Settle the customer payment. Settle the provider side under the company payment steps. Write both in the admin panel.", "If the customer paid the provider in cash, write the same day: the customer, the booking, the amount, the provider, the date, and who is holding the cash. Send that note to the Customer Experience Manager and to Accounts.", "Call the customer after the service and write what they said about the work, the charge, and the payment.", "The booking stays open until the service meets the standard, both sides are settled, and the admin panel shows it."], [{ title: "A no-show on either side is a failed visit", why: "The confirmation call exists so the customer is not waiting alone and the provider is not standing at an empty address." }])
      ],
      handoffs: [
        side(card("You receive", "A lead with your name", "The assigner has already written the type and the priority.", ["Work it in order. Do not take one from the inbox."], [], [["From", "Customer Experience Manager, or the deputy while that manager is on leave"], ["When", "When your name is written"]]), "in"),
        side(card("You receive", "The handover from the previous shift", "Open leads and bookings, with the type, the priority, the last message, and the next time.", ["Continue the hot ones first."], [], [["From", "The previous Customer Experience Executive"], ["When", "At the start of your shift"]]), "in"),
        side(card("You receive", "The provider's yes or no on a customer booking", "Provider Support asks the provider. You write the answer on the booking.", ["If the answer is no, keep the customer updated the same day and keep looking."], [], [["From", "Provider Support"], ["When", "Before you tell the customer a provider name"]]), "in"),
        side(card("You give", "A service provider lead that was put on your name", "Say it is a service provider and return it.", ["You do not brief them and you do not onboard them."], [], [["To", "The assigner"], ["When", "The same day"]]), "out"),
        side(card("You give", "A price, advance, discount, or refund while you are the deputy", "The request, and that the Customer Experience Manager is on leave.", ["Do not promise it."], [], [["To", "Head of Operations"], ["When", "The same day, if it cannot wait"]]), "out"),
        side(card("You give", "The full job to the provider on a booking", "The job, the address, the time, the service charge, and any special instruction.", ["Give this before the visit, and again in the confirmation before the work starts."], [], [["To", "The provider who said yes"], ["When", "Before the visit"]]), "out"),
        side(card("You give", "The admin panel record", "Type, details, messages, booking, both confirmations, and both payments.", ["The Customer Experience Manager checks this record without calling you."], [], [["To", "Customer Experience Manager"], ["When", "Each working day"]]), "out"),
        side(card("You give", "Money you are not allowed to decide", "A discount, a refund, money back, a price that is not in the approved price file, or a booking advance that is not in the payment policy.", ["Write the request. Do not promise it."], [], [["To", "Customer Experience Manager"], ["When", "The same day"]]), "out")
      ],
      workflow: [
        card("At the start of the shift", "Open your list, not the inbox", "The assigner has already written your name, the type, and the priority.", ["Hot leads first, oldest first.", "Then warm. Then cold.", "Do not start a cold lead while a hot or warm lead is waiting.", "If a service provider is on your name, return it the same day."]),
        card("When you are the named deputy", "Assign, and do not keep the leads", "Only on the dates written before the Customer Experience Manager's leave.", ["Mark the type, the priority, and one owner.", "The next owner is the person on shift with the fewest hot leads, then the fewest open leads.", "Move a service provider lead to the provider inbox the same day.", "Write that the manager is on leave.", "Do not approve a price, a booking advance, a discount, or a refund. If one cannot wait, send it to the Head of Operations the same day.", "When the manager is back, stop assigning."]),
        card("If the lead is a future customer", "Write the file and keep the follow-up", "They are not booking today.", ["Write the full details in the admin panel.", "Set the next call.", "Call on that date. After the call, or if they do not answer, send the call brief by message.", "Continue until they become a customer and book, they decline, or you close the lead with a reason and a status.", "The aim is at least 50 bookings out of every 100 eligible future customers assigned to you in the period."]),
        card("If the lead is a customer", "Write the file, give the price, and book only if they agree", "The full details go in the admin panel the same day.", ["Write name, phone, source, exact job, full address and area, and the appliance or property.", "Give the price from the approved price file.", "If they do not agree, write why, set the next time, and send the message.", "If they agree, take the booking advance in the company payment policy and write the amount.", "Find a provider in the written order and get their yes.", "Create the booking in the admin panel.", "Send the call brief so the last message is from Panun Kaergar."]),
        card("Before the work starts", "Confirm both sides and the charge", "Do this on a call before the visit, early enough to change the plan if one side cannot come.", ["Call the customer. Confirm they will be at the address at the booked time.", "Call the provider. Confirm they will be at the address at the booked time.", "Tell both the same service charge.", "Write all three answers in the admin panel.", "If either side may miss the visit, change the time or the provider and tell both sides the same day.", "Send each of them a message with the time, the address, and the service charge."]),
        card("After the work", "Settle both sides and write it in the admin panel", "The service is not done until it meets the written standard for that job.", ["Write what was done and anything the standard still requires.", "Settle what the customer owes. Write it in the admin panel.", "Settle the provider side under the company payment steps. Write it in the admin panel.", "Call the customer and write what they said about the work, the charge, and the payment.", "Send the call brief.", "Mark the booking finished only when the standard is met, both sides are settled, and the admin panel shows both."]),
        card("Every working day, and before you leave", "Stay current, then hand over", "A lead with your name that you have not looked at today is a lead you are not handling.", ["Open every lead and booking with your name. Hot first, then warm, then cold.", "Do the follow-ups that are due, in that same order.", "Check that the last message on each open lead is from Panun Kaergar. If it is not, send the next step today.", "Leave the handover: type, priority, status, work done, work still open, next action, and next time, for every open lead and booking."])
      ],
      reporting: [
        { period: "Each working day", kicker: "Admin panel", title: "The lead and the booking are current", when: "Before you leave.", lead: "The Customer Experience Manager can see the type, the last message, and the next time without calling you.", body: ["Update every lead and booking assigned to you."], contents: [{ title: "What the panel must show", why: "Type, details, last message from Panun Kaergar, status, next action, and next time." }], mustHave: ["Lead type", "Source", "Full details for that type", "Last message from Panun Kaergar", "Next time"], submit: [{ to: "Customer Experience Manager", why: "In the admin panel." }] },
        { period: "Before the visit", kicker: "Visit", title: "Both sides confirmed", when: "Before the work starts.", lead: "Neither the customer nor the provider is left to discover a no-show at the address.", body: ["Write the three confirmations."], contents: [{ title: "The three confirmations", why: "Customer will attend, provider will attend, both know the same service charge." }], mustHave: ["Customer confirmation", "Provider confirmation", "Service charge told to both", "Message sent to both"], submit: [{ to: "The admin panel", why: "So the next person can see it if you are away." }] },
        { period: "After the work", kicker: "Money", title: "Both sides settled", when: "When the service is done, before the booking is marked finished.", lead: "Customer money and provider money are separate lines in the admin panel.", body: ["Do not mark the booking finished while either line is open."], contents: [{ title: "Both lines", why: "What the customer paid, and what was settled with the provider." }], mustHave: ["Customer payment", "Provider settlement", "Service checked against the written standard", "Call after the service"], submit: [{ to: "Customer Experience Manager and Accounts", why: "In the admin panel, the same day." }] }
      ],
      standards: [
        { title: "You work your list in order", lead: "Hot, then warm, then cold. Oldest first inside the same band.", body: ["You do not open the inbox.", "You do not start a cold lead while a hot or warm lead with your name is waiting."] },
        { title: "A service provider lead leaves your list the same day", lead: "You return it. You do not onboard it.", body: ["Provider Support briefs the provider after an owner is assigned on the provider list."] },
        { title: "As deputy, you only assign", lead: "For the dates written in the admin panel.", body: ["Each lead shows the priority, the owner, and that the manager is on leave.", "You do not approve a price, a booking advance, a discount, or a refund."] },
        { title: "A customer or future customer is complete in the admin panel", lead: "The next person can continue without calling you.", body: ["Name, phone, source, exact job, full address and area, appliance or property, status, comment, next action, and next time.", "The price, once given, is the price from the approved price file.", "The call recording is linked when a recording is required."] },
        { title: "The last message is from Panun Kaergar", lead: "On every lead you handle.", body: ["After a call, the call brief is sent as a message.", "After a missed call, the message is still sent.", "If they write after you, you reply the same day."] },
        { title: "A booking is created only after the price is accepted", lead: "The advance matches the company payment policy. The provider has said yes.", body: ["The booking in the admin panel shows customer, address, job, price, advance, provider, date, and time."] },
        { title: "Neither side is left to a no-show", lead: "Before the work starts, both have confirmed.", body: ["The customer will be at the address.", "The provider will be at the address.", "Both have been told the same service charge.", "All three are written in the admin panel, and both have a message."] },
        { title: "The booking is finished only when both sides are settled", lead: "The service meets the written standard for that job.", body: ["The customer payment is written in the admin panel.", "The provider settlement is written in the admin panel.", "If the customer paid the provider in cash, the note names the customer, the booking, the amount, the provider, the date, and who is holding the cash.", "The call after the service is written.", "Until then the booking stays open."] },
        { title: "You look at your own leads and bookings every working day", lead: "What changed is written before you leave.", body: ["A follow-up due today is done, or the missed call is followed by a message and a new time.", "The handover names every open lead and booking."] }
      ],
      kpis: cxeKpis,
      escalations: [
        { title: "The lead is not a customer, a future customer, or a service provider", lead: "Do not invent a type.", body: ["Write what they asked for. Send it to the assigner the same day."], points: [{ title: "You send it to", why: "The Customer Experience Manager, or the Head of Operations if you are the deputy and it cannot wait." }] },
        { title: "The price or the booking advance is not in the written policy", lead: "Do not choose a number.", body: ["Stop. Tell the customer you will confirm the amount."], points: [{ title: "You send it to", why: "The Customer Experience Manager. If you are the deputy and it cannot wait, the Head of Operations, the same day." }] },
        { title: "No provider can attend", lead: "Tell the customer the same day.", body: ["Keep the booking open. Ask Provider Support to keep looking. Send the customer a message with the next time."], points: [{ title: "You tell", why: "Provider Support and the customer." }] },
        { title: "One side may miss the visit", lead: "Do not hope they will turn up.", body: ["Change the time or the provider. Tell both sides. Write it in the admin panel."], points: [{ title: "You tell", why: "The customer and the provider, the same day." }] },
        { title: "The written way was broken", lead: "Correction comes before a warning. A warning comes before training. Training comes before closer watching. Closer watching comes before a formal correction plan.", body: ["This is a miss. You hid a lead, skipping the message after a call, leaving a no-show unwritten, or marking both payments settled when they are not, can go straight to disciplinary action under company policy, the employment terms, and the law."] }
      ]
    }
  });
  addRole({
    id: "cxm",
    name: "Customer Experience Manager",
    reportsTo: "Head of Operations",
    hero: "role-cxm.png",
    result: "Every customer and future-customer lead has one owner and a written priority, the team works that order, and each week you can show two counts from the records: eligible leads that became bookings, and those same bookings later finished to the written standard with the customer payment and the provider settlement both in the admin panel.",
    what: "You manage the Customer Experience Executives. You are the assigner for the customer inbox. You mark the type, the priority, and one owner. You do not ask the team who wants the lead. You check that they worked hot before warm before cold, handle what they escalate, train the gaps, and report from the records. You may join the customer work when the load, a shortage, or a serious complaint requires it. That does not replace assigning, and it does not replace managing the team.",
    why: "The executive’s question is: did I do the follow-up? Your question is: did my team do the follow-ups, did I see the failures in the records, and did I correct them? If you only do their job, nobody is managing.",
    how: "Check leads, tickets, recordings, bookings, complaints, and reports. Do not close a check because someone said it was done. Open the record. Where the same problem repeats, find whether it is the person, the training, an unclear step, the workload, the system, communication, or missing providers. Do not repeat the same correction if the process itself is wrong.",
    lanes: [
      { kicker: "Part 1", title: "The inbox and the team", work: "Mark each new lead: customer, future customer, or service provider. Move a service provider lead to the provider inbox the same day. For a customer or a future customer, write hot, warm, or cold, and one owner. The next owner is the person on shift with the fewest hot leads, then the fewest open leads. If someone is away, move their hot leads the same day to someone who is in. Before you go on leave, name one Customer Experience Executive as deputy, with the dates, in the admin panel.", result: "No hot lead sits in the inbox, and nobody picks their own lead.", who: "You. If an executive job is empty, you do that work and write that you are doing it today. You do not become the person who also picks the best leads." },
      { kicker: "Part 2", title: "The numbers and the complaints", work: "Watch leads, sources, areas, services, conversion, cancellations, follow-ups, future customers, and provider-related booking problems. On the weekly page, put eligible leads that became bookings next to those same bookings that were later finished to the written standard, with both sides of the money in the admin panel. Handle escalated complaints. Join the customer when the case needs you.", result: "A strong booking count with unfinished or unsettled jobs is sent back the same week, and complaints are not left pending.", who: "You. The executive handles the routine complaint and still owns the customer. You handle what is outside their authority." },
      { kicker: "Part 3", title: "Training and the report", work: "Train the gap, then watch the work afterwards to see if it stuck. Report from recorded data: leads, the two booking counts, future customers, area demand, service demand, and the four money lines. The booking advance, exactly as the written payment policy says. The customer payment, as written in the admin panel. The provider settlement, as written in the admin panel. Cash still open, with who holds it. Do not add a fifth line called revenue.", result: "Head of Operations gets a true report on time, and a repeated problem has a cause and an action.", who: "You." }
    ],
    acting: "You may join Customer Support when the workload, a staff shortage, or a serious complaint requires it. Write that you did. Do not let that become your main job, and do not leave the inbox without an assigner. Before leave, the deputy's name and dates are in the admin panel. When you return, you read the hot leads from those days and you take the queue back.",
    owns: [
      "The customer inbox: type, priority, and one owner on every customer and future-customer lead",
      "The deputy named before your leave",
      "Whether the team follows the customer process",
      "Escalated and repeated complaints",
      "Training, and the check after training",
      "Lead, booking, cancellation, and future-customer performance",
      "The weekly page that shows bookings taken next to those same bookings finished and settled on both sides",
      "Area and service demand from the records",
      "Reports Head of Operations can trust"
    ],
    mustNot: [
      "Let an executive take a lead from the inbox",
      "Leave a hot lead without an owner while a cold lead is being worked",
      "Go on leave without a named deputy and dates in the admin panel",
      "Accept someone saying ‘all done’ when the record says otherwise",
      "Allow an unauthorised discount, refund, compensation, or promise",
      "Hide a cancellation, a complaint, a missed lead, or poor conversion",
      "Report a strong booking count while leaving off the bookings that were not finished, or that are missing one side of the money",
      "Mark work complete without checking it",
      "Submit a report built on assumptions",
      "Ignore a repeated error"
    ],
    good: [
      "You can show follow-ups done on time from the records, not from a conversation.",
      "A missed lead was found and assigned an owner the same day.",
      "An escalated complaint has a status and a next action.",
      "Training was followed by a check that the error stopped.",
      "The report matches the system.",
      "The weekly page shows both counts, and any booking that was taken and not finished to the written standard went back to its owner the same week.",
      "A repeated problem has a written cause, not another identical warning."
    ],
    bad: [
      "You spent the day answering leads and never looked at the team’s missed follow-ups.",
      "The weekly story hides cancellations.",
      "The booking count looks strong, and the finished-and-settled count is missing or much weaker, and you still called the week a success.",
      "A discount was promised and you left it.",
      "Training was marked done after one session and the same error returned.",
      "The report uses estimates.",
      "The same executive misses follow-ups and nothing is written."
    ],
    records: [
      "Team assignment and coverage",
      "Notes from the leads you opened, tickets, recordings, bookings, and complaints",
      "Escalated complaint file",
      "Training given, and the later check",
      "Lead, booking, future-customer, area, and service reports",
      "The weekly two counts, and the list of bookings sent back because they were not finished and settled",
      "Corrective actions with owner and deadline"
    ],
    rules: [
      "You assign every customer and future-customer lead. Hot, warm, or cold. One owner. The person on shift with the fewest hot leads, then the fewest open leads. You do not ask who wants it.",
      "A service provider lead goes to the provider inbox the same day. It is not assigned to a Customer Experience Executive.",
      "Before leave, name one deputy and the dates in the admin panel. When you return, read the hot leads from those days and take the queue back.",
      "Each day: no hot lead is in the inbox without an owner. Nobody worked a cold lead while a hot lead assigned to them was waiting. If someone is away, their hot leads move the same day.",
      "This is a miss. You did not check the Customer Experience team.",
      "This is a miss. You knew the same mistake was happening again, and you did not correct it.",
      "This is a miss. You ignored missed leads or repeated missed follow-ups.",
      "This is a miss. You did not act on a serious customer complaint.",
      "This is a miss. You did not arrange required training after a training gap is identified.",
      "This is a miss. You gave a report that was wrong or misleading, or you changed the team's numbers.",
      "This is a miss. You hid cancellations, complaints, missed leads, poor conversion, or repeated employee errors.",
      "This is a miss. The weekly page shows eligible leads that became bookings, and it does not show those same bookings later finished to the written standard with both sides of the money in the admin panel. A first count that is excellent or acceptable, next to a missing or weak second count, is sent back the same week. You do not add another seat. The Customer Experience Executive stays the owner of the customer.",
      "This is a miss. You marked work finished without checking the record.",
      "This is a miss. You did not monitor required recordings, customer records, or ticket and status updates.",
      "This is a miss. You allowed an unauthorised discount, refund, compensation, or commitment.",
      "This is a miss. You did not act when an employee repeatedly violates the procedure.",
      "This is a miss. You gave an instruction that contradicts company policy.",
      "This is a miss. You shared confidential customer, provider, or company information without authorisation.",
      "This is a miss. You did not submit a required report on time, or submitting a report based on assumptions instead of recorded data.",
      "This is a miss. You ignored a management instruction on customer operations.",
      "This is a miss. You did not support the team during a critical customer-service situation.",
      "This is a miss. You repeatedly allowed the same operational problem without investigation or corrective action.",
      "The executive’s question is: did I complete the follow-up? Your question is: did I make sure the team completed the follow-ups, did I identify the failures, and did I correct them?",
      "Action, by seriousness and frequency: correction, then warning, then training or coaching, then performance monitoring, then a performance plan, then further disciplinary action. The plan states the gap, the required standard, the improvement target, the corrective actions, the review period, the monitoring method, and the consequence of failure.",
      "Serious misconduct, deliberate data manipulation, concealment, or fraud can go straight to disciplinary action, including termination, subject to company policy, employment terms, and applicable law."
    ],
    kpis: cxmKpis,
    detailed: {
      copy: {
        heroKicker: "What this job is",
        definition: "You run the customer system. You assign the customer inbox. Customer Experience Executives work only the leads with their name, hot before warm before cold. You make sure they follow that order and the rest of the written process, you measure them from the records, you correct the gaps, you handle what they cannot, and you tell Head of Operations what is actually happening. Before leave, you name one deputy. Joining a busy shift does not turn you into another executive, and it does not replace the inbox.",
        owns: "You own the team’s result. You do not own a private way of handling leads.",
        lanes: "Three parts: the team, the customer problems, and the report. If you only write the report, you are describing the work and you are not running it. If you only answer the phone, nobody is checking the team.",
        responsibilities: "Click a row to open the full card.",
        handoffs: "You receive records and escalations. You give instructions, training, and reports. Chat is not a report.",
        workflow: "Each day, see whether the team did yesterday’s work. Each week, read the numbers and the repeated problems. Train, then watch whether the training worked.",
        reporting: "Lead, booking, future customer, and demand packs, plus the four money lines: the booking advance as the payment policy says, the customer payment in the admin panel, the provider settlement in the admin panel, and cash still open with who holds it. Finance writes the same four lines. Do not add a line called revenue. The weekly page always shows two counts together: eligible leads that became bookings, and those same bookings later finished with both sides of the money in the admin panel. If a figure is not in the records, it does not go in the report.",
        standards: "The bar for a manager, not for an executive.",
        kpis: "These count whether you made the team succeed. The executive is counted on whether they did their own follow-up.",
        escalations: "What you send to Head of Operations, and what you must not decide alone."
      },
      hub: { icon: "groups", line: "You assign the customer inbox. Executives work the leads with their name. You check the order, and you report the truth." },
      definition: {
        what: [
          { title: "You hold the team to the written process", why: "Lead, follow-up, booking, provider coordination, price, complaint, cancellation, payment, post-service call, recording, ticket, and handover." },
          { title: "You use the records, not the story", why: "Leads, tickets, recordings, bookings, complaints, and reports. Someone saying it is done can start a check. It cannot close the check." },
          { title: "You turn activity into a report Head of Operations can use", why: "Leads, the two booking counts, cancellations, future customers, area demand, service demand, and the four money lines from the records. Finance uses the same four lines." }
        ],
        why: [
          { title: "An executive can be busy and still miss the system", why: "Your job is to see the miss and correct it." },
          { title: "The same correction, repeated, means you have not found the cause", why: "Person, training, unclear step, workload, system, communication, or no provider. Name which one." },
          { title: "A report that hides bad news trains the company to be blind", why: "Cancellations, missed leads, poor conversion, and bookings that were taken but not finished and settled stay in the report." }
        ]
      },
      glossary: sharedGloss.concat([
        { id: "cxm", term: "Customer Experience Manager", aliases: ["Customer Experience Manager"], meaning: "This job. You assign customer and future-customer leads. You are accountable for the team following the written order and the customer process. The executive is accountable for the leads that have their name." },
        { id: "two-counts", term: "Two counts", aliases: ["two counts", "bookings taken and bookings finished"], meaning: "The two numbers you put on the same weekly page. The first is eligible leads that became bookings. The second is those same bookings that were later finished to the written standard, with the customer payment and the provider settlement both written in the admin panel. You read them together. A first count that is excellent or acceptable, next to a second count that is missing or weak, is a miss for that week. You send each unfinished or unsettled booking back to the Customer Experience Executive who owns it. You do not add another seat. The Customer Experience Executive stays the owner of the customer." },
        { id: "four-lines", term: "Four money lines", aliases: ["four money lines"], meaning: "The only money lines on this report, and the same four lines Finance writes. The booking advance, exactly as the written payment policy says. The customer payment, as written in the admin panel. The provider settlement, as written in the admin panel. Cash still open, with who holds it. Do not add a line called revenue. If a line has no record, leave it blank." }
      ]),
      responsibilities: [
        card("Responsibility 1", "Assign the inbox, then check the order", "You are the assigner. The team does not pick.", ["Write the type: customer, future customer, or service provider.", "Move a service provider lead to the provider inbox the same day.", "For a customer or a future customer, write hot, warm, or cold, and one owner.", "The next owner is the person on shift with the fewest hot leads, then the fewest open leads.", "If someone is away, move their hot leads the same day.", "Before leave, name one Customer Experience Executive as deputy, with the dates, in the admin panel.", "Each day, check that no hot lead is unowned, and that nobody worked a cold lead while a hot lead on their list was waiting.", "When you return from leave, read the hot leads from those days and take the queue back."], [{ title: "Accountability is yours", why: "If a hot lead sits while the team works a cold one, that miss is yours." }]),
        card("Responsibility 2", "Check the work in the records", "Lead management, follow-up, booking, provider coordination, pricing, complaints, cancellations, payment, post-service, recordings, tickets, handover, and company rules.", ["Find the gap in the lead, the ticket, the recording, the booking, or the complaint. Do not close the check on a conversation."], [{ title: "100% is still the operating standard", why: "A lower number on a check only shows how far the work has slipped. It does not lower the line for follow-ups, the record, the recording, the payment, or the provider's yes." }]),
        card("Responsibility 3", "Read conversion, loss, and demand", "Leads by source, area, and service. Valid, invalid, and unknown. Converted and not converted, with reasons. On the same page, the bookings that were later finished to the written standard, with both sides of the money in the admin panel. Cancellations and reasons. Future customers and conversion. Provider problems on bookings.", ["Eligible lead conversion target is 40 out of every 100. 40 or more is excellent. 30 to 39 is acceptable. 20 to 29 needs improvement. Below 20 is critical. Eligible future-customer conversion target is 50 out of every 100 of the people assigned or generated in the period.", "Write the second count beside the first. It is the bookings from that same set that were later finished to the written standard, with the customer payment and the provider settlement both in the admin panel.", "If the first count is excellent or acceptable and the second count is missing, that week is a miss. It is also a miss when a booking in that set is marked finished while the work, the call after the service, or either side of the money is missing, or when the visit date has passed and the booking is still not finished to that standard.", "Send each of those bookings back to the executive who owns it, the same week. That person stays the owner. Do not create a separate seat to take the booking."], [{ title: "Do not count leads that cannot be booked", why: "Invalid, duplicate, out of area, and impossible leads are excluded." }]),
        card("Responsibility 4", "Handle what the executive must not decide", "Escalated complaints, serious or repeated complaints, and any promise about a discount, refund, compensation, or offer.", ["Join the customer when the case needs a manager. Find whether the complaint came from handling, the provider, the price, communication, or the process."], [{ title: "A complaint without progress is yours", why: "Do not leave it pending." }]),
        card("Responsibility 5", "Train, then check that it stuck", "New people, the process, communication, conversion, complaints, booking and provider coordination, data entry, and a refresher when the standard slips.", ["After training, watch the work. A session that is not checked is not complete."], [{ title: "Training without a later check did not finish", why: "The standard is that trained people are monitored afterwards." }]),
        card("Responsibility 6", "Report only what the records say", "Lead performance, booking performance, future customers, high and low demand areas, high and low demand services, and the four money lines. The booking advance as the payment policy says. The customer payment in the admin panel. The provider settlement in the admin panel. Cash still open, with who holds it. Finance writes the same four lines. Do not add a line called revenue.", ["Do not estimate. Submit on time. Make the report the same shape every period so Head of Operations can compare it."], [{ title: "Areas with leads and no bookings are a finding", why: "So are areas with demand and no provider. Send that to Provider Experience, not only into a paragraph." }])
      ],
      handoffs: [
        side(card("You receive", "The team’s cases, tickets, and handovers", "This is the evidence.", ["If a required field is blank, that is a finding, not a reason to skip the person."], [], [["From", "Customer Experience Executives"], ["When", "Every working day"]]), "in"),
        side(card("You receive", "Escalations", "Complaints and promises outside the executive’s authority.", ["You act. You do not send it back without a decision or a next step."], [], [["From", "Customer Support"], ["When", "The same day"]]), "in"),
        side(card("You receive", "Provider position on a booking", "Availability, delay, replacement, or a provider complaint.", ["You and Provider Experience should be looking at the same facts."], [], [["From", "Provider Experience Manager"], ["When", "When a booking needs both teams"]]), "in"),
        side(card("You give", "A correction or a training assignment", "What failed, the standard, and what will be checked afterwards.", ["Write it. A spoken telling-off is not a record."], [], [["To", "The executive"], ["When", "When the record shows the miss"]]), "out"),
        side(card("You give", "The customer operations report", "Leads, the two booking counts, cancellations, future customers, complaints, area, service, and the four money lines from the records.", ["Name what failed. If bookings taken look strong and bookings finished do not, say so on this page. The four money lines are the booking advance as the policy says, the customer payment in the admin panel, the provider settlement in the admin panel, and cash still open with who holds it."], [], [["To", "Head of Operations"], ["When", "On the required day"]]), "out"),
        side(card("You give", "Demand the providers cannot cover", "Areas or services with leads and no capacity.", ["This is a coverage problem, not a scolding for the executive."], [], [["To", "Provider Experience Manager"], ["When", "When the pattern is visible"]]), "out")
      ],
      workflow: [
        card("Every working day", "Assign the inbox, then see whether yesterday’s work was done", "New leads first. Then follow-ups due, open complaints, bookings without a true status, and handovers.", ["No hot lead stays without an owner.", "Join the queue only when the operation needs you, and write that you did."]),
        card("Before leave, and the day you return", "The inbox still has one assigner", "Name the deputy before you go.", ["Write one Customer Experience Executive and the dates in the admin panel.", "On return, read the hot leads from the leave days and take the queue back. The deputy stops assigning."]),
        card("When a complaint is escalated", "Take it yourself", "Read the record, speak to the customer if required, decide inside your authority, or send it to Head of Operations with the facts.", ["Do not promise money the company has not approved."]),
        card("When the same error returns", "Name the cause", "Person, training, unclear step, workload, system, communication, or no provider.", ["Set an action, an owner, and a date. Do not issue the same correction again and call it management."]),
        card("After training", "Watch the work", "The session is not the end. Check the next cases for the error you trained.", ["If the error remains, the training did not stick."]),
        card("On the report day", "Write both counts from the system", "Leads, eligible leads that became bookings, those same bookings later finished with both sides of the money in the admin panel, cancellations, future customers, complaints, area, and service.", ["If you cannot trace a number, leave it out and say it is missing. Do not invent it.", "If the booking count is excellent or acceptable and the finished count is missing or weak, send those bookings back to their owners the same week."])
      ],
      reporting: [
        { period: "Weekly", kicker: "CXM-R1", title: "Customer operations report", when: "The day Head of Operations reviews the week.", lead: "Lead, booking, and future-customer performance from the records, including what failed. Bookings taken and bookings finished sit on the same page.", body: ["Do not estimate. A first count that is excellent or acceptable, with a second count missing or weak, is a miss. Send those bookings back the same week."], contents: [{ title: "Lead performance", why: "Total leads, by area, by service, by source, valid or invalid or unknown, converted, unconverted, and the conversion percentage." }, { title: "Two counts", why: "First, eligible leads that became bookings, with the written band. Second, those same bookings that were later finished to the written standard, with the customer payment and the provider settlement both in the admin panel. Also list the bookings in that set that are still open, past the visit date, or marked finished while one side of the money is missing." }, { title: "Booking performance", why: "Total bookings, by service, by area, cancelled bookings, cancellation percentage, cancellation reasons, completed services, and pending bookings." }, { title: "Future customers", why: "Received or assigned, contacted, converted, conversion percentage, and employee-wise conversion." }], mustHave: ["Period", "Leads by source, area, and service", "Valid, invalid, and unknown", "Eligible leads that became bookings", "Those bookings finished with both sides of the money", "Bookings sent back the same week", "Cancellations and reasons", "Follow-up misses", "Open complaints", "Future-customer conversion by employee", "Booking advance as the payment policy says", "Customer payment in the admin panel", "Provider settlement in the admin panel", "Cash still open, and who holds it"], submit: [{ to: "Head of Operations", why: "Written. Traceable to the system." }] },
        { period: "Monthly", kicker: "CXM-R2", title: "Demand and the four money lines", when: "With the monthly close, and whenever management asks.", lead: "Area demand, service demand, and the four money lines, from the payment policy and the admin panel.", body: ["Also provide, when asked: employee performance, lead conversion, booking performance, cancellation analysis, complaints, future-customer conversion, process violations, training requirements, and operational problems."], contents: [{ title: "Area demand", why: "High-demand areas, low-demand areas, areas with the most leads, areas with the most bookings, areas with leads but low conversion, and areas with demand but not enough providers." }, { title: "Service demand", why: "High-demand services, low-demand services, services with the most leads, services with the most bookings, services with poor conversion, and services that need more provider capacity." }, { title: "Four money lines", why: "The booking advance, exactly as the written payment policy says. The customer payment, as written in the admin panel. The provider settlement, as written in the admin panel. Cash still open, with who holds it. This month against the previous month uses these same four lines. Do not add a line called revenue. Finance writes the same four lines." }], mustHave: ["Month", "Current month against previous month", "Booking advance as the payment policy says", "Customer payment in the admin panel", "Provider settlement in the admin panel", "Cash still open, and who holds it", "Areas and services that need action"], submit: [{ to: "Head of Operations", why: "For the management report." }] }
      ],
      standards: [
        { title: "The customer inbox has an owner on every lead", lead: "Type, priority, and one name, the same day.", body: ["Hot before warm before cold.", "A service provider lead is in the provider inbox the same day.", "A deputy is named, with dates, before your leave.", "Until that name exists, the Head of Operations keeps the inbox closed."] },
        { title: "The team follows the written steps", lead: "The Customer Experience team follows the written steps, the rules, and the company policies.", body: ["You answer for the team, not only for the leads that have your own name."] },
        { title: "Monitoring", lead: "Team performance is monitored through actual data.", body: ["Leads, bookings, tickets, recordings, follow-ups, and reports. Someone saying it is done is not the check."] },
        { title: "Accountability", lead: "Every team member has clear responsibilities and a measurable expectation.", body: ["No important customer work without an owner."] },
        { title: "Follow-up control", lead: "Required standard: 100% follow-ups done on time as the team operating standard.", body: ["You make sure required customer follow-ups are not being missed."] },
        { title: "Data accuracy", lead: "Required standard: 100% accuracy for critical operational information.", body: ["Team reports and records reflect actual customer and booking activity."] },
        { title: "Reporting", lead: "Accurate, complete, based on recorded data, on time, easy to understand, and consistent from period to period.", body: ["Assumptions do not go in.", "The weekly page shows eligible leads that became bookings next to those same bookings later finished to the written standard, with both sides of the money in the admin panel.", "A strong first count with a missing or weak second count is sent back the same week."] },
        { title: "Complaint handling", lead: "An escalated complaint gets timely managerial attention.", body: ["It is not ignored or unnecessarily delayed."] },
        { title: "Training", lead: "An identified training gap produces training, correction, or coaching.", body: ["Training is followed by monitoring to confirm the problem was actually corrected."] },
        { title: "Process improvement", lead: "A repeated problem is investigated for its cause.", body: ["Employee performance, lack of training, an unclear procedure, workload, system limitations, communication gaps, or provider availability.", "Do not repeat the same correction when the process itself needs improvement."] },
        { title: "Operational support", lead: "When the workload or the staffing requires it, you join the customer work.", body: ["Customer service does not suffer because you stayed only in the manager’s chair."] },
        { title: "Confidentiality", lead: "Customer, provider, employee, financial, and company information is shared only with authorised people.", body: ["There is no fixed number of future customers. The target changes with the number assigned or generated."] }
      ],
      kpis: cxmKpis,
      escalations: [
        { title: "A complaint or promise is outside your authority", lead: "Send facts, what you already did, status, and the decision you need.", body: ["Do not invent a refund or a policy."], points: [{ title: "You send to", why: "Head of Operations." }] },
        { title: "Demand has no provider", lead: "This is not fixed by pushing the executive harder.", body: ["Name the area or service, the leads, and the missing capacity."], points: [{ title: "You send to", why: "Provider Experience Manager, and Head of Operations if it is blocking bookings." }] },
        { title: "The same failure continues after correction and training", lead: "Start the documented path: warning, monitoring, performance plan.", body: ["Serious falsification does not wait for that path."], points: [{ title: "You send to", why: "Head of Operations." }] }
      ]
    }
  });

  addRole({
    id: "poe",
    name: "Provider Support",
    reportsTo: "Provider Experience Manager",
    hero: "role-poe.png",
    result: "You onboard the provider leads that have your name, onboard-now before the ones to write down for later, and a provider is active only after onboarding is complete.",
    what: "You do not take a provider lead from the inbox. The Provider Experience Manager, or the deputy named while that manager is on leave, writes your name on the lead. You work onboard-now leads before write-down-later leads. Onboard now means they work in an area and a category Panun Kaergar already serves. Write down, do later means they do not. You still record a later lead the same day and send the message. You do not onboard it while an onboard-now lead is waiting. From there you run documents, agreement, briefing, training, the admin panel, certificate or ID, and ongoing support. You coordinate with the Customer Experience Executive when a booking needs a provider. You never treat a market price as Panun Kaergar’s approved price.",
    why: "If a provider is marked active before the file is complete, Customer Support will promise a job we cannot stand behind. If provider leads are missed, coverage shrinks and bookings fail.",
    how: "Open only the provider leads that have your name. Onboard-now first, oldest first. Then write-down-later: record them, send the message, and stop. For an onboard-now lead: contact, information, documents, verify, agreement, briefing, training, admin panel, certificate or ID, activate, then support. Tell them: Thank you for contacting Panun Kaergar. Provider Support will complete your details. Please wait before you take a job or tell a customer a price. A provider is not fully active until the required onboarding is done. When a booking needs a provider: check, confirm availability, share the job, confirm, update the status, tell the Customer Experience Executive, and watch it until completion. If you are the named deputy, you assign and you do not keep the leads.",
    lanes: [
      { kicker: "Part 1", title: "Registration and the file", work: "Contact new providers. Collect information, documents, and the signed agreement. Explain the process, the rules, and what they must do before, during, and after a job. Update the admin panel. Keep the offline backup.", result: "No provider is active with a missing document, agreement, or briefing.", who: "You." },
      { kicker: "Part 2", title: "Leads, follow-ups, and live support", work: "Work the provider leads that have your name. Onboard now before write down, do later. Record a later lead the same day, send the message, and do not onboard it while an onboard-now lead is waiting. Follow an onboard-now provider until activation or a proper close. Answer operational questions from the approved process. Coordinate bookings with the Customer Experience Executive.", result: "No assigned provider lead is unattended, a later lead does not jump the queue, and a booking is not treated as confirmed before the provider confirms.", who: "You. The Customer Experience Executive owns the customer. You own the provider side of the same booking. You do not open the inbox." },
      { kicker: "Part 3", title: "Training, pay records, and market prices", work: "Arrange required training and write attendance. Keep payment and account records and flag discrepancies. Note market prices for services, labour, parts, and materials, clearly marked as market information.", result: "Training status is true, payment issues are visible, and market prices are not company prices.", who: "You. You cannot change Panun Kaergar’s approved prices." }
    ],
    acting: "If this job is empty, the Provider Experience Manager does the work and writes that they are doing it today. You do not activate a provider early to clear a queue. If you are the named deputy while the Provider Experience Manager is on leave, you assign for those dates and you do not keep the leads. You do not change a price or a provider status while you are the deputy. If one of those cannot wait, send it to the Head of Operations the same day.",
    owns: [
      "The provider leads that have your name, onboard now before write down, do later",
      "Provider registration, documents, agreement, and panel record",
      "Booking coordination until the provider is truly confirmed",
      "Training records and certificates or ID that match the file",
      "Payment-record discrepancies",
      "Market-price notes, kept separate from approved prices"
    ],
    mustNot: [
      "Take a provider lead from the inbox",
      "Onboard a write-down-later lead while an onboard-now lead with your name is waiting",
      "Keep provider leads for yourself while you are the deputy, unless the written assignment rule gave you that lead",
      "Activate a provider before mandatory onboarding is done",
      "Confirm availability that was not actually confirmed",
      "Change or promise company pricing",
      "Present a market price as an approved price",
      "Mark training complete when it was not",
      "Hide a provider complaint, a payment problem, or a conflicting record"
    ],
    good: [
      "Every new active provider has documents, agreement, briefing, and training on file.",
      "Online and offline records match.",
      "Every provider lead has a status and a next action.",
      "Customer Support received a real confirmation, or a clear no.",
      "A payment discrepancy is written and escalated.",
      "A market price is labelled as market information."
    ],
    bad: [
      "A provider is active because a booking was urgent.",
      "The panel says one area and the paper file says another.",
      "A provider lead was closed with no reason.",
      "Customer Support was told yes when the provider had not answered.",
      "Training is ticked and the provider never attended.",
      "You quoted a market rate as our price."
    ],
    records: [
      "Registration, documents, agreement, verification",
      "Services, areas, availability, contact, payment account",
      "Training, certificate, and ID",
      "Lead status, follow-up date, and remarks",
      "Booking coordination and confirmation",
      "Payment discrepancies",
      "Market-price notes, marked as market information"
    ],
    rules: [
      "Work only the provider leads that have your name. Onboard now before write down, do later. Do not open the inbox.",
      "A write-down-later lead is recorded the same day and gets a message. It is not onboarded while an onboard-now lead is waiting.",
      "If you are the named deputy, assign for the written dates only. Write the provider priority, the owner, and that the manager is on leave. Do not keep the lead unless the assignment rule gives it to you.",
      "This is a miss. You missed or ignored a provider lead, or failing to complete a required provider follow-up.",
      "This is a miss. You registered a provider without the required information, or activating a provider before mandatory onboarding is complete.",
      "This is a miss. You did not collect required documents or agreements, or losing, misplacing, or improperly storing provider records.",
      "This is a miss. You entered false or incorrect provider information, or failing to update information that has changed.",
      "This is a miss. You kept conflicting information between records without correction.",
      "This is a miss. You marked a provider as trained when the required training has not been completed.",
      "This is a miss. You did not communicate an important company policy or update to providers.",
      "This is a miss. You confirmed availability without an actual confirmation, or giving Customer Support incorrect booking information.",
      "This is a miss. You did not coordinate with Customer Support when a booking needs a provider.",
      "This is a miss. You hid a provider complaint, issue, or payment problem, or failing to follow up a payment or account issue.",
      "This is a miss. You issued a certificate or ID card with incorrect information through negligence.",
      "This is a miss. You shared provider documents or confidential information without authorisation.",
      "This is a miss. You presented a market price as an approved Panun Kaergar price, or independently changing or promising company pricing.",
      "This is a miss. You manipulated provider data, lead status, training status, or payment records.",
      "This is a miss. You falsified a document, a record, or a follow-up.",
      "This is a miss. You repeatedly did not follow the Provider Support process.",
      "Action: correction, then warning, then training or coaching, then performance monitoring, then a performance plan, then further disciplinary action.",
      "Serious misconduct, deliberate falsification, concealment, fraud, or misuse of provider information can go straight to disciplinary action, including termination, subject to company policy, employment terms, and applicable law."
    ],
    kpis: poeKpis,
    detailed: {
      copy: {
        heroKicker: "What this job is",
        definition: "You are Provider Support. You work the provider leads that have your name. Onboard now comes before write down, do later. You take an onboard-now provider from the brief to a complete file, and you keep supporting them after they are active. A later lead is recorded and messaged the same day, and left until the onboard-now list is clear. The Customer Experience Executive does not hunt documents. You do not talk the customer into a booking. You do not set the price. If you are the named deputy, you assign and you do not keep the leads.",
        owns: "The provider file, the provider lead, the confirmation, and the flag when pay or documents are wrong.",
        lanes: "Three parts: the file, the live work, and the records that finance and training depend on.",
        responsibilities: "Click a row to open the full card.",
        handoffs: "Customer Support sends a booking need. You send a real yes or a real no. The panel and the paper file must say the same thing.",
        workflow: "New provider, provider lead, booking coordination, an issue, an update, a data change, and training. Use the path that matches the trigger.",
        reporting: "You write the file and the exception. The Provider Experience Manager writes the management report from those files.",
        standards: "Onboarding, documents, follow-up, confirmation, and payment records stay at 100%.",
        kpis: "How this job is counted. The list in the source stopped mid-title at Provider Meet, so that measure is not included until it is finished.",
        escalations: "What you do not decide."
      },
      hub: { icon: "engineering", line: "You work the provider leads that have your name. Onboard now before later. Then you make that provider real on file." },
      definition: {
        what: [
          { title: "You complete onboarding before activation", why: "Information, documents, verification, agreement, briefing, training, panel update, then certificate or ID." },
          { title: "You keep the provider able to take work", why: "Availability, job details, issues, cancellations, evidence, and payment questions, answered from the approved process." },
          { title: "You tell Customer Support the truth about availability", why: "A confirmation is a recorded yes from the provider, not a guess that they will probably go." }
        ],
        why: [
          { title: "An incomplete provider becomes a failed booking", why: "Activation is a gate, not a mood." },
          { title: "Two different files become a fight", why: "Online and offline must match, and a change updates both." },
          { title: "A market price said out loud becomes a promise", why: "Label it as market information. Approved prices stay with the company." }
        ]
      },
      glossary: sharedGloss.concat([
        { id: "market-price", term: "Market price", aliases: ["market price", "market prices", "market information"], meaning: "What the market currently charges for a service, labour, part, product, or material, taken from a current source and written down. It is not Panun Kaergar’s approved price. Provider Support cannot change the approved price." },
        { id: "active-provider", term: "Active provider", aliases: ["active provider", "fully active"], meaning: "A provider who has finished the required registration, documents, agreement, briefing, and training, and whose panel record is complete. Until then they are not treated as fully active." },
        { id: "poe", term: "Provider Support", aliases: ["Provider Support", "Provider Support Team"], meaning: "This job. You handle the provider leads that have your name, onboard now before write down, do later. You do not take leads from the inbox. The Provider Experience Manager is your team lead, and the assigner, unless you are the named deputy for their leave." }
      ]),
      responsibilities: [
        card("Responsibility 1", "Register and onboard before you activate", "Contact, information, documents, agreement, process briefing, how bookings work, and what the provider must do before, during, and after a job.", ["Update the full record in the admin panel. Do not treat them as active early."], [{ title: "The gate is the file", why: "A busy day is not a reason to skip it." }]),
        card("Responsibility 2", "Keep documents where they can be found", "Registration, documents, agreement, verification, services, areas, availability, contact, payment account, training, certificate and ID.", ["Online company records and the offline backup. Organised so someone else can retrieve them."], [{ title: "Conflicting records get corrected", why: "Two versions is a violation until one is fixed." }]),
        card("Responsibility 3", "Run the provider leads that have your name", "Onboard now first. Write down, do later only after those are moving.", ["Do not open the inbox.", "Onboard now: they are in an area and a category Panun Kaergar already serves. Brief them, then take them to activation or a real close.", "Tell them: Thank you for contacting Panun Kaergar. Provider Support will complete your details. Please wait before you take a job or tell a customer a price.", "Write down, do later: record the name, phone, work, and area the same day, send that message, and do not start onboarding while an onboard-now lead is waiting.", "Someone who stops answering follows the approved attempt process. Do not close without a valid reason.", "If you are the named deputy, assign for the written dates. The next owner is the person on shift with the fewest onboard-now leads, then the fewest open leads. Do not keep the lead unless that rule gives it to you."], [{ title: "No response is not a close", why: "Write the attempt and the next time. The last message is still from Panun Kaergar." }]),
        card("Responsibility 4", "Support the provider and coordinate the booking", "Lead handling, availability, requirements, customer coordination, evidence, payment steps, cancellations, complaints, and completion.", ["For a booking: check the provider, confirm availability, share the job details, confirm, update status, tell Customer Support, watch until completion. Issues outside your authority are escalated."], [{ title: "Customer Support and you must hold the same facts", why: "A gap here becomes a customer complaint." }]),
        card("Responsibility 5", "Training, ID, and payment records", "Arrange onboarding, process, category, policy, refresher, and partner training where required. Record who attended and who is still pending.", ["Certificates and ID must match the approved record. Check account status and pending payments. Write any discrepancy and send it to the right person."], [{ title: "Do not tick training that did not happen", why: "A false training record is a violation." }]),
        card("Responsibility 6", "Update data, and keep market prices in their own box", "Name, contact, category, expertise, area, distance, availability, documents, agreement, account, training, lead status, and payment information change in the panel when they change in life.", ["Collect market prices from current sources. Share them as market information. Do not change company prices yourself."], [{ title: "Tell providers when the company changes", why: "Policy, services, areas, pricing, process, and training. Record important updates." }])
      ],
      handoffs: [
        side(card("You receive", "A provider lead with your name", "The assigner has written onboard now, or write down, do later.", ["Work onboard now first. Do not take one from the inbox."], [], [["From", "Provider Experience Manager, or the deputy while that manager is on leave"], ["When", "When your name is written"]]), "in"),
        side(card("You receive", "A booking that needs a provider", "Area, service, time, and job details from Customer Support.", ["You confirm or you say no. You do not leave it hanging."], [], [["From", "Customer Support"], ["When", "Before the customer is told a provider is assigned"]]), "in"),
        side(card("You give", "A real confirmation or a real no", "Availability, and the job details the provider received.", ["Customer Support must not invent the yes."], [], [["To", "Customer Support"], ["When", "As soon as the provider has answered"]]), "out"),
        side(card("You give", "A complete provider file", "Documents, agreement, training, area, and status.", ["The manager audits this file."], [], [["To", "Provider Experience Manager"], ["When", "Before activation, and whenever it changes"]]), "out"),
        side(card("You give", "A payment discrepancy", "What the record says, what is missing, and who should see it.", ["Do not hide it."], [], [["To", "Provider Experience Manager and Accounts as assigned"], ["When", "When you find it"]]), "out")
      ],
      workflow: [
        card("New provider", "Finish the gate, then activate", "Lead, contact, information, documents, verify, agreement, briefing, training, admin panel, certificate or ID, activate, then ongoing support.", ["Stop before activate if a required step is open."]),
        card("Provider lead", "Onboard now first", "Open only the leads with your name.", ["Onboard now: brief them, then registration, onboarding, activation, or close with a reason.", "Write down, do later: record and message the same day, then leave them until onboard-now leads are moving.", "Do not close because they went quiet once."]),
        card("When you are the named deputy", "Assign, and do not keep the leads", "Only on the dates written before the Provider Experience Manager's leave.", ["Mark onboard now or write down, do later, and one owner.", "The next owner is the person on shift with the fewest onboard-now leads, then the fewest open leads.", "Write that the manager is on leave.", "When the manager is back, stop assigning."]),
        card("Booking", "Confirm before anyone tells the customer yes", "Requirement received, check provider, confirm availability, share job details, confirm, update status, tell Customer Support, monitor until completion.", ["A guess is not a confirmation."]),
        card("Issue", "Own it or escalate it", "Record, understand, guide, resolve if it is inside your authority, otherwise escalate, follow up, update, close.", ["An issue with no owner is still open."]),
        card("Company change", "Tell the providers who are affected", "Understand the change, prepare the message, inform them, record it where required, follow up on important changes.", ["Do not rewrite the policy while you are telling it."]),
        card("Data change", "Update every copy", "Verify, update the panel, update the online record, update the offline record where required, confirm they match.", ["A change in only one place is a conflicting record."]),
        card("Training", "Record who came and who did not", "Need, schedule, inform, conduct or coordinate, attendance, status, follow up with anyone pending.", ["Pending training stays open."])
      ],
      reporting: [
        { period: "When it happens", kicker: "POE-R1", title: "Exception note", when: "The day you find a missing document, a false-looking confirmation, a payment discrepancy, or a provider you cannot activate cleanly.", lead: "The manager cannot see what you only noticed.", body: ["Write the fact and the next action."], contents: [{ title: "What is wrong, and who owns the next step", why: "A discrepancy that stays in your head is hidden." }], mustHave: ["Provider", "What is wrong", "Next action", "Date"], submit: [{ to: "Provider Experience Manager", why: "Same day." }] },
        { period: "End of shift", kicker: "POE-R2", title: "Open provider work", when: "Before you leave.", lead: "Leads, onboarding, and booking confirmations that are not finished.", body: ["Status, pending work, next action, time."], contents: [{ title: "Handover", why: "Provider leads are as easy to lose as customer leads." }], mustHave: ["Open items", "Status", "Next action"], submit: [{ to: "Next shift and Provider Experience Manager", why: "So the work has an owner overnight." }] }
      ],
      standards: [
        { title: "Provider onboarding", lead: "Registration, verification, documents, agreement, and briefing are complete before activation.", body: ["A provider is not treated as fully active until those requirements are done."] },
        { title: "Documentation", lead: "Required documents are complete, accurate, accessible, and stored online and in the offline backup.", body: ["Records are organised so they can be retrieved."] },
        { title: "Data accuracy", lead: "Required standard: 100% accuracy for mandatory provider information.", body: ["The admin panel and the company records match the provider’s current situation."] },
        { title: "Provider leads", lead: "You work the leads that have your name. Onboard now before write down, do later.", body: ["You do not open the inbox.", "A later lead is recorded and messaged the same day, and is not onboarded while an onboard-now lead is waiting.", "A lead is not closed without a valid reason."] },
        { title: "Follow-up", lead: "Required standard: 100% follow-ups done on time.", body: ["Required provider follow-ups are completed on the assigned schedule."] },
        { title: "Provider support", lead: "Queries and operational issues get timely attention.", body: ["Nothing stays unresolved without an owner or a next action. Issues outside this job’s authority are escalated."] },
        { title: "Booking coordination", lead: "Availability is confirmed before the provider is treated as confirmed.", body: ["Customer Support receives that confirmation, or a clear no."] },
        { title: "Training", lead: "Required training is arranged and completed before the provider is expected to do work that needs it.", body: ["Attendance, completion, and pending training are recorded. A false training mark is a violation."] },
        { title: "Communication", lead: "Important company updates reach the relevant providers accurately and on time.", body: ["Policy, services, areas, pricing, process, and training. Important updates are recorded."] },
        { title: "Record-keeping", lead: "Provider records are updated whenever the information changes.", body: ["Name, contact, category, expertise, area, distance, availability, documents, agreement, account, training, lead status, and payment information."] },
        { title: "Payment records", lead: "Provider payment and account information is recorded correctly.", body: ["A discrepancy is identified, recorded, and escalated."] },
        { title: "Market information", lead: "Market prices are current, recorded, and clearly separated from approved company pricing.", body: ["Services, labour, parts, products, and materials. This team cannot change Panun Kaergar’s approved prices."] },
        { title: "Confidentiality", lead: "Provider documents, personal information, payment information, and company records are used only for authorised business.", body: ["For documentation, lead follow-ups, provider data, booking confirmation, payment records, and mandatory training, the operating requirement remains 100%."] }
      ],
      kpis: poeKpis,
      escalations: [
        { title: "The issue is outside your authority", lead: "Escalate with the record.", body: ["Do not invent a policy or a price."], points: [{ title: "You send to", why: "Provider Experience Manager." }] },
        { title: "A payment or document does not match", lead: "Write the difference.", body: ["Do not pick the version that makes the file look tidy."], points: [{ title: "You send to", why: "Provider Experience Manager, and Accounts if money is involved." }] },
        { title: "Customer Support is waiting and you have no provider", lead: "Say so. Do not confirm a maybe.", body: ["Start the search and keep the case updated."], points: [{ title: "You send to", why: "Provider Experience Manager and Customer Support." }] }
      ]
    }
  });

  addRole({
    id: "pom",
    name: "Provider Experience Manager",
    reportsTo: "Head of Operations",
    hero: "role-pom.png",
    result: "Every service provider lead has one owner and a written priority, onboard-now work happens before later work, and payment reports come from the records.",
    what: "You manage Provider Support. You are the assigner for the provider inbox. A lead reaches you after the Customer Experience Manager, or their deputy, has marked it as a service provider. You write onboard now or write down, do later, and one owner. You do not ask the team who wants the lead. You check the files, join provider operations when a case needs a manager, report provider performance and payments, recruit against real coverage gaps, train the team and the providers, and coordinate with the Customer Experience Manager. What you cannot decide, you send to the Head of Operations with the facts and what you already did.",
    why: "Provider Support executes. You make sure they did it correctly. A provider marked active on a verbal assurance, or a town with no provider that nobody has named, is your miss if you did not look.",
    how: "Review tasks, leads, follow-ups, onboarding, documents, availability, training, payments, support cases, booking coordination, and coverage. Use the records. Each day: pending work, leads, follow-ups, onboarding, issues, payments, coverage, then correct the team. When you join a provider conversation, do not drop the management work.",
    lanes: [
      { kicker: "Part 1", title: "The provider inbox and the team", work: "On each service provider lead, write onboard now or write down, do later, and one owner. Onboard now means an area and a category Panun Kaergar already serves. The next owner is the person on shift with the fewest onboard-now leads, then the fewest open leads. If someone is away, move their onboard-now leads the same day. Before leave, name one Provider Support person as deputy, with the dates, in the admin panel. Then check onboarding, documents, the panel, availability, training, and payments.", result: "No onboard-now lead sits without an owner, and a later lead is not onboarded ahead of it.", who: "You. If Provider Support is empty, you do that work and write it down." },
      { kicker: "Part 2", title: "Coverage, pay, and training", work: "Compare demand with providers. Name areas with enough cover, one provider, none, or only a temporary. Report payments due, paid, pending, and discrepancies from the records. Arrange team training, provider training, and meetings.", result: "Recruitment follows a gap, payment reports trace to records, and training attendance is real.", who: "You." },
      { kicker: "Part 3", title: "The other team, and Head of Operations", work: "Share booking facts with the Customer Experience Manager. Escalate disputes, serious payment issues, policy violations, suspension, service failures, and coverage gaps you cannot close.", result: "Both teams hold the same facts, and anything outside your authority has an owner above you.", who: "You." }
    ],
    acting: "Join Provider Support when the issue is urgent, the team is short, or the provider needs a manager. Write that you did. Do not let direct work erase the checks or the inbox. Before leave, the deputy's name and dates are in the admin panel. When you return, you read the onboard-now leads from those days and you take the queue back.",
    owns: [
      "The provider inbox: provider priority and one owner on every service provider lead",
      "The deputy named before your leave",
      "Whether Provider Support’s files and follow-ups are true",
      "Coverage by area and by service",
      "Provider payment visibility",
      "Team and provider training",
      "Coordination with Customer Experience",
      "Escalations to Head of Operations"
    ],
    good: [
      "You can point to the file for every newly active provider.",
      "An area with no provider is named, with an action.",
      "Pending provider payments are listed from records.",
      "A provider complaint has progress or a handover.",
      "Customer Experience heard the same confirmation you have.",
      "An issue outside your authority reached Head of Operations with facts and a recommendation."
    ],
    bad: [
      "You believed the team was fine because they said so.",
      "A town has one provider and no backup, and the report does not say so.",
      "Payments were estimated.",
      "You handled provider calls all day and did not look at onboarding.",
      "A serious dispute stayed with you because escalating felt slow.",
      "You changed a provider’s status on your own authority."
    ],
    records: [
      "Team task list with owner and next action",
      "Onboarding and document checks",
      "Coverage by area and category",
      "Payment report and discrepancy log",
      "Training and meeting attendance",
      "Escalations: problem, facts, actions taken, status, recommended next step"
    ],
    mustNot: [
      "Let Provider Support take a lead from the inbox",
      "Leave an onboard-now lead without an owner while a later lead is being onboarded",
      "Go on leave without a named deputy and dates in the admin panel",
      "Allow activation without onboarding",
      "Hide a payment discrepancy or a coverage gap",
      "Mark training complete when people did not attend",
      "Decide a suspension, a price, or a policy that needs Head of Operations",
      "Leave Customer Experience with a different story",
      "Report numbers you have not tied to records"
    ],
    rules: [
      "You assign every service provider lead. Onboard now, or write down, do later. One owner. The person on shift with the fewest onboard-now leads, then the fewest open leads. You do not ask who wants it.",
      "Before leave, name one deputy and the dates in the admin panel. When you return, read the onboard-now leads from those days and take the queue back.",
      "Each day: no onboard-now lead is without an owner. Nobody onboarded a later lead while an onboard-now lead assigned to them was waiting.",
      "This is a miss. You did not monitor the Provider Support team, or allowing repeated team errors without correction.",
      "This is a miss. You ignored a missed provider lead or follow-up.",
      "This is a miss. You did not review an incomplete or inaccurate provider record.",
      "This is a miss. You allowed a provider to be activated without the required onboarding.",
      "This is a miss. You ignored a provider coverage gap.",
      "This is a miss. You gave a provider, payment, or performance report that was wrong, or you changed or hid the provider numbers.",
      "This is a miss. You hid a payment discrepancy.",
      "This is a miss. You did not arrange required training, or marking training completed when it was not.",
      "This is a miss. You did not coordinate with the Customer Experience Manager when a case needs both teams.",
      "This is a miss. You ignored an escalated provider issue, or failing to escalate an issue outside this authority.",
      "This is a miss. You made an unauthorised policy, pricing, or provider-status decision.",
      "This is a miss. You shared confidential provider information without authorisation.",
      "This is a miss. You did not provide a required management report.",
      "This is a miss. You repeatedly did not complete assigned managerial responsibilities.",
      "This is a miss. You did not provide direct operational support when it is reasonably required.",
      "This is a miss. You concealed a serious provider problem from the Head of Operations.",
      "This is a miss. You repeatedly did not follow the Provider Experience process.",
      "The Provider Support team executes the provider work. You are accountable for that work being done correctly, for coverage and payment visibility, for developing the team and the providers, for coordination with Customer Operations, and for escalating important issues to the Head of Operations.",
      "Action: correction, then warning, then training or coaching, then performance monitoring, then a performance plan, then further disciplinary action.",
      "Serious misconduct, deliberate data manipulation, concealment, fraud, or misuse of provider information can go straight to disciplinary action, including termination, subject to company policy, employment terms, and applicable law."
    ],
    kpis: pomKpis,
    detailed: {
      copy: {
        heroKicker: "What this job is",
        definition: "You run the provider system. You assign the provider inbox. Provider Support works only the leads with their name, onboard now before write down, do later. You make sure that work is true, you keep coverage and payments visible, you develop the team and the providers, you stay aligned with Customer Experience, and you escalate what you cannot close. Before leave, you name one deputy.",
        owns: "The team’s result, the coverage map, and the payment picture. Not a private list of favourite providers.",
        lanes: "The team’s files, the gaps and the money, and the line to Customer Experience and Head of Operations.",
        responsibilities: "Click a row to open the full card.",
        handoffs: "You receive files and escalations. You give corrections, coverage actions, payment reports, and a clean escalation.",
        workflow: "Daily team check, coverage, payment report, training, an escalated provider issue, and the performance review.",
        reporting: "Provider performance, payments, and coverage. Based on records.",
        standards: "A manager’s bar: the team’s onboarding, documents, follow-ups, and confirmations.",
        kpis: "Your numbers move when the team’s work is actually right. A tidy report on top of a bad file is a miss.",
        escalations: "The list Head of Operations must receive, with the pack they need."
      },
      hub: { icon: "handshake", line: "You assign the provider inbox. Provider Support works the leads with their name. You show where coverage or money is wrong." },
      definition: {
        what: [
          { title: "You check the team from the files", why: "Leads, follow-ups, onboarding, documents, panel, availability, training, payments, support cases, and booking coordination." },
          { title: "You name where we cannot deliver", why: "Enough providers, only one, none, backup needed, shortage by service, high demand with no capacity, temporary providers in use." },
          { title: "You escalate with a pack, not a worry", why: "Problem, facts, actions already taken, current status, and a recommended next step." }
        ],
        why: [
          { title: "Recruitment without a gap is guessing", why: "Coverage reports exist so hiring follows demand." },
          { title: "Customer and provider teams drift if you do not pass the same facts", why: "Bookings, delays, cancellations, and complaints need one story." },
          { title: "A manager who only fights fires is not managing", why: "Direct support is required. It is not a substitute for the checks." }
        ]
      },
      glossary: sharedGloss.concat([
        { id: "coverage-gap", term: "Coverage gap", aliases: ["coverage gap", "coverage gaps", "no provider"], meaning: "An area or service where providers are missing, single, temporary, or too few for the demand. It must be named and given an action. Leaving it unknown is a miss." },
        { id: "pom", term: "Provider Experience Manager", aliases: ["Provider Experience Manager"], meaning: "This job. You are accountable for Provider Support doing the provider process correctly." }
      ]),
      responsibilities: [
        card("Responsibility 1", "Assign the provider inbox, then check the work", "You are the assigner. Provider Support does not pick.", ["Write onboard now, or write down, do later, and one owner.", "Onboard now: an area and a category Panun Kaergar already serves.", "The next owner is the person on shift with the fewest onboard-now leads, then the fewest open leads.", "If someone is away, move their onboard-now leads the same day.", "Before leave, name one Provider Support person as deputy, with the dates, in the admin panel.", "Each day, check that no onboard-now lead is unowned, and that a later lead was not onboarded ahead of one.", "When you return from leave, read the onboard-now leads from those days and take the queue back.", "Correct the error and show the standard."], [{ title: "Verbal confirmation is not the check", why: "Open the lead, the document, and the panel." }]),
        card("Responsibility 2", "Join the work without abandoning the system", "High-priority provider issues, difficult conversations, complaints, urgent bookings, staff shortage, meetings, and escalations.", ["Your own involvement must not leave leads, onboarding, or payments unreviewed."], [{ title: "Write the days you covered the executive work", why: "Otherwise it looks like the job was filled and the checks still happened." }]),
        card("Responsibility 3", "Report providers as they are", "Active, newly onboarded, activated, inactive, availability, response, performance, complaints, cancellations, service issues, coverage by area and by category, and who needs training or correction.", ["Use records. A provider count you cannot trace does not go in."], [{ title: "Inactive and weak providers belong in the report", why: "A list of only good providers hides the gap." }]),
        card("Responsibility 4", "Show provider money from the records", "Due, completed, pending, discrepancies, by provider and by period, plus what Accounts or Operations asks for.", ["Flag differences. Do not smooth them."], [{ title: "Pending must have an owner", why: "A pending payment with no next action is hidden." }]),
        card("Responsibility 5", "Train the team and the providers, and keep the meetings real", "Team: process, database, communication, leads, reporting, payment, new systems. Providers: orientation, category, process, policy, customer handling, evidence, pricing updates, refreshers.", ["Record attendance and the outcome. Follow up with anyone who missed it. A meeting that was not held is not marked held."], [{ title: "Training status must match attendance", why: "This is a miss. You marked it complete when it was not is a violation." }]),
        card("Responsibility 6", "Stay in step with Customer Experience, and escalate the rest", "Bookings, availability, confirmation, replacement, delays, cancellations, complaints that involve a provider, service issues, feedback, and pricing information.", ["Escalate a serious dispute, a significant payment issue, a major policy violation, a suspension, a serious service failure, a coverage gap that blocks operations, a repeated problem, or a company pricing decision."], [{ title: "The pack", why: "Problem, facts, actions already taken, status, recommended next step." }])
      ],
      handoffs: [
        side(card("You receive", "Provider Support’s files", "Leads, onboarding, documents, payments, and open issues.", ["This is what you review."], [], [["From", "Provider Support"], ["When", "Every working day"]]), "in"),
        side(card("You receive", "Customer demand that has no provider", "Area or service, leads, and the failed bookings.", ["Turn it into a coverage action."], [], [["From", "Customer Experience Manager"], ["When", "When the pattern shows"]]), "in"),
        side(card("You give", "The same booking facts Customer Experience has", "Confirmation, delay, replacement, or complaint status.", ["Do not leave them telling the customer a different story."], [], [["To", "Customer Experience Manager"], ["When", "As soon as the fact changes"]]), "out"),
        side(card("You give", "Coverage and payment reports", "Gaps, actions, amounts, and discrepancies.", ["Traceable to records."], [], [["To", "Head of Operations, and Accounts where money is theirs"], ["When", "On the required cycle"]]), "out"),
        side(card("You give", "An escalation pack", "Problem, facts, actions taken, status, recommendation.", ["Do not wait until it is a crisis if it is already outside your authority."], [], [["To", "Head of Operations"], ["When", "When you cannot decide it"]]), "out")
      ],
      workflow: [
        card("Every working day", "Assign the provider inbox, then review the team", "New provider leads first. Then tasks, pending work, follow-ups, onboarding, issues, payments, and coverage.", ["No onboard-now lead stays without an owner.", "Guide the person. Update the pending actions."]),
        card("Before leave, and the day you return", "The provider inbox still has one assigner", "Name the deputy before you go.", ["Write one Provider Support person and the dates in the admin panel.", "On return, read the onboard-now leads from the leave days and take the queue back. The deputy stops assigning."]),
        card("Coverage", "Hire against a gap, not a hunch", "Demand, current providers, gaps, priority areas and services, recruitment, new providers tracked, coverage updated, remaining gaps reported.", ["A temporary provider is named as temporary."]),
        card("Payments", "Build the report from records", "Collect, verify provider and booking, check amounts, identify pending, resolve or flag differences, submit.", ["Do not estimate a total."]),
        card("Training", "Schedule, hold, record, chase the absent", "Need, schedule, inform, conduct, attendance, completion, follow up with whoever is pending.", ["Attendance is the record."]),
        card("Escalated issue", "Try, then send it up if it is not yours", "Review the facts, attempt resolution, coordinate, record, escalate if required, follow up, write the final status.", ["Include what you already did."]),
        card("Team performance", "Compare the person with the standard", "Collect the data, check accuracy, compare with the KPIs, name the gap, correct or train, watch the next work, report it.", ["The team’s misses are part of your result."])
      ],
      reporting: [
        { period: "Weekly", kicker: "PXM-R1", title: "Provider performance and coverage", when: "With the weekly operations review.", lead: "Active providers, new providers, gaps, and problems, from the records.", body: ["Recruitment follows a real gap, not a general assumption."], contents: [{ title: "Provider position", why: "Number of active providers, new providers onboarded, providers activated, inactive providers, availability, response and acceptance, performance, complaints, cancellations, service issues, who needs training, and who needs follow-up or correction." }, { title: "Coverage", why: "Areas with enough providers, only one provider, no provider, a need for backup, a shortage by service, high demand with too few providers, areas that need recruitment, and areas using temporary providers." }], mustHave: ["Period", "Active, new, and inactive", "Coverage by area and by category", "Open complaints and service issues", "Training still pending"], submit: [{ to: "Head of Operations", why: "Written from records." }] },
        { period: "Weekly", kicker: "PXM-R2", title: "Provider payments", when: "On the cycle Accounts and Operations use.", lead: "Due, completed, pending, and discrepancies, from actual records.", body: ["Provider-wise and period-wise, plus any other payment data Accounts or Operations requires."], contents: [{ title: "Pending and discrepancies", why: "This is a miss. You hid a difference is a violation." }], mustHave: ["Provider", "Amount due", "Amount completed", "Pending", "Discrepancy if any", "Period"], submit: [{ to: "Head of Operations and Accounts", why: "So money is visible." }] }
      ],
      standards: [
        { title: "The provider inbox has an owner on every lead", lead: "Provider priority and one name, the same day.", body: ["Onboard now before write down, do later.", "A deputy is named, with dates, before your leave.", "Until that name exists, the Head of Operations keeps the inbox closed."] },
        { title: "Team management", lead: "Every assigned Provider Support task has an owner, a status, and a next action.", body: ["You maintain accountability for team performance."] },
        { title: "Task completion", lead: "Tasks are completed accurately and on time, not merely marked completed.", body: ["You verify the work. A tick is not proof."] },
        { title: "Data accuracy", lead: "Critical provider data accuracy target: 100%.", body: ["Provider information, leads, onboarding, payment, and coverage records match the actual situation."] },
        { title: "Provider leads", lead: "No provider lead is knowingly left unattended.", body: ["Required follow-ups are completed on the defined schedule. Operating standard: 100% follow-ups done on time."] },
        { title: "Coverage", lead: "Provider gaps are identified and reported. They do not stay unknown.", body: ["Enough coverage, only one provider, no provider, backup needed, shortage by service, high demand with too few providers, recruitment needed, and temporary providers in use."] },
        { title: "Payment reporting", lead: "Provider payment reports are accurate, traceable, and based on actual records.", body: ["Due, completed, pending, and discrepancies."] },
        { title: "Training", lead: "An identified training requirement is acted on and tracked until completion.", body: ["Team training, provider and partner training, and meetings. Attendance and the required outcome are recorded."] },
        { title: "Coordination", lead: "Customer and Provider teams share the required information promptly when a booking or issue involves both.", body: ["Both teams hold the same relevant facts."] },
        { title: "Escalation", lead: "An issue outside this authority is escalated. It is not delayed and it is not decided here.", body: ["The pack is the problem, the facts, the actions already taken, the current status, and a recommended next step."] },
        { title: "Reporting", lead: "Accurate, complete, based on actual data, on time, consistent, and easy to understand.", body: ["For provider leads, mandatory documentation, provider data, payment records, training completion, booking coordination, and escalations, the operating expectation remains 100%."] },
        { title: "Direct support", lead: "When the operation needs a manager, you assist Provider Support.", body: ["That assistance does not replace the team checks."] },
        { title: "Confidentiality", lead: "Provider documents, personal information, payments, and company information are handled only for authorised business.", body: ["Your performance is also assessed through the Provider Support team’s performance."] }
      ],
      kpis: pomKpis,
      escalations: [
        { title: "Suspension, pricing, or a serious provider dispute", lead: "Do not decide it yourself.", body: ["Problem, facts, actions taken, status, recommendation."], points: [{ title: "You send to", why: "Head of Operations." }] },
        { title: "A coverage gap is blocking bookings", lead: "Name the area or service and the demand.", body: ["Say what recruitment or backup you have already started."], points: [{ title: "You send to", why: "Head of Operations, with Customer Experience informed." }] },
        { title: "Payment records do not match", lead: "Do not hide the difference.", body: ["Show both figures."], points: [{ title: "You send to", why: "Head of Operations and Accounts." }] }
      ]
    }
  });

  addRole({
    id: "hoo",
    name: "Head of Operations",
    reportsTo: "CEO",
    hero: "role-hoo.png",
    result: "Both managers run their systems, the reports match the records, and every major problem has an owner, an action, and a deadline.",
    what: "You oversee the operating system through the Customer Experience Manager and the Provider Experience Manager. You review their reports, connect leads, bookings, payments, coverage, and demand, assign corrective actions, and check that those actions finish. You do not assign the daily inbox. You do not do their daily work. If a manager is away and no deputy was named, you write one name that morning, before anyone opens the inbox. You do not rebuild a report they already owe you, unless you are checking it or writing a view that needs both sides.",
    why: "If you answer the leads and chase the providers, the managers stop managing and you stop seeing the whole. E-Myth’s point here: the executives do the work, the managers run their systems, and you see whether those systems are working together.",
    how: "Each day, read the critical updates, the urgent issues, and whether the managers did their checks. Each week, review both reports, the KPIs, demand against coverage, and last week’s actions. Each month, compare with the previous month and set the next priorities. For a major problem: identify, verify, find the cause, assign an owner, set the action and the deadline, monitor, verify the result, close or escalate.",
    lanes: [
      { kicker: "Part 1", title: "The two managers", work: "Review whether Customer Experience is managing customer work and Provider Experience is managing provider work: monitoring, training, reports, enforcement, problem-solving, and escalations.", result: "Manager reports arrive on time, and a repeated team failure has a corrective action.", who: "You. You do not routinely open individual leads unless an audit or a specific issue requires it." },
      { kicker: "Part 2", title: "The joined picture", work: "Leads against bookings, eligible conversion, cancellations, revenue and collections, provider coverage against demand, area and service performance, month against month.", result: "Customer demand and provider capacity are looked at together, and money types are not mixed.", who: "You. The managers supply their reports. You connect them." },
      { kicker: "Part 3", title: "Actions and the month", work: "Give every major issue an owner, an action, and a deadline. Follow it until it is done. Write the management operational report. Set next month’s priorities.", result: "Nothing major sits without an owner, and the month has a written close.", who: "You." }
    ],
    acting: "You do not take the managers’ daily work as your job. If a manager is on leave and the deputy was already named, you do not assign. If the deputy was not named, you write one name that morning and then you stop. The inbox stays closed until that name is written. A price, a booking advance, a discount, or a refund that cannot wait while the Customer Experience Manager is away comes to you the same day. You decide only what is already allowed for that manager, or you send it upward. You do not become the assigner for the rest of the leave.",
    owns: [
      "One deputy, named that morning, when a manager is away and the inbox has no assigner",
      "Whether the two managers are managing",
      "The management reporting rhythm",
      "Cross-department issues: demand against coverage, complaints against provider performance, collections against provider pay",
      "Corrective actions until they are verified",
      "The monthly operational report"
    ],
    mustNot: [
      "Assign the daily inbox yourself, except to name the missing deputy that morning",
      "Leave the inbox open when no deputy has been named",
      "Recreate the managers’ daily reports as your own habit",
      "Accept an incomplete report and move on",
      "Treat lead growth or revenue movement as an employee failure by default",
      "Leave a major issue without an owner",
      "Hide a serious customer, provider, or money problem",
      "Present an unchecked figure as a fact"
    ],
    good: [
      "Both weekly reports arrived, and you checked the figures that mattered.",
      "A repeated complaint has a cause, an owner, and a date.",
      "An area with demand and no provider is in the same note as the leads.",
      "Collections, revenue, and provider pay are separate lines.",
      "Last month’s actions are either done or still open with a status.",
      "You did not spend the day inside one executive’s queue."
    ],
    bad: [
      "You rebuilt the lead list because it felt safer than reading the manager’s report.",
      "A manager missed two reports and you said nothing.",
      "A coverage gap is known on the floor and absent from your note.",
      "You called a revenue dip a person failing, with no look at demand or capacity.",
      "A corrective action has no deadline.",
      "A serious issue stayed with a manager who had already said they could not decide it."
    ],
    records: [
      "Daily note of critical issues and deadlines",
      "Weekly review of both managers",
      "Corrective action log: owner, action, deadline, status, result",
      "Monthly operational report",
      "Escalations sent upward, with the facts"
    ],
    rules: [
      "If a manager is on leave and no deputy was named, write one name that morning before the inbox opens. For customer leads, one Customer Experience Executive. For provider leads, one Provider Support person. Until that name is written, the inbox stays closed.",
      "You do not assign leads after that name is written. The deputy assigns. You do not approve a new price. A price, a booking advance, a discount, or a refund that cannot wait comes to you, and you use only what the company has already written.",
      "This is a miss. You did not monitor the Customer Experience Manager or the Provider Experience Manager.",
      "This is a miss. You did not review a required manager report, or repeatedly accepting an incomplete or inaccurate report without correction.",
      "This is a miss. You did not monitor manager KPIs.",
      "This is a miss. You ignored a repeated team or manager-level problem.",
      "This is a miss. You did not follow up an assigned corrective action, or allowing a major operational issue to remain without an owner or an action.",
      "This is a miss. You did not escalate a serious issue when escalation is required.",
      "This is a miss. You manipulated, hiding, or knowingly misrepresenting operational information, or presenting unverified information as a confirmed fact.",
      "This is a miss. You ignored a major discrepancy in a report without investigation.",
      "This is a miss. You did not conduct a required daily, weekly, or monthly operational review, or failing to create a required management-level report.",
      "This is a miss. You repeatedly did the managers’ daily work instead of the required oversight and analysis.",
      "This is a miss. You made a major operational decision without checking the available information where the process requires verification.",
      "This is a miss. You did not identify or act on a significant provider coverage gap.",
      "This is a miss. You did not monitor company collections, provider payments, or revenue reports where those are assigned to this role.",
      "This is a miss. You did not track an important corrective action until completion.",
      "This is a miss. You concealed a serious customer, provider, financial, or operational problem from management.",
      "This is a miss. You breached confidentiality.",
      "This is a miss. You repeatedly did not follow the approved operational system after guidance or correction.",
      "This is a miss. You falsified a record, a report, a KPI result, or an action status.",
      "This is a miss. You ignored a management instruction on operational control or reporting.",
      "Repeated process violations lead to documented correction, training, or warning, then a performance plan where appropriate. Continued failure after that may lead to further disciplinary action, up to termination, subject to company policy, employment terms, and applicable law. Serious misconduct can go straight to disciplinary action."
    ],
    kpis: hooKpis,
    detailed: {
      copy: {
        heroKicker: "What this job is",
        definition: "You see the whole operation. The Customer Experience Manager assigns the customer inbox. The Provider Experience Manager assigns the provider inbox. You make sure they are doing that, including naming a deputy before leave. If they did not, you name one person that morning and you do not take the queue. You connect their numbers, and you force repeated problems to a cause and an owner. You are not a third executive and you are not a third manager.",
        owns: "Oversight, the joined analysis, the missing deputy's name, and the action log. Not the next lead and not the next provider file.",
        lanes: "Watch the managers. Join the two pictures. Close the month with priorities. If you live in lane one by doing their tasks, lanes two and three do not happen.",
        responsibilities: "Click a row to open the full card.",
        handoffs: "You receive manager reports. You give actions, deadlines, and the management report. You escalate upward when the issue is no longer an operations decision.",
        workflow: "Daily, weekly, monthly, and the corrective-action path. Same rhythm so the company does not depend on your mood.",
        reporting: "You write the view that neither manager can write alone, and you check theirs rather than replacing them.",
        standards: "Reports on time, figures checked when it matters, every major issue owned, business indicators read as business indicators.",
        kpis: "These count whether oversight happened. They do not count how many leads you personally called.",
        escalations: "What you send above you, and what you send back to a manager who skipped their job."
      },
      hub: { icon: "account_tree", line: "Managers run their teams. You run the operating review and the actions that cross both teams." },
      definition: {
        what: [
          { title: "You monitor the managers", why: "Their KPIs, their monitoring, their training, their report accuracy, their enforcement, and whether they escalated what they should." },
          { title: "You connect customer demand with provider capacity and with money", why: "Leads, bookings, cancellations, collections, provider pay, and coverage by area and service." },
          { title: "You keep an action alive until the result is checked", why: "Identify, verify, cause, owner, action, deadline, monitor, verify, close or escalate." }
        ],
        why: [
          { title: "A company with two busy managers can still have no operating system", why: "Someone has to see both sides and the repeat." },
          { title: "Re-doing their reports hides whether they can manage", why: "Check. Do not replace, unless you are verifying or writing a cross-department view." },
          { title: "Not every bad month is a bad employee", why: "Demand, marketing, price, provider availability, and the market move the business indicators." }
        ]
      },
      glossary: sharedGloss.concat([
        { id: "business-indicator", term: "Business indicator", aliases: ["business indicator", "business indicators"], meaning: "Lead growth, booking growth, eligible conversion, cancellation rate, revenue growth, collections, provider growth, coverage, area performance, service performance, customer retention, and the provider payment position. Watch them. Do not treat them automatically as an employee failure." },
        { id: "corrective-action", term: "Corrective action", aliases: ["corrective action", "corrective actions"], meaning: "A written fix for a significant problem: owner, action, deadline, status, and a check of the result. A problem with no owner is not being managed." },
        { id: "hoo", term: "Head of Operations", aliases: ["Head of Operations"], meaning: "This job. You oversee the managers. You do not become them." }
      ]),
      responsibilities: [
        card("Responsibility 1", "See whether Customer Experience is managing", "Lead handling, follow-ups, bookings, complaints, cancellations, completion, feedback, data and recordings, productivity, KPIs, and the corrections they claim to have made.", ["Do not routinely open individual leads. Open them when an audit, an investigation, or a specific issue requires it."], [{ title: "A missed team follow-up that the manager ignored is your finding", why: "You are checking the manager, not replacing the executive." }]),
        card("Responsibility 2", "See whether Provider Experience is managing", "Onboarding, documents, availability, coverage, training, support, performance, issues, payments, complaints, recruitment gaps, and corrective actions.", ["A provider count with no gap list is an incomplete report. Send it back."], [{ title: "Activation without onboarding is a manager miss", why: "If they allowed it and you did not act, you allowed it too." }]),
        card("Responsibility 3", "Keep the reporting system honest", "Define what managers must send, how often, and what complete means. Collect, review, compare with source data when needed, and send back what is missing.", ["Reports must lead to an action. A report that changes nothing is filing."], [{ title: "Do not quietly rewrite their report", why: "An independent check is allowed. Doing their job for them is not." }]),
        card("Responsibility 4", "Write the analysis that needs both sides", "Leads against bookings, eligible conversion, cancellation trends, revenue and collections, coverage against demand, area, service, month against month, and repeated problems.", ["By area: leads, bookings, completed bookings, revenue, cancellations, provider coverage, demand with too few providers, providers with low conversion, and areas that need attention.", "By service: leads, bookings, conversion, revenue, cancellations, provider availability, shortages, customer complaints, and services that need training, recruitment, marketing, or a process change.", "Money stays as the four lines, and those lines are not added into one revenue figure: the booking advance as the payment policy says, the customer payment in the admin panel, the provider settlement in the admin panel, and cash still open with who holds it. Finance writes the same four lines. Routine financial processing stays with Finance.", "A cross-department issue has one owner, an action, a deadline, and a status: demand against availability, bookings against capacity, complaints against provider performance, collections against provider payments, revenue against bookings, and marketing demand against operational capacity."], [{ title: "Cross-department issues need one owner", why: "Demand against capacity, complaints against provider performance, collections against provider payments, marketing demand against operational capacity." }]),
        card("Responsibility 5", "Run the rhythm", "Daily: critical updates, urgent issues, deadlines, whether managers did their monitoring. Weekly: both reports, KPIs, demand, previous actions, new actions. Monthly: the comparison, manager performance, repeated causes, next month’s priorities.", ["Record the decisions. A decision that lives in a meeting did not happen."], [{ title: "Same time", why: "So the system does not depend on mood." }]),
        card("Responsibility 6", "Force a repeated problem to a cause", "Do not handle the fifth occurrence as if it were the first.", ["Assign the manager who owns it. Set the action and the deadline. Check the result. If the procedure itself is wrong, say so and get the change implemented."], [{ title: "Close only after the result is verified", why: "An action marked done with no check is not done." }])
      ],
      handoffs: [
        side(card("You receive", "Customer operations report", "Leads, bookings, complaints, conversion, cancellations, future customers.", ["If it is late or thin, send it back."], [], [["From", "Customer Experience Manager"], ["When", "Daily critical notes, weekly report, monthly close"]]), "in"),
        side(card("You receive", "Provider operations report", "Coverage, onboarding, payments, issues, training.", ["Compare it with the customer demand report."], [], [["From", "Provider Experience Manager"], ["When", "Daily critical notes, weekly report, monthly close"]]), "in"),
        side(card("You give", "A corrective action", "Owner, action, deadline, and how you will know it worked.", ["One owner. Not a committee with no name."], [], [["To", "The responsible manager"], ["When", "When the problem is verified"]]), "out"),
        side(card("You give", "The management operational report", "The joined month: what improved, what is still open, and the priorities.", ["Traceable. Separate the money lines."], [], [["To", "CEO and management"], ["When", "After the monthly review"]]), "out"),
        side(card("You give", "An upward escalation", "What operations cannot decide: policy, a serious risk, or a decision above this role.", ["Facts, not a mood."], [], [["To", "Higher management"], ["When", "When it is no longer yours to decide"]]), "out")
      ],
      workflow: [
        card("Every working day", "Read the critical line", "Updates from both managers, urgent issues, whether they completed their monitoring, KPI exceptions, actions due.", ["If a manager is away and no deputy is named, write one name before the inbox opens. Then stop.", "Follow yesterday’s pending actions. Escalate what must go up. Write the decisions."]),
        card("Every week", "Review both systems together", "Collect the reports, check completeness, compare important figures with the data, review both operations, review manager KPIs, leads, bookings, conversion, cancellations, coverage, demand, revenue, collections, and payments.", ["Name repeated problems. Set owners and deadlines. Review last week’s actions."]),
        card("Every month", "Close the month and set the next one", "Consolidate, compare with the previous month, review manager performance and repeated causes, assign actions, set priorities, write the management report.", ["Monitor the monthly actions until they finish."]),
        card("When a significant problem appears", "Run the action path", "Problem identified, facts checked, cause identified, manager assigned, action defined, deadline set, progress monitored, result checked, closed or escalated.", ["No major problem without an owner and a next action."])
      ],
      reporting: [
        { period: "Weekly", kicker: "HOO-R1", title: "Weekly operational review", when: "The same time each week, after both manager reports are in.", lead: "Decide what continues, what is corrected, and who owns it.", body: ["If a manager report is missing, that fact is the first line."], contents: [{ title: "Both operations, the gaps, and the actions", why: "A review that only restates one team is incomplete." }], mustHave: ["Week", "Report status", "KPI exceptions", "Demand against coverage", "Open actions", "New actions with owners"], submit: [{ to: "The two managers", why: "Their actions." }, { to: "Management", why: "Anything that needs a decision above operations." }] },
        { period: "Monthly", kicker: "HOO-R2", title: "Management operational report", when: "After the monthly review.", lead: "Current month against previous month, and the priorities for next month.", body: ["Leads, bookings, eligible conversion, cancellations, revenue, collections, provider payments, coverage, area, service, and manager performance."], contents: [{ title: "Improvements, gaps, and repeated causes", why: "The month is for causes, not only totals." }], mustHave: ["Month", "Comparison", "Money lines kept separate", "Coverage", "Repeated problems", "Priorities"], submit: [{ to: "CEO and management", why: "The management-level report." }] }
      ],
      standards: [
        { title: "Managers are monitored from reports, KPIs, and records", lead: "Not from someone saying it is done.", body: ["A late, incomplete, or inaccurate report is sent back. It is not quietly accepted."] },
        { title: "You do not do their daily job", lead: "The role is to make sure the managers are managing.", body: ["Do not recreate a report that is already theirs, unless you are verifying it or writing a view that needs more than one department.", "If a manager is away and no deputy was named, you write one name that morning. You do not assign the leads after that."] },
        { title: "Reports are accurate, complete, and traceable", lead: "Every required manager report is submitted on time.", body: ["Important figures are cross-checked when required. Unverified information is not presented as fact."] },
        { title: "Every major problem has an owner, an action, and a status", lead: "Corrective actions are followed until completion.", body: ["Identify, verify, find the cause, assign the owner, set the action, set the deadline, monitor, verify the result, then close or escalate.", "A repeated problem is investigated for its cause. It is not handled again as a brand-new incident."] },
        { title: "Customer and provider operations are reviewed together", lead: "Where they affect each other.", body: ["Area and service demand is analysed against provider availability."] },
        { title: "Money lines stay separate", lead: "Revenue, company collections, and provider payable amounts.", body: ["Do not add them into one number."] },
        { title: "Manager performance is measured against the defined KPIs", lead: "Serious issues are escalated without unnecessary delay.", body: ["Decisions use the available data and the documented information."] },
        { title: "Confidentiality", lead: "Company, customer, and provider information is protected.", body: ["Reports are used for decisions and improvement, not only for filing."] },
        { title: "Business indicators are not automatic employee failures", lead: "Lead growth, booking growth, eligible conversion, cancellation rate, revenue growth, collections, provider growth, coverage, area performance, service performance, customer retention, and the provider payment position.", body: ["Demand, marketing, pricing, provider availability, and market conditions can move these. Watch them. Do not treat them as a person failing without looking."] },
        { title: "Improvement", lead: "Keep identifying ways to improve efficiency, service quality, productivity, and operational control.", body: ["You watch whether the written steps are followed, using the manager reports and the checks. When the same mistake returns, you correct it. When a written step itself is wrong, you get the change approved and then you use the new step."] }
      ],
      kpis: hooKpis,
      escalations: [
        { title: "A manager report is late, thin, or conflicts with the data", lead: "Send it back. Do not patch it yourself and move on.", body: ["If it happens again, that is a manager performance issue with an owner and a deadline."], points: [{ title: "You send to", why: "The manager first. Higher management if it continues or if the figures were knowingly wrong." }] },
        { title: "The issue is above operations", lead: "Policy, serious reputational or financial risk, or a decision this role must not make.", body: ["Facts, status, and what you need decided."], points: [{ title: "You send to", why: "Higher management, without sitting on it." }] },
        { title: "A manager is on leave and the inbox has no deputy", lead: "Write one name that morning. Keep the inbox closed until you do.", body: ["Customer leads: one Customer Experience Executive. Provider leads: one Provider Support person. You do not assign the leads yourself after the name is written."], points: [{ title: "You write", why: "The deputy's name and the dates, in the admin panel, before the inbox opens." }] },
        { title: "A price, a booking advance, a discount, or a refund cannot wait", lead: "This arrives when the Customer Experience Manager is away and the deputy is not allowed to decide it.", body: ["Use only an amount the company has already written. If nothing is written, send it upward the same day. Do not invent a number."], points: [{ title: "You decide, or you send upward", why: "The same day." }] },
        { title: "A manager is repeatedly doing the executive job and skipping the system", lead: "That is a process miss, including when you are the one doing it.", body: ["Put the management work back in writing."], points: [{ title: "You correct", why: "The manager. If you are the one duplicating them, stop and write the oversight you skipped." }] }
      ]
    }
  });

  ["hoo", "cxm", "cxe", "pom", "poe"].forEach(function (id) {
    const index = G.roleIds.indexOf(id);
    if (index !== -1) G.roleIds.splice(index, 1);
    if (G.roles[id]) G.roleIds.push(id);
  });

  G.workflows.operations = {
    id: "operations",
    name: "Operations workflow",
    kicker: "How Operations works",
    lede: "New leads sit in one inbox. The Customer Experience Manager assigns customer and future-customer leads. The Provider Experience Manager assigns service provider leads. The people who do the work handle only the leads that have their name, in the written order. If a manager is on leave, one named deputy assigns. Head of Operations names that deputy if the name is missing, and does not take the queue.",
    hero: "org-operations.png",
    story: "E-Myth says the technician does the work, the manager runs the system, and the entrepreneur sees the whole. In Operations the people who do the work are the Customer Experience Executive and Provider Support. The managers are Customer Experience and Provider Experience. Head of Operations sees both systems and does not become either manager.",
    rules: [
      "Follow the written process. Do not invent a personal one.",
      "Nobody takes a lead from the inbox. The assigner writes the type, the priority, and one owner. Hot before warm before cold. Onboard now before write down, do later. Oldest first inside the same band.",
      "Before a manager goes on leave, one deputy and the dates are written in the admin panel. If that name is missing, the Head of Operations writes it that morning and the inbox stays closed until then. The deputy assigns. The deputy does not approve a price, a booking advance, a discount, or a refund.",
      "A booking is fully completed only when the service meets the written standard, the call after the service is written, the customer payment is written, and the provider settlement is written.",
      "No response is not a completed follow-up. A missed call still gets a message, and the last message on the lead is from Panun Kaergar.",
      "A provider is not active until onboarding is complete, and a confirmation is not real until the provider has confirmed.",
      "Only approved prices are promised. A market price is not a company price.",
      "The manager’s result is that the team followed the system. Head of Operations does not do the managers’ daily work.",
      "Critical controls stay at 100%: follow-ups, mandatory data, recordings, payment and status, provider confirmation, documents, and training."
    ],
    sectionHeads: {
      rules: { file: "sec-rules.png", kicker: "Do not mix", title: "Hard rules", lede: "The manager assigns. The executive works the leads with their name. Head of Operations names a missing deputy and does not take the queue. If those three mix, hot leads get missed." },
      calendar: { file: "sec-when.png", kicker: "Rhythm", title: "When this runs", lede: "Clear the open list every day. Review both managers every week. Close the month against the previous month." },
      boundaries: { file: "sec-owns.png", kicker: "Boundaries", title: "Who owns which result", lede: "Each box has one result. The manager does not become the executive. Head of Operations does not become the manager." },
      paths: { file: "sec-how.png", kicker: "The paths", title: "The three Operations paths", lede: "An incoming lead, a provider, or the operating review that looks at both. Each path has a start, a done, and a stop." },
      seats: { file: "sec-do.png", kicker: "The seats", title: "What each Operations person does", lede: "One result each. Open the role for the steps, the standards, and how the number is counted." }
    },
    calendar: [
      { when: "Every working day", what: "The Customer Experience Manager assigns the customer inbox, hot before warm before cold. The Provider Experience Manager assigns the provider inbox, onboard now before write down, do later. Each executive works only the leads with their name, in that order. If a manager is on leave, the named deputy assigns. If no deputy was named, Head of Operations writes one name before the inbox opens." },
      { when: "Same time every week", what: "Both managers submit reports from the records. Head of Operations reviews KPIs, demand against coverage, money, and last week’s actions, then assigns what is still open." },
      { when: "Month close", what: "Compare this month with the previous month. Separate collections, revenue, and provider pay. Name repeated causes. Set next month’s priorities." }
    ],
    boundaries: [
      { who: "Customer Experience Executive", does: "Works only the leads with their name, hot before warm before cold. Writes the customer in full, books only after the approved price and the policy advance, confirms both sides before the visit, and settles both sides after the work. Returns a service provider the same day. Does not open the inbox, unless they are the named deputy, and then they only assign." },
      { who: "Customer Experience Manager", does: "Assigns every customer and future-customer lead, moves service provider leads to the provider inbox the same day, and names a deputy before leave." },
      { who: "Provider Support", does: "Works only the provider leads with their name. Onboard now before write down, do later. Confirms availability before anyone tells the customer yes. Does not open the inbox, unless they are the named deputy, and then they only assign." },
      { who: "Provider Experience Manager", does: "Assigns every service provider lead, checks the files and the coverage, and names a deputy before leave." },
      { who: "Head of Operations", does: "Watches both managers. If a manager is away and no deputy was named, writes one name that morning and does not take the queue." },
      { who: "Growth", does: "Creates the enquiry. Operations does not invent demand." },
      { who: "Finance", does: "Prices and pays. Operations does not invent prices." }
    ],
    paths: [
      {
        id: "customer-job",
        title: "An incoming lead",
        art: "org-customer.png",
        why: "Use this for every lead that reaches Operations. The type is named before any booking starts.",
        when: "A lead arrives by phone call, WhatsApp, the website, or social media.",
        input: "A new lead, or an open lead from the previous shift.",
        output: "A service provider lead with Provider Support, a future customer with a next time, or a booking that is finished only when the service meets the written standard, the customer payment and the provider settlement are both in the admin panel, and the last message is from Panun Kaergar.",
        fail: "Do not let anyone pick from the inbox. Do not start a customer path for a service provider. Do not work a cold lead while a hot lead has no owner. Do not book before the customer accepts the approved price and the booking advance in the payment policy is taken. Do not leave a call, including a missed call, without a message. Do not mark the booking finished while either payment is open. Do not leave a visit unless both sides have confirmed they will be there.",
        steps: [
          { who: "Customer Experience Manager", does: "Marks the type, the priority, and one owner the same day. Hot, warm, or cold. Moves a service provider lead to the provider inbox. If this manager is on leave, the named deputy does this and does not keep the lead." },
          { who: "Customer Experience Executive", does: "Opens only the leads with their name. Hot first, then warm, then cold. Writes the full details. Gives the price from the approved price file. Returns a service provider if one was assigned by mistake." },
          { who: "Customer Experience Executive", does: "If the customer agrees, takes the booking advance in the company payment policy, gets a provider's yes, and creates the booking in the admin panel." },
          { who: "Customer Experience Executive", does: "Before the work starts, confirms the customer will be at the address, the provider will be at the address, and both have been told the same service charge. Sends each of them that message." },
          { who: "Customer Experience Executive", does: "After the work, checks the service against the written standard, settles the customer and the provider, writes both in the admin panel, and sends the call brief. The last message is from Panun Kaergar." },
          { who: "Customer Experience Manager", does: "Checks the admin panel. Corrects misses. Handles a price, a booking advance, a discount, or a refund the executive is not allowed to decide." }
        ]
      },
      {
        id: "provider",
        title: "A provider",
        art: "org-provider.png",
        why: "Use this after a lead has been moved to the provider inbox, or when a live provider’s file, training, or availability changes.",
        when: "The customer assigner marks a lead as a service provider, or a provider’s documents, area, or training change.",
        input: "A service provider lead in the provider inbox, or a change to a provider already on file.",
        output: "An onboard-now provider who is active only after a complete file, or a write-down-later lead that is recorded and messaged and not onboarded ahead of them.",
        fail: "Do not let Provider Support pick from the inbox. Do not onboard a later lead while an onboard-now lead is waiting. Do not activate before onboarding is complete. Do not confirm a provider who has not confirmed. Do not treat a market price as our price.",
        steps: [
          { who: "Provider Experience Manager", does: "Writes onboard now or write down, do later, and one owner. If this manager is on leave, the named deputy does this and does not keep the lead." },
          { who: "Provider Support", does: "Opens only the leads with their name. Briefs an onboard-now provider and continues onboarding. Records a later lead, sends the message, and does not onboard it while an onboard-now lead is waiting." },
          { who: "Provider Support", does: "Finishes training, updates the panel and the offline file, then activates." },
          { who: "Provider Experience Manager", does: "Checks the file before treating activation as real, and names coverage gaps." },
          { who: "Customer Experience Executive", does: "Uses only a confirmed provider on a booking." },
          { who: "Head of Operations", does: "Sees coverage against demand, not a provider list on its own." }
        ]
      },
      {
        id: "review",
        title: "The operating review",
        art: "org-operations.png",
        why: "Use this every day, week, and month so the two systems are actually being managed.",
        when: "Every working day for exceptions. Every week for both reports. Every month for the comparison.",
        input: "Manager reports, KPIs, and the records behind them.",
        output: "Actions with an owner and a deadline, and a management report that matches the data.",
        fail: "Do not accept someone saying everything is fine. Do not hide a repeated problem. Do not turn a business indicator into a person failing without looking at demand and capacity. Do not leave an inbox open when the manager is away and no deputy is named.",
        steps: [
          { who: "Both managers", does: "Submit what the records show, including unassigned hot leads, cold leads worked ahead of hot ones, and whether a deputy is named for any coming leave." },
          { who: "Head of Operations", does: "Checks completeness, compares the important figures, and looks at demand against coverage." },
          { who: "Head of Operations", does: "Assigns a corrective action: owner, action, deadline." },
          { who: "The named manager", does: "Does the action and shows the result in the record." },
          { who: "Head of Operations", does: "Verifies the result before the action is closed." }
        ]
      }
    ]
  };

  const sceneArt = {
    cxe: {
      lanes: ["ops-order.png", "role-cxe.png", "ops-visit.png"],
      responsibilities: ["ops-order.png", "ops-return.png", "role-cxe.png", "ops-settle.png", "ops-message.png", "ops-visit.png"],
      handoffs: ["role-cxm.png", "ops-order.png", "role-poe.png", "ops-return.png", "ops-deputy.png", "ops-visit.png", "role-cxm.png", "ops-settle.png"],
      workflow: ["ops-order.png", "ops-deputy.png", "role-cxe.png", "ops-settle.png", "ops-visit.png", "ops-settle.png", "ops-message.png"],
      reporting: ["role-cxe.png", "ops-visit.png", "ops-settle.png"],
      standards: ["ops-order.png", "ops-return.png", "role-cxe.png", "ops-message.png", "ops-settle.png", "ops-visit.png", "ops-settle.png", "ops-order.png", "ops-deputy.png"],
      escalations: ["ops-return.png", "ops-settle.png", "role-poe.png", "ops-visit.png", "ops-deputy.png"]
    },
    cxm: {
      lanes: ["ops-inbox.png", "role-cxm.png", "ops-deputy.png"],
      responsibilities: ["ops-order.png", "role-cxm.png", "ops-settle.png", "ops-message.png", "ops-deputy.png", "role-hoo.png"],
      handoffs: ["role-cxe.png", "ops-message.png", "role-pom.png", "ops-deputy.png", "role-hoo.png", "ops-order.png"],
      workflow: ["ops-inbox.png", "ops-message.png", "ops-order.png", "ops-deputy.png", "role-hoo.png", "ops-deputy.png"],
      reporting: ["role-cxm.png", "ops-settle.png"],
      standards: ["ops-inbox.png", "role-cxm.png", "ops-order.png", "ops-message.png", "role-cxe.png", "ops-settle.png", "role-hoo.png", "ops-message.png", "ops-deputy.png", "role-cxm.png", "ops-order.png", "ops-deputy.png"],
      escalations: ["role-hoo.png", "role-pom.png", "ops-deputy.png"]
    },
    poe: {
      lanes: ["role-poe.png", "ops-order.png", "ops-settle.png"],
      responsibilities: ["role-poe.png", "ops-message.png", "ops-order.png", "ops-visit.png", "ops-settle.png", "role-pom.png"],
      handoffs: ["role-pom.png", "role-cxe.png", "ops-visit.png", "role-poe.png", "ops-settle.png"],
      workflow: ["role-poe.png", "ops-order.png", "ops-visit.png", "ops-message.png", "ops-deputy.png", "role-pom.png", "ops-settle.png", "ops-message.png"],
      reporting: ["ops-message.png", "ops-order.png"],
      standards: ["role-poe.png", "ops-message.png", "role-poe.png", "ops-order.png", "ops-message.png", "ops-visit.png", "ops-visit.png", "ops-deputy.png", "ops-message.png", "role-poe.png", "ops-settle.png", "role-pom.png", "ops-deputy.png"],
      escalations: ["role-pom.png", "ops-settle.png", "role-cxe.png"]
    },
    pom: {
      lanes: ["ops-inbox.png", "role-pom.png", "role-cxm.png"],
      responsibilities: ["ops-order.png", "role-pom.png", "ops-settle.png", "ops-deputy.png", "role-cxm.png", "role-hoo.png"],
      handoffs: ["role-poe.png", "role-cxm.png", "ops-settle.png", "ops-order.png", "role-hoo.png"],
      workflow: ["ops-inbox.png", "role-pom.png", "ops-settle.png", "ops-deputy.png", "role-cxm.png", "role-hoo.png", "ops-deputy.png"],
      reporting: ["role-pom.png", "ops-settle.png"],
      standards: ["ops-inbox.png", "role-pom.png", "ops-order.png", "role-poe.png", "ops-visit.png", "ops-settle.png", "ops-deputy.png", "role-cxm.png", "ops-message.png", "role-hoo.png", "ops-order.png", "role-pom.png", "ops-deputy.png"],
      escalations: ["role-hoo.png", "ops-order.png", "ops-settle.png"]
    },
    hoo: {
      lanes: ["role-hoo.png", "ops-order.png", "ops-deputy.png"],
      responsibilities: ["role-cxm.png", "role-pom.png", "role-hoo.png", "ops-settle.png", "ops-deputy.png", "ops-order.png"],
      handoffs: ["role-cxm.png", "role-pom.png", "ops-deputy.png", "role-hoo.png", "ops-message.png"],
      workflow: ["ops-deputy.png", "role-hoo.png", "ops-settle.png", "ops-order.png"],
      reporting: ["role-hoo.png", "ops-settle.png"],
      standards: ["role-hoo.png", "ops-deputy.png", "role-hoo.png", "ops-order.png", "ops-visit.png", "ops-settle.png", "role-cxm.png", "ops-deputy.png", "ops-order.png", "role-hoo.png"],
      escalations: ["role-cxm.png", "ops-settle.png", "ops-deputy.png", "ops-inbox.png", "role-hoo.png"]
    }
  };
  Object.keys(sceneArt).forEach(function (id) {
    const role = G.roles[id];
    const map = sceneArt[id];
    if (!role || !role.detailed) return;
    (map.lanes || []).forEach(function (file, index) {
      if (role.lanes[index]) role.lanes[index].art = file;
    });
    ["responsibilities", "handoffs", "workflow", "reporting", "standards", "escalations"].forEach(function (key) {
      const list = role.detailed[key] || [];
      (map[key] || []).forEach(function (file, index) {
        if (list[index]) list[index].art = file;
      });
    });
  });

  const opsTab = (G.workflowTabs || []).find(function (tab) { return tab.id === "operations"; });
  if (opsTab) delete opsTab.soon;
})();

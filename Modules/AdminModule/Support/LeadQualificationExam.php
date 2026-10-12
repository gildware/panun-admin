<?php

namespace Modules\AdminModule\Support;

/**
 * Examiner questions for the lead course. Each prompt describes a situation.
 * Two options is a single choice. Three or four options is a multiple choice.
 * Exactly one option is correct.
 */
class LeadQualificationExam
{
    /**
     * @return array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>
     */
    public static function forLesson(string $section, string $lesson): array
    {
        return self::all()[$section][$lesson] ?? [];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    public static function all(): array
    {
        return array_replace(
            self::reading(),
            self::job(),
            self::sources(),
            self::shift(),
            self::types(),
            self::leadPage(),
            self::workflow(),
            self::unknown(),
            self::customer(),
            self::provider(),
            self::future(),
            self::invalid(),
            self::certification(),
        );
    }

    /**
     * @param  array<int, string>  $options
     * @return array{prompt: string, options: array<int, string>, correct: int, explanation: string}
     */
    private static function q(string $prompt, array $options, int $correct, string $why): array
    {
        return [
            'prompt' => $prompt,
            'options' => $options,
            'correct' => $correct,
            'explanation' => $why,
        ];
    }

    /**
     * Single choice: two described answers, one of them correct.
     *
     * @return array{prompt: string, options: array<int, string>, correct: int, explanation: string}
     */
    private static function one(string $prompt, string $right, string $wrong, string $why, bool $rightFirst = false): array
    {
        return self::q(
            $prompt,
            $rightFirst ? [$right, $wrong] : [$wrong, $right],
            $rightFirst ? 0 : 1,
            $why,
        );
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function reading(): array
    {
        return [
            'How to read this guide' => [
                'Terms we use' => [
                    self::q(
                        'A person messages Panun Kaergar because a kitchen tap is leaking and they want someone sent to the house. What do we call this person on the lead?',
                        ['Provider — they want to join and receive jobs', 'Customer — they need a home service from Panun Kaergar', 'Invalid — a leak is outside the service area', 'Future customer — any plumbing request is saved for later'],
                        1,
                        'A Customer needs a home service such as plumbing, electrical, or cleaning. A Provider wants to join and receive jobs.',
                    ),
                    self::q(
                        'Someone says they are an electrician and want to work with Panun Kaergar and receive jobs. They are not booking a repair for their own house. What do we call this person on the lead?',
                        ['Customer', 'Unknown', 'Provider', 'Invalid'],
                        2,
                        'A Provider wants to join as a service partner and receive jobs. Do not run the customer booking flow for them.',
                    ),
                    self::one(
                        'An Instagram DM says only "Hi, I called you earlier." No service is mentioned. You have not spoken to them yet. What type do you set?',
                        'Unknown. Call them and ask what they need before you change the type.',
                        'Customer. A message means they need a home service, so book it.',
                        'Unknown means there is not enough information yet. Do not guess Customer from a greeting.',
                    ),
                    self::q(
                        'You called an Unknown lead three times. Each time there was no answer, you sent WhatsApp, and you logged Add follow-up. What is the correct close?',
                        ['Mark as Invalid, reason "Did not Know About Enquiry"', 'Mark as Customer, then Change Status → Cancel → "No Response From Customer"', 'Hero → Mark as Non Responsive. The type stays Unknown', 'Leave the lead open and keep calling every day'],
                        2,
                        'Non Responsive is the close for no answer after 3 attempts. The type stays the same. It is not Invalid and it is not Cancel.',
                    ),
                    self::one(
                        'You have spoken to a Customer. They refuse the price after the provider explained the job. How do you close?',
                        'Change Status → Cancel, with the reason, because you have spoken to them.',
                        'Mark as Non Responsive, because they did not accept the booking.',
                        'Cancel is for a decision after you have spoken. Non Responsive is only for no answer after 3 attempts.',
                    ),
                    self::q(
                        'A lead can show Open or Closed on the queue. Which description matches that state?',
                        [
                            'Unknown, Customer, and Provider stay Open until Non Responsive, or until Customer/Provider is Completed or Cancelled. Invalid and Future are always Closed.',
                            'Every lead stays Open until you personally feel the work is finished.',
                            'Non Responsive changes the type to Invalid and keeps the lead Open.',
                            'Future customer stays Open until a booking is created.',
                        ],
                        0,
                        'Open/Closed is the queue state. Non Responsive keeps the type and shows Closed. Future and Invalid are always Closed.',
                    ),
                    self::one(
                        'You want the next shift to see an internal note and mention a teammate. Where does that go?',
                        'Comments. You can @mention staff and pin a handover note. Comments do not replace a follow-up row.',
                        'Initial remarks only. Remarks are the place for internal arguments and @mentions.',
                        'Initial remarks are the customer-facing summary. Comments are internal team notes.',
                    ),
                    self::q(
                        'The panel will not let you change an Unknown lead to Customer until the required outbound call is done. What is that called?',
                        ['A soft gate — tick confirm and continue anyway', 'A hard gate — the panel blocks the action until the step is done', 'Mark as Non Responsive', 'A Follow-up row'],
                        1,
                        'A hard gate blocks the action. A soft gate only shows a confirm modal, and you tick it only if the work is truly done.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function job(): array
    {
        $s = 'Your job';

        return [
            $s => [
                'Listen first, note on paper' => [
                    self::one(
                        'The customer is describing a kitchen leak, the address, and tomorrow morning. You are still on the call. What do you do?',
                        'Write the facts on paper. Update the panel after you hang up.',
                        'Type the remarks in the panel while they are speaking so nothing is lost.',
                        'On the call you listen and write notes. Panel typing makes you miss details and the customer hears the keyboard.',
                    ),
                    self::q(
                        'Before you hang up a customer call, what must you confirm aloud?',
                        [
                            'Only your own name',
                            'Service, address, date/time, and the next step you will take',
                            'The provider group message, which you post during the call',
                            'Nothing. Memory is enough until the end of the shift',
                        ],
                        1,
                        'Repeat the service, address, time, and the next step before you hang up. Then update the panel from your notes.',
                    ),
                ],
                'Live calls & same-day response' => [
                    self::one(
                        'You are five minutes into scanning follow-ups when the business phone rings. What do you do?',
                        'Answer the phone now. Finish that call in the panel and on WhatsApp, then return to the scan.',
                        'Let it ring and finish the follow-up scan first.',
                        'A live ringing phone beats queue work. Every enquiry still gets a touch the same day.',
                    ),
                    self::q(
                        'A new enquiry arrives at 4 PM. You are busy, and the shift ends at 6 PM. What is the standard?',
                        [
                            'Leave it for the next shift with no lead and no message',
                            'Touch it today. Do not leave an enquiry overnight with no contact',
                            'Mark it Invalid so it leaves the queue',
                            'Reply only if they call a second time',
                        ],
                        1,
                        'No enquiry waits overnight without contact. Same-day response is the standard.',
                    ),
                ],
                'Panel update — team handover' => [
                    self::one(
                        'You replied in WhatsApp and remember the promise in your head. The next shift starts in an hour. What must the lead show?',
                        'Handled By, remarks, status, and Followup On or a real close. Chat tags alone are not a handover.',
                        'Nothing else. The WhatsApp reply is the handover.',
                        'The next person cannot see a promise that exists only in your memory. The panel has to say what happened and what is next.',
                    ),
                    self::q(
                        'Your shift ends in an hour. Which handover is complete enough for the next person?',
                        [
                            'A pinned comment that says "busy" and an empty lead',
                            'Remarks with what was said and promised, Handled By set, and Followup On if the lead is still open',
                            'The chat marked open, with no assignee',
                            'A follow-up row with no remarks and no next date',
                        ],
                        1,
                        'Handover means the next shift can continue without calling you. Remarks, owner, and the next date or a close are required.',
                    ),
                ],
                'Classify immediately after call' => [
                    self::one(
                        'An Unknown lead answers. They say they need a plumber tomorrow in Rajbagh. The call is ending. What happens to the type?',
                        'Change it to exactly one type, Customer, before you hang up. Unknown is only until this call succeeds.',
                        'Leave it Unknown and classify tomorrow when you have more time.',
                        'A successful call ends Unknown. Pick the one type you now know, on that same call.',
                    ),
                    self::q(
                        'They answered and said "I am a plumber and I want to register." What do you set before the call ends?',
                        ['Unknown, until documents arrive', 'Customer, because they mentioned plumbing', 'Provider, and collect trade, area, and phone on this call', 'Invalid, because they are not booking a job'],
                        2,
                        'They want to receive jobs, so the type is Provider. Collect the onboarding basics on the same call.',
                    ),
                ],
                'WhatsApp & clear messages' => [
                    self::one(
                        'You finished a customer call: kitchen tap, Rajbagh, tomorrow 10 AM. When do you send WhatsApp?',
                        'The same minute as the panel update, with the service, address, time, and the next step.',
                        'At the end of the shift, if you remember.',
                        'WhatsApp after every call tells them Panun Kaergar is working on the enquiry. Do not go silent.',
                    ),
                    self::q(
                        'A provider-group post for a direct booking must include which details?',
                        [
                            'Only "need plumber"',
                            'Lead ID, service, problem, full address, and date/time',
                            'The customer phone number alone',
                            'A voice note with no text',
                        ],
                        1,
                        'Vague group posts get ignored. Lead ID goes on top, then the job details a provider can act on.',
                    ),
                ],
                'Finish one lead fully first' => [
                    self::one(
                        'Lead 1 has no remarks and no WhatsApp yet. A new DM arrives. What do you do?',
                        'Finish lead 1: panel, WhatsApp, and Followup On or a close. Then open the next lead.',
                        'Open the new DM and leave lead 1 half done.',
                        'One lead is finished before the next one starts. A half-done lead is invisible to the next shift.',
                    ),
                    self::q(
                        'When is a lead "finished" enough for you to leave it?',
                        [
                            'When you have thought about it',
                            'When the panel, WhatsApp, and Followup On are done, or the lead is properly closed',
                            'When the chat reply is sent, even if the lead is empty',
                            'When you have dialled once',
                        ],
                        1,
                        'Panel plus WhatsApp plus a next date, or a real close, is the end of that lead. Then you move on.',
                    ),
                ],
                'Work by priority — not FIFO' => [
                    self::q(
                        'Four things are waiting. Which do you handle first?',
                        [
                            'The oldest Facebook comment, because it arrived first',
                            'The ringing phone and any emergency, then a hot booking where the customer is waiting',
                            'Whichever lead is at the top of the All leads list',
                            'The lead you find most interesting',
                        ],
                        1,
                        'Work by priority, not first-in-first-out. A live call and an emergency beat old queue items.',
                    ),
                    self::one(
                        'A non-urgent DM arrives while you are on an emergency no-power call. What does the DM get?',
                        'A short acknowledgement and a lead row, after the emergency is handled. Not a full qualification in the middle of the emergency.',
                        'A full qualification immediately, and the emergency call can wait.',
                        'Emergency and live calls interrupt. A non-urgent DM gets a quick touch, not the whole process, until the emergency is done.',
                    ),
                ],
                'No pickup — 3 attempts rule' => [
                    self::q(
                        'Attempt 1 did not answer. What do you record before you stop for the day?',
                        [
                            'Nothing. Try again in your head tomorrow',
                            'WhatsApp the same day, Add follow-up → Taken → Call, remarks "Attempt 1/3", and a Followup On date',
                            'Cancel the lead for no response',
                            'Mark as Invalid',
                        ],
                        1,
                        'Every missed attempt gets WhatsApp and a follow-up row. The next call is on Followup On. Stop at 3.',
                    ),
                    self::one(
                        'All 3 attempts failed on an open Customer lead. They never answered. What is the close?',
                        'Mark as Non Responsive. The type stays Customer. Do not Cancel.',
                        'Change Status → Cancel → "No Response From Customer".',
                        'No answer is Non Responsive. Cancel is only after you have spoken to them.',
                    ),
                ],
                'Professional close — every outcome' => [
                    self::q(
                        'The person answered. Which close matches the situation?',
                        [
                            'No service today, they are saving the number → Future customer, with the reason, and a warm WhatsApp',
                            'No service today → Invalid',
                            'They need a plumber but you are busy → Invalid',
                            'They did not answer → Future customer',
                        ],
                        0,
                        'Future customer is a valid close when they do not need service today. Invalid is only a wrong service or outside the area, after they have answered.',
                    ),
                    self::one(
                        'You cannot help with car AC repair in Jammu. They are on the phone. How do you close?',
                        'Say so politely, name what Panun Kaergar does offer, set Invalid with the exact request and place, and send the polite WhatsApp.',
                        'Hang up. Invalid leads do not get a message.',
                        'Invalid is still a professional close. Remarks must say exactly what they asked for.',
                    ),
                ],
                'Customer-centric — show empathy' => [
                    self::one(
                        'The customer is worried because the house has no power and a child is at home. How do you speak?',
                        'Acknowledge the worry, use their name if you have it, and say the next step and when they will hear from you.',
                        'Skip the greeting and post the job in the group while they are still explaining.',
                        'Acknowledge first, then the next step and the time. They should know you understood the situation.',
                    ),
                    self::q(
                        'A provider search is taking longer than 10 minutes. What does the customer get?',
                        [
                            'Silence until a provider says yes',
                            'A WhatsApp update that you are still checking the slot',
                            'A Cancel, so the lead is no longer your problem',
                            'A promise of a visit time before any provider is confirmed',
                        ],
                        1,
                        'Do not go silent during a long search, and do not promise a visit time before a provider is confirmed.',
                    ),
                ],
                'Follow-up date — real availability' => [
                    self::one(
                        'The customer asked you to call on Thursday at 2 PM, when they are home. What do you set?',
                        'Followup On for Thursday at 2 PM. That is when you will actually reach them.',
                        'Followup On for tomorrow morning, because that is your usual time.',
                        'The follow-up date is when the person can answer, not a random time that is convenient only for you.',
                    ),
                    self::q(
                        'An open lead has no Followup On date. What is wrong?',
                        [
                            'Nothing. Open leads do not need a date',
                            'The next shift will not know when to call, so the lead is lost',
                            'The lead should be marked Invalid',
                            'Comments will remind the team automatically',
                        ],
                        1,
                        'An open lead without Followup On disappears. Set the date before you leave it.',
                    ),
                ],
                'Provider search & chase' => [
                    self::q(
                        'You posted a Path A job. Ten minutes pass and nobody replies in the group. What do you do?',
                        [
                            'Wait until the end of the day',
                            'Call nearby providers yourself and tell the customer you are still checking',
                            'Create Booking with no provider',
                            'Cancel for no response',
                        ],
                        1,
                        'The 10-minute group window is the start. If nobody replies, you chase providers and keep the customer updated.',
                    ),
                    self::one(
                        'A provider says YES and is free at the agreed time. The customer is ready. What happens before Create Booking?',
                        'Collect the ₹100 advance, then Create Booking.',
                        'Create Booking first and collect the money after the job.',
                        '₹100 is collected before Create Booking on Path A and Path B.',
                    ),
                ],
                'Your goal — get them served' => [
                    self::one(
                        'A Customer lead is qualified, Path A, and a provider is confirmed. What is the goal of the remaining steps?',
                        'Get the customer served: ₹100, Create Booking, and confirmation to both sides.',
                        'Close the lead as Future customer so the queue is shorter.',
                        'The job is to complete the booking, not to close the lead early.',
                    ),
                    self::q(
                        'You are about to log out. Which outcome means you actually did the job on that lead?',
                        [
                            'The chat was replied to, and the panel is empty',
                            'The customer has a confirmed booking, or an honest close they understand',
                            'The lead was marked Invalid to clear the list',
                            'The follow-up was rescheduled with no remarks',
                        ],
                        1,
                        'Success is a served customer or a clear, honest close. An empty panel is not a finished job.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function sources(): array
    {
        $s = 'Where leads come from';

        return [
            $s => [
                'Shift checklist' => [
                    self::q(
                        'You sit down at the start of a shift. Which item do you do before opening Facebook?',
                        [
                            'Answer the ringing phone if it rings, then check WhatsApp unread and Today\'s follow-ups',
                            'Read Facebook comments first, because social is the main source',
                            'Create a new lead for every old closed booking',
                            'Log out of WhatsApp so AI can handle the queue',
                        ],
                        0,
                        'The checklist starts with the ringing phone, then unread WhatsApp, Today\'s follow-ups, web and app lists, social, and the Human support tab.',
                    ),
                    self::one(
                        'The Human support tab has an AI escalation waiting at the start of the shift. When do you look at it?',
                        'At the start of the shift, as part of the checklist. It is not "when I have time".',
                        'Only at the end of the day, after every other channel.',
                        'AI escalations are same-day work. The checklist includes the Human support tab at the start.',
                    ),
                ],
                'Facebook, Instagram & YouTube — comments & DMs' => [
                    self::one(
                        'A Facebook comment says "Need plumber in Rajbagh tomorrow." These comments do not auto-create leads. What must you do the same day?',
                        'Create a manual lead, Source = Facebook, and paste the exact text into remarks. If you have no phone, ask for it in the reply first.',
                        'Reply on Facebook only. A comment does not need a lead.',
                        'If it is not in Leads with source, phone, and remarks, the next shift cannot see it.',
                    ),
                    self::q(
                        'The DM says only "Hi". You have a phone number. What type do you create?',
                        ['Customer', 'Invalid', 'Unknown, and call the same day', 'Future customer'],
                        2,
                        'A vague message stays Unknown until they answer and you know what they want. Create the lead the same day.',
                    ),
                ],
                'Direct calls & missed calls' => [
                    self::one(
                        'The business phone shows a missed call. What is the same-day action?',
                        'Create or open the lead, call back, and send WhatsApp within 5 minutes if they do not answer.',
                        'Check missed calls only at the end of the shift.',
                        'A missed call is a lead. WhatsApp goes out quickly so they know you saw it.',
                    ),
                    self::q(
                        'Someone calls 8899881555 while you are typing a remark. What wins?',
                        ['Finish the remark, then call them back later', 'Answer the live call first', 'Mark the current lead Invalid and then answer', 'Send them to voicemail and keep working the oldest DM'],
                        1,
                        'A live call beats panel typing. Answer, take notes, then finish the panel and WhatsApp.',
                    ),
                ],
                'WhatsApp — human numbers (non-AI)' => [
                    self::q(
                        'You reply in a human WhatsApp chat and move to the next lead. What did you miss if the thread has no assignee?',
                        [
                            'Nothing. A reply is enough',
                            'Assignee, chat status, and tags on the thread, plus the linked lead: Handled By, remarks, and Followup On',
                            'Only a Lead ID tag. The panel can wait until tomorrow',
                            'Close the chat with no lead, because WhatsApp stores the history',
                        ],
                        1,
                        'Chat tags are not a handover. The thread and the lead both have to show who owns it and what happens next.',
                    ),
                    self::one(
                        'The same phone number already has an open lead. A new WhatsApp arrives. What do you do?',
                        'Open the existing lead. Do not create a duplicate.',
                        'Add New Lead so the new message has its own row.',
                        'One open lead per phone. The panel warns you. Open the existing lead and update it.',
                    ),
                ],
                'AI WhatsApp chat (in panel)' => [
                    self::one(
                        'A customer chatted with the WhatsApp AI and the lead shows Continue with AI booking. What do you read before you continue?',
                        'The banner status and View AI chat. Confirm any gap in phone, service, address, or time with the customer.',
                        'Ignore the transcript and Create Booking manually as well as the AI booking.',
                        'Read the AI chat first. If an AI booking is already linked, do not also Create Booking by hand.',
                    ),
                    self::q(
                        'The AI path is stuck and the draft booking cannot be finished. What do you do?',
                        [
                            'Leave the banner for the next shift with no note',
                            'Take over the chat, fix the lead fields, and use Create Booking manually',
                            'Mark the lead Invalid because AI failed',
                            'Post the raw AI transcript in the provider group with no address',
                        ],
                        1,
                        'A stuck AI booking is taken over by a person. Fix the fields and book manually. Do not close it as Invalid.',
                    ),
                ],
                'Website — customer booking' => [
                    self::one(
                        'A web booking arrives: "Electrician — MCB tripping, Bemina." The address is only the area name. What do you do the same day?',
                        'Call and confirm the full address and time. Decide Path A or B on the call. Update the panel and send WhatsApp before any group post.',
                        'Post "Bemina electrician" in the provider group immediately.',
                        'Auto-created bookings still need a call when the address or time is incomplete. Do not post a vague job.',
                    ),
                    self::q(
                        'The web form already chose a service. The person has not answered your call. What type do you keep while you try?',
                        ['Change it to Invalid until they answer', 'Keep Customer if the form already made it a customer job, and do not post in the group', 'Change it to Unknown because web forms are always vague', 'Cancel it on the first missed call'],
                        1,
                        'A web booking that already names a customer job stays Customer. You still must speak to them before a group post. No answer uses the 3-attempt rule.',
                    ),
                ],
                'Website — provider request' => [
                    self::one(
                        'A website partner form says "Electrician, Srinagar south" and includes a phone number. They want to join, not book a repair. What flow do you open?',
                        'Provider onboarding. Call, explain the model, then send the agreement and document list.',
                        'Customer Path A. Post the electrician in the provider group as a job.',
                        'A partner application is a Provider lead. Do not run the customer booking flow.',
                    ),
                    self::q(
                        'The applicant answers and agrees to send documents by 10 August. What do you set before you leave the lead?',
                        [
                            'No date. They will send documents when they can',
                            'Followup On on the document deadline, and WhatsApp the agreement the same day',
                            'Mark as Non Responsive, because documents are not here yet',
                            'Create Booking for the applicant',
                        ],
                        1,
                        'The deadline is a real Followup On date. The agreement goes out the same day as the first call.',
                    ),
                ],
                'App — customer custom request' => [
                    self::one(
                        'A new app custom request appears while you are between leads. What is the same-day rule?',
                        'Open the request and the linked lead, then classify it or schedule the call. Do not leave it until tomorrow.',
                        'App requests are handled by the app team. Leave the row.',
                        'Web and app rows from today are same-day work. Open the linked lead and act.',
                    ),
                    self::q(
                        'The app request has a phone number and "need cleaning this week", but no full address. What do you do on the call?',
                        [
                            'Post in the provider group with no address',
                            'Collect the full address, the problem, and the time, then choose Path A or Path B',
                            'Mark Invalid because the form is short',
                            'Mark Future customer because the day is not named',
                        ],
                        1,
                        'A short form is still a customer job. The call fills the address and the path. Invalid is not for a missing field you can ask for.',
                    ),
                ],
                'Process flow' => [
                    self::q(
                        'A new enquiry arrives. What is the order?',
                        [
                            'Provider group post, then a call if someone complains',
                            'See it the same day, put it in Leads, call, classify after they answer, then panel and WhatsApp',
                            'Cancel it, then reopen it if they message again',
                            'Wait until the weekly report',
                        ],
                        1,
                        'Nothing is worked only inside Facebook or WhatsApp. It enters Leads, you speak to them, then the panel and WhatsApp match what you learned.',
                    ),
                    self::one(
                        'You handled the chat but never created a lead. Is the enquiry safe for the next shift?',
                        'No. If it is not in Leads with source, phone, and remarks, the team cannot see it.',
                        'Yes. The chat history is the record.',
                        'The lead is the record. A chat that is not linked to a lead is invisible on the next shift.',
                    ),
                ],
                'WhatsApp to customer' => [
                    self::one(
                        'You have a phone number from a Facebook DM and you created the lead. When do you send WhatsApp?',
                        'The same day, after the lead exists, with a clear next step. Set Followup On if the work is not finished.',
                        'Only after a booking ID exists.',
                        'WhatsApp is part of finishing the enquiry, not a reward for a completed booking.',
                    ),
                    self::q(
                        'You are sending WhatsApp after a real customer call. Which message is clear enough?',
                        [
                            '"Ok"',
                            '"As per our call — kitchen tap leak, Rajbagh, tomorrow 10 AM. We are finding a partner for that slot."',
                            '"We will see"',
                            'A blank message so they have your number',
                        ],
                        1,
                        'Name the service, place, time, and the next step. The customer should not have to guess what happens next.',
                    ),
                ],
                'Where leads come from — panel pages' => [
                    self::q(
                        'It is the start of the shift and you need the overdue queue. Where do you open Today\'s follow-ups?',
                        [
                            'Only inside a single WhatsApp chat',
                            'Dashboard, or Leads and bookings → Today\'s lead follow-ups',
                            'Provider admin → Add New Provider',
                            'The Facebook app',
                        ],
                        1,
                        'Today\'s follow-ups is the queue page. Overdue rows are highlighted there.',
                    ),
                    self::one(
                        'You need the list of AI chats that asked for a human. Which tab is that?',
                        'WhatsApp → Human support.',
                        'Leads → Add New Lead.',
                        'Human support is the AI escalation queue. It is checked at the start of the shift.',
                    ),
                ],
                'Where leads come from — remember and avoid' => [
                    self::q(
                        'An enquiry was handled only inside Facebook and never saved as a lead. Which habit from this section did you break?',
                        [
                            'Check Facebook only at the end of the day',
                            'If it is not in Leads with source, phone, and remarks, the team cannot see it',
                            'Create a duplicate lead when the same phone already has an open lead',
                            'Start the shift inside one chat and skip the other channels',
                        ],
                        1,
                        'Social, calls, WhatsApp, and web forms all have to land in Leads. A channel you never scan is a missed enquiry.',
                    ),
                    self::one(
                        'A web form arrived during a long call. When do you look at it?',
                        'On the next 30–60 minute scan, the same day. Do not leave it until you log out.',
                        'Tomorrow, because you were on a call when it arrived.',
                        'New rows are same-day work. The between-task scan exists so a form does not sit unseen.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function shift(): array
    {
        $s = 'Your shift — start, during, end';

        return [
            $s => [
                'Start of shift — before you take any lead' => [
                    self::one(
                        'You want to start inside one WhatsApp chat because it has unread messages. The follow-up scan is not done. What is the rule?',
                        'Finish the full channel scan first. A live ringing phone is the only thing that interrupts it.',
                        'Stay in that chat until it is empty, then scan if time remains.',
                        'The first 10–15 minutes are a full sweep. Starting in one chat hides yesterday\'s missed call.',
                    ),
                    self::q(
                        'After the sweep, in what order do you work the queue?',
                        [
                            'Oldest lead first, whatever the type',
                            'Emergency, then hot booking, then due today, then new messages',
                            'Facebook, then everything else',
                            'Whichever lead has the shortest remarks',
                        ],
                        1,
                        'Priority, not arrival order. Emergency and a customer waiting on a provider come before old DMs.',
                    ),
                ],
                'During shift — between tasks (every 30–60 minutes)' => [
                    self::q(
                        'You have just finished a lead properly. What is the mini-scan?',
                        [
                            'Only the lead you just closed',
                            'Missed calls, unread WhatsApp, Human support, web and app lists, social, and follow-ups due in the next hour',
                            'Nothing until the end of the shift',
                            'Create a new lead for every closed booking',
                        ],
                        1,
                        'New enquiries arrive while you work. Every 30–60 minutes, or between leads, scan those channels.',
                    ),
                    self::one(
                        'A new missed call appears on that scan. What do you do before you return to the queue?',
                        'Send WhatsApp within 5 minutes and create or update the lead.',
                        'Note it on paper and check it tomorrow.',
                        'A missed call does not wait for the end of the shift. WhatsApp and a lead row happen before you go back to the queue.',
                    ),
                ],
                'During shift — while you work (live leads & priority)' => [
                    self::one(
                        'The phone rings while you are updating remarks. What is the order after you answer?',
                        'Notes on the call, then the full panel update, then WhatsApp, then return to the queue.',
                        'Finish the old remarks first and let the phone ring.',
                        'A live call interrupts. After it ends, that call is written up before you go back.',
                    ),
                    self::q(
                        'Which situation is a hot booking that beats sorting old DMs?',
                        [
                            'A comment from last week with no phone number',
                            'A customer waiting on a provider reply or a slot confirmation',
                            'A closed Invalid lead',
                            'A Future customer from three months ago with no callback asked',
                        ],
                        1,
                        'A customer waiting on a provider is live work. Old DMs wait until that person has an update.',
                    ),
                ],
                'End of shift — handover & close-out' => [
                    self::q(
                        'It is the last 15 minutes. Which lead is safe to leave?',
                        [
                            'An open lead with no Followup On and no remarks',
                            'A chat you updated, while the linked lead is still empty',
                            'A lead with remarks, Handled By, and Followup On, or a real close',
                            'An unassigned Human support escalation',
                        ],
                        2,
                        'The next shift must continue without calling you. Every lead you touched needs a next action or a close.',
                    ),
                    self::one(
                        'You told a customer "I will do it tomorrow" but set no date in the panel. What will happen?',
                        'The next shift will miss it. Set Followup On, including a date for the next shift if it is not yours.',
                        'They will know, because you remember.',
                        'A promise with no panel date is not a handover.',
                    ),
                ],
                'Shift flow — start → during → end' => [
                    self::q(
                        'A new staff member asks how a shift is supposed to run from login to logout. What is the shape of a shift?',
                        [
                            'Start in one chat, work until you are tired, log out',
                            'Full scan, then priority work, a mini-scan every 30–60 minutes, then a handover where nothing you touched is half done',
                            'Answer only the leads that call you',
                            'Check social at logout and skip follow-ups',
                        ],
                        1,
                        'Start is the full scan. During is priority work plus the mini-scan. End is the handover.',
                    ),
                    self::one(
                        'A ringing phone starts during the opening scan. Does the scan order change?',
                        'Yes. Answer the phone, finish that call, then return to the scan.',
                        'No. Finish every tab before you answer.',
                        'The ringing phone is the exception. Everything else waits until that call is handled.',
                    ),
                ],
                'Your shift — start, during, end — panel pages' => [
                    self::q(
                        'Which pages do you need open in your mind at the start?',
                        [
                            'Today\'s follow-ups, WhatsApp Active Chats, and Human support',
                            'Only Add New Provider',
                            'Only the Facebook app',
                            'Only closed leads',
                        ],
                        0,
                        'Those three show the queue, the chats you own, and the AI escalations.',
                    ),
                    self::one(
                        'Where do overdue follow-ups show up so you can act on them first?',
                        'Today\'s follow-ups. Missed rows are highlighted. Open the lead and take the follow-up.',
                        'The All leads tab only. Overdue rows do not have their own page.',
                        'Working only from All leads hides the overdue queue. Start on Today\'s follow-ups.',
                    ),
                ],
                'Your shift — start, during, end — remember and avoid' => [
                    self::q(
                        'You are logging out. Which of these is a mistake to leave behind?',
                        [
                            'Every touched chat has an assignee, status, and tags',
                            'An open chat has no assignee, and an overdue follow-up has no remarks',
                            'Each open lead has Followup On',
                            'Remarks say who called and what was promised',
                        ],
                        1,
                        'Untagged chats and overdue rows with no explanation are the handover failures this section tells you to avoid.',
                    ),
                    self::one(
                        'You move to lead 2 while lead 1 has no WhatsApp and an empty panel. Is that allowed?',
                        'No. Finish panel and WhatsApp on the current lead first.',
                        'Yes, if lead 2 looks more urgent and lead 1 can wait with no record.',
                        'Do not start the next lead while the current one is unfinished.',
                    ),
                ],
                'Daily follow-up queue' => [
                    self::q(
                        'You open Today\'s follow-ups. What order do you work?',
                        [
                            'Missed first, then due today, then the new queue',
                            'Whatever is assigned to someone else',
                            'Only rows that are not highlighted',
                            'Bottom of the list, so the old ones wait',
                        ],
                        0,
                        'Missed rows are already late. Clear those before the ones that are only due today.',
                    ),
                    self::one(
                        'You click a follow-up row. What do you read before you call?',
                        'The Follow-ups tab and the remarks, so you know what was already promised.',
                        'Nothing. Dial immediately and ask the customer to repeat the whole history.',
                        'The history tells you the attempt number and the promise. Read it, then call.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function types(): array
    {
        $s = 'Lead types';

        return [
            $s => [
                'Lead types — the types' => [
                    self::q(
                        'You have just heard what the person wants. Which type matches the words they said?',
                        [
                            '"Kitchen tap is leaking, send someone tomorrow" → Customer',
                            '"Kitchen tap is leaking" → Provider',
                            '"I want to register as a plumber" → Customer',
                            '"Just saving your number" → Invalid',
                        ],
                        0,
                        'Customer needs a home service. Provider wants to join. Saving a number with no job today is Future customer, not Invalid.',
                    ),
                    self::q(
                        'One lead type is only allowed until you speak to the person. Which type is temporary?',
                        [
                            'Future customer, until they book',
                            'Unknown, until a successful call tells you the one real type',
                            'Invalid, until you feel like reopening it',
                            'Customer, until the first WhatsApp',
                        ],
                        1,
                        'Unknown is only until you speak to them. A successful call ends as exactly one type.',
                    ),
                    self::one(
                        'They need a home service we offer, but not today. Renovation is in three months. Which type?',
                        'Future customer, with the reason from the dropdown.',
                        'Invalid, because there is no booking today.',
                        'No need today is a valid Future close. Invalid is a wrong service or outside the area.',
                    ),
                ],
                'Lead types — remember and avoid' => [
                    self::q(
                        'You are about to set a lead type after a call. Which rule is correct?',
                        [
                            'Guess the type from the phone number',
                            'Pick exactly one type. Do not leave Unknown after they have answered',
                            'Use Invalid when nobody answered',
                            'Use Cancel when nobody answered',
                        ],
                        1,
                        'One type only. Unknown ends when the call succeeds. No answer is Non Responsive, and the type does not change.',
                    ),
                    self::one(
                        'Three calls, no answer, on an Unknown lead. What do you avoid?',
                        'Avoid Mark as Invalid and avoid Mark as Customer just so you can Cancel.',
                        'Avoid Mark as Non Responsive, because the type would stay Unknown.',
                        'The type stays Unknown. Non Responsive is the close. Invalid and Cancel are the mistakes.',
                    ),
                ],
                'Whole lead journey' => [
                    self::q(
                        'A lead starts as Unknown. They answer and need a plumber. What is the journey?',
                        [
                            'Stay Unknown until the booking is completed',
                            'Become Customer on that call, then Path A or Path B, then booking or an honest close',
                            'Become Invalid until a provider replies',
                            'Become Future customer, then post in the group',
                        ],
                        1,
                        'The successful call picks one type. A customer then goes through the path and the booking, or a spoken close.',
                    ),
                    self::one(
                        'They never answer. Where does this journey end?',
                        'Mark as Non Responsive after 3 attempts. The type you already had stays on the lead.',
                        'Change the type to Invalid and delete the follow-ups without a reason.',
                        'Non Responsive is the end of the no-answer journey. Reopen only if they come back.',
                    ),
                ],
                'Classify the enquiry' => [
                    self::q(
                        'On an Unknown call, in what order do you ask?',
                        [
                            'Ask all four at once and pick two types',
                            'Home service? Partner? Not now? Cannot help? Stop as soon as you know the one type',
                            'Always ask the Invalid question first',
                            'Skip the questions if the DM looks obvious',
                        ],
                        1,
                        'The four qualifier questions run in order. Stop at the first clear type. Do not guess from the message alone.',
                    ),
                    self::one(
                        'They say "Need car AC repair in Jammu." You offer home services in Kashmir. What type is this call?',
                        'Invalid. Write the exact request and the place, then close politely.',
                        'Customer. Book a home electrician anyway.',
                        'Invalid is for a service we do not offer or a place we do not cover, after they have answered.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function leadPage(): array
    {
        $s = 'Using the lead page';

        return [
            $s => [
                'Lead list — find your queue' => [
                    self::q(
                        'You want the leads you must act on today, with overdue rows highlighted. Which page?',
                        ['All leads, unsorted, with closed rows mixed in', 'Today\'s follow-ups, filtered to you or the unassigned leads you will take', 'Provider list', 'Only the Facebook inbox'],
                        1,
                        'Today\'s follow-ups is the working queue. All leads alone hides the overdue rows.',
                    ),
                    self::one(
                        'The list warns that this phone already has an open lead. What do you do?',
                        'Open that lead. Do not save a second one.',
                        'Save the new lead anyway so both stay open.',
                        'One open lead per phone. The warning is there so you update the existing row.',
                    ),
                ],
                'Lead detail page — what each section is for' => [
                    self::q(
                        'Where do you see the type, Open/Closed, and the status chip?',
                        ['Only inside Comments', 'The hero badges at the top of the lead', 'The provider group', 'The Facebook comment'],
                        1,
                        'The hero shows type, Open/Closed, status, and a pending follow-up. The activity tabs are lower on the page.',
                    ),
                    self::one(
                        'You need the count of how many times this lead was contacted. Where is that?',
                        'The Follow-ups tab. Each attempt is a row, not a sentence buried only in remarks.',
                        'Initial remarks. The attempt count is written there and nowhere else.',
                        'Follow-up rows are the history. Remarks summarize. They do not replace the rows.',
                    ),
                ],
                'Unknown leads — Mark as buttons' => [
                    self::one(
                        'The person answered and you now know they need a plumber, with address and time. Which hero button?',
                        'Mark as Customer. The modal asks for the required fields. Use it only after they have answered.',
                        'Mark as Non Responsive, because you are done with the call.',
                        'Mark as changes an Unknown lead after they answer. Non Responsive is for three missed attempts.',
                    ),
                    self::q(
                        'Nobody has answered three attempts. The DM already says "need electrician". Which button?',
                        ['Mark as Customer, then Cancel', 'Mark as Invalid', 'Mark as Non Responsive. The type stays Unknown', 'Mark as Future customer'],
                        2,
                        'A written job does not change the close if you never spoke to them. Non Responsive keeps the type.',
                    ),
                ],
                'Follow-ups — log every contact attempt' => [
                    self::one(
                        'You called and they did not pick up. How is that logged?',
                        'Add follow-up → Taken → Call, with remarks such as "Attempt 2/3 — no pickup, WA sent".',
                        'Reschedule, with no row that says you called.',
                        'Taken means the contact attempt happened. Reschedule is for a new date when you could not make the contact.',
                    ),
                    self::q(
                        'They asked you to call Thursday at 2 PM. You have not called yet. Which action?',
                        ['Taken → Call, dated Thursday, written today', 'Reschedule, Followup On Thursday 2 PM, and the reason in remarks', 'Delete the lead', 'Mark as Non Responsive'],
                        1,
                        'Reschedule sets the new date when the contact has not happened yet. Taken is for a call or WhatsApp you actually made.',
                    ),
                ],
                'Initial call recording — first contact audio' => [
                    self::one(
                        'Where does the first qualify-call recording go?',
                        'One audio upload on the lead main card. It can be played back and transcribed, and it also appears in Call Logs.',
                        'A new recording on every follow-up row only, with nothing on the main card.',
                        'The main card holds the first qualify call. Later calls can be attached on a call log or a Taken follow-up.',
                    ),
                    self::q(
                        'You uploaded the first qualify call on the lead card. What is that recording for?',
                        ['Replacing remarks, so you never write a summary', 'Playback and an AI transcript of the first qualify call', 'Proof that you can skip WhatsApp', 'A public file on the provider group'],
                        1,
                        'The recording supports the summary. You still write remarks and send WhatsApp.',
                    ),
                ],
                'Call logs — every phone contact in one place' => [
                    self::q(
                        'You add a call log. What else does the panel do?',
                        ['Nothing. Call logs are separate from follow-ups', 'It also creates a Taken follow-up row', 'It changes the type to Customer', 'It closes the lead'],
                        1,
                        'Activity → Call Logs → Add Call Log records who you called, when, and the remarks, and it creates the Taken follow-up.',
                    ),
                    self::one(
                        'You called the provider to chase a job, not the customer. What do you pick in the call log?',
                        'Provider, and search the provider. Remarks say what they replied.',
                        'Customer, because every call is logged as the lead contact.',
                        'The modal asks who you called: Customer, Provider, or Other.',
                    ),
                ],
                'Customer leads — booking tools' => [
                    self::q(
                        'The customer is ready to book and you have collected ₹100. Which sidebar action?',
                        ['Add Outbound Enquiry', 'Create Booking for this Lead', 'Mark as Invalid', 'Add New Provider'],
                        1,
                        'Create Booking is the customer tool after the path is agreed and ₹100 is collected. The form opens pre-filled.',
                    ),
                    self::one(
                        'Path B is still a discussion. The customer has not agreed. Do you Create Booking?',
                        'No. Book only after they commit. Until then, set Followup On and send WhatsApp.',
                        'Yes. Create Booking now and cancel later if they dislike the price.',
                        'Path B does not book until the customer agrees after the discussion.',
                    ),
                ],
                'Provider leads — onboarding tools' => [
                    self::one(
                        'Documents are in and the final call is done. Where do you create the provider record?',
                        'Providers → Add New Provider. Not on the lead page. The phone must match WhatsApp.',
                        'Create Booking on the lead, using the provider as the customer.',
                        'The provider record lives in Provider admin. The lead then shows the panel match.',
                    ),
                    self::q(
                        'The agreement WhatsApp has just gone out. What do you tick?',
                        ['Provider registered, before any documents', 'Provider Checklist → Agreement sent', 'Job completed', 'Non Responsive'],
                        1,
                        'Tick Agreement sent when the WhatsApp goes out. Registration is the last step, after documents and the final call.',
                    ),
                ],
                'Future customer — outbound enquiries' => [
                    self::one(
                        'A Future customer lead is already closed. Two months later you call to ask if the renovation has started. How do you record that call?',
                        'Add Outbound Enquiry on that Future lead. Do not reopen it as a new Unknown.',
                        'Mark as Non Responsive.',
                        'Future stays closed. A later check-in is an outbound enquiry, with its own status and remarks.',
                    ),
                    self::q(
                        'On that call they give you a brother\'s number. He needs an electrician this week. What do you create?',
                        ['Only a comment on the Future lead', 'A separate Customer lead for the brother', 'An Invalid lead for the brother', 'A second Future lead for the brother'],
                        1,
                        'A referral who needs service today gets their own Customer lead. The Future lead stays the closed nurture record.',
                    ),
                ],
                'How to close — pick the correct path' => [
                    self::q(
                        'You have the facts of the conversation. Which close matches those facts?',
                        [
                            'Spoken refusal after a price discussion → Change Status → Cancel, with the reason',
                            'No answer after 3 attempts → Cancel → No Response From Customer',
                            'Saving our number, no job today → Invalid',
                            'Car repair, they answered → Future customer',
                        ],
                        0,
                        'Cancel is after you have spoken. No answer is Non Responsive. No job today is Future. A service we cannot do is Invalid.',
                    ),
                    self::one(
                        'An open Provider never answered 3 calls. Which close is wrong?',
                        'Change Status → Cancel → "Not Interested" is wrong. Mark as Non Responsive, and keep the type Provider.',
                        'Mark as Non Responsive is wrong, because the type would stay Provider.',
                        'You never spoke to them, so this is not a Cancel. Non Responsive keeps the type.',
                    ),
                ],
                'Add New Lead — from the list' => [
                    self::q(
                        'You are creating a lead from an Instagram DM. What must be true before you save?',
                        [
                            'A phone number or a clear way to contact them, the correct Source, and the exact DM text in remarks',
                            'Source can be blank. Remarks can be blank',
                            'Always pick Customer, even when the DM says only hi',
                            'Skip the duplicate warning and save a second open lead',
                        ],
                        0,
                        'No phone and no way to contact them means you ask in the social reply first. Paste the exact text. Do not create a duplicate.',
                    ),
                    self::one(
                        'The enquiry came from a named ad campaign. What else do you set besides Source?',
                        'Ad Source, so the report shows which campaign it was.',
                        'Nothing. Source = Facebook is enough for every ad.',
                        'Ad Source is required when the enquiry came from a specific campaign. A wrong source breaks the reports.',
                    ),
                ],
                'Today\'s follow-ups — your daily queue' => [
                    self::one(
                        'Red, missed rows are on Today\'s follow-ups. You close the page without opening them. What did you miss?',
                        'The work that is already late. Open those rows first.',
                        'Nothing. Red rows are for the manager.',
                        'Missed rows are the first work of the queue. Do not leave them unopened.',
                    ),
                    self::q(
                        'A row is assigned to Unassigned and it is your channel to take. What do you do?',
                        ['Ignore it because your name is not on it', 'Take it, read the remarks, and log the follow-up', 'Delete it', 'Mark it Invalid'],
                        1,
                        'Unassigned rows that are yours to take are part of the queue. Do not work only the rows that already have your name.',
                    ),
                ],
                'Follow-up modal — Taken vs Reschedule' => [
                    self::q(
                        'You spoke to the customer. Which modal choice is correct?',
                        ['Reschedule, because the conversation was short', 'Taken, and pick Call or WhatsApp', 'Taken, with no channel', 'Reschedule, and leave Followup On empty'],
                        1,
                        'Spoken contact is Taken, with the channel. Reschedule is when you did not reach them and you are setting a new date.',
                    ),
                    self::one(
                        'The lead is still open after this follow-up. What must not be missing?',
                        'The next Followup On date. An open lead with no next date is lost.',
                        'A Cancel reason.',
                        'Every open lead leaves the modal with a next date, or it is closed on purpose.',
                    ),
                ],
                'Comments vs Initial remarks — handover' => [
                    self::q(
                        'The next shift will open this lead cold. Which text belongs in Initial remarks?',
                        [
                            '"@Sara — customer wants a female plumber only"',
                            '"Customer — tap leak Rajbagh, Path A, waiting on a provider, WhatsApp sent at 2 PM"',
                            'A long argument between two staff members',
                            'Nothing. Remarks are optional if Comments exist',
                        ],
                        1,
                        'Remarks are the summary any shift can read. The @mention belongs in Comments, and you can pin it.',
                    ),
                    self::one(
                        'You wrote a Comment and did not add a follow-up row for the call. Is the contact logged?',
                        'No. Comments do not replace Add follow-up or Add Call Log.',
                        'Yes. A comment is the attempt record.',
                        'Contact attempts are follow-up rows or call logs. Comments are internal notes.',
                    ),
                ],
                'Open vs Closed — when each shows' => [
                    self::q(
                        'An Unknown lead has had two missed calls and is waiting for attempt 3. When does an Unknown lead show Closed?',
                        ['As soon as you create it', 'After Mark as Non Responsive. The type stays Unknown', 'While you are still on attempt 2 of 3', 'When you pin a comment'],
                        1,
                        'Unknown stays Open through the 3 attempts. Non Responsive closes it and keeps the type.',
                    ),
                    self::one(
                        'A Future customer is saved with a reason. What does the queue show?',
                        'Closed. Future and Invalid are always closed. You can still log an outbound enquiry later.',
                        'Open, until someone creates a booking.',
                        'Future is a finished nurture close. It does not sit in the open follow-up queue.',
                    ),
                ],
                'Dropdown names — live from Lead Configuration' => [
                    self::one(
                        'You set Invalid. The reason dropdown does not match the remarks. Why does that matter?',
                        'Managers audit the reason against the remarks. Pick the reason that matches what they asked for.',
                        'The dropdown is decorative. Any reason is fine.',
                        'The configured reasons are the official close. Remarks and the dropdown have to say the same thing.',
                    ),
                    self::q(
                        'No answer after 3 calls on a provider you never reached. Which dropdown path is wrong?',
                        ['There is no Cancel reason for this. Use Mark as Non Responsive', 'Cancel → a "not interested" reason', 'Keep the type Provider', 'Clear Followup On by closing as Non Responsive'],
                        1,
                        'Provider cancel reasons are for after you have reached them, for example documents never sent. No answer is not a cancel reason.',
                    ),
                ],
                'Four ways to update a lead — use the right one' => [
                    self::q(
                        'You made a phone call. Which update is the right one?',
                        ['A Comment that says "called"', 'Add Call Log or Add follow-up → Taken → Call', 'Only Change Status', 'Only a tag'],
                        1,
                        'A call is a call log or a Taken follow-up. Status, tags, and comments do other jobs.',
                    ),
                    self::one(
                        'The type is wrong because they answered and they are a customer. What do you use?',
                        'Mark as Customer, after the qualify call, and fill the required fields.',
                        'A comment that says "this is a customer" while the type stays Unknown.',
                        'The type changes through Mark as, not through a comment.',
                    ),
                ],
                'Lead detail page — click map' => [
                    self::q(
                        'On an open Customer lead, where is Mark as Non Responsive?',
                        ['Inside the provider group', 'The hero Mark as row', 'Only on Future leads', 'Inside Initial remarks'],
                        1,
                        'Unknown has the type buttons plus Non Responsive. Open Customer and Provider have Mark as Non Responsive on the hero.',
                    ),
                    self::one(
                        'You want Create Booking, Add Follow-up, and Tags. Where are those?',
                        'The sidebar quick actions.',
                        'The Facebook page.',
                        'The sidebar is the action column: booking, follow-up, tags, temporary provider, and assigned to.',
                    ),
                ],
                'Add call log modal — fields' => [
                    self::q(
                        'You are logging a phone call from the lead. Which set of fields is a complete call log?',
                        [
                            'Who you called, when, remarks, and an optional recording',
                            'Only a star rating',
                            'Only the word "called"',
                            'A new lead type and nothing else',
                        ],
                        0,
                        'Who, when, and remarks are the record. A recording up to 10MB is optional and can be transcribed later.',
                    ),
                    self::one(
                        'The remarks on a missed attempt should say what?',
                        '"Attempt 2/3 — no pickup, WA sent", or the provider chase note.',
                        '"Ok".',
                        'The next person has to see the attempt number and whether WhatsApp went out.',
                    ),
                ],
                'Add follow-up modal — fields' => [
                    self::q(
                        'You spoke to the customer and Taken is selected on the follow-up. What else is required?',
                        ['Nothing', 'Call or WhatsApp', 'A Cancel reason', 'A new provider record'],
                        1,
                        'Taken needs a channel. Remarks should include the attempt, for example "Attempt 1/3 — no pickup, WA sent".',
                    ),
                    self::one(
                        'The job is an emergency. What urgency do you set on the follow-up?',
                        'High, and a HOT tag on the customer lead.',
                        'Low, so it stays out of the way.',
                        'Emergency is High. Medium is the default for ordinary work.',
                    ),
                ],
                'Panel clicks — Mark as Customer (from Unknown)' => [
                    self::one(
                        'The lead is still Unknown. When is Hero → Mark as Customer allowed?',
                        'After a successful qualify call, with the job details collected.',
                        'As soon as an Instagram DM arrives, before any call.',
                        'The button is for after they answer and you know the job. The modal then asks for the required customer fields.',
                    ),
                    self::q(
                        'They answered, you collected the job, and you save Mark as Customer. What changes?',
                        ['The type becomes Customer', 'The type becomes Invalid', 'The lead is deleted', 'The type stays Unknown and a booking is created'],
                        0,
                        'Saving the modal changes the type to Customer. You then follow the customer path.',
                    ),
                ],
                'Panel clicks — Create Booking (Customer, Path A/B)' => [
                    self::q(
                        'Your hand is on Create Booking for a customer lead. What has to be true before you save it?',
                        [
                            'The customer has agreed, and ₹100 is collected',
                            'You have only posted "need plumber" in the group',
                            'The lead is still Unknown',
                            'The customer has not answered',
                        ],
                        0,
                        'Create Booking opens pre-filled. Confirm the provider, slot, and address, then save. The booking ID appears on the hero.',
                    ),
                    self::one(
                        'An AI booking is already linked on the hero. Should you also Create Booking by hand?',
                        'No. Finish the AI booking. Use manual Create Booking only if that path is stuck.',
                        'Yes. Always create a second booking.',
                        'Two bookings for one AI draft is a mistake. Manual create is the fallback when AI is stuck.',
                    ),
                ],
                'Panel clicks — Continue with AI booking' => [
                    self::one(
                        'The banner says Continue with AI booking. What do you open first?',
                        'View AI chat, and confirm any missing phone, service, address, or time.',
                        'The provider group, with the raw transcript and no check.',
                        'The banner means a draft booking exists. A person still checks the gaps.',
                    ),
                    self::q(
                        'You finish the AI booking path. What do you still update?',
                        ['Nothing', 'Remarks, Handled By, and a HOT tag if it is urgent', 'The type to Invalid', 'A second manual booking'],
                        1,
                        'The booking ID shows on the hero. Remarks and owner still have to match what you did.',
                    ),
                ],
                'Panel clicks — Temporary provider & tags (Customer)' => [
                    self::one(
                        'A provider said YES for this slot, and the booking is not saved yet. Where do you note them on the lead?',
                        'Temporary provider, plus the remarks. Tags such as HOT if the job is urgent.',
                        'Change the lead type to Provider.',
                        'Temporary provider is the customer-lead tool for the partner you are about to book. It does not turn the customer into a provider.',
                    ),
                    self::q(
                        'The customer is waiting and the job is an emergency. Which tag fits?',
                        ['A tag that hides the lead', 'HOT', 'Invalid', 'Future'],
                        1,
                        'HOT marks the urgent customer lead so the next person sees it. It does not replace Followup On.',
                    ),
                ],
                'Panel clicks — Provider checklist & panel match' => [
                    self::q(
                        'The provider is saved in Provider admin. What do you check on the lead?',
                        [
                            'The Is Added in Panel link matches, and the phone matches WhatsApp',
                            'That Create Booking was used instead',
                            'That the type was changed to Customer',
                            'That the checklist is still completely empty',
                        ],
                        0,
                        'Step 4 is Add New Provider, then the lead shows the panel match. The checklist is ticked as each step is really done.',
                    ),
                    self::one(
                        'Documents have not arrived. Can you tick the final "added to group" step?',
                        'No. The final call and the group come after documents. Do not tick a step that is not done.',
                        'Yes. Tick everything at the start so the gate is quiet.',
                        'A soft gate asks you to confirm. You confirm only when the work is truly done.',
                    ),
                ],
                'Panel clicks — Outbound enquiry (Future customer)' => [
                    self::one(
                        'You call a closed Future customer about a renovation month. Which form?',
                        'Add Outbound Enquiry: name, phone, call or message, status, time, and remarks.',
                        'Mark as Non Responsive.',
                        'The hero outbound count goes up. Some statuses ask for a Lead ID or Booking ID. Follow the status config.',
                    ),
                    self::q(
                        'You have logged several calls on closed Future leads. Where is the full list of these check-ins?',
                        ['Inside Invalid reasons', 'Leads → Outbound Enquiries', 'The provider WhatsApp group', 'Today\'s follow-ups only'],
                        1,
                        'Each Future lead has the button. The standalone list is Leads → Outbound Enquiries.',
                    ),
                ],
                'Post-booking — status progression' => [
                    self::q(
                        'The booking is saved. What can the status do next?',
                        [
                            'Stay Pending forever with no update',
                            'Booked, then Completed after the job is done. Set a service-day follow-up if you need to check',
                            'Jump to Invalid',
                            'Change the type to Unknown',
                        ],
                        1,
                        'Create Booking puts a booking ID on the hero. Completed is after the job. A spoken refusal before that is Cancel with a reason.',
                    ),
                    self::one(
                        'The customer later refuses, after you spoke and the booking was not the right outcome. What do you use?',
                        'Change Status → Cancel, with the reason and remarks.',
                        'Mark as Non Responsive.',
                        'You spoke to them, so this is Cancel. Non Responsive is only the no-answer close.',
                    ),
                ],
                'Using the lead page — panel pages' => [
                    self::q(
                        'You need the right admin page for the job in front of you. Which pair is correct?',
                        [
                            'Leads list for the queue, Provider admin for a new partner record',
                            'Provider admin to qualify an Unknown caller',
                            'Outbound enquiries to log a missed call on an open Unknown lead',
                            'Create Booking to register a new partner',
                        ],
                        0,
                        'Use the page that matches the job. Partner records are not created with Create Booking.',
                    ),
                    self::one(
                        'You need sources and ad sources. Where are they configured?',
                        'Lead configuration.',
                        'The provider group.',
                        'The dropdown names you see on the lead come from Lead configuration.',
                    ),
                ],
                'Using the lead page — remember and avoid' => [
                    self::q(
                        'You are finishing a lead on the detail page. Which mistake is called out on this page?',
                        [
                            'Logging every call as a follow-up row',
                            'Using Comments instead of a follow-up, or Cancel when nobody answered',
                            'Setting Followup On on an open lead',
                            'Pasting the exact DM into remarks',
                        ],
                        1,
                        'The page is about using the right control. A comment is not a follow-up, and no answer is not Cancel.',
                    ),
                    self::one(
                        'You leave an open lead with no next date. What happens tomorrow?',
                        'It is missed. Set Followup On or close it properly before you leave.',
                        'It will float to the top on its own.',
                        'Open means the queue is still waiting. The date is how tomorrow finds it.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function workflow(): array
    {
        $s = 'Workflow checklist in the panel';

        return [
            $s => [
                'How this checklist is used' => [
                    self::one(
                        'The floating checklist is on the lead. When do you tick a box?',
                        'When that step is actually done, as you qualify and move toward booking.',
                        'All at once at the start, so the panel stops asking.',
                        'The checklist is the path you are on. A tick means the work happened.',
                    ),
                    self::q(
                        'A box is still empty and the panel blocks Create Booking. What does that mean?',
                        ['You can ignore it', 'A required step is not done yet', 'The lead is Invalid', 'WhatsApp is optional'],
                        1,
                        'The checklist is how the panel knows the path is complete. A blocked action is a step you still have to do.',
                    ),
                ],
                'Where to find it' => [
                    self::q(
                        'Where do you see the steps on a lead you already opened?',
                        ['The floating workflow control on the lead detail page', 'Only in Facebook', 'Only after the lead is Invalid', 'Inside the provider group message'],
                        0,
                        'The FAB sits on the lead. Workflow Stuck Items is the queue of leads that are blocked. Provider onboarding has its own checklist.',
                    ),
                    self::one(
                        'A lead is stuck because a required step was skipped. Where does a manager look across leads?',
                        'Workflow Stuck Items.',
                        'Closed Invalid leads only.',
                        'Stuck Items is the list of leads the checklist will not let move forward.',
                    ),
                ],
                'Hard vs soft gates on leads' => [
                    self::one(
                        'You try to change Unknown to Customer before any outbound call. The panel refuses. What kind of gate is that?',
                        'A hard gate. The action stays blocked until the required step is done.',
                        'A soft gate. Tick confirm and change the type anyway.',
                        'Hard means blocked. Soft means a confirm modal, and you confirm only if the work is truly done.',
                    ),
                    self::q(
                        'A soft gate asks you to confirm a skipped step. The step is not done. What do you do?',
                        ['Tick confirm so you can leave', 'Go back and do the step. Do not confirm a lie', 'Mark the lead Invalid', 'Delete the checklist'],
                        1,
                        'The confirm box is not a shortcut. Tick it only when the work is really finished.',
                    ),
                ],
                'Unknown lead (from workflow)' => [
                    self::q(
                        'What does the Unknown checklist make you do before the type changes?',
                        [
                            'Call, run the qualifier, and pick one type after they answer',
                            'Post in the provider group first',
                            'Cancel on the first missed call',
                            'Set Future customer for every Unknown',
                        ],
                        0,
                        'Unknown workflow is qualify first. The type changes after the call, not before it.',
                    ),
                    self::one(
                        'They never answer. What does this workflow end with?',
                        'Three logged attempts, then Mark as Non Responsive. The type stays Unknown.',
                        'Mark as Customer so the customer checklist can cancel it.',
                        'The Unknown path does not invent a type just to close a silent lead.',
                    ),
                ],
                'Customer Path A (from workflow)' => [
                    self::q(
                        'Path A checklist order, after the customer is ready to book without talking to the provider first. What is the sequence?',
                        [
                            'Group post with full details, 10-minute wait, chase if needed, ₹100, Create Booking',
                            'Create Booking, then ask what they need',
                            'Cancel, then post in the group',
                            '₹100 after the job, and no group post',
                        ],
                        0,
                        'Path A is direct booking. The group post starts the 10 minutes. ₹100 comes before Create Booking.',
                    ),
                    self::one(
                        'Nobody replies in 10 minutes. Does the checklist stop?',
                        'No. Call providers yourself and update the customer. Do not go silent.',
                        'Yes. Wait until tomorrow and do not call anyone.',
                        'The 10-minute reply is the SLA. Silence after that means you chase.',
                    ),
                ],
                'Customer Path B (from workflow)' => [
                    self::q(
                        'The customer wants the price and wants to speak to the plumber before paying. Which path checklist?',
                        ['Path A, and Create Booking now', 'Path B: discussion post, conference call, then book, follow up, or cancel', 'Invalid', 'Provider onboarding'],
                        1,
                        'Path B is discussion first. You stay on the conference call. Booking happens only after they agree.',
                    ),
                    self::one(
                        'They agree on the conference call. What is the same as Path A from here?',
                        'Collect ₹100, then Create Booking, then confirm both sides.',
                        'Close as Future customer.',
                        'Once they commit, the booking rules are the same. ₹100, then Create Booking.',
                    ),
                ],
                'Provider onboarding (from workflow)' => [
                    self::q(
                        'A new partner has answered the phone. What is the provider checklist order from here?',
                        [
                            'Brief call, agreement and documents, final call after documents, then Add New Provider and the WhatsApp group',
                            'Add them to the group first, documents later',
                            'Create Booking, then ask if they want to join',
                            'Cancel if the first call is busy',
                        ],
                        0,
                        'Step 3 and the group come after documents. A busy person gets a Followup On, not a cancel.',
                    ),
                    self::one(
                        'Three document reminders passed and nothing arrived. You did speak to them on step 1. What is the close?',
                        'Change Status → Cancel, documents not received.',
                        'Mark as Non Responsive.',
                        'You already reached them. Missing documents after the follow-ups is Cancel. Non Responsive is for never answering.',
                    ),
                ],
                'Workflow checklist in the panel — panel pages' => [
                    self::one(
                        'The checklist on the lead and the stuck queue are different screens. Which is the queue?',
                        'Workflow Stuck Items.',
                        'The provider WhatsApp group.',
                        'Stuck Items lists leads the checklist is blocking. The FAB is on the lead itself.',
                    ),
                    self::q(
                        'Provider registration is finished in which admin area?',
                        ['Create Booking', 'Providers → Add New Provider', 'Invalid reasons', 'Facebook comments'],
                        1,
                        'The checklist points at Provider admin for the real provider record. The lead only shows the match.',
                    ),
                ],
                'Workflow checklist in the panel — remember and avoid' => [
                    self::q(
                        'The checklist is on the lead and a soft gate is open. Which action breaks the checklist?',
                        [
                            'Ticking a box only after the step is done',
                            'Confirming a soft gate for a step you skipped',
                            'Calling before you change Unknown to Customer',
                            'Collecting ₹100 before Create Booking',
                        ],
                        1,
                        'A soft gate is not permission to skip. A hard gate will not let you past a missing call or a missing booking step.',
                    ),
                    self::one(
                        'You post the job in the group before the customer checklist says the details are confirmed. What is wrong?',
                        'The group post is late in the path, after the call, the panel, and WhatsApp.',
                        'Nothing. The group is always step 1.',
                        'Providers need a complete job. The checklist keeps the post after the details exist.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function unknown(): array
    {
        $s = 'Unknown — qualify & follow up';

        return [
            $s => [
                'Lead qualifier — ask on every Unknown call until you know the type' => [
                    self::q(
                        'They answer an Unknown call. You ask "Do you need a home service?" and they say the kitchen tap is leaking. What do you do next on this same call?',
                        [
                            'Stop. Collect service, problem, full address, and date/time. The type will be Customer',
                            'Keep asking all four questions and pick two types',
                            'Hang up and leave the type Unknown',
                            'Mark Invalid because they have not paid',
                        ],
                        0,
                        'Stop as soon as you know the type. A home-service need is Customer, and the booking details are collected before you hang up.',
                    ),
                    self::q(
                        'Which answer matches which qualifier question?',
                        [
                            '"I want to join as a partner" → Provider. "Just saving your number" → Future customer. "Car repair in Jammu" → Invalid',
                            '"I want to join" → Customer',
                            '"Saving your number" → Invalid',
                            '"Need a plumber" → Provider',
                        ],
                        0,
                        'The four questions are home service, partner, not now, and cannot help. Each one maps to one type.',
                    ),
                ],
                'Path A — User answers the call' => [
                    self::q(
                        'You are about to dial an Unknown lead. The DM said only "Hi, I called you." What do you do before the type changes?',
                        [
                            'Read the source and the message, dial, take notes on paper, and run the qualifier',
                            'Set Customer before they answer',
                            'Post in the provider group during the call',
                            'Type in the panel while they explain',
                        ],
                        0,
                        'Unknown stays Unknown until the call tells you the type. Notes are on paper. The panel is updated after you hang up.',
                    ),
                    self::one(
                        'The call ends. They need a plumber tomorrow at 10 AM in Rajbagh, Path A. What is the order after you hang up?',
                        'Panel first: type Customer, full remarks, Followup On if you are waiting. WhatsApp the same minute. Then the customer slide.',
                        'Provider group first, panel tomorrow.',
                        'The panel and WhatsApp happen before any group post, and before you open the next lead.',
                    ),
                ],
                'Path B — User does NOT pick up (max 3 attempts)' => [
                    self::q(
                        'Attempt 1, no answer. The type is Unknown. What do you write and send?',
                        [
                            'WhatsApp the missed-call template, Add follow-up Taken Call "Attempt 1/3", Followup On the next working day. Type stays Unknown',
                            'Mark as Customer',
                            'Mark as Invalid, "Did not Know About Enquiry"',
                            'One attempt is enough to close',
                        ],
                        0,
                        'Do not guess the type. Each miss gets WhatsApp and a follow-up row. Attempt 2 is on the Followup On date.',
                    ),
                    self::one(
                        'Attempt 2 on an Unknown lead, they finally answer. What changes in the process?',
                        'Run the full qualify flow on that call and set exactly one type before you hang up.',
                        'Count it as a failed attempt and keep calling until attempt 3 anyway.',
                        'Any answer ends the no-pickup path. Qualify them on that call.',
                    ),
                    self::q(
                        'Attempt 3, still no answer. What is the close?',
                        [
                            'Hero → Mark as Non Responsive. Type stays Unknown. Remarks list all three dates',
                            'Cancel → No Response',
                            'Invalid',
                            'Keep the lead open for a fourth week with no manager approval',
                        ],
                        0,
                        'Three failures close as Non Responsive. More than three needs a manager. The type does not change.',
                    ),
                ],
                'Path C — DM/form names a job, but nobody answers (3 attempts)' => [
                    self::one(
                        'The DM says "Need plumber — kitchen sink blocked, Rajbagh, tomorrow." Three calls, no answer. Do you Mark as Customer?',
                        'No. You never spoke to them. The type stays Unknown. Mark as Non Responsive and paste the DM into the remarks.',
                        'Yes. The DM named a job, so book it and post in the group.',
                        'A written job does not replace a conversation. Do not post in the group. Do not Mark as Invalid either.',
                    ),
                    self::q(
                        'They call back the next week. The lead is Non Responsive, type Unknown. What do you do?',
                        [
                            'Leave it closed',
                            'Reopen. It returns to follow-ups as Unknown. Then qualify and Mark as the real type',
                            'Create a booking from the old DM without calling',
                            'Change it to Invalid',
                        ],
                        1,
                        'Reopen is for when they actually come back. Until they answer, the close stays Non Responsive.',
                    ),
                ],
                'Unknown — qualify & follow up — examples' => [
                    self::q(
                        'DM: "Hi, I called you earlier." On the call they say the bathroom geyser is not heating, Hazratbal, today afternoon, and they want someone sent. What is the result?',
                        [
                            'Customer, Path A, panel and WhatsApp, then the provider group',
                            'Stay Unknown',
                            'Invalid, because the first message was vague',
                            'Provider, because a geyser is technical',
                        ],
                        0,
                        'The vague DM was Unknown. The successful call makes them Customer. Group post comes after the panel and WhatsApp.',
                    ),
                    self::one(
                        'WhatsApp says "I am a plumber and I want to register." You confirm trade and area on the call. What do you set?',
                        'Provider, on that call. Then send the agreement and follow onboarding.',
                        'Customer, and post them as a job.',
                        'A partner message is still Unknown until the call confirms it. Then it is Provider, not a booking.',
                    ),
                ],
                'Flow — user answers' => [
                    self::q(
                        'The Unknown lead picked up. Which flow matches an answered call?',
                        [
                            'Dial → qualifier → one type → details on that call → panel → WhatsApp → the matching slide',
                            'Group post → call → maybe classify',
                            'Invalid → reopen → Customer',
                            'Three attempts even though they answered',
                        ],
                        0,
                        'Answered means qualify now. The flow does not go to the group until the type and the details are known.',
                    ),
                    self::one(
                        'They answered, you know they need a plumber, and the call is ending. Can Unknown remain after a successful call?',
                        'No. Unknown is only until this call ends. Pick the one type.',
                        'Yes, if you are not sure you want the paperwork.',
                        'A successful call always leaves Unknown.',
                    ),
                ],
                'Flow — no pickup (3 attempts)' => [
                    self::q(
                        'Nobody answered. What does each no-pickup loop contain before you try again?',
                        [
                            'Call, WhatsApp, Add follow-up, next Followup On. After the third miss, Mark as Non Responsive',
                            'Call only. No WhatsApp and no row',
                            'Cancel on attempt 1',
                            'Change the type on attempt 2 even though they did not answer',
                        ],
                        0,
                        'The flow repeats three times. The close keeps the type and clears the lead from follow-ups.',
                    ),
                    self::one(
                        'They pick up on attempt 3. Which arrow do you follow?',
                        'Leave the no-pickup flow and run the answered qualify flow.',
                        'Close as Non Responsive anyway, because it is attempt 3.',
                        'An answer at any attempt is a qualify call, not a close.',
                    ),
                ],
                'WhatsApp to customer' => [
                    self::one(
                        'Attempt 1 did not answer. Which WhatsApp do you send?',
                        'The missed-call template, the same day, and you log it on the follow-up.',
                        'The booking confirmation, with a provider name you do not have.',
                        'No-pickup WhatsApp is the missed-call message. A summary of a job is only after they have talked to you.',
                    ),
                    self::q(
                        'They answered and the job is a kitchen tap tomorrow at 10 AM. What does the WhatsApp say?',
                        [
                            'The service, place, time, and that you are finding a partner',
                            '"Ok"',
                            'Nothing until the job is completed',
                            'The internal comment to @Sara',
                        ],
                        0,
                        'After a real call, WhatsApp repeats what you agreed and what happens next.',
                    ),
                ],
                'Unknown — qualify & follow up — remember and avoid' => [
                    self::q(
                        'You are closing an Unknown lead that never answered. Which line is a mistake?',
                        [
                            'Reclassify on a successful call',
                            'Guess Customer because they messaged, or Mark as Invalid because nobody answered',
                            'Notes on paper, panel after the call',
                            'Non Responsive after 3 attempts, type unchanged',
                        ],
                        1,
                        'Do not guess, do not update the panel during the call, and do not use Invalid or Cancel for silence.',
                    ),
                    self::one(
                        'Is a fourth no-pickup attempt your own decision?',
                        'No. More than 3 needs manager approval.',
                        'Yes. Keep calling until they block the number.',
                        'The cap is 3 unless a manager says otherwise.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function customer(): array
    {
        $s = 'Customer — first call to booking';

        return [
            $s => [
                'Path choice — decide on the first call (before provider group post)' => [
                    self::q(
                        'On the call they say "Book directly — just send someone." Address and time are complete. Which path?',
                        ['Path A — direct booking. Post the full job, 10-minute SLA, ₹100, Create Booking', 'Path B — they must talk to the provider first', 'Invalid', 'Future customer'],
                        0,
                        'Path A is for a customer who is ready to book without speaking to the provider first. Decide it on this call, before any group post.',
                    ),
                    self::one(
                        'They say "What will the plumber charge? I want to talk to him first." Which path?',
                        'Path B — discussion first. Do not Create Booking until they agree after the call with the provider.',
                        'Path A — collect ₹100 now anyway.',
                        'Price or a request to speak to the provider is Path B. Write that choice in the remarks.',
                    ),
                ],
                'Step 1 — First call (user answers)' => [
                    self::q(
                        'A web booking says "Electrician — MCB tripping, Bemina" and the address is only the area. They answer. What do you collect before you hang up?',
                        [
                            'Full address with a landmark, the problem, the time, emergency or not, and Path A or Path B',
                            'Only the first name',
                            'Nothing. Post "Bemina" in the group during the call',
                            'A Cancel reason',
                        ],
                        0,
                        'Vague jobs get ignored. The call completes the address and the path. Notes stay on paper until you hang up.',
                    ),
                    self::one(
                        'The call has ended. What is step 4 before any group post?',
                        'Update the panel: Customer, remarks with problem, address, time, and path. Then WhatsApp the same minute.',
                        'Post in the group, and update the panel at the end of the day.',
                        'Panel and WhatsApp come before the provider group.',
                    ),
                ],
                'Step 2 — No pickup on first call (max 3 attempts)' => [
                    self::one(
                        'The lead is already Customer from a web form. Attempt 1, no answer. Do you post in the provider group?',
                        'No. You have not spoken to them. WhatsApp, log Attempt 1/3, and set Followup On. Type stays Customer.',
                        'Yes. The form is enough to send a provider.',
                        'No group post until you have spoken. The type stays Customer while you try.',
                    ),
                    self::q(
                        'All 3 customer attempts failed. What is the hero action?',
                        ['Mark as Non Responsive. Type stays Customer. Do not Cancel', 'Cancel → No Response From Customer', 'Invalid → Did not Know About Enquiry', 'Future customer'],
                        0,
                        'Same 3-attempt rule as Unknown. Cancel is only after a spoken decision.',
                    ),
                ],
                'Panel — AI booking banner (when shown)' => [
                    self::one(
                        'Continue with AI booking is on the lead. What do you check before you finish it?',
                        'View AI chat. Confirm phone, service, address, and time if the transcript has gaps.',
                        'Create a second booking immediately and ignore the banner.',
                        'The banner is a draft. Read it. Do not add a manual booking on top of a linked AI booking.',
                    ),
                    self::q(
                        'Continue with AI booking is stuck and will not finish. What is the fallback?',
                        ['Mark Invalid', 'Take over the chat, fix the fields, Create Booking manually', 'Wait a week', 'Post the transcript with no address'],
                        1,
                        'Stuck means a person takes over. Manual Create Booking replaces the AI path. It does not sit beside it.',
                    ),
                ],
                'Path A — Direct booking (panel workflow steps)' => [
                    self::q(
                        'The customer is ready and the panel is updated. What does the Path A checklist do next?',
                        [
                            'Provider group post with Lead ID and the full job, then the 10-minute wait',
                            'Create Booking with no provider and no ₹100',
                            'Cancel',
                            'Change the type back to Unknown',
                        ],
                        0,
                        'The checklist posts the complete job first. Booking is after a provider and the advance.',
                    ),
                    self::one(
                        'A provider replied YES within 10 minutes. What do you collect before Save on Create Booking?',
                        '₹100 from the customer.',
                        'Nothing. Save the booking and ask for money after the visit.',
                        'The advance is before Create Booking, on Path A and Path B.',
                    ),
                ],
                'Path A — Direct booking' => [
                    self::q(
                        'Nobody replies in the group within 10 minutes. What do you do?',
                        [
                            'Call nearby providers, and WhatsApp the customer that you are still checking',
                            'Go silent until a provider appears',
                            'Promise 10 AM even though nobody has confirmed',
                            'Cancel for no response from the customer',
                        ],
                        0,
                        'You chase. If nobody can do the slot, you tell the customer honestly and follow up or cancel only after that conversation.',
                    ),
                    self::one(
                        'Create Booking is saved. What does the customer and the provider receive?',
                        'A confirmation WhatsApp to both sides. The booking ID is on the hero.',
                        'Nothing until the job is marked Completed.',
                        'Both sides need the confirmation. A service-day follow-up is optional. Completed is after the job.',
                    ),
                ],
                'Path B — Discussion first (panel workflow steps)' => [
                    self::q(
                        'Path B checklist: what is the group post called?',
                        [
                            'A discussion request, with Lead ID, the problem, the address, and the time window. Not a booking yet',
                            'A direct booking post',
                            'An Invalid close',
                            'A provider registration',
                        ],
                        0,
                        'Path B tells the group the customer wants to talk before booking. You do not Create Booking in this step.',
                    ),
                    self::one(
                        'The Path B conference call starts between the customer and the provider. Where are you?',
                        'On the call. You do not drop the customer alone with the provider.',
                        'Offline. They can sort the price without you.',
                        'You coordinate. The customer decides after they have spoken to the provider.',
                    ),
                ],
                'Path B — Discussion first, then book' => [
                    self::q(
                        'The conference ends and the customer agrees. What happens next?',
                        ['₹100, then Create Booking, then confirmation', 'Close as Future customer', 'Another discussion post with no booking', 'Mark as Non Responsive'],
                        0,
                        'Agreement ends Path B the same way Path A books: advance, Create Booking, confirmation.',
                    ),
                    self::one(
                        'They want to decide later because the price is high. What do you set?',
                        'Remarks with the concern, Followup On, and an honest WhatsApp. Offer another provider on that follow-up if that is the plan.',
                        'Create Booking anyway.',
                        'Not decided means the lead stays open with a date. Cancel only if they refuse after you have spoken.',
                    ),
                ],
                'Customer — first call to booking — examples' => [
                    self::q(
                        'Instagram: "Need electrician — MCB tripping, Bemina, today evening." They confirm on the call and say send someone. A provider says YES in 8 minutes. What is the chain?',
                        [
                            'Full address and 6 PM, Path A, panel, WhatsApp, group post, ₹100, Create Booking, confirmation to both',
                            'Group post before the call',
                            'Path B, because the DM was short',
                            'Non Responsive, because 8 minutes is too fast',
                        ],
                        0,
                        'That is the clear Path A example. YES inside 10 minutes goes to the advance and the booking.',
                    ),
                    self::one(
                        'Web form, cleaning in Srinagar. You call three days and never reach them. Was there a group post?',
                        'No. Mark as Non Responsive. Type stays Customer. The remarks list the three dates.',
                        'Yes. The form was enough to send a cleaner.',
                        'You never spoke to them, so nobody is dispatched. The close is Non Responsive.',
                    ),
                ],
                'Flow — first call & path choice' => [
                    self::q(
                        'The customer has answered the first call. Where in the flow is Path A or Path B chosen?',
                        ['On the first answered call, before the group post', 'After Create Booking', 'When the provider complains', 'At the end of the month'],
                        0,
                        'The flow splits into Path A or Path B on that call. The group post is different for each path.',
                    ),
                    self::one(
                        'Can you post in the group and choose the path afterwards?',
                        'No. The path is decided first, then the panel, WhatsApp, and the matching post.',
                        'Yes. The group will tell you which path it was.',
                        'A wrong path wastes the provider and the customer. Decide, write it down, then post.',
                    ),
                ],
                'Flow — no pickup (3 attempts)' => [
                    self::q(
                        'Customer no-pickup flow: what is forbidden before attempt 3 ends?',
                        [
                            'A provider group post, and Cancel for no response',
                            'WhatsApp',
                            'A follow-up row',
                            'Keeping the type as Customer',
                        ],
                        0,
                        'The flow logs three attempts. The end is Non Responsive, type unchanged. The group is not used.',
                    ),
                    self::one(
                        'They answer on attempt 2. Where does the flow go?',
                        'Back to the first-call steps, then Path A or Path B.',
                        'Straight to Non Responsive.',
                        'An answer cancels the no-pickup ending. You qualify the job on that call.',
                    ),
                ],
                'Flow — Path A direct booking' => [
                    self::q(
                        'The customer said "just send someone" and the details are complete. Which order is the Path A flow?',
                        [
                            'Panel and WhatsApp, group post, 10 minutes, chase if needed, ₹100, Create Booking, confirmation',
                            'Create Booking, then ask the address',
                            'Group post, then the first call',
                            'Cancel, then ₹100',
                        ],
                        0,
                        'That is the direct-booking flow. The booking is the late step, after a provider and the advance.',
                    ),
                    self::one(
                        'The customer is updated during a long search. Is that part of Path A?',
                        'Yes. If the search runs past the first reply window, WhatsApp them. Do not promise a time nobody has confirmed.',
                        'No. They wait in silence until the visit.',
                        'Silence is a failure. The flow includes the customer update.',
                    ),
                ],
                'Flow — Path B discussion first' => [
                    self::q(
                        'The customer wants to hear the price before they book. Which order is the Path B flow?',
                        [
                            'Expectation WhatsApp, discussion post, conference with you on the call, then book or follow up or cancel',
                            'Create Booking, then ask if they wanted a discussion',
                            'Invalid, then a group post',
                            'Non Responsive, because they asked for a price',
                        ],
                        0,
                        'Discussion first means they have not agreed yet. The conference result decides book, follow up, or a spoken cancel.',
                    ),
                    self::one(
                        'They deny the service after talking to the provider. Is Non Responsive correct?',
                        'No. You spoke to them. Change Status → Cancel with the reason and remarks.',
                        'Yes. A refusal is Non Responsive.',
                        'Non Responsive is silence. A spoken no is Cancel.',
                    ),
                ],
                'WhatsApp to customer' => [
                    self::one(
                        'Which message matches the customer-call template?',
                        '"As per our call — kitchen tap leak, Rajbagh, tomorrow 10 AM. We will look for a partner for that time."',
                        '"Ok send details" with no service and no time.',
                        'The template names the service, the place, the time, and that you are looking for a partner.',
                    ),
                    self::q(
                        'The customer call is over and the remarks are saved. When is that WhatsApp sent?',
                        ['The same minute as the panel update, before the group post', 'After the job is completed only', 'Never on Path B', 'Only if they ask'],
                        0,
                        'WhatsApp is mandatory after the call. The group post waits until it is sent.',
                    ),
                ],
                'Provider group — Path A (direct booking)' => [
                    self::q(
                        'A Path A group message is missing the Lead ID and the landmark. What is wrong?',
                        [
                            'Providers cannot tell which lead it is, and a vague address is ignored. Put the Lead ID on top, then service, problem, address, and time',
                            'Nothing. "Need plumber" is the standard',
                            'It should say Discussion Request',
                            'It should include the customer\'s bank PIN',
                        ],
                        0,
                        'The standard format starts with the Lead ID. The 10-minute SLA starts when that complete message is sent.',
                    ),
                    self::one(
                        'Does the Path A post say the customer wants a discussion before booking?',
                        'No. That sentence belongs on the Path B post. Path A is a direct booking.',
                        'Yes. Every group post is a discussion.',
                        'The title and the details tell the provider whether to say YES for the job or YES for a call.',
                    ),
                ],
                'Provider group — Path B (discussion first)' => [
                    self::q(
                        'What does the Path B group message have to say?',
                        [
                            'Discussion Request, Lead ID, the problem, the address, and the time window for the call. Not a confirmed booking',
                            'The same as Path A, with "send someone now"',
                            'Only the customer first name',
                            'That the lead is Invalid',
                        ],
                        0,
                        'Providers need to know this is a talk-first job. The booking message comes later, only if the customer agrees.',
                    ),
                    self::one(
                        'A provider is free for the discussion. What do you do before the conference?',
                        'Brief the customer, then start the call with you still on it.',
                        'Give the provider the customer number and leave.',
                        'You stay involved. The customer is not handed off alone.',
                    ),
                ],
                'Customer — first call to booking — remember and avoid' => [
                    self::q(
                        'You are checking your customer-lead habits before you log out. Which list is the rule, not the mistake?',
                        [
                            'Path on the first call, Lead ID on the group post, ₹100 before Create Booking, WhatsApp after every touch, Non Responsive if 3 attempts fail and the type stays Customer',
                            'Group post before you speak to a no-pickup customer',
                            'Create Booking before a Path B customer agrees',
                            'Cancel when nobody answered',
                        ],
                        0,
                        'Those are the remember lines. The others are the avoid lines for this section.',
                    ),
                    self::one(
                        'You drop the customer alone on the provider conference. Is that allowed?',
                        'No. You coordinate the call.',
                        'Yes. Your part ended when you posted in the group.',
                        'Leaving them alone is an avoid item. Stay on the call until they decide.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function provider(): array
    {
        $s = 'Provider — onboarding';

        return [
            $s => [
                'Provider onboarding — four steps to Provider registered ✓' => [
                    self::q(
                        'Someone wants to join Panun Kaergar and receive jobs. Which order is onboarding?',
                        [
                            'Brief call, agreement and document deadline the same day, final call only after documents, then Add New Provider and the WhatsApp group',
                            'WhatsApp group first, call later',
                            'Create Booking, then ask them to join',
                            'Final call before any documents',
                        ],
                        0,
                        'They must understand the model before documents. Step 3 is only when documents are in. The provider record is in Provider admin, and the phone must match WhatsApp.',
                    ),
                    self::one(
                        'Can you add them to the provider group before the final call?',
                        'No. The group is step 4, after the final call.',
                        'Yes. The group teaches them the rules.',
                        'The final call explains the 10-minute reply rule and payment. The group comes after that.',
                    ),
                ],
                'First contact — user answers' => [
                    self::q(
                        'They answer a provider lead. What do you explain on the brief call?',
                        [
                            'How jobs arrive, commission, the areas you need, and a document deadline',
                            'A customer visit time for their house',
                            'Nothing. Send the agreement with no conversation',
                            'That they are booked as a customer',
                        ],
                        0,
                        'Step 1 is the model, not a home booking. Then the panel and the agreement WhatsApp go out the same day.',
                    ),
                    self::one(
                        'The call ends. What is in the remarks and on the checklist?',
                        'Trade, area, the deadline, and Agreement sent once the WhatsApp goes out. Followup On is the deadline.',
                        'Only "called". No date.',
                        'The next person has to know the deadline. The checklist tick matches the WhatsApp you actually sent.',
                    ),
                ],
                'No pickup or busy — max 3 attempts to reach' => [
                    self::one(
                        'The provider says they are busy and asks for tomorrow. Is that attempt a close?',
                        'No. Set Followup On for the time they gave, and run the brief call then.',
                        'Yes. Mark as Non Responsive because they did not finish onboarding today.',
                        'Busy with a callback is still an open provider lead. Non Responsive is three real misses.',
                    ),
                    self::q(
                        'Three calls, no answer, type Provider. What is the close?',
                        ['Mark as Non Responsive. Type stays Provider', 'Cancel → Not Interested', 'Customer Path A', 'Invalid'],
                        0,
                        'You never reached them. That is Non Responsive, not a cancel reason.',
                    ),
                ],
                'Step 2 — Documents via WhatsApp' => [
                    self::q(
                        'Step 1 is done and you are writing the provider WhatsApp. What goes out on the agreement message?',
                        [
                            'The agreement, the document list, and a submit-by date. Followup On is that date',
                            'An open-ended "send when you can"',
                            'A customer booking confirmation',
                            'The provider group invite, before any documents',
                        ],
                        0,
                        'The deadline is written. ID, skill proof, and bank details are the usual list. You check WhatsApp on that date.',
                    ),
                    self::one(
                        'The deadline passed. This is document follow-up 3 of 3 and you already spoke on step 1. What is the close?',
                        'Change Status → Cancel, documents not received, with the dates in the remarks.',
                        'Mark as Non Responsive.',
                        'Documents after a real conversation are Cancel. Non Responsive is for a provider you never reached.',
                    ),
                ],
                'Step 3 & 4 — Final call, panel, and WhatsApp group' => [
                    self::q(
                        'Documents arrived. What does the final call cover?',
                        [
                            'Jobs in the group, reply YES within 10 minutes, payment, and conduct. Then Add New Provider and the correct group',
                            'Only "welcome", then Create Booking',
                            'A second agreement with no deadline',
                            'Mark as Customer',
                        ],
                        0,
                        'Step 3 is after documents. Step 4 is Provider admin, the phone must match WhatsApp, then the trade group and the registered status.',
                    ),
                    self::one(
                        'You create "Adil Electrician" in Provider admin. What do you verify on the lead?',
                        'The panel-match link, and that the phone is the WhatsApp number.',
                        'That a customer booking ID exists.',
                        'The lead does not create the provider. It shows whether the provider record matches.',
                    ),
                ],
                'Provider — onboarding — examples' => [
                    self::q(
                        'Web form: electrician, Budgam, answers the first call, documents arrive 8 August, deadline was 10 August. What is the ending?',
                        [
                            'Final call, Add New Provider, electrician group, Provider registered',
                            'Non Responsive, because documents came early',
                            'Customer booking',
                            'Cancel, because they answered',
                        ],
                        0,
                        'That is the full onboarding example. Each step has remarks, and Followup On sits on the document deadline until the documents arrive.',
                    ),
                    self::one(
                        'Step 1 is done, three document reminders, no files. What does the example close with?',
                        'Cancel — documents not received. The remarks list the reminder dates.',
                        'Non Responsive.',
                        'You already reached them on step 1. Missing documents are a cancel.',
                    ),
                ],
                'Flow — provider onboarding' => [
                    self::q(
                        'A provider applicant picks up the first call. Which branch is "they answered"?',
                        [
                            'Step 1 call → agreement → wait for documents → final call → panel and group',
                            'Three misses → Cancel → Not Interested',
                            'Create Booking → group',
                            'Invalid → Future',
                        ],
                        0,
                        'The answered branch is the four steps. The no-answer branch ends at Non Responsive and keeps the type.',
                    ),
                    self::one(
                        'Documents never come after the follow-ups. Which branch is that?',
                        'Cancel, after you had already reached them.',
                        'Non Responsive, even though step 1 happened.',
                        'The flow separates "could not reach" from "reached, but no documents".',
                    ),
                ],
                'WhatsApp to provider' => [
                    self::one(
                        'You are messaging a new partner, not a customer. Which message is the provider template?',
                        'The agreement, the document list, and a date such as 10 August.',
                        'A customer booking summary for their house.',
                        'The provider WhatsApp is the agreement, not a job confirmation.',
                    ),
                    self::q(
                        'You send that WhatsApp. What do you set on the lead the same day?',
                        ['Followup On on the deadline, and the Agreement sent tick', 'No date', 'Non Responsive', 'Create Booking'],
                        0,
                        'The message and the panel date match. Otherwise nobody checks whether the documents arrived.',
                    ),
                ],
                'Provider — onboarding — panel pages' => [
                    self::q(
                        'Documents are in and the final call is done. Which page creates the partner record?',
                        ['/admin/provider/create — Add New Provider', 'Create Booking on the lead', 'Today\'s follow-ups', 'Invalid reasons'],
                        0,
                        'The provider list is the other page. The lead only links to the record after it exists.',
                    ),
                    self::one(
                        'The phone on the provider record is different from WhatsApp. Is that a match?',
                        'No. The phone must match WhatsApp or the group and the panel will not be the same person.',
                        'Yes. Any number is fine.',
                        'The check is explicit: the panel number and the WhatsApp number are the same.',
                    ),
                ],
                'Provider — onboarding — remember and avoid' => [
                    self::q(
                        'You are about to add a new partner to the WhatsApp group. Which line is something to avoid?',
                        [
                            'Adding them to the group before the final call, or cancelling a provider you never reached',
                            'A written document deadline',
                            'Non Responsive after 3 unanswered attempts, type still Provider',
                            'Step 3 only after documents',
                        ],
                        0,
                        'Provider is not a customer. Do not skip the agreement, and do not use the customer booking flow for a partner application.',
                    ),
                    self::one(
                        'Max attempts to reach them, and max document follow-ups. What are the caps?',
                        '3 to reach them, and 3 document follow-ups.',
                        'Unlimited, until they block you.',
                        'Both caps are 3. The close is different: Non Responsive if you never reach them, Cancel if documents never come after you did.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function future(): array
    {
        $s = 'Future customer — nurture & close';

        return [
            $s => [
                'When to use Future customer (not Invalid, not Customer)' => [
                    self::q(
                        'Four people called today. Which person is a Future customer?',
                        [
                            'Saving the number, renovation in a few months, or just moved and not booking today',
                            'Car repair outside the service',
                            'A leaking tap they want fixed tomorrow',
                            'Three missed calls',
                        ],
                        0,
                        'Future is a valid contact with no need today. Wrong service or outside the area is Invalid. A job today is Customer.',
                    ),
                    self::one(
                        'They saw the ad and are only saving the number. Is that a failed lead?',
                        'No. It is a success. Explain the services, pick the reason, and send the warm WhatsApp.',
                        'Yes. Mark Invalid so the report looks cleaner.',
                        'Future customer is a valid close. Invalid is not "not now".',
                    ),
                ],
                'On the call — qualify, inform, close warmly' => [
                    self::q(
                        'What do you do on the Future call before you hang up?',
                        [
                            'Confirm why not today, explain plumbing, electrical, and cleaning, ask for a referral, then the panel reason and the warm WhatsApp',
                            'Say goodbye with no explanation of the services',
                            'Create Booking',
                            'Mark Non Responsive',
                        ],
                        0,
                        'The reason you pick in the dropdown has to be the reason they said. There is no Followup On unless they asked for a callback.',
                    ),
                    self::one(
                        'They mention a neighbour who needs an electrician this week, and they give you the number. Where does the neighbour go?',
                        'A new Customer lead. The Future lead stays closed.',
                        'Only a sentence inside the Future remarks, with no new lead.',
                        'A referral who needs service today is a separate customer. Do not hide them inside the Future lead.',
                    ),
                ],
                'Later — log outbound contact on Future customer lead' => [
                    self::one(
                        'The renovation month arrives. You call the closed Future lead. How is the call stored?',
                        'Add Outbound Enquiry, with call or message, a status, and remarks. The lead stays Future.',
                        'Reopen it as Unknown and run 3 attempts.',
                        'A later check-in is an outbound enquiry. The hero count increases. The full list is Leads → Outbound Enquiries.',
                    ),
                    self::q(
                        'The outbound status asks for a booking ID. What do you do?',
                        ['Pick the status that matches the config, and link the ID if that status requires it', 'Leave every field blank', 'Change the lead to Invalid', 'Delete the Future lead'],
                        0,
                        'Some statuses require a Lead ID or a Booking ID. Follow the status. Remarks say what you said and the outcome.',
                    ),
                ],
                'Future customer — nurture & close — examples' => [
                    self::q(
                        '"I saw your ad — just saving your number." No job today. What is the example result?',
                        [
                            'Future customer, reason Saving number, services explained, warm WhatsApp the same day',
                            'Invalid',
                            'Customer Path A',
                            'Non Responsive',
                        ],
                        0,
                        'The dropdown reason matches the words they used. The WhatsApp asks them to save the number.',
                    ),
                    self::one(
                        'Renovation in October, and the brother needs a plumber this week. What are the two records?',
                        'Future for the caller. Customer for the brother, if you have the number.',
                        'One Invalid lead for both.',
                        'The example keeps the nurture close and starts a real customer lead for the referral.',
                    ),
                ],
                'Flow — Future customer' => [
                    self::q(
                        'They are saving the number and do not need a service today. What does the Future flow end with?',
                        [
                            'Reason, services explained, warm WhatsApp, lead Closed. Outbound enquiry only if you contact them again later',
                            'An open follow-up every day',
                            'A provider group post',
                            'Create Booking',
                        ],
                        0,
                        'Future is closed on purpose. It is not a daily queue item unless they asked you to call back.',
                    ),
                    self::one(
                        'Does this flow ever end at Invalid because they have no job today?',
                        'No. That ending is the mistake the flow is there to prevent.',
                        'Yes. No booking means Invalid.',
                        'The flow exists so "not now" stays Future.',
                    ),
                ],
                'WhatsApp to customer' => [
                    self::one(
                        'You closed the lead as Future customer. What does the Future WhatsApp do?',
                        'Thanks them, names the services, and asks them to save 8899881555.',
                        'Says the booking is confirmed.',
                        'The close should feel like they are welcome to call later. It is not a failure message.',
                    ),
                    self::q(
                        'The Future customer reason is saved on the lead. When is the warm WhatsApp sent?',
                        ['The same minute as the panel update', 'Next month', 'Only if they book', 'Never. Future leads get no message'],
                        0,
                        'The warm close is part of finishing the call, the same as every other WhatsApp-after-call rule.',
                    ),
                ],
                'Future customer — nurture & close — panel pages' => [
                    self::q(
                        'Where do you log a later call, and where is the full list?',
                        [
                            'Add Outbound Enquiry on the lead, and Leads → Outbound Enquiries for the list',
                            'Create Booking',
                            'Mark as Non Responsive',
                            'The provider group',
                        ],
                        0,
                        'The lead button and the standalone list are the two places. Future does not go back on Today\'s follow-ups unless you set a callback they asked for.',
                    ),
                    self::one(
                        'You are saving a Future customer and the remarks already explain the call. Is the reason dropdown optional?',
                        'No. It is required, and it has to match what they said.',
                        'Yes. Remarks are enough.',
                        'Managers read the reason. An empty dropdown is an incomplete Future close.',
                    ),
                ],
                'Future customer — nurture & close — remember and avoid' => [
                    self::q(
                        'You are closing a "not now" call. Which of these is an avoid item?',
                        [
                            'Invalid for "no need now", closing without explaining the services, or skipping the reason dropdown',
                            'Asking for a referral',
                            'A warm WhatsApp',
                            'Logging a later call as an outbound enquiry',
                        ],
                        0,
                        'Future is a success. Explain at least a few services, pick the reason, and ask if anyone needs help today.',
                    ),
                    self::one(
                        'A referral number is sitting only in your memory. Is the Future lead finished?',
                        'No. Create the Customer lead for that person, and mention the referral in the remarks.',
                        'Yes. You can tell the next shift verbally.',
                        'A referral who needs service today is today\'s customer, not a story you remember.',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function invalid(): array
    {
        $s = 'Invalid — polite close';

        return [
            $s => [
                'When to use Invalid — confirm before you close' => [
                    self::q(
                        'You have confirmed what they asked for. Which request is Invalid?',
                        [
                            'iPhone screen repair, or plumbing in a city Panun Kaergar does not cover',
                            'Saving the number for later',
                            'A kitchen leak tomorrow in Rajbagh',
                            'No answer after 3 calls',
                        ],
                        0,
                        'Invalid is a wrong service or outside the area, after you have confirmed it. "Not now" is Future. Silence is Non Responsive. A job you can do is Customer.',
                    ),
                    self::one(
                        'You are busy, and the customer needs a plumber you do offer. Can you mark Invalid?',
                        'No. Invalid is not for being busy or unwilling. If you can help, they stay Customer.',
                        'Yes. Invalid clears the queue.',
                        'Confirm you truly cannot fulfil the request before you close it.',
                    ),
                ],
                'Confirm, document, close politely' => [
                    self::q(
                        'They want car AC repair in Jammu. What do you say and save?',
                        [
                            'A polite no, what you do offer, Invalid with the reason, and remarks that name the request and the place. Then the WhatsApp',
                            'Hang up with no remarks',
                            'Future customer, reason renovation',
                            'Create Booking for a home electrician',
                        ],
                        0,
                        'The remarks and the dropdown have to agree. The tone stays professional.',
                    ),
                    self::one(
                        'Is Followup On required after a correct Invalid close?',
                        'No, unless they also had a separate need you can help with.',
                        'Yes. Call them every day.',
                        'Invalid is closed. There is no follow-up for a request you cannot do.',
                    ),
                ],
                'Invalid — polite close — examples' => [
                    self::q(
                        '"Can you fix my iPhone screen?" What is the example close?',
                        [
                            'Invalid, service not offered, remarks "Mobile screen repair — not offered", polite WhatsApp',
                            'Customer Path B',
                            'Non Responsive',
                            'Provider onboarding',
                        ],
                        0,
                        'Name the request in the remarks. The WhatsApp says what you cannot do and what you do offer.',
                    ),
                    self::one(
                        'A web form asks for plumbing in a city you do not cover. What reason fits?',
                        'Area not covered, with the location in the remarks.',
                        'Did not know about the enquiry.',
                        'Outside the area is an Invalid reason. "Did not know" is not the no-answer close, and this person did tell you the city.',
                    ),
                ],
                'Flow — Invalid close' => [
                    self::q(
                        'They asked for something Panun Kaergar does not do. What is the Invalid flow?',
                        [
                            'Confirm you cannot help, explain politely, type Invalid plus the reason, WhatsApp, closed',
                            'Three attempts, then Invalid',
                            'Group post, then Invalid',
                            'Future reason, then a booking',
                        ],
                        0,
                        'The flow is a spoken, documented, polite no. It is not the silence flow.',
                    ),
                    self::one(
                        'The flow starts and you realise you do offer this service in this area. What do you do?',
                        'Stop. They are a Customer. Do not finish the Invalid close.',
                        'Finish Invalid, because you already opened the flow.',
                        'Invalid is only after you confirm you cannot fulfil it.',
                    ),
                ],
                'WhatsApp to customer' => [
                    self::one(
                        'You marked the lead Invalid after a polite no on the phone. What does the Invalid WhatsApp contain?',
                        'Sorry you cannot help with that request, the services you do offer, and the number 8899881555.',
                        'A provider name and a visit time.',
                        'The template is specific. "Cannot help" with no detail is not enough, and the tone is not rude.',
                    ),
                    self::q(
                        'The Invalid reason is saved and the remarks name the request. When do you send the WhatsApp?',
                        ['The same minute you save the Invalid reason', 'Never', 'A week later', 'Only if they complain'],
                        0,
                        'The WhatsApp is part of the close, not an optional extra.',
                    ),
                ],
                'Invalid — polite close — remember and avoid' => [
                    self::q(
                        'You are about to mark a lead Invalid. Which of these is an avoid item?',
                        [
                            'Invalid for a repair you can do, vague remarks such as "cannot help", or a rude WhatsApp',
                            'Writing the service name and the location',
                            'A reason that matches the remarks',
                            'A polite explanation of home services',
                        ],
                        0,
                        'Invalid is a professional close. The remarks have to say exactly what they asked for.',
                    ),
                    self::one(
                        'They have no need today and they were polite. Is Invalid the remember rule?',
                        'No. That is Future customer. Invalid is not "no need now".',
                        'Yes. Any lead with no booking is Invalid.',
                        'The remember list says never use Invalid for "no need now".',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>>>
     */
    private static function certification(): array
    {
        return [
            'Expert certification quiz' => [
                'Before the scenarios' => [
                    self::one(
                        'The scenario questions use the rules from the lessons. What score passes a lesson?',
                        'Every question in that lesson must be correct.',
                        'Half the questions are enough.',
                        'A pass means you can run the lead process, including the close rules, without guessing.',
                    ),
                    self::q(
                        'Booking follow-up on the service day, and payment after the visit, are not in these scenarios. Where are they taught?',
                        ['In this same lesson, as extra questions', 'In a separate booking course', 'They are not part of anyone\'s job', 'Inside Invalid reasons'],
                        1,
                        'This course ends at the lead process and Create Booking. The service-day follow-up is the next course.',
                    ),
                ],
            ],
        ];
    }
}

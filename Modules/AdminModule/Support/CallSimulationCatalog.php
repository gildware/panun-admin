<?php

namespace Modules\AdminModule\Support;

class CallSimulationCatalog
{
    /**
     * @return array<string, array{name: string, blurb: string, probe: ?string}>
     */
    public static function skills(): array
    {
        return [
            'greeting' => [
                'name' => 'Greeting and professionalism',
                'blurb' => 'Opens as Panun Kaergar, with a calm and polite tone.',
                'probe' => null,
            ],
            'listening' => [
                'name' => 'Active listening and understanding',
                'blurb' => 'Shows they heard the actual problem, not a generic script.',
                'probe' => 'Can you say back what you understood, so I know you heard me?',
            ],
            'questioning' => [
                'name' => 'Questioning and information gathering',
                'blurb' => 'Asks for the details needed before acting.',
                'probe' => null,
            ],
            'language' => [
                'name' => 'Communication and language proficiency',
                'blurb' => 'Speaks clearly enough for the caller to follow.',
                'probe' => null,
            ],
            'empathy' => [
                'name' => 'Empathy and patience',
                'blurb' => 'Acknowledges how the situation feels before fixing it.',
                'probe' => 'I am quite stressed about this. Do you actually understand that?',
            ],
            'problem' => [
                'name' => 'Problem-solving',
                'blurb' => 'Gives a concrete next step, not a vague promise.',
                'probe' => 'So what exactly will you do now?',
            ],
            'sop' => [
                'name' => 'SOP compliance',
                'blurb' => 'Follows the call flow: details, notes, WhatsApp, and a next time.',
                'probe' => 'What will you write on the booking after this call?',
            ],
            'policy' => [
                'name' => 'Policy and pricing knowledge',
                'blurb' => 'Uses the published price. No invented discount or promised time.',
                'probe' => 'What is the charge, and can you reduce it if I book right now?',
            ],
            'objection' => [
                'name' => 'Objection handling',
                'blurb' => 'Hears the objection and offers a real option.',
                'probe' => 'I am not convinced. Why should I stay with this?',
            ],
            'decision' => [
                'name' => 'Decision-making and authority',
                'blurb' => 'Says clearly what they can decide and what they cannot.',
                'probe' => 'Can you decide this yourself, or do you need someone else?',
            ],
            'escalation' => [
                'name' => 'Escalation handling',
                'blurb' => 'Brings in a manager when the call is beyond their authority.',
                'probe' => 'Then I want to speak to a manager.',
            ],
            'followup' => [
                'name' => 'Follow-up ownership',
                'blurb' => 'Names who will call back and when.',
                'probe' => 'Who will call me back, and when?',
            ],
            'documentation' => [
                'name' => 'Documentation and booking updates',
                'blurb' => 'Says what will be saved on the lead or booking.',
                'probe' => 'Will this be saved properly, or will the next person not know?',
            ],
            'closure' => [
                'name' => 'Call closure',
                'blurb' => 'Checks nothing is left and closes politely.',
                'probe' => null,
            ],
            'adaptability' => [
                'name' => 'Adaptability and handling unexpected questions',
                'blurb' => 'Answers a question that was not part of the main request.',
                'probe' => null,
            ],
            'conflict' => [
                'name' => 'Conflict resolution',
                'blurb' => 'Stays calm, does not argue, and moves the complaint forward.',
                'probe' => null,
            ],
            'time' => [
                'name' => 'Time management and call control',
                'blurb' => 'Keeps the call moving and does not end it too early.',
                'probe' => null,
            ],
            'accuracy' => [
                'name' => 'Accuracy of information',
                'blurb' => 'Does not invent a time, a price, or an approval.',
                'probe' => 'Is that the real information, or are you guessing?',
            ],
            'satisfaction' => [
                'name' => 'Customer satisfaction management',
                'blurb' => 'Checks the caller is clear and willing to go ahead.',
                'probe' => 'Will this actually sort it for me?',
            ],
            'safety' => [
                'name' => 'Safety and risk awareness',
                'blurb' => 'Stops work that is unsafe or outside what Panun Kaergar should send someone to do.',
                'probe' => null,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function scenarios(): array
    {
        $rows = [
            'new-service' => self::pack(
                'New service inquiry',
                'A customer needs a plumber for a leaking kitchen tap. She gives details only when asked, then pushes for a price and a 30-minute visit.',
                'Rukhsana',
                'Customer',
                'Hello? Is this Panun Kaergar? My kitchen tap is leaking.',
                'Also, my neighbour said you only take cash. Is that true?',
                'Thank you. Please WhatsApp me once a provider is actually set.',
                ['leak', 'tap', 'kitchen', 'drip'],
                [
                    ['when' => ['ask_problem'], 'line' => 'It has been dripping since last night. Water is collecting under the sink.'],
                    ['when' => ['ask_area'], 'line' => 'I am in Rajbagh, near the market. House number 14.'],
                    ['when' => ['ask_time'], 'line' => 'Tomorrow morning is fine, any time after 9.'],
                    ['when' => ['ask_name'], 'line' => 'My name is Rukhsana Bhat.'],
                ],
                [
                    ['when' => ['promise_soon'], 'line' => 'Good. So someone will be at my house within 30 minutes. I will wait.'],
                    ['when' => ['discount'], 'line' => 'If you reduce it, I will book right now.'],
                    ['when' => ['no_discount'], 'line' => 'Fine. Then give me the published price, and confirm the time only after a provider is set.'],
                    ['when' => ['price'], 'line' => 'All right. I do not want a guessed time. Confirm it after the provider is fixed.'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['price', 'no_discount'], 'line' => 'Before I decide, what will you charge?'],
                    ['after_turn' => 3, 'unless_intent' => ['promise_soon', 'no_promise'], 'line' => 'Can someone come in the next 30 minutes? It is getting worse.'],
                    ['after_turn' => 4, 'unless_intent' => ['ask_area'], 'line' => 'Are you going to ask where the house is?'],
                ],
                ['I need to know the next step.', 'What will you do after this call?'],
                ['policy' => 'Can you do it cheaper if I confirm right now?', 'safety' => 'The wire under the sink sparks a little. Your plumber can still do it, yes?']
            ),
            'incomplete-details' => self::pack(
                'Incomplete customer details',
                'The caller wants someone to come, and stays vague until the employee asks for the problem, the address, and a time.',
                'Farooq',
                'Customer',
                'Hi. I need someone to come. It is the usual problem at my place.',
                'One more thing. Can the person also look at the inverter, or is that a different booking?',
                'Please repeat the service, the area, and the time before you close, so it is not saved wrong.',
                ['usual', 'someone', 'place'],
                [
                    ['when' => ['ask_problem'], 'line' => 'The bathroom drain is blocked. Water is not going down.'],
                    ['when' => ['ask_area'], 'line' => 'Lal Bazar, lane 3. The blue gate.'],
                    ['when' => ['ask_time'], 'line' => 'This evening, after 5, if you have someone.'],
                    ['when' => ['ask_name'], 'line' => 'Farooq Ahmad.'],
                ],
                [
                    ['when' => ['promise_soon'], 'line' => 'You promised evening without knowing the address. Please do not do that.'],
                    ['when' => ['document'], 'line' => 'Good. Save the drain, Lal Bazar, and this evening. Not just “someone”.'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['ask_problem'], 'line' => 'You have not asked what the problem is. I only said it is the usual one.'],
                    ['after_turn' => 3, 'unless_intent' => ['ask_area'], 'line' => 'You still do not have my address.'],
                    ['after_turn' => 4, 'unless_intent' => ['ask_time'], 'line' => 'And you have not asked when I am free.'],
                ],
                ['That is not enough to send anyone.', 'I am not telling you again unless you ask a proper question.'],
                ['documentation' => 'How will you write this so the provider knows the drain and the lane?']
            ),
            'angry-complaint' => self::pack(
                'Angry customer or complaint',
                'A customer is upset because the plumber was rude in front of her family and left the tap leaking.',
                'Aisha',
                'Customer',
                'I am very angry. Your plumber shouted at me and left the tap leaking.',
                'And do not tell me to calm down. Tell me what Panun Kaergar will do today.',
                'I will wait for that call. Do not disappear after this.',
                ['angry', 'shouted', 'leaking', 'rude'],
                [
                    ['when' => ['empathy'], 'line' => 'It was embarrassing. He raised his voice in front of my family and then left.'],
                    ['when' => ['ask_problem'], 'line' => 'The kitchen tap is still leaking. He was here this morning and did not finish.'],
                    ['when' => ['ask_area'], 'line' => 'Jawahar Nagar. The booking is in my name, Aisha.'],
                    ['when' => ['escalate'], 'line' => 'Good. I want a manager to know. I do not want the same person sent back without a warning.'],
                    ['when' => ['reassign'], 'line' => 'Send a different plumber, and tell me the time after you have one. Not a guess.'],
                ],
                [
                    ['when' => ['blame'], 'line' => 'Do not put this on me. Your person left the job unfinished.'],
                    ['when' => ['rude'], 'line' => 'Speak to me properly. I called to complain, not to be insulted.'],
                    ['when' => ['promise_soon'], 'line' => 'You just promised a time again. That is how this went wrong last time.'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['empathy'], 'line' => 'You have not even said you are sorry. I am still upset.'],
                    ['after_turn' => 3, 'unless_intent' => ['reassign', 'problem', 'escalate'], 'line' => 'What will you actually do about the unfinished tap?'],
                ],
                ['I am waiting.', 'So what happens next?'],
                ['conflict' => 'I do not want excuses. I want the job finished by someone else.']
            ),
            'pricing-refund' => self::pack(
                'Pricing, discounts and refunds',
                'The geyser still does not heat. The caller wants the visit charge back and money off a second visit, and will not accept a guessed discount.',
                'Imran',
                'Customer',
                'Your man came, looked at the geyser, and left. I want the visit charge back.',
                'Someone told me other companies waive the visit charge. Why not you?',
                'Note the refund request properly. I will wait for the decision you are allowed to give.',
                ['refund', 'visit', 'discount', 'geyser'],
                [
                    ['when' => ['ask_problem'], 'line' => 'The geyser is not heating. He looked for ten minutes and said he would come later. He did not.'],
                    ['when' => ['empathy'], 'line' => 'I paid the visit charge and I still have no hot water.'],
                    ['when' => ['no_discount'], 'line' => 'All right. If you cannot discount it, say so clearly and tell me the published price.'],
                    ['when' => ['escalate'], 'line' => 'Then put the refund to a manager. Do not promise it yourself if you cannot.'],
                    ['when' => ['decision'], 'line' => 'Good. Tell me which part you can decide and which part a manager must confirm.'],
                ],
                [
                    ['when' => ['discount'], 'line' => 'You just offered money off. I will hold you to that.'],
                    ['when' => ['refund_promise'], 'line' => 'You said the visit charge will be refunded. I am taking that as final.'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['no_discount', 'discount', 'price'], 'line' => 'Can you take 20 percent off the next visit if I book it now?'],
                    ['after_turn' => 3, 'unless_intent' => ['escalate', 'decision', 'refund_promise'], 'line' => 'So is the visit charge coming back or not? I need a straight answer.'],
                ],
                ['I need a clear answer on the money.', 'Do not leave the refund hanging.'],
                ['policy' => 'If I pay cash, can you reduce the bill on the spot?']
            ),
            'hesitant-cancel' => self::pack(
                'Hesitant customer or cancellation',
                'A customer wants to cancel tomorrow’s cleaning. She may keep it if she is given a real reason and is not pushed.',
                'Saima',
                'Customer',
                'I think I should cancel the cleaning tomorrow. I am not sure you will send the right person.',
                'If I cancel, do I lose the booking amount? Be straight with me.',
                'All right. Save whichever choice I made, and do not call me five times after this.',
                ['cancel', 'cleaning', 'tomorrow'],
                [
                    ['when' => ['empathy'], 'line' => 'I had a bad experience with another company, so I am nervous.'],
                    ['when' => ['reschedule'], 'line' => 'Saturday morning could work, if it is the same booking and not a new charge.'],
                    ['when' => ['cancel_ok'], 'line' => 'Then cancel it. Please tell me if any amount is held, and do not invent a penalty.'],
                    ['when' => ['ask_name'], 'line' => 'Saima Rashid. The cleaning is for a 2BHK in Bemina.'],
                ],
                [
                    ['when' => ['pressure'], 'line' => 'Do not push me. If you force the booking I will cancel anyway.'],
                    ['when' => ['discount'], 'line' => 'I did not ask for a discount. I asked whether I should cancel.'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['reschedule', 'cancel_ok', 'objection'], 'line' => 'Give me one reason to keep it, without forcing me.'],
                    ['after_turn' => 4, 'unless_intent' => ['document'], 'line' => 'Whatever I decide, write the reason on the booking.'],
                ],
                ['I am still unsure.', 'Tell me the option again, shortly.'],
                ['objection' => 'My sister said your cleaners come late. Why would tomorrow be different?']
            ),
            'failed-repair' => self::pack(
                'Failed repair or unresolved service',
                'A plumber sealed a bathroom pipe yesterday and it is leaking again, worse. The customer wants the failed job put right, not a new booking.',
                'Bilal',
                'Customer',
                'Your plumber came yesterday. This morning the bathroom is leaking again, worse.',
                'Do I have to pay the full visit again for your mistake?',
                'Tell me who is coming back, or who will call me with a confirmed time.',
                ['leak', 'again', 'yesterday', 'bathroom'],
                [
                    ['when' => ['empathy'], 'line' => 'The ceiling of the floor below is getting wet now. I am worried.'],
                    ['when' => ['ask_problem'], 'line' => 'It is the bathroom pipe he sealed yesterday. Water is dripping again.'],
                    ['when' => ['reassign'], 'line' => 'Send someone who can actually finish it. I do not want the same incomplete job.'],
                    ['when' => ['decision'], 'line' => 'If a revisit is free, say that only if it is the real policy. Otherwise tell me a manager will confirm.'],
                    ['when' => ['followup'], 'line' => 'Call me today with the confirmed time. Not a message that says soon.'],
                ],
                [
                    ['when' => ['blame'], 'line' => 'It is not my fault the seal failed. Your person did the work.'],
                    ['when' => ['promise_soon'], 'line' => 'You promised a quick revisit without checking who is free. Please stop doing that.'],
                    ['when' => ['refund_promise'], 'line' => 'You promised I will not be charged. I will expect that in writing.'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['empathy'], 'line' => 'This has wasted my day. I need you to take it seriously.'],
                    ['after_turn' => 3, 'unless_intent' => ['reassign', 'problem', 'followup'], 'line' => 'What is the actual plan for the leak today?'],
                ],
                ['I need this noted as a failed repair.', 'Who owns the next call?'],
                ['accuracy' => 'Is the second visit free, yes or no? Do not guess.']
            ),
            'provider-late' => self::pack(
                'Provider unavailable, late or no-show',
                'The electrician was due at 10. It is 11:30, nobody has called, and the customer will not accept a guessed excuse.',
                'Nazia',
                'Customer',
                'Your electrician was supposed to be here at 10. It is 11:30 and nobody has called me. Where is he?',
                'I have to leave for work. If he cannot come, say that now.',
                'Message me the new time only after someone is actually assigned.',
                ['late', 'electrician', '10', '11:30'],
                [
                    ['when' => ['empathy'], 'line' => 'I stayed home from work for this. I am sitting here waiting.'],
                    ['when' => ['reassign'], 'line' => 'Then send another electrician, and tell me the new time only when it is confirmed.'],
                    ['when' => ['followup'], 'line' => 'Call me within 15 minutes with a real update. Do not leave me guessing.'],
                    ['when' => ['ask_time'], 'line' => 'I can wait until 1 if you confirm a person. After that I have to leave.'],
                ],
                [
                    ['when' => ['excuse'], 'line' => 'Do not invent a reason. You do not know why he is late unless you have checked.'],
                    ['when' => ['promise_soon'], 'line' => 'You said he is on the way. Is that true, or did you just say it to calm me?'],
                    ['when' => ['blame'], 'line' => 'This is your booking. Do not tell me these delays happen.'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['empathy'], 'line' => 'An apology would help. I have been waiting for an hour and a half.'],
                    ['after_turn' => 3, 'unless_intent' => ['reassign', 'followup', 'problem'], 'line' => 'Is he coming or are you sending someone else?'],
                ],
                ['I need an update I can trust.', 'Please do not say soon.'],
                ['accuracy' => 'Are you sure he is coming, or are you assuming?']
            ),
            'provider-onboarding' => self::pack(
                'Provider onboarding and verification',
                'An electrician in Budgam wants to join today. He must not be approved before his documents are checked.',
                'Tariq',
                'Provider',
                'Hello. I am an electrician in Budgam and I want to work with Panun Kaergar. Can you add me today?',
                'What percent do you keep, and how fast do I have to reply when a job is posted?',
                'I will send the documents. Do not put me on jobs until you have checked them.',
                ['electrician', 'budgam', 'join', 'work'],
                [
                    ['when' => ['ask_problem'], 'line' => 'I do house wiring, fans, and switchboards. I do not do high-voltage line work.'],
                    ['when' => ['ask_area'], 'line' => 'Budgam, and I can cover Srinagar if the job is planned.'],
                    ['when' => ['need_docs'], 'line' => 'I can send my ID and a photo today. Tell me exactly which documents you need.'],
                    ['when' => ['followup'], 'line' => 'Call me when you have checked the documents. I understand I am not approved yet.'],
                ],
                [
                    ['when' => ['approve_now'], 'line' => 'So I am approved and I can take jobs from today? I have not sent any documents.'],
                    ['when' => ['ask_name'], 'line' => 'Tariq Hussain.'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['need_docs'], 'line' => 'What documents do you need before I can join?'],
                    ['after_turn' => 3, 'unless_intent' => ['approve_now', 'decision'], 'line' => 'Can you approve me on this call, or not yet?'],
                ],
                ['Tell me the next step clearly.', 'I do not want a half approval.'],
                ['decision' => 'If my ID is unclear, who decides whether I can join?']
            ),
            'provider-reassign' => self::pack(
                'Provider allocation and reassignment',
                'The assigned plumber refused today’s job. The customer took leave and needs a real reassignment, not a guess.',
                'Mehnaz',
                'Customer',
                'You told me a plumber was assigned for today. He just told me he is not coming. What happens now?',
                'Please do not post it in a group and forget me. I need to know who owns this.',
                'WhatsApp me when the new person is confirmed. Until then I will assume nobody is coming.',
                ['assigned', 'not coming', 'plumber', 'today'],
                [
                    ['when' => ['empathy'], 'line' => 'I took leave for this visit. I cannot do that again tomorrow for nothing.'],
                    ['when' => ['reassign'], 'line' => 'All right. Find another plumber and tell me only when that person has agreed.'],
                    ['when' => ['ask_time'], 'line' => 'Today after 4 still works. Tomorrow morning also works if today is impossible.'],
                    ['when' => ['followup'], 'line' => 'Call me as soon as someone accepts. I do not want silence.'],
                    ['when' => ['document'], 'line' => 'Please write that the first plumber refused, so this does not look like my cancellation.'],
                ],
                [
                    ['when' => ['blame'], 'line' => 'I did not refuse the job. Your provider did.'],
                    ['when' => ['promise_soon'], 'line' => 'You said another plumber is coming shortly. Has anyone actually agreed?'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['reassign', 'problem'], 'line' => 'Are you finding someone else or closing my booking?'],
                    ['after_turn' => 3, 'unless_intent' => ['document', 'followup'], 'line' => 'Note that he refused. I do not want this counted against me.'],
                ],
                ['I am still waiting for a real plan.', 'Who is handling this after we hang up?'],
                ['followup' => 'If nobody accepts in the group, will you call other plumbers yourself?']
            ),
            'unsafe-job' => self::pack(
                'Unsafe or out-of-scope job',
                'The caller wants a regular electrician to open a sparking main board today. The employee must stop the unsafe job and say what to do instead.',
                'Yousuf',
                'Customer',
                'I need someone today to fix my main electric board. It is sparking, and I want your regular electrician to open it.',
                'It is only a small spark. Surely your normal electrician can do the main line?',
                'If you will not send someone into that, say what I should do instead, and note why you refused.',
                ['spark', 'main', 'board', 'electric'],
                [
                    ['when' => ['ask_problem'], 'line' => 'The main board sparks when the pump starts. I already tried to tighten a wire myself.'],
                    ['when' => ['unsafe_stop'], 'line' => 'All right. If your electrician must not open a live main board, say that clearly and tell me what to do instead.'],
                    ['when' => ['escalate'], 'line' => 'If a senior has to confirm that this is out of scope, do that and call me back.'],
                    ['when' => ['ask_area'], 'line' => 'Hyderpora. But do not send a person until you are sure it is safe.'],
                ],
                [
                    ['when' => ['promise_soon'], 'line' => 'You are sending someone to a sparking main board today? That does not sound safe.'],
                    ['when' => ['blame'], 'line' => 'I called you for help. Do not scold me. Tell me the safe option.'],
                ],
                [
                    ['after_turn' => 2, 'unless_intent' => ['unsafe_stop', 'escalate'], 'line' => 'So will you send the regular electrician to open the main board or not?'],
                    ['after_turn' => 3, 'unless_intent' => ['document'], 'line' => 'Write the reason if you refuse, so nobody else sends a person by mistake.'],
                ],
                ['I need a clear yes or no on sending someone.', 'Do not leave me with a sparking board and no answer.'],
                ['safety' => 'The sparks are small. Your man can switch the main off and do it, yes?']
            ),
        ];

        foreach (self::directors() as $id => $director) {
            if (! isset($rows[$id])) {
                continue;
            }
            $rows[$id]['guide'] = $director['guide'];
            $rows[$id]['test_skills'] = $director['skills'];
        }

        return $rows;
    }

    /**
     * How the caller behaves on this call, so the selected skills can be heard.
     *
     * @return array<string, array{guide: string, skills: list<string>}>
     */
    private static function directors(): array
    {
        return [
            'new-service' => [
                'skills' => ['greeting', 'questioning', 'language', 'policy', 'accuracy', 'followup', 'documentation', 'closure'],
                'guide' => 'You need a plumber for a leaking kitchen tap. Open with that only. If they do not greet you or name Panun Kaergar, sound unsure who you reached. Give the drip, Rajbagh, a time, and your name only when they ask that thing. Then ask the charge, and ask if someone can come in 30 minutes. If they name a time or a cheaper price before a provider is set, repeat it and hold them to it. If they give only the published price and will not confirm a time yet, accept that. Once, ask if they only take cash. Before you finish, ask what they will save and ask them to WhatsApp you when a provider is actually set.',
            ],
            'incomplete-details' => [
                'skills' => ['questioning', 'listening', 'sop', 'documentation', 'time', 'accuracy'],
                'guide' => 'You want someone to come, but you only say it is the usual problem at your place. Do not mention the blocked bathroom drain, Lal Bazar, this evening, or your name until they ask that exact thing. If they guess the job, correct them. If they promise a visit before they know the address, say they cannot send anyone yet. If they try to close, ask them to repeat the service, the area, and the time. If they have not asked a proper question, stay vague. Once, ask if the same person can also look at the inverter.',
            ],
            'angry-complaint' => [
                'skills' => ['empathy', 'conflict', 'listening', 'escalation', 'problem', 'satisfaction'],
                'guide' => 'You are angry. The plumber shouted at you in front of your family and left the kitchen tap leaking. Stay upset until they acknowledge that. Do not accept “calm down”, and do not accept blame. If they only say sorry and stop, ask what happens to the unfinished tap today. Do not accept the same plumber back. If they promise a time before another person is confirmed, challenge it. Ease off only when a different person will come, a manager will know, and they will call you with a confirmed time. If they ignore how it felt, say you are still waiting for that.',
            ],
            'pricing-refund' => [
                'skills' => ['policy', 'objection', 'decision', 'accuracy', 'escalation', 'empathy'],
                'guide' => 'The geyser still does not heat. The man only looked and left, and you paid the visit charge. You want that charge back and money off the next visit. Ask for 20 percent off. If they invent a discount or promise the refund, repeat it and hold them to it. If they say they cannot change the published price and a manager must decide the refund, accept that and ask them to note the request. Ask which part they can decide themselves. Once, say other companies waive the visit charge. Do not accept “we will see”.',
            ],
            'hesitant-cancel' => [
                'skills' => ['objection', 'empathy', 'decision', 'satisfaction', 'documentation', 'adaptability'],
                'guide' => 'You want to cancel tomorrow’s cleaning because you are not sure they will send the right person. You may keep it if they give one real reason without forcing you. Do not accept a discount you did not ask for, or a penalty they made up. Ask whether cancelling loses the booking amount. Once, say your sister said the cleaners come late, and ask why tomorrow would be different. If you decide, ask them to save the choice and the reason. If they pressure you, pull back.',
            ],
            'failed-repair' => [
                'skills' => ['problem', 'empathy', 'accuracy', 'followup', 'conflict', 'sop'],
                'guide' => 'A plumber sealed the bathroom pipe yesterday and it is leaking again, worse, and the ceiling below is getting wet. Treat this as their failed job, not a new booking. Ask if the second visit is free, and do not accept a guess. If they promise a quick revisit or a free visit they have not confirmed, hold them to it. If they blame you, correct them. You can close when they take the worry seriously, will send someone who can finish it, and name who will call today with a confirmed time.',
            ],
            'provider-late' => [
                'skills' => ['empathy', 'accuracy', 'time', 'followup', 'problem', 'listening'],
                'guide' => 'The electrician was due at 10. It is 11:30 and nobody has called. You stayed home from work. Do not accept an invented excuse, or “he is on the way”, unless they have actually checked. If they say “soon” or “these things happen”, push back. You can wait until 1 only if a person is confirmed. Ask whether he is coming or they are sending someone else, and who will call you with a real update. An apology matters, then a checked plan.',
            ],
            'provider-onboarding' => [
                'skills' => ['questioning', 'decision', 'policy', 'documentation', 'accuracy', 'sop'],
                'guide' => 'You are an electrician in Budgam who wants to join. You do house wiring, fans, and switchboards, not high-voltage lines. Ask to be added today. If they approve you before documents, act surprised and say you have not sent any ID. Ask which documents they need, what share they keep, and how fast you must reply to a job. Ask who decides if the ID is unclear. Accept that you are not approved, and not on jobs, until the documents are checked. Do not accept a half approval.',
            ],
            'provider-reassign' => [
                'skills' => ['problem', 'followup', 'documentation', 'escalation', 'time', 'satisfaction'],
                'guide' => 'A plumber was assigned for today and just told you he is not coming. You took leave. Do not accept blame. Ask if they are finding someone else or closing the booking. Today after 4, or tomorrow morning, works only after a person has agreed. If they say someone is coming shortly, ask whether that person has actually agreed. Ask them to note that the provider refused, not you, and to WhatsApp you when the new person is confirmed. Once, ask whether they will call other plumbers if nobody accepts in the group.',
            ],
            'unsafe-job' => [
                'skills' => ['safety', 'decision', 'escalation', 'accuracy', 'documentation', 'adaptability'],
                'guide' => 'You want a regular electrician today to open a sparking main board. You already tightened a wire yourself. At first, talk as if this is a normal switch job. Then push once that the spark is small and they can switch the main off. If they agree to send someone to open the live board, sound worried that it is unsafe. Accept it when they refuse clearly, tell you what to do instead, and say they will note why they refused. Do not let them treat it as a normal switch repair, or leave you without a yes or no.',
            ],
        ];
    }

    public static function scenario(string $id): ?array
    {
        return self::scenarios()[$id] ?? null;
    }

    public static function skill(string $id): ?array
    {
        return self::skills()[$id] ?? null;
    }

    /**
     * @param  list<string>  $listenFor
     * @param  list<array<string, mixed>>  $facts
     * @param  list<array<string, mixed>>  $reactions
     * @param  list<array<string, mixed>>  $probes
     * @param  list<string>  $nudges
     * @param  array<string, string>  $skillProbes
     * @return array<string, mixed>
     */
    private static function pack(
        string $name,
        string $summary,
        string $callerName,
        string $callerRole,
        string $opening,
        string $curveball,
        string $close,
        array $listenFor,
        array $facts,
        array $reactions,
        array $probes,
        array $nudges,
        array $skillProbes
    ): array {
        return [
            'name' => $name,
            'summary' => $summary,
            'caller_name' => $callerName,
            'caller_role' => $callerRole,
            'opening' => $opening,
            'curveball' => $curveball,
            'close' => $close,
            'listen_for' => $listenFor,
            'facts' => $facts,
            'reactions' => $reactions,
            'probes' => $probes,
            'nudges' => $nudges,
            'skill_probes' => $skillProbes,
            'min_turns' => 4,
            'max_turns' => 9,
        ];
    }
}

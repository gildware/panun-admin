<?php

namespace Modules\AdminModule\Support;

/**
 * Training for the Panun Kaergar Service Provider registration form,
 * onboarding questionnaire, and Service Provider Agreement.
 * Passing every question means the person understands the form and contract.
 */
class ServiceProviderAgreementCourseBuilder
{
    public const NAME = 'Service Provider Form & Agreement';

    public const PDF_PUBLIC_PATH = 'assets/admin-module/process-guide/training/PK_Revised_Agreement.pdf';

    /** Public CDN copy used on live (and fine locally). */
    public const PDF_CDN_URL = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/training/PK_Revised_Agreement.pdf';

    /**
     * @return array<int, array{title: string, lessons: array<int, array{title: string, body: string, questions: array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>}>}>
     */
    public function build(): array
    {
        return [
            $this->sectionPdfPack(),
            $this->sectionRegistration(),
            $this->sectionOnboarding(),
            $this->sectionPurposeStatus(),
            $this->sectionRoles(),
            $this->sectionPricingPayment(),
            $this->sectionCommission(),
            $this->sectionQualityEvidence(),
            $this->sectionOpsRules(),
            $this->sectionLegalClose(),
            $this->sectionCertification(),
        ];
    }

    public static function pdfUrl(): string
    {
        return self::PDF_CDN_URL;
    }

    /**
     * @param  array<int, string>  $options
     * @return array{prompt: string, options: array<int, string>, correct: int, explanation: string}
     */
    private function q(string $prompt, array $options, int $correct, string $why): array
    {
        return [
            'prompt' => $prompt,
            'options' => array_values($options),
            'correct' => $correct,
            'explanation' => $why,
        ];
    }

    private function pdfEmbed(string $hint = 'Scroll every page of the official pack before you continue.'): string
    {
        $url = e(self::pdfUrl());
        // Inline styles so live works even before CSS deploy; open-in-new-tab is primary.
        return <<<HTML
<div class="lq-pdf" style="margin:0 0 16px;border:1px solid #d7e0dc;border-radius:12px;overflow:hidden;background:#f8faf9;">
<div class="lq-pdf-bar" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid #d7e0dc;background:#fff;">
<span style="font-size:14px;line-height:1.4;"><strong>Official pack (PDF)</strong> — {$hint}</span>
<a href="{$url}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 14px;border-radius:8px;background:#1f2937;color:#fff !important;font-weight:700;text-decoration:none;white-space:nowrap;">Open PDF in new tab</a>
</div>
<iframe src="{$url}#toolbar=1&navpanes=0&view=FitH" title="Panun Kaergar Service Provider Form and Agreement" style="display:block;width:100%;height:78vh;min-height:640px;border:0;background:#eef1f0;"></iframe>
<p style="margin:0;padding:10px 14px;font-size:13px;color:#4b5563;background:#fff;border-top:1px solid #d7e0dc;">If the preview looks small or blank, use <a href="{$url}" target="_blank" rel="noopener noreferrer" style="font-weight:700;">Open PDF in new tab</a> to read all 9 pages.</p>
</div>
HTML;
    }

    private function pdfLink(string $pages): string
    {
        $url = e(self::pdfUrl());
        $pages = e($pages);

        return <<<HTML
<p class="lq-pdf-link" style="margin:0 0 12px;padding:12px 14px;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0;">
<a href="{$url}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:8px 12px;border-radius:8px;background:#166534;color:#fff !important;font-weight:700;text-decoration:none;margin-right:8px;">Open PDF in new tab</a>
<span>Study <strong>{$pages}</strong> for this lesson, then answer the questions.</span>
</p>
HTML;
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionPdfPack(): array
    {
        return [
            'title' => 'The PDF pack',
            'lessons' => [
                [
                    'title' => 'Read the Service Provider Form & Agreement (PDF)',
                    'body' => '<p class="lq-lead">This course is built on the official <strong>Panun Kaergar Service Provider</strong> pack. Read the PDF below in full before you move on. Later lessons walk section by section and test that you understood it.</p>'
                        .$this->pdfEmbed('9 pages: registration form, onboarding questionnaire, and Service Provider Agreement.')
                        .'<div class="lq-block"><div class="lq-kicker"><span class="material-icons">menu_book</span><span>What is inside</span></div><ol><li><strong>Page 1</strong> — Registration Form (company + contact person + age/truth confirmations)</li><li><strong>Pages 2–3</strong> — Onboarding Questionnaire (skills, availability, travel, earnings, expectations)</li><li><strong>Pages 4–9</strong> — Service Provider Agreement (Indian Contract Act, 1872) through acceptance and T&amp;Cs checklist</li></ol><p>Company contacts on the pack: +91-8899881555 · contact@panunkaergar.com · Main Road Jawahar Nagar, Srinagar, J&amp;K – 190008.</p></div>',
                    'questions' => [
                        $this->q(
                            'A provider who signed the pack asks “Am I a Panun Kaergar employee now?” What does the Agreement say?',
                            [
                                'Yes — signing creates employment',
                                'No — they are an independent contractor, not an employee',
                                'Only if they work 7 days a week',
                                'Yes for jobs above ₹1,000',
                            ],
                            1,
                            'The Agreement states the Service Provider is an independent contractor, not an employee.'
                        ),
                        $this->q(
                            'Who manages customer booking, pricing, payment collection and support for Platform jobs?',
                            [
                                'Only the Service Provider',
                                'Panun Kaergar on the customer-facing side; the provider performs the service',
                                'Whoever the customer prefers that day',
                                'Always a third-party bank alone',
                            ],
                            1,
                            'Panun Kaergar manages the customer-facing side; the provider’s duty is to perform the assigned service.'
                        ),
                        $this->q(
                            'Without Panun Kaergar authorisation, may a provider privately collect a different amount from a Platform customer?',
                            [
                                'Yes, if the customer agrees on site',
                                'No — they must not negotiate, discount or privately collect a different amount',
                                'Yes for cash jobs under ₹200',
                                'Yes if they take before-and-after photos',
                            ],
                            1,
                            'Clause 5 forbids negotiating, discounting or privately collecting amounts different from the company-approved amount.'
                        ),
                        $this->q(
                            'Approved service charge is ₹800. What commission does the pack’s table apply?',
                            [
                                '10%',
                                'Flat ₹100',
                                'No commission',
                                '50%',
                            ],
                            1,
                            '₹201–₹1,000 → flat ₹100 commission.'
                        ),
                        $this->q(
                            'Who is responsible if the provider damages customer property through negligence?',
                            [
                                'Panun Kaergar as employer',
                                'The Service Provider',
                                'Always the customer',
                                'Nobody under the Agreement',
                            ],
                            1,
                            'The provider is solely responsible for damage caused by their negligence or improper workmanship.'
                        ),
                        $this->q(
                            'By signing, the provider confirms they will follow Panun Kaergar’s approved pricing, payment process and:',
                            [
                                'Their own private commission rate',
                                'Commission structure',
                                'Only cash tips rules',
                                'Customer marketing lists for sale',
                            ],
                            1,
                            'Acceptance confirms they follow approved pricing, payment process and commission structure.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionRegistration(): array
    {
        return [
            'title' => 'Registration form',
            'lessons' => [
                [
                    'title' => 'What the registration form collects',
                    'body' => $this->pdfLink('page 1 — Registration Form').<<<'HTML'
<p class="lq-lead">Page 1 of the pack is the <strong>Panun Kaergar Service Provider Registration Form</strong>. Staff who onboard providers must know every field and why it exists.</p>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">badge</span><span>Company Information</span></div>
<ul>
<li>Company Name</li>
<li>Phone Number</li>
<li>Address, City, District, Zip code</li>
<li>Email</li>
<li>Industry</li>
<li>Registration Number (Reg. No.)</li>
</ul>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">person</span><span>Contact Person Information</span></div>
<ul>
<li>Full Name</li>
<li>Phone Number and Alternative Number</li>
<li>Email Address</li>
<li>PAN Number</li>
<li>Aadhaar Number</li>
<li>Executive ID (filled by Panun Kaergar staff)</li>
<li>Date and Signature of Provider</li>
</ul>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">gavel</span><span>Opening confirmations</span></div>
<p>Before signing, the provider must confirm:</p>
<ol>
<li>They are at least <strong>18 years old</strong> and legally allowed to work.</li>
<li>They will give <strong>truthful and complete</strong> information about themselves and the services they offer.</li>
</ol>
<p>Panun Kaergar contact printed on the form: +91-8899881555 · contact@panunkaergar.com · Main Road Jawahar Nagar, Srinagar, J&amp;K – 190008.</p>
</div>
HTML,
                    'questions' => [
                        $this->q(
                            'A new electrician is filling the registration form. Which block must include PAN and Aadhaar?',
                            [
                                'Company Information only',
                                'Contact Person Information',
                                'Only the Agreement signature page',
                                'Onboarding questionnaire travel section',
                            ],
                            1,
                            'PAN and Aadhaar sit under Contact Person Information on the registration form.'
                        ),
                        $this->q(
                            'What must the provider confirm about age before they can register?',
                            [
                                'They are at least 21 and have a trade licence',
                                'They are at least 18 years old and legally allowed to work',
                                'They have worked in Kashmir for 5 years',
                                'A parent or partner can sign if they are under 18',
                            ],
                            1,
                            'The form requires confirmation that the person is at least 18 and legally allowed to work.'
                        ),
                        $this->q(
                            'Who fills Executive ID on the registration form?',
                            [
                                'The provider invents any code they like',
                                'The customer who referred them',
                                'Panun Kaergar staff (it is an internal field on the form)',
                                'It is left blank forever',
                            ],
                            2,
                            'Executive ID is a Panun Kaergar onboarding field, not something the provider invents.'
                        ),
                        $this->q(
                            'The provider refuses to give a complete Aadhaar number and says “email is enough.” What is correct?',
                            [
                                'Accept email only — identity fields are optional',
                                'Registration needs truthful and complete information, including identity fields the form asks for',
                                'Skip identity and start sending jobs immediately',
                                'Ask them to invent a temporary Aadhaar',
                            ],
                            1,
                            'They agree to provide truthful and complete information; identity fields on the form are part of onboarding.'
                        ),
                        $this->q(
                            'Which of these belongs under Company Information, not Contact Person?',
                            [
                                'PAN Number',
                                'Aadhaar Number',
                                'Industry and Reg. No.',
                                'Alternative phone number',
                            ],
                            2,
                            'Industry and Reg. No. are company fields. PAN and Aadhaar are contact-person fields.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionOnboarding(): array
    {
        return [
            'title' => 'Onboarding questionnaire',
            'lessons' => [
                [
                    'title' => 'Skills, area, hours and availability',
                    'body' => $this->pdfLink('pages 2–3 — Onboarding Questionnaire').<<<'HTML'
<p class="lq-lead">Pages 2–3 are the <strong>Panun Kaergar Onboarding Questionnaire</strong>. It maps how we should assign work — not legal clauses.</p>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">handyman</span><span>What we ask</span></div>
<ul>
<li><strong>Main expertise</strong> and other related services/work they can also provide</li>
<li><strong>Years of experience:</strong> 0–1 / 2–4 / 5–7 / 10+ years</li>
<li><strong>Days available per week:</strong> 5 / 6 / 7 days</li>
<li><strong>Usual working hours</strong> (FROM–TO, AM/PM)</li>
<li><strong>Emergency on off day:</strong> Yes / No, and if Yes, under what conditions</li>
<li><strong>Main working area</strong> and other areas they can cover</li>
<li><strong>Maximum travel distance:</strong> 1–4 km / 5–10 km / 10–15 km</li>
<li><strong>How they travel:</strong> By foot / Bike or scooter / Public transport / Other</li>
</ul>
</div>
<p>Use these answers when matching bookings. Do not invent coverage the provider did not declare.</p>
HTML,
                    'questions' => [
                        $this->q(
                            'A plumber marks main expertise as “plumbing” and other services as “geyser install.” A customer wants only painting. What should you do?',
                            [
                                'Assign them anyway because they signed the agreement',
                                'Match jobs to declared expertise; do not invent skills they did not list',
                                'Tell the customer the plumber will learn on site',
                                'Hide the other-services field so they take everything',
                            ],
                            1,
                            'The questionnaire exists so we assign work within declared expertise and related services.'
                        ),
                        $this->q(
                            'Which experience bands appear on the form?',
                            [
                                'Only “fresher” or “expert”',
                                '0–1, 2–4, 5–7, and 10+ years',
                                'Exactly 3, 6, or 9 years only',
                                'No experience question is asked',
                            ],
                            1,
                            'The form offers 0–1, 2–4, 5–7, and 10+ years.'
                        ),
                        $this->q(
                            'A provider says they are free 5 days a week but will take emergencies on off days “only if paid double and within 3 km.” Where does that go?',
                            [
                                'Ignore it — emergencies are always mandatory',
                                'Yes on emergency off-day, with those conditions written in the “If Yes, under what conditions?” field',
                                'Only in the legal agreement indemnity clause',
                                'Tell them never to write conditions',
                            ],
                            1,
                            'Emergency willingness is Yes/No, and conditions must be recorded when Yes.'
                        ),
                        $this->q(
                            'Maximum travel distance options on the form are:',
                            [
                                'Anywhere in India',
                                '1–4 km, 5–10 km, or 10–15 km',
                                'Only Srinagar city centre',
                                'No distance question exists',
                            ],
                            1,
                            'The form asks for a maximum of 1–4, 5–10, or 10–15 km.'
                        ),
                    ],
                ],
                [
                    'title' => 'Earnings, job types, problems and expectations',
                    'body' => $this->pdfLink('page 3 — earnings, job types, problems, expectations').<<<'HTML'
<p class="lq-lead">The rest of the questionnaire captures commercial preferences and what the provider wants from Panun Kaergar.</p>
<ul>
<li><strong>Average current earning:</strong> Daily ₹ / Monthly ₹</li>
<li><strong>Minimum job value willing to accept:</strong> ₹200–300 / ₹400–500 / Daily basis / Other</li>
<li><strong>Preferred job types</strong> (select all that apply): Small (up to half a day) · Medium (1–2 days) · Large (3–4 days) · Long-term (1 week or more)</li>
<li><strong>Problems faced today:</strong> Difficulty finding customers · Payment delays · Not getting regular work · Other</li>
<li><strong>Expectations from Panun Kaergar:</strong> More customers · Regular work · Timely payment · Support with complaints · Other</li>
</ul>
<p>These answers guide allocation and coaching. They do <strong>not</strong> override the Agreement’s pricing, commission, or payment rules.</p>
HTML,
                    'questions' => [
                        $this->q(
                            'A provider ticks “minimum job value ₹400–500.” A genuine Platform booking is approved at ₹250. What is true?',
                            [
                                'They may privately renegotiate to ₹500 with the customer',
                                'Questionnaire preference guides matching; they still must follow company-approved price and process for Platform bookings',
                                'They can refuse forever with no notice to Panun Kaergar',
                                'The questionnaire replaces the commission table',
                            ],
                            1,
                            'Preferences help matching. Approved Platform pricing and process still control every booking.'
                        ),
                        $this->q(
                            'Which is a valid preferred job type on the form?',
                            [
                                'Only government contracts',
                                'Long-term projects — 1 week or more',
                                'Jobs with no photos ever',
                                'Cash-only private jobs outside Panun Kaergar',
                            ],
                            1,
                            'Long-term projects (1 week or more) is one of the listed preference options.'
                        ),
                        $this->q(
                            'Why do we ask what problems they face and what they expect from Panun Kaergar?',
                            [
                                'To rewrite the legal Agreement for each person',
                                'To understand their needs (customers, regular work, timely pay, complaint support) so operations can support them',
                                'So they can skip identity verification',
                                'Because commission is optional if they expect “timely payment”',
                            ],
                            1,
                            'Those questions inform how we support partners; they do not rewrite the contract.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionPurposeStatus(): array
    {
        return [
            'title' => 'Purpose & contractor status',
            'lessons' => [
                [
                    'title' => 'Business model and independent contractor',
                    'body' => $this->pdfLink('page 4 — clauses 1–2').<<<'HTML'
<p class="lq-lead">The Service Provider Agreement is under the <strong>Indian Contract Act, 1872</strong>. Clauses 1–2 define the split of work and legal status.</p>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">storefront</span><span>1. Purpose and business model</span></div>
<p>Panun Kaergar runs a <strong>managed</strong> home and related-services platform. Customers book through us. We manage the customer-facing side: acquisition, booking, pricing, communication, coordination, payment collection, support, feedback and complaint handling.</p>
<p>The Service Provider’s job is to <strong>professionally perform and complete</strong> the assigned service to the booking details and Panun Kaergar service standards.</p>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">handshake</span><span>2. Independent contractor status</span></div>
<p>The Service Provider is an <strong>independent contractor</strong> — not an employee, agent, partner, joint-venture participant or legal representative of Panun Kaergar — and must not claim to be one.</p>
<p>They are responsible for their own taxes, licences, qualifications, tools, equipment and other legal requirements. Nothing in the Agreement creates employment, partnership, agency or joint venture.</p>
</div>
HTML,
                    'questions' => [
                        $this->q(
                            'Who manages customer acquisition, booking, pricing and payment collection for Platform bookings?',
                            [
                                'Only the Service Provider',
                                'Panun Kaergar on the customer-facing side',
                                'Whichever party the customer prefers that day',
                                'The landlord of the customer’s home',
                            ],
                            1,
                            'Panun Kaergar manages the customer-facing side; the provider performs the service.'
                        ),
                        $this->q(
                            'A provider tells a customer “I am a Panun Kaergar employee, so my overtime is company policy.” Is that correct?',
                            [
                                'Yes — signing makes them an employee',
                                'No — they are an independent contractor and must not represent themselves as an employee',
                                'Only if they work 7 days a week',
                                'Yes for jobs above ₹1,000',
                            ],
                            1,
                            'Clause 2: independent contractor, not employee, and they must not represent themselves as one.'
                        ),
                        $this->q(
                            'Who is responsible for the provider’s taxes, licences and tools?',
                            [
                                'Panun Kaergar always',
                                'The Service Provider',
                                'The customer',
                                'Shared 50/50 by default',
                            ],
                            1,
                            'The provider is responsible for their own taxes, licences, tools and legal requirements.'
                        ),
                        $this->q(
                            'Under which law is this Agreement framed on the document?',
                            [
                                'Only local municipal bye-laws',
                                'Indian Contract Act, 1872',
                                'US employment law',
                                'No law is named',
                            ],
                            1,
                            'The document states it is under the Indian Contract Act, 1872.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionRoles(): array
    {
        return [
            'title' => 'Roles & responsibilities',
            'lessons' => [
                [
                    'title' => 'What Panun Kaergar does vs what the provider must do',
                    'body' => $this->pdfLink('pages 4–5 — clauses 3–4').<<<'HTML'
<p class="lq-lead">Clauses 3–4 list operational duties. Staff must brief providers on both lists.</p>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">support_agent</span><span>3. Panun Kaergar’s responsibilities</span></div>
<ul>
<li>Receiving and confirming bookings</li>
<li>Determining and communicating customer-facing prices</li>
<li>Assigning suitable Service Providers</li>
<li>Customer communication and scheduling</li>
<li>Payment collection and settlement</li>
<li>Customer support and complaint coordination</li>
<li>Service feedback and quality monitoring</li>
<li>Coordination of payment or service-related disputes</li>
</ul>
<p>Panun Kaergar may modify operational processes, pricing structures, service procedures and Platform features as the business develops.</p>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">engineering</span><span>4. Service Provider’s responsibilities</span></div>
<ul>
<li>Accept and complete assigned jobs professionally and within agreed service time</li>
<li>Have the required skills, experience, tools and equipment</li>
<li>Follow reasonable Panun Kaergar service and safety standards</li>
<li>Maintain professional behaviour with customers</li>
<li>Keep Panun Kaergar informed of delays, inability to complete, additional work, damage or other material issues</li>
<li>Provide accurate completion information and evidence when required</li>
<li>Take reasonable care of the customer’s property and premises</li>
</ul>
</div>
HTML,
                    'questions' => [
                        $this->q(
                            'Who sets and communicates the customer-facing price for a Platform booking?',
                            [
                                'The provider on the spot',
                                'Panun Kaergar',
                                'Whoever arrives first at the house',
                                'Always the cheapest competitor online',
                            ],
                            1,
                            'Clause 3: Panun Kaergar determines and communicates customer-facing prices.'
                        ),
                        $this->q(
                            'Mid-job the provider sees extra work and stays silent until invoice time. What did they break?',
                            [
                                'Nothing — silence is allowed',
                                'Duty to keep Panun Kaergar informed of additional work requirements and material issues',
                                'Only the branding clause',
                                'Force majeure',
                            ],
                            1,
                            'Clause 4 requires informing Panun Kaergar of additional work and material issues.'
                        ),
                        $this->q(
                            'Which task is Panun Kaergar’s, not the provider’s?',
                            [
                                'Performing the plumbing repair on site',
                                'Payment collection and settlement for Platform bookings',
                                'Bringing their own tools',
                                'Taking care of the customer’s furniture while working',
                            ],
                            1,
                            'Payment collection and settlement are listed under Panun Kaergar’s responsibilities.'
                        ),
                        $this->q(
                            'Can Panun Kaergar change operational processes and Platform features over time?',
                            [
                                'Never, once the PDF is printed',
                                'Yes — the Agreement says processes, pricing structures and Platform features may be modified as the business develops',
                                'Only if every provider re-signs in ink the same day',
                                'Only for jobs under ₹200',
                            ],
                            1,
                            'Clause 3 allows Panun Kaergar to modify processes, pricing structures, procedures and Platform features.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionPricingPayment(): array
    {
        return [
            'title' => 'Pricing, payment & non-circumvention',
            'lessons' => [
                [
                    'title' => 'Customer interaction and payment discipline',
                    'body' => $this->pdfLink('page 5 — clause 5').<<<'HTML'
<p class="lq-lead">Clause 5 is the heart of commercial discipline. Anyone who fills or explains the form must know this cold.</p>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">forum</span><span>5.1 Customer interaction</span></div>
<p>Panun Kaergar manages the primary customer relationship. The provider may communicate with the customer only as reasonably needed to perform the assigned service, and must stay professional.</p>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">payments</span><span>5.2 Pricing rules — without prior authorisation the provider must NOT</span></div>
<ul>
<li>Negotiate, discount or collect an amount different from the company-approved amount</li>
<li>Charge for additional work, changes or materials without first reporting to Panun Kaergar for approval</li>
<li>Privately collect or arrange payment for a Platform booking</li>
</ul>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">verified</span><span>5.3 Payment guarantee</span></div>
<p>Panun Kaergar <strong>guarantees</strong> the company-approved Service Provider amount for every genuine booking through the official process, subject to successful completion, verification and adjustments under the Agreement.</p>
<p>Any amount the provider independently agrees with a customer that differs from the approved amount is an <strong>Unauthorized Charge</strong>. It sits outside the guarantee and is the provider’s own responsibility. If the customer fails to pay the approved amount, Panun Kaergar will make reasonable recovery efforts — but bears no responsibility for Unauthorized Charges.</p>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">block</span><span>5.4 No bypass</span></div>
<p>Do not use a Platform customer to bypass Panun Kaergar, encourage cancellation to deal privately, or avoid commission/process. Breach may bring penalties (where legally permissible), suspension, termination and other lawful remedies.</p>
</div>
HTML,
                    'questions' => [
                        $this->q(
                            'Customer asks the provider for a “cash discount if you don’t tell the office.” Correct action?',
                            [
                                'Accept cash at a lower price',
                                'Refuse — no negotiating, discounting or privately collecting a different amount without Panun Kaergar authorisation',
                                'Accept if the discount is under 10%',
                                'Accept and add it as Unauthorized Charge on the app later',
                            ],
                            1,
                            'Clause 5.2 forbids negotiating/discounting/collecting a different amount without prior authorisation.'
                        ),
                        $this->q(
                            'What is an “Unauthorized Charge”?',
                            [
                                'The company-approved payout',
                                'Any amount the provider independently agrees with the customer that differs from the approved amount',
                                'Panun Kaergar’s commission',
                                'GST on tools the provider owns',
                            ],
                            1,
                            'Unauthorized Charge = independently agreed amount that differs from the approved amount.'
                        ),
                        $this->q(
                            'Does Panun Kaergar’s payment guarantee cover Unauthorized Charges?',
                            [
                                'Yes, always',
                                'No — they fall entirely outside the guarantee and are the provider’s responsibility',
                                'Only on Sundays',
                                'Only if before-and-after photos exist',
                            ],
                            1,
                            'Clause 5.3: Unauthorized Charges are outside the guarantee.'
                        ),
                        $this->q(
                            'Extra materials are needed mid-job. What must happen before charging more?',
                            [
                                'Provider sets a new price with the customer privately',
                                'Report the request to Panun Kaergar for approval first',
                                'Add 20% automatically',
                                'Nothing — materials are always free',
                            ],
                            1,
                            'Additional work/materials need Panun Kaergar approval before charging.'
                        ),
                        $this->q(
                            'A provider asks a Platform customer to cancel and rebook privately to “save commission.” This is:',
                            [
                                'Allowed growth hacking',
                                'A breach of the non-circumvention / bypass rules and can lead to suspension or termination',
                                'Required by clause 18 Force Majeure',
                                'Only wrong if the job is under ₹200',
                            ],
                            1,
                            'Clause 5.4 forbids bypassing the Platform or encouraging cancel-to-deal-privately.'
                        ),
                        $this->q(
                            'Customer delays paying the approved amount. What does the Agreement say?',
                            [
                                'Panun Kaergar has zero duty to help',
                                'Panun Kaergar will make reasonable efforts to help recover the approved amount',
                                'The provider must sue the customer without telling Panun Kaergar',
                                'The provider may invent a higher Unauthorized Charge instead',
                            ],
                            1,
                            'Reasonable recovery efforts apply to the approved amount; not to Unauthorized Charges.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionCommission(): array
    {
        return [
            'title' => 'Commission structure',
            'lessons' => [
                [
                    'title' => 'How commission is calculated',
                    'body' => $this->pdfLink('page 6 — clause 7 and commission table').<<<'HTML'
<p class="lq-lead">Clause 7: the customer pays the approved service charge to <strong>Panun Kaergar</strong>. We deduct commission and pay the remaining eligible amount to the provider. Unauthorized extra amounts are not in the payout and are not covered by the guarantee.</p>
<table>
<thead><tr><th>Approved service charge</th><th>Panun Kaergar commission</th></tr></thead>
<tbody>
<tr><td>₹0</td><td>No Commission</td></tr>
<tr><td>Up to ₹200</td><td>10%</td></tr>
<tr><td>₹201 – ₹1,000</td><td>Flat ₹100</td></tr>
<tr><td>Above ₹1,000</td><td>10% of total approved service charge</td></tr>
</tbody>
</table>
<p>Memorise the bands. Staff explaining the form must calculate examples correctly.</p>
HTML,
                    'questions' => [
                        $this->q(
                            'Approved service charge is ₹150. What commission applies?',
                            [
                                'Flat ₹100',
                                '10%',
                                'No commission',
                                '10% of something above ₹1,000',
                            ],
                            1,
                            'Up to ₹200 → 10% commission.'
                        ),
                        $this->q(
                            'Approved service charge is ₹800. What commission applies?',
                            [
                                '10%',
                                'Flat ₹100',
                                'No commission',
                                '20%',
                            ],
                            1,
                            '₹201–₹1,000 → flat ₹100.'
                        ),
                        $this->q(
                            'Approved service charge is ₹2,000. What commission applies?',
                            [
                                'Flat ₹100 only',
                                '10% of the total approved service charge',
                                'No commission',
                                '50%',
                            ],
                            1,
                            'Above ₹1,000 → 10% of total approved service charge.'
                        ),
                        $this->q(
                            'Approved charge is ₹0 (goodwill / covered case as written). Commission?',
                            [
                                'Flat ₹100',
                                'No Commission',
                                '10%',
                                'Provider pays Panun Kaergar ₹100',
                            ],
                            1,
                            '₹0 → No Commission on the table.'
                        ),
                        $this->q(
                            'Who does the customer pay the approved service charge to?',
                            [
                                'Directly and only to the provider’s personal UPI for Platform bookings',
                                'Directly to Panun Kaergar',
                                'Half to each by default',
                                'To whoever asks first on site',
                            ],
                            1,
                            'Clause 7: customer pays the approved service charge to Panun Kaergar.'
                        ),
                        $this->q(
                            'Provider collected ₹300 extra in cash “for tea.” Is that part of their Panun Kaergar payout?',
                            [
                                'Yes, add it to settlement',
                                'No — unauthorized amounts are not included in payout or the payment guarantee',
                                'Yes if they took photos',
                                'Yes if under ₹500',
                            ],
                            1,
                            'Unauthorized amounts are excluded from payout and guarantee.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionQualityEvidence(): array
    {
        return [
            'title' => 'Quality, damage & evidence',
            'lessons' => [
                [
                    'title' => 'Conduct, property damage and proof of work',
                    'body' => $this->pdfLink('pages 5–6 — quality, damage & work evidence').<<<'HTML'
<p class="lq-lead">Clauses on service quality, conduct, property damage and work evidence (including the mirrored quality section in the pack) require professional, safe work and proof.</p>
<ul>
<li>No fraud, misconduct, harassment or unlawful activity</li>
<li>Provider is <strong>solely responsible</strong> for damage to customer property or injury caused by their negligence, improper workmanship, misconduct or unauthorized actions</li>
<li>Panun Kaergar may assist in resolving such matters but is <strong>not responsible</strong> for damage caused by the provider</li>
<li><strong>Before-and-after photos</strong> (or other suitable proof) wherever applicable — used to verify completion and resolve complaints</li>
<li>Any damage, accident or serious incident must be reported to Panun Kaergar <strong>immediately</strong></li>
</ul>
HTML,
                    'questions' => [
                        $this->q(
                            'Provider cracks a marble slab through careless work. Who is responsible under the Agreement?',
                            [
                                'Panun Kaergar as employer',
                                'The Service Provider for damage from their negligence or improper workmanship',
                                'Always the customer',
                                'Nobody',
                            ],
                            1,
                            'The provider is solely responsible for damage caused by their negligence or improper workmanship.'
                        ),
                        $this->q(
                            'When should before-and-after photos be taken?',
                            [
                                'Never — trust is enough',
                                'Wherever applicable, as suitable proof of work and property condition',
                                'Only for jobs above ₹5,000',
                                'Only if the customer demands a refund',
                            ],
                            1,
                            'Before-and-after (or other suitable proof) wherever applicable.'
                        ),
                        $this->q(
                            'A ladder slip injures the customer’s relative. What must the provider do first under the Agreement?',
                            [
                                'Hide it and finish quietly',
                                'Report the serious incident to Panun Kaergar immediately',
                                'Post on social media first',
                                'Wait until monthly settlement',
                            ],
                            1,
                            'Damage, accident or serious incident must be reported immediately.'
                        ),
                        $this->q(
                            'Does Panun Kaergar accept legal responsibility for damage the provider caused?',
                            [
                                'Yes, always',
                                'No — it may assist resolution but is not responsible for damage caused by the provider',
                                'Yes if commission was taken',
                                'Yes for Unauthorized Charges only',
                            ],
                            1,
                            'Panun Kaergar may assist but is not responsible for provider-caused damage.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionOpsRules(): array
    {
        return [
            'title' => 'Cancellations, complaints, payout & data',
            'lessons' => [
                [
                    'title' => 'Acceptance, no-shows, complaints and settlement',
                    'body' => $this->pdfLink('pages 6–7 — clauses 8, 10, 11').<<<'HTML'
<p class="lq-lead">Clauses 8, 10 and 11 cover reliability after accept, investigations, and money settlement.</p>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">event_busy</span><span>8. Acceptance, cancellation &amp; no-show</span></div>
<p>Once accepted, complete the booking. If a genuine reason prevents completion, inform Panun Kaergar immediately. Repeated cancellations, refusal to attend, no-shows, unreasonable delays or failure to communicate may lead to warnings, penalties, reduced allocation, suspension or termination (subject to law and policy).</p>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">fact_check</span><span>10. Complaints, records &amp; verification</span></div>
<p>Panun Kaergar may investigate and require evidence (notes, photos, customer confirmation, material details, invoices). Cooperate; do not falsify records. Improper work may require corrective rework. Repeated/serious complaints or bad records may reduce jobs, suspend or terminate.</p>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">account_balance_wallet</span><span>11. Partner payout &amp; settlement</span></div>
<p>Payout follows the settlement cycle for completed, verified bookings. Legitimate adjustments may apply for refunds, cancellations, Unauthorized Charges, confirmed damages, penalties, duplicate payments or other amounts due under the Agreement.</p>
</div>
HTML,
                    'questions' => [
                        $this->q(
                            'Provider accepted a booking then went silent and never arrived. Possible consequences include:',
                            [
                                'Nothing — accept means optional',
                                'Warnings, penalties, reduced job allocation, suspension or termination',
                                'Automatic bonus',
                                'Only a polite email forever',
                            ],
                            1,
                            'Clause 8 lists warnings through termination for no-shows and related failures.'
                        ),
                        $this->q(
                            'During a complaint investigation the provider edits photos to hide damage. That is:',
                            [
                                'Smart documentation',
                                'Forbidden — they must not falsify, manipulate or misrepresent records',
                                'Required by clause 12',
                                'Allowed if commission already paid',
                            ],
                            1,
                            'Clause 10 forbids falsifying or misrepresenting records.'
                        ),
                        $this->q(
                            'Can Panun Kaergar adjust a payout for confirmed damages or Unauthorized Charges?',
                            [
                                'Never',
                                'Yes — legitimate adjustments are allowed under the Agreement',
                                'Only if the provider agrees in a WhatsApp sticker',
                                'Only on force majeure days',
                            ],
                            1,
                            'Clause 11 allows legitimate adjustments including damages and Unauthorized Charges.'
                        ),
                    ],
                ],
                [
                    'title' => 'Tools, verification, confidentiality and branding',
                    'body' => $this->pdfLink('page 7 — clauses 12–15').<<<'HTML'
<p class="lq-lead">Clauses 12–15 cover equipment, truthfulness in onboarding, customer data, and how partners may speak for the brand.</p>
<ul>
<li><strong>12. Tools:</strong> Maintain safe, appropriate tools/equipment; materials follow the process Panun Kaergar communicates or approves.</li>
<li><strong>13. Verification:</strong> Identity, contacts, category, experience, qualifications and references may be checked. False or fraudulent information may mean immediate suspension or termination.</li>
<li><strong>14. Confidentiality:</strong> Customer data only for completing the assigned service and related ops. No misuse, sale, disclosure or independent marketing. Platform pricing and internal processes stay confidential — including after termination as required by law/nature of the information.</li>
<li><strong>15. Branding:</strong> May identify as a Panun Kaergar Service Partner on an assigned booking, but must not make unauthorized commitments, warranties, refunds, discounts or other representations on behalf of Panun Kaergar.</li>
</ul>
HTML,
                    'questions' => [
                        $this->q(
                            'Provider sells a Platform customer’s phone number to a marketing agency. This breaches:',
                            [
                                'Nothing material',
                                'Confidentiality &amp; customer data rules',
                                'Only the commission table',
                                'Force majeure',
                            ],
                            1,
                            'Clause 14 forbids misuse, sale or disclosure of customer information.'
                        ),
                        $this->q(
                            'On site the provider promises the customer a full refund “from Panun Kaergar” without approval. Allowed?',
                            [
                                'Yes — partners speak for the company freely',
                                'No — no unauthorized commitments, warranties, refunds or discounts on behalf of Panun Kaergar',
                                'Yes if they are 10+ years experienced',
                                'Yes for small jobs only',
                            ],
                            1,
                            'Clause 15 forbids unauthorized commitments/refunds/discounts on behalf of Panun Kaergar.'
                        ),
                        $this->q(
                            'Fake experience was written on the registration form and later discovered. Possible result?',
                            [
                                'Ignore it',
                                'Immediate suspension or termination for false or fraudulent onboarding information',
                                'Automatic promotion',
                                'Only a commission holiday',
                            ],
                            1,
                            'Clause 13: false/fraudulent information may result in immediate suspension or termination.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionLegalClose(): array
    {
        return [
            'title' => 'Suspension, liability & closing terms',
            'lessons' => [
                [
                    'title' => 'Suspension, indemnity, disputes and acceptance',
                    'body' => $this->pdfLink('pages 8–9 — clauses 16–21 and T&Cs checklist').<<<'HTML'
<p class="lq-lead">Clauses 16–21 close the Agreement. Staff must know grounds for stopping access and what the signature means.</p>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">pause_circle</span><span>16. Suspension &amp; termination</span></div>
<p>Panun Kaergar may suspend or terminate access — immediately in serious/repeated cases — for reasons including serious complaints, suspected fraud, Unauthorized Charges, commission evasion, customer circumvention, property damage allegations, repeated cancellations/no-shows, serious misconduct, confidentiality violations, safety concerns, false onboarding information, or other material breach. Suspension may continue while reviewed. Either party may otherwise terminate on reasonable notice, subject to handover of accepted jobs. Some clauses survive termination (confidentiality, non-circumvention, liability, indemnity, payment obligations, etc.).</p>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">policy</span><span>17–19. Liability, force majeure, disputes</span></div>
<ul>
<li><strong>17:</strong> Provider is responsible for loss/claims from their negligence, misconduct, fraud, theft, unauthorized actions, damage, injury, law violations or serious breach; they indemnify Panun Kaergar to the extent permitted by law. Panun Kaergar manages booking/process but does not perform the actual service.</li>
<li><strong>18 Force majeure:</strong> Neither party is liable for failure/delay from events beyond reasonable control (disasters, severe weather, government restrictions, war, epidemics, widespread network failures, etc.).</li>
<li><strong>19:</strong> Good-faith internal resolution first; unresolved disputes may go to arbitration under the Arbitration and Conciliation Act, 1996, seat/venue in <strong>Jammu &amp; Kashmir, India</strong>, unless otherwise agreed or required by law. Governing law: India. Material commercial changes will be communicated through an official channel.</li>
</ul>
</div>
<div class="lq-block">
<div class="lq-kicker"><span class="material-icons">draw</span><span>20–21. Acceptance &amp; T&amp;Cs checklist</span></div>
<p>By signing, the provider confirms they read and understood the Agreement; independent status; Panun Kaergar’s managed role vs their duty to perform; approved pricing/payment/commission; guarantee only for approved amounts; responsibility for their own damage/negligence; and compliance with policies.</p>
<p>Final checklist commitments include: no false commitments on availability/services/pricing; immediate updates on issues/delays; before-and-after media when required; get approval before scope/price changes; follow rules (violation may suspend/terminate); keep availability updated; complete accepted jobs or inform immediately; professional behaviour with customers and team.</p>
</div>
HTML,
                    'questions' => [
                        $this->q(
                            'Which is a listed ground for immediate suspension/termination in serious cases?',
                            [
                                'Asking a clarifying question about the booking',
                                'Commission evasion or customer circumvention',
                                'Taking before-and-after photos',
                                'Reporting a delay immediately',
                            ],
                            1,
                            'Clause 16 lists commission evasion and customer circumvention among serious grounds.'
                        ),
                        $this->q(
                            'Where is the seat/venue of arbitration under the Agreement (unless otherwise agreed)?',
                            [
                                'Delhi only by default in the text',
                                'Jammu &amp; Kashmir, India',
                                'Singapore',
                                'Wherever the customer lives abroad',
                            ],
                            1,
                            'Clause 19 sets arbitration seat/venue in Jammu &amp; Kashmir, India.'
                        ),
                        $this->q(
                            'A flood blocks all roads (force majeure). Is either party automatically liable for the delay?',
                            [
                                'Yes, always the provider',
                                'No — neither party is responsible for failure/delay from circumstances beyond reasonable control as listed',
                                'Yes, always Panun Kaergar',
                                'Only if commission was unpaid',
                            ],
                            1,
                            'Clause 18 Force Majeure excuses failure/delay beyond reasonable control.'
                        ),
                        $this->q(
                            'Before changing job scope or pricing during service, the T&amp;Cs checklist requires the provider to:',
                            [
                                'Decide alone with the customer',
                                'Inform Panun Kaergar before proceeding with the change',
                                'Post a story on Instagram',
                                'Wait until after collecting cash',
                            ],
                            1,
                            'Final T&amp;Cs: inform Panun Kaergar before proceeding with scope/pricing changes.'
                        ),
                        $this->q(
                            'Signing the Agreement confirms the provider is:',
                            [
                                'A Panun Kaergar employee entitled to PF by default under this PDF',
                                'An independent service provider, not an employee',
                                'A joint-venture partner owning the brand',
                                'The customer’s legal agent',
                            ],
                            1,
                            'Clause 20 acceptance: independent service provider, not an employee.'
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, lessons: array<int, array<string, mixed>>}
     */
    private function sectionCertification(): array
    {
        return [
            'title' => 'Certification exam',
            'lessons' => [
                [
                    'title' => 'Form & agreement scenarios — part 1',
                    'body' => $this->pdfLink('full pack — use as reference while you answer').'<p class="lq-lead">These scenarios mix the registration form, questionnaire and Agreement. Every answer must be correct. If you pass, you can explain the pack to a provider without guessing.</p>',
                    'questions' => [
                        $this->q(
                            'You are onboarding a carpenter. Which documents/fields are you collecting on page 1 Contact Person Information?',
                            [
                                'Only Instagram handle',
                                'Full name, phones, email, PAN, Aadhaar, plus date and signature',
                                'Only company Reg. No.',
                                'Only travel distance',
                            ],
                            1,
                            'Contact Person Information includes identity and contact fields plus signature/date.'
                        ),
                        $this->q(
                            'Provider wants jobs across 40 km. The form’s maximum travel options go up to:',
                            [
                                '10–15 km as the top band on the form',
                                'Unlimited',
                                'Only 1–4 km forever',
                                'No travel question',
                            ],
                            0,
                            'The printed bands top out at 10–15 km; do not invent wider coverage on the form itself.'
                        ),
                        $this->q(
                            'Approved job ₹950. Commission?',
                            [
                                '10%',
                                'Flat ₹100',
                                'No commission',
                                '₹950',
                            ],
                            1,
                            '₹201–₹1,000 → flat ₹100.'
                        ),
                        $this->q(
                            'Approved job ₹1,500. Provider payout before other adjustments equals approved charge minus:',
                            [
                                'Flat ₹100',
                                '10% of ₹1,500',
                                'Nothing',
                                '50%',
                            ],
                            1,
                            'Above ₹1,000 → 10% of total approved service charge.'
                        ),
                        $this->q(
                            'Customer from a Platform booking asks to pay the provider on personal UPI for the approved amount “to be faster.” Correct response?',
                            [
                                'Accept personal UPI',
                                'No private collection for Platform bookings — payment follows Panun Kaergar’s process',
                                'Accept if they send a screenshot later',
                                'Accept under ₹200 only',
                            ],
                            1,
                            'Private collection for Platform bookings is forbidden without authorisation; customer pays via Panun Kaergar process.'
                        ),
                        $this->q(
                            'Provider marks Yes to emergency off-day work but leaves conditions blank. What should staff do?',
                            [
                                'Submit anyway — conditions never matter',
                                'Get the conditions written when Yes is selected',
                                'Change Yes to No without asking',
                                'Delete the questionnaire page',
                            ],
                            1,
                            'The form asks for conditions when emergency off-day is Yes.'
                        ),
                    ],
                ],
                [
                    'title' => 'Form & agreement scenarios — part 2',
                    'body' => $this->pdfLink('full pack — use as reference while you answer').'<p class="lq-lead">Final scenarios. Focus on guarantee limits, evidence, status, and closing commitments.</p>',
                    'questions' => [
                        $this->q(
                            'Provider and customer privately add ₹700 “tip for skipping the app.” Customer later refuses to pay that ₹700. Who carries that risk?',
                            [
                                'Panun Kaergar under the payment guarantee',
                                'The Service Provider — Unauthorized Charges are outside the guarantee',
                                'Always the arbitrator in Singapore',
                                'The Executive ID holder personally',
                            ],
                            1,
                            'Unauthorized Charges are the provider’s responsibility and outside the guarantee.'
                        ),
                        $this->q(
                            'After a booking, provider texts the customer monthly offers using the number from the job. Allowed?',
                            [
                                'Yes — forever marketing rights',
                                'No — customer data is only for completing the assigned service / related ops, not independent marketing',
                                'Yes if they took photos',
                                'Yes after termination only',
                            ],
                            1,
                            'Clause 14 forbids using customer info for independent marketing.'
                        ),
                        $this->q(
                            'Which statement is true about employment?',
                            [
                                'Providers are employees once they sign',
                                'Providers are independent contractors; the Agreement creates no employment relationship',
                                'Providers are partners owning 50% of Panun Kaergar',
                                'Providers are unpaid interns',
                            ],
                            1,
                            'Clauses 2 and 20: independent contractor / not an employee.'
                        ),
                        $this->q(
                            'Job finished but no photos were taken though the work was visible and applicable. Risk?',
                            [
                                'None',
                                'Harder verification and complaint handling — before-and-after (or suitable proof) is required wherever applicable',
                                'Automatic double payout',
                                'Force majeure protection',
                            ],
                            1,
                            'Evidence rules exist to verify completion and resolve complaints.'
                        ),
                        $this->q(
                            'Material commercial terms (commission/payout) change as the business develops. How are providers informed per the Agreement?',
                            [
                                'Never — silent changes only',
                                'Material changes to commercial terms will be communicated through an official channel',
                                'Only via random customer reviews',
                                'Only if they ask on an off day',
                            ],
                            1,
                            'Changes to Terms: material commercial changes go through an official channel.'
                        ),
                        $this->q(
                            'A provider signed the closing T&amp;Cs. Which commitment did they make about accepted bookings?',
                            [
                                'They may ignore accepted bookings if busy',
                                'Once they accept, they complete as agreed or immediately inform Panun Kaergar if unable',
                                'They may send any substitute with no notice',
                                'They may cancel silently after collecting an Unauthorized Charge',
                            ],
                            1,
                            'Closing checklist: complete accepted jobs or inform immediately if unable.'
                        ),
                    ],
                ],
            ],
        ];
    }
}

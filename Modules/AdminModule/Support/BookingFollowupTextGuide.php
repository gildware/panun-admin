<?php

namespace Modules\AdminModule\Support;

class BookingFollowupTextGuide
{
    /**
     * @return array<int, array{title: string, intro?: string, steps: array<int, array<string, mixed>}>}
     */
    public static function sections(): array
    {
        return [
            [
                'title' => '0. Terms (deck guide)',
                'steps' => [
                    ['title' => 'Key terms', 'body' => 'Accepted, Ongoing, Due Balance, Touchpoint, Re Assign, Special scenario, Bill breakdown — see slide 1 in training.'],
                ],
            ],
            [
                'title' => '1. Booking prerequisites',
                'intro' => 'Verify before Create Booking.',
                'steps' => [
                    ['title' => 'Lead qualified', 'body' => 'Customer type, full details on call, provider plan, ₹100 if required.'],
                    ['title' => 'Customer & address', 'body' => 'Phone, zone, area, complete address in panel.'],
                    ['title' => 'Service & provider', 'body' => 'Correct category/service, schedule, assigned provider for admin create.'],
                ],
            ],
            [
                'title' => '2. Your job in bookings',
                'steps' => [
                    ['title' => 'Seven habit cards', 'body' => 'Click cards for images + examples — own until closed, follow-ups, touchpoints, status, bill breakdown, payments, WhatsApp.'],
                ],
            ],
            [
                'title' => '3. How to create booking',
                'steps' => [
                    ['title' => 'From lead', 'body' => 'Create Booking for this Lead → Preview → Store.'],
                    ['title' => 'From web / app / WhatsApp', 'body' => 'Open queue → Create Booking on linked lead or WhatsApp draft.'],
                    ['title' => 'Direct', 'body' => 'Add New Booking → fill all sections → Store.'],
                    ['title' => 'After save', 'body' => 'WhatsApp both parties → Follow-ups tab → first follow-up → assignee.'],
                ],
            ],
            [
                'title' => '4. Follow up bookings',
                'steps' => [
                    ['title' => 'When to call', 'body' => 'At booking (always) · day before (only if service 3+ days away) · service day (always).'],
                    ['title' => 'Same-day / 1–2 days out', 'body' => 'Booking confirm + service day only — skip day-before call.'],
                    ['title' => '3+ days out', 'body' => 'Day before: provider first. Service day: morning + 1hr check → Ongoing.'],
                    ['title' => 'After service', 'body' => 'Provider bill breakdown → panel → customer confirm → feedback both sides → Completed.'],
                    ['title' => 'Provider unavailable', 'body' => 'Re Assign (before Ongoing), On hold + new date, or Cancel + No-show feedback.'],
                    ['title' => 'Customer not ready', 'body' => 'Confirm before provider leaves; Cancel After Visit if provider visited and customer absent.'],
                ],
            ],
            [
                'title' => '5. Booking statuses',
                'intro' => 'Set on booking details — list tabs are filters only.',
                'steps' => [
                    ['title' => 'Pending → Accepted → Ongoing → Completed', 'body' => 'Normal path.'],
                    ['title' => 'On hold', 'body' => 'Pause with reason + new date.'],
                    ['title' => 'Canceled', 'body' => 'Before visit or via Save and cancel on special scenario.'],
                    ['title' => 'List tabs', 'body' => 'Main: All, Pending, Accepted, Cancelled, Ongoing, Completed. View more: Reopened, Disputed, Hold after visit, Loss Making, etc.'],
                ],
            ],
            [
                'title' => '6. Special scenarios',
                'steps' => [
                    ['title' => 'Cancel After Visit', 'body' => 'Provider came, job impossible → visit charges → Save and cancel.'],
                    ['title' => 'Complete visit only', 'body' => 'Minimal work → visit fee → Save and complete.'],
                    ['title' => 'Loss making', 'body' => 'Underpaid → loss split → Save and complete → track recovery.'],
                ],
            ],
            [
                'title' => '7. Disputed bookings',
                'steps' => [
                    ['title' => 'Configure reasons', 'body' => 'Booking Configuration → Dispute reasons.'],
                    ['title' => 'Dispute and close', 'body' => 'Reopened bookings — refund split, remarks, apply.'],
                ],
            ],
            [
                'title' => '8. Managing payments',
                'steps' => [
                    ['title' => 'Advance at create', 'body' => 'Optional on create form.'],
                    ['title' => 'Add payment', 'body' => 'During Ongoing — amount, method, who received.'],
                    ['title' => 'Before close', 'body' => 'Itemized bill in panel + customer confirmed + Due Balance = 0.'],
                    ['title' => 'Provider share', 'body' => 'Settlement lines show company vs provider — flows to provider ledger.'],
                ],
            ],
            [
                'title' => '9. Feedback',
                'steps' => [
                    ['title' => 'Booking Review', 'body' => 'Approve pending public reviews.'],
                    ['title' => 'Performance feedback', 'body' => 'Provider No-show tag; customer issues.'],
                    ['title' => 'Calls', 'body' => 'Provider first, then customer.'],
                ],
            ],
            [
                'title' => '10. Payment checklist',
                'steps' => [
                    [
                        'title' => 'Before Completed or Save',
                        'items' => [
                            'Due Balance zero',
                            'All partial payments recorded',
                            'Settlement lines correct (if special scenario)',
                            'Provider share verified',
                            'Correct close path and tag',
                            'Reviews/feedback handled',
                        ],
                    ],
                ],
            ],
        ];
    }
}

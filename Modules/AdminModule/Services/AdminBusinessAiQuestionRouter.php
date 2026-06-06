<?php

namespace Modules\AdminModule\Services;

/**
 * Maps admin questions to the best read-only business AI tools.
 */
class AdminBusinessAiQuestionRouter
{
    /**
     * @return array<string, mixed>
     */
    public function extractQueryIdentifiers(string $userMessage): array
    {
        $ids = [];

        if (preg_match('/(?:\+91[\s-]?)?[6-9]\d{9}\b/', $userMessage, $phoneMatch)) {
            $digits = preg_replace('/\D/', '', $phoneMatch[0]) ?? '';
            $ids['phone'] = strlen($digits) > 10 ? substr($digits, -10) : $digits;
        }

        if (preg_match('/\b(PK-\d+)\b/i', $userMessage, $readableMatch)) {
            $ids['readable_id'] = strtoupper($readableMatch[1]);
        }

        if (preg_match('/\blead\s*(?:id|#|:)?\s*(\d+)\b/i', $userMessage, $leadMatch)) {
            $ids['lead_id'] = (int) $leadMatch[1];
        }

        if (preg_match('/\bbooking\s*(?:id|#|:)?\s*([a-f0-9-]{36}|\d+)\b/i', $userMessage, $bookingMatch)) {
            $ids['booking_id'] = $bookingMatch[1];
        }

        return $ids;
    }

    /**
     * @return list<array{name: string, args: array<string, mixed>}>
     */
    public function inferToolsForQuestion(string $userMessage, int $maxTools = 5): array
    {
        $text = strtolower(trim($userMessage));
        if ($text === '') {
            return [];
        }

        $tools = [];
        $ids = $this->extractQueryIdentifiers($userMessage);

        if ($ids !== []) {
            if (! empty($ids['lead_id']) && preg_match('/\b(detail|history|timeline|activity|show|tell|about)\b/i', $userMessage)) {
                return [['name' => 'get_lead_details', 'args' => ['lead_id' => $ids['lead_id']]]];
            }
            if (! empty($ids['readable_id']) || ! empty($ids['booking_id'])) {
                $bookingArgs = ! empty($ids['readable_id'])
                    ? ['readable_id' => $ids['readable_id']]
                    : ['booking_id' => $ids['booking_id']];
                if (preg_match('/\b(detail|history|timeline|show|tell|about|status)\b/i', $userMessage)) {
                    return [['name' => 'get_booking_details', 'args' => $bookingArgs]];
                }
            }
            $tools = $this->pushTool($tools, ['name' => 'get_entity_relations', 'args' => $ids]);
        }

        $isCancellationReasonQuestion = (bool) preg_match(
            '/\b(cancel+lation|cancel+led?)\b.*\b(reason|reasons|why)\b/i',
            $userMessage
        ) || (bool) preg_match(
            '/\b(reason|reasons)\b.*\b(cancel+lation|cancel+led?)\b/i',
            $userMessage
        ) || (bool) preg_match(
            '/\b(top|main|common|frequent|biggest)\b.*\b(cancel+lation|cancel+led?)\b/i',
            $userMessage
        );

        $isCategoryPerformanceQuestion = (bool) preg_match(
            '/\b(category|categories|subcategory|subcategories|service type|service types)\b/i',
            $userMessage
        ) && (bool) preg_match(
            '/\b(perform|performance|performing|doing well|do well|best|top|well|strong|weak|conversion|complete|completed|booked|revenue|volume|which|what)\b/i',
            $userMessage
        );

        if ($isCategoryPerformanceQuestion) {
            if (preg_match('/\b(lead|leads|conversion|pipeline|crm)\b/i', $userMessage)
                && ! preg_match('/\b(booking|bookings|order|orders)\b/i', $userMessage)) {
                $reportType = preg_match('/\b(provider|vendor)\b/i', $userMessage) ? 'provider' : 'customer';

                return [['name' => 'get_lead_inbound_report', 'args' => ['report_type' => $reportType]]];
            }

            $tools = [['name' => 'get_business_reports', 'args' => ['report_type' => 'booking_analytics']]];
            if (! preg_match('/\b(booking|bookings|order|orders)\b/i', $userMessage)) {
                $tools[] = ['name' => 'get_lead_inbound_report', 'args' => ['report_type' => 'customer']];
            }

            return array_slice($tools, 0, $maxTools);
        }

        if ($isCancellationReasonQuestion) {
            if (preg_match('/\b(booking|bookings|order|orders)\b/i', $userMessage) && ! preg_match('/\b(lead|leads|crm)\b/i', $userMessage)) {
                return [['name' => 'analyze_bookings', 'args' => ['analysis' => 'cancellation_timing_report']]];
            }
            if (preg_match('/\b(invalid)\b/i', $userMessage)) {
                return [['name' => 'analyze_leads', 'args' => ['analysis' => 'invalid_reasons', 'lead_type' => 'invalid']]];
            }
            if (preg_match('/\b(future customer|future_customer)\b/i', $userMessage)) {
                return [['name' => 'analyze_leads', 'args' => ['analysis' => 'future_customer_reasons', 'lead_type' => 'future_customer']]];
            }
            if (preg_match('/\b(provider|vendor|technician|partner)\b/i', $userMessage) && preg_match('/\b(lead|leads|crm|pipeline)\b/i', $userMessage)) {
                return [['name' => 'analyze_leads', 'args' => ['analysis' => 'provider_cancellation_reasons', 'lead_type' => 'provider']]];
            }

            return [['name' => 'analyze_leads', 'args' => ['analysis' => 'customer_cancellation_reasons', 'lead_type' => 'customer']]];
        }

        if (preg_match('/\b(service|services)\b/i', $userMessage)) {
            if (preg_match('/\b(top|popular|best.?selling|most booked|order)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'analyze_services', 'args' => ['analysis' => 'top_by_orders']]);
            } elseif (preg_match('/\b(low rated|bad rating|poor rating)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'analyze_services', 'args' => ['analysis' => 'low_rated']]);
            } elseif (preg_match('/\b(list|search|show|find)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'query_services', 'args' => []]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_services', 'args' => ['analysis' => 'catalog_overview']]);
            }
        }

        if (preg_match('/\b(categor(?:y|ies)|sub.?categor(?:y|ies)|service type|service types)\b/i', $userMessage)
            && ! $isCategoryPerformanceQuestion) {
            if (preg_match('/\b(zone|area)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'analyze_category_catalog', 'args' => ['analysis' => 'by_zone']]);
            } elseif (preg_match('/\b(list|search|show|find)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'query_categories', 'args' => []]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_category_catalog', 'args' => ['analysis' => 'catalog_overview']]);
            }
            if (preg_match('/\b(booking|bookings|order|orders|revenue|volume)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'get_business_reports', 'args' => ['report_type' => 'booking_analytics']]);
            }
            if (preg_match('/\b(lead|leads|conversion|crm|pipeline)\b/i', $userMessage)) {
                $reportType = preg_match('/\b(provider|vendor)\b/i', $userMessage) ? 'provider' : 'customer';
                $tools = $this->pushTool($tools, ['name' => 'get_lead_inbound_report', 'args' => ['report_type' => $reportType]]);
            }
        }

        if (preg_match('/\b(review|reviews|rating|ratings|feedback|star)\b/i', $userMessage)
            && ! preg_match('/\b(provider detail|customer detail)\b/i', $userMessage)) {
            if (preg_match('/\b(negative|bad|low|complaint|1 star|2 star)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'analyze_reviews', 'args' => ['analysis' => 'recent_negative']]);
            } elseif (preg_match('/\b(top|best|highest)\b/i', $userMessage) && preg_match('/\b(provider|vendor)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'analyze_reviews', 'args' => ['analysis' => 'top_rated_providers']]);
            } elseif (preg_match('/\b(top|best|highest)\b/i', $userMessage) && preg_match('/\b(service)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'analyze_reviews', 'args' => ['analysis' => 'top_rated_services']]);
            } elseif (preg_match('/\b(low|worst|poor)\b/i', $userMessage) && preg_match('/\b(service)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'analyze_reviews', 'args' => ['analysis' => 'low_rated_services']]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_reviews', 'args' => ['analysis' => 'overview']]);
            }
        }

        if (preg_match('/\b(coupon|coupons|promotion|promotions|discount|discounts|campaign|campaigns|promo code)\b/i', $userMessage)) {
            if (preg_match('/\b(active|running|live|current)\b/i', $userMessage)) {
                if (preg_match('/\b(coupon)\b/i', $userMessage)) {
                    $tools = $this->pushTool($tools, ['name' => 'analyze_promotions', 'args' => ['analysis' => 'active_coupons']]);
                } elseif (preg_match('/\b(campaign)\b/i', $userMessage)) {
                    $tools = $this->pushTool($tools, ['name' => 'analyze_promotions', 'args' => ['analysis' => 'active_campaigns']]);
                } else {
                    $tools = $this->pushTool($tools, ['name' => 'analyze_promotions', 'args' => ['analysis' => 'active_discounts']]);
                }
            } elseif (preg_match('/\b(list|search|show|find|code)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'query_promotions', 'args' => []]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_promotions', 'args' => ['analysis' => 'promotion_overview']]);
            }
        }

        if (preg_match('/\b(subscription|subscriptions|package subscriber|provider package|subscription package)\b/i', $userMessage)) {
            if (preg_match('/\b(expir|renew|ending|due)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'analyze_subscriptions', 'args' => ['analysis' => 'expiring_soon']]);
            } elseif (preg_match('/\b(list|search|show|find|who)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'query_subscriptions', 'args' => []]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_subscriptions', 'args' => ['analysis' => 'subscription_overview']]);
            }
        }

        if (preg_match('/\b(withdraw|withdrawal|payout|payouts)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'query_withdraw_requests', 'args' => []]);
        }

        if (preg_match('/\b(ledger|company balance|in\/out)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'query_ledger', 'args' => []]);
        }

        if (preg_match('/\b(transaction|transactions|payment history)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'query_transactions', 'args' => []]);
        }

        if (preg_match('/\b(pending balance|collect cash|owe|owing provider)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'query_pending_provider_balances', 'args' => []]);
        }

        if (preg_match('/\b(verify requests?|offline payments?|special scenarios?|booking queues?|overdue bookings?)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'get_booking_queues_overview', 'args' => []]);
            if (preg_match('/\b(list|show|which|who)\b/i', $userMessage)) {
                $queue = 'overdue_followups';
                if (preg_match('/\b(verify)\b/i', $userMessage)) {
                    $queue = 'verify_requests';
                } elseif (preg_match('/\b(offline payment|offline)\b/i', $userMessage)) {
                    $queue = 'offline_payments';
                } elseif (preg_match('/\b(special scenario|loss|scaled)\b/i', $userMessage)) {
                    $queue = 'special_scenarios';
                }
                $tools = $this->pushTool($tools, ['name' => 'query_booking_queues', 'args' => ['queue' => $queue]]);
            }
        }

        if (preg_match('/\b(outbound|enquiry|enquiries)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'query_outbound_enquiries', 'args' => []]);
        }

        if (preg_match('/\b(conversion|inbound report|lead report|category wise|zone wise)\b/i', $userMessage)
            && preg_match('/\b(lead|leads)\b/i', $userMessage)) {
            $reportType = preg_match('/\b(provider|vendor)\b/i', $userMessage) ? 'provider' : 'customer';
            $tools = $this->pushTool($tools, ['name' => 'get_lead_inbound_report', 'args' => ['report_type' => $reportType]]);
        }

        if (preg_match('/\b(productivity|handled leads|leads handled)\b/i', $userMessage)
            && preg_match('/\b(employee|staff|agent|user)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'get_employee_lead_productivity', 'args' => []]);
        }

        if ((preg_match('/\b(zone|zones|area|areas|region)\b/i', $userMessage)
                && preg_match('/\b(booking|bookings|order|orders)\b/i', $userMessage))
            || preg_match('/\b(booking analytics|booking report)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'get_business_reports', 'args' => ['report_type' => 'booking_analytics']]);
        }

        if (preg_match('/\b(whatsapp|chat|chats|inbox|unassigned|human support)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'get_whatsapp_conversations_overview', 'args' => []]);
        }

        if (preg_match('/\b(employee|staff|agent|handled by|who is handling|workload|incomplete|unspecified|missing data|not filled)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'analyze_employee_activity', 'args' => ['analysis' => 'full_employee_overview']]);
        }

        if (preg_match('/\b(no response|unresponsive|not responding|no reply)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'analyze_leads', 'args' => ['analysis' => 'no_response_timing_report', 'lead_type' => 'all']]);
        }

        if (preg_match('/\b(lag|delay|response time|when.*(come|arrive|received|created)|what time|peak hour|followup.*time|updat(e|ing).*time)\b/i', $userMessage)) {
            if (preg_match('/\b(booking|bookings|order|orders|cancel|accepted|pending)\b/i', $userMessage)) {
                if (preg_match('/\b(cancel+ed?|cancellation)\b/i', $userMessage)) {
                    $tools = $this->pushTool($tools, ['name' => 'analyze_bookings', 'args' => ['analysis' => 'cancellation_timing_report']]);
                } elseif (preg_match('/\b(overdue|followup|follow-up)\b/i', $userMessage)) {
                    $tools = $this->pushTool($tools, ['name' => 'analyze_bookings', 'args' => ['analysis' => 'followup_timing_report']]);
                } else {
                    $tools = $this->pushTool($tools, ['name' => 'analyze_bookings', 'args' => ['analysis' => 'booking_timing_report', 'cohort' => 'all']]);
                }
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_leads', 'args' => ['analysis' => 'lead_timing_report', 'lead_type' => 'all', 'cohort' => 'all']]);
            }
        }

        if (preg_match('/\b(provider|providers|vendor|technicians)\b/i', $userMessage)) {
            if (preg_match('/\b(search|list|show|find|pending approval|onboarding)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'query_providers', 'args' => []]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_providers', 'args' => ['analysis' => 'full_provider_overview']]);
            }
        }

        if (preg_match('/\b(customer|customers|client|clients)\b/i', $userMessage)
            && ! preg_match('/\b(lead|leads|cancellation)\b/i', $userMessage)) {
            if (preg_match('/\b(search|list|show|find)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'query_customers', 'args' => []]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_customers', 'args' => ['analysis' => 'full_customer_overview']]);
            }
        }

        if (preg_match('/\b(lead|leads|pipeline|crm|enquir(?:y|ies))\b/i', $userMessage)
            && ! preg_match('/\b(conversion|inbound report|lead report|category wise|zone wise)\b/i', $userMessage)) {
            if (preg_match('/\b(list|search|show|find|pending|open)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'query_leads', 'args' => ['limit' => 25]]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_leads', 'args' => ['analysis' => 'full_lead_overview', 'lead_type' => 'all']]);
            }
        }

        if (preg_match('/\b(booking|bookings|order|orders|pk-\d+)\b/i', $userMessage)
            && ! preg_match('/\b(analytics|zone|area|timing|lag|cancel)\b/i', $userMessage)) {
            if (preg_match('/\b(list|search|show|find|pending|overdue)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'query_bookings', 'args' => ['limit' => 25]]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'analyze_bookings', 'args' => ['analysis' => 'full_booking_overview']]);
            }
        }

        if (preg_match('/\b(earning|expense|commission|profit|revenue|financial|money|payable)\b/i', $userMessage)) {
            if (preg_match('/\b(earning|revenue)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'get_business_reports', 'args' => ['report_type' => 'earning']]);
            } elseif (preg_match('/\b(expense)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'get_business_reports', 'args' => ['report_type' => 'expense']]);
            } elseif (preg_match('/\b(commission)\b/i', $userMessage)) {
                $tools = $this->pushTool($tools, ['name' => 'get_business_reports', 'args' => ['report_type' => 'commission_earning']]);
            } else {
                $tools = $this->pushTool($tools, ['name' => 'get_business_dashboard_overview', 'args' => []]);
            }
        }

        if (preg_match('/\b(dashboard|widget|snapshot|today|followup|follow-up)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'get_dashboard_snapshot', 'args' => []]);
        }

        if (preg_match('/\b(relation|related|linked|connect|connection|same phone|who handles)\b/i', $userMessage)) {
            $tools = $this->pushTool($tools, ['name' => 'get_entity_relations', 'args' => $ids]);
        }

        if (preg_match('/\b(full|complete|health|overview|analysis|report|summary)\b/i', $userMessage)
            && $tools === []) {
            return $this->defaultDiscoveryBundle();
        }

        $tools = $this->supplementCoreDomainTools($tools, $userMessage);

        if ($tools === []) {
            return $this->defaultDiscoveryBundle();
        }

        return array_slice($tools, 0, $maxTools);
    }

    /**
     * Whether the question touches categories, services, bookings, leads, customers, or providers.
     */
    public function mentionsCoreDomain(string $userMessage): bool
    {
        return (bool) preg_match(
            '/\b(categor(?:y|ies)|sub.?categor|service|services|booking|bookings|order|orders|lead|leads|crm|pipeline|customer|customers|client|clients|provider|providers|vendor|technician|pk-\d+)\b/i',
            $userMessage
        );
    }

    /**
     * Multi-tool bundle covering all six core business domains.
     *
     * @return list<array{name: string, args: array<string, mixed>}>
     */
    public function defaultDiscoveryBundle(): array
    {
        return [
            ['name' => 'analyze_category_catalog', 'args' => ['analysis' => 'catalog_overview']],
            ['name' => 'analyze_services', 'args' => ['analysis' => 'catalog_overview']],
            ['name' => 'analyze_bookings', 'args' => ['analysis' => 'full_booking_overview']],
            ['name' => 'analyze_leads', 'args' => ['analysis' => 'full_lead_overview', 'lead_type' => 'all']],
            ['name' => 'analyze_customers', 'args' => ['analysis' => 'full_customer_overview']],
            ['name' => 'analyze_providers', 'args' => ['analysis' => 'full_provider_overview']],
        ];
    }

    /**
     * Ensure every core domain mentioned in the question has at least one data tool planned.
     *
     * @param  list<array{name: string, args: array<string, mixed>}>  $tools
     * @return list<array{name: string, args: array<string, mixed>}>
     */
    public function supplementCoreDomainTools(array $tools, string $userMessage): array
    {
        if (preg_match('/\b(categor(?:y|ies)|sub.?categor|service type)\b/i', $userMessage)
            && ! $this->hasToolFamily($tools, ['analyze_category_catalog', 'query_categories', 'get_lead_inbound_report'])) {
            $tools = $this->pushTool($tools, ['name' => 'analyze_category_catalog', 'args' => ['analysis' => 'catalog_overview']]);
        }

        if (preg_match('/\b(service|services)\b/i', $userMessage)
            && ! $this->hasToolFamily($tools, ['analyze_services', 'query_services'])) {
            $tools = $this->pushTool($tools, ['name' => 'analyze_services', 'args' => ['analysis' => 'catalog_overview']]);
        }

        if (preg_match('/\b(booking|bookings|order|orders|pk-\d+)\b/i', $userMessage)
            && ! $this->hasToolFamily($tools, ['analyze_bookings', 'query_bookings', 'get_booking_details', 'get_business_reports'])) {
            $tools = $this->pushTool($tools, ['name' => 'analyze_bookings', 'args' => ['analysis' => 'full_booking_overview']]);
        }

        if (preg_match('/\b(lead|leads|crm|pipeline|enquir(?:y|ies))\b/i', $userMessage)
            && ! $this->hasToolFamily($tools, ['analyze_leads', 'query_leads', 'get_lead_details', 'get_lead_inbound_report'])) {
            $tools = $this->pushTool($tools, ['name' => 'analyze_leads', 'args' => ['analysis' => 'full_lead_overview', 'lead_type' => 'all']]);
        }

        if (preg_match('/\b(customer|customers|client|clients)\b/i', $userMessage)
            && ! preg_match('/\b(lead|leads|cancellation|future customer)\b/i', $userMessage)
            && ! $this->hasToolFamily($tools, ['analyze_customers', 'query_customers', 'get_customer_details'])) {
            $tools = $this->pushTool($tools, ['name' => 'analyze_customers', 'args' => ['analysis' => 'full_customer_overview']]);
        }

        if (preg_match('/\b(provider|providers|vendor|vendors|technician|technicians|partner|partners)\b/i', $userMessage)
            && ! $this->hasToolFamily($tools, ['analyze_providers', 'query_providers', 'get_provider_details'])) {
            $tools = $this->pushTool($tools, ['name' => 'analyze_providers', 'args' => ['analysis' => 'full_provider_overview']]);
        }

        return $tools;
    }

    /**
     * @param  list<array{name: string, args: array<string, mixed>}>  $tools
     * @param  list<string>  $families
     */
    private function hasToolFamily(array $tools, array $families): bool
    {
        foreach ($tools as $tool) {
            $name = (string) ($tool['name'] ?? '');
            foreach ($families as $family) {
                if ($name === $family || str_starts_with($name, $family)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<array{name: string, args: array<string, mixed>}>  $tools
     * @param  array{name: string, args: array<string, mixed>}  $tool
     * @return list<array{name: string, args: array<string, mixed>}>
     */
    private function pushTool(array $tools, array $tool): array
    {
        foreach ($tools as $existing) {
            if (($existing['name'] ?? '') === ($tool['name'] ?? '')) {
                return $tools;
            }
        }
        $tools[] = $tool;

        return $tools;
    }
}

<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\LeadManagement\Entities\AdSource;
use Modules\LeadManagement\Entities\District;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadFutureCustomerReasonOption;
use Modules\LeadManagement\Entities\LeadFutureProviderReason;
use Modules\LeadManagement\Entities\LeadReasonFollowupOption;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadOutboundEnquiryStatus;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\ProviderChecklistItem;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\Source;

class LeadConfigurationController extends Controller
{
    public function index(): View
    {
        $sources = Source::orderBy('name')->get();
        $adSources = AdSource::orderBy('name')->get();
        $invalidReasons = LeadInvalidReason::with('options')->orderBy('name')->get();
        $futureCustomerReasons = LeadFutureCustomerReason::with('options')->orderBy('name')->get();
        $futureProviderReasons = LeadFutureProviderReason::with('options')->orderBy('name')->get();
        $districts = District::orderBy('name')->get();
        $cancellationReasons = LeadCancellationReason::with('options')->orderBy('name')->get();
        $customerLeadStatuses = CustomerLeadStatus::orderBy('name')->get();
        $customerLeadTags = CustomerLeadTag::orderBy('name')->get();
        $providerLeadStatuses = ProviderLeadStatus::orderBy('name')->get();
        $providerCancellationReasons = ProviderCancellationReason::with('options')->orderBy('name')->get();
        $providerChecklistItems = ProviderChecklistItem::orderBy('name')->get();
        $outboundEnquiryStatuses = LeadOutboundEnquiryStatus::orderBy('name')->get();

        return view('leadmanagement::admin.configuration.index', compact(
            'sources',
            'adSources',
            'invalidReasons',
            'futureCustomerReasons',
            'futureProviderReasons',
            'districts',
            'cancellationReasons',
            'customerLeadStatuses',
            'customerLeadTags',
            'providerLeadStatuses',
            'providerChecklistItems',
            'providerCancellationReasons',
            'outboundEnquiryStatuses'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type');

        [$modelClass, $nameField] = $this->resolveType($type);

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];
        if (in_array($type, ['provider_lead_status', 'customer_lead_status', 'customer_lead_tag'])) {
            $rules['color'] = 'nullable|string|max:20';
        }
        if (in_array($type, ['customer_lead_status', 'provider_lead_status'], true)) {
            $rules['base_type'] = 'nullable|in:pending,cancel,completed';
        }
        if ($this->isFollowupReasonType($type)) {
            $rules['followup_label'] = 'nullable|string|max:255';
        }
        if ($parentTable = $this->followupOptionParentTable($type)) {
            $rules['parent_id'] = 'required|exists:'.$parentTable.',id';
        }
        $data = $this->validateConfiguration($request, $rules);

        if ($reasonType = $this->sharedFollowupOptionType($type)) {
            LeadReasonFollowupOption::create([
                'reason_type' => $reasonType,
                'reason_id' => $data['parent_id'],
                'name' => $data['title'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            return $this->redirectToConfigurationTab($request, translate('Configuration_saved_successfully'));
        }

        $payload = [
            $nameField => $data['title'],
            'is_active' => $request->boolean('is_active', true),
        ];
        if ($type !== 'future_customer_reason_option') {
            $payload['description'] = $data['description'] ?? null;
        }
        if ($this->isFollowupReasonType($type)) {
            $payload['followup_label'] = trim((string) ($data['followup_label'] ?? '')) ?: null;
        }
        if ($type === 'future_customer_reason_option') {
            $payload['lead_future_customer_reason_id'] = $data['parent_id'];
        }
        if (in_array($type, ['provider_lead_status', 'customer_lead_status', 'customer_lead_tag'])) {
            $payload['color'] = $data['color'] ?? '#0d6efd';
        }
        if (in_array($type, ['customer_lead_status', 'provider_lead_status'], true)) {
            $payload['base_type'] = $data['base_type'] ?? 'pending';
        }
        $modelClass::create($payload);

        return $this->redirectToConfigurationTab($request, translate('Configuration_saved_successfully'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $type = $request->input('type');
        $mode = $request->input('mode', 'edit');

        [$modelClass, $nameField] = $this->resolveType($type);

        $item = $modelClass::findOrFail($id);

        if ($mode === 'toggle') {
            $this->validateConfiguration($request, [
                'is_active' => 'required|boolean',
            ]);

            $item->is_active = (bool) $request->input('is_active');
            $item->save();

            return $this->redirectToConfigurationTab($request, translate('Configuration_status_updated_successfully'));
        }

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];
        if (in_array($type, ['provider_lead_status', 'customer_lead_status', 'customer_lead_tag'])) {
            $rules['color'] = 'nullable|string|max:20';
        }
        if (in_array($type, ['customer_lead_status', 'provider_lead_status'], true)) {
            $rules['base_type'] = 'nullable|in:pending,cancel,completed';
        }
        if ($this->isFollowupReasonType($type)) {
            $rules['followup_label'] = 'nullable|string|max:255';
        }
        if ($parentTable = $this->followupOptionParentTable($type)) {
            $rules['parent_id'] = 'required|exists:'.$parentTable.',id';
        }
        $data = $this->validateConfiguration($request, $rules);

        $item->{$nameField} = $data['title'];
        if (! $this->isFollowupOptionType($type)) {
            $item->description = $data['description'] ?? null;
        }
        $item->is_active = $request->boolean('is_active', true);
        if ($this->isFollowupReasonType($type)) {
            $item->followup_label = trim((string) ($data['followup_label'] ?? '')) ?: null;
        }
        if ($type === 'future_customer_reason_option') {
            $item->lead_future_customer_reason_id = $data['parent_id'];
        }
        if ($this->sharedFollowupOptionType($type)) {
            $item->reason_id = $data['parent_id'];
        }
        if (in_array($type, ['provider_lead_status', 'customer_lead_status', 'customer_lead_tag']) && array_key_exists('color', $data)) {
            $item->color = $data['color'] ?? '#0d6efd';
        }
        if (in_array($type, ['customer_lead_status', 'provider_lead_status'], true) && array_key_exists('base_type', $data)) {
            $item->base_type = $data['base_type'] ?? 'pending';
        }
        $item->save();

        return $this->redirectToConfigurationTab($request, translate('Configuration_updated_successfully'));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $type = $request->input('type');

        [$modelClass] = $this->resolveType($type);

        $item = $modelClass::findOrFail($id);
        if ($type === 'future_customer_reason') {
            LeadFutureCustomerReasonOption::where('lead_future_customer_reason_id', $item->id)->delete();
        }
        if ($this->isFollowupReasonType($type) && $type !== 'future_customer_reason') {
            LeadReasonFollowupOption::where('reason_type', $type)->where('reason_id', $item->id)->delete();
        }
        $item->delete();

        return $this->redirectToConfigurationTab($request, translate('Configuration_deleted_successfully'));
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function validateConfiguration(Request $request, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo($this->configurationTabUrl($request));
        }

        return $validator->validated();
    }

    protected function redirectToConfigurationTab(Request $request, string $message): RedirectResponse
    {
        return redirect()->to($this->configurationTabUrl($request))->with('success', $message);
    }

    protected function configurationTabUrl(Request $request): string
    {
        $tab = (string) $request->input('return_tab', 'general-tab-pane');
        $allowed = [
            'general-tab-pane',
            'customer-tab-pane',
            'provider-tab-pane',
            'future-customer-tab-pane',
            'future-provider-tab-pane',
            'invalid-tab-pane',
        ];
        if (! in_array($tab, $allowed, true)) {
            $tab = 'general-tab-pane';
        }

        return route('admin.lead.configuration.index', ['tab' => $tab]);
    }

    /**
     * @return array{0: class-string, 1: string}
     */
    protected function resolveType(string $type): array
    {
        return match ($type) {
            'source' => [Source::class, 'name'],
            'ad_source' => [AdSource::class, 'name'],
            'invalid_reason' => [LeadInvalidReason::class, 'name'],
            'future_customer_reason' => [LeadFutureCustomerReason::class, 'name'],
            'future_customer_reason_option' => [LeadFutureCustomerReasonOption::class, 'name'],
            'future_provider_reason' => [LeadFutureProviderReason::class, 'name'],
            'future_provider_reason_option' => [LeadReasonFollowupOption::class, 'name'],
            'invalid_reason_option' => [LeadReasonFollowupOption::class, 'name'],
            'cancellation_reason_option' => [LeadReasonFollowupOption::class, 'name'],
            'provider_cancellation_reason_option' => [LeadReasonFollowupOption::class, 'name'],
            'district' => [District::class, 'name'],
            'cancellation_reason' => [LeadCancellationReason::class, 'name'],
            'customer_lead_status' => [CustomerLeadStatus::class, 'name'],
            'customer_lead_tag' => [CustomerLeadTag::class, 'name'],
            'provider_lead_status' => [ProviderLeadStatus::class, 'name'],
            'provider_cancellation_reason' => [ProviderCancellationReason::class, 'name'],
            'provider_checklist_item' => [ProviderChecklistItem::class, 'name'],
            'outbound_enquiry_status' => [LeadOutboundEnquiryStatus::class, 'name'],
            default => abort(400, 'Unknown configuration type'),
        };
    }

    protected function isFollowupReasonType(string $type): bool
    {
        return in_array($type, [
            'future_customer_reason',
            'future_provider_reason',
            'invalid_reason',
            'cancellation_reason',
            'provider_cancellation_reason',
        ], true);
    }

    protected function isFollowupOptionType(string $type): bool
    {
        return $type === 'future_customer_reason_option' || $this->sharedFollowupOptionType($type) !== null;
    }

    protected function sharedFollowupOptionType(string $type): ?string
    {
        return match ($type) {
            'future_provider_reason_option' => 'future_provider_reason',
            'invalid_reason_option' => 'invalid_reason',
            'cancellation_reason_option' => 'cancellation_reason',
            'provider_cancellation_reason_option' => 'provider_cancellation_reason',
            default => null,
        };
    }

    protected function followupOptionParentTable(string $type): ?string
    {
        return match ($type) {
            'future_customer_reason_option' => 'lead_future_customer_reasons',
            'future_provider_reason_option' => 'lead_future_provider_reasons',
            'invalid_reason_option' => 'lead_invalid_reasons',
            'cancellation_reason_option' => 'lead_cancellation_reasons',
            'provider_cancellation_reason_option' => 'provider_cancellation_reasons',
            default => null,
        };
    }
}


<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadManagement\Entities\AdSource;
use Modules\LeadManagement\Entities\District;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
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
        $invalidReasons = LeadInvalidReason::orderBy('name')->get();
        $futureCustomerReasons = LeadFutureCustomerReason::orderBy('name')->get();
        $districts = District::orderBy('name')->get();
        $cancellationReasons = LeadCancellationReason::orderBy('name')->get();
        $customerLeadStatuses = CustomerLeadStatus::orderBy('name')->get();
        $customerLeadTags = CustomerLeadTag::orderBy('name')->get();
        $providerLeadStatuses = ProviderLeadStatus::orderBy('name')->get();
        $providerCancellationReasons = ProviderCancellationReason::orderBy('name')->get();
        $providerChecklistItems = ProviderChecklistItem::orderBy('name')->get();
        $outboundEnquiryStatuses = LeadOutboundEnquiryStatus::orderBy('name')->get();

        return view('leadmanagement::admin.configuration.index', compact(
            'sources',
            'adSources',
            'invalidReasons',
            'futureCustomerReasons',
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
        $data = $request->validate($rules);

        $payload = [
            $nameField => $data['title'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];
        if (in_array($type, ['provider_lead_status', 'customer_lead_status', 'customer_lead_tag'])) {
            $payload['color'] = $data['color'] ?? '#0d6efd';
        }
        if (in_array($type, ['customer_lead_status', 'provider_lead_status'], true)) {
            $payload['base_type'] = $data['base_type'] ?? 'pending';
        }
        $modelClass::create($payload);

        return back()->with('success', translate('Configuration_saved_successfully'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $type = $request->input('type');
        $mode = $request->input('mode', 'edit');

        [$modelClass, $nameField] = $this->resolveType($type);

        $item = $modelClass::findOrFail($id);

        if ($mode === 'toggle') {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $item->is_active = (bool) $request->input('is_active');
            $item->save();

            return back()->with('success', translate('Configuration_status_updated_successfully'));
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
        $data = $request->validate($rules);

        $item->{$nameField} = $data['title'];
        $item->description = $data['description'] ?? null;
        $item->is_active = $request->boolean('is_active', true);
        if (in_array($type, ['provider_lead_status', 'customer_lead_status', 'customer_lead_tag']) && array_key_exists('color', $data)) {
            $item->color = $data['color'] ?? '#0d6efd';
        }
        if (in_array($type, ['customer_lead_status', 'provider_lead_status'], true) && array_key_exists('base_type', $data)) {
            $item->base_type = $data['base_type'] ?? 'pending';
        }
        $item->save();

        return back()->with('success', translate('Configuration_updated_successfully'));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $type = $request->input('type');

        [$modelClass] = $this->resolveType($type);

        $item = $modelClass::findOrFail($id);
        $item->delete();

        return back()->with('success', translate('Configuration_deleted_successfully'));
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
}


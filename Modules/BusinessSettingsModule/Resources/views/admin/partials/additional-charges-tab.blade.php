<div class="tab-content">
    <div class="tab-pane fade active show">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h5 class="mb-1">{{ translate('Additional_charges') }}</h5>
                        <p class="fz-12 text-muted mb-0">{{ translate('Additional_charges_tab_intro') }}</p>
                    </div>
                    <a href="{{ route('admin.business-settings.additional-charges.create') }}" class="btn btn--primary">
                        {{ translate('Add_additional_charge') }}
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>{{ translate('Name') }}</th>
                                <th>{{ translate('sort_order') }}</th>
                                <th>{{ translate('Booking') }}</th>
                                <th>{{ translate('Commission') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th class="text-end">{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($additionalChargeTypes as $t)
                                <tr>
                                    <td>{{ $t->name }}</td>
                                    <td>{{ $t->sort_order }}</td>
                                    <td>{{ ($t->customizable_at_booking ?? false) ? translate('Customizable') : translate('Not_customizable_on_booking') }}</td>
                                    <td>{{ ($t->is_commissionable ?? true) ? translate('Yes') : translate('No') }}</td>
                                    <td>{{ $t->is_active ? translate('active') : translate('inactive') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.business-settings.additional-charges.edit', $t->id) }}"
                                           class="btn btn-sm btn-outline-primary">{{ translate('edit') }}</a>
                                        <form action="{{ route('admin.business-settings.additional-charges.destroy', $t->id) }}"
                                              method="post" class="d-inline" onsubmit="return confirm(@json(translate('are_you_sure')));">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ translate('delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted fz-13">{{ translate('No_additional_charge_types_yet') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

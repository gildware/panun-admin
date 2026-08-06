@php
    $isTabbed = ($box['box_type'] ?? '') === 'tabbed';
    $tabKeys = array_keys($box['tabs'] ?? []);
    $singleTab = count($tabKeys) === 1;
    $columns = $box['columns'] ?? [];
    $listDisplay = $box['list_display'] ?? 'table';
    $emptyIcon = $box['empty_icon'] ?? match ($listDisplay) {
        'whatsapp_cards' => 'forum',
        'cards' => 'task_alt',
        default => 'inbox',
    };
    $emptyLabel = $box['empty_label'] ?? match ($box['key'] ?? '') {
        'whatsapp_assigned_unread' => translate('No_assigned_whatsapp_new_messages'),
        'whatsapp_unassigned' => translate('No_unassigned_whatsapp_chats'),
        'unassigned_leads' => translate('No_unassigned_leads'),
        'unassigned_bookings' => translate('No_unassigned_bookings'),
        'pending_tasks' => translate('No_pending_tasks'),
        'lead_followups_pending', 'booking_followups_pending' => translate('All_caught_up'),
        default => translate('no_data_available'),
    };
    $hasEmployeeTab = collect($box['tabs'] ?? [])->contains(fn ($tab) => ! empty($tab['employee_select']));
    $employeeTabKey = $hasEmployeeTab ? 'employee' : 'yours';
    $employeeFooterTemplate = match ($box['key'] ?? '') {
        'pending_tasks' => translate('View_employee_tasks'),
        'whatsapp_assigned_unread' => translate('View_employee_whatsapp_messages'),
        'lead_followups_pending', 'booking_followups_pending' => translate('View_employee_follow_ups'),
        default => '',
    };
@endphp

<div class="work-queue-box tone-{{ $box['tone'] ?? 'lead' }}" id="inbox-box-{{ $box['key'] }}" @if($hasEmployeeTab) data-has-employee-tab="1" @endif>
    <div class="work-queue-box-header">
        <div class="work-queue-box-title">
            <span class="material-symbols-outlined">{{ $box['icon'] ?? 'info' }}</span>
            <span>{{ $box['title'] ?? '' }}</span>
        </div>
        @if($isTabbed && ! $singleTab)
            <div class="work-queue-tabs" data-tabs="{{ $box['key'] }}">
                @foreach($box['tabs'] ?? [] as $tabKey => $tab)
                    <button type="button"
                            class="work-queue-tab {{ $loop->first ? 'active' : '' }} {{ ! empty($tab['employee_select']) ? 'work-queue-tab--employee js-work-queue-employee-tab d-none' : '' }}"
                            data-tab="{{ $box['key'] }}-{{ $tabKey }}">
                        @if(! empty($tab['employee_select']))
                            <span class="work-queue-tab-text js-work-queue-employee-tab-label" data-default-label="{{ translate('Employee') }}">{{ $tab['label'] }}</span>
                            <span class="work-queue-tab-count js-work-queue-employee-tab-count">({{ $tab['total'] ?? 0 }})</span>
                        @else
                            {{ $tab['label'] }} ({{ $tab['total'] ?? 0 }})
                        @endif
                    </button>
                @endforeach
            </div>
        @elseif($isTabbed)
            @php($firstTab = $box['tabs'][$tabKeys[0]] ?? [])
            <span class="work-queue-count-badge {{ ($firstTab['total'] ?? 0) > 0 ? 'is-hot' : '' }}">{{ $firstTab['total'] ?? 0 }}</span>
        @endif
    </div>

    @if($isTabbed)
        <div class="work-queue-box-content">
        @foreach($box['tabs'] ?? [] as $tabKey => $tab)
            <div data-panel="{{ $box['key'] }}-{{ $tabKey }}" class="work-queue-box-body {{ $loop->first ? 'active' : '' }}">
                @if(! empty($tab['employee_select']))
                    @if(($tab['employees'] ?? []) !== [])
                        @foreach($tab['datasets'] ?? [] as $employeeId => $dataset)
                            <div class="js-work-queue-employee-panel d-none"
                                 data-employee-id="{{ $employeeId }}"
                                 data-total="{{ $dataset['total'] ?? 0 }}"
                                 data-view-all-url="{{ $dataset['view_all_url'] ?? '#' }}">
                                @include('adminmodule::partials._employee-work-queue-tab-items', [
                                    'items' => $dataset['items'] ?? [],
                                    'columns' => $tab['columns'] ?? $columns,
                                    'listDisplay' => $listDisplay,
                                    'emptyIcon' => $emptyIcon,
                                    'emptyLabel' => $emptyLabel,
                                ])
                            </div>
                        @endforeach
                    @else
                        <div class="work-queue-empty">
                            <span class="material-symbols-outlined">group_off</span>
                            <span>{{ translate('Select_employee') }}</span>
                        </div>
                    @endif
                @else
                    @include('adminmodule::partials._employee-work-queue-tab-items', [
                        'items' => $tab['items'] ?? [],
                        'columns' => $tab['columns'] ?? $columns,
                        'listDisplay' => $listDisplay,
                        'emptyIcon' => $emptyIcon,
                        'emptyLabel' => $tab['empty_label'] ?? $emptyLabel,
                    ])
                @endif
            </div>
        @endforeach
        </div>
        <div class="work-queue-box-footer">
            @if($singleTab)
                <a href="{{ $box['view_all_all_url'] ?? '#' }}" class="work-queue-footer-link is-single">{{ $box['footer_all_label'] ?? translate('view_all') }}</a>
            @else
                <a href="{{ $box['view_all_yours_url'] ?? '#' }}"
                   class="work-queue-footer-link is-primary js-work-queue-employee-footer-link {{ $hasEmployeeTab ? 'd-none' : '' }}"
                   data-tab-key="{{ $employeeTabKey }}"
                   data-default-label="{{ $box['footer_yours_label'] ?? translate('View_yours') }}"
                   @if($hasEmployeeTab && $employeeFooterTemplate !== '')
                       data-employee-label-template="{{ $employeeFooterTemplate }}"
                   @endif>{{ $box['footer_yours_label'] ?? translate('View_yours') }}</a>
                <a href="{{ $box['view_all_all_url'] ?? '#' }}" class="work-queue-footer-link is-all">{{ $box['footer_all_label'] ?? translate('view_all') }}</a>
            @endif
        </div>
    @endif
</div>

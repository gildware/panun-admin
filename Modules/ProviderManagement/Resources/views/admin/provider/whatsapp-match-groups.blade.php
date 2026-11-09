@extends('adminmodule::layouts.master')

@section('title', translate('Manage_Data'))

@push('css_or_js')
    <style>
        .wg-page-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem 1rem;
            margin-bottom: 1rem;
        }
        .wg-page-header h4 {
            margin: 0 0 .2rem;
            font-size: 1.2rem;
            font-weight: 650;
        }
        .wg-page-header p {
            margin: 0;
            color: #667085;
            font-size: .875rem;
        }
        .wg-header-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
            margin-left: auto;
        }
        .wg-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem 1rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .85rem;
            padding: .7rem .85rem;
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: .55rem;
        }
        .wg-toolbar input[type="search"] {
            flex: 1 1 14rem;
            min-width: 12rem;
            max-width: 22rem;
            height: 2.25rem;
            border: 1px solid #d0d5dd;
            border-radius: .45rem;
            padding: 0 .75rem;
            font-size: .875rem;
        }
        .wg-toolbar .btn {
            white-space: nowrap;
            margin-left: auto;
        }
        .wg-toolbar input[type="search"]:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .12);
        }
        .wg-table-wrap {
            overflow: auto;
            border: 1px solid #e4e7ec;
            border-radius: .65rem;
            background: #fff;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        }
        .wg-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .84rem;
            margin: 0;
        }
        .wg-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #1d2939;
            color: #fff;
            text-align: left;
            padding: .7rem .75rem;
            white-space: nowrap;
            font-weight: 600;
            font-size: .78rem;
        }
        .wg-table td {
            padding: .7rem .75rem;
            border-bottom: 1px solid #f2f4f7;
            vertical-align: middle;
            line-height: 1.35;
        }
        .wg-table tbody tr:hover td {
            background: #f8fafc;
        }
        .wg-table tbody tr.is-focus td {
            background: #f0fdf9;
        }
        .wg-group-name {
            font-weight: 650;
            color: #101828;
        }
        .wg-color-swatch {
            display: inline-block;
            width: .85rem;
            height: .85rem;
            border-radius: 999px;
            margin-right: .45rem;
            border: 1px solid rgba(16, 24, 40, .14);
            vertical-align: -0.1rem;
        }
        #wg-table tbody tr[data-color] td {
            background-color: var(--wg-row-bg);
        }
        .wg-count {
            font-variant-numeric: tabular-nums;
            font-weight: 650;
            color: #0f766e;
        }
        .wg-preview {
            color: #667085;
            font-size: .8rem;
            max-width: 32rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .wg-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            justify-content: flex-end;
            white-space: nowrap;
        }
        .wg-actions .btn {
            font-size: .8rem;
            padding: .28rem .7rem;
        }
        .wg-actions form {
            margin: 0;
            display: inline;
        }
        .wg-empty {
            text-align: center;
            color: #667085;
            padding: 2rem 1rem;
        }
        .wg-note {
            margin-top: .85rem;
            color: #667085;
            font-size: .8rem;
        }
        #wgGroupModal .modal-dialog {
            max-width: 40rem;
        }
        #wgGroupModal .modal-header {
            border-bottom: 1px solid #f2f4f7;
        }
        #wgGroupModal .modal-title {
            font-size: 1.05rem;
            font-weight: 650;
        }
        #wgGroupModal label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: #344054;
            margin-bottom: .3rem;
        }
        #wgGroupModal .form-control {
            border: 1px solid #d0d5dd;
            border-radius: .5rem;
            padding: .55rem .75rem;
            font-size: .875rem;
        }
        #wgGroupModal .form-control:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .12);
        }
        #wgGroupModal .form-hint {
            margin: .35rem 0 0;
            color: #667085;
            font-size: .78rem;
        }
        #wgGroupModal .modal-footer {
            border-top: 1px solid #f2f4f7;
        }
        .wg-contacts-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .55rem;
        }
        .wg-contacts-head label {
            margin: 0;
        }
        .wg-contacts-list {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            max-height: 22rem;
            overflow: auto;
            padding-right: .15rem;
        }
        .wg-contact-row {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr) auto;
            gap: .45rem;
            align-items: start;
            padding: .55rem;
            border: 1px solid #e4e7ec;
            border-radius: .5rem;
            background: #f9fafb;
        }
        .wg-contact-row .wg-field {
            min-width: 0;
        }
        .wg-contact-row .wg-field label {
            margin-bottom: .2rem;
            font-size: .72rem;
            font-weight: 600;
            color: #667085;
        }
        .wg-contact-row .wg-remove-contact {
            margin-top: 1.25rem;
            white-space: nowrap;
            font-size: .78rem;
            padding: .35rem .55rem;
        }
        .wg-contacts-empty {
            color: #667085;
            font-size: .82rem;
            padding: .65rem .2rem;
        }
        @media (max-width: 575.98px) {
            .wg-contact-row {
                grid-template-columns: 1fr;
            }
            .wg-contact-row .wg-remove-contact {
                margin-top: 0;
                justify-self: start;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $focus = (string) request()->query('focus', '');
        $openModal = (string) session('open_group_modal', '');
        if ($openModal === '' && old('group_name') !== null) {
            $openModal = old('original_name') ? 'edit' : 'create';
        }
        $oldContacts = old('contacts');
        if (!is_array($oldContacts)) {
            $oldContacts = null;
        }
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="wg-page-header">
                <div>
                    <h4>{{ translate('Manage_Data') }}</h4>
                    <p>{{ translate('Add_edit_or_remove_WhatsApp_group_names_and_phone_numbers') }}</p>
                </div>
                @include('providermanagement::admin.provider.partials._whatsapp-match-nav', ['active' => 'groups'])
            </div>

            <div class="wg-toolbar">
                <input type="search" id="wg-q" placeholder="{{ translate('Search_group_or_phone') }}">
                <button type="button" class="btn btn--primary" id="wg-add-btn">
                    {{ translate('Add_WhatsApp_group') }}
                </button>
            </div>

            <div class="wg-table-wrap">
                <table class="wg-table" id="wg-table">
                    <thead>
                        <tr>
                            <th style="width: 2.5rem;">#</th>
                            <th>{{ translate('WhatsApp_group_name') }}</th>
                            <th>{{ translate('Contacts') }}</th>
                            <th>{{ translate('Preview') }}</th>
                            <th style="text-align: right;">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $groupName => $contacts)
                            @php
                                $previewParts = [];
                                foreach (array_slice($contacts, 0, 4) as $contact) {
                                    $label = trim(($contact['name'] ?? '') !== ''
                                        ? ($contact['name'].' ('.$contact['phone'].')')
                                        : ($contact['phone'] ?? ''));
                                    if ($label !== '') {
                                        $previewParts[] = $label;
                                    }
                                }
                                $preview = implode(', ', $previewParts);
                                if (count($contacts) > 4) {
                                    $preview .= '…';
                                }
                                $searchBits = [$groupName];
                                foreach ($contacts as $contact) {
                                    $searchBits[] = $contact['phone'] ?? '';
                                    $searchBits[] = $contact['name'] ?? '';
                                }
                                $groupColor = $colors[$groupName] ?? '#F2F4F7';
                            @endphp
                            <tr
                                class="{{ $focus === $groupName ? 'is-focus' : '' }}"
                                data-group="{{ $groupName }}"
                                data-contacts='@json($contacts)'
                                data-search="{{ strtolower(implode(' ', $searchBits)) }}"
                                data-color="{{ $groupColor }}"
                                style="--wg-row-bg: {{ $groupColor }};"
                                id="group-{{ md5($groupName) }}"
                            >
                                <td>{{ $loop->iteration }}</td>
                                <td class="wg-group-name">
                                    <span class="wg-color-swatch" style="background: {{ $groupColor }};" title="{{ $groupColor }}"></span>{{ $groupName }}
                                </td>
                                <td><span class="wg-count">{{ count($contacts) }}</span></td>
                                <td><div class="wg-preview" title="{{ $preview }}">{{ $preview !== '' ? $preview : '—' }}</div></td>
                                <td>
                                    <div class="wg-actions">
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary wg-edit-btn"
                                            data-group="{{ $groupName }}"
                                        >
                                            {{ translate('Edit') }}
                                        </button>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.provider.whatsapp-match.groups.destroy') }}"
                                            onsubmit="return confirm(@json(translate('Confirm_delete_WhatsApp_group')));"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="group_name" value="{{ $groupName }}">
                                            <button type="submit" class="btn btn-outline-danger">{{ translate('Delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="wg-empty">
                                    {{ translate('No_WhatsApp_provider_numbers_configured') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="wg-note">
                {{ translate('Phone_match_uses_last_10_digits') }}.
                {{ translate('Contact_name_is_optional') }}.
            </p>
        </div>
    </div>

    <div class="modal fade" id="wgGroupModal" tabindex="-1" aria-labelledby="wgGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" id="wg-group-form" action="{{ route('admin.provider.whatsapp-match.groups.store') }}">
                    @csrf
                    <input type="hidden" name="original_name" id="wg-original-name" value="{{ old('original_name') }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="wgGroupModalLabel">{{ translate('Add_WhatsApp_group') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="wg-group-name">{{ translate('WhatsApp_group_name') }}</label>
                            <input
                                type="text"
                                class="form-control"
                                id="wg-group-name"
                                name="group_name"
                                value="{{ old('group_name') }}"
                                placeholder="{{ translate('Enter_WhatsApp_group_name') }}"
                                required
                                maxlength="120"
                            >
                        </div>

                        <div class="wg-contacts-head">
                            <label>{{ translate('Contacts') }}</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="wg-add-contact">
                                {{ translate('Add_contact') }}
                            </button>
                        </div>
                        <div id="wg-contacts-list" class="wg-contacts-list" aria-live="polite"></div>
                        <p class="form-hint">{{ translate('Add_each_contact_with_phone_required_and_name_optional') }}</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary" id="wg-group-submit">
                            {{ translate('Save_WhatsApp_group') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const storeUrl = @json(route('admin.provider.whatsapp-match.groups.store'));
            const updateUrl = @json(route('admin.provider.whatsapp-match.groups.update'));
            const labels = {
                addTitle: @json(translate('Add_WhatsApp_group')),
                editTitle: @json(translate('Edit_WhatsApp_group')),
                save: @json(translate('Save_WhatsApp_group')),
                nameOptional: @json(translate('Name_optional')),
                phone: @json(translate('Phone_number')),
                remove: @json(translate('Remove')),
                empty: @json(translate('No_contacts_yet_add_one_below')),
                needPhone: @json(translate('Each_contact_needs_a_phone_number')),
            };

            const modalEl = document.getElementById('wgGroupModal');
            const form = document.getElementById('wg-group-form');
            const titleEl = document.getElementById('wgGroupModalLabel');
            const nameInput = document.getElementById('wg-group-name');
            const originalInput = document.getElementById('wg-original-name');
            const contactsList = document.getElementById('wg-contacts-list');
            const addContactBtn = document.getElementById('wg-add-contact');
            const search = document.getElementById('wg-q');
            const rows = [...document.querySelectorAll('#wg-table tbody tr[data-group]')];
            const openMode = @json($openModal);
            const focusName = @json($focus);
            const oldContacts = @json($oldContacts);

            if (!modalEl || !form || !contactsList || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            let contactIndex = 0;

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function syncEmptyState() {
                const hasRows = contactsList.querySelectorAll('.wg-contact-row').length > 0;
                let empty = contactsList.querySelector('.wg-contacts-empty');
                if (hasRows) {
                    empty?.remove();
                    return;
                }
                if (!empty) {
                    empty = document.createElement('div');
                    empty.className = 'wg-contacts-empty';
                    empty.textContent = labels.empty;
                    contactsList.appendChild(empty);
                }
            }

            function addContactRow(contact = {}) {
                contactsList.querySelector('.wg-contacts-empty')?.remove();
                const index = contactIndex++;
                const row = document.createElement('div');
                row.className = 'wg-contact-row';
                row.dataset.contactRow = '1';
                row.innerHTML = `
                    <div class="wg-field">
                        <label for="wg-contact-name-${index}">${escapeHtml(labels.nameOptional)}</label>
                        <input
                            type="text"
                            class="form-control"
                            id="wg-contact-name-${index}"
                            name="contacts[${index}][name]"
                            value="${escapeHtml(contact.name || '')}"
                            maxlength="120"
                            placeholder="${escapeHtml(labels.nameOptional)}"
                            autocomplete="off"
                        >
                    </div>
                    <div class="wg-field">
                        <label for="wg-contact-phone-${index}">${escapeHtml(labels.phone)}</label>
                        <input
                            type="text"
                            class="form-control"
                            id="wg-contact-phone-${index}"
                            name="contacts[${index}][phone]"
                            value="${escapeHtml(contact.phone || '')}"
                            required
                            maxlength="32"
                            inputmode="tel"
                            placeholder="7006321479"
                            autocomplete="off"
                        >
                    </div>
                    <button type="button" class="btn btn-outline-danger wg-remove-contact">${escapeHtml(labels.remove)}</button>
                `;
                row.querySelector('.wg-remove-contact')?.addEventListener('click', () => {
                    row.remove();
                    syncEmptyState();
                });
                contactsList.appendChild(row);
                return row;
            }

            function setContacts(contacts) {
                contactsList.innerHTML = '';
                contactIndex = 0;
                const list = Array.isArray(contacts) ? contacts : [];
                if (list.length === 0) {
                    addContactRow();
                } else {
                    list.forEach(contact => addContactRow(contact || {}));
                }
                syncEmptyState();
            }

            function openCreate(seedContacts = null) {
                form.action = storeUrl;
                titleEl.textContent = labels.addTitle;
                originalInput.value = '';
                nameInput.value = '';
                setContacts(seedContacts);
                modal.show();
                setTimeout(() => nameInput.focus(), 200);
            }

            function openEdit(groupName, contacts) {
                form.action = updateUrl;
                titleEl.textContent = labels.editTitle;
                originalInput.value = groupName || '';
                nameInput.value = groupName || '';
                setContacts(contacts);
                modal.show();
                setTimeout(() => nameInput.focus(), 200);
            }

            document.getElementById('wg-add-btn')?.addEventListener('click', () => openCreate());
            addContactBtn?.addEventListener('click', () => {
                const row = addContactRow();
                row.querySelector('input[name$="[phone]"]')?.focus();
            });

            form.addEventListener('submit', (event) => {
                const phones = [...contactsList.querySelectorAll('input[name$="[phone]"]')]
                    .map(input => (input.value || '').trim())
                    .filter(Boolean);
                if (phones.length === 0) {
                    event.preventDefault();
                    if (contactsList.querySelectorAll('.wg-contact-row').length === 0) {
                        addContactRow();
                    }
                    alert(labels.needPhone);
                    contactsList.querySelector('input[name$="[phone]"]')?.focus();
                }
            });

            document.querySelectorAll('.wg-edit-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('tr');
                    let contacts = [];
                    try {
                        contacts = JSON.parse(row?.dataset.contacts || '[]');
                    } catch (e) {
                        contacts = [];
                    }
                    openEdit(row?.dataset.group || btn.dataset.group || '', contacts);
                });
            });

            search?.addEventListener('input', () => {
                const term = (search.value || '').trim().toLowerCase();
                rows.forEach(row => {
                    const hay = row.dataset.search || '';
                    row.style.display = !term || hay.includes(term) ? '' : 'none';
                });
            });

            if (openMode === 'create') {
                form.action = storeUrl;
                titleEl.textContent = labels.addTitle;
                setContacts(oldContacts);
                modal.show();
            } else if (openMode === 'edit') {
                form.action = updateUrl;
                titleEl.textContent = labels.editTitle;
                if (!originalInput.value && focusName) {
                    originalInput.value = focusName;
                }
                if (oldContacts) {
                    setContacts(oldContacts);
                } else if (focusName) {
                    const focusRow = document.querySelector(`#wg-table tbody tr[data-group="${CSS.escape(focusName)}"]`);
                    let contacts = [];
                    try {
                        contacts = JSON.parse(focusRow?.dataset.contacts || '[]');
                    } catch (e) {
                        contacts = [];
                    }
                    setContacts(contacts);
                } else {
                    setContacts([]);
                }
                modal.show();
            }

            const focusRow = document.querySelector('#wg-table tbody tr.is-focus');
            if (focusRow) {
                focusRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })();
    </script>
@endpush

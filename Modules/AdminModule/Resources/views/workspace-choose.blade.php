<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('adminmodule::layouts.partials._document-head', ['pageTitle' => 'Choose workspace'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; }
        body {
            min-height: 100%;
            display: flex;
            flex-direction: column;
            font-family: Outfit, system-ui, sans-serif;
            color: #1c1e33;
            background:
                radial-gradient(900px 420px at 12% -10%, rgba(37, 39, 77, .10), transparent 60%),
                radial-gradient(800px 420px at 88% -8%, rgba(15, 118, 110, .10), transparent 58%),
                radial-gradient(700px 380px at 50% 110%, rgba(194, 65, 12, .08), transparent 55%),
                #f3f4f8;
        }
        a { color: inherit; text-decoration: none; }
        .gate-header {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            min-height: 78px;
            padding: 12px 22px;
            background: #1a1c38;
            color: #fff;
        }
        .gate-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; letter-spacing: -.02em; }
        .gate-mark {
            width: 36px; height: 36px; border-radius: 10px; background: #fff; color: #1a1c38;
            display: grid; place-items: center; font-size: 12px; font-weight: 800;
        }
        .gate-brand small { display: block; margin-top: 1px; font-weight: 500; color: rgba(255,255,255,.62); font-size: 12px; }
        .gate-profile { position: relative; flex: 0 0 auto; }
        .gate-profile-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 40px;
            max-width: 220px;
            padding: 4px 12px 4px 4px;
            border: 0;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            white-space: nowrap;
        }
        .gate-profile-btn:hover,
        .gate-profile.is-open .gate-profile-btn { background: rgba(255, 255, 255, 0.2); }
        .gate-profile-avatar {
            width: 32px; height: 32px; border-radius: 50%; overflow: hidden; flex: 0 0 auto;
            border: 1.5px solid rgba(255,255,255,.28); background: rgba(255,255,255,.12);
        }
        .gate-profile-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .gate-profile-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
        .gate-profile-caret { width: 16px; height: 16px; opacity: .85; flex: 0 0 auto; }
        .gate-profile-menu {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 20;
            min-width: 240px;
            padding: 8px 0;
            background: #fff;
            color: #1c1e33;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(37, 39, 77, .18);
        }
        .gate-profile.is-open .gate-profile-menu { display: block; }
        .gate-profile-card { display: flex; align-items: center; gap: 12px; padding: 8px 16px 12px; }
        .gate-profile-card img {
            width: 50px; height: 50px; border-radius: 50%; object-fit: cover; flex: 0 0 auto; background: #eef0f6;
        }
        .gate-profile-card strong { display: block; font-size: 15px; line-height: 1.2; }
        .gate-profile-card span {
            display: block; margin-top: 2px; color: #6b7280; font-size: 13px;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 150px;
        }
        .gate-profile-signout {
            display: block; width: 100%; padding: 10px 16px; border: 0; border-top: 1px solid #f1f0ec;
            background: transparent; color: #1c1e33; font: inherit; font-size: 14px; font-weight: 600;
            text-align: left; cursor: pointer;
        }
        .gate-profile-signout:hover { background: #f6f7fb; }
        .workspace-choose {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 28px 20px 24px;
        }
        .workspace-choose-wrap {
            width: min(1480px, 100%);
            margin: 0 auto;
        }
        .workspace-welcome {
            margin: 0 0 18px;
            text-align: left;
        }
        .workspace-welcome h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.1;
            letter-spacing: -.04em;
            font-weight: 800;
        }
        .workspace-welcome p {
            margin: 6px 0 0;
            color: #6b7280;
            font-size: 16px;
        }
        .workspace-choose-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 16px;
            align-items: stretch;
        }
        .workspace-card {
            --accent: #43466e;
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            padding: 18px 16px 16px;
            background: #fff;
            border: 1px solid #e6e4e1;
            border-radius: 18px;
            box-shadow: 0 18px 40px -32px rgba(26, 28, 56, .55);
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }
        .workspace-card:hover,
        .workspace-card:focus-visible {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: 0 22px 40px -26px color-mix(in srgb, var(--accent) 55%, transparent);
            outline: none;
        }
        .workspace-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            object-fit: cover;
            box-shadow: 0 12px 24px -16px color-mix(in srgb, var(--accent) 80%, #000);
        }
        .workspace-card h2 {
            margin: 12px 0 0;
            font-size: 20px;
            line-height: 1.15;
            letter-spacing: -.03em;
            font-weight: 800;
        }
        .workspace-card p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.3;
        }
        .workspace-card ul {
            list-style: none;
            margin: 10px 0 12px;
            padding: 0;
        }
        .workspace-card li {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            padding: 8px 0;
            border-top: 1px solid #f1f0ec;
            font-size: 13px;
            line-height: 1.3;
            font-weight: 600;
        }
        .workspace-card li::before {
            content: "";
            width: 6px;
            height: 6px;
            margin-top: 3px;
            border-radius: 99px;
            background: var(--accent);
            flex: 0 0 auto;
        }
        .workspace-card .go {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 40px;
            padding: 0 12px;
            border-radius: 10px;
            background: var(--accent);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }
        .workspace-card .go span { font-size: 14px; line-height: 1; }
        .workspace-card.ops { --accent: #25274d; }
        .workspace-card.hr { --accent: #c2410c; }
        .workspace-card.train { --accent: #0f766e; }
        .workspace-card.mkt { --accent: #6d28d9; }
        .workspace-card.set { --accent: #1d4ed8; }
        .workspace-card.acc { --accent: #15803d; }
        .workspace-card-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 14px -8px rgba(220, 38, 38, .9);
        }
        @media (max-width: 720px) {
            .workspace-choose-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .gate-header { align-items: center; }
            .gate-profile-name { display: none; }
            .gate-profile-btn { max-width: none; padding-right: 8px; }
        }
    </style>
</head>
<body>
    <header class="gate-header">
        <div class="gate-brand">
            <div class="gate-mark">PK</div>
            <div>Panun Kaergar<small>Signed in</small></div>
        </div>
        <div class="gate-profile">
            <button type="button"
                    class="gate-profile-btn"
                    aria-expanded="false"
                    aria-haspopup="true"
                    title="{{ $profileName }}">
                <span class="gate-profile-avatar">
                    <img src="{{ $profileImage }}"
                         alt="{{ translate('profile_image') }}"
                         width="32"
                         height="32"
                         onerror="this.onerror=null;this.src='{{ $profileFallback }}'">
                </span>
                <span class="gate-profile-name">{{ \Illuminate\Support\Str::limit($profileName, 20) }}</span>
                <svg class="gate-profile-caret" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div class="gate-profile-menu" role="menu">
                <div class="gate-profile-card">
                    <img src="{{ $profileImage }}"
                         alt=""
                         width="50"
                         height="50"
                         onerror="this.onerror=null;this.src='{{ $profileFallback }}'">
                    <div>
                        <strong>{{ \Illuminate\Support\Str::limit($signedInUser->first_name ?: $profileName, 20) }}</strong>
                        <span>{{ \Illuminate\Support\Str::limit($signedInUser->email, 20) }}</span>
                    </div>
                </div>
                <button type="button" class="gate-profile-signout" role="menuitem">{{ translate('Sign_Out') }}</button>
            </div>
        </div>
    </header>
    <main class="workspace-choose">
        <div class="workspace-choose-wrap">
            <div class="workspace-welcome">
                <h1>Welcome, {{ $signedInUser->first_name ?: $profileName }}</h1>
                <p>Select the space you want to work in.</p>
            </div>
            <div class="workspace-choose-grid">
            @php
                $workspaceBadge = function (string $key) use ($workspaceNotificationCounts) {
                    $count = (int) ($workspaceNotificationCounts[$key] ?? 0);
                    if ($count <= 0) {
                        return '';
                    }
                    $label = $count > 99 ? '99+' : (string) $count;

                    return '<span class="workspace-card-badge" aria-label="'.$count.' unread notifications">'.$label.'</span>';
                };
            @endphp
            @if(\App\Support\AdminWorkspace::userCanEnter('operations'))
            <a class="workspace-card ops" href="{{ route('admin.workspace.enter', 'operations') }}" target="_blank" rel="noopener noreferrer">
                {!! $workspaceBadge('operations') !!}
                <img class="workspace-icon" src="{{ asset('assets/admin-module/img/workspace/operations.jpg') }}" alt="" width="72" height="72">
                <h2>Operations</h2>
                <p>Today’s jobs.</p>
                <ul>
                    <li>Leads and Hunting Board</li>
                    <li>Bookings and Task Board</li>
                    <li>Customers and Providers</li>
                    <li>Messages and Reports</li>
                </ul>
                <span class="go">Open operations <span aria-hidden="true">→</span></span>
            </a>
            @endif
            <a class="workspace-card hr" href="{{ route('admin.workspace.enter', 'hr') }}" target="_blank" rel="noopener noreferrer">
                {!! $workspaceBadge('hr') !!}
                <img class="workspace-icon" src="{{ asset('assets/admin-module/img/workspace/hr.jpg') }}" alt="" width="72" height="72">
                <h2>HR Management</h2>
                <p>People, leave, and your own file.</p>
                <ul>
                    <li>Holidays, leaves, and people</li>
                    <li>Departments and configuration</li>
                    <li>Workspace</li>
                </ul>
                <span class="go">Open HR <span aria-hidden="true">→</span></span>
            </a>
            <a class="workspace-card train" href="{{ route('admin.workspace.enter', 'training') }}" target="_blank" rel="noopener noreferrer">
                {!! $workspaceBadge('training') !!}
                <img class="workspace-icon" src="{{ asset('assets/admin-module/img/workspace/training.jpg') }}" alt="" width="72" height="72">
                <h2>Training</h2>
                <p>Process guides.</p>
                <ul>
                    <li>Panun Kaergar</li>
                    <li>Lead Qualification Flow</li>
                    <li>Booking Follow-up Flow</li>
                </ul>
                <span class="go">Open training <span aria-hidden="true">→</span></span>
            </a>
            @if(\App\Support\AdminWorkspace::userCanEnter('marketing'))
            <a class="workspace-card mkt" href="{{ route('admin.workspace.enter', 'marketing') }}" target="_blank" rel="noopener noreferrer">
                {!! $workspaceBadge('marketing') !!}
                <img class="workspace-icon" src="{{ asset('assets/admin-module/img/workspace/marketing.svg') }}" alt="" width="72" height="72">
                <h2>Marketing</h2>
                <p>Campaigns and reach.</p>
                <ul>
                    <li>Provider ads</li>
                    <li>App campaigns</li>
                    <li>WhatsApp marketing</li>
                </ul>
                <span class="go">Open marketing <span aria-hidden="true">→</span></span>
            </a>
            @endif
            @if(\App\Support\AdminWorkspace::userCanEnter('settings'))
            <a class="workspace-card set" href="{{ route('admin.workspace.enter', 'settings') }}" target="_blank" rel="noopener noreferrer">
                {!! $workspaceBadge('settings') !!}
                <img class="workspace-icon" src="{{ asset('assets/admin-module/img/workspace/settings.svg') }}" alt="" width="72" height="72">
                <h2>Settings</h2>
                <p>How the company is set up.</p>
                <ul>
                    <li>Business and login</li>
                    <li>App, payments, and integrations</li>
                    <li>Backup and logs</li>
                </ul>
                <span class="go">Open settings <span aria-hidden="true">→</span></span>
            </a>
            @endif
            @if(\App\Support\AdminWorkspace::userCanEnter('accounts'))
            <a class="workspace-card acc" href="{{ route('admin.workspace.enter', 'accounts') }}" target="_blank" rel="noopener noreferrer">
                {!! $workspaceBadge('accounts') !!}
                <img class="workspace-icon" src="{{ asset('assets/admin-module/img/workspace/accounts.svg') }}" alt="" width="72" height="72">
                <h2>Accounts</h2>
                <p>Company money, attendance, and pay.</p>
                <ul>
                    <li>Attendance and payroll</li>
                    <li>Transactions and ledger</li>
                    <li>Withdrawals and balances</li>
                </ul>
                <span class="go">Open accounts <span aria-hidden="true">→</span></span>
            </a>
            @endif
            </div>
        </div>
    </main>
    <script>
        (function () {
            var wrap = document.querySelector('.gate-profile');
            var button = wrap.querySelector('.gate-profile-btn');
            var signOut = wrap.querySelector('.gate-profile-signout');

            function setOpen(open) {
                wrap.classList.toggle('is-open', open);
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            button.addEventListener('click', function () {
                setOpen(!wrap.classList.contains('is-open'));
            });

            document.addEventListener('click', function (event) {
                if (!wrap.contains(event.target)) {
                    setOpen(false);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setOpen(false);
                }
            });

            signOut.addEventListener('click', function () {
                if (window.confirm(@json(translate('are_you_sure').'?'))) {
                    window.location.href = @json(route('admin.auth.logout'));
                }
            });
        })();
    </script>
</body>
</html>

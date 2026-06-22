@php
    $avg = round((float) ($avgRating ?? 0), 1);
    $count = (int) ($ratingCount ?? 0);
    $role = (string) ($roleLabel ?? '');
    $metaLabel = (string) ($ratingsMetaLabel ?? translate('ratings'));
    $compact = (bool) ($compact ?? false);
@endphp

@once
    @push('css_or_js')
        <style>
            .profile-role-pill {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: .28rem .85rem;
                font-size: .78rem;
                font-weight: 700;
                letter-spacing: .02em;
                text-transform: uppercase;
                background: rgba(4, 97, 165, .1);
                color: #0461a5;
                border: 1px solid rgba(4, 97, 165, .18);
                line-height: 1.2;
            }

            .profile-role-pill--provider {
                background: rgba(54, 179, 126, .12);
                color: #138a57;
                border-color: rgba(54, 179, 126, .28);
            }

            .profile-rating-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .35rem;
            }

            .profile-rating-pill {
                display: inline-flex;
                align-items: center;
                flex-wrap: wrap;
                gap: .45rem .6rem;
                border-radius: 999px;
                padding: .45rem .9rem;
                background: rgba(117, 133, 144, .1);
                border: 1px solid rgba(117, 133, 144, .14);
            }

            .profile-rating-stars {
                display: inline-flex;
                align-items: center;
                gap: .1rem;
                line-height: 1;
            }

            .profile-rating-stars .material-icons,
            .profile-rating-stars .material-symbols-outlined {
                font-size: 1.05rem;
                color: #f5a623;
            }

            .profile-rating-score {
                font-size: .92rem;
                font-weight: 600;
                color: #4f5d6a;
                white-space: nowrap;
            }

            .profile-rating-count {
                font-size: .86rem;
                font-weight: 600;
                color: #758590;
                white-space: nowrap;
            }

            .profile-rating-meta {
                font-size: .82rem;
                font-weight: 500;
                color: #758590;
                padding-left: .15rem;
            }
        </style>
    @endpush
@endonce

@if($compact)
    <div class="profile-rating-pill">
        <div class="profile-rating-stars" aria-hidden="true">
            @for($i = 1; $i <= 5; $i++)
                @if($avg >= $i)
                    <span class="material-icons">star</span>
                @elseif($avg >= ($i - 0.5))
                    <span class="material-icons">star_half</span>
                @else
                    <span class="material-symbols-outlined">grade</span>
                @endif
            @endfor
        </div>
        <span class="profile-rating-score">{{ $avg }} {{ translate('out_of') }} 5</span>
        <span class="profile-rating-count">({{ $count }})</span>
    </div>
@else
<div class="profile-rating-stack">
    <span class="profile-role-pill {{ ($roleType ?? '') === 'provider' ? 'profile-role-pill--provider' : '' }}">
        {{ $role }}
    </span>

    <div class="profile-rating-pill">
        <div class="profile-rating-stars" aria-hidden="true">
            @for($i = 1; $i <= 5; $i++)
                @if($avg >= $i)
                    <span class="material-icons">star</span>
                @elseif($avg >= ($i - 0.5))
                    <span class="material-icons">star_half</span>
                @else
                    <span class="material-symbols-outlined">grade</span>
                @endif
            @endfor
        </div>
        <span class="profile-rating-score">{{ $avg }} {{ translate('out_of') }} 5</span>
        <span class="profile-rating-count">({{ $count }})</span>
    </div>

    <div class="profile-rating-meta">{{ $count }} {{ $metaLabel }}</div>
</div>
@endif

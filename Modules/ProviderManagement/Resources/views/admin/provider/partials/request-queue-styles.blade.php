<style>
    .onboarding-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: .75rem 1rem;
        margin-bottom: .75rem;
        overflow-x: auto;
    }
    .onboarding-toolbar .nav--tabs {
        flex-wrap: nowrap;
        border: 0;
    }
    .onboarding-toolbar .nav--tabs .nav-link {
        padding: .35rem .65rem;
        white-space: nowrap;
    }
    .onboarding-toolbar__meta,
    .onboarding-toolbar__count {
        white-space: nowrap;
    }
    .onboarding-card .card-body {
        padding: .65rem .75rem;
    }
    .onboarding-table {
        font-size: .8125rem;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    .onboarding-table > :not(caption) > * > * {
        padding: .3rem .45rem;
        vertical-align: middle;
    }
    .onboarding-card .onboarding-table > thead > tr > th {
        font-size: .9rem;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: .01em;
        white-space: nowrap;
        color: #1d2939;
        background-color: #e8edf5;
        background-clip: padding-box;
        padding: .6rem .55rem .55rem;
        border-bottom: 8px solid #fff;
        box-shadow: inset 0 -2px 0 #c5cedd;
    }
    .onboarding-table tbody td {
        white-space: nowrap;
    }
    .onboarding-table tbody tr:nth-child(even) > td {
        background: #f3f5fb;
    }
    .onboarding-table tbody tr:hover > td {
        background: #e8edf8;
    }
    .onboarding-provider {
        display: flex;
        align-items: center;
        gap: .4rem;
        min-width: 0;
        color: inherit;
        text-decoration: none;
    }
    .onboarding-provider__avatar {
        width: 1.75rem;
        height: 1.75rem;
        border-radius: .3rem;
        overflow: hidden;
        flex: 0 0 auto;
        background: #e9ecef;
    }
    .onboarding-provider__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .onboarding-provider__name {
        margin: 0;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: .8125rem;
        font-weight: 600;
        line-height: 1.2;
    }
    .onboarding-clip {
        display: block;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .onboarding-media-thumb {
        width: 2.25rem;
        height: 2.25rem;
        object-fit: cover;
        border-radius: .3rem;
        display: block;
    }
    .onboarding-table .table-actions {
        flex-wrap: nowrap;
        gap: .3rem;
    }
    .onboarding-table .table-actions .btn {
        white-space: nowrap;
        padding: .12rem .4rem;
        font-size: .7rem;
        line-height: 1.3;
    }
</style>

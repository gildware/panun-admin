{{-- Brand-tinted surface for social inbox admin pages (WhatsApp / Instagram / Facebook). --}}
<style>
    .social-inbox-page--whatsapp {
        background:
            linear-gradient(165deg, rgba(37, 211, 102, 0.22) 0%, rgba(37, 211, 102, 0.07) 36%, transparent 58%),
            linear-gradient(180deg, rgba(18, 140, 126, 0.10) 0%, transparent 420px);
        border-top: 3px solid rgba(37, 211, 102, 0.65);
    }
    .social-inbox-page--facebook {
        background:
            linear-gradient(165deg, rgba(8, 102, 255, 0.20) 0%, rgba(0, 132, 255, 0.08) 38%, transparent 60%),
            linear-gradient(180deg, rgba(24, 119, 242, 0.09) 0%, transparent 420px);
        border-top: 3px solid rgba(0, 132, 255, 0.65);
    }
    .social-inbox-page--instagram {
        background: linear-gradient(
            125deg,
            rgba(245, 133, 41, 0.20) 0%,
            rgba(221, 42, 123, 0.16) 38%,
            rgba(129, 52, 175, 0.14) 72%,
            transparent 92%
        );
        border-top: 3px solid rgba(228, 64, 95, 0.55);
    }
    .social-inbox-page .wa-inbox-toolbar-card {
        padding: 0.4rem 0.65rem !important;
        margin-bottom: 0.75rem !important;
    }
    .social-inbox-page .wa-inbox-toolbar-row {
        gap: 0.45rem;
    }
    .social-inbox-page .wa-inbox-toolbar-end {
        gap: 0.45rem;
        min-width: 0;
        max-width: 100%;
    }
    .social-inbox-page .wa-inbox-tabs-compact.nav--tabs,
    .social-inbox-page .wa-inbox-tabs-compact.nav-pills {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .social-inbox-page .wa-inbox-tabs-compact.nav--tabs::-webkit-scrollbar,
    .social-inbox-page .wa-inbox-tabs-compact.nav-pills::-webkit-scrollbar {
        display: none;
    }
    .social-inbox-page .wa-inbox-tabs-compact .nav-link {
        padding: 0.28rem 0.5rem;
        font-size: 0.8125rem;
        white-space: nowrap;
        line-height: 1.35;
    }
    .social-inbox-page .wa-inbox-toolbar-search {
        flex: 1 1 auto;
        width: min(320px, 42vw);
        max-width: 320px;
        min-width: 9rem;
    }
    .social-inbox-page .wa-inbox-toolbar-search .wa-global-search-card {
        margin: 0;
        padding: 0;
        border: 0;
        box-shadow: none;
        background: transparent;
    }
    .social-inbox-page .wa-inbox-toolbar-search .form-control {
        font-size: 0.8125rem;
        padding: 0.28rem 0.55rem;
        min-height: 1.95rem;
    }
    .social-inbox-page .wa-inbox-toolbar-actions .btn {
        padding: 0.25rem 0.45rem;
        font-size: 0.8125rem;
        line-height: 1.35;
    }
    .social-inbox-page .wa-inbox-toolbar-actions .material-icons {
        font-size: 17px !important;
    }
    @media (max-width: 767.98px) {
        .social-inbox-page .wa-inbox-toolbar-end {
            width: 100%;
            justify-content: flex-end;
        }
    }
    @media (min-width: 768px) {
        .social-inbox-page .wa-inbox-toolbar-row {
            flex-wrap: nowrap;
        }
        .social-inbox-page .wa-inbox-toolbar-end {
            flex-wrap: nowrap;
            justify-content: flex-end;
        }
    }
</style>

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
</style>

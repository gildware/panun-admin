@include('leadmanagement::admin.leads.partials._comment-attachments-styles')
<style>
    .lead-comments-list-wrap {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        max-height: 420px;
        overflow: auto;
    }
    .lead-comments-list {
        display: flex;
        flex-direction: column;
        gap: .85rem;
    }
    .lead-comments-empty {
        color: #94a3b8;
        font-size: .875rem;
        text-align: center;
        padding: 2rem 1rem;
    }
    .lead-comment-item {
        display: grid;
        grid-template-columns: 36px 1fr;
        gap: .75rem;
        align-items: start;
    }
    .lead-comment-item.is-pinned .lead-comment-card {
        border-color: #fcd34d;
        background: #fffbeb;
    }
    .lead-comment-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: .72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .lead-comment-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: .75rem .9rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .lead-comment-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .5rem;
        margin-bottom: .4rem;
    }
    .lead-comment-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .35rem .5rem;
        font-size: .78rem;
        color: #64748b;
    }
    .lead-comment-meta strong {
        color: #0f172a;
        font-size: .84rem;
        font-weight: 650;
    }
    .lead-comment-time {
        color: #94a3b8;
    }
    .lead-comment-pin-badge {
        display: inline-flex;
        align-items: center;
        gap: .15rem;
        color: #b45309;
        font-weight: 600;
    }
    .lead-comment-pin-badge .material-icons {
        font-size: 14px;
    }
    .lead-comment-actions {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
    }
    .lead-comment-actions .material-icons {
        font-size: 18px;
    }
    .lead-comment-actions .btn-link {
        line-height: 1;
        min-width: auto;
        color: #64748b;
    }
    .lead-comment-actions .btn-link:hover {
        color: #0f172a;
    }
    .lead-comment-body {
        font-size: .875rem;
        line-height: 1.5;
        color: #334155;
        word-break: break-word;
    }
    .lead-comment-body .staff-chat-entity-link {
        font-weight: 600;
    }
    .lead-comment-compose {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: .75rem;
    }
    .lead-comment-compose__input {
        border: none;
        resize: vertical;
        min-height: 56px;
        box-shadow: none !important;
    }
    .lead-comment-compose__input:focus {
        outline: none;
    }
    .lead-comment-compose__footer {
        border-top: 1px solid #f1f5f9;
    }
    .booking-detail-v2 .lead-comment-compose .staff-chat-entity-picker,
    .booking-detail-v2 .comment-compose .staff-chat-entity-picker {
        position: absolute;
        left: .75rem;
        right: .75rem;
        bottom: 100%;
        margin-bottom: .375rem;
        z-index: 30;
        max-height: min(280px, 50vh);
        overflow: auto;
    }
    .booking-detail-v2 .lead-comment-compose .staff-tag-btn,
    .booking-detail-v2 .comment-compose .tag-btn.staff-tag-trigger {
        font-size: .6875rem;
        padding: .2rem .5rem;
        line-height: 1.3;
    }
    .booking-detail-v2 .comment-compose__footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
        padding-top: .5rem;
        border-top: 1px solid #f1f5f9;
    }
    .booking-detail-v2 .comment-compose .staff-chat-compose-editor {
        width: 100%;
    }
    .booking-detail-v2 .lead-comment-compose .staff-chat-compose-editor,
    .booking-detail-v2 .comment-compose .staff-chat-compose-editor {
        width: 100%;
        display: block;
    }
    .booking-detail-v2 .lead-comment-compose .staff-chat-compose-highlight,
    .booking-detail-v2 .comment-compose .staff-chat-compose-highlight {
        border: none;
        background: #fff;
        border-radius: 0;
        box-shadow: none;
    }
    .booking-detail-v2 .lead-comment-compose .staff-chat-compose-input,
    .booking-detail-v2 .comment-compose .staff-chat-compose-input {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        min-height: 56px;
        width: 100%;
        resize: vertical;
        font-size: .875rem;
        line-height: 1.5;
        padding: .25rem .5rem;
    }
    .booking-detail-v2 .lead-comment-compose .staff-chat-compose-input:focus,
    .booking-detail-v2 .comment-compose .staff-chat-compose-input:focus {
        outline: none;
        box-shadow: none !important;
    }
    .booking-detail-v2 .lead-comment-compose .staff-chat-compose-input::placeholder,
    .booking-detail-v2 .comment-compose .staff-chat-compose-input::placeholder {
        -webkit-text-fill-color: #94a3b8;
        color: #94a3b8;
    }
</style>

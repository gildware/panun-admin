<style>
    .comment-attachments-toolbar {
        margin-top: .5rem;
        flex-wrap: wrap;
    }
    .comment-attach-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        color: #64748b;
        cursor: pointer;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .comment-attach-btn:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .comment-attach-btn .material-icons {
        font-size: 18px;
    }
    .comment-attachments-hint {
        margin-left: .15rem;
    }
    .comment-pending-file {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .55rem;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: .75rem;
        max-width: 100%;
    }
    .comment-pending-file-remove {
        border: none;
        background: transparent;
        color: inherit;
        line-height: 1;
        padding: 0;
        cursor: pointer;
        font-size: 1rem;
    }
    .comment-attachments-list {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        margin-top: .65rem;
    }
    .comment-attachment {
        display: block;
        max-width: 100%;
    }
    .comment-attachment--image img {
        display: block;
        max-width: 220px;
        max-height: 160px;
        min-width: 48px;
        min-height: 48px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        object-fit: cover;
        background: #f8fafc;
    }
    .comment-attachment__name {
        display: block;
        margin-top: .35rem;
        font-size: .75rem;
        color: #475569;
        word-break: break-all;
    }
    .comment-attachment--video video,
    .comment-attachment--audio audio {
        display: block;
        width: min(100%, 320px);
        max-height: 180px;
        border-radius: 10px;
    }
    .comment-attachment--file {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .45rem .65rem;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        color: #334155;
        text-decoration: none;
        font-size: .8125rem;
    }
    .comment-attachment--file:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .comment-attachment__download {
        display: inline-block;
        margin-top: .25rem;
        color: #64748b;
        text-decoration: none;
    }
    .comment-attachment__download:hover {
        color: #0f172a;
        text-decoration: underline;
    }
    form.lead-comment-compose.is-submitting,
    form.comment-compose.is-submitting {
        opacity: 0.85;
    }
    form.lead-comment-compose.is-submitting [type="submit"],
    form.comment-compose.is-submitting [type="submit"] {
        cursor: wait;
    }
    form.lead-comment-compose.is-submitting [type="submit"] .spinner-border,
    form.comment-compose.is-submitting [type="submit"] .spinner-border {
        vertical-align: -0.125em;
    }
</style>

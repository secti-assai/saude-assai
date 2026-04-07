<style>
    :root {
        --public-ink: #0b2232;
        --public-muted: #4b6578;
        --public-soft: #f3f8fb;
        --public-card: rgba(255, 255, 255, 0.93);
        --public-border: rgba(11, 34, 50, 0.14);
        --public-shadow: 0 28px 56px -36px rgba(8, 32, 44, 0.56);
        --public-focus: rgba(11, 120, 135, 0.26);
        --public-accent: #0b7887;
        --public-danger: #c0362c;
    }

    body.public-page {
        margin: 0;
        min-height: 100vh;
        font-family: "Segoe UI", "Trebuchet MS", sans-serif;
        color: var(--public-ink);
        background:
            radial-gradient(circle at 84% 18%, rgba(13, 148, 136, 0.22) 0%, transparent 42%),
            radial-gradient(circle at 8% 90%, rgba(59, 130, 246, 0.14) 0%, transparent 48%),
            linear-gradient(150deg, #f0f8ff 0%, #effcf7 48%, #f9fafb 100%);
        padding: 1.75rem 1rem;
    }

    .public-shell {
        width: min(860px, 100%);
        margin: 0 auto;
    }

    .public-card {
        background: var(--public-card);
        border: 1px solid var(--public-border);
        border-radius: 24px;
        box-shadow: var(--public-shadow);
        overflow: hidden;
    }

    .public-header {
        padding: 1.5rem 1.5rem 1.25rem;
        border-bottom: 1px solid rgba(11, 34, 50, 0.09);
        background: linear-gradient(120deg, rgba(255, 255, 255, 0.92), rgba(240, 252, 255, 0.72));
    }

    .public-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        background: rgba(11, 120, 135, 0.12);
        color: #0b5560;
        border: 1px solid rgba(11, 120, 135, 0.2);
        border-radius: 999px;
        padding: 0.28rem 0.7rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .public-title {
        margin: 0.9rem 0 0.35rem;
        font-size: clamp(1.35rem, 2vw, 1.95rem);
        line-height: 1.2;
        font-weight: 800;
        color: #072334;
    }

    .public-subtitle {
        margin: 0;
        color: var(--public-muted);
        font-size: 0.97rem;
    }

    .public-content {
        padding: 1.4rem 1.5rem 1.6rem;
        display: grid;
        gap: 1rem;
    }

    .public-panel {
        border: 1px solid rgba(11, 34, 50, 0.1);
        background: linear-gradient(150deg, rgba(255, 255, 255, 0.96), rgba(243, 248, 251, 0.92));
        border-radius: 16px;
        padding: 0.95rem 1rem;
    }

    .public-panel dl {
        margin: 0;
        display: grid;
        gap: 0.7rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .public-panel dt {
        margin: 0;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #5f7283;
        font-weight: 700;
    }

    .public-panel dd {
        margin: 0.16rem 0 0;
        color: #0e2f42;
        font-size: 0.95rem;
        font-weight: 600;
        word-break: break-word;
    }

    .public-grid {
        display: grid;
        gap: 0.9rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .public-field label {
        display: block;
        margin-bottom: 0.4rem;
        font-size: 0.86rem;
        font-weight: 700;
        color: #1c3d4f;
    }

    .public-field input,
    .public-field textarea,
    .public-field select {
        width: 100%;
        border-radius: 12px;
        border: 1px solid #b8c9d6;
        background: #ffffff;
        color: #062131;
        font-size: 0.95rem;
        padding: 0.66rem 0.78rem;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .public-field textarea {
        resize: vertical;
        min-height: 120px;
    }

    .public-field input:focus,
    .public-field textarea:focus,
    .public-field select:focus {
        outline: none;
        border-color: #0b7887;
        box-shadow: 0 0 0 4px var(--public-focus);
    }

    .public-hint {
        margin-top: 0.42rem;
        font-size: 0.78rem;
        color: #607487;
    }

    .public-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.7rem;
        padding-top: 0.3rem;
    }

    .public-btn {
        appearance: none;
        border: 0;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 700;
        padding: 0.7rem 1.2rem;
        cursor: pointer;
        transition: transform 0.12s ease, filter 0.12s ease;
    }

    .public-btn:hover {
        transform: translateY(-1px);
        filter: brightness(1.04);
    }

    .public-btn:active {
        transform: translateY(0);
    }

    .public-btn-primary {
        background: linear-gradient(135deg, #0b7887, #0a5f6c);
        color: #fff;
    }

    .public-btn-danger {
        background: linear-gradient(135deg, #c0362c, #9b241d);
        color: #fff;
    }

    .public-alert {
        border-radius: 14px;
        border: 1px solid transparent;
        padding: 0.8rem 0.95rem;
        font-size: 0.86rem;
    }

    .public-alert ul {
        margin: 0;
        padding-left: 1.1rem;
    }

    .public-alert-danger {
        border-color: rgba(192, 54, 44, 0.28);
        background: #fff2f1;
        color: #8c1e17;
    }

    .public-alert-success {
        border-color: rgba(12, 133, 84, 0.28);
        background: #ebfff4;
        color: #0f6a46;
    }

    .public-alert-info {
        border-color: rgba(20, 95, 160, 0.26);
        background: #eaf5ff;
        color: #134d82;
    }

    .public-rating {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.5rem;
    }

    .public-rating label {
        position: relative;
        cursor: pointer;
    }

    .public-rating input {
        position: absolute;
        opacity: 0;
    }

    .public-rating span {
        display: block;
        text-align: center;
        border: 1px solid #b8c9d6;
        border-radius: 12px;
        padding: 0.55rem 0.2rem;
        font-weight: 700;
        color: #27485b;
        background: #fff;
        transition: all 0.16s ease;
    }

    .public-rating input:checked + span {
        background: #0b7887;
        border-color: #0b7887;
        color: #fff;
        box-shadow: 0 8px 16px -12px rgba(11, 120, 135, 0.7);
    }

    .public-rating label:hover span {
        border-color: #0b7887;
    }

    @media (max-width: 760px) {
        .public-header,
        .public-content {
            padding: 1.1rem 1rem;
        }

        .public-panel dl,
        .public-grid {
            grid-template-columns: 1fr;
        }

        .public-actions {
            justify-content: stretch;
        }

        .public-btn {
            width: 100%;
        }
    }
</style>

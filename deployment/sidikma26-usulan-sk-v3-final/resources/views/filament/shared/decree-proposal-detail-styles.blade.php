<style>
    .decree-proposal-detail-modal {
        height: 100dvh;
        max-height: 100dvh;
        overflow: hidden;
        border: 0;
        border-radius: 0;
        background: #f4f8f6;
    }

    .decree-proposal-detail-modal .fi-modal-header {
        flex: 0 0 auto;
        border-bottom: 0;
        background: linear-gradient(115deg, #075b43 0%, #0b7052 72%, #17618c 140%);
        color: #fff;
    }

    .decree-proposal-detail-modal .fi-modal-header .fi-modal-heading,
    .decree-proposal-detail-modal .fi-modal-header .fi-modal-description,
    .decree-proposal-detail-modal .fi-modal-header .fi-icon {
        color: #fff;
    }

    .decree-proposal-detail-modal .fi-modal-header .fi-modal-heading {
        font-size: 1.375rem;
        line-height: 1.5;
    }

    .decree-proposal-detail-modal .fi-modal-header .fi-modal-description {
        color: rgba(255, 255, 255, .78);
    }

    .decree-proposal-detail-modal .fi-modal-content {
        min-height: 0;
        flex: 1 1 auto;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        background: #f4f8f6;
        padding: 1.5rem 1rem;
        scrollbar-color: #8abda8 transparent;
        scrollbar-width: thin;
    }

    .decree-proposal-detail-modal .fi-modal-content > * {
        box-sizing: border-box;
        width: 100%;
        max-width: 1200px;
        margin-inline: auto;
    }

    .decree-proposal-detail-modal .fi-modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 10;
        flex: 0 0 auto;
        border-top: 1px solid #cfe2d8;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 -8px 20px rgba(7, 91, 67, .08);
        backdrop-filter: blur(10px);
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section {
        overflow: hidden;
        border: 1px solid #cfe2d8;
        border-radius: .8rem;
        background: #fff;
        box-shadow: 0 2px 8px rgba(7, 91, 67, .05);
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child {
        background: #eef8f1;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section + .decree-proposal-detail-section {
        margin-top: 1rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header {
        border-bottom: 1px solid #cfe2d8;
        background: linear-gradient(90deg, #0b7052, #13815f);
        padding: .8rem 1rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header-heading {
        font-size: 1.25rem;
        font-weight: 650;
        line-height: 1.5;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header-heading,
    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header .fi-icon {
        color: #fff;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-content {
        background: #fbfefc;
        padding: .25rem 1rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-section-header {
        background: linear-gradient(90deg, #17618c, #287ca9);
    }

    .decree-proposal-detail-modal .fi-in-entry {
        grid-template-columns: minmax(280px, 320px) minmax(0, 1fr);
        gap: .5rem;
        border-bottom: 0;
        padding: .55rem 0;
    }

    .decree-proposal-detail-modal .fi-in-entry-label-col {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        border-inline-end: 0;
        padding-inline-end: 0;
    }

    .decree-proposal-detail-modal .fi-in-entry-label-col::after {
        content: ':';
        color: #315b4c;
        font-weight: 700;
    }

    .decree-proposal-detail-modal .fi-in-entry-content-col {
        min-width: 0;
    }

    .decree-proposal-detail-modal .fi-in-entry:nth-child(even) {
        background: rgba(232, 245, 238, .42);
    }

    .decree-proposal-detail-modal .fi-in-entry:last-child {
        border-bottom: 0;
    }

    .decree-proposal-detail-modal .fi-in-entry-label {
        color: #315b4c;
        font-size: 1.125rem;
        font-weight: 700;
        line-height: 1.55;
    }

    .decree-proposal-detail-modal .fi-in-entry-content {
        min-width: 0;
        color: #183d31;
        font-size: 1.125rem;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .decree-proposal-detail-modal .fi-in-entry-content .fi-btn {
        font-size: 1rem;
        line-height: 1.5;
    }

    .decree-proposal-detail-modal .fi-modal-footer .fi-btn {
        font-size: 1rem;
        line-height: 1.5;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry-label-col,
    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry-content-col {
        width: 100%;
        justify-content: center;
        text-align: center;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry-label-col::after {
        content: none;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry-label {
        font-size: 1.125rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:nth-child(2) .fi-in-entry-content {
        color: #075b43;
        font-size: 1.5rem;
        font-weight: 750;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:nth-child(3) .fi-in-entry-content {
        font-size: 1.125rem;
        font-weight: 650;
    }

    .decree-proposal-detail-modal .fi-fo-field-wrp-label,
    .decree-proposal-detail-modal .fi-fo-field-wrp-helper-text,
    .decree-proposal-detail-modal .fi-input,
    .decree-proposal-detail-modal .fi-select-input,
    .decree-proposal-detail-modal .fi-fo-textarea {
        font-size: 1rem;
    }

    @media (max-width: 640px) {
        .decree-proposal-detail-modal .fi-modal-content {
            padding: .75rem 1rem;
        }

        .decree-proposal-detail-modal .fi-in-entry {
            grid-template-columns: 1fr;
            gap: .25rem;
        }

        .decree-proposal-detail-modal .fi-in-entry-label-col::after {
            content: none;
        }

    }

    @media (prefers-reduced-motion: reduce) {
        .decree-proposal-detail-modal,
        .decree-proposal-detail-modal * {
            scroll-behavior: auto;
        }
    }
</style>

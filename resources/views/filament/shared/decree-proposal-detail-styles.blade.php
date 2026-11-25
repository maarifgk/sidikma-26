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
        padding: 1.25rem clamp(1rem, 3vw, 2.5rem);
        scrollbar-color: #8abda8 transparent;
        scrollbar-width: thin;
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

    .decree-proposal-detail-modal .decree-proposal-detail-section + .decree-proposal-detail-section {
        margin-top: 1rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header {
        border-bottom: 1px solid #cfe2d8;
        background: linear-gradient(90deg, #0b7052, #13815f);
        padding: .8rem 1rem;
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
        grid-template-columns: minmax(12rem, 15rem) minmax(0, 1fr);
        gap: 1rem;
        border-bottom: 1px solid #b9d5c7;
        padding: .7rem 0;
    }

    .decree-proposal-detail-modal .fi-in-entry-label-col {
        border-inline-end: 1px solid #cfe2d8;
        padding-inline-end: 1rem;
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
        font-size: .82rem;
        font-weight: 700;
    }

    .decree-proposal-detail-modal .fi-in-entry-content {
        min-width: 0;
        color: #183d31;
        font-size: .88rem;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    @media (max-width: 640px) {
        .decree-proposal-detail-modal .fi-modal-content {
            padding: .75rem;
        }

        .decree-proposal-detail-modal .fi-in-entry {
            grid-template-columns: 1fr;
            gap: .25rem;
        }

        .decree-proposal-detail-modal .fi-in-entry-label-col {
            border-inline-end: 0;
            padding-inline-end: 0;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .decree-proposal-detail-modal,
        .decree-proposal-detail-modal * {
            scroll-behavior: auto;
        }
    }
</style>

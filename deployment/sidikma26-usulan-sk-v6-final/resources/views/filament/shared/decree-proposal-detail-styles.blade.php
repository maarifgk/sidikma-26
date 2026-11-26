<style>
    .decree-proposal-detail-modal {
        height: 100dvh;
        max-height: 100dvh;
        overflow: hidden;
        border: 0;
        border-radius: 0;
        background: #f4f8f6;
        font-family: Cambria, "Times New Roman", Georgia, serif;
    }

    .decree-proposal-detail-modal,
    .decree-proposal-detail-modal * {
        box-sizing: border-box;
        font-family: inherit;
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
        font-size: clamp(1.35rem, 1.2rem + .45vw, 1.625rem);
        font-weight: 700;
        line-height: 1.4;
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
        padding: clamp(1rem, 2vw, 1.75rem) 0;
        scrollbar-color: #8abda8 transparent;
        scrollbar-width: thin;
    }

    .decree-proposal-detail-modal .fi-modal-content > * {
        box-sizing: border-box;
        width: 94%;
        max-width: 1600px;
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
        overflow-x: auto;
    }

    .decree-proposal-detail-modal .fi-modal-footer > * {
        max-width: 100%;
        flex-wrap: wrap;
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
        margin-top: 1.25rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header {
        border-bottom: 1px solid #cfe2d8;
        background: linear-gradient(90deg, #0b7052, #13815f);
        padding: .8rem 1rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header-heading {
        font-size: clamp(1.25rem, 1.1rem + .35vw, 1.5rem);
        font-weight: 700;
        line-height: 1.4;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header-heading,
    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header .fi-icon {
        color: #fff;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-content {
        display: block;
        height: auto;
        min-height: 0;
        background: #fbfefc;
        padding: 1.25rem clamp(1rem, 2vw, 1.5rem);
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-section-header {
        background: linear-gradient(90deg, #17618c, #287ca9);
    }

    .decree-proposal-detail-modal .fi-in-entry {
        display: flex;
        width: 100%;
        min-width: 0;
        height: auto;
        min-height: 0;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: .25rem;
        margin: 0;
        border-bottom: 0;
        padding: .7rem 0;
        text-align: center;
    }

    .decree-proposal-detail-modal .fi-in-entry + .fi-in-entry {
        margin-top: 1.25rem;
    }

    .decree-proposal-detail-modal .fi-in-entry-label-col {
        display: flex;
        align-items: center;
        justify-content: center;
        border-inline-end: 0;
        padding-inline-end: 0;
        text-align: center;
        width: 100%;
        max-width: none;
    }

    .decree-proposal-detail-modal .fi-in-entry-label-col::after {
        content: none;
    }

    .decree-proposal-detail-modal .fi-in-entry-content-col {
        min-width: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        text-align: center;
        max-width: none;
    }

    .decree-proposal-detail-modal .fi-in-entry:nth-child(even) {
        background: rgba(232, 245, 238, .42);
    }

    .decree-proposal-detail-modal .fi-in-entry:last-child {
        border-bottom: 0;
    }

    .decree-proposal-detail-modal .fi-in-entry-label {
        color: #315b4c;
        font-size: clamp(1.1875rem, 1.05rem + .35vw, 1.375rem);
        font-weight: 700;
        line-height: 1.4;
        text-align: center;
    }

    .decree-proposal-detail-modal .fi-in-entry-content {
        min-width: 0;
        width: 100%;
        max-width: none;
        color: #202b27;
        font-size: clamp(1.1875rem, 1.05rem + .35vw, 1.375rem);
        font-weight: 400;
        line-height: 1.45;
        overflow-wrap: anywhere;
        text-align: center;
    }

    .decree-proposal-detail-modal .fi-in-entry-content .fi-btn {
        font-size: 1rem;
        line-height: 1.5;
    }

    .decree-proposal-detail-modal .fi-in-entry-content .fi-in-entry-content-ctn,
    .decree-proposal-detail-modal .fi-in-entry-content .fi-in-text,
    .decree-proposal-detail-modal .fi-in-entry-content .fi-in-icon,
    .decree-proposal-detail-modal .fi-in-entry-content .fi-in-badge {
        justify-content: center;
        text-align: center;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:first-child .fi-in-entry-content,
    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:first-child .fi-in-entry-content-col {
        width: 180px;
        min-height: 240px;
        margin-inline: auto;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content-col {
        flex-direction: column;
        gap: .5rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content .fi-in-entry-content-ctn {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
        justify-content: center;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry + .fi-in-entry {
        margin-top: 1.5rem;
    }

    .decree-proposal-detail-modal .fi-modal-footer .fi-btn {
        font-size: 1rem;
        line-height: 1.5;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry-label {
        font-size: 1.125rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:nth-child(2) .fi-in-entry-content {
        color: #075b43;
        font-size: clamp(1.5rem, 1.25rem + .7vw, 1.75rem);
        font-weight: 700;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:nth-child(3) .fi-in-entry-content {
        font-size: clamp(1.25rem, 1.1rem + .4vw, 1.5rem);
        font-weight: 500;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:first-child img {
        width: 180px;
        height: 240px;
        aspect-ratio: 3 / 4;
        border: 1px solid #b7cfc2;
        border-radius: 5px;
        background: #eef2ef;
        object-fit: contain;
        margin-inline: auto;
    }

    .decree-proposal-detail-modal .decree-proposal-photo-entry .decree-proposal-photo {
        width: 180px;
        height: 240px;
        aspect-ratio: 3 / 4;
        border: 1px solid #b7cfc2;
        border-radius: 5px;
        background: #eef2ef;
        object-fit: contain;
    }

    .decree-proposal-detail-modal .fi-fo-field-wrp-label,
    .decree-proposal-detail-modal .fi-fo-field-wrp-helper-text,
    .decree-proposal-detail-modal .fi-input,
    .decree-proposal-detail-modal .fi-select-input,
    .decree-proposal-detail-modal .fi-fo-textarea {
        font-size: clamp(1.0625rem, 1rem + .2vw, 1.1875rem);
    }

    .decree-proposal-detail-modal .fi-in-entry-content .fi-btn,
    .decree-proposal-detail-modal .fi-modal-footer .fi-btn {
        font-size: clamp(1rem, .95rem + .12vw, 1.0625rem);
        line-height: 1.4;
    }

    @media (max-width: 900px) {
        .decree-proposal-detail-modal .fi-modal-content > * {
            width: calc(100% - 2rem);
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:first-child img,
        .decree-proposal-detail-modal .decree-proposal-photo-entry .decree-proposal-photo {
            width: 165px;
            height: 220px;
        }
    }

    @media (min-width: 901px) and (max-width: 1500px) {
        .decree-proposal-detail-modal .fi-modal-header .fi-modal-heading {
            font-size: 1.5rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header-heading {
            font-size: 1.375rem;
        }

        .decree-proposal-detail-modal .fi-in-entry-label,
        .decree-proposal-detail-modal .fi-in-entry-content {
            font-size: 1.25rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:nth-child(2) .fi-in-entry-content {
            font-size: 1.625rem;
        }
    }

    @media (max-width: 640px) {
        .decree-proposal-detail-modal .fi-modal-content > * {
            width: calc(100% - 1.25rem);
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-content {
            padding: .75rem;
        }

        .decree-proposal-detail-modal .fi-in-entry {
            padding: .55rem 0;
        }

        .decree-proposal-detail-modal .fi-in-entry + .fi-in-entry {
            margin-top: .75rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry + .fi-in-entry {
            margin-top: 1.25rem;
        }

        .decree-proposal-detail-modal .fi-in-entry-label,
        .decree-proposal-detail-modal .fi-in-entry-content {
            font-size: 1.125rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:nth-child(2) .fi-in-entry-content {
            font-size: 1.375rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section:first-child .fi-in-entry:first-child img,
        .decree-proposal-detail-modal .decree-proposal-photo-entry .decree-proposal-photo {
            width: 135px;
            height: 180px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .decree-proposal-detail-modal,
        .decree-proposal-detail-modal * {
            scroll-behavior: auto;
        }
    }
    /* Final visual layer: composition aligned with the supplied review dashboard reference. */
    .decree-proposal-detail-modal {
        background: #edf7f2;
        color: #172c24;
    }

    .decree-proposal-detail-modal .fi-modal-header {
        min-height: 5.35rem;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, .04) 0 72%, transparent 72%),
            linear-gradient(115deg, #064d39 0%, #08734f 64%, #075b43 100%);
        padding-inline: clamp(1rem, 3vw, 2.5rem);
    }

    .decree-proposal-detail-modal .fi-modal-header .fi-modal-heading {
        font-size: clamp(1.55rem, 1.2rem + .8vw, 2.1rem);
        letter-spacing: -.01em;
        line-height: 1.12;
    }

    .decree-proposal-detail-modal .fi-modal-header .fi-modal-description {
        color: rgba(255, 255, 255, .82);
        font-size: clamp(.95rem, .86rem + .22vw, 1.12rem);
        line-height: 1.25;
    }

    .decree-proposal-detail-modal .fi-modal-content {
        background: #edf7f2;
        padding-block: clamp(.8rem, 1.8vw, 1.45rem) 1.2rem;
        scrollbar-color: #77ad97 transparent;
    }

    .decree-proposal-detail-modal .fi-modal-content > * {
        width: 94%;
        max-width: 1800px;
    }

    .decree-proposal-detail-modal .fi-modal-footer {
        border-top-color: #c6dfd2;
        background: rgba(245, 252, 248, .96);
        box-shadow: 0 -8px 24px rgba(6, 77, 57, .1);
        padding: .85rem clamp(1rem, 3vw, 2rem);
    }

    .decree-proposal-detail-modal .fi-modal-footer > * {
        justify-content: center;
        gap: .7rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section {
        border-color: #a9d0bd;
        border-radius: .75rem;
        background: #fff;
        box-shadow: 0 3px 12px rgba(6, 77, 57, .07);
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section + .decree-proposal-detail-section {
        margin-top: 1rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header {
        min-height: 2.8rem;
        border-bottom: 0;
        background: linear-gradient(90deg, #08734f, #109463);
        padding: .58rem 1.15rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header-heading {
        font-size: clamp(1.25rem, 1.05rem + .45vw, 1.65rem);
        letter-spacing: -.01em;
        line-height: 1.2;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-content {
        min-height: 0;
        background: #fbfefc;
        padding: 1rem clamp(1rem, 2vw, 1.35rem);
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section {
        background: #f7fcf9;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content {
        display: grid;
        grid-template-columns: minmax(14rem, .8fr) minmax(22rem, 1.35fr) minmax(18rem, 1fr);
        align-items: stretch;
        padding: 1.1rem clamp(.8rem, 2.4vw, 1.7rem);
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry {
        min-width: 0;
        margin: 0;
        padding: .75rem 1.25rem;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(1) {
        grid-column: 1;
        grid-row: 1 / span 3;
        border-right: 1px solid #c5ddd1;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(2),
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(3) {
        grid-column: 2;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(2) { grid-row: 1; }
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(3) { grid-row: 2; }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(4),
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(5),
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(6) {
        grid-column: 3;
        border-left: 1px solid #c5ddd1;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(4) { grid-row: 1; }
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(5) { grid-row: 2; }
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(6) { grid-row: 3; }

    .decree-proposal-detail-modal .fi-in-entry-label-col,
    .decree-proposal-detail-modal .fi-in-entry-content-col {
        width: 100%;
        min-width: 0;
        max-width: none;
        justify-content: center;
        padding: 0;
        text-align: center;
    }

    .decree-proposal-detail-modal .fi-in-entry-label {
        color: #146040;
        font-size: clamp(1.05rem, .95rem + .25vw, 1.25rem);
        line-height: 1.25;
    }

    .decree-proposal-detail-modal .fi-in-entry-content {
        min-width: 0;
        max-width: none;
        color: #16221d;
        font-size: clamp(1.05rem, .97rem + .2vw, 1.2rem);
        line-height: 1.35;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(2) .fi-in-entry-content {
        color: #101b16;
        font-size: clamp(1.55rem, 1.2rem + .85vw, 2rem);
        line-height: 1.18;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(3) .fi-in-entry-content {
        color: #101b16;
        font-size: clamp(1.25rem, 1.05rem + .5vw, 1.55rem);
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:first-child .fi-in-entry-content,
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:first-child .fi-in-entry-content-col {
        width: 180px;
        min-height: 240px;
        margin-inline: auto;
    }

    .decree-proposal-detail-modal .decree-proposal-photo-entry .decree-proposal-photo,
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:first-child img {
        width: 180px;
        height: 240px;
        aspect-ratio: 3 / 4;
        border: 1px solid #abc8ba;
        border-radius: .45rem;
        background: #eef4f0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .12);
        object-fit: contain;
    }

    .decree-proposal-detail-modal .decree-proposal-education-section .fi-section-content {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 1rem;
    }

    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-section-content {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        column-gap: 0;
    }

    .decree-proposal-detail-modal .decree-proposal-education-section .fi-in-entry + .fi-in-entry,
    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry + .fi-in-entry {
        border-left: 1px solid #c5ddd1;
    }

    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry:nth-child(3n + 1) {
        border-left: 0;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-section-header {
        background: linear-gradient(90deg, #135f93, #217db0);
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-section-content {
        counter-reset: decree-document-row;
        padding: .35rem 1.2rem .55rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry {
        display: grid;
        grid-template-columns: 2.25rem minmax(13rem, 1.35fr) minmax(13rem, 1fr);
        align-items: center;
        column-gap: .85rem;
        min-height: 3.35rem;
        margin: 0;
        border-bottom: 1px solid #e1efe8;
        border-radius: 0;
        padding: .45rem .45rem;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry:nth-child(odd) {
        background: #eef8f3;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry:last-child {
        border-bottom: 0;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry::before {
        display: grid;
        width: 1.8rem;
        height: 1.55rem;
        place-items: center;
        border-radius: .55rem;
        background: #e5f0e9;
        color: #172c24;
        content: counter(decree-document-row);
        counter-increment: decree-document-row;
        font-size: 1rem;
        font-weight: 700;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-label-col {
        justify-content: flex-start;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-label {
        color: #18241f;
        font-size: clamp(1.125rem, 1rem + .18vw, 1.375rem);
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content-col {
        display: block;
        grid-column: 3;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content-ctn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content {
        width: auto;
        flex: 1 1 auto;
        color: #13803f;
        font-size: clamp(1.125rem, 1rem + .18vw, 1.375rem);
        font-weight: 700;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content .fi-in-text-item.fi-color-success {
        color: #13803f;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content .fi-in-text-item.fi-color-gray,
    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content .fi-in-text-item.fi-color-danger,
    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content .fi-in-text-item.fi-color-warning {
        color: #6f7b76;
        font-style: italic;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affix {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        gap: .45rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affix .fi-btn {
        min-height: 2rem;
        border-radius: .45rem;
        padding: .35rem .75rem;
        font-size: 1.125rem;
        line-height: 1.15;
    }

    .decree-proposal-detail-modal .fi-modal-footer .fi-btn {
        min-height: 2.65rem;
        border-radius: .65rem;
        font-size: clamp(1rem, .92rem + .2vw, 1.15rem);
        line-height: 1.25;
    }

    @media (max-width: 980px) {
        .decree-proposal-detail-modal .fi-modal-content > * {
            width: calc(100% - 1.5rem);
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content {
            grid-template-columns: minmax(10rem, .75fr) minmax(16rem, 1.25fr);
        }

        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-section-content {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry:nth-child(3n + 1) {
            border-left: 1px solid #c5ddd1;
        }

        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry:nth-child(2n + 1) {
            border-left: 0;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(1) {
            grid-row: 1 / span 2;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(4),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(5),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(6) {
            grid-column: 2;
            border-left: 0;
            border-top: 1px solid #c5ddd1;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(4) { grid-row: 3; }
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(5) { grid-row: 4; }
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(6) { grid-row: 5; }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry {
            grid-template-columns: 2.1rem minmax(12rem, 1fr);
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content-col {
            grid-column: 2;
        }
    }

    @media (max-width: 640px) {
        .decree-proposal-detail-modal .fi-modal-content > * {
            width: calc(100% - 1rem);
        }

        .decree-proposal-detail-modal .fi-modal-header {
            min-height: 4.8rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header {
            padding-inline: .8rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-content {
            padding: .75rem;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content,
        .decree-proposal-detail-modal .decree-proposal-education-section .fi-section-content,
        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-section-content {
            display: block;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry,
        .decree-proposal-detail-modal .decree-proposal-education-section .fi-in-entry,
        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry {
            border: 0;
            border-bottom: 1px solid #c5ddd1;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(1),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(2),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(3),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(4),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(5),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(6) {
            display: flex;
            grid-column: auto;
            grid-row: auto;
            border-left: 0;
            border-right: 0;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-section-content {
            padding: .25rem .5rem .45rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry {
            grid-template-columns: 1.9rem minmax(0, 1fr);
            gap: .25rem .55rem;
            padding: .6rem .2rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content-col {
            grid-column: 2;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content-ctn {
            align-items: flex-start;
            flex-direction: column;
            gap: .45rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affix {
            flex-wrap: wrap;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affix .fi-btn {
            font-size: .9rem;
        }

        .decree-proposal-detail-modal .fi-modal-footer {
            padding: .65rem .75rem;
        }

        .decree-proposal-detail-modal .fi-modal-footer > * {
            justify-content: stretch;
        }

        .decree-proposal-detail-modal .fi-modal-footer .fi-btn {
            flex: 1 1 8rem;
        }

        .decree-proposal-detail-modal .decree-proposal-photo-entry .decree-proposal-photo,
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:first-child img {
            width: 135px;
            height: 180px;
        }
    }
</style>

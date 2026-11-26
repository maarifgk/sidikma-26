<style>
    /*
     * SIDIKMA 26 detail view
     * One-screen desktop composition with a readable scroll fallback for long data.
     */
    .decree-proposal-detail-modal {
        height: 100dvh;
        max-height: 100dvh;
        overflow: hidden;
        border: 0;
        border-radius: 0;
        background: #edf7f2;
        color: #172c24;
        font-family: Cambria, "Times New Roman", Georgia, serif;
    }

    .decree-proposal-detail-modal,
    .decree-proposal-detail-modal * {
        box-sizing: border-box;
        font-family: Cambria, "Times New Roman", Georgia, serif;
    }

    .decree-proposal-detail-modal .fi-modal-header {
        flex: 0 0 auto;
        min-height: 4.8rem;
        border-bottom: 0;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, .04) 0 72%, transparent 72%),
            linear-gradient(115deg, #064d39 0%, #08734f 64%, #075b43 100%);
        color: #fff;
        padding: .7rem clamp(1rem, 2vw, 2rem);
    }

    .decree-proposal-detail-modal .fi-modal-header .fi-modal-heading,
    .decree-proposal-detail-modal .fi-modal-header .fi-modal-description,
    .decree-proposal-detail-modal .fi-modal-header .fi-icon {
        color: #fff;
    }

    .decree-proposal-detail-modal .fi-modal-header .fi-modal-heading {
        font-size: 30px;
        font-weight: 700;
        letter-spacing: -.01em;
        line-height: 1.1;
    }

    .decree-proposal-detail-modal .fi-modal-header .fi-modal-description {
        color: rgba(255, 255, 255, .84);
        font-size: 17px;
        line-height: 1.2;
    }

    .decree-proposal-detail-modal .fi-modal-content {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        align-content: start;
        gap: .6rem;
        min-height: 0;
        flex: 1 1 auto;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        background: #edf7f2;
        padding: .6rem 0 .75rem;
        scrollbar-color: #77ad97 transparent;
        scrollbar-width: thin;
    }

    .decree-proposal-detail-modal .fi-modal-content > * {
        width: 100%;
        max-width: none;
        min-width: 0;
        margin-inline: 0;
    }

    .decree-proposal-detail-modal .fi-modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 10;
        flex: 0 0 auto;
        border-top: 1px solid #c6dfd2;
        background: rgba(245, 252, 248, .97);
        box-shadow: 0 -6px 18px rgba(6, 77, 57, .1);
        backdrop-filter: blur(8px);
        overflow-x: auto;
        padding: .55rem clamp(1rem, 2vw, 2rem);
    }

    .decree-proposal-detail-modal .fi-modal-footer > * {
        max-width: 100%;
        justify-content: center;
        flex-wrap: wrap;
        gap: .55rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section {
        overflow: hidden;
        border: 1px solid #a9d0bd;
        border-radius: .55rem;
        background: #fff;
        box-shadow: 0 2px 8px rgba(6, 77, 57, .06);
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header {
        min-height: 2.35rem;
        border-bottom: 0;
        background: linear-gradient(90deg, #08734f, #109463);
        padding: .4rem .85rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header-heading {
        color: #fff;
        font-size: 23px;
        font-weight: 700;
        letter-spacing: -.01em;
        line-height: 1.15;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header .fi-icon {
        color: #fff;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-content {
        min-height: 0;
        background: #fbfefc;
        padding: .55rem .75rem;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content {
        display: grid;
        grid-template-columns: minmax(10rem, .8fr) minmax(18rem, 1.35fr) minmax(15rem, 1fr);
        grid-template-rows: repeat(3, 4.375rem);
        align-items: stretch;
        column-gap: .75rem;
        row-gap: .35rem;
        padding: .65rem 1rem;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry {
        min-width: 0;
        margin: 0;
        padding: .35rem .8rem;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(1) {
        grid-column: 1;
        grid-row: 1 / span 3;
        border-right: 1px solid #c5ddd1;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(2),
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(3) {
        grid-column: 2;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(2) { grid-row: 1; }
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(3) { grid-row: 2; }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(4),
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(5),
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(6) {
        grid-column: 3;
        border-left: 1px solid #c5ddd1;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(4) { grid-row: 1; }
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(5) { grid-row: 2; }
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(6) { grid-row: 3; }

    .decree-proposal-detail-modal .fi-in-entry {
        display: flex;
        width: 100%;
        min-width: 0;
        min-height: 0;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .15rem;
        margin: 0;
        padding: .35rem .4rem;
        text-align: center;
    }

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
        font-size: 18px;
        font-weight: 700;
        line-height: 1.2;
        text-align: inherit;
    }

    .decree-proposal-detail-modal .fi-in-entry-content {
        width: 100%;
        min-width: 0;
        max-width: none;
        color: #16221d;
        font-size: 20px;
        line-height: 1.25;
        overflow-wrap: anywhere;
        text-align: inherit;
    }

    .decree-proposal-detail-modal .fi-in-entry-content-ctn,
    .decree-proposal-detail-modal .fi-in-entry-content .fi-in-text,
    .decree-proposal-detail-modal .fi-in-entry-content .fi-in-badge {
        justify-content: center;
        text-align: inherit;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(2) .fi-in-entry-content {
        color: #101b16;
        font-size: 28px;
        font-weight: 700;
        line-height: 1.1;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(3) .fi-in-entry-content {
        color: #101b16;
        font-size: 23px;
        font-weight: 700;
    }

    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(1) .fi-in-entry-content,
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(1) .fi-in-entry-content-col {
        width: 150px;
        min-height: 200px;
        margin-inline: auto;
    }

    .decree-proposal-detail-modal .decree-proposal-photo-entry .decree-proposal-photo,
    .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(1) img {
        width: 150px;
        height: 200px;
        aspect-ratio: 3 / 4;
        border: 1px solid #abc8ba;
        border-radius: .35rem;
        background: #eef4f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
        object-fit: contain;
    }

    .decree-proposal-detail-modal .decree-proposal-identity-section .fi-section-content,
    .decree-proposal-detail-modal .decree-proposal-education-section .fi-section-content,
    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-section-content {
        display: grid;
        align-items: start;
        column-gap: .55rem;
        row-gap: .15rem;
    }

    .decree-proposal-detail-modal .decree-proposal-identity-section .fi-section-content {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .decree-proposal-detail-modal .decree-proposal-education-section .fi-section-content {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-section-content {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .decree-proposal-detail-modal .decree-proposal-identity-section .fi-in-entry,
    .decree-proposal-detail-modal .decree-proposal-education-section .fi-in-entry,
    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry,
    .decree-proposal-detail-modal .decree-proposal-notes-section .fi-in-entry {
        align-items: flex-start;
        justify-content: flex-start;
        padding: .35rem .45rem;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-identity-section .fi-in-entry-label-col,
    .decree-proposal-detail-modal .decree-proposal-identity-section .fi-in-entry-content-col,
    .decree-proposal-detail-modal .decree-proposal-education-section .fi-in-entry-label-col,
    .decree-proposal-detail-modal .decree-proposal-education-section .fi-in-entry-content-col,
    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry-label-col,
    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry-content-col,
    .decree-proposal-detail-modal .decree-proposal-notes-section .fi-in-entry-label-col,
    .decree-proposal-detail-modal .decree-proposal-notes-section .fi-in-entry-content-col {
        justify-content: flex-start;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-identity-section .fi-in-entry-content,
    .decree-proposal-detail-modal .decree-proposal-education-section .fi-in-entry-content,
    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry-content,
    .decree-proposal-detail-modal .decree-proposal-notes-section .fi-in-entry-content {
        font-size: 19px;
    }

    .decree-proposal-detail-modal .decree-proposal-identity-section .fi-in-entry:nth-child(2) {
        grid-column: span 2;
    }

    .decree-proposal-detail-modal .decree-proposal-identity-section .fi-in-entry:nth-child(6) {
        grid-column: span 2;
    }

    .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry:nth-child(2) {
        grid-column: span 2;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-section-header {
        background: linear-gradient(90deg, #135f93, #217db0);
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-section-content {
        display: block;
        counter-reset: decree-document-row;
        padding: .15rem .35rem .3rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-section-content > .fi-grid-col {
        margin: 0;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry {
        display: grid;
        grid-template-columns: 1.85rem minmax(0, 1fr);
        align-items: center;
        column-gap: .5rem;
        min-height: 2.35rem;
        margin: 0;
        border-bottom: 1px solid #e1efe8;
        border-radius: 0;
        padding: .22rem .25rem;
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
        width: 1.55rem;
        height: 1.35rem;
        place-items: center;
        border-radius: .4rem;
        background: #e5f0e9;
        color: #172c24;
        content: counter(decree-document-row);
        counter-increment: decree-document-row;
        font-size: 16px;
        font-weight: 700;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-label-col {
        justify-content: flex-start;
        min-width: 0;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-label {
        color: #18241f;
        font-size: 18px;
        line-height: 1.15;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content-col {
        display: block;
        grid-column: 2;
        min-width: 0;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content-ctn {
        display: block;
        min-width: 0;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content {
        width: 100%;
        color: #13803f;
        font-size: 17px;
        font-weight: 700;
        text-align: left;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affixed {
        display: grid;
        grid-template-columns: minmax(8rem, .7fr) minmax(14rem, 1.15fr);
        align-items: center;
        gap: .45rem;
        width: 100%;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affixed-content {
        min-width: 0;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affix {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .35rem;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affix .fi-btn {
        min-height: 1.85rem;
        border-radius: .35rem;
        padding: .25rem .55rem;
        font-size: 16px;
        font-weight: 700;
        line-height: 1.1;
        white-space: nowrap;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-item.fi-color-success {
        color: #13803f;
    }

    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-item.fi-color-gray,
    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-item.fi-color-danger,
    .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-item.fi-color-warning {
        color: #6f7b76;
        font-style: italic;
    }

    .decree-proposal-detail-modal .fi-modal-footer .fi-btn {
        min-height: 2.25rem;
        border-radius: .5rem;
        font-size: 17px;
        font-weight: 700;
        line-height: 1.15;
    }

    .decree-proposal-detail-modal .fi-modal-footer .fi-btn:focus-visible,
    .decree-proposal-detail-modal .fi-in-text-affix .fi-btn:focus-visible {
        outline: 2px solid #075b43;
        outline-offset: 2px;
    }

    .decree-proposal-detail-modal .decree-proposal-main-layout {
        width: 100%;
        max-width: none;
        min-width: 0;
    }

    .decree-proposal-detail-modal .decree-proposal-main-layout > .fi-sc {
        width: 100%;
        min-width: 0;
        grid-template-columns: minmax(0, 1fr);
    }

    .decree-proposal-detail-modal .decree-proposal-main-layout > .fi-sc > .fi-sc-component,
    .decree-proposal-detail-modal .decree-proposal-main-column,
    .decree-proposal-detail-modal .decree-proposal-main-column > .fi-sc {
        width: 100%;
        max-width: none;
        min-width: 0;
    }

    @media (min-width: 1280px) {
        .decree-proposal-detail-modal .fi-modal-content {
            gap: .55rem;
        }

        .decree-proposal-detail-modal .decree-proposal-main-layout > .fi-sc {
            grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr) !important;
        }
    }

    @media (min-width: 1280px) and (max-height: 850px) {
        .decree-proposal-detail-modal .fi-modal-header {
            min-height: 4.2rem;
            padding-block: .45rem;
        }

        .decree-proposal-detail-modal .fi-modal-content {
            gap: .4rem;
            padding-block: .35rem .45rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header {
            padding-block: .28rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header-heading {
            font-size: 22px;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-content {
            padding-block: .35rem;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content {
            padding-block: .35rem;
            grid-template-rows: repeat(3, 3.75rem);
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry {
            padding-block: .2rem;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(1) .fi-in-entry-content,
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(1) .fi-in-entry-content-col,
        .decree-proposal-detail-modal .decree-proposal-photo-entry .decree-proposal-photo,
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(1) img {
            width: 135px;
            min-height: 180px;
            height: 180px;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:nth-child(2) .fi-in-entry-content {
            font-size: 26px;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry {
            min-height: 2.05rem;
            padding-block: .12rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-label {
            font-size: 17px;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content {
            font-size: 16px;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affix .fi-btn {
            min-height: 1.65rem;
            padding-block: .15rem;
            font-size: 15px;
        }

        .decree-proposal-detail-modal .fi-modal-footer {
            padding-block: .4rem;
        }

        .decree-proposal-detail-modal .fi-modal-footer .fi-btn {
            min-height: 2rem;
            font-size: 16px;
        }
    }

    @media (max-width: 1279px) {
        .decree-proposal-detail-modal .fi-modal-content > * {
            width: 100%;
        }

        .decree-proposal-detail-modal .decree-proposal-main-layout > .fi-sc {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content {
            grid-template-columns: minmax(10rem, .75fr) minmax(16rem, 1.25fr);
            grid-template-rows: repeat(5, 4.375rem);
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(1) {
            grid-row: 1 / span 2;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(4),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(5),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(6) {
            grid-column: 2;
            border-left: 0;
            border-top: 1px solid #c5ddd1;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(4) { grid-row: 3; }
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(5) { grid-row: 4; }
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(6) { grid-row: 5; }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry {
            grid-template-columns: 1.85rem minmax(0, 1fr);
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry-content-col {
            grid-column: 2;
        }

        .decree-proposal-detail-modal .decree-proposal-identity-section .fi-section-content,
        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-section-content {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry:nth-child(2) {
            grid-column: auto;
        }
    }

    @media (max-width: 700px) {
        .decree-proposal-detail-modal .fi-modal-header {
            min-height: 4.4rem;
            padding-block: .5rem;
        }

        .decree-proposal-detail-modal .fi-modal-header .fi-modal-heading {
            font-size: 24px;
        }

        .decree-proposal-detail-modal .fi-modal-header .fi-modal-description {
            font-size: 16px;
        }

        .decree-proposal-detail-modal .fi-modal-content {
            display: block;
            padding-block: .45rem .65rem;
        }

        .decree-proposal-detail-modal .fi-modal-content > * {
            width: 100%;
            margin-bottom: .55rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header {
            padding-inline: .65rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-header-heading {
            font-size: 21px;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section .fi-section-content {
            padding: .55rem;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content,
        .decree-proposal-detail-modal .decree-proposal-identity-section .fi-section-content,
        .decree-proposal-detail-modal .decree-proposal-education-section .fi-section-content,
        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-section-content {
            display: block;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content {
            grid-template-rows: none;
            row-gap: 0;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry,
        .decree-proposal-detail-modal .decree-proposal-identity-section .fi-in-entry,
        .decree-proposal-detail-modal .decree-proposal-education-section .fi-in-entry,
        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry,
        .decree-proposal-detail-modal .decree-proposal-notes-section .fi-in-entry {
            border: 0;
            border-bottom: 1px solid #c5ddd1;
        }

        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(1),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(2),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(3),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(4),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(5),
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-section-content > .fi-grid-col:nth-child(6) {
            display: flex;
            grid-column: auto;
            grid-row: auto;
            border-left: 0;
            border-right: 0;
        }

        .decree-proposal-detail-modal .decree-proposal-identity-section .fi-in-entry:nth-child(2),
        .decree-proposal-detail-modal .decree-proposal-identity-section .fi-in-entry:nth-child(6),
        .decree-proposal-detail-modal .decree-proposal-employment-section .fi-in-entry:nth-child(2) {
            grid-column: auto;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-entry {
            grid-template-columns: 1.75rem minmax(0, 1fr);
            min-height: 0;
            padding-block: .45rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affixed {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .35rem;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affix {
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .decree-proposal-detail-modal .decree-proposal-detail-section--documents .fi-in-text-affix .fi-btn {
            font-size: 16px;
        }

        .decree-proposal-detail-modal .decree-proposal-photo-entry .decree-proposal-photo,
        .decree-proposal-detail-modal .decree-proposal-hero-section .fi-in-entry:first-child img {
            width: 135px;
            height: 180px;
        }

        .decree-proposal-detail-modal .fi-modal-footer {
            padding: .45rem .6rem;
        }

        .decree-proposal-detail-modal .fi-modal-footer > * {
            justify-content: stretch;
        }

        .decree-proposal-detail-modal .fi-modal-footer .fi-btn {
            flex: 1 1 8rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .decree-proposal-detail-modal,
        .decree-proposal-detail-modal * {
            scroll-behavior: auto;
        }
    }
</style>

.receipt-page {
    min-height: 100vh;
    margin: 0;
    background: #f4efe8;
    color: #2c1810;
    font: 14px/1.5 Arial, sans-serif;
}

.receipt-shell {
    width: min(calc(100% - 28px), 440px);
    margin: 28px auto;
}

.receipt-paper {
    padding: 25px;
    border: 1px solid #e7ddd0;
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 18px 48px rgb(57 37 28 / 10%);
}

.receipt-header {
    padding-bottom: 18px;
    border-bottom: 1px dashed #d9cbbb;
    text-align: center;
}

.receipt-brand {
    color: #8a653d;
    font-size: 0.7rem;
    font-weight: 850;
    letter-spacing: 0.16em;
}

.receipt-title {
    margin: 8px 0 3px;
    color: #2c1810;
    font-size: 1.4rem;
    font-weight: 900;
    line-height: 1.2;
}

.receipt-address,
.receipt-type {
    margin: 4px 0 0;
    color: #74685e;
    font-size: 0.82rem;
}

.receipt-status {
    display: inline-flex;
    margin-top: 12px;
    padding: 5px 11px;
    border: 1px solid #c8e5d0;
    border-radius: 999px;
    background: #edf8ef;
    color: #21643a;
    font-size: 0.72rem;
    font-weight: 850;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.receipt-status--pending {
    border-color: #f1d8a4;
    background: #fff7e7;
    color: #805414;
}

.receipt-status--unpaid {
    border-color: #e2d7cd;
    background: #f7f3ef;
    color: #625248;
}

.receipt-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin: 16px 0;
    padding: 14px;
    border: 1px solid #eee6de;
    border-radius: 15px;
    background: #fcfaf7;
}

.receipt-meta-item {
    display: grid;
    gap: 3px;
    min-width: 0;
}

.receipt-meta-item--wide {
    grid-column: 1 / -1;
}

.receipt-meta-label {
    color: #84796f;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.receipt-meta-value {
    overflow-wrap: anywhere;
    color: #38291f;
    font-size: 0.84rem;
    font-weight: 750;
}

.receipt-section-title {
    margin: 18px 0 8px;
    color: #4a3528;
    font-size: 0.82rem;
    font-weight: 850;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.receipt-items {
    padding: 2px 0 12px;
    border-bottom: 1px dashed #d9cbbb;
}

.receipt-item,
.receipt-total-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 7px 0;
}

.receipt-item-name {
    color: #38291f;
    font-weight: 700;
}

.receipt-item-quantity {
    display: block;
    margin-top: 2px;
    color: #84796f;
    font-size: 0.78rem;
}

.receipt-item-price {
    flex: 0 0 auto;
    color: #38291f;
    font-weight: 750;
}

.receipt-totals {
    display: grid;
    gap: 2px;
    padding: 10px 0 14px;
    border-bottom: 1px dashed #d9cbbb;
}

.receipt-total-row {
    padding: 5px 0;
    color: #62564d;
    font-size: 0.88rem;
}

.receipt-total-row--grand {
    margin-top: 4px;
    padding-top: 11px;
    border-top: 1px solid #e9dfd5;
    color: #2c1810;
    font-size: 1.08rem;
    font-weight: 900;
}

.receipt-code-box {
    margin-top: 16px;
    padding: 15px;
    border: 1px dashed #9f8061;
    border-radius: 15px;
    background: #fffdf9;
    text-align: center;
}

.receipt-code-label {
    margin: 0;
    color: #84796f;
    font-size: 0.68rem;
    font-weight: 850;
    letter-spacing: 0.16em;
}

.receipt-code {
    display: block;
    margin: 5px 0;
    color: #2c1810;
    font: 900 1.45rem/1.25 ui-monospace, SFMono-Regular, Consolas, monospace;
    letter-spacing: 0.13em;
    overflow-wrap: anywhere;
}

.receipt-note,
.receipt-thanks {
    margin: 12px 0 0;
    color: #74685e;
    font-size: 0.78rem;
    text-align: center;
}

.receipt-thanks {
    color: #6f4e37;
    font-weight: 800;
}

.receipt-actions {
    display: grid;
    gap: 9px;
    margin-top: 14px;
}

.receipt-actions form {
    margin: 0;
}

.receipt-button {
    display: inline-flex;
    width: 100%;
    min-height: 46px;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 15px;
    border: 1px solid #2c1810;
    border-radius: 13px;
    background: #2c1810;
    color: #fff;
    font: inherit;
    font-weight: 800;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
}

.receipt-button--confirm {
    border-color: #16803c;
    background: #16803c;
}

.receipt-button--danger {
    border-color: #c62828;
    background: #c62828;
}

.receipt-button--secondary {
    border-color: #ded3c7;
    background: #fff;
    color: #49372b;
}

.receipt-actions-note {
    margin: 2px 0;
    color: #74685e;
    font-size: 0.75rem;
    line-height: 1.45;
    text-align: center;
}

.receipt-button:focus-visible {
    outline: 3px solid #1d4ed8;
    outline-offset: 3px;
}

@media print {
    @page {
        margin: 5mm;
    }

    .receipt-page {
        min-height: 0;
        background: #fff;
        color: #111;
    }

    .receipt-shell {
        width: 100%;
        max-width: 420px;
        margin: 0 auto;
    }

    .receipt-paper {
        padding: 4mm;
        border: 0;
        border-radius: 0;
        box-shadow: none;
    }

    .receipt-actions {
        display: none !important;
    }
}

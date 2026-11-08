@php
    $hasBankDetails = filled($shop?->bank_name)
        || filled($shop?->bank_account_number)
        || filled($shop?->bank_account_name);
@endphp

@if($hasBankDetails)
    <div class="checkout-bank-card">
        <div class="checkout-bank-card-heading">
            <span class="checkout-bank-icon"><i class="ph-fill ph-bank" aria-hidden="true"></i></span>
            <div>
                <span class="checkout-bank-caption">Tujuan transfer</span>
                <strong class="checkout-bank-name">{{ $shop?->bank_name ?: 'Rekening kedai' }}</strong>
            </div>
        </div>
        @if(filled($shop?->bank_account_number))
            <div class="checkout-bank-account">
                <span>Nomor rekening</span>
                <strong>{{ $shop?->bank_account_number }}</strong>
            </div>
        @endif
        @if(filled($shop?->bank_account_name))
            <div class="checkout-bank-holder">
                <span>Atas nama</span>
                <strong>{{ $shop?->bank_account_name }}</strong>
            </div>
        @endif
    </div>
@endif

@if(filled($shop?->payment_instructions))
    <p class="checkout-transfer-note">{{ $shop?->payment_instructions }}</p>
@elseif(!$hasBankDetails)
    <p class="checkout-transfer-fallback">Informasi rekening belum tersedia. Silakan hubungi kedai sebelum melakukan transfer.</p>
    @if($whatsappUrl)
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">Minta detail rekening via WhatsApp <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
    @endif
@endif

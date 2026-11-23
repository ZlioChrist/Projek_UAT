<?php

use App\Support\WhatsAppPhoneNumber;

it('normalizes Indonesian mobile numbers to international format', function () {
    expect(WhatsAppPhoneNumber::normalize('0812 3456 7890'))->toBe('6281234567890')
        ->and(WhatsAppPhoneNumber::normalize('+62 812-3456-7890'))->toBe('6281234567890')
        ->and(WhatsAppPhoneNumber::normalize('81234567890'))->toBe('6281234567890');
});

it('rejects invalid and non-Indonesian phone numbers', function () {
    expect(WhatsAppPhoneNumber::normalize('+1 202 555 0142'))->toBeNull()
        ->and(WhatsAppPhoneNumber::normalize('0812'))->toBeNull()
        ->and(WhatsAppPhoneNumber::normalize(''))->toBeNull();
});

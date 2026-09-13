<?php

use App\Analytics\EventResolver;
use App\Analytics\EventType;

test('event labels are generated mechanically', function () {
    expect(EventType::WhatsappLead->label())->toBe('Whatsapp Lead')
        ->and(EventType::SectionView->isFunnelEvent())->toBeFalse();
});

test('cta events resolve from action rather than zone', function () {
    $resolver = new EventResolver;

    expect($resolver->resolve('ctwa', 'floating', 'whatsapp'))->toBe(EventType::WhatsappLead)
        ->and($resolver->resolve('ctwa', 'hero', 'scroll'))->toBe(EventType::Intent)
        ->and($resolver->resolve('form', 'pricing', 'internal_checkout'))->toBe(EventType::Intent);
});

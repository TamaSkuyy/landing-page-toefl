<?php

namespace App\Analytics;

use InvalidArgumentException;

final class EventResolver
{
    public const ZONES = ['hero', 'pricing', 'sticky', 'floating', 'footer', 'midpage', 'faq', 'nav'];

    public const ACTIONS = ['whatsapp', 'external_checkout', 'form_anchor', 'internal_checkout', 'scroll', 'link'];

    public function resolve(string $mode, string $zone, string $action): EventType
    {
        if (! in_array($zone, self::ZONES, true)) {
            throw new InvalidArgumentException("Unknown CTA zone: {$zone}");
        }

        if (! in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException("Unknown CTA action: {$action}");
        }

        if ($mode === 'ctwa' && $action === 'whatsapp') {
            return EventType::WhatsappLead;
        }

        if ($mode === 'ctwa' && $action === 'external_checkout') {
            return EventType::DirectCheckout;
        }

        if ($mode === 'form' && in_array($action, ['whatsapp', 'external_checkout'], true)) {
            throw new InvalidArgumentException("CTA action {$action} is not valid in form mode.");
        }

        return EventType::Intent;
    }
}

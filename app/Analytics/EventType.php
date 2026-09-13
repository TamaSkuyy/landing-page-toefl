<?php

namespace App\Analytics;

enum EventType: string
{
    case Visit = 'visit';
    case Engagement = 'engagement';
    case Intent = 'intent';
    case DirectCheckout = 'direct_checkout';
    case WhatsappLead = 'whatsapp_lead';
    case FormStart = 'form_start';
    case Lead = 'lead';
    case Payment = 'payment';
    case Scroll = 'scroll';
    case SectionView = 'section_view';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    public function isFunnelEvent(): bool
    {
        return ! in_array($this, [self::Scroll, self::SectionView], true);
    }

    public function allowedFor(string $mode): bool
    {
        $shared = [self::Visit, self::Engagement, self::Intent, self::Scroll, self::SectionView];
        $modeEvents = $mode === 'form'
            ? [self::FormStart, self::Lead, self::Payment]
            : [self::DirectCheckout, self::WhatsappLead];

        return in_array($this, [...$shared, ...$modeEvents], true);
    }

    /** @return list<self> */
    public static function forMode(string $mode): array
    {
        return array_values(array_filter(self::cases(), fn (self $event) => $event->allowedFor($mode)));
    }

    /** @return array<string, string> */
    public static function labelsFor(string $mode): array
    {
        $labels = [];
        foreach (self::forMode($mode) as $event) {
            $labels[$event->value] = $event->label();
        }

        return $labels;
    }
}

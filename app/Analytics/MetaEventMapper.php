<?php

namespace App\Analytics;

final class MetaEventMapper
{
    public function map(EventType $event): ?string
    {
        return match ($event) {
            EventType::Visit => 'PageView',
            EventType::Engagement => 'ViewContent',
            EventType::Intent => 'Intent',
            EventType::DirectCheckout, EventType::FormStart => 'InitiateCheckout',
            EventType::WhatsappLead, EventType::Lead => 'Lead',
            EventType::Payment => 'Purchase',
            EventType::Scroll, EventType::SectionView => null,
        };
    }

    /** @return array<string, string> */
    public function forMode(string $mode): array
    {
        $map = [];
        foreach (EventType::forMode($mode) as $event) {
            if ($metaEvent = $this->map($event)) {
                $map[$event->value] = $metaEvent;
            }
        }

        return $map;
    }
}

<?php

namespace App\Http\Requests;

use App\Analytics\EventResolver;
use App\Analytics\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class TrackEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('event_type')) {
            $this->merge(['events' => [$this->only(['event_type', 'event_data'])]]);
        }
    }

    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:20'],
            'events.*.event_type' => ['required', Rule::enum(EventType::class)],
            'events.*.event_data' => ['sometimes', 'array'],
            'events.*.event_data.event_id' => ['nullable', 'string', 'max:100'],
            'events.*.event_data.zone' => ['nullable', Rule::in(EventResolver::ZONES)],
            'events.*.event_data.action' => ['nullable', Rule::in(EventResolver::ACTIONS)],
            'events.*.event_data.depth' => ['nullable', 'integer', 'between:0,100'],
            'events.*.event_data.section' => ['nullable', 'string', 'max:255'],
            'events.*.event_data.landing_source' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $mode = (string) config('analytics.mode');
            $allowed = array_map(fn (EventType $event) => $event->value, EventType::forMode($mode));

            foreach ($this->input('events', []) as $index => $payload) {
                $event = EventType::tryFrom((string) ($payload['event_type'] ?? ''));
                if (! $event) {
                    continue;
                }

                if (! $event->allowedFor($mode)) {
                    $validator->errors()->add("events.{$index}.event_type", "Event is not valid for {$mode} mode. Allowed: ".implode(', ', $allowed));
                }

                if (in_array($event, [EventType::Lead, EventType::Payment], true)) {
                    $validator->errors()->add("events.{$index}.event_type", 'This event may only be written by the server.');
                }

                $data = $payload['event_data'] ?? [];
                if (isset($data['action'], $data['zone'])) {
                    try {
                        $resolved = app(EventResolver::class)->resolve($mode, $data['zone'], $data['action']);
                        if ($resolved !== $event) {
                            $validator->errors()->add("events.{$index}.event_type", "CTA action resolves to {$resolved->value}.");
                        }
                    } catch (InvalidArgumentException $exception) {
                        $validator->errors()->add("events.{$index}.event_data.action", $exception->getMessage());
                    }
                }
            }
        }];
    }

    /** @return list<array{event_type: EventType, event_data: array<string, mixed>}> */
    public function events(): array
    {
        return array_map(fn (array $payload) => [
            'event_type' => EventType::from($payload['event_type']),
            'event_data' => $payload['event_data'] ?? [],
        ], $this->input('events'));
    }
}

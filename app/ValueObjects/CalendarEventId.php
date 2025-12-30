<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\CalendarSessionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Calendar Event ID Value Object
 *
 * Provides type-safe handling of calendar event IDs.
 * Event IDs follow the format: {prefix}-{id} (e.g., 'qi-123', 'ap-456')
 *
 * This replaces the error-prone string manipulation patterns like:
 * ```php
 * // OLD (error-prone):
 * $numericId = (int) str_replace(['quran-', 'academic-'], '', $eventId);
 *
 * // NEW (type-safe):
 * $eventId = CalendarEventId::fromString($eventIdString);
 * $model = $eventId->resolve();
 * ```
 *
 * @see \App\Enums\CalendarSessionType
 */
final readonly class CalendarEventId
{
    /**
     * Create a new CalendarEventId instance.
     */
    private function __construct(
        public CalendarSessionType $type,
        public int $id
    ) {}

    /**
     * Create from a string event ID.
     *
     * @param  string  $eventId  Event ID in format '{prefix}-{id}'
     *
     * @throws \InvalidArgumentException If format is invalid
     */
    public static function fromString(string $eventId): self
    {
        $parts = explode('-', $eventId, 2);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(
                "Invalid event ID format: '{$eventId}'. Expected format: '{prefix}-{id}'"
            );
        }

        [$prefix, $idString] = $parts;

        if (! is_numeric($idString)) {
            throw new \InvalidArgumentException(
                "Invalid event ID: '{$eventId}'. ID portion must be numeric."
            );
        }

        $type = CalendarSessionType::fromEventIdPrefix($prefix);
        $id = (int) $idString;

        return new self($type, $id);
    }

    /**
     * Create from type and ID.
     */
    public static function make(CalendarSessionType $type, int $id): self
    {
        return new self($type, $id);
    }

    /**
     * Create from a session model instance.
     */
    public static function fromModel(Model $model): self
    {
        $type = match (true) {
            $model instanceof \App\Models\QuranSession => CalendarSessionType::fromQuranSession($model),
            $model instanceof \App\Models\AcademicSession => CalendarSessionType::ACADEMIC_PRIVATE,
            $model instanceof \App\Models\InteractiveCourseSession => CalendarSessionType::INTERACTIVE_COURSE,
            default => throw new \InvalidArgumentException(
                'Unsupported model type: '.get_class($model)
            ),
        };

        return new self($type, $model->id);
    }

    /**
     * Convert to string format.
     */
    public function toString(): string
    {
        return $this->type->eventIdPrefix().'-'.$this->id;
    }

    /**
     * Resolve the event ID to its corresponding Eloquent model.
     *
     * @throws ModelNotFoundException If model not found
     */
    public function resolve(): Model
    {
        $modelClass = $this->type->modelClass();
        $record = $modelClass::find($this->id);

        if (! $record) {
            throw (new ModelNotFoundException)->setModel($modelClass, [$this->id]);
        }

        return $record;
    }

    /**
     * Attempt to resolve the model, returning null if not found.
     */
    public function tryResolve(): ?Model
    {
        $modelClass = $this->type->modelClass();

        return $modelClass::find($this->id);
    }

    /**
     * Check if this event ID represents a Quran session.
     */
    public function isQuran(): bool
    {
        return $this->type->isQuran();
    }

    /**
     * Check if this event ID represents an Academic session.
     */
    public function isAcademic(): bool
    {
        return $this->type->isAcademic();
    }

    /**
     * Check if the referenced session exists.
     */
    public function exists(): bool
    {
        $modelClass = $this->type->modelClass();

        return $modelClass::where('id', $this->id)->exists();
    }

    /**
     * Get the session type.
     */
    public function getType(): CalendarSessionType
    {
        return $this->type;
    }

    /**
     * Get the numeric ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Check equality with another CalendarEventId.
     */
    public function equals(self $other): bool
    {
        return $this->type === $other->type && $this->id === $other->id;
    }

    /**
     * Convert to string when cast.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Serialize for JSON.
     *
     * @return array{type: string, id: int, eventId: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type->value,
            'id' => $this->id,
            'eventId' => $this->toString(),
        ];
    }
}

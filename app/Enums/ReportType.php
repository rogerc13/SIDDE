<?php

namespace App\Enums;

/**
 * Single source of truth for the reporting suite.
 *
 * Adding a report is a matter of adding a case here plus a query/export mapping;
 * routes, controllers, PDF titles, the frontend selector and exports all derive
 * from this enum.
 */
enum ReportType: string
{
    case Date = 'date';
    case Category = 'category';
    case Status = 'status';
    case MostScheduled = 'most-scheduled';
    case LeastScheduled = 'least-scheduled';
    case NotScheduled = 'not-scheduled';
    case Canceled = 'canceled';
    case ParticipantByQuantity = 'participant-by-quantity';
    case Duration = 'duration';
    case ParticipantByStatus = 'participant-by-status';
    case ParticipantAverage = 'participant-average';
    case Gender = 'gender';

    /**
     * Human readable (Spanish) title used on screen, in PDFs and filenames.
     */
    public function label(): string
    {
        return match ($this) {
            self::Date => 'Cantidad de Acciones de Formación',
            self::Category => 'Cantidad de Acciones de Formación Por Áreas de Conocimiento',
            self::Status => 'Cantidad de Acciones de Formación Por Estatus',
            self::MostScheduled => 'Acciones de Formación más Programadas (Histórico)',
            self::LeastScheduled => 'Acciones de Formación menos Programadas (Histórico)',
            self::NotScheduled => 'Acciones de Formación No Programadas (Histórico)',
            self::Canceled => 'Acciones de Formación Canceladas',
            self::ParticipantByQuantity => 'Acciones de Formación por Cantidad de Participantes',
            self::Duration => 'Duración de Acciones de Formación',
            self::ParticipantByStatus => 'Estatus de Participantes',
            self::ParticipantAverage => 'Promedio de Participantes',
            self::Gender => 'Distribución de Participantes por Género',
        };
    }

    /**
     * Reports whose result depends on the selected date range. The two
     * historical reports ignore it entirely.
     */
    public function isDateRanged(): bool
    {
        return match ($this) {
            self::MostScheduled, self::LeastScheduled, self::NotScheduled => false,
            default => true,
        };
    }

    /**
     * Whether the PDF opens with an executive summary / narrative page.
     */
    public function hasInsight(): bool
    {
        return true;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }

    public static function tryFromRequest(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }
}

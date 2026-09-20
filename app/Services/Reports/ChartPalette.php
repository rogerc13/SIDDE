<?php

namespace App\Services\Reports;

/**
 * Single source of truth for report colors.
 *
 * Mirrored by the frontend module (public/assets/js/reports/palette.js) so the
 * browser and the server-rendered PDF charts stay visually identical. The
 * categorical palette is colorblind-friendly and never relies on red/green as
 * the only distinguishing encoding.
 */
final class ChartPalette
{
    /** @var array<int, string> */
    public const CATEGORICAL = [
        '#4C72B0', '#DD8452', '#55A868', '#C44E52', '#8172B3',
        '#937860', '#DA8BC3', '#8C8C8C', '#CCB974', '#64B5CD',
    ];

    public const OTHERS = '#999999';
    public const NEUTRAL = '#8C8C8C';
    public const NEGATIVE = '#DD8452';
    public const POSITIVE = '#4C72B0';

    /**
     * @return array<int, string>
     */
    public function categorical(): array
    {
        return self::CATEGORICAL;
    }

    public function forIndex(int $index): string
    {
        $count = count(self::CATEGORICAL);

        return self::CATEGORICAL[(($index % $count) + $count) % $count];
    }

    /**
     * Deterministic color for a label, so the same category keeps its color
     * across reports.
     */
    public function forKey(string $key): string
    {
        return $this->forIndex(crc32($key));
    }

    public function courseStatus(string $name): string
    {
        return match ($this->normalize($name)) {
            'por dictar', 'por iniciar' => '#8172B3',
            'en curso' => '#4C72B0',
            'culminado', 'aprobado' => '#55A868',
            'cancelado' => '#C44E52',
            'reprobado', 'suspendido' => '#DD8452',
            default => self::NEUTRAL,
        };
    }

    public function participantStatus(string $name): string
    {
        return match ($this->normalize($name)) {
            'en curso' => '#4C72B0',
            'reprobado', 'suspendido' => '#DD8452',
            'aprobado' => '#55A868',
            'cancelado' => '#C44E52',
            'por iniciar', 'por dictar' => '#8172B3',
            default => self::NEUTRAL,
        };
    }

    public function gender(string $label): string
    {
        return match ($this->normalize($label)) {
            'masculino' => '#4C72B0',
            'femenino' => '#DD8452',
            default => self::NEUTRAL,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'categorical' => self::CATEGORICAL,
            'others' => self::OTHERS,
            'neutral' => self::NEUTRAL,
            'courseStatus' => [
                'Por Dictar' => $this->courseStatus('Por Dictar'),
                'En Curso' => $this->courseStatus('En Curso'),
                'Culminado' => $this->courseStatus('Culminado'),
                'Cancelado' => $this->courseStatus('Cancelado'),
            ],
            'participantStatus' => [
                'En Curso' => $this->participantStatus('En Curso'),
                'Reprobado' => $this->participantStatus('Reprobado'),
                'Aprobado' => $this->participantStatus('Aprobado'),
                'Cancelado' => $this->participantStatus('Cancelado'),
                'Por Iniciar' => $this->participantStatus('Por Iniciar'),
            ],
            'gender' => [
                'Masculino' => $this->gender('Masculino'),
                'Femenino' => $this->gender('Femenino'),
                'No especificado' => self::NEUTRAL,
            ],
        ];
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}

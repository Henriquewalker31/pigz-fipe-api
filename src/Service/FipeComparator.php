<?php

namespace App\Service;

use App\Entity\FipePrice;
use App\Entity\Vehicle;

final class FipeComparator
{
    /** Até 2% de diferença para mais ou para menos conta como "na média". */
    public const TOLERANCE_PERCENT = 2.0;

    public function compare(Vehicle $vehicle, ?FipePrice $fipe): ?array
    {
        if ($fipe === null) {
            return null;
        }

        $vehicleCents = self::cents($vehicle->getPrice());
        $fipeCents = self::cents($fipe->getPrice());
        $diff = $vehicleCents - $fipeCents;
        $percent = $fipeCents > 0 ? round($diff / $fipeCents * 100, 2) : null;

        return [
            'fipe_code' => $fipe->getFipeModel()->getFipeCode(),
            'reference_month' => $fipe->getReferenceMonth()->format('Y-m'),
            'fipe_price' => $fipeCents / 100,
            'vehicle_price' => $vehicleCents / 100,
            'difference' => $diff / 100,
            'difference_percent' => $percent,
            'classification' => match (true) {
                $percent === null => 'indisponivel',
                $percent <= -self::TOLERANCE_PERCENT => 'abaixo_da_fipe',
                $percent >= self::TOLERANCE_PERCENT => 'acima_da_fipe',
                default => 'na_media_da_fipe',
            },
        ];
    }

    /** @param FipePrice[] $prices do mais recente para o mais antigo */
    public function summarizeHistory(array $prices): ?array
    {
        if (!$prices) {
            return null;
        }

        $latest = $prices[0];
        $oldest = $prices[count($prices) - 1];
        $values = array_map(fn (FipePrice $p) => self::cents($p->getPrice()), $prices);
        $oldestCents = self::cents($oldest->getPrice());
        $latestCents = self::cents($latest->getPrice());

        return [
            'months' => count($prices),
            'from' => $oldest->getReferenceMonth()->format('Y-m'),
            'to' => $latest->getReferenceMonth()->format('Y-m'),
            'oldest_price' => $oldestCents / 100,
            'latest_price' => $latestCents / 100,
            'variation_percent' => $oldestCents > 0 ? round(($latestCents - $oldestCents) / $oldestCents * 100, 2) : null,
            'min_price' => min($values) / 100,
            'max_price' => max($values) / 100,
        ];
    }

    private static function cents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
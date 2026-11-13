<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class FipePriceInput
{
    public function __construct(
        #[Assert\NotBlank, Assert\Regex(pattern: '/^\d{4}-(0[1-9]|1[0-2])$/', message: 'Use o formato AAAA-MM.')]
        public readonly string $referenceMonth = '',
        #[Assert\Positive] public readonly float $price = 0,
    ) {}

    public function month(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->referenceMonth . '-01');
    }
}
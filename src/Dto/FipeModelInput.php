<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class FipeModelInput
{
    public function __construct(
        #[Assert\NotBlank, Assert\Regex(pattern: '/^\d{6}-\d$/', message: 'Código FIPE no formato 000000-0.')]
        public readonly string $fipeCode = '',
        #[Assert\Choice(choices: ['carro', 'moto', 'caminhao'])] public readonly string $vehicleType = 'carro',
        #[Assert\NotBlank, Assert\Length(max: 80)] public readonly string $brand = '',
        #[Assert\NotBlank, Assert\Length(max: 150)] public readonly string $model = '',
        // 32000 = zero km na FIPE
        #[Assert\Range(min: 1900, max: 32000)] public readonly int $modelYear = 0,
        #[Assert\NotBlank, Assert\Length(max: 30)] public readonly string $fuel = '',
    ) {}
}
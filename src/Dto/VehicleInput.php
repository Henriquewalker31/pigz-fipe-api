<?php

namespace App\Dto;

use App\Enum\VehicleStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class VehicleInput
{
    public function __construct(
        #[Assert\Positive] public readonly ?int $fipeModelId = null,
        #[Assert\NotBlank, Assert\Length(max: 80)] public readonly string $brand = '',
        #[Assert\NotBlank, Assert\Length(max: 150)] public readonly string $model = '',
        #[Assert\Length(max: 150)] public readonly ?string $version = null,
        #[Assert\Length(max: 30)] public readonly ?string $bodyType = null,
        #[Assert\Range(min: 1900, max: 2100)] public readonly int $manufactureYear = 0,
        #[Assert\Range(min: 1900, max: 2100)] public readonly int $modelYear = 0,
        #[Assert\NotBlank, Assert\Length(max: 30)] public readonly string $fuel = '',
        #[Assert\Length(max: 30)] public readonly ?string $transmission = null,
        #[Assert\Length(max: 40)] public readonly ?string $color = null,
        #[Assert\PositiveOrZero] public readonly int $mileage = 0,
        #[Assert\Range(min: 2, max: 5)] public readonly ?int $doors = null,
        #[Assert\All([
            new Assert\Type('string'),
            new Assert\Length(max: 50),
            new Assert\Regex(pattern: '/,/', match: false, message: 'Não use vírgulas nos opcionais.'),
        ])]
        public readonly array $features = [],
        #[Assert\Length(max: 80)] public readonly ?string $city = null,
        #[Assert\Length(exactly: 2)] public readonly ?string $state = null,
        #[Assert\Positive] public readonly float $price = 0,
        public readonly ?string $description = null,
        #[Assert\Choice(callback: [VehicleStatus::class, 'values'])] public readonly string $status = 'available',
    ) {}

    #[Assert\Callback]
    public function validateYears(ExecutionContextInterface $context): void
    {
        if ($this->modelYear < $this->manufactureYear || $this->modelYear > $this->manufactureYear + 1) {
            $context->buildViolation('O ano modelo deve ser igual ao ano de fabricação ou um ano maior.')
                ->atPath('modelYear')
                ->addViolation();
        }
    }
}
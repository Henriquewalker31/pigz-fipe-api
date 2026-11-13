<?php

namespace App\Entity;

use App\Repository\FipePriceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FipePriceRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_fipe_price_month', columns: ['fipe_model_id', 'reference_month'])]
class FipePrice
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FipeModel::class, inversedBy: 'prices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FipeModel $fipeModel;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $referenceMonth;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $price;

    public function __construct(FipeModel $fipeModel, \DateTimeImmutable $month, float $price)
    {
        $this->fipeModel = $fipeModel;
        $this->update($month, $price);
    }

    public function update(\DateTimeImmutable $month, float $price): void
    {
        $this->referenceMonth = $month;
        $this->price = number_format($price, 2, '.', '');
    }

    public function getId(): ?int { return $this->id; }
    public function getFipeModel(): FipeModel { return $this->fipeModel; }
    public function getReferenceMonth(): \DateTimeImmutable { return $this->referenceMonth; }
    public function getPrice(): string { return $this->price; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference_month' => $this->referenceMonth->format('Y-m'),
            'price' => (float) $this->price,
        ];
    }
}
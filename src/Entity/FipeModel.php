<?php

namespace App\Entity;

use App\Dto\FipeModelInput;
use App\Repository\FipeModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FipeModelRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_fipe_code_year_fuel', columns: ['fipe_code', 'model_year', 'fuel'])]
#[ORM\Index(name: 'idx_fipe_brand', columns: ['brand'])]
class FipeModel
{
    /** A FIPE usa o ano 32000 para veículos zero km. */
    public const ZERO_KM_YEAR = 32000;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $fipeCode;

    #[ORM\Column(length: 20)]
    private string $vehicleType;

    #[ORM\Column(length: 80)]
    private string $brand;

    #[ORM\Column(length: 150)]
    private string $model;

    #[ORM\Column]
    private int $modelYear;

    #[ORM\Column(length: 30)]
    private string $fuel;

    /** @var Collection<int, FipePrice> */
    #[ORM\OneToMany(targetEntity: FipePrice::class, mappedBy: 'fipeModel', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['referenceMonth' => 'DESC'])]
    private Collection $prices;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(FipeModelInput $input)
    {
        $this->prices = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->apply($input);
    }

    public function apply(FipeModelInput $input): void
    {
        $this->fipeCode = $input->fipeCode;
        $this->vehicleType = $input->vehicleType;
        $this->brand = $input->brand;
        $this->model = $input->model;
        $this->modelYear = $input->modelYear;
        $this->fuel = $input->fuel;
    }

    public function getId(): ?int { return $this->id; }
    public function getFipeCode(): string { return $this->fipeCode; }
    public function getModelYear(): int { return $this->modelYear; }

    /** @return FipePrice[] do mês mais recente para o mais antigo */
    public function getPrices(): array { return array_values($this->prices->toArray()); }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fipe_code' => $this->fipeCode,
            'vehicle_type' => $this->vehicleType,
            'brand' => $this->brand,
            'model' => $this->model,
            'model_year' => $this->modelYear,
            'fuel' => $this->fuel,
        ];
    }
}
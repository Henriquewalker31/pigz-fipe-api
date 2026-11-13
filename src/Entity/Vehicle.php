<?php

namespace App\Entity;

use App\Dto\VehicleInput;
use App\Enum\VehicleStatus;
use App\Repository\VehicleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
#[ORM\Index(name: 'idx_vehicle_status', columns: ['status'])]
#[ORM\Index(name: 'idx_vehicle_price', columns: ['price'])]
class Vehicle
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FipeModel::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FipeModel $fipeModel = null;

    #[ORM\Column(length: 80)]
    private string $brand;

    #[ORM\Column(length: 150)]
    private string $model;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $version = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $bodyType = null;

    #[ORM\Column]
    private int $manufactureYear;

    #[ORM\Column]
    private int $modelYear;

    #[ORM\Column(length: 30)]
    private string $fuel;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $transmission = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $color = null;

    #[ORM\Column]
    private int $mileage;

    #[ORM\Column(nullable: true)]
    private ?int $doors = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $features = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $price;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, enumType: VehicleStatus::class)]
    private VehicleStatus $status;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(VehicleInput $input, ?FipeModel $fipeModel)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->apply($input, $fipeModel);
    }

    public function apply(VehicleInput $in, ?FipeModel $fipeModel): void
    {
        $this->fipeModel = $fipeModel;
        $this->brand = $in->brand;
        $this->model = $in->model;
        $this->version = $in->version;
        $this->bodyType = $in->bodyType;
        $this->manufactureYear = $in->manufactureYear;
        $this->modelYear = $in->modelYear;
        $this->fuel = $in->fuel;
        $this->transmission = $in->transmission;
        $this->color = $in->color;
        $this->mileage = $in->mileage;
        $this->doors = $in->doors;
        $this->features = array_values(array_unique(array_map('trim', $in->features))) ?: null;
        $this->city = $in->city;
        $this->state = $in->state ? strtoupper($in->state) : null;
        $this->price = number_format($in->price, 2, '.', '');
        $this->description = $in->description;
        $this->status = VehicleStatus::from($in->status);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getFipeModel(): ?FipeModel { return $this->fipeModel; }
    public function getPrice(): string { return $this->price; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'version' => $this->version,
            'body_type' => $this->bodyType,
            'manufacture_year' => $this->manufactureYear,
            'model_year' => $this->modelYear,
            'fuel' => $this->fuel,
            'transmission' => $this->transmission,
            'color' => $this->color,
            'mileage' => $this->mileage,
            'doors' => $this->doors,
            'features' => $this->features ?? [],
            'city' => $this->city,
            'state' => $this->state,
            'price' => (float) $this->price,
            'description' => $this->description,
            'status' => $this->status->value,
            'fipe_model' => $this->fipeModel?->toArray(),
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}
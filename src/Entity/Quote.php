<?php



namespace App\Entity;

use App\Enum\City as CityEnum;
use App\Enum\Company as CompanyEnum;
use App\Enum\FuelType;
use App\Enum\InsuranceType;
use App\Enum\QuoteStatus;
use App\Enum\VehiculeBrand;
use App\Repository\QuoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\Table(name: 'quotes')]
class Quote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'uuid', type: 'guid', unique: true)]
    private string $uuid;

    #[ORM\Column(name: 'access_token', length: 64, unique: true)]
    private string $accessToken;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(enumType: CityEnum::class, nullable: true)]
    private ?CityEnum $city = null;

    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(name: 'city_entity_id', nullable: true)]
    private ?City $cityEntity = null;

    #[ORM\Column(enumType: CompanyEnum::class, nullable: true)]
    private ?CompanyEnum $company = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_entity_id', nullable: true)]
    private ?Company $companyEntity = null;

    #[ORM\Column(length: 20)]
    private string $phoneNumber;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeInterface $birthDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeInterface $licenseDate;

    #[ORM\Column(enumType: InsuranceType::class)]
    private InsuranceType $insuranceType;

    #[ORM\Column(name: 'vehicule_brand', enumType: VehiculeBrand::class)]
    private VehiculeBrand $vehiculeBrand;

    #[ORM\Column(enumType: FuelType::class)]
    private FuelType $fuelType;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeInterface $firstRegistrationDate;

    #[ORM\Column]
    private int $seatCount;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $newValue;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $marketValue;

    #[ORM\Column(length: 20)]
    private string $registrationNumber;

    #[ORM\Column(nullable: true)]
    private ?int $fiscalPower = null;

    #[ORM\Column(nullable: true)]
    private ?int $engineCapacity = null;

    #[ORM\Column(enumType: QuoteStatus::class)]
    private QuoteStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNote = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $customEstimation = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $selectedOffer = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->uuid        = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->accessToken = bin2hex(random_bytes(32));
        $this->createdAt   = new \DateTimeImmutable();
        $this->updatedAt   = new \DateTimeImmutable();
        
    }

    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    // ── Identity ──────────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getUuid(): string { return $this->uuid; }
    public function setUuid(string $uuid): static { $this->uuid = $uuid; return $this; }

    public function getAccessToken(): string { return $this->accessToken; }
    public function setAccessToken(string $token): static { $this->accessToken = $token; return $this; }

    /**
     * Vérifie le token d'accès de manière sécurisée (timing-safe).
     */
    public function isValidToken(string $token): bool
    {
        return $token !== '' && hash_equals($this->accessToken, $token);
    }

    // ── Personal info ─────────────────────────────────────────────────────────

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getPhoneNumber(): string { return $this->phoneNumber; }
    public function setPhoneNumber(string $phoneNumber): static { $this->phoneNumber = $phoneNumber; return $this; }

    // ── Location / Company ────────────────────────────────────────────────────

    public function getCity(): ?CityEnum { return $this->city; }
    public function setCity(?CityEnum $city): static { $this->city = $city; return $this; }

    public function getCityEntity(): ?City { return $this->cityEntity; }
    public function setCityEntity(?City $cityEntity): static { $this->cityEntity = $cityEntity; return $this; }

    public function getCompany(): ?CompanyEnum { return $this->company; }
    public function setCompany(?CompanyEnum $company): static { $this->company = $company; return $this; }

    public function getCompanyEntity(): ?Company { return $this->companyEntity; }
    public function setCompanyEntity(?Company $companyEntity): static { $this->companyEntity = $companyEntity; return $this; }

    // ── Driver ────────────────────────────────────────────────────────────────

    public function getBirthDate(): \DateTimeInterface { return $this->birthDate; }
    public function setBirthDate(\DateTimeInterface $birthDate): static { $this->birthDate = $birthDate; return $this; }

    public function getLicenseDate(): \DateTimeInterface { return $this->licenseDate; }
    public function setLicenseDate(\DateTimeInterface $licenseDate): static { $this->licenseDate = $licenseDate; return $this; }

    // ── Vehicle ───────────────────────────────────────────────────────────────

    public function getInsuranceType(): InsuranceType { return $this->insuranceType; }
    public function setInsuranceType(InsuranceType $insuranceType): static { $this->insuranceType = $insuranceType; return $this; }

    public function getVehiculeBrand(): VehiculeBrand { return $this->vehiculeBrand; }
    public function setVehiculeBrand(VehiculeBrand $vehiculeBrand): static { $this->vehiculeBrand = $vehiculeBrand; return $this; }

    public function getVehicleBrand(): VehiculeBrand { return $this->vehiculeBrand; }
    public function setVehicleBrand(VehiculeBrand $vehiculeBrand): static { $this->vehiculeBrand = $vehiculeBrand; return $this; }

    public function getFuelType(): FuelType { return $this->fuelType; }
    public function setFuelType(FuelType $fuelType): static { $this->fuelType = $fuelType; return $this; }

    public function getFirstRegistrationDate(): \DateTimeInterface { return $this->firstRegistrationDate; }
    public function setFirstRegistrationDate(\DateTimeInterface $date): static { $this->firstRegistrationDate = $date; return $this; }

    public function getSeatCount(): int { return $this->seatCount; }
    public function setSeatCount(int $seatCount): static { $this->seatCount = $seatCount; return $this; }

    public function getNewValue(): string { return $this->newValue; }
    public function setNewValue(string $newValue): static { $this->newValue = $newValue; return $this; }

    public function getMarketValue(): string { return $this->marketValue; }
    public function setMarketValue(string $marketValue): static { $this->marketValue = $marketValue; return $this; }

    public function getRegistrationNumber(): string { return $this->registrationNumber; }
    public function setRegistrationNumber(string $registrationNumber): static
    {
        $this->registrationNumber = strtoupper($registrationNumber);
        return $this;
    }

    public function getFiscalPower(): ?int { return $this->fiscalPower; }
    public function setFiscalPower(?int $fiscalPower): static { $this->fiscalPower = $fiscalPower; return $this; }

    public function getEngineCapacity(): ?int { return $this->engineCapacity; }
    public function setEngineCapacity(?int $engineCapacity): static { $this->engineCapacity = $engineCapacity; return $this; }

    // ── Quote data ────────────────────────────────────────────────────────────

    public function getStatus(): QuoteStatus { return $this->status; }
    public function setStatus(QuoteStatus $status): static { $this->status = $status; return $this; }

    public function getAdminNote(): ?string { return $this->adminNote; }
    public function setAdminNote(?string $adminNote): static { $this->adminNote = $adminNote; return $this; }

    public function getCustomEstimation(): ?string { return $this->customEstimation; }
    public function setCustomEstimation(?string $customEstimation): static { $this->customEstimation = $customEstimation; return $this; }

    public function getSelectedOffer(): ?string { return $this->selectedOffer; }
    public function setSelectedOffer(?string $selectedOffer): static { $this->selectedOffer = $selectedOffer; return $this; }

    // ── Timestamps ────────────────────────────────────────────────────────────

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
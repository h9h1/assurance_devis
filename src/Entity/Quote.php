<?php



namespace App\Entity;

use App\Enum\City as CityEnum;
use App\Enum\Company as CompanyEnum;
use App\Enum\FuelType;
use App\Enum\VehiculeBrand;
use App\Enum\InsuranceType;
use App\Enum\QuoteStatus;
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

    #[ORM\Column(name: 'last_name', length: 100)]
    private string $lastName;

    #[ORM\Column(name: 'first_name', length: 100)]
    private string $firstName;

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

    #[ORM\Column(name: 'phone_number', length: 20)]
    private string $phoneNumber;

    #[ORM\Column(name: 'email', length: 180, nullable: true)]
    private ?string $email = null;


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

    #[ORM\Column(name: 'registration_number', length: 20)]
    private string $registrationNumber;

    #[ORM\Column(name: 'fiscal_power', nullable: true)]
    private ?int $fiscalPower = null;

    #[ORM\Column(name: 'engine_capacity', nullable: true)]
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
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getLastName(): string
    {
        return $this->lastName;
    }
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }
    public function getFirstName(): string
    {
        return $this->firstName;
    }
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }
    public function getCity(): ?CityEnum
    {
        return $this->city;
    }

    public function getCityEntity(): ?City
    {
        return $this->cityEntity;
    }

    public function setCityEntity(?City $cityEntity): self
    {
        $this->cityEntity = $cityEntity;
        return $this;
    }
    public function setCity(?CityEnum $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCompany(): ?CompanyEnum
    {
        return $this->company;
    }

    public function getCompanyEntity(): ?Company
    {
        return $this->companyEntity;
    }

    public function setCompanyEntity(?Company $companyEntity): self
    {
        $this->companyEntity = $companyEntity;
        return $this;
    }
    public function setCompany(?CompanyEnum $company): self
    {
        $this->company = $company;
        return $this;
    }
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }

    public function getBirthDate(): \DateTimeInterface
    {
        return $this->birthDate;
    }
    public function setBirthDate(\DateTimeInterface $birthDate): self
    {
        $this->birthDate = $birthDate;
        return $this;
    }
    public function getLicenseDate(): \DateTimeInterface
    {
        return $this->licenseDate;
    }
    public function setLicenseDate(\DateTimeInterface $licenseDate): self
    {
        $this->licenseDate = $licenseDate;
        return $this;
    }
    public function getInsuranceType(): InsuranceType
    {
        return $this->insuranceType;
    }
    public function setInsuranceType(InsuranceType $insuranceType): self
    {
        $this->insuranceType = $insuranceType;
        return $this;
    }
    public function getVehiculeBrand(): VehiculeBrand
    {
        return $this->vehiculeBrand;
    }

    public function setVehiculeBrand(VehiculeBrand $vehiculeBrand): self
    {
        $this->vehiculeBrand = $vehiculeBrand;
        return $this;
    }

    public function getVehicleBrand(): VehiculeBrand
    {
        return $this->vehiculeBrand;
    }

    public function setVehicleBrand(VehiculeBrand $vehiculeBrand): self
    {
        $this->vehiculeBrand = $vehiculeBrand;
        return $this;
    }


    public function getFuelType(): FuelType
    {
        return $this->fuelType;
    }
    public function setFuelType(FuelType $fuelType): self
    {
        $this->fuelType = $fuelType;
        return $this;
    }
    public function getFirstRegistrationDate(): \DateTimeInterface
    {
        return $this->firstRegistrationDate;
    }
    public function setFirstRegistrationDate(\DateTimeInterface $firstRegistrationDate): self
    {
        $this->firstRegistrationDate = $firstRegistrationDate;
        return $this;
    }
    public function getSeatCount(): int
    {
        return $this->seatCount;
    }
    public function setSeatCount(int $seatCount): self
    {
        $this->seatCount = $seatCount;
        return $this;
    }
    public function getNewValue(): string
    {
        return $this->newValue;
    }
    public function setNewValue(string $newValue): self
    {
        $this->newValue = $newValue;
        return $this;
    }
    public function getMarketValue(): string
    {
        return $this->marketValue;
    }
    public function setMarketValue(string $marketValue): self
    {
        $this->marketValue = $marketValue;
        return $this;
    }
    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }
    public function setRegistrationNumber(string $registrationNumber): self
    {
        $this->registrationNumber = strtoupper($registrationNumber);
        return $this;
    }
    public function getFiscalPower(): ?int
    {
        return $this->fiscalPower;
    }
    public function setFiscalPower(?int $fiscalPower): self
    {
        $this->fiscalPower = $fiscalPower;
        return $this;
    }
    public function getEngineCapacity(): ?int
    {
        return $this->engineCapacity;
    }
    public function setEngineCapacity(?int $engineCapacity): self
    {
        $this->engineCapacity = $engineCapacity;
        return $this;
    }
    public function getStatus(): QuoteStatus
    {
        return $this->status;
    }
    public function setStatus(QuoteStatus $status): self
    {
        $this->status = $status;
        return $this;
    }
    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }
    public function setAdminNote(?string $adminNote): self
    {
        $this->adminNote = $adminNote;
        return $this;
    }
    public function getCustomEstimation(): ?string
    {
        return $this->customEstimation;
    }
    public function setCustomEstimation(?string $customEstimation): self
    {
        $this->customEstimation = $customEstimation;
        return $this;
    }
    public function getSelectedOffer(): ?string
    {
        return $this->selectedOffer;
    }
    public function setSelectedOffer(?string $selectedOffer): self
    {
        $this->selectedOffer = $selectedOffer;
        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

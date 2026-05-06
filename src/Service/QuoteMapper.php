<?php



namespace App\Service;

use App\DTO\QuoteRequest;
use App\Entity\Quote;
use App\Enum\City as CityEnum;
use App\Enum\Company as CompanyEnum;
use App\Enum\FuelType;
use App\Enum\InsuranceType;
use App\Enum\QuoteStatus;
use App\Enum\VehiculeBrand;
use App\Repository\CityRepository;
use App\Repository\CompanyRepository;

class QuoteMapper
{
    public function mapToEntity(
        QuoteRequest $dto,
        ?Quote $quote = null,
        ?CityRepository $cityRepository = null,
        ?CompanyRepository $companyRepository = null,
    ): Quote {
        $quote ??= new Quote();

        $quote
            ->setLastName($dto->lastName ?? '')
            ->setFirstName($dto->firstName ?? '')
            ->setPhoneNumber($dto->phoneNumber ?? '')
            ->setBirthDate(new \DateTimeImmutable($dto->birthDate ?? 'today'))
            ->setLicenseDate(new \DateTimeImmutable($dto->licenseDate ?? 'today'))
            ->setInsuranceType(InsuranceType::from($dto->insuranceType ?? ''))
            ->setVehicleBrand(VehiculeBrand::from($dto->vehicleBrand ?? ''))
            ->setFuelType(FuelType::from($dto->fuelType ?? ''))
            ->setFirstRegistrationDate(new \DateTimeImmutable($dto->firstRegistrationDate ?? 'today'))
            ->setSeatCount((int) ($dto->seatCount ?? 0))
            ->setNewValue(number_format((float) ($dto->newValue ?? 0), 2, '.', ''))
            ->setMarketValue(number_format((float) ($dto->marketValue ?? 0), 2, '.', ''))
            ->setRegistrationNumber($dto->registrationNumber ?? '')
            ->setFiscalPower($dto->insuranceType === 'auto' ? $dto->fiscalPower : null)
            ->setEngineCapacity($dto->insuranceType === 'moto' ? $dto->engineCapacity : null)
            // Toujours remplir l'Enum city (NOT NULL en base)
            ->setEmail($dto->email)
            ->setCity(CityEnum::tryFrom($dto->city ?? '') ?? CityEnum::Unknown)
            // Toujours remplir l'Enum company (NOT NULL en base)
            ->setCompany(CompanyEnum::tryFrom($dto->company ?? '') ?? CompanyEnum::Unknown)
            ->touch();

        // Initialise le statut uniquement à la création (pas en mise à jour)
        if ($quote->getId() === null) {
            $quote->setStatus(QuoteStatus::CONFIRMED);
        }

        // City Entity (pour les variations de prix)
        if ($cityRepository && $dto->city) {
            $cityEntity = $cityRepository->findOneBy(['name' => $dto->city, 'isActive' => true]);
            if ($cityEntity) {
                $quote->setCityEntity($cityEntity);
            }
        }

        // Company Entity (pour les variations de prix)
        if ($companyRepository) {
            $companyName  = $dto->company ?? '';
            $companyEntity = $companyRepository->findOneBy(['name' => $companyName, 'isActive' => true])
                ?? ($companyRepository->findActive()[0] ?? null);
            if ($companyEntity) {
                $quote->setCompanyEntity($companyEntity);
            }
        }

        return $quote;
    }

    public function toArray(Quote $quote): array
    {
        return [
            'id'                    => $quote->getId(),
            'uuid'                  => $quote->getUuid(),
            'accessToken'           => $quote->getAccessToken(),
            'lastName'              => $quote->getLastName(),
            'firstName'             => $quote->getFirstName(),
            'email'                 => $quote->getEmail(),
            'city'                  => $quote->getCity()?->value    ?? '',
            'company'               => $quote->getCompany()?->value ?? '',
            'phoneNumber'           => $quote->getPhoneNumber(),
            'birthDate'             => $quote->getBirthDate()->format('Y-m-d'),
            'licenseDate'           => $quote->getLicenseDate()->format('Y-m-d'),
            'insuranceType'         => $quote->getInsuranceType()->value,
            'vehicleBrand'          => $quote->getVehicleBrand()->value,
            'fuelType'              => $quote->getFuelType()->value,
            'firstRegistrationDate' => $quote->getFirstRegistrationDate()->format('Y-m-d'),
            'seatCount'             => $quote->getSeatCount(),
            'newValue'              => (float) $quote->getNewValue(),
            'marketValue'           => (float) $quote->getMarketValue(),
            'registrationNumber'    => $quote->getRegistrationNumber(),
            'fiscalPower'           => $quote->getFiscalPower(),
            'engineCapacity'        => $quote->getEngineCapacity(),
            'status'                => $quote->getStatus()->value,
            'adminNote'             => $quote->getAdminNote(),
            'customEstimation'      => $quote->getCustomEstimation(),
            'selectedOffer'         => $quote->getSelectedOffer(),
            'createdAt'             => $quote->getCreatedAt()->format(DATE_ATOM),
            'updatedAt'             => $quote->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
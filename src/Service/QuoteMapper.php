<?php


namespace App\Service;

use App\Enum\City;
use App\Enum\Company;
use App\Enum\VehiculeBrand;
use App\DTO\QuoteRequest;
use App\Entity\Quote;
use App\Enum\FuelType;
use App\Enum\InsuranceType;
use App\Repository\CityRepository;
use App\Repository\CompanyRepository;

class QuoteMapper
{
    public function mapToEntity(
        QuoteRequest $dto,
        ?Quote $quote = null,
        ?CityRepository $cityRepository = null,
        ?CompanyRepository $companyRepository = null,
    ): Quote
    {
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
            ->touch();

        if ($cityRepository && $dto->city) {
            $city = $cityRepository->findOneBy(['name' => $dto->city, 'isActive' => true]);
            if ($city) $quote->setCity($city);
        }

        if ($companyRepository) {
            $companyName = $dto->company ?? 'RMA';
            $company = $companyRepository->findOneBy(['name' => $companyName, 'isActive' => true])
                ?? ($companyRepository->findActive()[0] ?? null);
            if ($company) $quote->setCompany($company);
        }

        return $quote;
    }

    public function toArray(Quote $quote): array
    {
        return [
            'id' => $quote->getId(),
            'lastName' => $quote->getLastName(),
            'firstName' => $quote->getFirstName(),
            'city'    => $quote->getCity()?->value    ?? '',
            'company' => $quote->getCompany()?->value ?? '',
            'phoneNumber' => $quote->getPhoneNumber(),
            'birthDate' => $quote->getBirthDate()->format('Y-m-d'),
            'licenseDate' => $quote->getLicenseDate()->format('Y-m-d'),
            'insuranceType' => $quote->getInsuranceType()->value,
            'vehicleBrand' => $quote->getVehicleBrand()->value,
            'fuelType' => $quote->getFuelType()->value,
            'firstRegistrationDate' => $quote->getFirstRegistrationDate()->format('Y-m-d'),
            'seatCount' => $quote->getSeatCount(),
            'newValue' => (float) $quote->getNewValue(),
            'marketValue' => (float) $quote->getMarketValue(),
            'registrationNumber' => $quote->getRegistrationNumber(),
            'fiscalPower' => $quote->getFiscalPower(),
            'engineCapacity' => $quote->getEngineCapacity(),
            'status' => $quote->getStatus()->value,
            'createdAt' => $quote->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $quote->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\VehiculeBrand;
use App\Enum\Company;
use App\DTO\QuoteRequest;
use App\Entity\Quote;
use App\Enum\City;
use App\Enum\FuelType;
use App\Enum\InsuranceType;

class QuoteMapper
{
    public function mapToEntity(QuoteRequest $dto, ?Quote $quote = null): Quote
    {
        $quote ??= new Quote();

        $quote
            ->setLastName($dto->lastName ?? '')
            ->setFirstName($dto->firstName ?? '')
            ->setCity(City::from($dto->city ?? 'Casablanca'))
            ->setCompany(Company::from($dto->company ?? 'RMA'))
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

        return $quote;
    }

    public function toArray(Quote $quote): array
    {
        return [
            'id' => $quote->getId(),
            'lastName' => $quote->getLastName(),
            'firstName' => $quote->getFirstName(),
            'city' => $quote->getCity()->value,
            'company' => $quote->getCompany()->value,
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

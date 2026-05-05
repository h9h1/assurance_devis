<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\FuelType;
use App\Enum\InsuranceType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class QuoteRequest
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100)]
    public ?string $lastName = null;

    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100)]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'La ville est obligatoire.')]
    public ?string $city = null;

    public ?string $company = null;

    #[Assert\NotBlank(message: 'Le téléphone est obligatoire.')]
    #[Assert\Regex(pattern: '/^(\+212|0)[5-7][0-9]{8}$/', message: 'Format de téléphone invalide.')]
    public ?string $phoneNumber = null;

    #[Assert\NotBlank(message: 'La date de naissance est obligatoire.')]
    #[Assert\Date(message: 'Date de naissance invalide.')]
    public ?string $birthDate = null;

    #[Assert\NotBlank(message: 'La date du permis est obligatoire.')]
    #[Assert\Date(message: 'Date du permis invalide.')]
    public ?string $licenseDate = null;

    #[Assert\NotBlank(message: 'Le type d\'assurance est obligatoire.')]
    #[Assert\Choice(choices: ['auto', 'moto'], message: 'Type d\'assurance invalide.')]
    public ?string $insuranceType = null;

    #[Assert\NotBlank(message: 'La marque est obligatoire.')]
    #[Assert\Length(max: 120)]
    public ?string $vehicleBrand = null;

    #[Assert\NotBlank(message: 'Le carburant est obligatoire.')]
    #[Assert\Choice(choices: ['essence', 'diesel', 'hybride', 'electrique'], message: 'Carburant invalide.')]
    public ?string $fuelType = null;

    #[Assert\NotBlank(message: 'La date de mise en circulation est obligatoire.')]
    #[Assert\Date(message: 'Date de mise en circulation invalide.')]
    public ?string $firstRegistrationDate = null;

    #[Assert\NotNull(message: 'Le nombre de places est obligatoire.')]
    #[Assert\Positive(message: 'Le nombre de places doit être positif.')]
    public ?int $seatCount = null;

    #[Assert\NotNull(message: 'La valeur à neuf est obligatoire.')]
    #[Assert\Positive(message: 'La valeur à neuf doit être positive.')]
    public ?float $newValue = null;

    #[Assert\NotNull(message: 'La valeur vénale est obligatoire.')]
    #[Assert\Positive(message: 'La valeur vénale doit être positive.')]
    public ?float $marketValue = null;

    #[Assert\NotBlank(message: 'L\'immatriculation est obligatoire.')]
    #[Assert\Regex(pattern: '/^[A-Z0-9-]{5,20}$/i', message: 'Format d\'immatriculation invalide.')]
    public ?string $registrationNumber = null;

    #[Assert\Positive(message: 'La puissance fiscale doit être positive.')]
    public ?int $fiscalPower = null;

    #[Assert\Positive(message: 'La cylindrée doit être positive.')]
    public ?int $engineCapacity = null;

    #[Assert\Callback]
    public function validateBusinessRules(ExecutionContextInterface $context): void
    {
        $birthDate = $this->createDate($this->birthDate);
        $licenseDate = $this->createDate($this->licenseDate);
        $registrationDate = $this->createDate($this->firstRegistrationDate);
        $today = new \DateTimeImmutable('today');

        if ($birthDate instanceof \DateTimeImmutable && $birthDate >= $today) {
            $context->buildViolation('La date de naissance doit être dans le passé.')
                ->atPath('birthDate')
                ->addViolation();
        }

        if ($licenseDate instanceof \DateTimeImmutable && $licenseDate >= $today) {
            $context->buildViolation('La date du permis doit être dans le passé.')
                ->atPath('licenseDate')
                ->addViolation();
        }

        if ($registrationDate instanceof \DateTimeImmutable && $registrationDate > $today) {
            $context->buildViolation('La date de mise en circulation ne peut pas être future.')
                ->atPath('firstRegistrationDate')
                ->addViolation();
        }

        if ($birthDate instanceof \DateTimeImmutable && $licenseDate instanceof \DateTimeImmutable) {
            if ($licenseDate <= $birthDate) {
                $context->buildViolation('La date d\'obtention du permis doit être postérieure à la date de naissance.')
                    ->atPath('licenseDate')
                    ->addViolation();
            }

            $majorityDate = $birthDate->modify('+18 years');
            if ($licenseDate < $majorityDate) {
                $context->buildViolation('Le permis ne peut pas être obtenu avant 18 ans.')
                    ->atPath('licenseDate')
                    ->addViolation();
            }
        }

        if ($this->newValue !== null && $this->marketValue !== null && $this->marketValue > $this->newValue) {
            $context->buildViolation('La valeur vénale ne peut pas dépasser la valeur à neuf.')
                ->atPath('marketValue')
                ->addViolation();
        }

        if ($this->insuranceType === InsuranceType::AUTO->value && $this->fiscalPower === null) {
            $context->buildViolation('La puissance fiscale est obligatoire pour une assurance auto.')
                ->atPath('fiscalPower')
                ->addViolation();
        }

        if ($this->insuranceType === InsuranceType::MOTO->value && $this->engineCapacity === null) {
            $context->buildViolation('La cylindrée est obligatoire pour une assurance moto.')
                ->atPath('engineCapacity')
                ->addViolation();
        }
    }

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->lastName = self::stringOrNull($data['lastName'] ?? null);
        $dto->firstName = self::stringOrNull($data['firstName'] ?? null);
        $dto->city    = self::stringOrNull($data['city'] ?? null);
        $dto->company = self::stringOrNull($data['company'] ?? null);
        $dto->phoneNumber = self::stringOrNull($data['phoneNumber'] ?? null);
        $dto->birthDate = self::stringOrNull($data['birthDate'] ?? null);
        $dto->licenseDate = self::stringOrNull($data['licenseDate'] ?? null);
        $dto->insuranceType = self::stringOrNull($data['insuranceType'] ?? null);
        $dto->vehicleBrand = self::stringOrNull($data['vehicleBrand'] ?? null);
        $dto->fuelType = self::stringOrNull($data['fuelType'] ?? null);
        $dto->firstRegistrationDate = self::stringOrNull($data['firstRegistrationDate'] ?? null);
        $dto->seatCount = self::intOrNull($data['seatCount'] ?? null);
        $dto->newValue = self::floatOrNull($data['newValue'] ?? null);
        $dto->marketValue = self::floatOrNull($data['marketValue'] ?? null);
        $dto->registrationNumber = self::stringOrNull($data['registrationNumber'] ?? null);
        $dto->fiscalPower = self::intOrNull($data['fiscalPower'] ?? null);
        $dto->engineCapacity = self::intOrNull($data['engineCapacity'] ?? null);

        return $dto;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    private function createDate(?string $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;
        return $value === '' ? null : $value;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private static function floatOrNull(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }
}

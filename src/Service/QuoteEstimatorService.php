<?php


namespace App\Service;

use App\Entity\Quote;
use App\Enum\Company;

class QuoteEstimatorService
{
    public function getOffers(Quote $quote): array
    {
        $basePrice = $this->calculateBasePrice($quote);

        return [
            [
                'code' => 'tiers',
                'title' => 'Offre Tiers',
                'description' => 'Protection de base avec responsabilité civile.',
                'annual_price' => $basePrice,
                'monthly_price' => round($basePrice / 12, 2),
            ],
            [
                'code' => 'intermediaire',
                'title' => 'Offre Intermédiaire',
                'description' => 'Protection élargie avec garanties complémentaires.',
                'annual_price' => $basePrice + 1200,
                'monthly_price' => round(($basePrice + 1200) / 12, 2),
            ],
            [
                'code' => 'tous_risques',
                'title' => 'Offre Tous Risques',
                'description' => 'Couverture complète pour le véhicule et le conducteur.',
                'annual_price' => $basePrice + 2500,
                'monthly_price' => round(($basePrice + 2500) / 12, 2),
            ],
        ];
    }

    private function calculateBasePrice(Quote $quote): float
    {
        $price = 2500;

        $birthDate = $quote->getBirthDate();
        $licenseDate = $quote->getLicenseDate();
        $today = new \DateTimeImmutable();

        $age = $birthDate ? $birthDate->diff($today)->y : 30;
        $licenseYears = $licenseDate ? $licenseDate->diff($today)->y : 5;

        if ($age < 25) {
            $price += 1200;
        }

        if ($licenseYears < 2) {
            $price += 900;
        }

        if ($quote->getInsuranceType()?->value === 'auto') {
            $price += 800;

            if ($quote->getFiscalPower() !== null && $quote->getFiscalPower() > 8) {
                $price += 700;
            }
        }

        if ($quote->getInsuranceType()?->value === 'moto') {
            $price += 500;

            if ($quote->getEngineCapacity() !== null && $quote->getEngineCapacity() > 600) {
                $price += 900;
            }
        }

        if ($quote->getNewValue() !== null && $quote->getNewValue() > 200000) {
            $price += 1000;
        }

        if ($quote->getMarketValue() !== null && $quote->getMarketValue() > 100000) {
            $price += 600;
        }
        
    $price += $this->companyPriceAdjustment($quote->getCompany());
    return $price;
}

private function companyPriceAdjustment(Company $company): int
    {
        return match ($company) {
            Company::Axa_Assurance => 500,
            Company::Wafa_Assurance => 0,
            Company::RMA => -300,
            default => 0,
        };
    }
}




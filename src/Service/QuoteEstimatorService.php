<?php


namespace App\Service;

use App\Entity\Company;
use App\Entity\Quote;
use App\Repository\OfferRepository;
use App\Repository\CompanyOfferVariationRepository;

class QuoteEstimatorService
{
    public function __construct(
        private readonly OfferRepository                 $offerRepository,
        private readonly CompanyOfferVariationRepository $variationRepository,
    ) {}

    public function getOffers(Quote $quote): array
    {
        return $this->buildOffers($this->offerRepository->findAllActive(), $this->calculateAdjustment($quote));
    }

    public function getOffersByCompany(Quote $quote, Company $company): array
    {
        $baseAdjustment = $this->calculateAdjustment($quote);
        $dbOffers       = $this->offerRepository->findAllActive();
        $variations     = $this->variationRepository->findActiveByCompanyIndexedByOffer($company);

        $offers = [];
        foreach ($dbOffers as $offer) {
            $annualPrice = max(0.0, (float) $offer->getAnnualPrice() + $baseAdjustment);

            if (isset($variations[$offer->getId()])) {
                $annualPrice = $variations[$offer->getId()]->applyTo($annualPrice);
            }

            $annualPrice  = round($annualPrice, 2);
            $offers[] = [
                'code'          => $offer->getCode(),
                'title'         => $offer->getTitle(),
                'description'   => $offer->getDescription(),
                'annual_price'  => $annualPrice,
                'monthly_price' => round($annualPrice / 12, 2),
            ];
        }
        return $offers;
    }

    private function buildOffers(array $dbOffers, int $adjustment): array
    {
        $offers = [];
        foreach ($dbOffers as $offer) {
            $annualPrice = max(0, (float) $offer->getAnnualPrice() + $adjustment);
            $offers[] = [
                'code'          => $offer->getCode(),
                'title'         => $offer->getTitle(),
                'description'   => $offer->getDescription(),
                'annual_price'  => round($annualPrice, 2),
                'monthly_price' => round($annualPrice / 12, 2),
            ];
        }
        return $offers;
    }

    private function calculateAdjustment(Quote $quote): int
    {
        $adjustment = 0;

        $birthDate = $quote->getBirthDate();
        $licenseDate = $quote->getLicenseDate();
        $today = new \DateTimeImmutable();

        $age = $birthDate ? $birthDate->diff($today)->y : 30;
        $licenseYears = $licenseDate ? $licenseDate->diff($today)->y : 5;

        if ($age < 25) {
            $adjustment += 1200;
        }

        if ($licenseYears < 2) {
            $adjustment += 900;
        }

        if ($quote->getInsuranceType()?->value === 'auto') {
            $adjustment += 800;

            if ($quote->getFiscalPower() !== null && $quote->getFiscalPower() > 8) {
                $adjustment += 700;
            }
        }

        if ($quote->getInsuranceType()?->value === 'moto') {
            $adjustment += 500;

            if ($quote->getEngineCapacity() !== null && $quote->getEngineCapacity() > 600) {
                $adjustment += 900;
            }
        }

        if ($quote->getNewValue() !== null && $quote->getNewValue() > 200000) {
            $adjustment += 1000;
        }

        if ($quote->getMarketValue() !== null && $quote->getMarketValue() > 100000) {
            $adjustment += 600;
        }
        
    return $adjustment;
    }
    }





<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Quote;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class QuoteMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $senderEmail = 'noreply@aksam-assurance.ma',
        private readonly string $senderName  = 'Aksam Assurance',
    ) {}

    public function sendRecap(Quote $quote): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($quote->getEmail(), $quote->getFirstName() . ' ' . $quote->getLastName()))
            ->replyTo(new Address('contact@aksam-assurance.ma', $this->senderName))
            ->subject('Votre devis #' . $quote->getId() . ' — Aksam Assurance')
            ->htmlTemplate('emails/quote_recap.html.twig')
            ->context([
                'quote'       => $quote,
                'companyName' => $this->resolveCompanyName($quote),
                'cityName'    => $this->resolveCityName($quote),
                'estimation'  => $quote->getCustomEstimation()
                    ? number_format((float) $quote->getCustomEstimation(), 2, ',', ' ') . ' MAD / an'
                    : null,
                'monthly'     => $quote->getCustomEstimation()
                    ? number_format((float) $quote->getCustomEstimation() / 12, 2, ',', ' ') . ' MAD / mois'
                    : null,
            ]);

        $this->mailer->send($email);
    }

    private function resolveCompanyName(Quote $quote): string
    {
        if ($quote->getCompanyEntity()) return $quote->getCompanyEntity()->getName();
        if ($quote->getCompany())       return $quote->getCompany()->value;
        return '';
    }

    private function resolveCityName(Quote $quote): string
    {
        if ($quote->getCityEntity()) return $quote->getCityEntity()->getName();
        if ($quote->getCity())       return $quote->getCity()->value;
        return '';
    }
}

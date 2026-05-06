<?php



namespace App\Service;

use App\Entity\Quote;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class QuoteMailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {}

    public function sendRecap(Quote $quote): void
    {
        if (!$quote->getEmail()) {
            throw new \LogicException('Aucun email renseigné pour ce devis.');
        }

        $companyName = '';
        if ($quote->getCompanyEntity())  $companyName = $quote->getCompanyEntity()->getName();
        elseif ($quote->getCompany())    $companyName = $quote->getCompany()->value;

        $cityName = '';
        if ($quote->getCityEntity())     $cityName = $quote->getCityEntity()->getName();
        elseif ($quote->getCity())       $cityName = $quote->getCity()->value;

        // Toujours définir — null si pas encore d'estimation
        $estimation = null;
        $monthly    = null;
        if ($quote->getCustomEstimation()) {
            $estimation = number_format((float) $quote->getCustomEstimation(), 2, ',', ' ') . ' MAD / an';
            $monthly    = number_format((float) $quote->getCustomEstimation() / 12, 2, ',', ' ') . ' MAD / mois';
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@aksam-assurance.ma', 'Aksam Assurance'))
            ->to(new Address($quote->getEmail(), $quote->getFirstName() . ' ' . $quote->getLastName()))
            ->replyTo('contact@aksam-assurance.ma')
            ->subject('Votre devis #' . $quote->getId() . ' — Aksam Assurance')
            ->htmlTemplate('emails/quote_recap.html.twig')
            ->context([
                'quote'       => $quote,
                'companyName' => $companyName,
                'cityName'    => $cityName,
                'estimation'  => $estimation,
                'monthly'     => $monthly,
            ]);

        $this->mailer->send($email);
    }
}
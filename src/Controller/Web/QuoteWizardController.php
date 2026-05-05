<?php



namespace App\Controller\Web;

use App\DTO\QuoteRequest;
use App\Entity\Quote;
use App\Enum\VehiculeBrand;
use App\Enum\FuelType;
use App\Enum\QuoteStatus;
use App\Repository\CityRepository;
use App\Repository\CompanyRepository;
use App\Repository\QuoteRepository;
use App\Service\QuoteEstimatorService;
use App\Service\QuoteMapper;
use App\Service\QuoteMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\QuotePdfService;

class QuoteWizardController extends AbstractController
{
    #[Route('/devis/nouveau', name: 'quote_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ValidatorInterface $validator,
        QuoteMapper $mapper,
        EntityManagerInterface $entityManager,
        CityRepository $cityRepository,
        CompanyRepository $companyRepository,
    ): Response {
        $formData = $request->isMethod('POST') ? $request->request->all() : [];
        $dto = QuoteRequest::fromArray($formData);
        $errors = [];

        if ($request->isMethod('POST')) {
            $violations = $validator->validate($dto);

            if (count($violations) === 0) {
                $quote = $mapper->mapToEntity($dto, null, $cityRepository, $companyRepository);
                $entityManager->persist($quote);
                $entityManager->flush();

                $this->addFlash('success', 'Votre demande de devis a bien été enregistrée.');

                return $this->redirectToRoute('quote_offers', ['id' => $quote->getId()]);
            }

            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
        }

        return $this->render('quote/new.html.twig', [
            'cities' => $cityRepository->findActive(),
            'fuelTypes' => FuelType::cases(),
            'vehiculeBrands' => VehiculeBrand::cases(),
            'data' => $dto->toArray(),
            'errors' => $errors,
        ]);
    }

    #[Route('/devis/{id}', name: 'quote_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Quote $quote, QuoteMapper $mapper): Response
    {
        return $this->render('quote/show.html.twig', [
            'quote' => $mapper->toArray($quote),
        ]);
    }
     #[Route('/devis/{id}/recap-pdf', name: 'quote_recap_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadRecap(
        Quote $quote,
        QuoteMapper $mapper,
        QuotePdfService $pdfService,
    ): Response {
        $quoteArray = $mapper->toArray($quote);
        $pdfContent = $pdfService->generateRecap($quote, $quoteArray);
 
        $filename = sprintf('devis_%d_recap.pdf', $quote->getId());
 
        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($pdfContent),
        ]);
    }

    #[Route('/devis/{id}/offres', name: 'quote_offers', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function offers(
        Quote $quote,
        QuoteEstimatorService $estimator,
        QuoteMapper $mapper,
        CompanyRepository $companyRepository,
    ): Response {
        return $this->render('quote/offers.html.twig', [
            'quote' => $mapper->toArray($quote),
            'offers' => $estimator->getOffers($quote),
            'companies' => $companyRepository->findActive(),
        ]);
    }

    #[Route('/devis/{id}/choisir-offre', name: 'quote_select_offer', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function selectOffer(
        Quote $quote,
        Request $request,
        EntityManagerInterface $entityManager,
        QuoteEstimatorService $estimator,
    ): Response {
        $offerCode = $request->request->get('offer_code');

        if ($offerCode) {
            // Calcul du prix de l'offre choisie
            $company = $quote->getCompanyEntity();
            $offers  = $company
                ? $estimator->getOffersByCompany($quote, $company)
                : $estimator->getOffers($quote);

            $price = null;
            foreach ($offers as $offer) {
                if ($offer['code'] === $offerCode) {
                    $price = $offer['annual_price'];
                    break;
                }
            }

            $quote->setSelectedOffer($offerCode);
            $quote->setStatus(QuoteStatus::SUBMITTED);
            if ($price !== null) {
                $quote->setCustomEstimation((string) $price);
            }
            $quote->touch();
            $entityManager->flush();

            $this->addFlash('success', 'Offre "' . ucfirst($offerCode) . '" sélectionnée avec succès!');
        }

        return $this->redirectToRoute('quote_show', ['id' => $quote->getId()]);
    }

    #[Route('/devis/{id}/offres-by-company', name: 'quote_offers_by_company', requirements: ['id' => '\\d+'], methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])]
    public function offersByCompany(
        Quote $quote,
        Request $request,
        QuoteEstimatorService $estimator,
        CompanyRepository $companyRepository,
    ): JsonResponse {
        $companyName = $request->query->get('company');

        if ($companyName) {
            $company = $companyRepository->findOneBy(['name' => $companyName, 'isActive' => true]);

            if (!$company) {
                return new JsonResponse(['success' => false, 'message' => 'Compagnie introuvable.'], 404);
            }

            $offers = $estimator->getOffersByCompany($quote, $company);

            return new JsonResponse(['success' => true, 'offers' => $offers]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Company not provided',
        ], 400);
    }


    #[Route('/devis/{id}/envoyer-email', name: 'quote_send_email', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function sendEmail(
        Quote $quote,
        Request $request,
        EntityManagerInterface $em,
        QuoteMailerService $mailer,
    ): Response {
        $email = trim($request->request->get('email', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Adresse email invalide.');
            return $this->redirectToRoute('quote_show', ['id' => $quote->getId()]);
        }

        $quote->setEmail($email);
        $em->flush();

        try {
            $mailer->sendRecap($quote);
            $this->addFlash('success', 'Le récapitulatif a été envoyé à ' . $email . '.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('quote_show', ['id' => $quote->getId()]);
    }

}

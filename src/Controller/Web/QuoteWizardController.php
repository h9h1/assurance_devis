<?php



namespace App\Controller\Web;

use App\DTO\QuoteRequest;
use App\Entity\Quote;
use App\Enum\City;
use App\Enum\VehiculeBrand;
use App\Enum\Company;
use App\Enum\FuelType;
use App\Enum\QuoteStatus;
use App\Repository\QuoteRepository;
use App\Service\QuoteEstimatorService;
use App\Service\QuoteMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuoteWizardController extends AbstractController
{
    #[Route('/devis/nouveau', name: 'quote_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ValidatorInterface $validator,
        QuoteMapper $mapper,
        EntityManagerInterface $entityManager,
    ): Response {
        $formData = $request->isMethod('POST') ? $request->request->all() : [];
        $dto = QuoteRequest::fromArray($formData);
        $errors = [];

        if ($request->isMethod('POST')) {
            $violations = $validator->validate($dto);

            if (count($violations) === 0) {
                $quote = $mapper->mapToEntity($dto);
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
            'cities' => City::cases(),
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

    #[Route('/devis/{id}/offres', name: 'quote_offers', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function offers(
        Quote $quote,
        QuoteEstimatorService $estimator,
        QuoteMapper $mapper
    ): Response {
        return $this->render('quote/offers.html.twig', [
            'quote' => $mapper->toArray($quote),
            'offers' => $estimator->getOffers($quote),
            'companies' => Company::cases(),
        ]);
    }

    #[Route('/devis/{id}/choisir-offre', name: 'quote_select_offer', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function selectOffer(
        Quote $quote,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $offerCode = $request->request->get('offer_code');

        if ($offerCode) {
            $quote->setSelectedOffer($offerCode);
            $quote->setStatus(QuoteStatus::SUBMITTED);
            $quote->touch();
            $entityManager->flush();

            $this->addFlash('success', 'Offre "' . ucfirst($offerCode) . '" sélectionnée avec succès!');
        }

        return $this->redirectToRoute('quote_show', ['id' => $quote->getId()]);
    }

    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function home(): Response
    {
        return $this->redirectToRoute('quote_new');
    }

    
}
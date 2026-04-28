<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        return $this->render('admin/login.html.twig', [
            'error'        => $authenticationUtils->getLastAuthenticationError(),
            'lastUsername' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout')]
    public function logout(): void
    {
        throw new \LogicException('Ce code ne devrait jamais être atteint.');
    }
}

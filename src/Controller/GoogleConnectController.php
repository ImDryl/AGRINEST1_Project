<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleConnectController extends AbstractController
{
    #[Route('/connect/google', name: 'app_connect_google')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(
                ['email', 'profile'],
                [
                    'prompt' => 'select_account',
                ]
            );
    }

    #[Route('/connect/google/check', name: 'app_connect_google_check')]
    public function connectCheck(): Response
    {
        throw new \LogicException('Google OAuth check route should be handled by the authenticator.');
    }
}

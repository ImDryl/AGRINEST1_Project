<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

final class ApiLoginAliasController extends AbstractController
{
    #[Route('/api/login_checkout', name: 'api_login_checkout_alias', methods: ['POST'])]
    public function loginCheckoutAlias(): RedirectResponse
    {
        // Keep POST method/body while forwarding common typo endpoint to the real JSON login check path.
        return new RedirectResponse('/api/login_check', 307);
    }
}


<?php

namespace App\Controller;

use App\Payment\OrderPaymentMethods;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/payment-methods')]
final class ApiPaymentController extends AbstractController
{
    #[Route('', name: 'api_payment_methods', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'Payment methods fetched successfully.',
            'data' => [
                'items' => OrderPaymentMethods::apiItems(),
            ],
            'errors' => [],
        ]);
    }
}

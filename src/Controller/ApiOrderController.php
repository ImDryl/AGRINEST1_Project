<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Payment\OrderPaymentMethods;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/orders')]
final class ApiOrderController extends AbstractController
{
    public function __construct(private OrderRepository $orderRepository)
    {
    }

    #[Route('', name: 'api_orders_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $orders = $this->orderRepository->findForCustomer($user);

        return $this->json([
            'success' => true,
            'message' => 'Orders fetched successfully.',
            'data' => [
                'items' => array_map(fn (Order $order) => $this->serializeOrderSummary($order), $orders),
            ],
            'errors' => [],
        ]);
    }

    #[Route('/{id}', name: 'api_orders_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $order = $this->orderRepository->findOneForCustomer($id, $user);

        if (!$order instanceof Order) {
            return $this->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return $this->json([
            'success' => true,
            'message' => 'Order fetched successfully.',
            'data' => $this->serializeOrderDetail($order, $request),
            'errors' => [],
        ]);
    }

    private function serializeOrderSummary(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'status' => OrderPaymentMethods::resolveStatus($order),
            'paymentMethod' => OrderPaymentMethods::resolvePaymentLabel($order),
            'total' => (float) $order->getTotal(),
            'orderDate' => $order->getOrderDate()?->format(\DateTimeInterface::ATOM),
            'itemCount' => $order->getOrderItems()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrderDetail(Order $order, Request $request): array
    {
        $summary = $this->serializeOrderSummary($order);
        $items = [];

        foreach ($order->getOrderItems() as $item) {
            $product = $item->getProduct();
            $imageUrl = null;
            if ($product && $product->getImage()) {
                $imageUrl = $request->getSchemeAndHttpHost() . '/uploads/images/' . $product->getImage();
            }

            $items[] = [
                'productId' => $product?->getId(),
                'name' => $product?->getName() ?? 'Product',
                'quantity' => $item->getQuantity(),
                'price' => (float) $item->getPrice(),
                'subtotal' => (float) $item->getPrice() * $item->getQuantity(),
                'imageUrl' => $imageUrl,
            ];
        }

        $summary['customerName'] = $order->getCustomerName();
        $summary['customerEmail'] = $order->getCustomerEmail();
        $summary['customerPhone'] = $order->getCustomerPhone();
        $summary['items'] = $items;

        return $summary;
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required.');
        }

        return $user;
    }
}

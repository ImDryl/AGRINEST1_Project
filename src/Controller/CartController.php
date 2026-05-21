<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Payment\OrderPaymentMethods;
use App\Repository\ProductRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    public function __construct(private ActivityLogService $activityLogService)
    {
    }

    #[Route('', name: 'app_cart_index', methods: ['GET'])]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        [$items, $total] = $this->buildCartView($session, $productRepository);

        return $this->render('cart/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        Product $product,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        $isAjax = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        if (!$this->isCsrfTokenValid('cart_add_' . $product->getId(), (string) $request->request->get('_token'))) {
            if ($isAjax) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid request token. Please refresh and try again.',
                ], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', 'Invalid request token. Please try again.');

            return $this->redirectToRoute('app_product_index');
        }

        $quantityToAdd = max(1, (int) $request->request->get('quantity', 1));
        $cart = $session->get('cart', []);
        $currentQty = (int) ($cart[$product->getId()] ?? 0);
        $stock = (int) ($product->getQuantity() ?? 0);

        if ($stock <= 0) {
            if ($isAjax) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'This product is out of stock.',
                ], Response::HTTP_CONFLICT);
            }
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $reservedQty = min($quantityToAdd, $stock);
        $newQty = $currentQty + $reservedQty;

        if ($reservedQty <= 0) {
            if ($isAjax) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Unable to reserve stock for this product.',
                ], Response::HTTP_CONFLICT);
            }
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        $cart[$product->getId()] = $newQty;
        $session->set('cart', $cart);
        $product->setQuantity($stock - $reservedQty);
        $entityManager->persist($product);
        $entityManager->flush();

        if ($isAjax) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Added to cart.',
                'productId' => $product->getId(),
                'cartQuantityForProduct' => $newQty,
                'cartTotalItems' => array_sum(array_map('intval', $cart)),
                'remainingStock' => (int) ($product->getQuantity() ?? 0),
            ]);
        }

        $redirect = (string) $request->request->get('_redirect');
        if ($redirect !== '') {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(
        Product $product,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('cart_update_' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token. Please try again.');

            return $this->redirectToRoute('app_cart_index');
        }

        $cart = $session->get('cart', []);
        $currentQty = (int) ($cart[$product->getId()] ?? 0);
        $requestedQty = (int) $request->request->get('quantity', 1);
        $stock = (int) ($product->getQuantity() ?? 0);

        if ($requestedQty <= 0) {
            // Removing from cart returns reserved stock.
            if ($currentQty > 0) {
                $product->setQuantity($stock + $currentQty);
                $entityManager->persist($product);
            }
            unset($cart[$product->getId()]);
        } else {
            $delta = $requestedQty - $currentQty;
            if ($delta > 0) {
                // Need to reserve additional units.
                $canReserve = min($delta, $stock);
                $requestedQty = $currentQty + $canReserve;
                if ($canReserve > 0) {
                    $product->setQuantity($stock - $canReserve);
                    $entityManager->persist($product);
                }
            } elseif ($delta < 0) {
                // Quantity reduced, return difference to stock.
                $returnQty = abs($delta);
                $product->setQuantity($stock + $returnQty);
                $entityManager->persist($product);
            }
            if ($requestedQty > 0) {
                $cart[$product->getId()] = $requestedQty;
            } else {
                unset($cart[$product->getId()]);
            }
        }

        $session->set('cart', $cart);
        $entityManager->flush();

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(
        Product $product,
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('cart_remove_' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token. Please try again.');

            return $this->redirectToRoute('app_cart_index');
        }

        $cart = $session->get('cart', []);
        $currentQty = (int) ($cart[$product->getId()] ?? 0);
        if ($currentQty > 0) {
            $stock = (int) ($product->getQuantity() ?? 0);
            $product->setQuantity($stock + $currentQty);
            $entityManager->persist($product);
            $entityManager->flush();
        }
        unset($cart[$product->getId()]);
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->isCsrfTokenValid('cart_clear', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request token. Please try again.');

            return $this->redirectToRoute('app_cart_index');
        }

        $cart = $session->get('cart', []);
        if (is_array($cart) && $cart !== []) {
            $productIds = array_map('intval', array_keys($cart));
            $products = $productRepository->findBy(['id' => $productIds]);
            $productMap = [];
            foreach ($products as $product) {
                $productMap[$product->getId()] = $product;
            }

            foreach ($cart as $productId => $quantity) {
                $productId = (int) $productId;
                $quantity = max(0, (int) $quantity);
                if ($quantity <= 0) {
                    continue;
                }
                $product = $productMap[$productId] ?? null;
                if (!$product) {
                    continue;
                }
                $stock = (int) ($product->getQuantity() ?? 0);
                $product->setQuantity($stock + $quantity);
                $entityManager->persist($product);
            }
            $entityManager->flush();
        }

        $session->remove('cart');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/checkout', name: 'app_cart_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        SessionInterface $session,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ): Response {
        [$items, $total] = $this->buildCartView($session, $productRepository);
        if ($items === []) {
            $this->addFlash('warning', 'Your cart is empty.');

            return $this->redirectToRoute('app_product_index');
        }

        $formData = [
            'customer_name' => (string) $request->request->get('customer_name', ''),
            'customer_email' => (string) $request->request->get('customer_email', (string) ($this->getUser()?->getUserIdentifier() ?? '')),
            'customer_phone' => (string) $request->request->get('customer_phone', ''),
            'payment_method' => (string) $request->request->get('payment_method', ''),
        ];
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('cart_checkout', (string) $request->request->get('_token'))) {
                $errors[] = 'Invalid request token. Please try again.';
            }
            if (trim($formData['customer_name']) === '') {
                $errors[] = 'Customer name is required.';
            }
            $phone = trim($formData['customer_phone']);
            if ($phone === '') {
                $errors[] = 'Customer phone is required.';
            } elseif (!$this->isValidPhoneNumber($phone)) {
                $errors[] = 'Please enter a valid phone number (digits only, optional +, spaces, or hyphens).';
            } else {
                $formData['customer_phone'] = $phone;
            }
            if (!filter_var($formData['customer_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }

            $paymentKey = strtolower(trim($formData['payment_method']));
            $paymentLabel = OrderPaymentMethods::labelForKey($paymentKey);
            if ($paymentLabel === null) {
                $errors[] = 'Please select a valid payment method.';
            }

            if ($errors === []) {
                $order = new Order();
                $order->setCustomerName(trim($formData['customer_name']));
                $order->setCustomerEmail(mb_strtolower(trim($formData['customer_email'])));
                $order->setCustomerPhone(trim($formData['customer_phone']));
                $order->setStatus('Pending');
                $order->setPaymentMethod($paymentLabel);
                $order->setOrderDate(new \DateTime());

                if ($this->getUser() instanceof \App\Entity\User) {
                    $order->setCreatedBy($this->getUser());
                }

                $cart = $session->get('cart', []);
                $productIds = array_map('intval', array_keys($cart));
                $products = $productIds === [] ? [] : $productRepository->findBy(['id' => $productIds]);
                $productMap = [];
                foreach ($products as $product) {
                    $productMap[$product->getId()] = $product;
                }

                foreach ($cart as $productId => $quantity) {
                    $productId = (int) $productId;
                    $quantity = (int) $quantity;
                    $product = $productMap[$productId] ?? null;
                    if (!$product || $quantity <= 0) {
                        continue;
                    }

                    $orderItem = new OrderItem();
                    $orderItem->setProduct($product);
                    $orderItem->setQuantity($quantity);
                    $orderItem->setPrice((string) $product->getPrice());
                    $order->addOrderItem($orderItem);
                }

                if ($errors === [] && $order->getOrderItems()->count() > 0) {
                    $order->calculateTotal();
                    $entityManager->persist($order);
                    $entityManager->flush();

                    $this->activityLogService->log('Create', 'Order', $order->getId(), sprintf('Order %s created from cart checkout', (string) $order->getOrderNumber()));
                    $session->remove('cart');
                    $this->addFlash('success', sprintf('Order placed successfully! Order number: %s', (string) $order->getOrderNumber()));

                    return $this->redirectToRoute('app_product_index');
                }
            }
        }

        return $this->render('cart/checkout.html.twig', [
            'items' => $items,
            'total' => $total,
            'formData' => $formData,
            'errors' => $errors,
            'paymentMethods' => OrderPaymentMethods::apiItems(),
        ]);
    }

    /**
     * @return array{0: array<int, array{product: Product, quantity: int, subtotal: float}>, 1: float}
     */
    private function buildCartView(SessionInterface $session, ProductRepository $productRepository): array
    {
        $cart = $session->get('cart', []);
        if (!is_array($cart) || $cart === []) {
            return [[], 0.0];
        }

        $productIds = array_map('intval', array_keys($cart));
        $products = $productRepository->findBy(['id' => $productIds]);
        $productMap = [];
        foreach ($products as $product) {
            $productMap[$product->getId()] = $product;
        }

        $items = [];
        $total = 0.0;
        $normalizedCart = [];
        foreach ($cart as $productId => $quantity) {
            $productId = (int) $productId;
            $quantity = max(1, (int) $quantity);
            $product = $productMap[$productId] ?? null;
            if (!$product) {
                continue;
            }

            $price = (float) $product->getPrice();
            $subtotal = $price * $quantity;
            $availableStock = max(0, (int) $product->getQuantity());
            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'maxQuantity' => $quantity + $availableStock,
                'availableStock' => $availableStock,
            ];
            $normalizedCart[$productId] = $quantity;
            $total += $subtotal;
        }

        $session->set('cart', $normalizedCart);

        return [$items, $total];
    }

    private function isValidPhoneNumber(string $phone): bool
    {
        if (!preg_match('/^\+?[0-9][0-9\s-]*$/', $phone)) {
            return false;
        }

        $digitsOnly = preg_replace('/\D/', '', $phone);
        if (!is_string($digitsOnly)) {
            return false;
        }

        $length = strlen($digitsOnly);

        return $length >= 10 && $length <= 15;
    }
}

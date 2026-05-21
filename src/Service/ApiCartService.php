<?php

namespace App\Service;

use App\Payment\OrderPaymentMethods;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Stateless API cart backed by cache (per user id). Mirrors web session cart behavior.
 */
final class ApiCartService
{
    private const CACHE_TTL = 604800; // 7 days

    public function __construct(
        private CacheItemPoolInterface $cache,
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private ActivityLogService $activityLogService,
    ) {
    }

    public function getCartView(User $user, Request $request): array
    {
        [$items, $total, $totalItems] = $this->buildCartView($user, $request);

        return [
            'items' => $items,
            'total' => $total,
            'totalItems' => $totalItems,
        ];
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>, errors?: string[]}
     */
    public function add(User $user, int $productId, int $quantityToAdd, Request $request): array
    {
        $product = $this->productRepository->find($productId);
        if (!$product instanceof Product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $quantityToAdd = max(1, $quantityToAdd);
        $cart = $this->readCart($user);
        $currentQty = (int) ($cart[$product->getId()] ?? 0);
        $stock = (int) ($product->getQuantity() ?? 0);

        if ($stock <= 0) {
            return ['success' => false, 'message' => 'This product is out of stock.'];
        }

        $reservedQty = min($quantityToAdd, $stock);
        $newQty = $currentQty + $reservedQty;

        if ($reservedQty <= 0) {
            return ['success' => false, 'message' => 'Unable to reserve stock for this product.'];
        }

        $cart[$product->getId()] = $newQty;
        $this->writeCart($user, $cart);
        $product->setQuantity($stock - $reservedQty);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        [$items, $total, $totalItems] = $this->buildCartView($user, $request);

        return [
            'success' => true,
            'message' => 'Added to cart.',
            'data' => [
                'productId' => $product->getId(),
                'cartQuantityForProduct' => $newQty,
                'cartTotalItems' => $totalItems,
                'remainingStock' => (int) ($product->getQuantity() ?? 0),
                'items' => $items,
                'total' => $total,
            ],
        ];
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>, errors?: string[]}
     */
    public function update(User $user, int $productId, int $requestedQty, Request $request): array
    {
        $product = $this->productRepository->find($productId);
        if (!$product instanceof Product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $cart = $this->readCart($user);
        $currentQty = (int) ($cart[$product->getId()] ?? 0);
        $stock = (int) ($product->getQuantity() ?? 0);

        if ($requestedQty <= 0) {
            if ($currentQty > 0) {
                $product->setQuantity($stock + $currentQty);
                $this->entityManager->persist($product);
            }
            unset($cart[$product->getId()]);
        } else {
            $delta = $requestedQty - $currentQty;
            if ($delta > 0) {
                $canReserve = min($delta, $stock);
                $requestedQty = $currentQty + $canReserve;
                if ($canReserve > 0) {
                    $product->setQuantity($stock - $canReserve);
                    $this->entityManager->persist($product);
                }
            } elseif ($delta < 0) {
                $returnQty = abs($delta);
                $product->setQuantity($stock + $returnQty);
                $this->entityManager->persist($product);
            }
            if ($requestedQty > 0) {
                $cart[$product->getId()] = $requestedQty;
            } else {
                unset($cart[$product->getId()]);
            }
        }

        $this->writeCart($user, $cart);
        $this->entityManager->flush();

        [$items, $total, $totalItems] = $this->buildCartView($user, $request);

        return [
            'success' => true,
            'message' => 'Cart updated.',
            'data' => [
                'items' => $items,
                'total' => $total,
                'totalItems' => $totalItems,
            ],
        ];
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function remove(User $user, int $productId, Request $request): array
    {
        $product = $this->productRepository->find($productId);
        if (!$product instanceof Product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $cart = $this->readCart($user);
        $currentQty = (int) ($cart[$product->getId()] ?? 0);
        if ($currentQty > 0) {
            $stock = (int) ($product->getQuantity() ?? 0);
            $product->setQuantity($stock + $currentQty);
            $this->entityManager->persist($product);
            $this->entityManager->flush();
        }
        unset($cart[$product->getId()]);
        $this->writeCart($user, $cart);

        [$items, $total, $totalItems] = $this->buildCartView($user, $request);

        return [
            'success' => true,
            'message' => 'Item removed from cart.',
            'data' => [
                'items' => $items,
                'total' => $total,
                'totalItems' => $totalItems,
            ],
        ];
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function clear(User $user, Request $request): array
    {
        $cart = $this->readCart($user);
        if ($cart !== []) {
            $productIds = array_map('intval', array_keys($cart));
            $products = $this->productRepository->findBy(['id' => $productIds]);
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
                $this->entityManager->persist($product);
            }
            $this->entityManager->flush();
        }

        $this->writeCart($user, []);

        return [
            'success' => true,
            'message' => 'Cart cleared.',
            'data' => [
                'items' => [],
                'total' => 0.0,
                'totalItems' => 0,
            ],
        ];
    }

    /**
     * @param array{customer_name?: string, customer_email?: string, customer_phone?: string, payment_method?: string} $formData
     *
     * @return array{success: bool, message: string, data?: array<string, mixed>, errors?: string[]}
     */
    public function checkout(User $user, array $formData, Request $request): array
    {
        [$items, $total, ] = $this->buildCartView($user, $request);
        if ($items === []) {
            return ['success' => false, 'message' => 'Your cart is empty.', 'errors' => ['Your cart is empty.']];
        }

        $errors = [];
        $customerName = trim((string) ($formData['customer_name'] ?? ''));
        $customerEmail = trim((string) ($formData['customer_email'] ?? $user->getEmail() ?? ''));
        $customerPhone = trim((string) ($formData['customer_phone'] ?? ''));

        if ($customerName === '') {
            $errors[] = 'Customer name is required.';
        }
        if ($customerPhone === '') {
            $errors[] = 'Customer phone is required.';
        } elseif (!$this->isValidPhoneNumber($customerPhone)) {
            $errors[] = 'Please enter a valid phone number (digits only, optional +, spaces, or hyphens).';
        }
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        $paymentKey = strtolower(trim((string) ($formData['payment_method'] ?? '')));
        if ($paymentKey === '' || !OrderPaymentMethods::isValidKey($paymentKey)) {
            $errors[] = 'Please select a valid payment method.';
        }

        if ($errors !== []) {
            return ['success' => false, 'message' => 'Please fix the form errors.', 'errors' => $errors];
        }

        $paymentLabel = OrderPaymentMethods::labelForKey($paymentKey);

        $order = new Order();
        $order->setCustomerName($customerName);
        $order->setCustomerEmail(mb_strtolower($customerEmail));
        $order->setCustomerPhone($customerPhone);
        $order->setStatus('Pending');
        $order->setPaymentMethod($paymentLabel);
        $order->setOrderDate(new \DateTime());
        $order->setCreatedBy($user);

        $cart = $this->readCart($user);
        $productIds = array_map('intval', array_keys($cart));
        $products = $productIds === [] ? [] : $this->productRepository->findBy(['id' => $productIds]);
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

        if ($order->getOrderItems()->count() === 0) {
            return ['success' => false, 'message' => 'No valid items in cart.', 'errors' => ['No valid items in cart.']];
        }

        $order->calculateTotal();
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->activityLogService->log(
            'Create',
            'Order',
            $order->getId(),
            sprintf('Order %s created from mobile API checkout', (string) $order->getOrderNumber())
        );

        $this->writeCart($user, []);

        return [
            'success' => true,
            'message' => sprintf('Order placed successfully! Order number: %s', (string) $order->getOrderNumber()),
            'data' => [
                'orderId' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'total' => (float) $order->getTotal(),
                'paymentMethod' => $paymentLabel,
                'status' => $order->getStatus() ?? 'Pending',
            ],
        ];
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: float, 2: int}
     */
    private function buildCartView(User $user, Request $request): array
    {
        $cart = $this->readCart($user);
        if ($cart === []) {
            return [[], 0.0, 0];
        }

        $productIds = array_map('intval', array_keys($cart));
        $products = $this->productRepository->findBy(['id' => $productIds]);
        $productMap = [];
        foreach ($products as $product) {
            $productMap[$product->getId()] = $product;
        }

        $items = [];
        $total = 0.0;
        $totalItems = 0;
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
            $imageUrl = null;
            if ($product->getImage()) {
                $imageUrl = $request->getSchemeAndHttpHost() . '/uploads/images/' . $product->getImage();
            }

            $items[] = [
                'productId' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'availableStock' => (int) ($product->getQuantity() ?? 0),
                'category' => $product->getCategory()?->getName(),
                'imageUrl' => $imageUrl,
            ];
            $normalizedCart[$productId] = $quantity;
            $total += $subtotal;
            $totalItems += $quantity;
        }

        $this->writeCart($user, $normalizedCart);

        return [$items, $total, $totalItems];
    }

    /** @return array<int, int> */
    private function readCart(User $user): array
    {
        $item = $this->cache->getItem($this->cartKey($user));
        if (!$item->isHit()) {
            return [];
        }
        $data = $item->get();

        return is_array($data) ? $data : [];
    }

    /** @param array<int, int> $cart */
    private function writeCart(User $user, array $cart): void
    {
        $item = $this->cache->getItem($this->cartKey($user));
        $item->set($cart);
        $item->expiresAfter(self::CACHE_TTL);
        $this->cache->save($item);
    }

    private function cartKey(User $user): string
    {
        return 'api_cart_user_' . (string) $user->getId();
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

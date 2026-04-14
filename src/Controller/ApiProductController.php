<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
final class ApiProductController extends AbstractController
{
    #[Route('/products', name: 'api_products_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $search = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 9);
        $limit = max(1, min($limit, 50));

        $total = $productRepository->countShopListing($search !== '' ? $search : null);
        $totalPages = max(1, (int) ceil($total / $limit));
        $page = min($page, $totalPages);

        /** @var Product[] $products */
        $products = $productRepository->findShopListing($search !== '' ? $search : null, $page, $limit);

        $items = array_map(function (Product $product) use ($request): array {
            $imageUrl = null;
            if ($product->getImage()) {
                $imageUrl = $request->getSchemeAndHttpHost() . '/uploads/images/' . $product->getImage();
            }

            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice() !== null ? (float) $product->getPrice() : null,
                'quantity' => $product->getQuantity(),
                'category' => $product->getCategory()?->getName(),
                'imageUrl' => $imageUrl,
            ];
        }, $products);

        return $this->json([
            'success' => true,
            'message' => 'Products fetched successfully.',
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => $totalPages,
                ],
                'filters' => [
                    'q' => $search,
                ],
            ],
            'errors' => [],
        ]);
    }
}


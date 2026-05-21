<?php

namespace App\Controller;

use App\Repository\StockLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminStockLogsController extends AbstractController
{
    public function __construct(private StockLogRepository $stockLogRepository)
    {
    }

    #[Route('/admin/stock-logs', name: 'app_admin_stock_logs_index')]
    public function index(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            $this->addFlash('error', 'Access denied.');

            return $this->redirectToRoute('app_homepage');
        }

        $email = $request->query->get('email', '');
        $changeType = $request->query->get('change_type', '');
        $product = trim((string) $request->query->get('product', ''));
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');

        $qb = $this->stockLogRepository->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC');

        if ($email !== '') {
            $qb->andWhere('s.username LIKE :username')
                ->setParameter('username', '%' . $email . '%');
        }

        if ($changeType !== '') {
            $qb->andWhere('s.changeType = :changeType')
                ->setParameter('changeType', $changeType);
        }

        if ($product !== '') {
            $qb->andWhere('s.productName LIKE :productName')
                ->setParameter('productName', '%' . $product . '%');
        }

        if ($dateFrom !== '') {
            $qb->andWhere('s.createdAt >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo !== '') {
            $dateToObj = new \DateTime($dateTo);
            $dateToObj->setTime(23, 59, 59);
            $qb->andWhere('s.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateToObj);
        }

        $allUserEmails = $this->stockLogRepository->createQueryBuilder('s')
            ->select('DISTINCT s.username')
            ->orderBy('s.username', 'ASC')
            ->getQuery()
            ->getResult();

        $allChangeTypes = [
            'initial' => 'Initial stock',
            'adjustment' => 'Manual adjustment',
            'order' => 'Order deduction',
        ];

        $logs = $qb->getQuery()->getResult();

        return $this->render('admin/stock_logs/index.html.twig', [
            'logs' => $logs,
            'email' => $email,
            'changeType' => $changeType,
            'product' => $product,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'allUserEmails' => array_column($allUserEmails, 'username'),
            'allChangeTypes' => $allChangeTypes,
            'totalLogs' => count($logs),
        ]);
    }
}

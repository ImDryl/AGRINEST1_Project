<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminLogsController extends AbstractController
{
    public function __construct(private ActivityLogRepository $activityLogRepository)
    {
    }

    #[Route('/admin/logs', name: 'app_admin_logs_index')]
    public function logsIndex(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Access denied. Only administrators can view activity logs.');
            return $this->redirectToRoute('app_admin_products_index');
        }

        $email = $request->query->get('email', '');
        $action = $request->query->get('action', '');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');

        $qb = $this->activityLogRepository->createQueryBuilder('log')
            ->orderBy('log.timestamp', 'DESC');

        if ($email) {
            $qb->andWhere('log.username LIKE :username')
                ->setParameter('username', '%' . $email . '%');
        }

        if ($action) {
            $qb->andWhere('log.action = :action')
                ->setParameter('action', $action);
        }

        if ($dateFrom) {
            $qb->andWhere('log.timestamp >= :dateFrom')
                ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo) {
            $dateToObj = new \DateTime($dateTo);
            $dateToObj->setTime(23, 59, 59);
            $qb->andWhere('log.timestamp <= :dateTo')
                ->setParameter('dateTo', $dateToObj);
        }

        $allUserEmails = $this->activityLogRepository->createQueryBuilder('log')
            ->select('DISTINCT log.username')
            ->orderBy('log.username', 'ASC')
            ->getQuery()
            ->getResult();

        $allActions = ['Create', 'Update', 'Delete', 'Login', 'Logout', 'Enable', 'Disable'];
        $logs = $qb->getQuery()->getResult();
        $totalLogs = count($logs);

        return $this->render('admin/logs/index.html.twig', [
            'logs' => $logs,
            'email' => $email,
            'action' => $action,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'allUserEmails' => array_column($allUserEmails, 'username'),
            'allActions' => $allActions,
            'totalLogs' => $totalLogs,
        ]);
    }
}

<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findShopListing(?string $search, int $page, int $limit): array
    {
        $qb = $this->createShopListingQueryBuilder($search)
            ->orderBy('p.id', 'DESC')
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countShopListing(?string $search): int
    {
        $qb = $this->createShopListingQueryBuilder($search)
            ->select('COUNT(p.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function createShopListingQueryBuilder(?string $search): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.category', 'c');

        $search = $search !== null ? trim($search) : '';
        if ($search !== '') {
            $like = '%' . mb_strtolower($search) . '%';
            $qb->andWhere($qb->expr()->orX(
                'LOWER(p.name) LIKE :s',
                'LOWER(c.name) LIKE :s'
            ))
                ->setParameter('s', $like);
        }

        return $qb;
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

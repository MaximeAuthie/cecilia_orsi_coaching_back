<?php

namespace App\Repository;

use App\Entity\BannerText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BannerText>
 *
 * @method BannerText|null find($id, $lockMode = null, $lockVersion = null)
 * @method BannerText|null findOneBy(array $criteria, array $orderBy = null)
 * @method BannerText[]    findAll()
 * @method BannerText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BannerTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BannerText::class);
    }

//    /**
//     * @return BannerText[] Returns an array of BannerText objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?BannerText
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

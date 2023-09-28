<?php

namespace App\Repository;

use App\Entity\Visit;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visit>
 *
 * @method Visit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Visit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Visit[]    findAll()
 * @method Visit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visit::class);
    }

//    /**
//     * @return Visit[] Returns an array of Visit objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Visit
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * @param DateTimeImmutable $startDate
     * @return Visit[] Returns an array of Visit objects
    **/

    public function findByStartDate(DateTimeImmutable $startDate): array
    {
        return $this->createQueryBuilder('v')
        ->where('v.time_visit > :filterDate')
        ->setParameter('filterDate', $startDate)
        ->getQuery()
        ->getResult();
    }

    /**
     * @param DateTimeImmutable $startDate
     * @return Visit[] Returns an array of Visit objects
    **/

    public function findByIpAndDate(string $publicIp, DateTimeImmutable $startDate): array
    {
        return $this->createQueryBuilder('v')
        ->where('v.time_visit > :filterHour AND v.ip_visit= :ip')
        ->setParameter('ip', $publicIp)
        ->setParameter('filterHour', $startDate)
        ->getQuery()
        ->getResult();
    }
}

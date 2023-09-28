<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

   /**
    * @param int  $articleId
    * @return Comment[] Returns an array of Comment objects
    **/
   public function findValidatedByArticle(int $articleId): array
   {
       return $this->createQueryBuilder('c')
       ->where('c.user IS NOT NULL AND c.isValidated_comment=1 AND c.article=:id')
       ->setParameter('id', $articleId)
       ->getQuery()
       ->getResult();
   }

   /**
    * @return Comment[] Returns an array of Comment objects
    **/
    public function findAllModeratedComments(): array
    {
        return $this->createQueryBuilder('c')
        ->where('c.user IS NOT NULL')
        ->getQuery()
        ->getResult();
    }

//    public function findOneBySomeField($value): ?Comment
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

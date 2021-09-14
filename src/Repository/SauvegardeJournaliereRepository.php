<?php

namespace App\Repository;

use App\Entity\SauvegardeJournaliere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SauvegardeJournaliere|null find($id, $lockMode = null, $lockVersion = null)
 * @method SauvegardeJournaliere|null findOneBy(array $criteria, array $orderBy = null)
 * @method SauvegardeJournaliere[]    findAll()
 * @method SauvegardeJournaliere[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SauvegardeJournaliereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SauvegardeJournaliere::class);
    }

    // /**
    //  * @return SauvegardeJournaliere[] Returns an array of SauvegardeJournaliere objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SauvegardeJournaliere
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

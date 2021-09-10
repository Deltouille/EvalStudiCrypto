<?php

namespace App\Repository;

use App\Entity\CryptoListe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CryptoListe|null find($id, $lockMode = null, $lockVersion = null)
 * @method CryptoListe|null findOneBy(array $criteria, array $orderBy = null)
 * @method CryptoListe[]    findAll()
 * @method CryptoListe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CryptoListeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CryptoListe::class);
    }

    // /**
    //  * @return CryptoListe[] Returns an array of CryptoListe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CryptoListe
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

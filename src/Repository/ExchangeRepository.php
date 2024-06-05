<?php

namespace App\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Exchange;

/**
 * @extends ServiceEntityRepository<Exchange>
 */
class ExchangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exchange::class);
    }

    public function findExchangeRateInCache(DateTime $date, string $currency, string $baseCurrency): ?Exchange
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date = :date')
            ->andWhere('e.currency = :currency')
            ->andWhere('e.baseCurrency = :baseCurrency')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('currency', $currency)
            ->setParameter('baseCurrency', $baseCurrency)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws \Exception
     */
    public function saveExchangeRateBatchToCache(DateTime $date, array $currencyMap, string $baseCurrency): void
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->andWhere('e.date = :date')
            ->setParameter('date', $date->format('Y-m-d'));

        $cashedRates = [];
        foreach ( $queryBuilder->getQuery()->getResult() as $entity )
        {
            $cashedRates[$entity->getCurrency()] = $entity->getRate();
        }

        foreach ( $currencyMap as $currency => $rate )
        {
            if ( isset( $cashedRates[$currency] ) )
            {
                continue;
            }

            $exchange = new Exchange();
            $exchange->setDate($date);
            $exchange->setCurrency($currency);
            $exchange->setRate($rate);
            $exchange->setBaseCurrency($baseCurrency);

            $this->getEntityManager()->persist($exchange);
        }

        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return Exchange[] Returns an array of Exchange objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Exchange
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

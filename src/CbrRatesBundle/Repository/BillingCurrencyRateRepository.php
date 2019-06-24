<?php
namespace CbrRatesBundle\Repository;

use CbrRatesBundle\Entity\BillingCurrencyRate;
use Doctrine\ORM\EntityRepository;
use CbrRatesBundle\Entity\BillingCurrency;

/**
 * Class BillingCurrencyRateRepository
 */
class BillingCurrencyRateRepository extends EntityRepository
{
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getRatesQb()
    {
        return $this->createQueryBuilder('bcr')
            ->orderBy('bcr.id', 'DESC')
        ;
    }

    /**
     * @param BillingCurrency|string $currencyFrom
     * @param BillingCurrency|string $currencyTo
     * @param \DateTime              $date
     *
     * @return float
     */
    public function getRate($currencyFrom, $currencyTo, \DateTime $date = null)
    {
        /** @var BillingCurrencyRate $rate */
        $rate = $this->createQueryBuilder('bcr')
            ->andWhere('bcr.currencyFrom = :curFrom')
            ->andWhere('bcr.currencyTo = :curTo')
            ->andWhere('bcr.date <= :date')
            ->setParameters([
                'curFrom' => $currencyFrom,
                'curTo' => $currencyTo,
                'date' => $date ?: new \DateTime(),
            ])
            ->orderBy('bcr.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->useResultCache(true, 3600)
            ->getOneOrNullResult();

        return $rate ? $rate->getNormalizedValue() : null;
    }
}

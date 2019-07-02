<?php
namespace CbrRates\Repository;

use CbrRates\Entity\BillingCurrencyRate;
use Doctrine\ORM\EntityRepository;
use CbrRates\Entity\BillingCurrency;

/**
 * Class BillingCurrencyRateRepository
 */
class BillingCurrencyRateRepository extends EntityRepository
{
    /**
     * @param array $params
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getRatesQb(array $params)
    {
        $qb = $this
            ->createQueryBuilder('bcr')
        ;

        if (isset($params['rateUpperDate'])) {
            $qb
                ->andWhere('bcr.date <= :rateUpperDate')
                ->setParameter('rateUpperDate', $params['rateUpperDate']);
        }

        if (isset($params['currencyFrom'])) {
            $qb
                ->andWhere('bcr.currencyFrom = :currencyFrom')
                ->setParameter('currencyFrom', $params['currencyFrom']);
        }

        if (isset($params['currencyTo'])) {
            $qb
                ->andWhere('bcr.currencyTo = :currencyTo')
                ->setParameter('currencyTo', $params['currencyTo']);
        }

        if (isset($params['rateLowerDate'])) {
            $qb
                ->andWhere('bcr.date >= :rateLowerDate')
                ->setParameter('rateLowerDate', $params['rateLowerDate']);
        }

        if (isset($params['currencySort'])) {
            $qb
                ->addOrderBy('bcr.currencyFrom', $params['currencySort']);
        }

        if (isset($params['dateSort'])) {
            $qb
                ->addOrderBy('bcr.date', $params['dateSort']);
        }

        $qb
            ->addOrderBy('bcr.id')
        ;

        return $qb;
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
        $params = [
            'currencyFrom' => $currencyFrom,
            'currencyTo' => $currencyTo,
        ];

        /** @var BillingCurrencyRate $rate */
        $rate = $this->getRatesQb($params)
            ->andWhere('bcr.date = :rateUpperDate')
            ->setParameter('rateUpperDate', $date)
            ->orderBy('bcr.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $rate ? $rate->getNormalizedValue() : null;
    }
}

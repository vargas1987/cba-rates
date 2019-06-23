<?php
namespace CbrRatesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="billing.currency_rate")
 * @ORM\Entity(repositoryClass="CbrRatesBundle\Repository\BillingCurrencyRateRepository")
 */
class BillingCurrencyRate
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var BillingCurrency
     *
     * @ORM\ManyToOne(targetEntity="\CbrRatesBundle\Entity\BillingCurrency")
     * @ORM\JoinColumn(name="currency_from", referencedColumnName="char_code")
     */
    private $currencyFrom;

    /**
     * @var BillingCurrency
     *
     * @ORM\ManyToOne(targetEntity="\CbrRatesBundle\Entity\BillingCurrency")
     * @ORM\JoinColumn(name="currency_to", referencedColumnName="char_code")
     */
    private $currencyTo;

    /**
     * @var integer
     *
     * @ORM\Column(name="nominal", type="integer")
     */
    private $nominal;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float")
     */
    private $value;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     *
     * @return BillingCurrencyRate
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return BillingCurrency
     */
    public function getCurrencyFrom()
    {
        return $this->currencyFrom;
    }

    /**
     * @param BillingCurrency $currencyFrom
     *
     * @return BillingCurrencyRate
     */
    public function setCurrencyFrom($currencyFrom)
    {
        $this->currencyFrom = $currencyFrom;

        return $this;
    }

    /**
     * @return BillingCurrency
     */
    public function getCurrencyTo()
    {
        return $this->currencyTo;
    }

    /**
     * @param BillingCurrency $currencyTo
     *
     * @return BillingCurrencyRate
     */
    public function setCurrencyTo($currencyTo)
    {
        $this->currencyTo = $currencyTo;

        return $this;
    }

    /**
     * @return integer
     */
    public function getNominal()
    {
        return $this->nominal;
    }

    /**
     * @param integer $nominal
     *
     * @return BillingCurrencyRate
     */
    public function setNominal($nominal)
    {
        $this->nominal = $nominal;

        return $this;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     *
     * @return BillingCurrencyRate
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return float
     */
    public function getNormalizedValue()
    {
        return $this->value / $this->nominal;
    }
}

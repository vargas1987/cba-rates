<?php
namespace CbrRatesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="billing.currency")
 * @ORM\Entity(repositoryClass="CbrRatesBundle\Repository\BillingCurrencyRepository")
 */
class BillingCurrency
{
    const CODE_RUB = 'RUB';
    const CODE_EUR = 'EUR';
    const CODE_GBP = 'GBP';

    /**
     * @ORM\Column(name="char_code", type="text")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $charCode;
    /**

     * @ORM\Column(name="num_code", type="integer")
     */
    private $numCode;

    /**
     * @ORM\Column(name="name", type="text")
     */
    private $name;

    /**
     * @return string
     */
    public function getCharCode()
    {
        return $this->charCode;
    }

    /**
     * @param string $charCode
     *
     * @return BillingCurrency
     */
    public function setCharCode($charCode)
    {
        $this->charCode = $charCode;

        return $this;
    }

    /**
     * @return integer
     */
    public function getNumCode()
    {
        return $this->numCode;
    }

    /**
     * @param integer $numCode
     *
     * @return BillingCurrency
     */
    public function setNumCode($numCode)
    {
        $this->numCode = $numCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return BillingCurrency
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}

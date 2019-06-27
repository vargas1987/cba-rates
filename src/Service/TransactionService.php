<?php

namespace CbrRates\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class TransactionService
 */
class TransactionService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * TransactionService constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        $this->em->beginTransaction();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        $this->em->rollback();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        $this->em->commit();
    }
}

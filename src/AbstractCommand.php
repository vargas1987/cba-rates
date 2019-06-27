<?php

namespace CbrRates;

use CbrRates\Service\CbrService;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use CbrRates\Service\TransactionService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class AbstractCommand
 */
abstract class AbstractCommand extends ContainerAwareCommand
{
    /**
     * @param string $id
     * @return object
     */
    protected function get($id)
    {
        return $this->getContainer()->get($id);
    }

    /**
     * @return EntityManager|object
     */
    protected function getEm()
    {
        return $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * @return Logger|object
     */
    protected function getLogger()
    {
        return $this->get(Logger::class);
    }

    /**
     * @return CbrService|object
     */
    protected function getCbrService()
    {
        return $this->get(CbrService::class);
    }

    /**
     * @return TransactionService|object
     */
    protected function getTransactionService()
    {
        return $this->get(TransactionService::class);
    }
}

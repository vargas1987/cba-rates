<?php

namespace CbrRates;

use Doctrine\ORM\EntityManager;
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
}

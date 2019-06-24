<?php

namespace CbrRatesBundle\Utils;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\AdapterInterface;
use CbrRatesBundle\Exception\InvalidPagerDataException;

/**
 * Class EntityPagerAdapter
 * @package Pagerfanta\Adapter
 */
class EntityPagerAdapter implements AdapterInterface
{
    /**
     * @var QueryBuilder
     */
    private $qb;

    /**
     * @var bool
     */
    private $optimize;

    /**
     * EntityPagerAdapter constructor.
     * @param QueryBuilder $query
     * @param bool         $optimize
     */
    public function __construct($query, bool $optimize = false)
    {
        $this->qb = $query;
        $from = $query->getDQLPart('from');
        if (count($from) !== 1) {
            throw new InvalidPagerDataException('EntityPagerAdapter can use only with one from clause.');
        }
        $this->optimize = $optimize;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        $countQb = clone $this->qb;
        /** @var From $from */
        $from = current($countQb->getDQLPart('from'));
        $meta = $countQb->getEntityManager()->getClassMetadata($from->getFrom());
        if ($this->optimize) {
            $this->optimize($countQb);
        }
        $parameters = $this->getDefinedParameters();
        foreach ($this->findParametersInCond($this->getOrderByString($countQb), array_keys($parameters)) as $key => $item) {
            unset($parameters[$item]);
        }
        $countQb->setParameters($parameters);
        $countQb->select(sprintf('count(%s %s.%s) as cnt', count($countQb->getDQLPart('join')) ? 'DISTINCT' : '', $from->getAlias(), $meta->getSingleIdentifierFieldName()))
            ->resetDQLPart('orderBy')->resetDQLPart('groupBy');

        return $countQb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $countQb = clone $this->qb;
        $from = current($countQb->getDQLPart('from'));
        $meta = $countQb->getEntityManager()->getClassMetadata($from->getFrom());
        $id = $from->getAlias().'.'.$meta->getSingleIdentifierFieldName();
        if ($this->optimize) {
            $this->optimize($countQb);
        }
        /** @var Select[] $select */
        $select = $countQb->getDQLPart('select');
        $selects = [];
        $hiddenSelects = [];
        $hiddenSelectItems = [];
        /** @var OrderBy[] $orderBy */
        $orderBy = $countQb->getDQLPart('orderBy');
        $orderByItems = [];
        $orderNum = 0;
        $orderByParams = [];

        foreach ($select as $item) {
            foreach ($item->getParts() as $part) {
                $part = trim(preg_replace('/[\t\n\r\s]+/', ' ', $part));
                if (preg_match('/(.*)(as HIDDEN)\s(.*)/', $part, $matches)) {
                    $hiddenSelects[] = $matches[0];
                    $hiddenSelectItems[] = $matches[3];
                }
            }
        }

        foreach ($orderBy as $item) {
            foreach ($item->getParts() as $part) {
                $orderByParams = array_merge($orderByParams, $this->findParametersInCond($part, array_keys($this->getDefinedParameters())));
                $orderAlias = '__orderBy'.++$orderNum;
                if (preg_match('/(.*)\s(ASC|DESC),?/', $part, $matches) && !\in_array($matches[1], $hiddenSelectItems, true)) {
                    $orderByItems[] = [$orderAlias, $matches[2]];
                    $selects[] = $matches[1].' as HIDDEN '.$orderAlias;

                    continue;
                }

                $orderByItems[] = [$matches[1], $matches[2]];
            }
        }

        $selects[] = $id;
        $ids = $countQb->select('DISTINCT '.implode(', ', $selects))
            ->setFirstResult($offset)
            ->setMaxResults($length)
            ->resetDQLPart('orderBy');

        foreach ($hiddenSelects as $hiddenSelect) {
            $ids->addSelect($hiddenSelect);
        }

        foreach ($orderByItems as $orderByItem) {
            $ids->addOrderBy($orderByItem[0], $orderByItem[1]);
        }

        $ids = array_map('current', $ids->resetDQLPart('groupBy')->getQuery()->getScalarResult());
        $selectParams = [];
        /** @var Parameter $parameter */
        foreach ($this->qb->getParameters() as $parameter) {
            if (in_array($parameter->getName(), $orderByParams)) {
                $selectParams[$parameter->getName()] = $parameter->getValue();
            }
        }
        $selectParams['__ids'] = $ids;
        $resQb = (clone $this->qb)
            ->resetDQLPart('where')
            ->andWhere($id.' in (:__ids)')
            ->setParameters($selectParams);

        return $resQb->getQuery()->getResult();
    }

    /**
     * @param mixed $DQLPart
     * @param array $aliases
     * @return array
     * @throws InvalidPagerDataException
     */
    private function getAliasInUse($DQLPart, array &$aliases)
    {
        switch (true) {
            case is_array($DQLPart):
                foreach ($DQLPart as $item) {
                    $this->getAliasInUse($item, $aliases);
                }
                break;
            case is_string($DQLPart):
                foreach ($this->findAliasInCond($DQLPart, $this->qb->getAllAliases()) as $item) {
                    if (!in_array($item, $aliases)) {
                        $aliases[] = $item;
                    }
                }
                break;
            case $DQLPart instanceof Comparison:
                $this->getAliasInUse($DQLPart->getLeftExpr(), $aliases);
                $this->getAliasInUse($DQLPart->getRightExpr(), $aliases);
                break;
            case $DQLPart instanceof Func:
                foreach ($DQLPart->getArguments() as $item) {
                    $this->getAliasInUse($item, $aliases);
                }
                break;
            case $DQLPart instanceof Andx:
            case $DQLPart instanceof Orx:
                foreach ($DQLPart->getParts() as $item) {
                    $this->getAliasInUse($item, $aliases);
                }
                break;
            default:
                throw new InvalidPagerDataException('Undefined dql part '.get_class($DQLPart));
        }

        return $aliases;
    }

    /**
     * @param string     $cond
     * @param null|array $possibleAliases
     * @return array
     */
    private function findAliasInCond(string $cond, $possibleAliases = null)
    {
        $result = [];
        $restrict = $possibleAliases !== null;
        if (preg_match_all('/([a-zA-Z_][^\W\:]*)(\.[a-zA-z_][\w_]+)/', $cond, $matches)) {
            foreach ($matches[1] as $match) {
                if (!in_array($match, $result) && (!$restrict || in_array($match, $possibleAliases))) {
                    $result[] = $match;
                }
            }
        }

        return $result;
    }

    /**
     * @param string     $cond
     * @param null|array $possibleParameters
     * @return array
     */
    private function findParametersInCond(string $cond, $possibleParameters = null)
    {
        $result = [];
        $restrict = $possibleParameters !== null;
        if (preg_match_all('/\:(\w+)/', $cond, $matches)) {
            foreach ($matches[1] as $match) {
                if (!in_array($match, $result) && (!$restrict || in_array($match, $possibleParameters))) {
                    $result[] = $match;
                }
            }
        }

        return $result;
    }

    /**
     * @param Join[] $joins
     * @param string $alias
     * @param array  $aliases
     */
    private function searchJoin(array $joins, string $alias, array &$aliases)
    {
        foreach ($joins as $join) {
            if ($join->getAlias() === $alias) {
                foreach ($this->findAliasInCond($join->getJoin(), $this->qb->getAllAliases()) as $item) {
                    if (!in_array($item, $aliases)) {
                        $aliases[] = $item;
                        $this->searchJoin($joins, $item, $aliases);
                    }
                }
            }
        }
    }

    /**
     * @param QueryBuilder $countQb
     * @throws InvalidPagerDataException
     */
    private function optimize(QueryBuilder $countQb)
    {
        $from = current($countQb->getDQLPart('from'));
        $whereParts = $countQb->getDQLPart('where');
        $aliases = [];
        $joins = [];
        if ($whereParts) {
            $this->getAliasInUse($whereParts, $aliases);
            $this->getAliasInUse($this->getOrderByString($countQb), $aliases);
            /** @var Join $join */
            $joins = $countQb->getDQLPart('join')[$from->getAlias()] ?? [];
            foreach ($aliases as $alias) {
                $this->searchJoin($joins, $alias, $aliases);
            }
        }
        $countQb->resetDQLPart('join');
        foreach ($joins as $join) {
            if (in_array($join->getAlias(), $aliases)) {
                switch ($join->getJoinType()) {
                    case Join::LEFT_JOIN:
                        $countQb->leftJoin($join->getJoin(), $join->getAlias(), $join->getConditionType(), $join->getCondition(), $join->getIndexBy());
                        break;
                    case Join::INNER_JOIN:
                        $countQb->innerJoin($join->getJoin(), $join->getAlias(), $join->getConditionType(), $join->getCondition(), $join->getIndexBy());
                        break;
                }
            }
        }
    }

    /**
     * @param QueryBuilder $countQb
     * @return string
     */
    private function getOrderByString(QueryBuilder $countQb)
    {
        $orderBy = $countQb->getDQLPart('orderBy');

        return implode(', ', $orderBy);
    }

    /**
     * @return array
     */
    private function getDefinedParameters()
    {
        $parameters = [];
        /** @var Parameter $parameter */
        foreach ($this->qb->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter->getValue();
        }

        return $parameters;
    }
}

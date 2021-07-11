<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause;

use ANOITCOM\EAVBundle\EAV\ORM\Criteria\Filter\AbstractFilterCriteria\ParametersCounter;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteriaExpression;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;

class WhereInClause implements TripletClauseInterface
{

    private string $field;

    private array $values;

    private bool $isAnd;

    private ParametersCounter $parametersCounter;


    public function __construct(string $field, array $values, ParametersCounter $parametersCounter, bool $isAnd)
    {
        $this->field             = $field;
        $this->values            = $values;
        $this->parametersCounter = $parametersCounter;
        $this->isAnd             = $isAnd;
    }


    /**
     * @param QueryBuilder $qb
     *
     * @return string|CompositeExpression
     */
    public function makeExpression(QueryBuilder $qb)
    {
        $parameterName = $this->field . '_' . $this->parametersCounter->getNext();

        $expr = $qb->expr()->in($this->field, ':' . $parameterName);

        return new TripletCriteriaExpression($expr, [ [ $parameterName, $this->values, Connection::PARAM_STR_ARRAY ] ], $this->isAnd);

    }
}
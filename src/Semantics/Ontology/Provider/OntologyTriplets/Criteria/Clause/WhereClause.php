<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause;

use ANOITCOM\EAVBundle\EAV\ORM\Criteria\Filter\AbstractFilterCriteria\ParametersCounter;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteriaExpression;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;

class WhereClause implements TripletClauseInterface
{

    private string $operator;

    private string $field;

    private $value;

    private bool $isAnd;

    private ParametersCounter $parametersCounter;


    public function __construct(string $field, string $operator, $value, ParametersCounter $parametersCounter, bool $isAnd)
    {
        $this->field             = $field;
        $this->operator          = $operator;
        $this->value             = $value;
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

        $expr = $qb->expr()->comparison($this->field, $this->operator, ':' . $parameterName);

        return new TripletCriteriaExpression($expr, [ [ $parameterName, $this->value ] ], $this->isAnd);

    }
}
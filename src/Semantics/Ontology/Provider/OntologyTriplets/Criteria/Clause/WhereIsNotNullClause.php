<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause;

use ANOITCOM\EAVBundle\EAV\ORM\Criteria\Filter\AbstractFilterCriteria\ParametersCounter;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteriaExpression;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;

class WhereIsNotNullClause implements TripletClauseInterface
{

    private string $field;

    private bool $isAnd;

    private ParametersCounter $parametersCounter;


    public function __construct(string $field, ParametersCounter $parametersCounter, bool $isAnd)
    {
        $this->field             = $field;
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
        $expr = $qb->expr()->isNotNull($this->field);

        return new TripletCriteriaExpression($expr, [], $this->isAnd);

    }
}
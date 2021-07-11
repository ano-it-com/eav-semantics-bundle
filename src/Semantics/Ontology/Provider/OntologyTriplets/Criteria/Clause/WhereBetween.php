<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause;

use ANOITCOM\EAVBundle\EAV\ORM\Criteria\Filter\AbstractFilterCriteria\ParametersCounter;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteriaExpression;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;

class WhereBetween implements TripletClauseInterface
{

    private string $field;

    private $value1;

    private $value2;

    private bool $isAnd;

    private ParametersCounter $parametersCounter;


    public function __construct(string $field, $value1, $value2, ParametersCounter $parametersCounter, bool $isAnd)
    {
        $this->field             = $field;
        $this->value1            = $value1;
        $this->value2            = $value2;
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

        $expr = $qb->expr()->andX(
            $expr1 = $qb->expr()->gte($this->field, ':' . $parameterName . '1'),
            $expr2 = $qb->expr()->lte($this->field, ':' . $parameterName . '2')
        );

        return new TripletCriteriaExpression($expr, [
            [ $parameterName . '1', $this->value1 ],
            [ $parameterName . '2', $this->value2 ],
        ], $this->isAnd);

    }
}
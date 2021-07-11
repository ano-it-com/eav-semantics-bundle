<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteria;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteriaExpression;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;

class WhereComposite
{

    private TripletCriteria $criteria;

    private bool $isAnd;


    public function __construct(TripletCriteria $criteria, bool $isAnd)
    {
        $this->criteria = $criteria;
        $this->isAnd    = $isAnd;
    }


    /**
     * @param QueryBuilder $qb
     *
     * @return string|CompositeExpression
     */
    public function makeExpression(QueryBuilder $qb)
    {
        $parameters = [];

        $andExpressions = [];
        $orExpressions  = [];

        foreach ($this->criteria->getExpressions($qb) as $expression) {

            $parameters = [ ...$parameters, ...$expression->getParameters() ];

            if ($expression->isAnd()) {
                $andExpressions[] = $expression->getExpression();
            } else {
                $orExpressions[] = $expression->getExpression();
            }
        }

        $and = $qb->expr()->andX()->addMultiple($andExpressions);
        $or  = $qb->expr()->orX()->addMultiple($orExpressions);

        $composite = $qb->expr()->andX()->add($and)->add($or);

        return new TripletCriteriaExpression($composite, $parameters, $this->isAnd);
    }
}
<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria;

use Doctrine\DBAL\Query\QueryBuilder;

class TripletCriteriaHandler
{

    public function applyCriteria(QueryBuilder $qb, array $criteria): void
    {
        foreach ($criteria as $oneCriteria) {
            if ( ! $oneCriteria instanceof TripletCriteria) {
                throw new \InvalidArgumentException('Each criteria must implements TripletCriteria');
            }

            foreach ($oneCriteria->getExpressions($qb) as $expression) {

                foreach ($expression->getParameters() as $oneParameters) {
                    $qb->setParameter(...$oneParameters);
                }

                if ($expression->isAnd()) {
                    $qb->andWhere($expression->getExpression());
                } else {
                    $qb->orWhere($expression->getExpression());
                }
            }

        }
    }


    public function applyOrdering(QueryBuilder $qb, array $orderBy): void
    {
        $allowedDirections = [
            'asc',
            'desc'
        ];
        foreach ($orderBy as $field => $dir) {
            if ( ! in_array(strtolower($dir), $allowedDirections, true)) {
                $dir = 'asc';
            }

            $qb->orderBy($field, $dir);
        }

    }
}
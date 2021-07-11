<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria;

use ANOITCOM\EAVBundle\EAV\ORM\Criteria\Filter\AbstractFilterCriteria\ParametersCounter;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause\TripletClauseInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause\WhereBetween;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause\WhereClause;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause\WhereComposite;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause\WhereInClause;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause\WhereIsNotNullClause;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause\WhereIsNullClause;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\Clause\WhereNotInClause;
use Doctrine\DBAL\Query\QueryBuilder;

class TripletCriteria
{

    public const SUBJECT = 's';
    public const PREDICATE = 'p';
    public const OBJECT = 'o';

    /** @var TripletClauseInterface[] */
    protected array $clauses = [];

    protected ParametersCounter $parameterCounter;


    public function __construct()
    {
        $this->parameterCounter = new ParametersCounter();
    }


    public function where(string $field, string $operator, $value): TripletCriteria
    {
        $this->clauses[] = new WhereClause($field, $operator, $value, $this->parameterCounter, true);

        return $this;
    }


    public function orWhere(string $field, string $operator, $value): TripletCriteria
    {
        $this->clauses[] = new WhereClause($field, $operator, $value, $this->parameterCounter, false);

        return $this;
    }


    public function whereIn(string $field, array $values): TripletCriteria
    {
        $this->clauses[] = new WhereInClause($field, $values, $this->parameterCounter, true);

        return $this;

    }


    public function orWhereIn(string $field, array $values): TripletCriteria
    {
        $this->clauses[] = new WhereInClause($field, $values, $this->parameterCounter, false);

        return $this;
    }


    public function whereNotIn(string $field, array $values): TripletCriteria
    {
        $this->clauses[] = new WhereNotInClause($field, $values, $this->parameterCounter, true);

        return $this;
    }


    public function orWhereNotIn(string $field, array $values): TripletCriteria
    {
        $this->clauses[] = new WhereNotInClause($field, $values, $this->parameterCounter, false);

        return $this;
    }


    public function whereBetween(string $field, $value1, $value2): TripletCriteria
    {
        $this->clauses[] = new WhereBetween($field, $value1, $value2, $this->parameterCounter, true);

        return $this;
    }


    public function orWhereBetween(string $field, $value1, $value2): TripletCriteria
    {
        $this->clauses[] = new WhereBetween($field, $value1, $value2, $this->parameterCounter, false);

        return $this;
    }


    public function whereIsNull(string $field): TripletCriteria
    {
        $this->clauses[] = new WhereIsNullClause($field, $this->parameterCounter, true);

        return $this;
    }


    public function orWhereIsNull(string $field): TripletCriteria
    {
        $this->clauses[] = new WhereIsNullClause($field, $this->parameterCounter, false);

        return $this;
    }


    public function whereIsNotNull(string $field): TripletCriteria
    {
        $this->clauses[] = new WhereIsNotNullClause($field, $this->parameterCounter, true);

        return $this;
    }


    public function orWhereIsNotNull(string $field): TripletCriteria
    {
        $this->clauses[] = new WhereIsNotNullClause($field, $this->parameterCounter, false);

        return $this;
    }


    public function whereComposite(callable $innerCriteriaCallback): TripletCriteria
    {
        $freshCriteria = new static();

        $innerCriteriaCallback($freshCriteria);

        $this->clauses[] = new WhereComposite($freshCriteria, true);

        return $this;
    }


    public function orWhereComposite(callable $innerCriteriaCallback): TripletCriteria
    {
        $freshCriteria = new static();

        $innerCriteriaCallback($freshCriteria);

        $this->clauses[] = new WhereComposite($freshCriteria, false);

        return $this;
    }


    public function getExpressions(QueryBuilder $qb): array
    {
        $expressions = [];
        foreach ($this->clauses as $clause) {
            $expressions[] = $clause->makeExpression($qb);
        }

        return $expressions;
    }
}
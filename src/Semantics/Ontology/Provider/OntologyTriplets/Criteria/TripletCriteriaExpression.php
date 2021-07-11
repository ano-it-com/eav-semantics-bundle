<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria;

use Doctrine\DBAL\Query\Expression\CompositeExpression;

class TripletCriteriaExpression
{

    /** @var string|CompositeExpression */
    private $expression;

    private array $parameters;

    private bool $and;


    public function __construct($expression, array $parameters, bool $and)
    {
        $this->expression = $expression;
        $this->parameters = $parameters;
        $this->and        = $and;
    }


    /**
     * @return CompositeExpression|string
     */
    public function getExpression()
    {
        return $this->expression;
    }


    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }


    /**
     * @return bool
     */
    public function isAnd(): bool
    {
        return $this->and;
    }
}
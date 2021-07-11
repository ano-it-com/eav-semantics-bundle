<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy;

class SemanticProviderStrategyCollection implements \IteratorAggregate
{

    private iterable $strategyCollection;


    public function __construct(iterable $strategyCollection)
    {
        $this->strategyCollection = $strategyCollection;
    }


    public function getIterator()
    {
        return new \IteratorIterator($this->strategyCollection);
    }
}
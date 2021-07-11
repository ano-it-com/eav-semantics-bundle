<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy;

interface SemanticProviderStrategyInterface
{

    public function supportsMethod(string $method): bool;
}
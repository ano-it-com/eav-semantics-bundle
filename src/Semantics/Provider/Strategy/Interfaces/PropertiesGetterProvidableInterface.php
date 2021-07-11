<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces;

interface PropertiesGetterProvidableInterface
{

    public function hasDatatypeProperties(string $iri, array $ontologies = []): bool;


    public function hasObjectProperties(string $iri, array $ontologies = []): bool;


    public function getDatatypeProperties(string $iri, array $ontologies = []): array;


    public function getObjectProperties(string $iri, array $ontologies = []): array;

}
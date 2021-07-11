<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;

interface ClassExistenceProvidableInterface
{

    /**
     * @param string        $iri
     * @param OntologyDTO[] $ontologies
     *
     * @return bool
     */
    public function hasClass(string $iri, array $ontologies = []): bool;
}
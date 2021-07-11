<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;

interface ClassHierarchyProvidableInterface
{

    /**
     * @param string        $iri
     * @param bool          $onlyDirect
     * @param OntologyDTO[] $ontologies
     *
     * @return string[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function subClasses(string $iri, bool $onlyDirect = true, array $ontologies = []): array;


    /**
     * @param string        $iri
     * @param bool          $onlyDirect
     * @param OntologyDTO[] $ontologies
     *
     * @return string[]
     */
    public function superClasses(string $iri, bool $onlyDirect = true, array $ontologies = []): array;
}
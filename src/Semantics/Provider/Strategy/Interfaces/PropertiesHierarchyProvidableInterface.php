<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;

interface PropertiesHierarchyProvidableInterface
{

    /**
     * @param string        $iri
     * @param bool          $onlyDirect
     * @param OntologyDTO[] $ontologies
     *
     * @return string[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function subProperties(string $iri, bool $onlyDirect = true, array $ontologies = []): array;


    /**
     * @param string        $iri
     * @param bool          $onlyDirect
     * @param OntologyDTO[] $ontologies
     *
     * @return string[]
     */
    public function superProperties(string $iri, bool $onlyDirect = true, array $ontologies = []): array;

}
<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Import\OntologyReader;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\TripleDTO;

interface OntologyFileReaderInterface
{

    /**
     * @return TripleDTO[]
     */
    public function getTriples(): array;
}
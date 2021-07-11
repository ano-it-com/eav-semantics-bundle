<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Import\OntologyReader;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Import\OntologyReader\ARC2Reader\ARC2FileReader;

class OntologyFileReaderFactory
{

    public function create(string $path): OntologyFileReaderInterface
    {
        return new ARC2FileReader($path);
    }
}
<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Import\OntologyReader\ARC2Reader;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Import\OntologyReader\OntologyFileReaderInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\TripleDTO;
use ARC2;
use ARC2_RDFParser;

class ARC2FileReader implements OntologyFileReaderInterface
{

    private ARC2_RDFParser $parser;

    private bool $parsed = false;

    private string $path;


    public function __construct(string $path)
    {
        $this->parser = ARC2::getRDFParser();
        $this->path   = $path;
    }


    /**
     * @return TripleDTO[]
     */
    public function getTriples(): array
    {
        $this->parse();

        $triplesList = $this->parser->getTriples();

        $triples = [];

        foreach ($triplesList as $triple) {
            $triples[] = new TripleDTO(
                $triple['s'] !== '' ? $triple['s'] : null,
                $triple['p'] !== '' ? $triple['p'] : null,
                $triple['o'] !== '' ? $triple['o'] : null,
                $triple['s_type'] !== '' ? $triple['s_type'] : null,
                $triple['p_type'] ?? null,
                $triple['o_type'] !== '' ? $triple['o_type'] : null,
                $triple['o_datatype'] !== '' ? $triple['o_datatype'] : null,
                $triple['o_lang'] !== '' ? $triple['o_lang'] : null
            );
        }

        $bNodesHandler = new ARC2BNodesHandler($triples);

        $triples = $bNodesHandler->handle();

        return $triples;
    }


    private function parse(): void
    {
        if ($this->parsed) {
            return;
        }

        $this->parser->parse($this->path);
        $this->parsed = true;

    }
}
<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Import;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Import\OntologyReader\OntologyFileReaderFactory;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyPersister;
use Ramsey\Uuid\Uuid;

class FileOntologyImporter
{

    private OntologyFileReaderFactory $readerFactory;

    /**
     * @var OntologyPersister
     */
    private OntologyPersister $ontologyPersister;


    public function __construct(
        OntologyFileReaderFactory $readerFactory,
        OntologyPersister $ontologyPersister
    ) {
        $this->readerFactory     = $readerFactory;
        $this->ontologyPersister = $ontologyPersister;
    }


    public function readTriplesFromFile(string $path): array
    {
        $reader = $this->readerFactory->create($path);

        return $reader->getTriples();
    }


    public function import(string $path, string $namespaceId, string $iri, string $title, ?string $comment = null, ?string $externalIri = null): void
    {
        $triples  = $this->readTriplesFromFile($path);
        $ontology = new OntologyDTO(Uuid::uuid4(), $namespaceId, $title, $iri, $comment, $externalIri);

        $this->ontologyPersister->save($ontology, $triples);
    }


    public function update(string $path, string $namespaceId, string $iri, string $title, ?string $comment = null, ?string $externalIri = null): void
    {
        $triples  = $this->readTriplesFromFile($path);
        $ontology = new OntologyDTO(Uuid::uuid4(), $namespaceId, $title, $iri, $comment, $externalIri);

        $this->ontologyPersister->update($iri, $ontology, $triples);
    }
}
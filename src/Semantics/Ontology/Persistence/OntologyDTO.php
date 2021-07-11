<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence;

class OntologyDTO
{

    public string $id;

    public string $namespaceId;

    public string $title;

    public string $iri;

    public ?string $comment;

    public ?string $externalIri;


    public function __construct(
        string $id,
        string $namespaceId,
        string $title,
        string $iri,
        ?string $comment,
        ?string $externalIri
    ) {
        $this->id          = $id;
        $this->namespaceId = $namespaceId;
        $this->title       = $title;
        $this->iri         = $iri;
        $this->comment     = $comment;
        $this->externalIri = $externalIri;
    }
}
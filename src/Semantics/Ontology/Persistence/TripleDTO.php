<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence;

class TripleDTO
{

    public ?string $s;

    public ?string $p;

    public ?string $o;

    /** @var ?string "uri", "bnode", or "var" */
    public ?string $sType;

    /** @var ?string "uri" */
    public ?string $pType;

    /** @var ?string "uri", "bnode", "literal", or "var" */
    public ?string $oType;

    public ?string $oDataType;

    public ?string $oLang;

    public ?string $id;

    public ?string $ontologyId;


    public function __construct(
        ?string $s = null,
        ?string $p = null,
        ?string $o = null,
        ?string $sType = null,
        ?string $pType = null,
        ?string $oType = null,
        ?string $oDataType = null,
        ?string $oLang = null,
        ?string $id = null,
        ?string $ontologyId = null
    ) {
        $this->s          = $s;
        $this->p          = $p;
        $this->o          = $o;
        $this->sType      = $sType;
        $this->oType      = $oType;
        $this->pType      = $pType;
        $this->oDataType  = $oDataType;
        $this->oLang      = $oLang;
        $this->id         = $id;
        $this->ontologyId = $ontologyId;
    }

}
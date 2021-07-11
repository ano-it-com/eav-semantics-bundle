<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\TypesExtractor;

use ANOITCOM\EAVBundle\EAV\ORM\Repository\EAVNamespaceRepositoryInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\Ontology\OntologyProvider;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\OntologyTripletsProvider;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\SemanticProvider;

class TypesExtractorsFactory
{

    /**
     * @var OntologyTripletsProvider
     */
    private OntologyTripletsProvider $tripletsProvider;

    /**
     * @var SemanticProvider
     */
    private SemanticProvider $semanticProvider;

    /**
     * @var EAVNamespaceRepositoryInterface
     */
    private EAVNamespaceRepositoryInterface $namespaceRepository;

    /**
     * @var OntologyProvider
     */
    private OntologyProvider $ontologyProvider;


    public function __construct(
        OntologyTripletsProvider $tripletsProvider,
        SemanticProvider $semanticProvider,
        EAVNamespaceRepositoryInterface $namespaceRepository,
        OntologyProvider $ontologyProvider
    ) {
        $this->tripletsProvider    = $tripletsProvider;
        $this->semanticProvider    = $semanticProvider;
        $this->namespaceRepository = $namespaceRepository;
        $this->ontologyProvider    = $ontologyProvider;
    }


    public function create(OntologyDTO $ontology): TypesExtractor
    {
        return new TypesExtractor($ontology, $this->ontologyProvider, $this->tripletsProvider, $this->semanticProvider, $this->namespaceRepository);
    }
}
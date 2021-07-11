<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\TypesExtractor;

use ANOITCOM\EAVBundle\EAV\ORM\Entity\NamespaceEntity\EAVNamespaceInterface;
use ANOITCOM\EAVBundle\EAV\ORM\Repository\EAVNamespaceRepositoryInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\TripleDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\Ontology\OntologyProvider;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteria;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\OntologyTripletsProvider;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\SemanticBaseTerms;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\SemanticProvider;

class TypesExtractor
{

    private OntologyDTO $ontology;

    private OntologyTripletsProvider $tripletsProvider;

    private SemanticProvider $semanticProvider;

    private EAVNamespaceRepositoryInterface $namespaceRepository;

    private OntologyProvider $ontologyProvider;

    private array $ontologiesIris = [];


    public function __construct(
        OntologyDTO $ontology,
        OntologyProvider $ontologyProvider,
        OntologyTripletsProvider $tripletsProvider,
        SemanticProvider $semanticProvider,
        EAVNamespaceRepositoryInterface $namespaceRepository
    ) {
        $this->ontology            = $ontology;
        $this->ontologyProvider    = $ontologyProvider;
        $this->tripletsProvider    = $tripletsProvider;
        $this->semanticProvider    = $semanticProvider;
        $this->namespaceRepository = $namespaceRepository;
    }


    public function extract(callable $customTypeDefinition = null): array
    {
        $typesMeta  = [];
        $allClasses = $this->getAllClasses();
        $definition = $customTypeDefinition ? $customTypeDefinition : [ $this, 'isType' ];

        $typeClasses = $this->filterClassesByDefinition($allClasses, $definition);

        foreach ($typeClasses as $typeClass) {
            $typesMeta[$typeClass]['type_class'] = true;
        }

        $subClassesOfTypeClasses = $this->filterClassesBySubClassesOfTypes($allClasses, $typeClasses);

        foreach ($subClassesOfTypeClasses as $typeClass => $parentClasses) {
            $typesMeta[$typeClass]['subtype_class'] = $parentClasses;
        }

        $types = array_unique(array_merge($typeClasses, array_keys($subClassesOfTypeClasses)));

        $namespace = $this->namespaceRepository->find($this->ontology->namespaceId);

        if ( ! $namespace) {
            throw new \InvalidArgumentException('Namespace with id: \'' . $this->ontology->namespaceId . '\' not found!');
        }

        $typesInfo = $this->createTypes($types, $namespace, $typesMeta);

        return [ $typesInfo, $typesMeta ];

    }


    private function createTypes(array $types, EAVNamespaceInterface $namespace, &$typesMeta): array
    {
        $ontologies = [ $this->ontology ];

        $criteria = (new TripletCriteria())
            ->whereIn(TripletCriteria::SUBJECT, $types)
            ->where('ontology_id', '=', $this->ontology->id);

        $triplets = $this->tripletsProvider->findBy([ $criteria ]);

        $typesInfo = [];
        foreach ($triplets as $triplet) {
            $typesInfo[$triplet->s][] = $triplet;
        }

        $parsedTypeInfo           = [];
        $datatypePropertiesByType = [];
        $allDatatypeProperties    = [];
        foreach ($typesInfo as $type => $infoTriplets) {
            $label   = $this->getLabelFromTriplets($infoTriplets);
            $comment = $this->getCommentFromTriplets($infoTriplets);
            $alias   = $this->getAliasFromIri($type);

            $parsedTypeInfo[$type] = [
                'alias'         => $alias,
                'title'         => $label,
                'comment'       => $comment,
                'ontologyClass' => $type,
                'properties'    => [],
            ];

            $typeProperties = $this->semanticProvider->getDatatypeProperties($type, $ontologies);
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $allDatatypeProperties           = array_merge($allDatatypeProperties, $typeProperties);
            $datatypePropertiesByType[$type] = $typeProperties;
        }

        foreach ($datatypePropertiesByType as $type => $dataProperties) {
            foreach ($dataProperties as $dataProperty) {
                $typesMeta[$type]['data_properties'][$dataProperty]['direct'] = true;
            }
        }

        $allDatatypeProperties = array_values(array_unique($allDatatypeProperties));

        $criteria = (new TripletCriteria())->whereIn(TripletCriteria::SUBJECT, $allDatatypeProperties);

        $triplets = $this->tripletsProvider->findBy([ $criteria ]);

        $propertiesInfo = [];
        foreach ($triplets as $triplet) {
            $propertiesInfo[$triplet->s][] = $triplet;
        }

        $parsedPropertyInfo = [];

        foreach ($propertiesInfo as $property => $infoTriplets) {
            $label   = $this->getLabelFromTriplets($infoTriplets);
            $comment = $this->getCommentFromTriplets($infoTriplets);
            $alias   = $this->getAliasFromIri($property);

            $parsedPropertyInfo[$property] = [
                'alias'         => $alias,
                'title'         => $label,
                'comment'       => $comment,
                'ontologyClass' => $property,
            ];
        }

        foreach ($datatypePropertiesByType as $type => &$propertyNames) {
            $superClasses = $this->semanticProvider->superClasses($type, $onlyDirect = false, $ontologies);

            foreach ($superClasses as $superType) {
                $superProperties = $datatypePropertiesByType[$superType] ?? [];
                foreach ($superProperties as $superProperty) {
                    if ( ! in_array($superProperty, $propertyNames, true)) {
                        $propertyNames[] = $superProperty;

                        $typesMeta[$type]['data_properties'][$superProperty]['from_super'][] = $superType;
                    }
                }
            }
        }

        unset($propertyNames);

        foreach ($parsedTypeInfo as $type => $info) {
            $parsedTypeInfo[$type]['properties'] = [];

            $dataProperties = $datatypePropertiesByType[$type] ?? [];

            foreach ($dataProperties as $dataProperty) {
                $propertyInfo                                       = $parsedPropertyInfo[$dataProperty] ?? [];
                $parsedTypeInfo[$type]['properties'][$dataProperty] = $propertyInfo;
            }

        }

        return $parsedTypeInfo;

    }


    private function getAliasFromIri(string $type): string
    {
        $this->loadAllOntologiesIris();

        $foundIris = [];

        foreach ($this->ontologiesIris as $ontologyIri) {
            if (mb_stripos($type, $ontologyIri) === 0) {
                $iriLength             = mb_strlen($ontologyIri);
                $foundIris[$iriLength] = $ontologyIri;
            }
        }

        if ( ! count($foundIris)) {
            return $type;
        }

        krsort($foundIris);

        $longestIri = reset($foundIris);

        return str_replace($longestIri, '', $type);
    }


    private function loadAllOntologiesIris(): void
    {
        if (count($this->ontologiesIris)) {
            return;
        }

        $this->ontologiesIris = $this->ontologyProvider->allIris();
    }


    /**
     * @param TripleDTO[] $triplets
     *
     * @return string|null
     */
    private function getLabelFromTriplets(array $triplets): ?string
    {
        return $this->getValueFromTripletsWithLangPriority($triplets, SemanticBaseTerms::LABEL);
    }


    /**
     * @param TripleDTO[] $triplets
     *
     * @return string|null
     */
    private function getCommentFromTriplets(array $triplets): ?string
    {
        return $this->getValueFromTripletsWithLangPriority($triplets, SemanticBaseTerms::COMMENT);
    }


    /**
     * @param TripleDTO[] $triplets
     *
     * @return string|null
     */
    private function getValueFromTripletsWithLangPriority(array $triplets, string $predicate): ?string
    {
        $languageVariants = [];
        foreach ($triplets as $triplet) {
            if ($triplet->p === $predicate) {
                $lang                    = $triplet->oLang ?: 'none';
                $languageVariants[$lang] = $triplet->o;
            }
        }

        $langPriorities = [ 'ru', 'en', 'none' ];

        foreach ($langPriorities as $lang) {
            if (isset($languageVariants[$lang])) {
                return $languageVariants[$lang];
            }
        }

        if (count($languageVariants)) {
            return reset($languageVariants);
        }

        return null;
    }


    private function isType(string $iri): bool
    {
        return $this->semanticProvider->hasDatatypeProperties($iri) || $this->semanticProvider->hasObjectProperties($iri);
    }


    private function getAllClasses(): array
    {
        $criteria = (new TripletCriteria())
            ->where(TripletCriteria::PREDICATE, '=', SemanticBaseTerms::TYPE)
            ->where(TripletCriteria::OBJECT, '=', SemanticBaseTerms::OWL_CLASS)
            ->where('ontology_id', '=', $this->ontology->id);

        $triplets = $this->tripletsProvider->findBy([ $criteria ]);

        return array_unique(array_map(static function (TripleDTO $triple) { return $triple->s; }, $triplets));
    }


    private function filterClassesByDefinition(array $classes, callable $definition): array
    {
        return array_values(array_filter($classes, $definition));
    }


    private function filterClassesBySubClassesOfTypes(array $allClasses, array $typeClasses): array
    {
        $subClassesOfTypes = [];

        foreach ($allClasses as $class) {
            $superClasses = $this->semanticProvider->superClasses($class, $onlyDirect = false);

            $intersection = array_intersect($superClasses, $typeClasses);

            if (count($intersection)) {
                $subClassesOfTypes[$class] = $intersection;
            }
        }

        return $subClassesOfTypes;
    }
}
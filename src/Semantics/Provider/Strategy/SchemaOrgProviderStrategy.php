<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\TripleDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteria;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\OntologyTripletsProvider;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\SemanticBaseTerms;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces\PropertiesGetterProvidableInterface;

class SchemaOrgProviderStrategy implements SemanticProviderStrategyInterface, PropertiesGetterProvidableInterface
{

    private OntologyTripletsProvider $tripletsProvider;

    /**
     * @var OWLProviderStrategy
     */
    private OWLProviderStrategy $OWLProviderStrategy;


    public function __construct(OntologyTripletsProvider $tripletsProvider, OWLProviderStrategy $OWLProviderStrategy)
    {
        $this->tripletsProvider    = $tripletsProvider;
        $this->OWLProviderStrategy = $OWLProviderStrategy;
    }


    public function supportsMethod(string $method): bool
    {
        return method_exists($this, $method);
    }


    public function hasDatatypeProperties(string $iri, array $ontologies = []): bool
    {
        // TODO Improve Performance
        $properties = $this->getDatatypeProperties($iri, $ontologies);

        return count($properties) > 0;
    }


    public function hasObjectProperties(string $iri, array $ontologies = []): bool
    {
        // TODO Improve Performance
        $properties = $this->getObjectProperties($iri, $ontologies);

        return count($properties) > 0;
    }


    public function getDatatypeProperties(string $iri, array $ontologies = []): array
    {
        $properties = $this->getProperties($iri, $ontologies);

        return $this->filterByDatatypeProperty($properties, $ontologies);
    }


    public function getObjectProperties(string $iri, array $ontologies = []): array
    {
        $properties = $this->getProperties($iri, $ontologies);

        return $this->filterByObjectProperty($properties, $ontologies);
    }


    private function getProperties(string $iri, array $ontologies = []): array
    {
        $criteria = (new TripletCriteria())
            ->where(TripletCriteria::PREDICATE, '=', SemanticBaseTerms::SCHEMA_ORG_DOMAIN_INCLUDES)
            ->where(TripletCriteria::OBJECT, 'like', '%' . $iri . '%');

        $this->applyOntologiesCriteria($criteria, $ontologies);

        $triples = $this->tripletsProvider->findBy([ $criteria ]);

        $properties = [];

        foreach ($triples as $triple) {
            $subject = $triple->s;
            $object  = $triple->o;
            if (stripos($object, SemanticBaseTerms::DOMAIN) !== false || $object === $iri) {
                $properties[] = $subject;
            }
        }

        return $properties;

        //return array_map(static function (TripleDTO $triple) { return $triple->s; }, $triples);
    }


    private function applyOntologiesCriteria(TripletCriteria $criteria, array $ontologies = []): void
    {
        if (count($ontologies)) {
            $criteria->whereIn('ontology_id', array_map(static function (OntologyDTO $ontology) { return $ontology->id; }, $ontologies));
        }
    }


    private function filterByDatatypeProperty(array $properties, array $ontologies = []): array
    {
        $collectedTypes = $this->collectPropertiesTypes($properties, $ontologies);

        return array_filter($properties, static function ($property) use ($collectedTypes) {
            return isset($collectedTypes[$property]['is_datatype']) && $collectedTypes[$property]['is_datatype'] === true;
        });
    }


    private function filterByObjectProperty(array $properties, array $ontologies = []): array
    {
        $collectedTypes = $this->collectPropertiesTypes($properties, $ontologies);

        return array_filter($properties, static function ($property) use ($collectedTypes) {
            return isset($collectedTypes[$property]['is_object']) && $collectedTypes[$property]['is_object'] === true;
        });
    }


    private function collectPropertiesTypes(array $properties, array $ontologies = []): array
    {
        $collectedTypes = [];

        // rangeIncludes
        $rangesByProperty = $this->getRangesByProperties($properties, $ontologies);

        foreach ($rangesByProperty as $property => $ranges) {
            if (isset($collectedTypes[$property])) {
                continue;
            }

            $propertyIsDatatype = false;
            $propertyIsObject   = false;

            foreach ($ranges as $range) {
                $isDatatype = $this->isDataType($range, $ontologies);

                if ($isDatatype) {
                    $propertyIsDatatype = true;
                } else {
                    $propertyIsObject = true;
                }
            }

            $collectedTypes[$property]['is_datatype'] = $propertyIsDatatype;
            $collectedTypes[$property]['is_object']   = $propertyIsObject;
        }

        return $collectedTypes;
    }


    private function isDataType(string $iri, array $ontologies = []): bool
    {
        $superClasses   = $this->OWLProviderStrategy->superClasses($iri, $onlyDirect = false, $ontologies);
        $superClasses[] = $iri;

        $criteria = (new TripletCriteria())
            ->whereIn(TripletCriteria::SUBJECT, $superClasses)
            ->where(TripletCriteria::PREDICATE, '=', SemanticBaseTerms::TYPE)
            ->where(TripletCriteria::OBJECT, '=', SemanticBaseTerms::SCHEMA_ORG_DATA_TYPE);

        $this->applyOntologiesCriteria($criteria, $ontologies);

        $triples = $this->tripletsProvider->findBy([ $criteria ]);

        return count($triples) > 0;
    }


    public function getRangesByProperties(array $properties, array $ontologies): array
    {
        $criteria = (new TripletCriteria())
            ->whereIn(TripletCriteria::SUBJECT, $properties)
            ->where(TripletCriteria::PREDICATE, '=', SemanticBaseTerms::SCHEMA_ORG_RANGE_INCLUDES);

        $this->applyOntologiesCriteria($criteria, $ontologies);

        $triples = $this->tripletsProvider->findBy([ $criteria ]);

        $rangesByProperties = [];

        foreach ($triples as $triple) {
            $rangesByProperties[$triple->s][] = $triple->o;
        }

        return $rangesByProperties;
    }

}
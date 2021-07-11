<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\TripleDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteria;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\OntologyTripletsProvider;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\SemanticBaseTerms;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces\ClassExistenceProvidableInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces\ClassHierarchyProvidableInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces\PropertiesGetterProvidableInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces\PropertiesHierarchyProvidableInterface;

class OWLProviderStrategy implements SemanticProviderStrategyInterface, ClassExistenceProvidableInterface, ClassHierarchyProvidableInterface, PropertiesGetterProvidableInterface, PropertiesHierarchyProvidableInterface
{

    private OntologyTripletsProvider $tripletsProvider;


    public function __construct(OntologyTripletsProvider $tripletsProvider)
    {
        $this->tripletsProvider = $tripletsProvider;
    }


    public function supportsMethod(string $method): bool
    {
        return method_exists($this, $method);
    }


    /**
     * @param string        $iri
     * @param OntologyDTO[] $ontologies
     *
     * @return bool
     */
    public function hasClass(string $iri, array $ontologies = []): bool
    {
        $criteria = (new TripletCriteria())
            ->where(TripletCriteria::SUBJECT, '=', $iri)
            ->where(TripletCriteria::PREDICATE, '=', SemanticBaseTerms::TYPE)
            ->where(TripletCriteria::OBJECT, '=', SemanticBaseTerms::OWL_CLASS);

        $this->applyOntologiesCriteria($criteria, $ontologies);

        $triple = $this->tripletsProvider->findOneBy([ $criteria ]);

        return $triple !== null;

    }


    /**
     * @param string        $iri
     * @param bool          $onlyDirect
     * @param OntologyDTO[] $ontologies
     *
     * @return string[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function subClasses(string $iri, bool $onlyDirect = true, array $ontologies = []): array
    {
        return $this->getSubClasses($iri, SemanticBaseTerms::SUB_CLASS_OF, $onlyDirect, $ontologies);
    }


    /**
     * @param string        $iri
     * @param bool          $onlyDirect
     * @param OntologyDTO[] $ontologies
     *
     * @return string[]
     */
    public function superClasses(string $iri, bool $onlyDirect = true, array $ontologies = []): array
    {
        return $this->getSuperClasses($iri, SemanticBaseTerms::SUB_CLASS_OF, $onlyDirect, $ontologies);
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
        return $this->getPropertiesForClassByType($iri, SemanticBaseTerms::DATATYPE_PROPERTY, $ontologies);
    }


    public function getObjectProperties(string $iri, array $ontologies = []): array
    {
        return $this->getPropertiesForClassByType($iri, SemanticBaseTerms::OBJECT_PROPERTY, $ontologies);
    }


    /**
     * @param string        $iri
     * @param bool          $onlyDirect
     * @param OntologyDTO[] $ontologies
     *
     * @return string[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function subProperties(string $iri, bool $onlyDirect = true, array $ontologies = []): array
    {
        return $this->getSubClasses($iri, SemanticBaseTerms::SUB_PROPERTY_OF, $onlyDirect, $ontologies);
    }


    /**
     * @param string        $iri
     * @param bool          $onlyDirect
     * @param OntologyDTO[] $ontologies
     *
     * @return string[]
     */
    public function superProperties(string $iri, bool $onlyDirect = true, array $ontologies = []): array
    {
        return $this->getSuperClasses($iri, SemanticBaseTerms::SUB_PROPERTY_OF, $onlyDirect, $ontologies);
    }


    /**
     * @param string[] $iris
     * @param callable $criteriaMaker       function(array $iris) : TripletCriteria[], make criteria for each level search iteration
     * @param callable $tripletResultGetter function(TripleDTO $triple), return result from triple
     * @param bool     $onlyDirect          return only direct relations
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    private function hierarchyQuery(array $iris, callable $criteriaMaker, callable $tripletResultGetter, bool $onlyDirect = true): array
    {
        $processed     = [];
        $resultsGetter = function (array $iris) use (&$processed, $criteriaMaker, $tripletResultGetter) {
            $iris      = array_diff($iris, $processed);
            $processed = array_merge($processed, $iris);

            $criteria = $criteriaMaker($iris);

            $triples = $this->tripletsProvider->findBy($criteria);

            $results = [];
            foreach ($triples as $triple) {
                $result = $tripletResultGetter($triple);
                if ( ! in_array($result, $processed, true)) {
                    $results[] = $result;
                }
            }

            return $results;

        };

        $foundResults = [];

        do {
            $iris = $resultsGetter($iris);

            foreach ($iris as $iri) {
                $foundResults[] = $iri;
            }

            if ($onlyDirect) {
                // only one execution
                $iris = [];
            }

        } while (count($iris));

        return array_unique($foundResults);
    }


    private function applyOntologiesCriteria(TripletCriteria $criteria, array $ontologies): void
    {
        if (count($ontologies)) {
            $criteria->whereIn('ontology_id', array_map(static function (OntologyDTO $ontology) { return $ontology->id; }, $ontologies));
        }
    }


    private function filterByType(array $classes, string $classToFilter, array $ontologies = []): array
    {
        $criteria = (new TripletCriteria())
            ->whereIn(TripletCriteria::SUBJECT, $classes)
            ->where(TripletCriteria::PREDICATE, '=', SemanticBaseTerms::TYPE)
            ->where(TripletCriteria::OBJECT, '=', $classToFilter);

        $this->applyOntologiesCriteria($criteria, $ontologies);

        $triples = $this->tripletsProvider->findBy([ $criteria ]);

        return array_unique(array_map(static function (TripleDTO $triple) { return $triple->s; }, $triples));
    }


    private function getSubClasses(string $iri, string $predicate, bool $onlyDirect = true, array $ontologies = []): array
    {
        $criteriaMaker = function (array $iris) use ($ontologies, $predicate) {
            $criteria = (new TripletCriteria())
                ->where(TripletCriteria::PREDICATE, '=', $predicate)
                ->whereIn(TripletCriteria::OBJECT, $iris);

            $this->applyOntologiesCriteria($criteria, $ontologies);

            return [ $criteria ];
        };

        $tripletResultGetter = static function (TripleDTO $triple) {
            return $triple->s;
        };

        return $this->hierarchyQuery([ $iri ], $criteriaMaker, $tripletResultGetter, $onlyDirect);
    }


    private function getSuperClasses(string $iri, string $predicate, bool $onlyDirect = true, array $ontologies = []): array
    {
        $criteriaMaker = function (array $iris) use ($ontologies, $predicate) {
            $criteria = (new TripletCriteria())
                ->whereIn(TripletCriteria::SUBJECT, $iris)
                ->where(TripletCriteria::PREDICATE, '=', $predicate);

            $this->applyOntologiesCriteria($criteria, $ontologies);

            return [ $criteria ];
        };

        $tripletResultGetter = static function (TripleDTO $triple) {
            return $triple->o;
        };

        return $this->hierarchyQuery([ $iri ], $criteriaMaker, $tripletResultGetter, $onlyDirect);
    }


    private function getPropertiesForClassByType(string $classIri, string $propertyClassIri, array $ontologies = []): array
    {
        $criteria = (new TripletCriteria())
            ->where(TripletCriteria::PREDICATE, '=', SemanticBaseTerms::DOMAIN)
            ->where(TripletCriteria::OBJECT, 'like', '%' . $classIri . '%');

        $this->applyOntologiesCriteria($criteria, $ontologies);

        $triples = $this->tripletsProvider->findBy([ $criteria ]);

        // fake filter

        $properties = [];

        foreach ($triples as $triple) {
            $subject = $triple->s;
            $object  = $triple->o;
            if (stripos($object, SemanticBaseTerms::DOMAIN) !== false || $object === $classIri) {
                $properties[] = $subject;
            }
        }

        //$properties = array_map(static function (TripleDTO $triple) { return $triple->s; }, $triples);

        // TODO Filter by real domain/range processing

        $properties = $this->filterByType($properties, $propertyClassIri);

        return $properties;
    }
}
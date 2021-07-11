<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Provider;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces\ClassExistenceProvidableInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces\ClassHierarchyProvidableInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces\PropertiesGetterProvidableInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\Interfaces\PropertiesHierarchyProvidableInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\SemanticProviderStrategyCollection;
use ANOITCOM\EAVSemanticsBundle\Semantics\Provider\Strategy\SemanticProviderStrategyInterface;

class SemanticProvider implements ClassExistenceProvidableInterface, ClassHierarchyProvidableInterface, PropertiesGetterProvidableInterface, PropertiesHierarchyProvidableInterface
{

    private SemanticProviderStrategyCollection $strategyCollection;


    public function __construct(SemanticProviderStrategyCollection $strategyCollection)
    {
        $this->strategyCollection = $strategyCollection;
    }


    /**
     * @param string        $iri
     * @param OntologyDTO[] $ontologies
     *
     * @return bool
     */
    public function hasClass(string $iri, array $ontologies = []): bool
    {
        $results = $this->collectResultsFromStrategies(__METHOD__, func_get_args());

        return $this->orBooleanMerge($results);

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
        $results = $this->collectResultsFromStrategies(__METHOD__, func_get_args());

        return $this->mergeArrays($results);
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
        $results = $this->collectResultsFromStrategies(__METHOD__, func_get_args());

        return $this->mergeArrays($results);
    }


    public function hasDatatypeProperties(string $iri, array $ontologies = []): bool
    {
        $results = $this->collectResultsFromStrategies(__METHOD__, func_get_args());

        return $this->orBooleanMerge($results);
    }


    public function hasObjectProperties(string $iri, array $ontologies = []): bool
    {
        $results = $this->collectResultsFromStrategies(__METHOD__, func_get_args());

        return $this->orBooleanMerge($results);
    }


    public function getDatatypeProperties(string $iri, array $ontologies = []): array
    {
        $results = $this->collectResultsFromStrategies(__METHOD__, func_get_args());

        return $this->mergeArrays($results);
    }


    public function getObjectProperties(string $iri, array $ontologies = []): array
    {
        $results = $this->collectResultsFromStrategies(__METHOD__, func_get_args());

        return $this->mergeArrays($results);
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
        $results = $this->collectResultsFromStrategies(__METHOD__, func_get_args());

        return $this->mergeArrays($results);
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
        $results = $this->collectResultsFromStrategies(__METHOD__, func_get_args());

        return $this->mergeArrays($results);
    }


    private function collectResultsFromStrategies(string $fullMethodName, array $args): array
    {
        $shortMethodName = $this->getShortMethodName($fullMethodName);

        $results = [];

        /** @var SemanticProviderStrategyInterface $strategy */
        foreach ($this->strategyCollection as $strategy) {
            if ( ! $strategy->supportsMethod($shortMethodName)) {
                continue;
            }

            $results[] = $strategy->{$shortMethodName}(...$args);
        }

        if ( ! count($results)) {
            throw new \RuntimeException('No Strategy found for method ' . $shortMethodName . ' for SemanticProvider');
        }

        return $results;
    }


    private function getShortMethodName(string $fullMethodName): string
    {
        $parts = explode('::', $fullMethodName);

        return array_pop($parts);
    }


    private function orBooleanMerge(array $results): bool
    {
        foreach ($results as $result) {
            if ($result) {
                return true;
            }
        }

        return false;
    }


    private function mergeArrays(array $results): array
    {
        $first = array_shift($results);

        return array_values(array_unique(array_merge($first, ...$results)));
    }
}
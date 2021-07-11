<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Import\OntologyReader\ARC2Reader;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\TripleDTO;

class ARC2BNodesHandler
{

    private const FIRST = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#first';
    private const REST = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#rest';
    private const NIL = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil';
    private const BNODE = 'bnode';
    private const URI = 'uri';

    private array $toSkipIndexes = [];

    private array $groupedTriplesList = [];

    private array $triples;


    /**
     *
     * @param TripleDTO[] $triples
     */
    public function __construct(array $triples)
    {
        $this->triples = array_values($triples);
    }


    /**
     *
     * @return TripleDTO[]
     */
    public function handle(): array
    {

        $newTriples = [];

        foreach ($this->triples as $index => $triple) {
            if ($this->mustSkip($triple, $index)) {
                continue;
            }

            if ( ! $this->mustProcess($triple)) {
                $newTriples[] = $triple;
                continue;
            }

            $this->initGroupedTriplesList();

            $value = $this->processBNode($triple);

            $triple->o = $value;

            $newTriples[] = $triple;
        }

        return $newTriples;
    }


    private function mustProcess(TripleDTO $triple): bool
    {
        return $triple->sType !== self::BNODE && $triple->oType === self::BNODE;
    }


    private function mustSkip(TripleDTO $triple, int $index): bool
    {

        if ($triple->sType === self::BNODE) {
            return true;
        }

        return in_array($index, $this->toSkipIndexes, true);
    }


    private function processBNode(TripleDTO $triple): string
    {

        if ($triple->oType !== self::BNODE) {
            $predicate = $this->getPredicate($triple);

            return $predicate . $this->formatValue($triple->o, $triple->oType);
        }

        $bNodeId = $triple->o;

        /** @var TripleDTO[] $bNodeTriples */
        $bNodeTriples = $this->groupedTriplesList[$bNodeId];

        $this->addIndicesToSkipped(array_keys($bNodeTriples));

        if ($this->isListNodes($bNodeTriples)) {

            $values = [];

            [ $first, $last ] = $this->getFirstAndLastFromListNodes($bNodeTriples);

            while (true) {
                $value = ($first->oType === self::BNODE) ? $this->processBNode($first) : $this->formatValue($first->o, $first->oType);

                $values[] = $value;

                if ($last->o === self::NIL) {
                    break;
                }

                $listInnerBNodesTriples = $this->groupedTriplesList[$last->o];
                $this->addIndicesToSkipped(array_keys($listInnerBNodesTriples));

                [ $first, $last ] = $this->getFirstAndLastFromListNodes($listInnerBNodesTriples);
            }

            $predicate = $this->getPredicate($triple);

            return $predicate . '( ' . implode(' ', $values) . ' )';
        }

        $values = [];
        foreach ($bNodeTriples as $bNodeTriple) {
            $values[] = $this->processBNode($bNodeTriple);
        }

        $predicate = $this->getPredicate($triple);

        return $predicate . '[ ' . implode('; ', $values) . ' ]';

    }


    private function addIndicesToSkipped(array $indices): void
    {
        foreach ($indices as $index) {
            $this->toSkipIndexes[] = $index;
        }
    }


    private function getFirstAndLastFromListNodes(array $bNodeTriples): array
    {
        if (count($bNodeTriples) !== 2) {
            throw new \InvalidArgumentException('Wrong List Type - more than 2 nodes');
        }

        $first = reset($bNodeTriples);
        if ($first->p !== self::FIRST) {
            throw new \InvalidArgumentException('Wrong First List Node');
        }

        $last = end($bNodeTriples);
        if ($last->p !== self::REST) {
            throw new \InvalidArgumentException('Wrong Last List Node');
        }

        return [ $first, $last ];
    }


    /**
     * @param TripleDTO[] $bNodeTriples
     *
     * @return bool
     */
    private function isListNodes(array $bNodeTriples): bool
    {
        $first = reset($bNodeTriples);

        return $first->p === self::FIRST;
    }


    private function formatValue(?string $value, ?string $type): string
    {
        if ($type === self::URI) {
            return '<' . $value . '>';
        }

        return $value;
    }


    private function initGroupedTriplesList(): void
    {
        if ( ! count($this->groupedTriplesList) && count($this->triples)) {
            foreach ($this->triples as $index => $triple) {
                $this->groupedTriplesList[$triple->s][$index] = $triple;
            }
        }
    }


    /**
     * @param TripleDTO $triple
     *
     * @return string
     */
    private function getPredicate(TripleDTO $triple): string
    {
        return $triple->p === self::FIRST ? '' : $this->formatValue($triple->p, self::URI) . ' ';
    }
}
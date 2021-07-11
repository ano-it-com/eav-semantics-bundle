<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\Ontology\OntologyProvider;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteria;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\OntologyTripletsProvider;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

class OntologyPersister
{

    private Connection $connection;

    private string $ontologyTable = 'eav_ontology';

    private string $ontologyDataTable = 'eav_ontology_data';

    /**
     * @var OntologyProvider
     */
    private OntologyProvider $ontologyProvider;

    /**
     * @var OntologyTripletsProvider
     */
    private OntologyTripletsProvider $tripletsProvider;


    public function __construct(Connection $connection, OntologyProvider $ontologyProvider, OntologyTripletsProvider $tripletsProvider)
    {
        $this->connection       = $connection;
        $this->ontologyProvider = $ontologyProvider;
        $this->tripletsProvider = $tripletsProvider;
    }


    /**
     * @param OntologyDTO $ontology
     * @param TripleDTO[] $triples
     *
     * @throws \Throwable
     */
    public function save(OntologyDTO $ontology, array $triples): void
    {
        $this->connection->transactional(function () use ($ontology, $triples) {
            $this->executeOntologyInsert($ontology);
            $this->executeOntologyDataInsert($ontology->id, $triples);
        });
    }


    public function getById(string $id): ?OntologyDTO
    {
        return $this->ontologyProvider->findOneById($id);
    }


    public function getOntologyByIri(string $iri): ?OntologyDTO
    {
        return $this->ontologyProvider->findOneByIri($iri);
    }


    public function getOntologyTriples(OntologyDTO $ontology): array
    {
        return $this->tripletsProvider->findBy([ (new TripletCriteria())->where('ontology_id', '=', $ontology->id) ]);
    }


    /**
     * @param string      $iri
     * @param OntologyDTO $newOntology
     * @param TripleDTO[] $triples
     *
     * @throws \Throwable
     */
    public function update(string $iri, OntologyDTO $newOntology, array $newTriples): void
    {
        $oldOntology = $this->getOntologyByIri($iri);

        if ( ! $oldOntology) {
            throw new \InvalidArgumentException('Ontology with IRI ' . $iri . ' not found');
        }

        $oldTriples = $this->getOntologyTriples($oldOntology);

        $this->connection->transactional(function () use ($oldOntology, $oldTriples, $newOntology, $newTriples) {
            // update ontology
            $this->connection->update($this->ontologyTable, [
                'id'           => $oldOntology->id,
                'namespace_id' => $newOntology->namespaceId,
                'iri'          => $newOntology->iri,
                'title'        => $newOntology->title,
                'comment'      => $newOntology->comment,
                'external_iri' => $newOntology->externalIri,
                'meta'         => null,
            ], [ 'id' => $oldOntology->id ]);

            // compare by hash from valuable values
            $oldTriplesByHash = array_combine(
                array_map(function (TripleDTO $triple) { return $this->hashTriple($triple); }, $oldTriples),
                $oldTriples
            );
            $newTriplesByHash = array_combine(
                array_map(function (TripleDTO $triple) { return $this->hashTriple($triple); }, $newTriples),
                $newTriples
            );

            // update triples
            /** @var TripleDTO $newTriple */
            foreach ($newTriplesByHash as $newTripleHash => $newTriple) {
                if ( ! isset($oldTriplesByHash[$newTripleHash])) {
                    $this->connection->insert($this->ontologyDataTable, [
                        'id'          => Uuid::uuid4(),
                        'ontology_id' => $oldOntology->id,
                        's'           => $newTriple->s,
                        'p'           => $newTriple->p,
                        'o'           => $newTriple->o,
                        's_type'      => $newTriple->sType,
                        'o_type'      => $newTriple->oType,
                        'p_type'      => $newTriple->pType,
                        'o_data_type' => $newTriple->oDataType,
                        'o_lang'      => $newTriple->oLang,
                        'meta'        => null,
                    ]);
                }
            }

            //delete triples
            /** @var TripleDTO $oldTriple */
            foreach ($oldTriplesByHash as $oldTripleHash => $oldTriple) {
                if ( ! isset($newTriplesByHash[$oldTripleHash])) {
                    $this->connection->delete($this->ontologyDataTable, [ 'id' => $oldTriple->id ]);
                }
            }
        });


    }


    public function delete(string $id): void
    {
        // ontology data have CASCADE DELETE
        $this->connection->delete($this->ontologyTable, [ 'id' => $id ]);
    }


    private function hashTriple(TripleDTO $tripleDTO): string
    {
        $meaningfulData = [
            $tripleDTO->s,
            $tripleDTO->p,
            $tripleDTO->o,
            $tripleDTO->sType,
            $tripleDTO->pType,
            $tripleDTO->oType,
            $tripleDTO->oDataType,
            $tripleDTO->oLang,
        ];

        return hash('sha256', implode('||', $meaningfulData));
    }


    /**
     * @param string      $ontologyId
     * @param TripleDTO[] $triples
     */
    private function executeOntologyDataInsert(string $ontologyId, array $triples): void
    {
        $values = array_map(function (TripleDTO $triple) use ($ontologyId) {
            return [
                'id'          => Uuid::uuid4(),
                'ontology_id' => $ontologyId,
                's'           => $triple->s,
                'p'           => $triple->p,
                'o'           => $triple->o,
                's_type'      => $triple->sType,
                'o_type'      => $triple->oType,
                'p_type'      => $triple->pType,
                'o_data_type' => $triple->oDataType,
                'o_lang'      => $triple->oLang,
                'meta'        => null,
            ];
        }, $triples);

        $this->executeBatchInsert($this->ontologyDataTable, $values);
    }


    private function executeOntologyInsert(OntologyDTO $ontology): void
    {
        $this->connection->insert($this->ontologyTable, [
            'id'           => $ontology->id,
            'namespace_id' => $ontology->namespaceId,
            'iri'          => $ontology->iri,
            'title'        => $ontology->title,
            'comment'      => $ontology->comment,
            'external_iri' => $ontology->externalIri,
            'meta'         => null,
        ]);
    }


    private function executeBatchInsert(string $table, array $values): void
    {
        $maxParams = 65535;

        $firstValues   = reset($values);
        $columns       = sprintf('(%s)', implode(', ', array_keys($firstValues)));
        $columnsLength = count($firstValues);

        $valuesChunks = array_chunk($values, floor($maxParams / count($firstValues)));

        foreach ($valuesChunks as $valuesChunk) {
            $datasetLength = count($valuesChunk);

            $placeholders = sprintf('(%s)', implode(', ', array_fill(0, $columnsLength, '?')));
            $placeholder  = implode(', ', array_fill(0, $datasetLength, $placeholders));

            $parameters = [];
            foreach ($valuesChunk as $valueSet) {
                foreach ($valueSet as $oneValue) {
                    $parameters[] = $oneValue;
                }
            }

            $sql = sprintf(
                'INSERT INTO %s %s VALUES %s;',
                $table,
                $columns,
                $placeholder
            );

            $this->connection->executeUpdate($sql, $parameters);
        }


    }

}
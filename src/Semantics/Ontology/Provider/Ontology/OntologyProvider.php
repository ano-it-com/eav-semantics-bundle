<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\Ontology;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyDTO;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

class OntologyProvider
{

    private const TABLE_NAME = 'eav_ontology';

    /**
     * @var Connection
     */
    private Connection $connection;


    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    /**
     * @param string[] $iris
     * @param string[] $namespaceIds
     *
     * @return OntologyDTO[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function findByIri(array $iris, array $namespaceIds = []): array
    {
        $stmt = $this->connection
            ->createQueryBuilder()
            ->from(self::TABLE_NAME)
            ->select('*')
            ->where(self::TABLE_NAME . '.iri IN (:iris)')
            ->setParameter('iris', $iris, Connection::PARAM_STR_ARRAY)
            ->execute();

        $ontologyRows = $stmt->fetchAll(FetchMode::ASSOCIATIVE);

        return $this->hydrate($ontologyRows);
    }


    /**
     * @param string[] $ids
     * @param string[] $namespaceIds
     *
     * @return OntologyDTO[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function findById(array $ids, array $namespaceIds = []): array
    {
        $stmt = $this->connection
            ->createQueryBuilder()
            ->from(self::TABLE_NAME)
            ->select('*')
            ->where(self::TABLE_NAME . '.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY)
            ->execute();

        $ontologyRows = $stmt->fetchAll(FetchMode::ASSOCIATIVE);

        return $this->hydrate($ontologyRows);
    }


    public function findOneByIri(string $iri, array $namespaceIds = []): ?OntologyDTO
    {
        $ontologies = $this->findByIri([ $iri ], $namespaceIds);

        return count($ontologies) ? $ontologies[0] : null;
    }


    public function findOneById(string $id, array $namespaceIds = []): ?OntologyDTO
    {
        $ontologies = $this->findById([ $id ], $namespaceIds);

        return count($ontologies) ? $ontologies[0] : null;
    }


    /**
     * @return string[]
     */
    public function allIris(): array
    {
        $stmt = $this->connection
            ->createQueryBuilder()
            ->from(self::TABLE_NAME)
            ->select('iri')
            ->execute();

        $ontologyRows = $stmt->fetchAll(FetchMode::ASSOCIATIVE);

        return array_map(static function (array $row) { return $row['iri']; }, $ontologyRows);
    }


    private function hydrate(array $rows): array
    {
        return array_map(static function (array $row) {
            return new OntologyDTO(
                $row['id'],
                $row['namespace_id'],
                $row['title'],
                $row['iri'],
                $row['comment'],
                $row['external_iri']
            );
        }, $rows);
    }
}
<?php

namespace ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets;

use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\TripleDTO;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteria;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Provider\OntologyTriplets\Criteria\TripletCriteriaHandler;
use Doctrine\DBAL\Connection;

class OntologyTripletsProvider
{

    /**
     * @var Connection
     */
    private Connection $connection;

    private const TABLE_NAME = 'eav_ontology_data';

    private TripletCriteriaHandler $tripletCriteriaHandler;


    public function __construct(Connection $connection, TripletCriteriaHandler $tripletCriteriaHandler)
    {
        $this->connection             = $connection;
        $this->tripletCriteriaHandler = $tripletCriteriaHandler;
    }


    /**
     * @param TripletCriteria[] $criteria
     * @param array             $orderBy
     * @param int|null          $limit
     * @param int|null          $offset
     *
     * @return TripleDTO[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function findBy(array $criteria, array $orderBy = [], $limit = null, $offset = null): array
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->from(self::TABLE_NAME)
            ->select([ self::TABLE_NAME . '.*' ]);

        $this->tripletCriteriaHandler->applyCriteria($qb, $criteria);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        $this->tripletCriteriaHandler->applyOrdering($qb, $orderBy);

        $stmt = $qb->execute();

        $sql = $qb->getSQL();

        $params = $qb->getParameters();

        $rows = $stmt->fetchAll();

        return array_map(function (array $row) {
            return new TripleDTO(
                $row['s'],
                $row['p'],
                $row['o'],
                $row['s_type'],
                $row['o_type'],
                $row['p_type'],
                $row['o_data_type'],
                $row['o_lang'],
                $row['id'],
                $row['ontology_id'],
            );
        }, $rows);
    }


    public function findOneBy(array $criteria, array $orderBy = []): ?TripleDTO
    {
        $triplets = $this->findBy($criteria, $orderBy, 1);

        return count($triplets) ? $triplets[0] : null;
    }
}
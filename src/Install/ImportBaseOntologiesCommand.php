<?php

namespace ANOITCOM\EAVSemanticsBundle\Install;

use ANOITCOM\EAVBundle\EAV\ORM\Criteria\Filter\CommonFilters\FilterCriteria\FilterCriteria;
use ANOITCOM\EAVBundle\EAV\ORM\Entity\NamespaceEntity\EAVNamespace;
use ANOITCOM\EAVBundle\EAV\ORM\Entity\NamespaceEntity\EAVNamespaceInterface;
use ANOITCOM\EAVBundle\EAV\ORM\EntityManager\EAVEntityManagerInterface;
use ANOITCOM\EAVBundle\EAV\ORM\Repository\EAVNamespaceRepositoryInterface;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Import\FileOntologyImporter;
use ANOITCOM\EAVSemanticsBundle\Semantics\Ontology\Persistence\OntologyPersister;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

class ImportBaseOntologiesCommand extends Command
{

    protected static $defaultName = 'eav-semantics:ontology:import';

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var EAVEntityManagerInterface
     */
    private EAVEntityManagerInterface $em;

    /**
     * @var EAVNamespaceRepositoryInterface
     */
    private EAVNamespaceRepositoryInterface $namespaceRepository;

    /**
     * @var FileOntologyImporter
     */
    private FileOntologyImporter $fileOntologyImporter;

    /**
     * @var OntologyPersister
     */
    private OntologyPersister $ontologyPersister;


    public function __construct(
        KernelInterface $kernel,
        Filesystem $fs,
        EAVEntityManagerInterface $em,
        EAVNamespaceRepositoryInterface $namespaceRepository,
        FileOntologyImporter $fileOntologyImporter,
        OntologyPersister $ontologyPersister
    ) {
        parent::__construct(self::$defaultName);
        $this->kernel               = $kernel;
        $this->fs                   = $fs;
        $this->em                   = $em;
        $this->namespaceRepository  = $namespaceRepository;
        $this->fileOntologyImporter = $fileOntologyImporter;
        $this->ontologyPersister    = $ontologyPersister;
    }


    public function run(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('Installing Base Ontologies...');

        $ontologiesMeta = $this->getOntologyMetaToProcess();

        $total   = count($ontologiesMeta);
        $current = 1;

        foreach ($ontologiesMeta as $ontologyMeta) {

            $namespace = $this->getOrCreateNamespace($ontologyMeta);

            $oldOntology = $this->ontologyPersister->getOntologyByIri($ontologyMeta['iri']);

            if ( ! $oldOntology) {
                $io->writeln('Creating \'' . $ontologyMeta['title'] . '\' ontology...(' . $current . '/' . $total . ')');

                $this->fileOntologyImporter->import($ontologyMeta['path'], $namespace->getId(), $ontologyMeta['iri'], $ontologyMeta['title'], $ontologyMeta['comment']);

                $io->success('\'' . $ontologyMeta['title'] . '\' ontology created successfully!');
            } else {
                $io->writeln('Updating \'' . $ontologyMeta['title'] . '\' ontology...(' . $current . '/' . $total . ')');

                $this->fileOntologyImporter->update($ontologyMeta['path'], $namespace->getId(), $ontologyMeta['iri'], $ontologyMeta['title'], $ontologyMeta['comment']);

                $io->success('\'' . $ontologyMeta['title'] . '\' ontology updated successfully!');
            }

            $current++;
        }

        $io->success('Installation complete successful');

        return 0;

    }


    private function getOrCreateNamespace(array $ontologyMeta): EAVNamespaceInterface
    {
        $namespace = $this->namespaceRepository->findOneBy([ (new FilterCriteria())->where('iri', '=', $ontologyMeta['iri']) ]);

        if ( ! $namespace) {
            $namespace = new EAVNamespace(Uuid::uuid4(), $ontologyMeta['iri']);
            $namespace->setTitle($ontologyMeta['title']);
            $namespace->setComment($ontologyMeta['comment']);

            $this->em->persist($namespace);
            $this->em->flush();
        }

        return $namespace;
    }


    private function getOntologyMetaToProcess(): array
    {
        $meta = [];

        $dir = __DIR__ . '/Ontology';

        $finder = new Finder();
        $finder->files()->in($dir);

        foreach ($finder->getIterator() as $file) {
            $absoluteFilePath = $file->getRealPath();

            $fileMeta = $this->getMetaForFilepath($absoluteFilePath);

            if ($fileMeta) {
                $meta[] = $fileMeta;
            }
        }

        return $meta;
    }


    private function getMetaForFilepath(string $filepath): ?array
    {
        $allFilesMeta = $this->getOntologyFilesMeta();

        $metadata = null;
        foreach (array_keys($allFilesMeta) as $metaFileName) {
            $currentFileName = pathinfo($filepath, PATHINFO_BASENAME);
            if ($currentFileName === $metaFileName) {
                $metadata = $allFilesMeta[$metaFileName];
                break;
            }
        }

        if ( ! $metadata) {
            return null;
        }

        return [
            'path'    => $filepath,
            'iri'     => $metadata['iri'],
            'title'   => $metadata['title'],
            'comment' => $metadata['comment'],
        ];
    }


    private function getOntologyFilesMeta(): array
    {
        return [
            'schemaorg.ttl'                  => [
                'iri'     => 'http://schema.org/',
                'title'   => 'Schema.org',
                'comment' => 'Schema.org is a collaborative, community activity with a mission to create, maintain, and promote schemas for structured data on the Internet.'
            ],
            '22-rdf-syntax-ns.ttl'           => [
                'iri'     => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'title'   => 'The RDF Concepts Vocabulary (RDF)',
                'comment' => 'This is the RDF Schema for the RDF vocabulary terms in the RDF Namespace, defined in RDF 1.1 Concepts.'
            ],
            'dublin_core_abstract_model.ttl' => [ 'iri' => 'http://purl.org/dc/dcam/', 'title' => 'Metadata terms for vocabulary description', 'comment' => null ],
            'dublin_core_elements.ttl'       => [ 'iri' => 'http://purl.org/dc/elements/1.1/', 'title' => 'Dublin Core Metadata Element Set, Version 1.1', 'comment' => null ],
            'dublin_core_terms.ttl'          => [ 'iri' => 'http://purl.org/dc/terms/', 'title' => 'DCMI Metadata Terms - other', 'comment' => null ],
            'dublin_core_type.ttl'           => [ 'iri' => 'http://purl.org/dc/dcmitype/', 'title' => 'DCMI Type Vocabulary', 'comment' => null ],
            'foaf.rdf'                       => [
                'iri'     => 'http://xmlns.com/foaf/0.1/',
                'title'   => 'Friend of a Friend (FOAF) vocabulary',
                'comment' => 'The Friend of a Friend (FOAF) RDF vocabulary, described using W3C RDF Schema and the Web Ontology Language.'
            ],
            'owl.ttl'                        => [
                'iri'     => 'http://www.w3.org/2002/07/owl#',
                'title'   => 'The OWL 2 Schema vocabulary (OWL 2)',
                'comment' => 'This ontology partially describes the built-in classes and properties that together form the basis of the RDF/XML syntax of OWL 2. The content of this ontology is based on Tables 6.1 and 6.2 in Section 6.4 of the OWL 2 RDF-Based Semantics specification, available at http://www.w3.org/TR/owl2-rdf-based-semantics/. Please note that those tables do not include the different annotations (labels, comments and rdfs:isDefinedBy links) used in this file. Also note that the descriptions provided in this ontology do not provide a complete and correct formal description of either the syntax or the semantics of the introduced terms (please see the OWL 2 recommendations for the complete and normative specifications). Furthermore, the information provided by this ontology may be misleading if not used with care. This ontology SHOULD NOT be imported into OWL ontologies. Importing this file into an OWL 2 DL ontology will cause it to become an OWL 2 Full ontology and may have other, unexpected, consequences.'
            ],
            'rdf-schema.ttl'                 => [ 'iri' => 'http://www.w3.org/2000/01/rdf-schema#', 'title' => 'The RDF Schema vocabulary (RDFS)', 'comment' => null ],
            'skos.rdf'                       => [
                'iri'     => 'http://www.w3.org/2004/02/skos/core#',
                'title'   => 'SKOS Vocabulary',
                'comment' => 'An RDF vocabulary for describing the basic structure and content of concept schemes such as thesauri, classification schemes, subject heading lists, taxonomies, \'folksonomies\', other types of controlled vocabulary, and also concept schemes embedded in glossaries and terminologies.'
            ],
        ];
    }

}
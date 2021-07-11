<?= "<?php\n"; ?>

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class <?= $className ?> extends AbstractMigration
{

    public function getDescription(): string
    {
        return '';
    }


    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        // EAV SEMANTICS MIGRATION MARK - DO NOT DELETE

        // ONTOLOGY
        $this->addSql('CREATE TABLE eav_ontology (id UUID NOT NULL, namespace_id UUID NOT NULL, iri TEXT NOT NULL, title VARCHAR(255) NOT NULL, comment TEXT DEFAULT NULL, external_iri TEXT DEFAULT NULL, meta JSONB DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE eav_ontology_data (id UUID NOT NULL, ontology_id UUID NOT NULL, s TEXT DEFAULT NULL, p TEXT DEFAULT NULL, o TEXT DEFAULT NULL, s_type VARCHAR(255) DEFAULT NULL, o_type VARCHAR(255) DEFAULT NULL, p_type VARCHAR(255) DEFAULT NULL, o_data_type VARCHAR(255) DEFAULT NULL, o_lang VARCHAR(255) DEFAULT NULL, meta JSONB DEFAULT NULL, PRIMARY KEY(id))');

        $this->addSql('ALTER TABLE eav_ontology ADD CONSTRAINT FK_eav_ontology_namespace FOREIGN KEY (namespace_id) REFERENCES eav_namespace (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE eav_ontology ADD CONSTRAINT UNIQUE_eav_ontology_iri UNIQUE (iri)');
        $this->addSql('ALTER TABLE eav_ontology_data ADD CONSTRAINT FK_eav_ontology_data_ontology FOREIGN KEY (ontology_id) REFERENCES eav_ontology (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE INDEX IDX_eav_ontology_namespace_id ON eav_ontology (namespace_id)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_iri ON eav_ontology (iri)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_title ON eav_ontology (title)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_data_ontology_id ON eav_ontology_data (ontology_id)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_data_s ON eav_ontology_data (s)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_data_p ON eav_ontology_data (p)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_data_o ON eav_ontology_data (o)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_data_s_type ON eav_ontology_data (s_type)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_data_o_type ON eav_ontology_data (o_type)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_data_p_type ON eav_ontology_data (p_type)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_data_o_data_type ON eav_ontology_data (o_data_type)');
        $this->addSql('CREATE INDEX IDX_eav_ontology_data_o_lang ON eav_ontology_data (o_lang)');

        $this->addSql('ALTER TABLE eav_type ADD COLUMN ontology_class TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE eav_type_property ADD COLUMN ontology_class TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE eav_entity_relation_type ADD COLUMN ontology_class TEXT DEFAULT NULL');

        $this->addSql('CREATE INDEX IDX_eav_entity_relation_type_ontology_class ON eav_entity_relation_type (ontology_class)');
        $this->addSql('CREATE INDEX IDX_eav_type_ontology_class ON eav_type (ontology_class)');
        $this->addSql('CREATE INDEX IDX_eav_type_property_ontology_class ON eav_type_property (ontology_class)');

    }


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX IDX_eav_entity_relation_type_ontology_class');
        $this->addSql('DROP INDEX IDX_eav_type_ontology_class');
        $this->addSql('DROP INDEX IDX_eav_type_property_ontology_class');
        $this->addSql('ALTER TABLE eav_type DROP COLUMN ontology_class RESTRICT');
        $this->addSql('ALTER TABLE eav_type_property DROP COLUMN ontology_class RESTRICT');
        $this->addSql('ALTER TABLE eav_entity_relation_type DROP COLUMN ontology_class RESTRICT');

        $this->addSql('ALTER TABLE eav_ontology DROP CONSTRAINT UNIQUE_eav_ontology_iri');
        $this->addSql('ALTER TABLE eav_ontology DROP CONSTRAINT FK_eav_ontology_namespace');
        $this->addSql('ALTER TABLE eav_ontology_data DROP CONSTRAINT FK_eav_ontology_data_ontology');
        $this->addSql('DROP TABLE eav_ontology');
        $this->addSql('DROP TABLE eav_ontology_data');
    }

}

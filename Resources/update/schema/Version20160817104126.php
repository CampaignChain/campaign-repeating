<?php

namespace Application\Migrations;

use CampaignChain\Campaign\RepeatingBundle\Resources\update\data\UpdateInstances;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160817104126 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function preUp(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        /** @var UpdateInstances $dataUpdater */
        $dataUpdater = $this->container->get('campaignchain_campaign_repeating.update.instances');
        $dataUpdater->execute();
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE campaignchain_campaign_repeating_instance');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE campaignchain_campaign_repeating_instance (id INT AUTO_INCREMENT NOT NULL, repeatingCampaign_id INT NOT NULL, scheduledCampaign_id INT NOT NULL, INDEX IDX_D33E9E9E9B83C7E3 (repeatingCampaign_id), INDEX IDX_D33E9E9E8D9C0062 (scheduledCampaign_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campaignchain_campaign_repeating_instance ADD CONSTRAINT FK_D33E9E9E8D9C0062 FOREIGN KEY (scheduledCampaign_id) REFERENCES campaignchain_campaign (id)');
        $this->addSql('ALTER TABLE campaignchain_campaign_repeating_instance ADD CONSTRAINT FK_D33E9E9E9B83C7E3 FOREIGN KEY (repeatingCampaign_id) REFERENCES campaignchain_campaign (id)');
    }
}

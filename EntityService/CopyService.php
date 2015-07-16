<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Campaign\RepeatingBundle\EntityService;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Module;
use CampaignChain\CoreBundle\Entity\Campaign;

class CopyService
{
    protected $em;
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function repeating2Scheduled(Campaign $repeatingCampaign, $status = null, $name = null)
    {
        if($status == null){
            $status = Action::STATUS_OPEN;
        }

        $campaignService = $this->container->get('campaignchain.core.campaign');

        try {
            $this->em->getConnection()->beginTransaction();

            // Clone the campaign template.
            $scheduledCampaign = $campaignService->cloneCampaign(
                $repeatingCampaign,
                $status
            );

            // Change module relationship of cloned campaign to scheduled campaign.
            $moduleService = $this->container->get('campaignchain.core.module');
            $scheduledCampaign->setCampaignModule(
                $moduleService->getModule(
                    Module::REPOSITORY_CAMPAIGN,
                    'campaignchain/campaign-scheduled',
                    'campaignchain-scheduled'
                )
            );
            // Specify other parameters of scheduled campaign.
            if($name != null){
                $scheduledCampaign->setName($name);
            }
            $scheduledCampaign->setHasRelativeDates(false);
            $scheduledCampaign->setStatus($status);
            $scheduledCampaign->setInterval(null);
            $scheduledCampaign->setIntervalStartDate(null);
            $scheduledCampaign->setIntervalNextRun(null);
            $scheduledCampaign->setIntervalEndDate(null);
            $scheduledCampaign->setIntervalEndOccurrence(null);
            $hookService = $this->container->get('campaignchain.core.hook');
            $scheduledCampaign->setTriggerHook(
                $hookService->getHook('campaignchain-duration')
            );

            $this->em->flush();

            // Move the cloned campaign to the start date.
            $scheduledCampaign = $campaignService->moveCampaign(
                $scheduledCampaign, $repeatingCampaign->getIntervalNextRun(),
                $status
            );

            $this->em->getConnection()->commit();

            return $scheduledCampaign;

        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}
<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\Campaign\RepeatingBundle\Service;

use CampaignChain\Hook\DateRepeatBundle\Entity\DateRepeat;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Module;
use CampaignChain\CoreBundle\Entity\Campaign;

class Copy
{
    const BUNDLE_NAME = 'campaignchain/campaign-repeating';
    const MODULE_IDENTIFIER = 'campaignchain-repeating';

    protected $em;
    protected $container;
    protected $logger;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
        $this->logger = $this->container->get('logger');
    }

    public function repeating2Repeating(Campaign $fromCampaign, DateRepeat $dateRepeat = null, $name = null)
    {
        try {
            $this->em->getConnection()->beginTransaction();

            $templateCopyService = $this->container->get('campaignchain.campaign.template.copy');
            $repeatingCampaign = $templateCopyService->template2Template($fromCampaign, null, $name);

            if($dateRepeat != null) {
                $repeatingCampaign = $this->setDateRepeat($repeatingCampaign, $dateRepeat);
            }

            $this->em->flush();
            $this->em->getConnection()->commit();

            return $repeatingCampaign;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    public function repeating2Scheduled(
        Campaign $repeatingCampaign, \DateTime $startDate,
        $status = null, $name = null, $asChild = false
    )
    {
        if($status == null){
            $status = Action::STATUS_OPEN;
        }

        $campaignService = $this->container->get('campaignchain.core.campaign');

        try {
            $this->em->getConnection()->beginTransaction();

            // Clone the campaign template.
            /** @var Campaign $scheduledCampaign */
            $scheduledCampaign = $campaignService->cloneCampaign(
                $repeatingCampaign,
                $status
            );

            // Change module relationship of cloned campaign to scheduled campaign.
            $moduleService = $this->container->get('campaignchain.core.module');
            $scheduledCampaign->setCampaignModule(
                $moduleService->getModule(
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
            if($asChild){
                $scheduledCampaign->setParent($repeatingCampaign);
                $repeatingCampaign->addChild($scheduledCampaign);
            }

            $this->em->flush();

            // Move the cloned campaign to the start date.
            $scheduledCampaign = $campaignService->moveCampaign(
                $scheduledCampaign, $startDate,
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

    public function scheduled2Repeating(Campaign $fromCampaign, DateRepeat $dateRepeat, $name = null)
    {
        try {
            $this->em->getConnection()->beginTransaction();

            $templateCopyService = $this->container->get('campaignchain.campaign.template.copy');
            $repeatingCampaign = $templateCopyService->scheduled2Template($fromCampaign, null, $name);

            $repeatingCampaign = $this->setModuleAndHook($repeatingCampaign);
            $repeatingCampaign = $this->setDateRepeat($repeatingCampaign, $dateRepeat);

            $this->em->flush();
            $this->em->getConnection()->commit();

            return $repeatingCampaign;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    public function template2Repeating(Campaign $fromCampaign, DateRepeat $dateRepeat, $name = null)
    {
        try {
            $this->em->getConnection()->beginTransaction();

            $templateCopyService = $this->container->get('campaignchain.campaign.template.copy');
            $repeatingCampaign = $templateCopyService->template2Template($fromCampaign, null, $name);

            $repeatingCampaign = $this->setModuleAndHook($repeatingCampaign);
            $repeatingCampaign = $this->setDateRepeat($repeatingCampaign, $dateRepeat);

            $this->em->flush();
            $this->em->getConnection()->commit();

            return $repeatingCampaign;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    private function setDateRepeat(Campaign $repeatingCampaign, DateRepeat $dateRepeat)
    {
        $repeatingCampaign->setInterval($dateRepeat->getInterval());
        $repeatingCampaign->setIntervalStartDate($dateRepeat->getIntervalStartDate());
        $repeatingCampaign->setIntervalNextRun($dateRepeat->getIntervalNextRun());
        $repeatingCampaign->setIntervalEndDate($dateRepeat->getIntervalEndDate());
        $repeatingCampaign->setIntervalEndOccurrence($dateRepeat->getIntervalEndOccurrence());

        return $repeatingCampaign;
    }

    private function setModuleAndHook(Campaign $repeatingCampaign)
    {
        $moduleService = $this->container->get('campaignchain.core.module');
        $module = $moduleService->getModule(
            static::BUNDLE_NAME,
            static::MODULE_IDENTIFIER
        );
        $repeatingCampaign->setCampaignModule($module);
        $hookService = $this->container->get('campaignchain.core.hook');
        $repeatingCampaign->setTriggerHook(
            $hookService->getHook('campaignchain-date-repeat')
        );

        return $repeatingCampaign;
    }
}
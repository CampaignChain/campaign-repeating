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

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\Module;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\Job\JobActionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\Campaign\RepeatingBundle\Entity\RepeatingCampaignInstance;

class Job implements JobActionInterface
{
    protected $logger;
    protected $em;
    protected $container;

    protected $message;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    /**
     * Turns a Repeating Campaign into a Scheduled Campaign so that it gets
     * executed as specified by the repeat date.
     *
     * @param \CampaignChain\CoreBundle\Job\The $id
     * @return string
     * @throws \Exception
     */
    public function execute($id)
    {
        $this->logger = $this->container->get('logger');

        $campaignService = $this->container->get('campaignchain.core.campaign');
        /** @var Campaign $repeatingCampaign */
        $repeatingCampaign = $campaignService->getCampaign($id);

        try {
            $this->em->getConnection()->beginTransaction();

            $now = new \DateTime('now');

            // Has the interval's end date been reached?
            if(
                $repeatingCampaign->getIntervalEndDate() &&
                $repeatingCampaign->getIntervalEndDate() < $now
            ){
                $repeatingCampaign->setStatus(Action::STATUS_CLOSED);

                $this->message =
                    'Closed Repeating Campaign "'.$repeatingCampaign->getName().
                    '" with ID "'.$repeatingCampaign->getId().'" due to '
                    .'end date '
                    .$repeatingCampaign->getIntervalEndDate()->format(\DateTime::ISO8601);

                $this->em->getConnection()->commit();

                return self::STATUS_OK;
            }

            /** @var Copy $copyService */
            $copyService = $this->container->get('campaignchain.campaign.repeating.copy');
            $scheduledCampaign = $copyService->repeating2Scheduled(
                $repeatingCampaign, $repeatingCampaign->getIntervalNextRun(),
                Action::STATUS_BACKGROUND_PROCESS);

            // Add the new Repeating Campaign instance.
            $instance = new RepeatingCampaignInstance();
            $instance->setRepeatingCampaign($repeatingCampaign);
            $instance->setScheduledCampaign($scheduledCampaign);
            $this->em->persist($instance);

            $this->message =
                'Created new background processes for Repeating Campaign "'.$repeatingCampaign->getName().
                '" with ID "'.$repeatingCampaign->getId().'" as Scheduled Campaign'.
                ' with ID "'.$scheduledCampaign->getId().'"'.
                ' starting at '.$scheduledCampaign->getStartDate()->format(\DateTime::ISO8601).'.';

            /*
             * Update next run date for repeating campaign.
             */
            $updatedNextRun = clone $repeatingCampaign->getIntervalNextRun();
            $updatedNextRun->modify($repeatingCampaign->getInterval());
            $doUpdate = true;
            // Is the next run date after the interval's end date?
            if(
                $repeatingCampaign->getIntervalEndDate() !== null &&
                $repeatingCampaign->getIntervalEndDate() < $updatedNextRun
            ){
                $doUpdate = false;
                $this->message .=
                    ' Repeating Campaign was closed, because the next run '.
                    'would be after the interval end date, which is at '.
                    $repeatingCampaign->getIntervalEndDate()->format(\DateTime::ISO8601).
                    '.';
            }
            // Would we reach the maximum number of occurrences in the next run?
            if($repeatingCampaign->getIntervalEndOccurrence() !== null){
                $qb = $this->em->createQueryBuilder()
                    ->select('COUNT(rci)')
                    ->from('CampaignChainCampaignRepeatingBundle:RepeatingCampaignInstance','rci')
                    ->where('rci.repeatingCampaign = :repeatingCampaign')
                    ->setParameter('repeatingCampaign', $repeatingCampaign);
                $occurrences = $qb->getQuery()->getSingleScalarResult();
                $this->logger->info('Occurrences: '.$occurrences);

                if(($occurrences+1) == $repeatingCampaign->getIntervalEndOccurrence()){
                    $doUpdate = false;
                    $this->message .=
                        ' Repeating Campaign was closed, because the maximum '.
                        'number of '.$repeatingCampaign->getIntervalEndOccurrence().
                        ' occurrences was reached.';
                }
            }

            if($doUpdate){
                $repeatingCampaign->setIntervalNextRun($updatedNextRun);
                $this->message .= ' Next run at '.$updatedNextRun->format(\DateTime::ISO8601);
            } else {
                $repeatingCampaign->setStatus(Action::STATUS_CLOSED);

                $this->message =
                    'Closed Repeating Campaign "'.$repeatingCampaign->getName().
                    '" with ID "'.$repeatingCampaign->getId().'"';
            }

            $this->em->getConnection()->commit();

            return self::STATUS_OK;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    public function getMessage(){
        return $this->message;
    }
}
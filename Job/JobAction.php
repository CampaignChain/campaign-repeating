<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) Sandro Groganz <sandro@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Campaign\RepeatingBundle\Job;

use CampaignChain\CoreBundle\Entity\Action;
use CampaignChain\CoreBundle\Entity\Campaign;
use CampaignChain\CoreBundle\Entity\Module;
use Doctrine\ORM\EntityManager;
use CampaignChain\CoreBundle\Entity\Medium;
use CampaignChain\CoreBundle\Job\JobActionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CampaignChain\Campaign\RepeatingBundle\Entity\RepeatingCampaignInstance;

class JobAction implements JobActionInterface
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
        $repeatingCampaign = $campaignService->getCampaign($id);

        try {
            $this->em->getConnection()->beginTransaction();

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
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

namespace CampaignChain\Campaign\RepeatingBundle\Resources\update\data;

use CampaignChain\CoreBundle\Entity\Campaign;
use Doctrine\Common\Persistence\ManagerRegistry;

class UpdateInstances
{
    /**
     * @var Registry
     */
    private $em;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->em = $managerRegistry->getManager();
    }

    public function execute()
    {
        $connection = $this->em->getConnection();
        $statement = $connection->prepare(
            'SELECT * FROM campaignchain_campaign_repeating_instance'
        );
        $statement->execute();

        $instances = $statement->fetchAll();

        try {
            $this->em->getConnection()->beginTransaction();

            foreach ($instances as $instance){
                /** @var Campaign $campaignParent */
                $campaignParent = $this->em
                    ->getRepository('CampaignChainCoreBundle:Campaign')
                    ->find($instance['repeatingCampaign_id']);

                /** @var Campaign $campaignChild */
                $campaignChild = $this->em
                    ->getRepository('CampaignChainCoreBundle:Campaign')
                    ->find($instance['scheduledCampaign_id']);

                $campaignParent->addChild($campaignChild);
                $campaignChild->setParent($campaignParent);

                $this->em->flush();
            }

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }
}
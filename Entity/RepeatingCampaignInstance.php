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

namespace CampaignChain\Campaign\RepeatingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Captures the relationship between a Repeating Campaign and its instances, i.e.
 * Scheduled Campaigns derived from it.
 *
 * @ORM\Entity
 * @ORM\Table(name="campaignchain_campaign_repeating_instance")
 */
class RepeatingCampaignInstance
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="CampaignChain\CoreBundle\Entity\Campaign")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $repeatingCampaign;

    /**
     * @ORM\ManyToOne(targetEntity="CampaignChain\CoreBundle\Entity\Campaign")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $scheduledCampaign;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set campaign
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $campaign
     * @return Activity
     */
    public function setRepeatingCampaign(\CampaignChain\CoreBundle\Entity\Campaign $repeatingCampaign = null)
    {
        $this->repeatingCampaign = $repeatingCampaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \CampaignChain\CoreBundle\Entity\Campaign
     */
    public function getRepeatingCampaign()
    {
        return $this->repeatingCampaign;
    }

    /**
     * Set campaign
     *
     * @param \CampaignChain\CoreBundle\Entity\Campaign $campaign
     * @return Activity
     */
    public function setScheduledCampaign(\CampaignChain\CoreBundle\Entity\Campaign $scheduledCampaign = null)
    {
        $this->scheduledCampaign = $scheduledCampaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \CampaignChain\CoreBundle\Entity\Campaign
     */
    public function getScheduledCampaign()
    {
        return $this->scheduledCampaign;
    }
}

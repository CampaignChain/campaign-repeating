<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

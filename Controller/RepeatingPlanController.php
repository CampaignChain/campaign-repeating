<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Campaign\RepeatingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use CampaignChain\Campaign\TemplateBundle\Controller\PlanController;

class RepeatingPlanController extends PlanController
{
    const CAMPAIGN_DISPLAY_NAME = "Repeating Campaign";
    const BUNDLE_NAME = 'campaignchain/campaign-repeating';
    const MODULE_IDENTIFIER = 'campaignchain-repeating';
}
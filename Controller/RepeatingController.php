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

namespace CampaignChain\Campaign\RepeatingBundle\Controller;

use CampaignChain\Campaign\TemplateBundle\Controller\TemplateController;
use Symfony\Component\HttpFoundation\Request;

class RepeatingController extends TemplateController
{
    const CAMPAIGN_DISPLAY_NAME = "Repeating Campaign";
    const BUNDLE_NAME = 'campaignchain/campaign-repeating';
    const MODULE_IDENTIFIER = 'campaignchain-repeating';
    const TRIGGER_HOOK = 'campaignchain-date-repeat';

    public function indexAction()
    {
        $repository_campaigns = $this->getDoctrine()->getRepository('CampaignChainCoreBundle:Campaign')->getCampaignsByModule(static::MODULE_IDENTIFIER, static::BUNDLE_NAME);

        return $this->render(
            'CampaignChainCampaignRepeatingBundle::index.html.twig',
            array(
                'page_title' => static::CAMPAIGN_DISPLAY_NAME . 's',
                'repository_campaigns' => $repository_campaigns,
            ));
    }

    public function copyAction(Request $request, $id)
    {
        $campaignService = $this->get('campaignchain.core.campaign');
        $fromCampaign = $campaignService->getCampaign($id);
        $campaignURI = $campaignService->getCampaignURI($fromCampaign);

        switch ($campaignURI) {
            case 'campaignchain/campaign-repeating/campaignchain-repeating':
                $toCampaign = clone $fromCampaign;
                $toCampaign->setName($fromCampaign->getName() . ' (copied)');

                $campaignType = $this->getCampaignType();
                $campaignType->setHooksOptions(
                    array(
                        'campaignchain-timespan' => array(
                            'disabled' => true,
                        )
                    )
                );

                $form = $this->createForm($campaignType, $toCampaign);

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $copyService = $this->get('campaignchain.campaign.repeating.copy');
                    $clonedCampaign = $copyService->repeating2Repeating(
                        $fromCampaign,
                        $form->get('campaignchain_hook_campaignchain_date_repeat')->getData(),
                        $toCampaign->getName());

                    $this->addFlash(
                        'success',
                        'The ' . static::CAMPAIGN_DISPLAY_NAME . ' <a href="' . $this->generateUrl(
                            'campaignchain_core_campaign_edit',
                            array('id' => $clonedCampaign->getId())) . '">' .
                        $clonedCampaign->getName() . '</a> was copied successfully.'
                    );

                    return $this->redirectToRoute('campaignchain_core_campaign');
                }

                return $this->render(
                    'CampaignChainCoreBundle:Base:new.html.twig',
                    array(
                        'page_title' => 'Copy ' . static::CAMPAIGN_DISPLAY_NAME,
                        'form' => $form->createView(),
                    ));
                break;
            case 'campaignchain/campaign-template/campaignchain-template':
                $toCampaign = clone $fromCampaign;
                $toCampaign->setName($fromCampaign->getName() . ' (copied)');

                $campaignType = $this->getCampaignType();
                $campaignType->setHooksOptions(
                    array(
                        'campaignchain-timespan' => array(
                            'disabled' => true,
                        )
                    )
                );

                $form = $this->createForm($campaignType, $toCampaign);

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $copyService = $this->get('campaignchain.campaign.repeating.copy');
                    $clonedCampaign = $copyService->template2Repeating(
                        $fromCampaign,
                        $form->get('campaignchain_hook_campaignchain_date_repeat')->getData(),
                        $toCampaign->getName());

                    $this->addFlash(
                        'success',
                        'The ' . static::CAMPAIGN_DISPLAY_NAME . ' <a href="' . $this->generateUrl(
                            'campaignchain_core_campaign_edit',
                            array('id' => $clonedCampaign->getId())) . '">' .
                        $clonedCampaign->getName() . '</a> was copied successfully.'
                    );

                    return $this->redirectToRoute('campaignchain_core_campaign');
                }

                return $this->render(
                    'CampaignChainCoreBundle:Base:new.html.twig',
                    array(
                        'page_title' => 'Copy ' . static::CAMPAIGN_DISPLAY_NAME,
                        'form' => $form->createView(),
                    ));
                break;
            case 'campaignchain/campaign-scheduled/campaignchain-scheduled':
                $toCampaign = clone $fromCampaign;

                $toCampaign->setName($toCampaign->getName() . ' (copied)');
                $campaignType = $this->getCampaignType();
                $campaignType->setHooksOptions(
                    array(
                        'campaignchain-timespan' => array(
                            'disabled' => true,
                        )
                    )
                );

                $form = $this->createForm($campaignType, $toCampaign);

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $copyService = $this->get('campaignchain.campaign.repeating.copy');
                    $clonedCampaign = $copyService->scheduled2Repeating(
                        $fromCampaign,
                        $form->get('campaignchain_hook_campaignchain_date_repeat')->getData(),
                        $toCampaign->getName());

                    $this->addFlash(
                        'success',
                        'The campaign template <a href="' . $this->generateUrl('campaignchain_core_campaign_edit',
                            array('id' => $clonedCampaign->getId())) . '">' . $clonedCampaign->getName() . '</a> was copied successfully.'
                    );

                    return $this->redirectToRoute('campaignchain_core_campaign');
                }

                return $this->render(
                    'CampaignChainCoreBundle:Base:new.html.twig',
                    array(
                        'page_title' => 'Copy Scheduled Campaign as ' . static::CAMPAIGN_DISPLAY_NAME,
                        'form' => $form->createView(),
                    ));

                break;
        }
    }
}
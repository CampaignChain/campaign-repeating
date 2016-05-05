<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Campaign\RepeatingBundle\Controller;

use CampaignChain\Campaign\TemplateBundle\Controller\TemplateController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CampaignChain\CoreBundle\Entity\Campaign;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CampaignChain\CoreBundle\Entity\Module;
use CampaignChain\CoreBundle\Entity\Action;

class RepeatingController extends TemplateController
{
    const CAMPAIGN_DISPLAY_NAME = "Repeating Campaign";
    const BUNDLE_NAME = 'campaignchain/campaign-repeating';
    const MODULE_IDENTIFIER = 'campaignchain-repeating';
    const TRIGGER_HOOK = 'campaignchain-date-repeat';

    public function indexAction(){
        // Get the campaign templates
        $qb = $this->getDoctrine()->getEntityManager()->createQueryBuilder();
        $qb->select('c')
            ->from('CampaignChain\CoreBundle\Entity\Campaign', 'c')
            ->from('CampaignChain\CoreBundle\Entity\Module', 'm')
            ->from('CampaignChain\CoreBundle\Entity\Bundle', 'b')
            ->where('b.name = :bundleName')
            ->andWhere('m.identifier = :moduleIdentifier')
            ->andWhere('m.id = c.campaignModule')
            ->setParameter('bundleName', static::BUNDLE_NAME)
            ->setParameter('moduleIdentifier', static::MODULE_IDENTIFIER)
            ->orderBy('c.name', 'ASC');
        $query = $qb->getQuery();
        $repository_campaigns = $query->getResult();

        return $this->render(
            'CampaignChainCampaignRepeatingBundle::index.html.twig',
            array(
                'page_title' => static::CAMPAIGN_DISPLAY_NAME.'s',
                'repository_campaigns' => $repository_campaigns,
            ));
    }

    public function copyAction(Request $request, $id)
    {
        $campaignService = $this->get('campaignchain.core.campaign');
        $fromCampaign = $campaignService->getCampaign($id);
        $campaignURI = $campaignService->getCampaignURI($fromCampaign);

        switch($campaignURI){
            case 'campaignchain/campaign-repeating/campaignchain-repeating':
                $toCampaign = clone $fromCampaign;
                $toCampaign->setName($fromCampaign->getName().' (copied)');

                $campaignType = $this->get('campaignchain.core.form.type.campaign');
                $campaignType->setBundleName(static::BUNDLE_NAME);
                $campaignType->setModuleIdentifier(static::MODULE_IDENTIFIER);
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

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'The '.static::CAMPAIGN_DISPLAY_NAME.' <a href="'.$this->generateUrl(
                            'campaignchain_core_campaign_edit',
                            array('id' => $clonedCampaign->getId())).'">'.
                        $clonedCampaign->getName().'</a> was copied successfully.'
                    );

                    return $this->redirect($this->generateUrl('campaignchain_core_campaign'));
                }

                return $this->render(
                    'CampaignChainCoreBundle:Base:new.html.twig',
                    array(
                        'page_title' => 'Copy '.static::CAMPAIGN_DISPLAY_NAME,
                        'form' => $form->createView(),
                    ));
                break;
            case 'campaignchain/campaign-template/campaignchain-template':
                $toCampaign = clone $fromCampaign;
                $toCampaign->setName($fromCampaign->getName().' (copied)');

                $campaignType = $this->get('campaignchain.core.form.type.campaign');
                $campaignType->setBundleName(static::BUNDLE_NAME);
                $campaignType->setModuleIdentifier(static::MODULE_IDENTIFIER);
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

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'The '.static::CAMPAIGN_DISPLAY_NAME.' <a href="'.$this->generateUrl(
                            'campaignchain_core_campaign_edit',
                            array('id' => $clonedCampaign->getId())).'">'.
                        $clonedCampaign->getName().'</a> was copied successfully.'
                    );

                    return $this->redirect($this->generateUrl('campaignchain_core_campaign'));
                }

                return $this->render(
                    'CampaignChainCoreBundle:Base:new.html.twig',
                    array(
                        'page_title' => 'Copy '.static::CAMPAIGN_DISPLAY_NAME,
                        'form' => $form->createView(),
                    ));
                break;
            case 'campaignchain/campaign-scheduled/campaignchain-scheduled':
                $toCampaign = clone $fromCampaign;

                $toCampaign->setName($toCampaign->getName().' (copied)');
                $campaignType = $this->get('campaignchain.core.form.type.campaign');
                $campaignType->setBundleName(static::BUNDLE_NAME);
                $campaignType->setModuleIdentifier(static::MODULE_IDENTIFIER);
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

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'The campaign template <a href="'.$this->generateUrl('campaignchain_core_campaign_edit', array('id' => $clonedCampaign->getId())).'">'.$clonedCampaign->getName().'</a> was copied successfully.'
                    );

                    return $this->redirect($this->generateUrl('campaignchain_core_campaign'));
                }

                return $this->render(
                    'CampaignChainCoreBundle:Base:new.html.twig',
                    array(
                        'page_title' => 'Copy Scheduled Campaign as '.static::CAMPAIGN_DISPLAY_NAME,
                        'form' => $form->createView(),
                    ));

                break;
        }
    }
}
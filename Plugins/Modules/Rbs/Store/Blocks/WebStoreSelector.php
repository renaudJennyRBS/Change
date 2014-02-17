<?php
namespace Rbs\Store\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Store\Blocks\WebStoreSelector
 */
class WebStoreSelector extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('availableWebStoreIds', array());
		$parameters->addParameterMeta('billingAreaId');
		$parameters->addParameterMeta('templateName', 'webStoreSelector-horizontal.twig');

		$parameters->setLayoutParameters($event->getBlockLayout());

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$webStore = $commerceServices->getContext()->getWebStore();
		if ($webStore instanceof \Rbs\Store\Documents\WebStore)
		{
			$parameters->setParameterValue('webStoreId', $webStore->getId());
		}

		$billingArea = $commerceServices->getContext()->getBillingArea();
		if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
		{
			$parameters->setParameterValue('billingAreaId', $billingArea->getId());
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$data = array();
		foreach ($parameters->getParameter('availableWebStoreIds') as $webStoreId)
		{
			$webStore = $documentManager->getDocumentInstance($webStoreId);
			if ($webStore instanceof \Rbs\Store\Documents\WebStore)
			{
				$storeData = [
					'id' => $webStore->getId(),
					'title' => $webStore->getCurrentLocalization()->getTitle(),
					'billingAreas' => []
				];
				foreach ($webStore->getBillingAreas() as $billingArea)
				{
					/** @var $billingArea \Rbs\Price\Documents\BillingArea */
					$storeData['billingAreas'][] = [
						'id' => $billingArea->getId(),
						'title' => $billingArea->getCurrentLocalization()->getTitle()
					];
				}
				$data[] = $storeData;
			}
		}
		$attributes['webStoreData'] = $data;

		return $parameters->getParameter('templateName');
	}
}
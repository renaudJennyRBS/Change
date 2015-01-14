<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Brand;

/**
 * @name \Rbs\Brand\BrandManager
 */
class BrandManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'BrandManager';

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Brand/Events/BrandManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getBrandData', [$this, 'onDefaultGetBrandData'], 5);
	}

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * Default context:
	 *  - *dataSetNames, *visualFormats, *URLFormats
	 *  - website, websiteUrlManager, section, page, detailed
	 *  - *data
	 * @api
	 * @param \Rbs\Brand\Documents\Brand|integer $brand
	 * @param array $context
	 * @return array
	 */
	public function getBrandData($brand, array $context)
	{
		if (is_numeric($brand))
		{
			$brand = $this->getDocumentManager()->getDocumentInstance($brand);
		}

		if ($brand instanceof \Rbs\Brand\Documents\Brand)
		{
			$em = $this->getEventManager();
			$eventArgs = $em->prepareArgs(['brand' => $brand, 'context' => $context]);
			$em->trigger('getBrandData', $this, $eventArgs);
			if (isset($eventArgs['brandData']))
			{
				$reviewData = $eventArgs['brandData'];
				if (is_object($reviewData))
				{
					$callable = [$reviewData, 'toArray'];
					if (is_callable($callable))
					{
						$reviewData = call_user_func($callable);
					}
				}
				if (is_array($reviewData))
				{
					return $reviewData;
				}
			}
		}
		return [];
	}

	/**
	 * Input params: brand, context
	 * Output param: brandData
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetBrandData(\Change\Events\Event $event)
	{
		if (!$event->getParam('brandData'))
		{
			$brandDataComposer = new \Rbs\Brand\BrandDataComposer($event);
			$event->setParam('brandData', $brandDataComposer->toArray());
		}
	}
}
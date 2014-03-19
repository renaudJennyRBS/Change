<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\ProductSet
 */
class ProductSet extends \Compilation\Rbs\Catalog\Documents\ProductSet
{
	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATE, array($this, 'onDefaultCreate'), 10);
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATED, array($this, 'onDefaultCreated'));
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultCreate(\Change\Documents\Events\Event $event)
	{
		/* @var $productSet \Rbs\Catalog\Documents\ProductSet */
		$productSet = $event->getDocument();
		if (!$productSet->getLabel() && $productSet->getRootProduct())
		{
			$productSet->setLabel($productSet->getRootProduct()->getLabel());
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultCreated(\Change\Documents\Events\Event $event)
	{
		/* @var $productSet \Rbs\Catalog\Documents\ProductSet */
		$productSet = $event->getDocument();
		$product = $productSet->getRootProduct();
		if ($product instanceof \Rbs\Catalog\Documents\Product)
		{
			$product->setProductSet($productSet);
			$product->update();
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultRouteParamsRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultRouteParamsRestResult($event);
		$restResult = $event->getParam('restResult');

		/* @var $document \Rbs\Catalog\Documents\ProductSet */
		$document = $restResult->getDocument();
		$restResult->setProperty('rootProductId', $document->getRootProductId());
	}
}

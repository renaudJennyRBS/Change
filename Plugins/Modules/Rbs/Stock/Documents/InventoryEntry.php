<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Documents;

use Change\Documents\Events\Event;
use Change\I18n\PreparedKey;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Stock\Documents\InventoryEntry
 */
class InventoryEntry extends \Compilation\Rbs\Stock\Documents\InventoryEntry
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		$sku = $this->getSku();
		if ($sku)
		{
			return $sku->getCode();
		}
		return null;
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_CREATE, array($this, 'onDefaultCreate'), 5);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultCreate($event)
	{
		/* @var $document InventoryEntry */
		$document = $event->getDocument();
		if ($document->isNew())
		{

			// Get Inventory by SKU and Warehouse
			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$stockManager = $cs->getStockManager();
				$existingInventory = $stockManager->getInventoryEntry($document->getSku(), $document->getWarehouse());
				if ($existingInventory)
				{
					$warehouseLabel = $document->getWarehouseId();
					if ($warehouseLabel == null)
					{
						$warehouseLabel = $event->getApplicationServices()->getI18nManager()->trans('m.rbs.stock.admin.warehouse_default_label', array('ucf'));
					}
					$errors = $event->getParam('propertiesErrors', array());
					$errors['sku'][] = new PreparedKey('m.rbs.stock.admin.inventory_already_exists', array('ucf'), array('skuLabel' => $document->getSku()->getLabel(), 'warehouse' => $warehouseLabel));
					$event->setParam('propertiesErrors', $errors);
				}
			}
			else
			{
				$errors = $event->getParam('propertiesErrors', array());
				$errors['sku'][] = new PreparedKey('m.rbs.stock.admin.commerce_services_missing', array('ucf'));
				$event->setParam('propertiesErrors', $errors);
			}
		}
	}

	/**
	 * @api
	 */
	public function setUnlimited()
	{
		$this->setValueOfMovements(0);
		$this->setLevel(\Rbs\Stock\StockManager::UNLIMITED_LEVEL);
	}

	/**
	 * @api
	 * Requires an open transaction @see \Change\Transaction\TransactionManager::begin()
	 * @param integer $valueOfMovements
	 * @param boolean $forceUpdate
	 */
	public function updateValueOfMovements($valueOfMovements, $forceUpdate = false)
	{
		$valueOfMovements = intval($valueOfMovements);
		if ($this->getSku() && $this->getSku()->getUnlimitedInventory())
		{
			$valueOfMovements = 0;
		}

		if ($this->getValueOfMovements() !== $valueOfMovements || $forceUpdate)
		{
			$this->setValueOfMovements($valueOfMovements);
			$qb = $this->getDbProvider()->getNewStatementBuilder('InventoryEntry_updateValueOfMovements');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->getDocumentTable($this->getDocumentModel()->getRootName()));
				$qb->assign($fb->getDocumentColumn('valueOfMovements'), $fb->parameter('valueOfMovements'));
				$qb->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
			}

			$uq = $qb->updateQuery();
			$uq->bindParameter('valueOfMovements', $valueOfMovements);
			$uq->bindParameter('id', $this->getId());
			$uq->execute();


			$this->removeOldPropertyValue('valueOfMovements');
			$modifiedPropertyNames = ['valueOfMovements'];
			$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_UPDATED, $this,
				['modifiedPropertyNames' => $modifiedPropertyNames]);
			$this->getEventManager()->trigger($event);
		}
	}
}

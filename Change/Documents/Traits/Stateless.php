<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Traits;

use Change\Documents\AbstractDocument;
use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\PropertiesValidationException;

/**
 * @name \Change\Documents\Traits\Stateless
 *
 * From \Change\Documents\AbstractDocument
 * @method integer getPersistentState()
 * @method integer setPersistentState($newValue)
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Events\EventManager getEventManager()
 * @method string[] getModifiedPropertyNames()
 */
trait Stateless
{
	/**
	 * Load properties
	 * @api
	 */
	public function load()
	{
		if ($this->getPersistentState() === AbstractDocument::STATE_INITIALIZED)
		{
			$this->doLoad();
			$this->setPersistentState(AbstractDocument::STATE_LOADED);
			$event = new DocumentEvent(DocumentEvent::EVENT_LOADED, $this);
			$this->getEventManager()->trigger($event);
		}
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	abstract protected function doLoad();

	/**
	 * Call create() or update()
	 * @api
	 */
	public function save()
	{
		if ($this->getPersistentState() === AbstractDocument::STATE_NEW)
		{
			$this->create();
		}
		else
		{
			$this->update();
		}
	}

	/**
	 * @api
	 */
	public function create()
	{
		if ($this->getPersistentState() !== AbstractDocument::STATE_NEW)
		{
			throw new \RuntimeException('Document is not new', 51001);
		}

		$callable = array($this, 'onCreate');
		if (is_callable($callable))
		{
			call_user_func($callable);
		}
		$event = new DocumentEvent(DocumentEvent::EVENT_CREATE, $this);
		$this->getEventManager()->trigger($event);

		$propertiesErrors = $event->getParam('propertiesErrors');
		if (is_array($propertiesErrors) && count($propertiesErrors))
		{
			$e = new PropertiesValidationException('Invalid document properties.', 52000);
			$e->setPropertiesErrors($propertiesErrors);
			throw $e;
		}

		$this->doCreate();

		$this->setPersistentState(AbstractDocument::STATE_LOADED);
		$event = new DocumentEvent(DocumentEvent::EVENT_CREATED, $this);
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	abstract protected function doCreate();

	/**
	 * @api
	 */
	public function update()
	{
		if ($this->getPersistentState() === AbstractDocument::STATE_NEW)
		{
			throw new \RuntimeException('Document is new', 51002);
		}

		$callable = array($this, 'onUpdate');
		if (is_callable($callable))
		{
			call_user_func($callable);
		}

		$event = new DocumentEvent(DocumentEvent::EVENT_UPDATE, $this);
		$this->getEventManager()->trigger($event);

		$propertiesErrors = $event->getParam('propertiesErrors');
		if (is_array($propertiesErrors) && count($propertiesErrors))
		{
			$e = new PropertiesValidationException('Invalid document properties.', 52000);
			$e->setPropertiesErrors($propertiesErrors);
			throw $e;
		}

		$modifiedPropertyNames = $this->getModifiedPropertyNames();

		$this->doUpdate($modifiedPropertyNames);
		$this->setPersistentState(AbstractDocument::STATE_LOADED);

		$event = new DocumentEvent(DocumentEvent::EVENT_UPDATED, $this, array('modifiedPropertyNames' => $modifiedPropertyNames));
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @param string[] $modifiedPropertyNames
	 * @return void
	 */
	abstract protected function doUpdate($modifiedPropertyNames);

	/**
	 * @api
	 */
	public function delete()
	{
		//Already deleted
		if ($this->getPersistentState() === AbstractDocument::STATE_DELETED
			|| $this->getPersistentState() === AbstractDocument::STATE_DELETING
		)
		{
			return;
		}

		if ($this->getPersistentState() === AbstractDocument::STATE_NEW)
		{
			throw new \RuntimeException('Document is new', 51002);
		}

		$callable = array($this, 'onDelete');
		if (is_callable($callable))
		{
			call_user_func($callable);
		}
		$event = new DocumentEvent(DocumentEvent::EVENT_DELETE, $this);
		$this->getEventManager()->trigger($event);

		$this->doDelete();
		$this->setPersistentState(AbstractDocument::STATE_DELETED);

		$event = new DocumentEvent(DocumentEvent::EVENT_DELETED, $this);
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @throws \Exception
	 */
	abstract protected function doDelete();
}
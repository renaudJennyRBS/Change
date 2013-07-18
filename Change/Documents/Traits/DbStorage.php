<?php
namespace Change\Documents\Traits;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentManager;
use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\PropertiesValidationException;

/**
 * @name \Change\Documents\Traits\DbStorage
 *
 * From \Change\Documents\AbstractDocument
 * @method integer getPersistentState()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Zend\EventManager\EventManagerInterface getEventManager()
 * @method string[] getModifiedPropertyNames()
 * @method boolean hasModifiedProperties()
 * @method setModificationDate($dateTime)
 *
 * From \Change\Documents\Traits\Correction
 * @method saveCorrection()
 * @method populateCorrection()
 *
 * From \Change\Documents\Traits\Localized
 * @method deleteAllLocalizedPart()
 *
 * From \Change\Documents\Traits\Publication
 * @method string[] getValidPublicationStatusForCorrection()
 *
 */
trait DbStorage
{
	/**
	 * Load properties
	 * @api
	 */
	public function load()
	{
		if ($this->getPersistentState() === DocumentManager::STATE_INITIALIZED)
		{
			$this->doLoad();

			$event = new DocumentEvent(DocumentEvent::EVENT_LOADED, $this);
			$this->getEventManager()->trigger($event);
		}
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doLoad()
	{
		$this->getDocumentManager()->loadDocument($this);
		$callable = array($this, 'onLoad');
		if (is_callable($callable))
		{
			call_user_func($callable);
		}
	}

	/**
	 * @api
	 */
	public function save()
	{
		if ($this->getPersistentState() === DocumentManager::STATE_NEW)
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
		if ($this->getPersistentState() !== DocumentManager::STATE_NEW)
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

		$event = new DocumentEvent(DocumentEvent::EVENT_CREATED, $this);
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doCreate()
	{
		/* @var $document AbstractDocument|Localizable */
		$document = $this;

		$dm = $this->getDocumentManager();
		$dm->affectId($document);
		$dm->insertDocument($document);
		if ($document instanceof Localizable)
		{
			$document->saveCurrentLocalization();
		}
	}

	/**
	 * @api
	 */
	public function update()
	{
		if ($this->getPersistentState() === DocumentManager::STATE_NEW)
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
		if ($this->getDocumentModel()->useCorrection())
		{
			$this->populateCorrection();
		}

		$this->doUpdate();
		$event = new DocumentEvent(DocumentEvent::EVENT_UPDATED, $this, array('modifiedPropertyNames' => $modifiedPropertyNames));
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @throws \Exception
	 * @return void
	 */
	protected function doUpdate()
	{
		if ($this->getDocumentModel()->useCorrection())
		{
			$modified = $this->saveCorrection();
		}
		else
		{
			$modified = false;
		}

		$dm = $this->getDocumentManager();

		if ($this->hasModifiedProperties() || $modified)
		{
			$this->setModificationDate(new \DateTime());

			if ($this instanceof \Change\Documents\Interfaces\Editable)
			{
				$this->nextDocumentVersion();
			}

			$dm->updateDocument($this);

			if ($this instanceof Localizable)
			{
				$this->saveCurrentLocalization();
			}
		}
	}

	/**
	 * @api
	 */
	public function delete()
	{
		//Already deleted
		if ($this->getPersistentState() === DocumentManager::STATE_DELETED
			|| $this->getPersistentState() === DocumentManager::STATE_DELETING
		)
		{
			return;
		}

		if ($this->getPersistentState() === DocumentManager::STATE_NEW)
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

		$event = new DocumentEvent(DocumentEvent::EVENT_DELETED, $this);
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @throws \Exception
	 */
	protected function doDelete()
	{
		$dm = $this->getDocumentManager();
		$dm->deleteDocument($this);

		if ($this->getDocumentModel()->isLocalized())
		{
			$this->deleteAllLocalizedPart();
		}
	}

	// Metadata management

	/**
	 * @var array<String,String|String[]>
	 */
	private $metas;

	/**
	 * @var boolean
	 */
	private $modifiedMetas = false;

	/**
	 * @api
	 */
	public function saveMetas()
	{
		if ($this->modifiedMetas)
		{
			$this->getDocumentManager()->saveMetas($this, $this->metas);
			$this->modifiedMetas = false;
		}
	}

	protected function resetMetas()
	{
		$this->metas = null;
		$this->modifiedMetas = false;
	}

	/**
	 * @return void
	 */
	protected function checkMetasLoaded()
	{
		if ($this->metas === null)
		{
			$this->metas = $this->getDocumentManager()->loadMetas($this);
			$this->modifiedMetas = false;
		}
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function hasModifiedMetas()
	{
		return $this->modifiedMetas;
	}

	/**
	 * @api
	 * @return array
	 */
	public function getMetas()
	{
		$this->checkMetasLoaded();
		return $this->metas;
	}

	/**
	 * @api
	 * @param array $metas
	 */
	public function setMetas($metas)
	{
		$this->checkMetasLoaded();
		if (count($this->metas))
		{
			$this->metas = array();
			$this->modifiedMetas = true;
		}
		if (is_array($metas))
		{
			foreach ($metas as $name => $value)
			{
				$this->metas[$name] = $value;
			}
			$this->modifiedMetas = true;
		}
	}

	/**
	 * @api
	 * @param string $name
	 * @return boolean
	 */
	public function hasMeta($name)
	{
		$this->checkMetasLoaded();
		return isset($this->metas[$name]);
	}

	/**
	 * @api
	 * @param string $name
	 * @return mixed
	 */
	public function getMeta($name)
	{
		$this->checkMetasLoaded();
		return isset($this->metas[$name]) ? $this->metas[$name] : null;
	}

	/**
	 * @api
	 * @param string $name
	 * @param mixed|null $value
	 */
	public function setMeta($name, $value)
	{
		$this->checkMetasLoaded();
		if ($value === null)
		{
			if (isset($this->metas[$name]))
			{
				unset($this->metas[$name]);
				$this->modifiedMetas = true;
			}
		}
		elseif (!isset($this->metas[$name]) || $this->metas[$name] != $value)
		{
			$this->metas[$name] = $value;
			$this->modifiedMetas = true;
		}
	}
}
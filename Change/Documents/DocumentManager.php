<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents;

use Change\Db\Query\ResultsConverter;
use Change\Db\ScalarType;

/**
 * @name \Change\Documents\DocumentManager
 * @api
 */
class DocumentManager
{
	const EVENT_MANAGER_IDENTIFIER = 'Documents';

	/**
	 * @var integer
	 */
	protected $cycleCount = 0;

	/**
	 * Temporary identifier for new persistent document
	 * @var integer
	 */
	protected $newInstancesCounter = 0;

	/**
	 * @var string[] ex: "en_US" or "fr_FR"
	 */
	protected $LCIDStack = [];

	/**
	 * @var boolean
	 */
	protected $inTransaction = false;

	/**
	 * @var array
	 */
	protected $LCIDStackTransaction = [];

	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider = null;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager = null;

	/**
	 * @var \Change\Documents\ModelManager
	 */
	protected $modelManager = null;

	/**
	 * @var \Change\Events\EventManager
	 */
	protected $eventManager = null;

	/**
	 * @param \Change\Application $application
	 * @return $this
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * @return \Change\Events\EventManager
	 */
	protected function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$this->eventManager = $this->getApplication()->getNewEventManager(static::EVENT_MANAGER_IDENTIFIER);
			$this->eventManager->attach('injection', array($this, 'onDefaultInjection'), 5);
		}
		return $this->eventManager;
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		return $this->getApplication()->getLogging();
	}

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->getApplication()->getConfiguration();
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager(\Change\I18n\I18nManager $i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return $this
	 */
	public function setModelManager(\Change\Documents\ModelManager $modelManager)
	{
		$this->modelManager = $modelManager;
		return $this;
	}

	/**
	 * @api
	 * @return \Change\Documents\ModelManager
	 */
	public function getModelManager()
	{
		return $this->modelManager;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function beginTransaction(\Change\Events\Event $event)
	{
		if ($event->getParam('primary'))
		{
			$this->inTransaction = true;
		}
		$count = $event->getParam('count');
		$this->LCIDStackTransaction[$count] = $this->LCIDStack;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function commit(\Change\Events\Event $event)
	{
		if ($event->getParam('primary'))
		{
			$this->inTransaction = false;
			$this->reset();
		}
		$count = $event->getParam('count');
		unset($this->LCIDStackTransaction[$count]);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function rollBack(\Change\Events\Event $event)
	{

		$count = $event->getParam('count');
		if (isset($this->LCIDStackTransaction[$count]))
		{
			$this->LCIDStack = $this->LCIDStackTransaction[$count];
		}
		if ($event->getParam('primary'))
		{
			$this->LCIDStackTransaction = array();
			$this->inTransaction = false;
			$this->reset();
		}
	}

	/**
	 * @api
	 * @return bool
	 */
	public function inTransaction()
	{
		return $this->inTransaction;
	}

	public function shutdown()
	{
		if ($this->eventManager)
		{
			$this->reset();
		}
	}

	/**
	 * @api
	 * Cleanup all documents instance
	 */
	public function reset()
	{
		$this->getEventManager()->trigger('resetCache');
		$this->newInstancesCounter = 0;
	}

	/**
	 * @param string $cacheKey
	 * @return \Change\Db\Query\Builder
	 */
	protected function getNewQueryBuilder($cacheKey = null)
	{
		return $this->getDbProvider()->getNewQueryBuilder($cacheKey);
	}

	/**
	 * @param string $cacheKey
	 * @return \Change\Db\Query\StatementBuilder
	 */
	protected function getNewStatementBuilder($cacheKey = null)
	{
		return $this->getDbProvider()->getNewStatementBuilder($cacheKey);
	}

	/**
	 * @param string $modelName
	 * @throws \InvalidArgumentException
	 * @return AbstractDocument
	 */
	public function getNewDocumentInstanceByModelName($modelName)
	{
		$model = $this->getModelManager()->getModelByName($modelName);
		if ($model === null)
		{
			throw new \InvalidArgumentException('Invalid model name (' . $modelName . ')', 50002);
		}
		return $this->getNewDocumentInstanceByModel($model);
	}

	/**
	 * @param AbstractModel $model
	 * @throws \RuntimeException
	 * @return AbstractDocument
	 */
	public function getNewDocumentInstanceByModel(AbstractModel $model)
	{
		$newDocument = $this->createNewDocumentInstance($model);
		$this->newInstancesCounter--;
		$newDocument->initialize($this->newInstancesCounter, AbstractDocument::STATE_NEW);
		$newDocument->setDefaultValues($model);
		return $newDocument;
	}

	/**
	 * @param AbstractModel $model
	 * @throws \RuntimeException
	 * @return AbstractDocument
	 */
	protected function createNewDocumentInstance(AbstractModel $model)
	{
		if ($model->isAbstract())
		{
			throw new \RuntimeException('Unable to create instance of abstract model: ' . $model, 999999);
		}
		$className = $model->getDocumentClassName();
		if (!class_exists($className))
		{
			throw new \RuntimeException('Class could not be loaded ' . $className, 999999);
		}

		/* @var $document AbstractDocument */
		$document = new $className($model);
		$document->setApplication($this->getApplication())
			->setDocumentManager($this)
			->setDbProvider($this->dbProvider);
		$this->getEventManager()->trigger('injection', $this, array('document' => $document));
		return $document;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultInjection(\Change\Events\Event $event)
	{
		$document = $event->getParam('document');
		if ($document instanceof AbstractDocument)
		{
			$document->onDefaultInjection($event);
		}
	}

	/**
	 * @api
	 * @param integer $documentId
	 * @param AbstractModel|String $model
	 * @return AbstractDocument|null
	 */
	public function getDocumentInstance($documentId, $model = null)
	{
		$id = intval($documentId);
		if ($id <= 0)
		{
			return null;
		}

		if (is_string($model))
		{
			$modelName = $model;
			$model = $this->getModelManager()->getModelByName($modelName);
			if ($model === null)
			{
				$this->getLogging()->warn(__METHOD__ . ' Invalid document model name: ' . $modelName);
				return null;
			}
		}

		$document = $this->getFromCache($id);
		if ($document !== null)
		{
			if ($document && $model)
			{
				if ($document->getDocumentModelName() !== $model->getName()
					&& !in_array($model->getName(), $document->getDocumentModel()->getAncestorsNames())
				)
				{
					$this->getLogging()->warn(
						__METHOD__ . ' Invalid document model name: ' . $document->getDocumentModelName() . ', '
						. $model->getName() . ' Expected');
					return null;
				}
			}
			return $document;
		}

		$this->gcCache();

		if ($model)
		{
			if ($model->isAbstract())
			{
				return null;
			}
			elseif ($model->isStateless())
			{
				$document = $this->createNewDocumentInstance($model);
				$document->initialize($id, AbstractDocument::STATE_INITIALIZED);
				$document->load();
				return $document;
			}
		}

		$qb = $this->getNewQueryBuilder(__METHOD__ . ($model ? $model->getRootName() : 'std'));
		if (!$qb->isCached())
		{

			$fb = $qb->getFragmentBuilder();
			if ($model)
			{
				$qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'))
					->from($fb->getDocumentTable($model->getRootName()))
					->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
			}
			else
			{
				$qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'))
					->from($fb->getDocumentIndexTable())
					->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
			}
		}

		$query = $qb->query();
		$query->bindParameter('id', $id);

		$constructorInfos = $query->getFirstResult();
		if ($constructorInfos)
		{
			$modelName = $constructorInfos['model'];
			$documentModel = $this->getModelManager()->getModelByName($modelName);
			if ($documentModel !== null && !$documentModel->isAbstract())
			{
				$document = $this->createNewDocumentInstance($documentModel);
				$document->initialize($id, AbstractDocument::STATE_INITIALIZED);
				return $document;
			}
			else
			{
				$this->getLogging()->error(__METHOD__ . ' Invalid model name: ' . $modelName);
			}
		}
		else
		{
			$this->getLogging()->info('Document id ' . $id . ' not found');
		}

		return null;
	}

	/**
	 * @param AbstractDocument $document
	 */
	public function reference(AbstractDocument $document)
	{
		if ($document->getId() > 0)
		{
			$this->getEventManager()->trigger('setInCache', $this, ['document' => $document]);
		}
	}

	/**
	 * @api
	 * @param $documentId
	 * @return boolean
	 */
	public function isInCache($documentId)
	{
		return $this->getFromCache($documentId) !== null;
	}

	/**
	 * @api
	 * @param integer $documentId
	 * @return AbstractDocument|null
	 */
	public function getFromCache($documentId)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['id' => intval($documentId)]);
		$eventManager->trigger('getFromCache', $this, $args);
		return isset($args['document']) ? $args['document'] : null;
	}

	protected function gcCache()
	{
		if (!$this->inTransaction)
		{
			$this->cycleCount++;
			if ($this->cycleCount % 100 === 0)
			{
				$this->reset();
			}
		}
	}

	/**
	 * @api
	 * @param string|AbstractModel $modelOrModelName
	 * @param string $LCID if null use refLCID of document
	 * @return \Change\Documents\Query\Query
	 */
	public function getNewQuery($modelOrModelName, $LCID = null)
	{
		$query = new \Change\Documents\Query\Query($modelOrModelName, $this, $this->getModelManager(), $this->getDbProvider());
		if ($LCID)
		{
			$query->setLCID($LCID);
		}
		return $query;
	}

	/**
	 * @api
	 * @param AbstractDocument $document
	 * @param array $backupData
	 * @return integer
	 */
	public function insertDocumentBackup(AbstractDocument $document, array $backupData)
	{
		$qb = $this->getNewStatementBuilder(__METHOD__);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->getDocumentDeletedTable(), $fb->getDocumentColumn('id'), $fb->getDocumentColumn('model'),
				'deletiondate', 'datas')
				->addValues($fb->integerParameter('id'), $fb->parameter('model'),
					$fb->dateTimeParameter('deletiondate'),
					$fb->lobParameter('datas'));
		}

		$iq = $qb->insertQuery();
		$iq->bindParameter('id', $document->getId());
		$iq->bindParameter('model', $document->getDocumentModelName());
		$iq->bindParameter('deletiondate', new \DateTime());
		$iq->bindParameter('datas', json_encode($backupData));
		return $iq->execute();
	}

	/**
	 * @api
	 * @param integer $documentId
	 * @return array|null
	 */
	public function getBackupData($documentId)
	{
		$qb = $this->getNewQueryBuilder(__METHOD__);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->getDocumentColumn('model'), 'model'), 'deletiondate', 'datas')
				->from($fb->getDocumentDeletedTable())
				->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
		}

		$sq = $qb->query();
		$sq->bindParameter('id', $documentId);

		$converter = new ResultsConverter($sq->getDbProvider(), array('datas' => ScalarType::TEXT,
			'deletiondate' => ScalarType::DATETIME));

		$row = $sq->getFirstResult(array($converter, 'convertRow'));
		if ($row !== null)
		{
			$datas = json_decode($row['datas'], true);
			$datas['id'] = intval($documentId);
			$datas['model'] = $row['model'];
			$datas['deletiondate'] = $row['deletiondate'];
			return $datas;
		}
		return null;
	}

	// Working lang.
	/**
	 * Get the current lcid.
	 * @api
	 * @return string ex: "en_US" or "fr_FR"
	 */
	public function getLCID()
	{
		if (count($this->LCIDStack) > 0)
		{
			return end($this->LCIDStack);
		}
		else
		{
			return $this->getI18nManager()->getLCID();
		}
	}

	/**
	 * Push a new working language code.
	 * @api
	 * @throws \InvalidArgumentException
	 * @param string $LCID ex: "fr_FR"
	 */
	public function pushLCID($LCID)
	{
		if (!$this->getI18nManager()->isSupportedLCID($LCID))
		{
			throw new \InvalidArgumentException('Invalid LCID argument', 51012);
		}
		array_push($this->LCIDStack, $LCID);
	}

	/**
	 * Pop the last working language code.
	 * @api
	 * @throws \LogicException if there is no lang to pop
	 * @throws \Exception if provided
	 * @param \Exception $exception
	 */
	public function popLCID($exception = null)
	{
		if ($this->getLCIDStackSize() === 0)
		{
			if ($exception === null)
			{
				$exception = new \LogicException('Invalid LCID Stack size', 51013);
			}
		}
		else
		{
			array_pop($this->LCIDStack);
		}

		if ($exception !== null)
		{
			throw $exception;
		}
	}

	/**
	 * Get the lang stack size.
	 * @api
	 * @return integer
	 */
	public function getLCIDStackSize()
	{
		return count($this->LCIDStack);
	}
}
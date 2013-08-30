<?php
namespace Change\Documents;

use Change\Application\ApplicationServices;
use Change\Db\Query\ResultsConverter;
use Change\Db\ScalarType;
use Change\Transaction\TransactionManager;

/**
 * @name \Change\Documents\DocumentManager
 * @api
 */
class DocumentManager
{
	/**
	 * @var ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var DocumentServices
	 */
	protected $documentServices;

	/**
	 * Document instances by id
	 * @var array<integer, \Change\Documents\AbstractDocument>
	 */
	protected $documentInstances = array();

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
	protected $LCIDStack = array();

	/**
	 * @var boolean
	 */
	protected $inTransaction = false;

	/**
	 * @var array
	 */
	protected $LCIDStackTransaction = array();

	/**
	 * @param ApplicationServices $applicationServices
	 */
	public function setApplicationServices(ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;

		$tm = $applicationServices->getTransactionManager();
		$this->inTransaction = $tm->started();
		$tem = $tm->getEventManager();

		$tem->attach(TransactionManager::EVENT_BEGIN, array($this, 'beginTransaction'));
		$tem->attach(TransactionManager::EVENT_COMMIT, array($this, 'commit'));
		$tem->attach(TransactionManager::EVENT_ROLLBACK, array($this, 'rollBack'));
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function beginTransaction(\Zend\EventManager\Event $event)
	{
		if ($event->getParam('primary'))
		{
			$this->inTransaction = true;
		}
		$count = $event->getParam('count');
		$this->LCIDStackTransaction[$count] = $this->LCIDStack;
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function commit(\Zend\EventManager\Event $event)
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
	 * @param \Zend\EventManager\Event $event
	 */
	public function rollBack(\Zend\EventManager\Event $event)
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
	 * @return bool
	 */
	public function inTransaction()
	{
		return $this->inTransaction;
	}

	/**
	 * @return ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param DocumentServices $documentServices
	 */
	public function setDocumentServices(DocumentServices $documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return DocumentServices
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	public function __destruct()
	{
		$this->reset();
	}

	/**
	 * Cleanup all documents instance
	 */
	public function reset()
	{
		array_map(function (AbstractDocument $document)
		{
			$document->cleanUp();
		}, $this->documentInstances);
		$this->documentInstances = array();
		$this->newInstancesCounter = 0;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->applicationServices->getDbProvider();
	}

	/**
	 * @param string $cacheKey
	 * @return \Change\Db\Query\Builder
	 */
	protected function getNewQueryBuilder($cacheKey = null)
	{
		return $this->applicationServices->getDbProvider()->getNewQueryBuilder($cacheKey);
	}

	/**
	 * @param string $cacheKey
	 * @return \Change\Db\Query\StatementBuilder
	 */
	protected function getNewStatementBuilder($cacheKey = null)
	{
		return $this->applicationServices->getDbProvider()->getNewStatementBuilder($cacheKey);
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->getApplicationServices()->getI18nManager();
	}

	/**
	 * @api
	 * @return \Change\Documents\ModelManager
	 */
	public function getModelManager()
	{
		return $this->getDocumentServices()->getModelManager();
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
		return new $className($this->getDocumentServices(), $model);
	}

	/**
	 * @param integer $documentId
	 * @param AbstractModel $model
	 * @return AbstractDocument|null
	 */
	public function getDocumentInstance($documentId, AbstractModel $model = null)
	{
		$id = intval($documentId);
		if ($id <= 0)
		{
			return null;
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
					$this->applicationServices->getLogging()->warn(
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
				$this->applicationServices->getLogging()->error(__METHOD__ . ' Invalid model name: ' . $modelName);
			}
		}
		else
		{
			$this->applicationServices->getLogging()->info('Document id ' . $id . ' not found');
		}

		return null;
	}

	/**
	 * @param AbstractDocument $document
	 */
	public function reference(AbstractDocument $document)
	{
		$documentId = $document->getId();
		if ($documentId > 0)
		{
			$this->documentInstances[$documentId] = $document;
		}
	}

	/**
	 * @param $documentId
	 * @return boolean
	 */
	public function isInCache($documentId)
	{
		return $this->getFromCache($documentId) !== null;
	}

	/**
	 * @param integer $documentId
	 * @return AbstractDocument|null
	 */
	public function getFromCache($documentId)
	{
		$id = intval($documentId);
		return isset($this->documentInstances[$id]) ? $this->documentInstances[$id] : null;
	}

	protected function gcCache()
	{
		if (!$this->inTransaction)
		{
			$this->cycleCount++;
			if ($this->cycleCount % 100 === 0)
			{
				$this->applicationServices->getLogging()->info(__METHOD__ . ': ' . count($this->documentInstances));
				$this->reset();
			}
		}
	}
	/**
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
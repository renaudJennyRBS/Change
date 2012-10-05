<?php
namespace Change\Db;

/**
 * @name \Change\Db\Provider
 * @method \Change\Db\Provider getInstance()
 */
abstract class Provider extends \Change\AbstractSingleton
{	
	/**
	 * @var integer
	 */
	protected $id;	
	
	/**
	 * @var array
	 */
	protected $connectionInfos;
	
	/**
	 * @var array
	 */
	protected $timers;
	
	/**
	 * Document instances by id
	 * @var array<integer, \Change\Documents\AbstractDocument>
	 */
	protected $m_documentInstances = array();
	
	/**
	 * I18nDocument instances by id
	 * @var array<integer, \f_persistentdocument_I18nPersistentDocument> TODO Old class Usage
	*/
	protected $m_i18nDocumentInstances = array();
	
	/**
	 * @var array
	 */
	protected $m_tmpRelation = array();
	
	/**
	 * Temporay identifier for new persistent document
	 * @var Integer
	 */
	protected $m_newInstancesCounter = 0;
	
	/**
	 * @var integer
	 */
	protected $transactionCount = 0;
	
	/**
	 * @var boolean
	 */
	protected $transactionDirty = false;
	
	/**
	 * @var boolean
	 */
	protected $m_inTransaction = false;
	
	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}	
	
	/**
	 * @return string
	 */
	public abstract function getType();
	
	
	protected function __construct()
	{
		parent::__construct();
		$connectionInfos = \Change\Application::getInstance()->getConfiguration()->getEntry('databases/default', array());
		$this->connectionInfos = $connectionInfos;
		$this->timers = array('init' => microtime(true), 'longTransaction' => isset($connectionInfos['longTransaction']) ? floatval($connectionInfos['longTransaction']) : 0.2);
	}	
	
	public function __destruct()
	{
		if ($this->hasTransaction())
		{
			\Change\Application\LoggingManager::getInstance()->warn(__METHOD__ . ' called while active transaction (' . $this->transactionCount . ')');
		}
	}
	
	protected final function checkDirty()
	{
		if ($this->transactionDirty)
		{
			throw new \Exception('Transaction is dirty');
		}
	}
	
	/**
	 * @return void
	 */
	public function beginTransaction()
	{
		$this->checkDirty();
		if ($this->transactionCount == 0)
		{
			$this->transactionCount++;
			if ($this->m_inTransaction)
			{

				\Change\Application\LoggingManager::getInstance()->warn(get_class($this) . " while already in transaction");
			}
			else
			{
				$this->timers['bt'] = microtime(true);
				$this->beginTransactionInternal();
				$this->m_inTransaction = true;
				//TODO Old class Usage
				\indexer_IndexService::getInstance()->beginIndexTransaction();
			}
		}
		else
		{
			$embededTransaction = intval(\Change\Application::getInstance()->getConfiguration()->getEntry('databases/default/embededTransaction', '5'));
			$this->transactionCount++;
			if ($this->transactionCount > $embededTransaction)
			{
				\Change\Application\LoggingManager::getInstance()->warn('embeded transaction: ' . $this->transactionCount);
			}
		}
	}
	
	/**
	 * @param boolean $isolatedWrite make sense in the context of read-write separated database. Set to true if the next client request does not care about the data you wrote. It will then perform reads on read database.
	 * @throws Exception if bad transaction count
	 * @return void
	 */
	public function commit($isolatedWrite = false)
	{
		$this->checkDirty();
		if ($this->transactionCount <= 0)
		{
			throw new \Exception('commit-bad-transaction-count ('.$this->transactionCount.')');
		}
		if ($this->transactionCount == 1)
		{
			if (!$this->m_inTransaction)
			{
				\Change\Application\LoggingManager::getInstance()->warn("PersistentProvider->commit() called while not in transaction");
			}
			else
			{
				$this->commitInternal();
				$duration = round(microtime(true) - $this->timers['bt'], 4);
				if ($duration > $this->timers['longTransaction'])
				{
					\Change\Application\LoggingManager::getInstance()->warn('Long Transaction detected '.  number_format($duration, 3) . 's > ' . $this->timers['longTransaction']);
				}
				$this->m_inTransaction = false;		
				$this->beginTransactionInternal();
				//TODO Old class Usage
				\indexer_IndexService::getInstance()->commitIndex();
				$this->commitInternal();
			}
		}
		$this->transactionCount--;
	}
	
	/**
	 * cancel transaction.
	 * @param Exception $e
	 * @throws BaseException('rollback-bad-transaction-count') if rollback called while no transaction
	 * @throws \Change\Db\Exception\TransactionCancelledException on embeded transaction
	 * @return Exception the given exception so it is easy to throw it
	 */
	public function rollBack($e = null)
	{
		\Change\Application\LoggingManager::getInstance()->warn('TransactionManager->rollBack called');
		if ($this->transactionCount == 0)
		{
			\Change\Application\LoggingManager::getInstance()->warn('TransactionManager->rollBack() => bad transaction count (no transaction)');
			throw new \Exception('rollback-bad-transaction-count');
		}
		$this->transactionCount--;
		
		if (!$this->transactionDirty)
		{
			$this->transactionDirty = true;
			if (!$this->m_inTransaction)
			{
				\Change\Application\LoggingManager::getInstance()->warn("PersistentProvider->rollBack() called while not in transaction");
			}
			else
			{
				$this->clearDocumentCache();
				//TODO Old class Usage
				\indexer_IndexService::getInstance()->rollBackIndex();
				$this->rollBackInternal();
				$this->m_inTransaction = false;
			}
		}
		
		if ($this->transactionCount == 0)
		{
			$this->transactionDirty = false;
		}
		else
		{
			if (!($e instanceof \Change\Db\Exception\TransactionCancelledException))
			{
				$e = new \Change\Db\Exception\TransactionCancelledException($e);
			}
			throw $e;
		}
		return ($e instanceof \Change\Db\Exception\TransactionCancelledException) ? $e->getPrevious() : $e;
	}
	
	/**
	 * @return boolean
	 */
	public function hasTransaction()
	{
		return $this->transactionCount > 0;
	}
	
	/**
	 * @return boolean
	 */
	public function isTransactionDirty()
	{
		return $this->transactionDirty;
	}
	
	/**
	 * @deprecated
	 * @return \Change\Db\Provider
	 */
	public function getPersistentProvider()
	{
		return $this;
	}
		
	/**
	 * @return array
	 */
	public function getConnectionInfos()
	{
		return $this->connectionInfos;
	}	
	
	/**
	 * @return boolean
	 */
	public abstract function checkConnection();
	
	/**
	 * @return void
	 */
	public abstract function closeConnection();
	
	/**
	 * @param string $sql
	 * @param \Change\Db\StatmentParameter[] $parameters
	 * @return \Change\Db\AbstractStatment
	 */
	public abstract function createNewStatment($sql, $parameters = null);

	/**
	 * @return \Change\Db\InterfaceSchemaManager
	 */
	public abstract function getSchemaManager();	
	
	/**
	 * @param boolean $useDocumentCache
	 * @return \Change\Db\Provider
	 */
	public abstract function setDocumentCache($useDocumentCache);

	/**
	 * @return void
	 */
	public function reset()
	{
		$this->clearDocumentCache();
	}
	
	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	protected function isInCache($documentId)
	{
		return isset($this->m_documentInstances[intval($documentId)]);
	}
	
	/**
	 * @param integer $documentId
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function getFromCache($documentId)
	{
		return $this->m_documentInstances[intval($documentId)];
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $doc
	 * @param string $lang
	 * @return \f_persistentdocument_I18nPersistentDocument|NULL TODO Old class Usage
	 */
	protected function getI18nDocumentFromCache($doc, $lang)
	{
		$docId = intval($doc->getId());
		if (isset($this->m_i18nDocumentInstances[$docId]))
		{
			if (isset($this->m_i18nDocumentInstances[$docId][$lang]))
			{
				return $this->m_i18nDocumentInstances[$docId][$lang];
			}
		}
		else
		{
			$this->m_i18nDocumentInstances[$docId] = array();
		}
		return null;
	}
	
	/**
	 * @param integer $documentId
	 * @param \Change\Documents\AbstractDocument $document
	 * @return void
	 */
	protected function putInCache($documentId, $document)
	{
		$documentId = intval($documentId);
		$this->m_documentInstances[$documentId] = $document;
		if ($document->getPersistentModel()->isLocalized() && $document->getRawI18nVoObject() !== null)
		{
			$this->m_i18nDocumentInstances[$documentId][$document->getLang()] = $document->getRawI18nVoObject();
		}
	}
	
	/**
	 * @param integer $documentId
	 * @return void
	 */
	protected function deleteFromCache($documentId)
	{
		unset($this->m_documentInstances[$documentId]);	
	}	
	
	/**
	 * @return void
	 */
	protected function clearDocumentCache()
	{
		$this->m_documentInstances = array();
		$this->m_i18nDocumentInstances = array();
	}	
	

	/**
	 * @return void
	 */
	protected abstract function beginTransactionInternal();
		
	/**
	 * @return void
	 */	
	protected abstract function commitInternal();
		
	/**
	 * @return void
	 */	
	protected abstract function rollBackInternal();	
	
	/**
	 * Return a instance of the document[@id = $id and @modelName = $modelName]
	 *
	 * @param integer $id
	 * @param string $modelName
	 * @param integer $treeId
	 * @param array $I18nInfoArray
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function getDocumentInstanceWithModelName($id, $modelName, $treeId, $I18nInfoArray)
	{
		if (!$this->isInCache($id))
		{
			$className = $this->getDocumentClassFromModel($modelName);
			$i18nInfo = (count($I18nInfoArray) === 0) ? null : \Change\Documents\I18nInfo::getInstanceFromArray($I18nInfoArray);
			$doc = new $className($id, $i18nInfo, $treeId);
			$this->putInCache($id, $doc);
			return $doc;
		}
		return $this->getFromCache($id);
	}
		
	/**
	 * @param string $documentModelName
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getNewDocumentInstance($documentModelName)
	{
		$this->m_newInstancesCounter--;
		$className = $this->getDocumentClassFromModel($documentModelName);
		return new $className($this->m_newInstancesCounter);
	}
	
	/**
	 * Return the persistent document class name from the document model name
	 * @param string $modelName
	 * @return string
	 */
	protected function getDocumentClassFromModel($modelName)
	{
		//TODO Old class Usage
		return \f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName($modelName)->getDocumentClassName();
	}
	
	/**
	 * Return the I18n persistent document class name from the document model name
	 * @param string $modelName
	 * @return string
	 */
	protected function getI18nDocumentClassFromModel($modelName)
	{
		return $this->getDocumentClassFromModel($modelName).'I18n';
	}
		
	/**
	 * Return the model name of the document or false
	 * @param integer $id
	 * @return string|false
	 */
	public abstract function getDocumentModelName($id);
	
	/**
	 * Return a instance of the document or null
	 * @param integer $documentId
	 * @return \Change\Documents\AbstractDocument|NULL
	 */
	public abstract function getDocumentInstanceIfExist($documentId);
	
	/**
	 * Return a instance of the document or Exception if the document not found
	 * @param integer $documentId
	 * @param string $modelName
	 * @param string $lang
	 * @return \Change\Documents\AbstractDocument
	 * @throws Exception
	 */
	public abstract function getDocumentInstance($documentId, $modelName = null, $lang = null);
	
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return integer
	 */
	public function getCachedDocumentId($document)
	{
		$id = $document->getId();
		if ($id < 0) 
		{
			$this->putInCache($id, $document);
			$this->m_tmpRelation[$id] = $id;
		}
		return $id;
	}
	
	/**
	 * @param integer $cachedId
	 * @return \Change\Documents\AbstractDocument
	 * @throws Exception
	 */
	public function getCachedDocumentById($cachedId)
	{
		if ($cachedId < 0)
		{
			$id = isset($this->m_tmpRelation[$cachedId]) ? $this->m_tmpRelation[$cachedId] : $cachedId;
			if ($this->isInCache($id))
			{
				return $this->getFromCache($id);
			}
			throw new \Exception('document ' . $cachedId . '/'. $id . ' is not in memory');
		}
		return $this->getDocumentInstance($cachedId);
	}
	
	protected function setCachedRelation($cachedId, $documentId)
	{
		if (isset($this->m_tmpRelation[$cachedId]))
		{
			$this->m_tmpRelation[$cachedId] = $documentId;
		}
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $modelName
	 * @throws \Exception
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function checkModelCompatibility($document, $modelName)
	{
		if ($modelName !== null && !$document->getPersistentModel()->isModelCompatible($modelName))
		{
			throw new \Exception('document ' . $document->getId() . ' is a ' . $document->getDocumentModelName() . ' but not a ' . $modelName);
		}
		return $document;
	}
	
	/**
	 * When we want to get a document, the data is not loaded. When we want to access to it,
	 * this function is called for giving all data to the object.
	 *
	 * @param \Change\Documents\AbstractDocument $document
	 * @throws Exception
	 */
	public abstract function loadDocument($document);
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $lang
	 * @return \f_persistentdocument_I18PersistentDocument
	 */
	public abstract function getI18nDocument($document, $lang, $isVo = false);
	
	/**
	 * @param string $propertyName
	 * @return integer
	 */
	public abstract function getRelationId($propertyName);
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $propertyName
	 */
	public abstract function loadRelations($document, $propertyName);
	

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public abstract function insertDocument($document);
	
	/**
	 * Update a document.
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public abstract function updateDocument($document);

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public abstract function deleteDocument($document);
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Documents\AbstractDocument $destDocument
	 * @return \Change\Documents\AbstractDocument the result of mutation (destDocument)
	 */
	public abstract function mutate($document, $destDocument);

	/**
	 * @param string $documentModelName
	 * @return f_persistentdocument_criteria_Query TODO Old class Usage
	 */
	public function createQuery($documentModelName = null, $includeChildren = true)
	{
		$query = new \f_persistentdocument_criteria_QueryImpl();
		if (!is_null($documentModelName))
		{
			$query->setDocumentModelName($documentModelName, $includeChildren);
		}
		return $query;
	}
	
	/**
	 * @param \f_persistentdocument_criteria_Query $query TODO Old class Usage
	 * @return \Change\Documents\AbstractDocument[]
	 */
	public abstract function find($query);
	
	/**
	 * If the query has some projection, retrieve one of them into a dedicated array
	 * @param \f_persistentdocument_criteria_Query $query TODO Old class Usage
	 * @param string $columnName the name of the projection
	 * @return mixed[]
	*/
	public function findColumn($query, $columnName)
	{
		if (!$query->hasProjectionDeep())
		{
			throw new \Exception("Could not find column if there is no projection");
		}
		$rows = $this->find($query);
		if (count($rows) == 0)
		{
			return $rows;
		}
		$result = array();
		if (!array_key_exists($columnName, $rows[0]))
		{
			throw new \Exception("Column $columnName not found in query");
		}
		foreach ($rows as $row)
		{
			$result[] = $row[$columnName];
		}
		return $result;
	}
	
	/**
	 * @param f_persistentdocument_criteria_QueryIntersection $intersection TODO Old class Usage
	 * @return \Change\Documents\AbstractDocument[]
	 */
	public function findIntersection($intersection)
	{
		$ids = $this->findIntersectionIds($intersection);
		if (count($ids) == 0)
		{
			return array();
		}
		//TODO Old class Usage
		return $this->find($this->createQuery($intersection->getDocumentModel()->getName())->add(\Restrictions::in("id", $ids)));
	}
	
	/**
	 * @param f_persistentdocument_criteria_QueryIntersection $intersection TODO Old class Usage
	 * @return integer[]
	 */
	public function findIntersectionIds($intersection)
	{
		// TODO: merge queries that are "mergeable"
		// TODO: here we may have queries on different compatible models. Restrict queries to
		//		 the most specific model to reduce the number of returned ids to intersect?
		$idRows = null;
		foreach ($intersection->getQueries() as $groupedQuery)
		{
			if (method_exists($groupedQuery, 'getIds'))
			{
				$ids = $groupedQuery->getIds();
				$result = array();
				foreach ($ids as $id)
				{
					$result[] = array("id" => $id);
				}
			}
			else
			{
				$this->addIdProjectionIfNeeded($groupedQuery);
				$result = $groupedQuery->find();
			}
			if ($idRows === null)
			{
				$idRows = $result;
			}
			else
			{
				$idRows = array_uintersect($idRows, $result, array($this, "compareRows"));
			}
		}
	
		return array_map(array($this, "getIdFromRow"), $idRows);
	}
	
	private function compareRows($row1, $row2)
	{
		return (int)$row1["id"] - (int)$row2["id"];
	}
	
	private function getIdFromRow($row)
	{
		return $row["id"];
	}
	
	protected function addIdProjectionIfNeeded($groupedQuery)
	{
		$hasIdProjection = false;
		$hasThisProjection = false;
		$newProjections = array();
		if ($groupedQuery->hasProjection())
		{
			foreach ($groupedQuery->getProjection() as $projection)
			{
				//TODO Old class Usage
				if ($projection instanceof \f_persistentdocument_criteria_ThisProjection)
				{
					// FIXME: remove other documentProjections ... ?
					$hasThisProjection = true;
					continue;
				}
				//TODO Old class Usage
				if ($projection instanceof \f_persistentdocument_criteria_PropertyProjection)
				{
					if ($projection->getAs() == "id")
					{
						$hasIdProjection = true;
						// continue; // FIXME .. ?
					}
				}
				$newProjections[] = $projection;
			}
		}
		elseif ($groupedQuery->hasHavingCriterion() && !$groupedQuery->hasProjection())
		{
			// implicit this projection
			$hasThisProjection = true;
		}
	
		if (!$hasIdProjection)
		{
			if ($hasThisProjection || $groupedQuery->hasHavingCriterion())
			{
				//TODO Old class Usage
				$newProjections[] = \Projections::groupProperty("id");
			}
			else
			{
				//TODO Old class Usage
				$newProjections[] = \Projections::property("id");
			}
		}
		$groupedQuery->setProjectionArray($newProjections);
	}
	
	/**
	 * @param f_persistentdocument_criteria_QueryUnion $union TODO Old class Usage
	 * @return integer[]
	 */
	public function findUnionIds($union)
	{
		// TODO: use UNION SQL operator
		$idRows = array();
		foreach ($union->getQueries() as $groupedQuery)
		{
			if (method_exists($groupedQuery, 'getIds'))
			{
				$ids = $groupedQuery->getIds();
				$newIdRows = array();
				foreach ($ids as $id)
				{
					$newIdRows[] = array("id" => $id);
				}
			}
			else
			{
				$this->addIdProjectionIfNeeded($groupedQuery);
				$newIdRows = $groupedQuery->find();
			}
			$idRows = array_merge($idRows, $newIdRows);
		}
	
		return array_unique(array_map(array($this, "getIdFromRow"), $idRows));
	}
	
	/**
	 * @param f_persistentdocument_criteria_QueryIntersection $union TODO Old class Usage
	 * @return \Change\Documents\AbstractDocument[]
	 */
	public function findUnion($union)
	{
		$ids = $this->findUnionIds($union);
		if (count($ids) == 0)
		{
			return array();
		}
		//TODO Old class Usage
		return $this->find($this->createQuery($union->getDocumentModel()->getName())->add(\Restrictions::in("id", $ids)));
	}
	
	/**
	 * Transform result
	 *
	 * @param array<array<String, mixed>> $rows
	 * @param f_persistentdocument_criteria_ExecutableQuery $query TODO Old class Usage
	 * @return array<mixed>
	 */
	protected function fetchProjection($rows, $query)
	{
		$names = $query->getDocumentProjections();
		$namesCount = count($names);
		if ($namesCount > 0)
		{
			$result = array();
			$i18nFieldNames = $this->getI18nFieldNames();
			foreach ($rows as $row)
			{
				foreach ($names as $name)
				{
					$i18NInfos = array();
					foreach ($i18nFieldNames as $i18nFieldName)
					{
						$i18NInfos[$i18nFieldName] = $row[$name. '_' . $i18nFieldName];
					}
					$row[$name] = $this->getDocumentInstanceWithModelName(intval($row[$name.'_id']), $row[$name.'_model'], $row[$name.'_treeid'], $i18NInfos);
				}
				$result[] = $row;
			}
			return $result;
		}
		return $rows;
	}
	
	/**
	 * Helper for '$this->find($query)[0]'
	 *
	 * @param f_persistentdocument_criteria_Query $query
	 * @return \Change\Documents\AbstractDocument|null if no document was returned by find($query)
	 */
	public function findUnique($query)
	{
		if ($query->getMaxResults() != 1)
		{
			$query->setMaxResults(2);
		}
	
		$docs = $this->find($query);
		$nbDocs = count($docs);
		if ($nbDocs > 0)
		{
			if ($nbDocs > 1)
			{
				\Change\Application\LoggingManager::getInstance()->warn(get_class($this).'->findUnique() called while find() returned more than 1 results');
			}
			return $docs[0];
		}
		return null;
	}
	
	//
	// Tree Methods à usage du treeService
	//
	
	/**
	* @param integer $documentId
	* @param integer $treeId
	* @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	*/
	public abstract function getNodeInfo($documentId, $treeId);	

	/**
	 * @param integer[] $documentsId
	 * @param integer $treeId
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public abstract function getNodesInfo($documentsId, $treeId);

	/**
	 * @param \f_persistentdocument_PersistentTreeNode $node TODO Old class Usage
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public abstract function getChildrenNodesInfo($node);


	/**
	 * @param \f_persistentdocument_PersistentTreeNode $node TODO Old class Usage
	 * @return array<document_id, tree_id, parent_id, node_order, node_level, node_path, children_count>
	 */
	public abstract function getDescendantsNodesInfo($node, $deep = -1);

	/**
	 * @param \f_persistentdocument_PersistentTreeNode $node TODO Old class Usage
	 * @return integer[]
	 */
	public abstract function getChildrenId($node);


	/**
	 * @param \f_persistentdocument_PersistentTreeNode $node TODO Old class Usage
	 * @return integer[]
	 */
	public abstract function getDescendantsId($node);


	/**
	 * @param \f_persistentdocument_PersistentTreeNode $rootNode TODO Old class Usage
	 */
	public function createTree($rootNode)
	{
		$this->getSchemaManager()->createTreeTable($rootNode->getId());
		$this->insertNode($rootNode);
	}

	/**
	 * Suppression de tout l'arbre
	 * @param \f_persistentdocument_PersistentTreeNode $rootNode TODO Old class Usage
	 * @return integer[]
	 */
	public abstract function clearTree($rootNode);

	/**
	 * Ajoute un nouveau noeud
	 * @param \f_persistentdocument_PersistentTreeNode $node TODO Old class Usage
	 */
	protected abstract function insertNode($node);

	/**
	 * @param \f_persistentdocument_PersistentTreeNode $parentNode TODO Old class Usage
	 * @param \f_persistentdocument_PersistentTreeNode[] $nodes TODO Old class Usage
	 */
	public abstract function orderNodes($parentNode, $nodes);


	/**
	 * Supression d'un noeud
	 * @param \f_persistentdocument_PersistentTreeNode $treeNode TODO Old class Usage
	 */
	public abstract function deleteEmptyNode($treeNode);


	/**
	 * Supression d'une arboresence
	 * @param \f_persistentdocument_PersistentTreeNode $treeNode TODO Old class Usage
	 * @return integer[]
	 */
	public abstract function deleteNodeRecursively($treeNode);

	/**
	 * @param \f_persistentdocument_PersistentTreeNode $parentNode TODO Old class Usage
	 * @param \f_persistentdocument_PersistentTreeNode $childNode TODO Old class Usage
	 */
	public abstract function appendChildNode($parentNode, $childNode);

	/**
	 * @param \f_persistentdocument_PersistentTreeNode $parentNode TODO Old class Usage
	 * @param \f_persistentdocument_PersistentTreeNode $childNode TODO Old class Usage
	 */
	public abstract function insertChildNodeAtOrder($parentNode, $childNode);

	/**
	 * @param \f_persistentdocument_PersistentTreeNode $parentNode TODO Old class Usage
	 * @param \f_persistentdocument_PersistentTreeNode $movedNode TODO Old class Usage
	 * @param \f_persistentdocument_PersistentTreeNode $destNode TODO Old class Usage
	 * @return integer[]
	 */
	public abstract function moveNode($parentNode, $movedNode, $destNode);


	// Relation
	
	/**
	 * @param string $type
	 * @param integer $documentId1
	 * @param integer $documentId2
	 * @param string $documentModel1
	 * @param string $documentModel2
	 * @param string $name
	 * @return \f_persistentdocument_PersistentRelation[] TODO Old class Usage
	 */
	protected abstract function getRelations($type = null, $documentId1 = null, $documentId2 = null, $name = null, $documentModel1 = null, $documentModel2 = null);
	
	
	/**
	 * @param integer $masterDocumentId
	 * @param string $relationName
	 * @param string $slaveDocumentModel
	 * @return \f_persistentdocument_PersistentRelation[] TODO Old class Usage
	 */
	public function getChildRelationByMasterDocumentId($masterDocumentId, $relationName = null, $slaveDocumentModel = null)
	{
		return $this->getRelations("CHILD", $masterDocumentId, null, $relationName, null, $slaveDocumentModel);
	}
	
	/**
	 * @param integer $slaveDocumentId
	 * @param string $relationName
	 * @param string $masterDocumentModel
	 * @return \f_persistentdocument_PersistentRelation[] TODO Old class Usage
	 */
	public function getChildRelationBySlaveDocumentId($slaveDocumentId, $relationName = null, $masterDocumentModel = null)
	{
		return $this->getRelations("CHILD", null, $slaveDocumentId, $relationName, $masterDocumentModel, null);
	}
	
	/**
	 * @param integer $documentId1
	 * @param integer $documentId2
	 * @return boolean
	 */
	public function getChildRelationExist($documentId1, $documentId2)
	{
		if (count($this->getRelations(null, $documentId1, $documentId2, null, null, null)) > 0)
		{
			return true;
		}
		if (count($this->getRelations(null, $documentId2, $documentId1, null, null, null)) > 0)
		{
			return true;
		}
		return false;
	}	

	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @param integer $userId
	 * @return string|NULL
	 */
	public abstract function getUserSettingValue($packageName, $settingName, $userId);
	
	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @return string|NULL
	 */
	public function getSettingValue($packageName, $settingName)
	{
		return $this->getUserSettingValue($packageName, $settingName, 0);
	}

	/**
	 * @param string $value
	 * @param string $settingName
	 * @return string|NULL
	 */
	public abstract function getSettingPackage($value, $settingName);

	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @param integer $userId
	 * @param string|NULL $value
	 */
	public abstract function setUserSettingValue($packageName, $settingName, $userId, $value);
	

	/**
	 * @param string $packageName
	 * @param string $settingName
	 * @param string|NULL $value
	 */
	public function setSettingValue($packageName, $settingName, $value)
	{
		$this->setUserSettingValue($packageName, $settingName, 0, $value);
	}


	// -------------------------------------------------------------------------
	// TAGS STUFF
	// -------------------------------------------------------------------------
	
	/**
	 * Return the tags affected to the document with ID $documentId.
	 * @internal use by TagService
	 * @param integer $documentId Id of the document the get the list of tags of.
	 * @return string[]
	 */
	public abstract function getTags($documentId);
	
	/**
	 * @return array<tag => array<id>>
	*/
	public abstract function getAllTags();
	
	/**
	 * @internal use by TagService
	 * @param string $tag
	 * @return array of documentid
	 */
	public abstract function getDocumentIdsByTag($tag);

	/**
	 * @internal use by TagService
	 *
	 * @param integer $documentId
	 * @param array $tags Array of string tag name (tolower)
	 * @param boolean $allTagsRequired
	 * @return boolean
	 */
	public abstract function hasTags($documentId, $tags, $allTagsRequired);

	/**
	 * @internal use by TagService
	 * @param integer $documentId
	 * @param string $tag
	 * @return boolean
	 */
	public abstract function hasTag($documentId, $tag);

	/**
	 * @internal use by TagService
	 * @param integer $documentId
	 * @param string $tag
	 * @return boolean
	 */
	public abstract function removeTag($documentId, $tag);


	/**
	 * Adds the tag $tag tag to the document with ID $documentId.
	 * @internal use by TagService
	 * @param integer $documentId
	 * @param string $tag
	 */
	public abstract function addTag($documentId, $tag);

	/**
	 * Return a translated text or null
	 * @param string $lcid
	 * @param string $id
	 * @param string $keyPath
	 * @return array[$content, $format]
	 */
	public abstract function translate($lcid, $id, $keyPath);

	/**
	 * Clear the translation table or a part of that
	 * @param string $package Example: m.users
	 */
	public abstract function clearTranslationCache($package = null);
	
	/**
	 * @param string $lcid
	 * @param string $id
	 * @param string $keyPath
	 * @param string $content
	 * @param integer $useredited
	 * @param string $format [TEXT] | HTML
	 * @param boolean $forceUpdate
	 */
	public abstract function addTranslate($lcid, $id, $keyPath, $content, $useredited, $format = 'TEXT', $forceUpdate = false);
		
	/**
	 * @return array
	 */
	public abstract function getPackageNames();

		
	/**
	 * @return array
	 */
	public abstract function getUserEditedPackageNames();
	

	/**
	 * @param string $keyPath
	 * @return array['id' => string, 'lang' => string, 'content' => string, 'useredited' => integer, 'format' => string]
	 */
	public abstract function getPackageData($keyPath);
	
	/**
	 * @param string $keyPath
	 * @param string $id
	 * @param string $lcid
	 */
	public abstract function deleteI18nKey($keyPath, $id = null, $lcid = null);
	
	//I18nSynchro
	
	/**
	 * @param integer $id
	 * @param string $lang
	 * @param string $synchroStatus 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 * @param string|null $fromLang
	 */
	public abstract function setI18nSynchroStatus($id, $lang, $synchroStatus, $fromLang = null);
	
	/**
	 * @param integer $id
	 * @return array
	 * 		- 'fr'|'en'|'??' : array
	 * 			- status : 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 * 			- from : fr'|'en'|'??'|null
	*/
	public abstract function getI18nSynchroStatus($id);
	
	/**
	 * @return integer[]
	*/
	public abstract function getI18nSynchroIds();
	
	/**
	 * @param \f_persistentdocument_PersistentDocumentModel $pm TODO Old class Usage
	 * @param integer $id
	 * @param string $lang
	 * @param string $fromLang
	*/
	public abstract function prepareI18nSynchro($pm, $documentId, $lang, $fromLang);
	
	/**
	 * @param \f_persistentdocument_PersistentDocumentModel $pm TODO Old class Usage
	 * @param \f_persistentdocument_I18nPersistentDocument $to TODO Old class Usage
	*/
	public abstract function setI18nSynchro($pm, $to);
	
	/**
	 * @param integer $id
	 * @param string|null $lang
	*/
	public abstract function deleteI18nSynchroStatus($id, $lang = null);
		
	/**
	 * @param integer $documentId
	 * @return array<<nb_rules, website_id, website_lang>>
	 */
	public abstract function getUrlRewritingDocumentWebsiteInfo($documentId);

	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 * @return array<<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type>>
	 */
	public abstract function getUrlRewritingDocument($documentId, $lang, $websiteId);

	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 */
	public abstract function deleteUrlRewritingDocument($documentId, $lang, $websiteId);
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @return array<<nb_rules, website_id, website_lang>>
	 */
	public abstract function getUrlRewritingActionWebsiteInfo($moduleName, $actionName);

	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @param string $lang
	 * @param integer $websiteId
	 * @return array<<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, from_url, to_url, redirect_type>>
	 */
	public abstract function getUrlRewritingAction($moduleName, $actionName, $lang, $websiteId);

	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 * @param string $lang
	 * @param integer $websiteId
	 */
	public abstract function deleteUrlRewritingAction($moduleName, $actionName, $lang, $websiteId);
	
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 * @return string from_url
	 */
	public abstract function getUrlRewriting($documentId, $lang, $websiteId = 0, $actionName = 'ViewDetail');

	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @return array<array<rule_id, origine, document_id, website_lang, website_id, from_url, to_url, redirect_type, modulename, actionname>>
	 */
	public abstract function getUrlRewritingInfo($documentId, $lang);
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @param integer $websiteId
	 * @param string $fromURL
	 * @param string $toURL
	 * @param integer $redirectType
	 * @param string $moduleName
	 * @param string $actionName
	 * @param integer $origine
	 */
	public abstract function setUrlRewriting($documentId, $lang, $websiteId, $fromURL, $toURL, $redirectType, $moduleName, $actionName, $origine = 0);
	
	/**
	 * @param integer $documentId
	 * @return integer count deleted rules
	 */
	public abstract function clearUrlRewriting($documentId);
	
	
	/**
	 * @param string $url
	 * @param integer $websiteId
	 * @param string $lang
	 * @return array<rule_id, origine, modulename, actionname, document_id, website_lang, website_id, to_url, redirect_type>
	 */
	public abstract function getUrlRewritingInfoByUrl($url, $websiteId, $lang);

	/**
	 * @param string $url
	 * @param integer $websiteId
	 * @return array<rule_id, document_id, website_lang, website_id, to_url, redirect_type>
	 */
	public abstract function getPageForUrl($url, $websiteId = 0);

	//
	// Permission Section
	//

	/**
	 * Compile a user/groupAcl in f_permission_compiled.
	 *
	 * @param \users_persistentdocument_userAcl | \users_persistentdocument_groupAcl $acl TODO Old class Usage
	 */
	public abstract function compileACL($acl);

	/**
	 * Remove all compiled acls for node $nodeId
	 *
	 * @param integer $nodeId
	 * @param string $packageName (ex: modules_website)
	 */
	public abstract function removeACLForNode($nodeId, $packageName = null);


	/**
	 * Permissions defined on $nodeId predicate
	 *
	 * @param integer $nodeId
	 * @return boolean
	 */
	public abstract function hasCompiledPermissions($nodeId);

	/**
	 * Permissions defined on $nodeId for $package predicate
	 *
	 * @param integer $nodeId
	 * @param string $packageName
	 * @return boolean
	 */
	public abstract function hasCompiledPermissionsForPackage($nodeId, $packageName);

	/**
	 * Checks the existence of a permission on a node for an array of accessors.
	 *
	 * @param array<Integer> $accessors
	 * @param string $fullPermName
	 * @param integer $nodeId
	 * @return boolean
	 */
	public abstract function checkCompiledPermission($accessors, $perm, $node);


	/**
	 * @param string $permission
	 * @param integer $nodeId
	 * @return array<Integer>
	 */
	public abstract function getAccessorsByPermissionForNode($permission, $nodeId);


	/**
	 * @param array<Integer> $accessorIds
	 * @param integer $nodeId
	 * @return array<String>
	 */
	public abstract function getPermissionsForUserByNode($accessorIds, $nodeId);


	public abstract function clearAllPermissions();
	
	/**
	 * Get the permission "Definition" points for tree $packageName (ex: modules_website).
	 *
	 * @param string $packageName
	 * @return Array<Integer>
	 */
	public abstract function getPermissionDefinitionPoints($packageName);

	/**
	 * @param string $url
	 * @return \f_persistentdocument_I18PersistentDocument[]|null TODO Old class Usage
	 */
	public abstract function getI18nWebsitesFromUrl($url);


	/**
	 * @param string $blockName
	 * @param array<String> $specs
	 */
	public abstract function registerSimpleCache($cacheId, $specs);

	/**
	 * @param string $pattern
	 */
	public abstract function getCacheIdsByPattern($pattern);

	/**
	 * @param string $date_entry
	 * @param integer $userId
	 * @param string $moduleName
	 * @param string $actionName
	 * @param integer $documentId
	 * @param string $username
	 * @param string $serializedInfo
	 * @return integer
	 */
	public abstract function addUserActionEntry($date_entry, $userId, $moduleName, $actionName, $documentId, $username, $serializedInfo);

	/**
	 * @param integer $userId
	 * @param string $moduleName
	 * @param string $actionName
	 * @param integer $documentId
	 * @return integer
	 */
	public abstract function getCountUserActionEntry($userId, $moduleName, $actionName, $documentId);

	/**
	 * @param integer $userId
	 * @param string $moduleName
	 * @param string $actionName
	 * @param integer $documentId
	 * @param integer $rowIndex
	 * @param integer $rowCount
	 * @param string $sortOnField (date | user)
	 * @param string $sortDirection (ASC | DESC)
	 * @return array(array(entry_id, entry_date, user_id, document_id, module_name, action_name, info, link_id));
	 */
	public abstract function getUserActionEntry($userId, $moduleName, $actionName, $documentId, $rowIndex, $rowCount, $sortOnField, $sortDirection);

	/**
	 * @param string $fieldName (document | module | action | [user])
	 * @return array<array<distinctvalue => VALUE>>
	 */
	public abstract function getDistinctLogEntry($fieldName);


	/**
	 * @param string $date
	 * @param string|null $moduleName
	 */
	public abstract function deleteUserActionEntries($date, $moduleName = null);
	
	
	// Indexing function
	
	/** 
	 * @param integer $documentId
	 * @param array<status, lastupdate>
	 */
	public abstract function getIndexingDocumentStatus($documentId);

	/**
	 * @param integer $documentId
	 * @param string $newStatus
	 * @param string $lastUpdate
	 */
	public abstract function setIndexingDocumentStatus($documentId, $newStatus, $lastUpdate = null);

	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	public abstract function deleteIndexingDocumentStatus($documentId);

	
	/**
	 * @return integer
	 */
	public abstract function clearIndexingDocumentStatus();
	
	/**
	 * @return array<indexing_status =>, nb_document =>, max_id>
	 */
	public abstract function getIndexingStats();

	/**
	 * @return array<max_id => integer >
	 */
	public abstract function getIndexingPendingEntries();

	/** 
	 * @param integer $maxDocumentId
	 * @param integer $chunkSize
	 * @param integer[]
	 */
	public abstract function getIndexingDocuments($maxDocumentId, $chunkSize = 100);

}
<?php
namespace Change\Db;

/**
 * @name \Change\Db\DbProvider
 * @method \Change\Db\DbProvider getInstance()
 */
abstract class DbProvider
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
	 * @var \Change\Logging\Logging
	 */
	protected $logging;
	
	/**
	 * @return \Change\Db\DbProvider
	 */
	public static function getInstance()
	{
		return \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
	}
	
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
	
	/**
	 * @param \Change\Configuration\Configuration $config
	 * @param \Change\Logging\Logging $logging
	 * @throws \RuntimeException
	 * @return \Change\Db\DbProvider
	 */
	public static function newInstance(\Change\Configuration\Configuration $config, \Change\Logging\Logging $logging)
	{
		$connectionInfos = $config->getEntry('databases/default', array());
		if (!isset($connectionInfos['dbprovider']))
		{
			throw new \RuntimeException('Missing or incomplete database configuration');
		}
		$className = $connectionInfos['dbprovider'];
		return new $className($connectionInfos, $logging);
	}
	
	/**
	 * @param array $connectionInfos
	 * @param \Change\Logging\Logging $logging
	 */
	public function __construct(array $connectionInfos, \Change\Logging\Logging $logging)
	{
		$this->connectionInfos = $connectionInfos;
		$this->logging = $logging;
		$this->timers = array('init' => microtime(true), 'longTransaction' => isset($connectionInfos['longTransaction']) ? floatval($connectionInfos['longTransaction']) : 0.2);
	}	
	
	public function __destruct()
	{
		if ($this->hasTransaction())
		{
			$this->logging->warn(__METHOD__ . ' called while active transaction (' . $this->transactionCount . ')');
		}
	}
	
	/**
	 * @throws \Exception
	 */
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

				$this->logging->warn(get_class($this) . " while already in transaction");
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
				$this->logging->warn('embeded transaction: ' . $this->transactionCount);
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
				$this->logging->warn("PersistentProvider->commit() called while not in transaction");
			}
			else
			{
				$this->commitInternal();
				$duration = round(microtime(true) - $this->timers['bt'], 4);
				if ($duration > $this->timers['longTransaction'])
				{
					$this->logging->warn('Long Transaction detected '.  number_format($duration, 3) . 's > ' . $this->timers['longTransaction']);
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
	 * Cancel transaction.
	 * @param \Exception $e
	 * @throws \BaseException('rollback-bad-transaction-count') if rollback called while no transaction
	 * @throws \Change\Db\Exception\TransactionCancelledException on embeded transaction
	 * @return Exception the given exception so it is easy to throw it
	 */
	public function rollBack($e = null)
	{
		$this->logging->warn('Provider->rollBack called');
		if ($this->transactionCount == 0)
		{
			$this->logging->warn('Provider->rollBack() => bad transaction count (no transaction)');
			throw new \Exception('rollback-bad-transaction-count');
		}
		$this->transactionCount--;
		
		if (!$this->transactionDirty)
		{
			$this->transactionDirty = true;
			if (!$this->m_inTransaction)
			{
				$this->logging->warn("Provider->rollBack() called while not in transaction");
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
	 * @return array
	 */
	public function getConnectionInfos()
	{
		return $this->connectionInfos;
	}
	
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
	 * @param string $propertyName
	 * @return integer
	 */
	public abstract function getRelationId($propertyName);
	
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
	
	/**
	 * Clear all permissions.
	 */
	public abstract function clearAllPermissions();
	
	/**
	 * Get the permission "Definition" points for tree $packageName (ex: modules_website).
	 *
	 * @param string $packageName
	 * @return Array<Integer>
	 */
	public abstract function getPermissionDefinitionPoints($packageName);

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
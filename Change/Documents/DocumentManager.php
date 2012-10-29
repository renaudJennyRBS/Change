<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\DocumentManager
 */
class DocumentManager
{
	
	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;
	
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;	
	
	/**
	 * Document instances by id
	 * @var array<integer, \Change\Documents\AbstractDocument>
	 */
	protected $documentInstances = array();
	
	/**
	 * @var array<integer, \Change\Documents\AbstractI18nDocument>
	*/
	protected $i18nDocumentInstances = array();
	
	/**
	 * @var array
	*/
	protected $tmpRelationIds = array();
	
	/**
	 * Temporay identifier for new persistent document
	 * @var integer
	 */
	protected $newInstancesCounter = 0;
	
	
	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Application\ApplicationServices $applicationServices
	 */
	public function __construct(\Change\Documents\DocumentServices $documentServices, \Change\Application\ApplicationServices $applicationServices)
	{
		$this->documentServices = $documentServices;
		$this->applicationServices = $applicationServices;
	}
	
	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->applicationServices->getDbProvider();
	}
	
	/**
	 * @return \Change\Documents\ModelManager
	 */
	protected function getModelManager()
	{
		return $this->documentServices->getModelManager();
	}
	
	/**
	 * @param string $modelName
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getNewDocumentInstance($modelName)
	{
		$model = $this->getModelManager()->getModelByName($modelName);
		if ($model === null)
		{
			throw new \InvalidArgumentException('Invalid model name (' . $modelName . ')');
		}
		$this->newInstancesCounter--;
		$className = $this->getDocumentClassFromModel($model);

		$i18nInfo = new I18nInfo($this->applicationServices->getI18nManager()->getLang());
		/* @var $newDocument \Change\Documents\AbstractDocument */
		$newDocument = new $className($this->newInstancesCounter, $i18nInfo, null);
		$newDocument->initialize($this->documentServices, $model);
		return $newDocument;
	}
	
	/**
	 * @param integer $documentId
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDocumentInstance($documentId, $model = null)
	{
		$id = intval($documentId);
		if ($id > 0)
		{
			if ($this->isInCache($id))
			{
				return $this->getFromCache($id);
			}
			
			$constructorInfos = $this->getDbProvider()->getDocumentConstructorInfos($id, $model);
			if ($constructorInfos !== null)
			{
				list ($realModelName, $lang, $label, $treeId) = $constructorInfos;
				$realModel = $this->getModelManager()->getModelByName($realModelName);				
				$className = $this->getDocumentClassFromModel($realModel);
				/* @var $document \Change\Documents\AbstractDocument */
				$i18nInfo = new I18nInfo($lang, $label);
				$document = new $className($id, $i18nInfo, $treeId);
				$document->initialize($this->documentServices, $realModel);
				
				$this->putInCache($id, $document);
			}
			
			return $document;
		}
		return null;
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function loadDocumentInstance($document)
	{
		$documentId = $document->getId();
		$this->getDbProvider()->loadDocument($document);
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function saveDocumentInstance($document)
	{
		$documentId = $document->getId();
		if ($documentId < 0)
		{
			$this->getDbProvider()->insertDocument($document);
		}
		else
		{
			$this->getDbProvider()->updateDocument($document);
		}
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $lang
	 * @return \Change\Documents\AbstractI18nDocument
	 */
	public function getI18nDocumentInstance($document, $lang)
	{
		$documentId = $document->getId();
		$i18ndoc = $this->getI18nDocumentFromCache($documentId, $lang);
		if ($i18ndoc === null)
		{
			$i18ndoc = $this->getDbProvider()->getI18nDocument($document, $lang);
			if ($i18ndoc === null)
			{
				$i18nClassName = $this->getI18nDocumentClassFromModel($document->getDocumentModelName());
				$i18nDoc = new $i18nClassName($documentId, $lang, true);
			}
			$this->i18nDocumentInstances[$documentId][$lang] = $i18nDoc;
		}
		return $i18nDoc;
	}
	
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return integer
	 */
	public function initializeRelationDocumentId($document)
	{
		$id = $document->getId();
		if ($id < 0)
		{
			$this->putInCache($id, $document);
			$this->tmpRelationIds[$id] = $id;
		}
		return $id;
	}
	
	/**
	 * @param integer $documentId
	 * @return integer
	 * @throws Exception
	 */
	public function resolveRelationDocumentId($documentId)
	{
		if ($documentId < 0)
		{
			$documentId = isset($this->tmpRelationIds[$documentId]) ? $this->tmpRelationIds[$documentId] : $documentId;
			if (!$this->isInCache($documentId))
			{
				throw new \Exception('document ' . $documentId . ' not found');
			}
		}
		return intval($documentId);
	}
	
	/**
	 * @param integer $documentId
	 * @return \Change\Documents\AbstractDocument
	 * @throws Exception
	 */
	public function getRelationDocument($documentId)
	{
		if ($documentId < 0)
		{
			$id = intval(isset($this->tmpRelationIds[$documentId]) ? $this->tmpRelationIds[$documentId] : $documentId);
			if ($this->isInCache($id))
			{
				return $this->getFromCache($id);
			}
			throw new \Exception('document ' . $documentId . '/'. $id . ' not found');
		}
		return $this->getDocumentInstance($documentId);
	}
	
	/**
	 * @param integer $tmpId
	 * @param integer $documentId
	 */
	protected function updateTemporaryRelationId($tmpId, $documentId)
	{
		if (isset($this->tmpRelationIds[$tmpId]))
		{
			$this->tmpRelationIds[$tmpId] = $documentId;
		}
	}	
	
	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	protected function isInCache($documentId)
	{
		return isset($this->documentInstances[intval($documentId)]);
	}
	
	/**
	 * @param integer $documentId
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function getFromCache($documentId)
	{
		return $this->documentInstances[intval($documentId)];
	}
	
	/**
	 * @param integer $documentId
	 * @param string $lang
	 * @return \Change\Documents\AbstractI18nDocument|NULL
	 */
	protected function getI18nDocumentFromCache($documentId, $lang)
	{
		if (isset($this->i18nDocumentInstances[$documentId]))
		{
			if (isset($this->i18nDocumentInstances[$documentId][$lang]))
			{
				return $this->i18nDocumentInstances[$documentId][$lang];
			}
		}
		else
		{
			$this->i18nDocumentInstances[$documentId] = array();
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
		$this->documentInstances[$documentId] = $document;
	}
		
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return string
	 */
	protected function getDocumentClassFromModel($model)
	{
		return ucfirst($model->getVendorName()) . '\\' . ucfirst($model->getModuleName()) . '\\Documents\\' . ucfirst(ucfirst($model->getDocumentName()));
	}
		
	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @return string
	 */
	protected function getI18nDocumentClassFromModel($model)
	{
		return $this->getDocumentClassFromModel($model).'I18n';
	}
}
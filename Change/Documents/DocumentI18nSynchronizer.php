<?php
namespace Change\Documents;

/** 
 * @name \Change\Documents\DocumentI18nSynchronizer
 */
class DocumentI18nSynchronizer
{
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;
	
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;
	
	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Documents\DocumentManager $documentManager
	 */
	public function __construct(\Change\Application\ApplicationServices $applicationServices, \Change\Documents\DocumentManager $documentManager)
	{
		$this->applicationServices = $applicationServices;
		$this->documentManager = $documentManager;
	}
	
	/**
	 * @param integer $documentId
	 */
	public function resetSynchroForDocumentId($documentId)
	{
		if ($this->hasI18nDocumentsSynchro())
		{
			$d = $this->documentManager->getDocumentInstance($documentId);
			if ($d && $d->getPersistentModel()->isLocalized())
			{
				$this->applicationServices->getDbProvider()->setI18nSynchroStatus($d->getId(), $d->getLang(), self::SYNCHRO_MODIFIED, null);
			}
		}
	}
	
	/**
	 * @param integer $documentId
	 */
	public function initSynchroForDocumentId($documentId)
	{
		if ($this->hasI18nDocumentsSynchro())
		{
			$d = $this->documentManager->getDocumentInstance($documentId);
			if ($d && $d->getPersistentModel()->isLocalized())
			{
				foreach ($d->getI18nInfo()->getLangs() as $lang)
				{
					$this->applicationServices->getDbProvider()->setI18nSynchroStatus($d->getId(), $lang, self::SYNCHRO_MODIFIED, null);
				}
			}
		}
	}
	
	/**
	 * @return integer[]
	 */
	public function getDocumentIdsToSynchronize()
	{
		if ($this->hasI18nDocumentsSynchro())
		{
			return $this->applicationServices->getDbProvider()->getI18nSynchroIds();
		}
		return array();
	}
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array
	 *		- isLocalized : boolean
	 *		- action : 'none'|'generate'|'synchronize'
	 *		- config : array
	 *			- 'fr'|'??' : string[]
	 *			- ...
	 *		- states : array
	 *			- 'fr'|'??' : array
	 *				- status : 'MODIFIED'|'VALID'|'SYNCHRONIZED'
	 *				- from : fr'|'en'|'??'|null
	 *			- ...
	 */
	public function getI18nSynchroForDocument($document)
	{
		$result = array('isLocalized' => false, 'action' => 'none', 'config' => array());
		$pm = $document->getPersistentModel();
		if ($pm->isLocalized())
		{
			$result['isLocalized'] = true;
			if ($this->hasI18nDocumentsSynchro())
			{
				$result['config'] = $this->getI18nDocumentsSynchro();
				$data = $this->applicationServices->getDbProvider()->getI18nSynchroStatus($document->getId());
				$result['states'] = $data;
				foreach ($document->getI18nInfo()->getLangs() as $lang)
				{
					if (!isset($data[$lang]))
					{
						$result['action'] = 'generate';
						break;
					}
					elseif ($data[$lang]['status'] === self::SYNCHRO_MODIFIED)
					{
						$result['action'] = 'synchronize';
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * @param integer $documentId
	 * @return boolean
	 */
	public function synchronizeDocumentId($documentId)
	{
		$dbProvider = $this->applicationServices->getDbProvider();
		
		if (!$this->hasI18nDocumentsSynchro())
		{
			// No synchro configured.
			return false;
		}
		$d = $this->documentManager->getDocumentInstance($documentId);
		if ($d === null)
		{
			// Invalid document.
			return false;
		}
	
		$pm = $d->getPersistentModel();
		if (!$pm->isLocalized())
		{
			// Not applicable on this document.
			return false;
		}
	
		try
		{
			$dbProvider->beginTransaction();
			$ds = $d->getDocumentService();
	
			$synchroConfig = $ds->getI18nSynchroConfig($d, $this->getI18nDocumentsSynchro());
			if (count($synchroConfig))
			{
				//TODO Old class Usage
				$dcs = \f_DataCacheService::getInstance();
				$datas = $dbProvider->getI18nSynchroStatus($d->getId());
				if (count($datas) === 0)
				{
					foreach ($d->getI18nInfo()->getLangs() as $lang)
					{
						$datas[$lang] = array('status' => self::SYNCHRO_MODIFIED, 'from' => null);
					}
				}
				else
				{
					$datas[$d->getLang()] = array('status' => self::SYNCHRO_MODIFIED, 'from' => null);
				}
	
				foreach ($synchroConfig as $lang => $fromLangs)
				{
					if (!isset($datas[$lang]) || $datas[$lang]['status'] === self::SYNCHRO_SYNCHRONIZED)
					{
						foreach ($fromLangs as $fromLang)
						{
							if (isset($datas[$fromLang]) && $datas[$fromLang]['status'] !== self::SYNCHRO_SYNCHRONIZED)
							{
								list($from, $to) = $dbProvider->prepareI18nSynchro($pm, $documentId, $lang, $fromLang);
								try
								{
									$this->documentManager->pushLang($fromLang);
									if ($ds->synchronizeI18nProperties($d, $from, $to))
									{
										$dbProvider->setI18nSynchro($pm, $to);
										$dbProvider->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_SYNCHRONIZED, $fromLang);
										//TODO Old class Usage
										$dcs->clearCacheByPattern(\f_DataCachePatternHelper::getModelPattern($d->getDocumentModelName()));
										$dcs->clearCacheByDocId(\f_DataCachePatternHelper::getIdPattern($documentId));
									}
									elseif (isset($datas[$lang]))
									{
										$dbProvider->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
									}
									$this->documentManager->popLang();
								}
								catch (\Exception $e)
								{
									$this->documentManager->popLang($e);
								}
								break;
							}
						}
					}
				}
	
				foreach ($datas as $lang => $synchroInfos)
				{
					if ($synchroInfos['status'] === self::SYNCHRO_MODIFIED)
					{
						$dbProvider->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
					}
					elseif ($synchroInfos['status'] === self::SYNCHRO_SYNCHRONIZED && !isset($synchroConfig[$lang]))
					{
						$dbProvider->setI18nSynchroStatus($documentId, $lang, self::SYNCHRO_VALID, null);
					}
				}
			}
			else
			{
				$dbProvider->deleteI18nSynchroStatus($documentId);
			}
			$dbProvider->commit();
		}
		catch (\Exception $e)
		{
			$dbProvider->rollback($e);
			return false;
		}
		return true;
	}
}
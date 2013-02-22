<?php
namespace Change\Documents;

use Change\Documents\DocumentManager;

/**
 * @api
 * @name \Change\Documents\LocalizableFunctions
 */
class LocalizableFunctions
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var integer
	 */
	protected $documentId;

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function __construct(\Change\Documents\AbstractDocument $document)
	{
		$this->documentManager = $document->getDocumentManager();
		$this->documentId = $document->getId();
	}

	public function __destruct()
	{
		unset($this->documentManager);
	}

	/**
	 * @api
	 * @return \Change\Documents\DocumentManager
	 */
	public function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @return \Change\Documents\AbstractDocument
	 */
	protected function getDocument()
	{
		$document = $this->documentManager->getDocumentInstance($this->documentId);
		$this->documentId = $document->getId();
		return $document;
	}
	
	/**
	 * @var \Change\Documents\AbstractI18nDocument[]
	 */
	protected $i18nPartArray = array();

	/**
	 * @var string[]
	 */
	protected $LCIDArray;


	/**
	 * @api
	 * @return integer
	 */
	public function getId()
	{
		return $this->getDocument()->getId();
	}

	/**
	 * @api
	 * @return string
	 */
	public function getRefLCID()
	{
		return $this->getDocument()->getRefLCID();
	}

	/**
	 * @api
	 * @return string
	 */
	public function getLCID()
	{
		return $this->getDocumentManager()->getLCID();
	}

	/**
	 * @api
	 * @return string[]
	 */
	public function getLCIDArray()
	{
		if ($this->LCIDArray === null)
		{
			$this->LCIDArray = $this->getDocumentManager()->getI18nDocumentLCIDArray($this->getDocument());
		}
		foreach ($this->i18nPartArray as $LCID => $i18nPart)
		{
			if (!in_array($LCID, $this->LCIDArray) && $i18nPart->getPersistentState() === DocumentManager::STATE_LOADED)
			{
				$this->LCIDArray[] = $LCID;
			}
		}
		return $this->LCIDArray;
	}

	/**
	 * @api
	 * @param string $LCID
	 * @return \Change\Documents\AbstractI18nDocument|null
	 */
	public function getI18nPart($LCID)
	{
		if (isset($this->i18nPartArray[$LCID]))
		{
			return $this->i18nPartArray[$LCID];
		}
		$LCIDArray = $this->getLCIDArray();
		if (in_array($LCID, $LCIDArray))
		{
			$this->i18nPartArray[$LCID] = $this->getDocumentManager()->getI18nDocumentInstanceByDocument($this->getDocument(), $LCID);
			return $this->i18nPartArray[$LCID];
		}
		return null;
	}

	/**
	 * @api
	 * @throws \RuntimeException if current LCID = refLCID
	 */
	public function delete()
	{
		$i18nPart = $this->getCurrent();
		if ($i18nPart->getLCID() == $this->getRefLCID())
		{
			throw new \RuntimeException('Unable to delete refLCID: ' .  $this->getRefLCID(), 51014);
		}

		if ($i18nPart->getPersistentState() === DocumentManager::STATE_LOADED)
		{
			$this->getDocumentManager()->deleteI18nDocument($this->getDocument(), $i18nPart);
		}
	}

	/**
	 * @api
	 * @return \Change\Documents\AbstractI18nDocument
	 */
	public function getCurrent()
	{
	 	$LCID = $this->getDocumentManager()->getLCID();
	 	if (!isset($this->i18nPartArray[$LCID]))
	 	{
	 		$this->i18nPartArray[$LCID] = $this->getDocumentManager()->getI18nDocumentInstanceByDocument($this->getDocument(), $LCID);
	 	}
	 	return $this->i18nPartArray[$LCID];
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function isDeleted()
	{
		return $this->getCurrent()->isDeleted();
	}			

	/**
	 * @api
	 * @return boolean
	 */
	public function isNew()
	{
		return $this->getCurrent()->isNew();
	}

	/**
	 * @api
	 * @return \Change\Documents\Correction
	 */
	public function getCorrection()
	{
		return $this->getDocument()->getCorrection($this->getLCID());
	}

	/**
	 * For Internal dialog with DocumentManager
	 * @param \Change\Documents\AbstractI18nDocument|null $i18nPart
	 */
	public function unsetI18nPart(\Change\Documents\AbstractI18nDocument $i18nPart = null)
	{
		if ($i18nPart === null)
		{
			foreach ($this->i18nPartArray as $LCID => $i18nPart)
			{
				$i18nPart->setPersistentState(DocumentManager::STATE_DELETED);
			}
			$this->LCIDArray = array();
		}
		else
		{
			$LCID = $i18nPart->getLCID();
			if ($this->i18nPartArray[$LCID] === $i18nPart)
			{
				$i18nPart->setPersistentState(DocumentManager::STATE_DELETED);
				if ($this->LCIDArray !== null)
				{
					$this->LCIDArray = array_values(array_diff($this->LCIDArray, array($LCID)));
				}
			}
		}
	}

	/**
	 * For Internal dialog with Document
	 */
	public function reset()
	{
		$LCID = $this->getLCID();
		unset($this->i18nPartArray[$LCID]);
	}
}
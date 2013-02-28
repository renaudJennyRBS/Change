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
	 * @return \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Localizable
	 */
	protected function getDocument()
	{
		$document = $this->documentManager->getDocumentInstance($this->documentId);
		$this->documentId = $document->getId();
		return $document;
	}
	
	/**
	 * @var \Change\Documents\AbstractLocalizedDocument[]
	 */
	protected $localizedPartArray = array();

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
			$this->LCIDArray = $this->getDocumentManager()->getLocalizedDocumentLCIDArray($this->getDocument());
		}
		foreach ($this->localizedPartArray as $LCID => $localizedPart)
		{
			if (!in_array($LCID, $this->LCIDArray) && $localizedPart->getPersistentState() === DocumentManager::STATE_LOADED)
			{
				$this->LCIDArray[] = $LCID;
			}
		}
		return $this->LCIDArray;
	}

	/**
	 * @api
	 * @param string $LCID
	 * @return \Change\Documents\AbstractLocalizedDocument|null
	 */
	public function getLocalizedPart($LCID)
	{
		if (isset($this->localizedPartArray[$LCID]))
		{
			return $this->localizedPartArray[$LCID];
		}
		$LCIDArray = $this->getLCIDArray();
		if (in_array($LCID, $LCIDArray))
		{
			$this->localizedPartArray[$LCID] = $this->getDocumentManager()->getLocalizedDocumentInstanceByDocument($this->getDocument(), $LCID);
			return $this->localizedPartArray[$LCID];
		}
		return null;
	}

	/**
	 * @api
	 * @throws \RuntimeException if current LCID = refLCID
	 */
	public function delete()
	{
		$localizedPart = $this->getCurrent();
		if ($localizedPart->getLCID() == $this->getRefLCID())
		{
			throw new \RuntimeException('Unable to delete refLCID: ' .  $this->getRefLCID(), 51014);
		}

		if ($localizedPart->getPersistentState() === DocumentManager::STATE_LOADED)
		{
			$this->getDocumentManager()->deleteLocalizedDocument($this->getDocument(), $localizedPart);
		}
	}

	/**
	 * @api
	 * @return \Change\Documents\AbstractLocalizedDocument
	 */
	public function getCurrent()
	{
	 	$LCID = $this->getDocumentManager()->getLCID();
	 	if (!isset($this->localizedPartArray[$LCID]))
	 	{
	 		$this->localizedPartArray[$LCID] = $this->getDocumentManager()->getLocalizedDocumentInstanceByDocument($this->getDocument(), $LCID);
	 	}
	 	return $this->localizedPartArray[$LCID];
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
	 * For Internal dialog with DocumentManager
	 * @param \Change\Documents\AbstractLocalizedDocument|null $localizedPart
	 */
	public function unsetLocalizedPart(\Change\Documents\AbstractLocalizedDocument $localizedPart = null)
	{
		if ($localizedPart === null)
		{
			foreach ($this->localizedPartArray as $LCID => $localizedPart)
			{
				$localizedPart->setPersistentState(DocumentManager::STATE_DELETED);
			}
			$this->LCIDArray = array();
		}
		else
		{
			$LCID = $localizedPart->getLCID();
			if ($this->localizedPartArray[$LCID] === $localizedPart)
			{
				$localizedPart->setPersistentState(DocumentManager::STATE_DELETED);
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
		unset($this->localizedPartArray[$LCID]);
	}
}
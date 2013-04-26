<?php
namespace Change\Documents;

use Change\Documents\Interfaces\Publishable;

/**
 * @api
 * @name \Change\Documents\PublishableFunctions
 */
class PublishableFunctions
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
		$this->documentManager = $document->getDocumentServices()->getDocumentManager();
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
	 * @return \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable
	 */
	protected function getDocument()
	{
		$document = $this->documentManager->getDocumentInstance($this->documentId);
		$this->documentId = $document->getId();
		return $document;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function published()
	{
		$document = $this->getDocument();
		if (Publishable::STATUS_PUBLISHABLE === $document->getPublicationStatus())
		{
			$st = $document->getStartPublication();
			$ep = $document->getEndPublication();
			$now = new \DateTime();
			return (null === $st || $st <= $now) && (null === $ep || $now < $ep);
		}
		return false;
	}


	/**
	 * @api
	 * @return boolean
	 */
	public function canStartValidation()
	{
		$document = $this->getDocument();
		return ($document->getPublicationStatus() == Publishable::STATUS_DRAFT);
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function startValidation()
	{
		if (!$this->canStartValidation())
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}

		$document = $this->getDocument();
		$document->setPublicationStatus(Publishable::STATUS_VALIDATION);
		$document->update();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function canStartPublication()
	{
		$document = $this->getDocument();
		return ($document->getPublicationStatus() == Publishable::STATUS_VALIDATION);
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function startPublication()
	{
		if (!$this->canStartPublication())
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}
		$document = $this->getDocument();
		$document->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$document->update();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function canDeactivate()
	{
		$document = $this->getDocument();
		return ($document->getPublicationStatus() == Publishable::STATUS_PUBLISHABLE);
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function deactivate()
	{
		if (!$this->canDeactivate())
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}

		$document = $this->getDocument();
		$document->setPublicationStatus(Publishable::STATUS_DEACTIVATED);
		$document->update();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function canActivate()
	{
		$document = $this->getDocument();
		return ($document->getPublicationStatus() == Publishable::STATUS_DEACTIVATED);
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function activate()
	{
		if (!$this->canActivate())
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}

		$document = $this->getDocument();
		$document->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$document->update();
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $preferredWebsite
	 * @return \Change\Presentation\Interfaces\Section|null
	 */
	public function getDefaultSection(\Change\Presentation\Interfaces\Website $preferredWebsite = null)
	{
		return $this->getDocument()->getDefaultSection($preferredWebsite);
	}
}
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
	 * @return string new Publication Status
	 * @throws \RuntimeException
	 */
	public function startValidation()
	{
		$document = $this->getDocument();
		if ($document->getPublicationStatus() != Publishable::STATUS_DRAFT)
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}

		$document->setPublicationStatus(Publishable::STATUS_VALIDATION);
		$document->update();

		return $document->getPublicationStatus();
	}

	/**
	 * @api
	 * @return string new Publication Status
	 * @throws \RuntimeException
	 */
	public function startPublication()
	{
		$document = $this->getDocument();
		if ($document->getPublicationStatus() != Publishable::STATUS_VALIDATION)
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}
		$document->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$document->update();
		return $document->getPublicationStatus();
	}

	public function deactivate()
	{
		$document = $this->getDocument();
		$document->setPublicationStatus(Publishable::STATUS_DEACTIVATED);
		$document->update();
	}

	public function activate()
	{
		$document = $this->getDocument();
		$document->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$document->update();
	}
}
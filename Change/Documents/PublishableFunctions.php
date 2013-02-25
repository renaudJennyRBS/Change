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

	public function startValidation()
	{
		$document = $this->getDocument();
		$document->setPublicationStatus(Publishable::STATUS_VALIDATION);
		$document->update();
	}

	public function startPublication()
	{
		$document = $this->getDocument();
		$document->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$document->update();
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
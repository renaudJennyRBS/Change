<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\DocumentWeakReference
 */
class DocumentWeakReference implements \Serializable
{
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	private $documentModelName;
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function __construct(\Change\Documents\AbstractDocument $document)
	{
		$this->id = $document->getId();
		$this->documentModelName = $document->getDocumentModelName();
	}
	
	/**
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDocument(\Change\Documents\DocumentManager $documentManager)
	{
		return $documentManager->getDocumentInstance($this->id, $this->documentModelName);
	}
	
	/**
	 * @return string
	 */
	public function serialize()
	{
		return $this->id . ' '. $this->documentModelName;
	}

	/**
	 * @param string $serialized
	 */
	public function unserialize($serialized)
	{
		list($this->id, $this->documentModelName) = explode(' ', $serialized);
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return 'WeakReference: ' . $this->id. ', '. $this->documentModelName;
	}
}

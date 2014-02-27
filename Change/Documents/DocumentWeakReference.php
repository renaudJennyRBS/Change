<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
	private $modelName;
	
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function __construct(AbstractDocument $document)
	{
		$this->id = $document->getId();
		$this->modelName = $document->getDocumentModelName();
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDocument(DocumentManager $documentManager)
	{
		if ($this->modelName)
		{
			return $documentManager->getDocumentInstance($this->id, $this->modelName);
		}
		return $documentManager->getDocumentInstance($this->id);
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
	public function getModelName()
	{
		return $this->modelName;
	}

	/**
	 * @return string
	 */
	public function serialize()
	{
		return $this->id . ' '. $this->modelName;
	}

	/**
	 * @param string $serialized
	 * @return mixed|void
	 */
	public function unserialize($serialized)
	{
		list($this->id, $this->modelName) = explode(' ', $serialized);
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return 'WeakReference: ' . $this->id. ', '. $this->modelName;
	}
}

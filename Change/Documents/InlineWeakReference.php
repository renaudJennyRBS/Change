<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents;

/**
 * @name \Change\Documents\InlineWeakReference
 */
class InlineWeakReference
{
	/**
	 * @var array
	 */
	private $dbData;

	/**
	 * @param \Change\Documents\AbstractInline $document
	 */
	public function __construct(AbstractInline $document)
	{
		$this->dbData = $document->dbData();
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDocument(DocumentManager $documentManager)
	{
		if (is_array($this->dbData) && isset($this->dbData['model']))
		{
			$doc = $documentManager->getNewInlineInstanceByModelName($this->dbData['model']);
			$doc->dbData($this->dbData);
			return $doc;
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return 'WeakReference: ' . is_array($this->dbData) ? $this->dbData['model'] : 'invalid';
	}
}

<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Constraints;

use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;

/**
 * @name \Change\Documents\Constraints\PublicationStatus
 */
class PublicationStatus extends \Zend\Validator\AbstractValidator
{
	const PUBLICATION_STATUS_INVALID = 'publicationStatusInvalid';
	const NEW_PUBLICATION_STATUS_INVALID = 'newPublicationStatusInvalid';

	/**
	 * @var \Change\Documents\AbstractDocument
	 */
	protected $document;

	public function __construct($params = array())
	{
		$this->messageTemplates = array(self::PUBLICATION_STATUS_INVALID => self::PUBLICATION_STATUS_INVALID,
			self::NEW_PUBLICATION_STATUS_INVALID => self::NEW_PUBLICATION_STATUS_INVALID);
		parent::__construct($params);
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 */
	public function setDocument($document)
	{
		$this->document = $document;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocument()
	{
		if ($this->document === null)
		{
			throw new \RuntimeException('Property not set.', 999999);
		}
		return $this->document;
	}
	/**
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$statuses = array(Publishable::STATUS_DRAFT,
			Publishable::STATUS_VALIDATION,
			Publishable::STATUS_VALIDCONTENT,
			Publishable::STATUS_VALID,
			Publishable::STATUS_PUBLISHABLE,
			Publishable::STATUS_UNPUBLISHABLE,
			Publishable::STATUS_FROZEN,
			Publishable::STATUS_FILED);

		if (!in_array($value, $statuses))
		{
			$this->setValue($value);
			$this->error(self::PUBLICATION_STATUS_INVALID);
			return false;
		}
		if ($value !== Publishable::STATUS_DRAFT)
		{
			$document = $this->getDocument();
			if ($document->isNew() || (($document instanceof Localizable) && $document->getCurrentLocalization()->isNew()))
			{
				$this->setValue($value);
				$this->error(self::NEW_PUBLICATION_STATUS_INVALID);
				return false;
			}
		}
		return true;
	}
}
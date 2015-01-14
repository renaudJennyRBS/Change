<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Ajax\V1;

/**
 * @name \Change\Http\Ajax\V1\PublishedData
 */
class PublishedData
{
	/**
	 * @var \Change\Documents\Interfaces\Publishable|\Change\Documents\AbstractDocument
	 */
	protected $document;

	/**
	 * @param \Change\Documents\Interfaces\Publishable|\Change\Documents\AbstractDocument $document
	 */
	function __construct($document)
	{
		$this->document = $document;
	}

	/**
	 * @return \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable
	 */
	public function getDocument()
	{
		return $this->document;
	}

	/**
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document
	 * @return $this
	 */
	public function setDocument($document)
	{
		$this->document = $document;
		return $this;
	}

	/**
	 * @param \DateTime $at
	 * @return array
	 */
	public function getCommonData($at = null)
	{
		$data = [];
		$document = $this->document;
		if ($document instanceof \Change\Documents\Interfaces\Publishable && $document->published($at))
		{
			$data['id'] = $document->getId();
			$data['title'] = $document->getDocumentModel()->getPropertyValue($document, 'title');
		}
		return $data;
	}

	/**
	 * @param array $URLFormats
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @param \DateTime $at
	 * @return array
	 */
	public function getURLData($URLFormats, $section, $at = null)
	{
		$data = [];
		$document = $this->document;
		if (!($document instanceof \Change\Documents\Interfaces\Publishable) || !$document->published($at))
		{
			return $data;
		}

		/** @var \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document */
		if (is_array($URLFormats) && count($URLFormats) && $section instanceof \Change\Presentation\Interfaces\Section)
		{
			$website = $section->getWebsite();
			if ($website)
			{
				$urlManager = $website->getUrlManager($website->getLCID());
				if (in_array('canonical', $URLFormats))
				{
					$data['canonical'] = $urlManager->getCanonicalByDocument($document)->normalize()->toString();
				}
				if (in_array('contextual', $URLFormats))
				{
					if ($website === $section)
					{
						$data['contextual'] = $urlManager->getCanonicalByDocument($document)->normalize()->toString();
					}
					else
					{
						$data['contextual'] = $urlManager->getByDocument($document, $section)->normalize()->toString();
					}
				}
				$data['publishedInWebsite'] = $document->getCanonicalSection($website) != null;
			}
		}
		return $data;
	}
}
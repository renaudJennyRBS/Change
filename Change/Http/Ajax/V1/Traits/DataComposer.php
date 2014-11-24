<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Ajax\V1\Traits;

/**
 * @name \Change\Http\Ajax\V1\Traits\DataComposer
 */
trait DataComposer
{
	/**
	 * @var array
	 */
	protected $dataSetNames;

	/**
	 * @var array
	 */
	protected $visualFormats;

	/**
	 * @var array
	 */
	protected $URLFormats;

	/**
	 * @var boolean
	 */
	protected $detailed;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @var \Rbs\Website\Documents\Website
	 */
	protected $website;

	/**
	 * @var \Change\Http\Web\UrlManager
	 */
	protected $websiteUrlManager;

	/**
	 * @var \Rbs\Website\Documents\Section
	 */
	protected $section;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var \Change\Presentation\RichText\RichTextManager
	 */
	protected $richTextManager;

	/**
	 * @param array $context
	 * @return $this
	 */
	protected function setContext(array $context)
	{
		// Set default context values.
		$context += ['visualFormats' => [], 'URLFormats' => [], 'dataSetNames' => [], 'data' => [],
			'website' => null, 'websiteUrlManager' => null, 'section' => null, 'detailed' => false];

		$this->visualFormats = $context['visualFormats'];
		$this->URLFormats = $context['URLFormats'];
		$this->dataSetNames = $context['dataSetNames'];
		$this->detailed = $context['detailed'];
		$this->website = $context['website'];
		$this->websiteUrlManager = $context['websiteUrlManager'];
		$this->section = $context['section'];
		$this->data = $context['data'];

		return $this;
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @return $this
	 */
	protected function setServices(\Change\Services\ApplicationServices $applicationServices)
	{
		$this->documentManager = $applicationServices->getDocumentManager();
		$this->i18nManager = $applicationServices->getI18nManager();
		$this->richTextManager = $applicationServices->getRichTextManager();
		return $this;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return mixed|null
	 */
	protected function getPublishedData($document)
	{
		$publishedData = new \Change\Http\Ajax\V1\PublishedData($document);
		$common =  $publishedData->getCommonData();
		if (count($common))
		{
			$section = $this->section ? $this->section : $this->website;
			$common['URL'] = $publishedData->getURLData($this->URLFormats, $section);
			return $common;
		}
		return null;
	}

	/**
	 * @param \Change\Documents\RichtextProperty $richTextProperty
	 * @return null|string
	 */
	protected function formatRichText($richTextProperty)
	{
		if ($this->website && $richTextProperty instanceof \Change\Documents\RichtextProperty)
		{
			return $this->richTextManager->render($richTextProperty, 'Website',
				['website' => $this->website]);
		}
		return null;
	}

	/**
	 * @param \DateTime $dateTime
	 * @return null|string
	 */
	protected function formatDate($dateTime)
	{
		if ($dateTime instanceof \DateTime)
		{
			return $dateTime->format(\DateTime::ISO8601);
		}
		return null;
	}

	/**
	 * @param string $dataSetName
	 * @return boolean
	 */
	protected function hasDataSet($dataSetName)
	{
		if ($dataSetName && $this->dataSetNames)
		{
			return array_key_exists($dataSetName, $this->dataSetNames);
		}
		return false;
	}
} 
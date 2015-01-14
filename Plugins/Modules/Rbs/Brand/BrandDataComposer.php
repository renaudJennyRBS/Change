<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Brand;

/**
 * @name \Rbs\Brand\BrandDataComposer
 */
class BrandDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Brand\Documents\Brand
	 */
	protected $brand;

	/**
	 * @var \Rbs\Brand\BrandManager
	 */
	protected $brandManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;

	/**
	 * @param \Change\Events\Event $event
	 */
	function __construct(\Change\Events\Event $event)
	{
		$this->brand = $event->getParam('brand');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$this->brandManager = $commerceServices->getBrandManager();
	}

	protected function generateDataSets()
	{
		$brand = $this->brand;

		$this->dataSets = [
			'common' => [
				'id' => $brand->getId(),
				'title' => $brand->getCurrentLocalization()->getTitle(),
				'description' => $this->formatRichText($brand->getCurrentLocalization()->getDescription()),
				'websiteURL' => $brand->getCurrentLocalization()->getWebsiteUrl(),
				'visual' => $this->getImageData($brand->getVisual())
			]
		];

		if (is_array($this->URLFormats) && count($this->URLFormats))
		{
			$publishedData = new \Change\Http\Ajax\V1\PublishedData($this->brand);
			$section = $this->section ? $this->section : $this->website;
			$this->dataSets['common']['URL'] = $publishedData->getURLData($this->URLFormats, $section);
		}
	}

	public function toArray()
	{
		if ($this->dataSets === null)
		{
			$this->generateDataSets();
		}
		return $this->dataSets;
	}

	/**
	 * @param \Rbs\Media\Documents\Image $image
	 * @return array|null
	 */
	protected function getImageData($image)
	{
		$imagesFormats = new \Rbs\Media\Http\Ajax\V1\ImageFormats($image);
		$formats = $imagesFormats->getFormatsData($this->visualFormats);
		return count($formats) ? $formats : null;
	}
}
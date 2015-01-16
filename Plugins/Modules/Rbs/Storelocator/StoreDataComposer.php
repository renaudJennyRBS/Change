<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator;

/**
 * @name \Rbs\Storelocator\StoreDataComposer
 */
class StoreDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Storelocator\Documents\Store
	 */
	protected $store;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;


	function __construct(\Change\Events\Event $event)
	{
		$this->store = $event->getParam('store');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());
	}


	public function toArray()
	{
		if ($this->dataSets === null)
		{
			$this->generateDataSets();
		}
		return $this->dataSets;
	}

	protected function generateDataSets()
	{
		if (!$this->store)
		{
			$this->dataSets = [];
			return;
		}

		$this->dataSets = [
			'common' => [
				'id' => $this->store->getId(),
				'code' => $this->store->getCode(),
				'LCID' => $this->store->getCurrentLCID(),
				'title' => $this->store->getCurrentLocalization()->getTitle(),
			]
		];
		$this->generateCommonDataSet();

		if ($this->detailed || $this->hasDataSet('address')) {
			$address = $this->store->getAddress();
			$this->dataSets['address'] = $address ? $address->toArray() : null;
		}
		if ($this->detailed || $this->hasDataSet('coordinates')) {
			$this->dataSets['coordinates'] = $this->store->getCoordinates();
			$commercialSign = $this->store->getCommercialSign();
			if ($commercialSign && $commercialSign->getMarker()) {
				$marker = $commercialSign->getMarker();
				$imagesFormats = new \Rbs\Media\Http\Ajax\V1\ImageFormats($commercialSign->getMarker());
				$format = $imagesFormats->getFormatsData(['original' => [0, 0]]);
				$format['id'] = $marker->getId();
				$format['size'] = [$marker->getWidth(), $marker->getHeight()];
				unset($format['alt']);
				$this->dataSets['coordinates']['marker'] = $format;
			}
		}

		if ($this->detailed || $this->hasDataSet('commercialSign')) {
			$this->dataSets['commercialSign'] = null;
			$commercialSign = $this->store->getCommercialSign();
			if ($commercialSign instanceof \Rbs\Storelocator\Documents\CommercialSign)
			{
				$data = ['common' => ['id' => $commercialSign->getId(), 'code' => $commercialSign->getCode()]];
				$data['common']['title'] = $commercialSign->getCurrentLocalization()->getTitle();
				$data['presentation']['description'] = $this->formatRichText($commercialSign->getCurrentLocalization()->getDescription());
				$data['presentation']['visuals'] = [];
				foreach ($commercialSign->getVisuals() as $visual)
				{
					$formats = $this->getImageData($visual);
					if ($formats)
					{
						$data['presentation']['visuals'][] = $formats;
						if (!$this->detailed)
						{
							break;
						}
					}
				}
				$this->dataSets['commercialSign'] = $data;
			}
		}

		if ($this->detailed || $this->hasDataSet('services'))
		{
			$this->dataSets['services'] = [];
			foreach ($this->store->getServices() as $service)
			{
				$data = ['common' => ['id' => $service->getId(), 'code' => $service->getCode()]];
				$data['common']['title'] = $service->getCurrentLocalization()->getTitle();
				$data['presentation']['description'] = $this->formatRichText($service->getCurrentLocalization()->getDescription());
				$data['presentation']['pictogram'] = null;
				$formats = $this->getImageData($service->getPictogram());
				if ($formats)
				{
					$data['presentation']['pictogram'] = $formats;
				}
				$this->dataSets['services'][] = $data;
			}
		}

		if ($this->detailed || $this->hasDataSet('hours'))
		{
			$this->dataSets['hours']['openingHours'] = $this->store->addDayTitle($this->store->getOpeningHours(), $this->i18nManager);
			$this->dataSets['hours']['specialDays'] = $this->store->getSpecialDays();
		}

		if ($this->detailed || $this->hasDataSet('card'))
		{
			$card = $this->store->getCard();
			$this->dataSets['card'] = $card ? $card : null;
		}
	}

	protected function generateCommonDataSet()
	{
		$publishedData = new \Change\Http\Ajax\V1\PublishedData($this->store);
		$section = $this->section ? $this->section : $this->website;
		$this->dataSets['common']['URL'] = $publishedData->getURLData($this->URLFormats, $section);
		$visuals = $this->store->getVisuals();
		foreach ($visuals as $visual)
		{
			$formats = $this->getImageData($visual);
			if ($formats)
			{
				$this->dataSets['common']['visuals'][] = $formats;
				if (!$this->detailed)
				{
					break;
				}
			}
		}

		if ($this->detailed || $this->hasDataSet('description'))
		{
			$desc = $this->store->getCurrentLocalization()->getDescription();
			if ($desc && !$desc->isEmpty())
			{
				$this->dataSets['common']['description'] = $this->formatRichText($desc);
			}
		}
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
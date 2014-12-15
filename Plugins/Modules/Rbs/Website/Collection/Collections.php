<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Website\Collection\Collections
 */
class Collections
{

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addInterstitialAudiences(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$items = [
				'all' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_audience_all', array('ucf')),
				'guest' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_audience_guest', array('ucf')),
				'registered' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_audience_registered', array('ucf'))
			];
			$collection = new \Change\Collection\CollectionArray('Rbs_Website_InterstitialAudiences', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addInterstitialDisplayFrequencies(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = array(
				'always' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_display_frequency_always', array('ucf')),
				'session' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_display_frequency_session', array('ucf')),
				'reprieve' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_display_frequency_reprieve', array('ucf')),
				'once' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_display_frequency_once', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Website_InterstitialDisplayFrequencies', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addInterstitialPopinSizes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = array(
				'small' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_popin_size_small', array('ucf')),
				'medium' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_popin_size_medium', array('ucf')),
				'large' => new I18nString($i18n, 'm.rbs.website.admin.interstitial_popin_size_large', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Website_InterstitialPopinSizes', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}
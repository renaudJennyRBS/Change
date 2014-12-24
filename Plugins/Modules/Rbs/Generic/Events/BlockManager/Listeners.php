<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\BlockManager;

use Change\Presentation\Blocks\Standard\RegisterByBlockName;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\BlockManager\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		new RegisterByBlockName('Rbs_Website_Menu', true, $events);
		new RegisterByBlockName('Rbs_Website_Thread', true, $events);
		new RegisterByBlockName('Rbs_Website_SiteMap', true, $events);
		new RegisterByBlockName('Rbs_Website_SwitchLang', true, $events);
		new RegisterByBlockName('Rbs_Website_Richtext', true, $events);
		new RegisterByBlockName('Rbs_Website_Exception', true, $events);
		new RegisterByBlockName('Rbs_Website_Error', true, $events);
		new RegisterByBlockName('Rbs_Website_Interstitial', true, $events);
		new RegisterByBlockName('Rbs_Website_XhtmlTemplate', true, $events);
		new RegisterByBlockName('Rbs_Website_Text', true, $events);
		new RegisterByBlockName('Rbs_Website_HtmlFragment', true, $events);
		new RegisterByBlockName('Rbs_Website_TrackersAskConsent', true, $events);
		new RegisterByBlockName('Rbs_Website_TrackersManage', true, $events);

		new RegisterByBlockName('Rbs_User_Login', true, $events);
		new RegisterByBlockName('Rbs_User_AccountShort', true, $events);
		new RegisterByBlockName('Rbs_User_CreateAccount', true, $events);
		new RegisterByBlockName('Rbs_User_ResetPassword', true, $events);
		new RegisterByBlockName('Rbs_User_EditAccount', true, $events);
		new RegisterByBlockName('Rbs_User_ManageAutoLoginToken', true, $events);
		new RegisterByBlockName('Rbs_User_ChangePassword', true, $events);

		new RegisterByBlockName('Rbs_Simpleform_Form', true, $events);

		new RegisterByBlockName('Rbs_Review_ReviewDetail', true, $events);

		new  RegisterByBlockName('Rbs_Seo_HeadMetas', true, $events);

		new RegisterByBlockName('Rbs_Theme_ThemeSelector', true, $events);
		new RegisterByBlockName('Rbs_Theme_ThemeSelectorMail', true, $events);

		new RegisterByBlockName('Rbs_Elasticsearch_ShortSearch', true, $events);
		new RegisterByBlockName('Rbs_Elasticsearch_ResultHeader', true, $events);
		new RegisterByBlockName('Rbs_Elasticsearch_Result', true, $events);
		new RegisterByBlockName('Rbs_Elasticsearch_Facets', true, $events);
		new RegisterByBlockName('Rbs_Elasticsearch_StoreResult', true, $events);
		new RegisterByBlockName('Rbs_Elasticsearch_StoreFacets', true, $events);

		new RegisterByBlockName('Rbs_Mail_Richtext', true, $events);

		new RegisterByBlockName('Rbs_Geo_ManageAddresses', true, $events);

		new RegisterByBlockName('Rbs_Media_Video', true, $events);
		new RegisterByBlockName('Rbs_Media_Image', true, $events);
		new RegisterByBlockName('Rbs_Media_File', true, $events);

		$callback = function ($event)
		{
			(new \Change\Presentation\Blocks\FileCacheAdapter())->onGetCacheAdapter($event);
		};
		$events->attach(\Change\Presentation\Blocks\BlockManager::EVENT_GET_CACHE_ADAPTER, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}

<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http\Actions;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Actions\GetHome
 */
class GetHome
{
	/**
	 * Use Required Event Params:
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$result = new \Rbs\Admin\Http\Result\Home();
		$templateFileName = implode(DIRECTORY_SEPARATOR, array(__DIR__, 'Assets', 'home.twig'));
		$attributes = array('baseURL' => $event->getUrlManager()->getByPathInfo('/')->normalize()->toString());
		$attributes['LCID'] = $event->getApplicationServices()->getI18nManager()->getLCID();
		$attributes['lang'] = substr($attributes['LCID'], 0, 2);

		/* @var $manager \Rbs\Admin\Manager */
		$manager = $event->getParam('manager');


		$OAuth = $event->getApplicationServices()->getOAuthManager();
		$consumer = $OAuth->getConsumerByApplication('Rbs_Admin');
		$attributes['OAuth']['Consumer'] = $consumer ? array_merge($consumer->toArray(), array('realm' => 'Rbs_Admin')) : array();

		$attributes['mainMenu'] = $manager->getMainMenu();
		$manager->getResources();

		$devMode = $event->getApplication()->inDevelopmentMode();
		$renderer = function () use ($templateFileName, $manager, $attributes, $devMode)
		{

			$resourceDirectoryPath = $devMode ? $manager->getResourceDirectoryPath() : null;
			$resourceBaseUrl = $manager->getResourceBaseUrl();

			$scripts = $manager->prepareScriptAssets($resourceDirectoryPath, $resourceBaseUrl);
			$attributes = ['scripts' => $scripts] + $attributes;

			$styles = $manager->prepareCssAssets($resourceDirectoryPath, $resourceBaseUrl);
			$attributes = ['styles' => $styles] + $attributes;

			$manager->prepareImageAssets($resourceDirectoryPath);

			return $manager->renderTemplateFile($templateFileName, $attributes);
		};
		$result->setRenderer($renderer);
		$event->setResult($result);
	}
}
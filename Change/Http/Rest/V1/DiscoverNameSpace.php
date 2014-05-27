<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\V1\DiscoverNameSpace
 */
class DiscoverNameSpace
{

	/**
	 * Use Event Params: namespace, resolver
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$namespace = $event->getParam('namespace');
		$resolver = $event->getParam('resolver');
		if (!($resolver instanceof NameSpaceDiscoverInterface))
		{
			return;
		}

		/* @var $names string[] */
		$names = $resolver->getNextNamespace($event, explode('.', $namespace));
		if (!count($names))
		{
			return;
		}

		$result = new NamespaceResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$urlManager = $event->getUrlManager();
		$selfLink = new Link($urlManager, $this->generatePathInfoByNamespace($namespace));
		$result->addLink($selfLink);

		foreach ($names as $name)
		{
			$link = new Link($urlManager, $this->generatePathInfoByNamespace($name), $name);
			$result->addLink($link);
		}
		$event->setResult($result);
		return;
	}

	/**
	 * @param string $namespace
	 * @return string
	 */
	protected function generatePathInfoByNamespace($namespace)
	{
		if (empty($namespace))
		{
			return '/';
		}
		return '/' . str_replace('.', '/', $namespace) . '/';
	}
}

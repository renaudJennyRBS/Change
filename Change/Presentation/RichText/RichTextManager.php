<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\RichText;

/**
 * @name \Change\Presentation\RichText\RichTextManager
 */
class RichTextManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const DEFAULT_IDENTIFIER = 'Presentation.RichText';
	const EVENT_RENDER = 'render';

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::DEFAULT_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/RichTextManager');
	}

	/**
	 * @param \Change\Documents\RichtextProperty $richText
	 * @param string $profile 'Admin' or 'Website'
	 * @param array|null $context
	 * @return string
	 */
	public function render(\Change\Documents\RichtextProperty $richText, $profile, $context = null)
	{
		$eventManager = $this->getEventManager();
		$params = [
			'profile' => $profile,
			'richText' => $richText,
			'context' => $context
		];
		$params = $eventManager->prepareArgs($params);
		$eventManager->trigger(static::EVENT_RENDER, $this, $params);
		$html = (isset($params['html']) ? $params['html'] : '');
		$richText->setHtml($html);
		return trim($html);
	}
}
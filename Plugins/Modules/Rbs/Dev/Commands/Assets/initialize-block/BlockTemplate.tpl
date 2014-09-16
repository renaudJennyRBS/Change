<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace #namespace#;

/**
 * @name \#namespace#\#className#
 */
class #className# extends \Change\Presentation\Blocks\Standard\Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);

		// Declare your parameters here.
		//$parameters->addParameterMeta('myParameter');

		$parameters->setLayoutParameters($event->getBlockLayout());

		// Uncomment following line to disable caches on this block.
		//$parameters->setNoCache();

		// Fill your parameters here.
		//$parameters->setParameterValue('myParameter', $value);

		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();

		// Implement your block here.

		return '#templateName#';
	}
}
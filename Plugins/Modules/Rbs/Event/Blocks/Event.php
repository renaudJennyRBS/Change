<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Event\Blocks;

/**
 * @name \Rbs\Event\Blocks\Event
 */
class Event extends \Rbs\Event\Blocks\Base\BaseEvent
{
	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		return ($document instanceof \Rbs\Event\Documents\Event && $document->published());
	}

	/**
	 * @return string
	 */
	protected function getDefaultTemplateName()
	{
		return 'event.twig';
	}
}
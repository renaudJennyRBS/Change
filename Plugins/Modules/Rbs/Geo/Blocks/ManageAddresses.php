<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Blocks;

/**
 * @name \Rbs\Geo\Blocks\ManageAddresses
 */
class ManageAddresses extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta('authenticated');

		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		$user = $event->getAuthenticationManager()->getCurrentUser();
		$parameters->setParameterValue('authenticated', $user->authenticated());
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
		if ($parameters->getParameter('authenticated'))
		{
			return 'manage-addresses-list.twig';
		}
		return null;
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Blocks;

/**
 * @name \Rbs\User\Blocks\EditAccount
 */
class EditAccount extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta('authenticated', false);
		$parameters->addParameterMeta('accessorId', null);
		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		$page = $event->getParam('page');
		if ($page instanceof \Rbs\Website\Documents\Page)
		{
			$parameters->setParameterValue('pageId', $page->getId());
		}

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			$parameters->setParameterValue('authenticated', true);
			$parameters->setParameterValue('accessorId', $user->getId());
		}

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
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$context = new \Change\Http\Ajax\V1\Context($event->getApplication(), $documentManager);
		$context->setPage($parameters->getParameter('pageId'));
		$context->setDetailed(true);
		$section = $event->getParam('section');
		if ($section)
		{
			$context->setSection($section);
		}

		/* @var \Rbs\Generic\GenericServices $genericServices */
		$genericServices = $event->getServices('genericServices');
		$attributes['userData'] = $genericServices->getUserManager()->getUserData($parameters->getParameter('accessorId'), $context->toArray());

		return 'edit-account.twig';
	}
}
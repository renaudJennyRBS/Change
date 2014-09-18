<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Blocks;

/**
 * @name \Rbs\Order\Blocks\CreditNoteSummary
 */
class CreditNoteSummary extends \Change\Presentation\Blocks\Standard\Block
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
		$parameters->addParameterMeta('showIfEmpty', false);
		$parameters->addParameterMeta('usage');
		$parameters->setLayoutParameters($event->getBlockLayout());

		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
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
		$user = $documentManager->getDocumentInstance($parameters->getParameter('accessorId'));
		if ($user instanceof \Rbs\User\Documents\User)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$orderManager = $commerceServices->getOrderManager();
			$creditNotesInfo = $orderManager->getAvailableCreditNotesInfo($user);
			$attributes['creditNotesInfo'] = $creditNotesInfo;
			if ($parameters->getParameterValue('showIfEmpty') || count($creditNotesInfo) > 0)
			{
				$usage = $documentManager->getDocumentInstance($parameters->getParameter('usage'));
				if ($usage instanceof \Rbs\Website\Documents\Text && !$usage->getCurrentLocalization()->getText()->isEmpty())
				{
					$attributes['usage'] = $usage->getCurrentLocalization()->getText();
				}
				return 'credit-note-summary.twig';
			}
		}
		return null;
	}
}
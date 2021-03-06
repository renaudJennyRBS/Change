<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;

/**
 * @name \Rbs\Payment\Blocks\CreateAccountForTransaction
 */
class CreateAccountForTransaction extends \Rbs\User\Blocks\CreateAccount
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);

		$request = $event->getHttpRequest();
		$transactionId = $request->getQuery('transactionId');
		$transaction = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($transactionId);
		if ($transaction instanceof \Rbs\Payment\Documents\Transaction && $transaction->getEmail())
		{
			$parameters->addParameterMeta('transactionId');
			$parameters->setParameterValue('transactionId', $transactionId);
			$parameters->setParameterValue('formAction', 'Action/Rbs/Payment/CreateAccountRequest?transactionId=' . $transactionId);

			$initialValues = $parameters->getParameterValue('initialValues');
			$initialValues['email'] = $transaction->getEmail();
			$parameters->setParameterValue('initialValues', $initialValues);
			$readonlyNames = $parameters->getParameterValue('readonlyNames');
			$readonlyNames['email'] = true;
			$parameters->setParameterValue('readonlyNames', $readonlyNames);
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		if (!$parameters->getParameterValue('transactionId'))
		{
			return null;
		}
		$this->setTemplateModuleName('Rbs_User');
		return parent::execute($event, $attributes);
	}
}
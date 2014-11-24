<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Http\Ajax;

/**
* @name \Rbs\Productreturn\Http\Ajax\Process
*/
class Process
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Productreturn\ReturnManager
	 */
	protected $returnManager;

	/**
	 * @var array
	 */
	protected $context;

	/**
	 * Default actionPath: Rbs/Productreturn/Process/([0-9]+)
	 * event param : processId
	 * @param \Change\Http\Event $event
	 */
	public function getOrder(\Change\Http\Event $event)
	{
		/** @var \Rbs\Commerce\CommerceServices $commerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!$commerceServices)
		{
			return;
		}

		$this->returnManager = $commerceServices->getReturnManager();
		$this->documentManager = $event->getApplicationServices()->getDocumentManager();

		$this->context = $event->paramsToArray();
		$orderData = $this->returnManager->getProcessData($event->getParam('processId'), $this->context);

		$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/Productreturn/Process', $orderData);
		$event->setResult($result);
	}
} 
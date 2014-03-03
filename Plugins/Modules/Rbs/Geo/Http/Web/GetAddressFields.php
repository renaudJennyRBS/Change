<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Http\Web;

/**
* @name \Rbs\Commerce\Http\Web\GetCurrentCart
*/
class GetAddressFields extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$request = $event->getRequest();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$query = $documentManager->getNewQuery('Rbs_Geo_AddressFields');
		$d2qb = $query->getModelBuilder('Rbs_Geo_Country', 'addressFields');
		$query->andPredicates($d2qb->eq('code', $arguments['countryCode']), $d2qb->activated());
		$addressFields = $query->getFirstDocument();
		if ($addressFields instanceof \Rbs\Geo\Documents\AddressFields)
		{
			$formDefinition = new \Rbs\Geo\Presentation\FormDefinition($addressFields);
			$formDefinition->setCollectionManager($event->getApplicationServices()->getCollectionManager());

			$definition = $formDefinition->toArray();
			$definition['fieldsLayout'] = $addressFields->getFieldsLayoutData();

			$result = $this->getNewAjaxResult($definition);
			$event->setResult($result);
			return;
		}
	}
}
<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Mails;

use Change\Events\Event;

/**
 * @name \Rbs\Productreturn\Mails\InstallMails
 */
class InstallMails
{
	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$mailTemplate = $event->getParam('mailTemplate');
		$filters = $event->getParam('filters');

		if (count($filters) === 0 || in_array('Rbs_Productreturn', $filters))
		{
			$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'mails.json';
			$json = json_decode(file_get_contents($filePath), true);

			$import = new \Rbs\Generic\Json\Import($applicationServices->getDocumentManager());
			$import->addOnly(true);
			$import->setDocumentCodeManager($applicationServices->getDocumentCodeManager());

			$resolveDocument = function ($id, $contextId) use ($mailTemplate)
			{
				switch ($id)
				{
					case 'mail_template':
						return $mailTemplate;
						break;
				}
				return null;
			};
			$import->getOptions()->set('resolveDocument', $resolveDocument);

			try
			{
				$applicationServices->getTransactionManager()->begin();
				$import->fromArray($json);
				$applicationServices->getTransactionManager()->commit();
			}
			catch (\Exception $e)
			{
				throw $applicationServices->getTransactionManager()->rollBack($e);
			}
		}
	}
}
<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Admin;

/**
 * @name \Rbs\User\Admin\SearchDocuments
 */
class SearchDocuments
{
	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function execute(\Change\Events\Event $event)
	{
		if (is_array($event->getParam('documents')))
		{
			return;
		}

		$modelName = $event->getParam('modelName');
		if ($modelName == 'Rbs_User_User')
		{
			$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery($modelName);
			$query->orPredicates(
				$query->like('login', $event->getParam('searchString')),
				$query->like('email', $event->getParam('searchString'))
			);
			$query->addOrder('login');
			$query->addOrder('email');
			$query->addOrder('id');
			$event->setParam('documents', $query->getDocuments(0, $event->getParam('limit'))->toArray());
		}
	}
} 
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Theme\Events;

/**
 * @name \Rbs\Theme\Events\MailTemplateResolver
 */
class MailTemplateResolver
{
	/**
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Theme\Documents\MailTemplate|null
	 */
	public function resolve($event)
	{
		$code = $event->getParam('code');
		$theme = $event->getParam('theme');
		$applicationServices = $event->getApplicationServices();
		if ($code && $theme && $applicationServices)
		{
			$mailTemplateModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_MailTemplate');
			$query = $applicationServices->getDocumentManager()->getNewQuery($mailTemplateModel);
			$query->andPredicates($query->eq('code', $code), $query->eq('theme', $theme));
			return $query->getFirstDocument();
		}
		return null;
	}
}
<?php
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
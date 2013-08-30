<?php
namespace Rbs\Theme\Events;

/**
 * @name \Rbs\Theme\Events\MailTemplateResolver
 */
class MailTemplateResolver
{
	/**
	 * @param \Zend\EventManager\Event $event
	 * @return \Rbs\Theme\Documents\MailTemplate|null
	 */
	public function resolve($event)
	{
		$code = $event->getParam('code');
		$theme = $event->getParam('theme');
		/* @var $documentServices \Change\Documents\DocumentServices */
		$documentServices = $event->getParam('documentServices');
		$documentServices->getApplicationServices()->getLogging()->fatal(var_export([$code, $theme->getLabel()], true));
		if ($code && $theme && $documentServices)
		{
			$mailTemplateModel = $documentServices->getModelManager()->getModelByName('Rbs_Theme_MailTemplate');
			$query = new \Change\Documents\Query\Query($documentServices, $mailTemplateModel);
			$query->andPredicates($query->eq('code', $code), $query->eq('theme', $theme));
			return $query->getFirstDocument();
		}
		return null;
	}
}
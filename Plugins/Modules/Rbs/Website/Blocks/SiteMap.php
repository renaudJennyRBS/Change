<?php
namespace Rbs\Website\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;

/**
 * @name \Rbs\Website\Blocks\SiteMap
 */
class SiteMap extends Menu
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->getParameterMeta('templateName')->setDefaultValue('siteMap.twig');
		$parameters->getParameterMeta('maxLevel')->setDefaultValue(5);
		$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $event->getParam('website')->getId());
		return $parameters;
	}
}
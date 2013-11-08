<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
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
		$parameters->setParameterValue('documentId', $event->getParam('website')->getId());
		return $parameters;
	}
}
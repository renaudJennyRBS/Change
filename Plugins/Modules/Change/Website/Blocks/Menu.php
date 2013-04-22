<?php
namespace Change\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * TODO Sample
 * @name \Change\Website\Blocks\Menu
 */
class Menu extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getPresentationServices, getDocumentServices
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('showTitle', Property::TYPE_BOOLEAN, true, false);
		$parameters->addParameterMeta('documentId', Property::TYPE_DOCUMENT);
		$parameters->addParameterMeta('maxLevel', Property::TYPE_INTEGER, true, 1);

		$parameters->setLayoutParameters($event->getBlockLayout());
		$request = $event->getHttpRequest();
		if ($request)
		{
			//TODO Fill request parameters
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters(), getBlockResult(),
	 *        getPresentationServices(), getDocumentServices()
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$attributes['test'] = 'plop plob plop';
		$event->getBlockResult()->addNamedHeadAsString('description', '<meta name="description" content="AMenu" />');
		return 'menu.twig';
	}
}
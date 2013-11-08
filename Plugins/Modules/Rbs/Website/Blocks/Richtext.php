<?php
namespace Rbs\Website\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * TODO Sample
 * @name \Rbs\Website\Blocks\Richtext
 */
class Richtext extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('content');
		$parameters->addParameterMeta('contentType', 'Markdown');
		$parameters->setLayoutParameters($event->getBlockLayout());
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$context = array('website' => $event->getUrlManager()->getWebsite());
		$richText = new \Change\Documents\RichtextProperty();
		$richText->setRawText($event->getBlockParameters()->getParameter('content'));
		$richText->setEditor($event->getBlockParameters()->getParameter('contentType'));
		$attributes['htmlContent'] = $event->getApplicationServices()
			->getRichTextManager()
			->render($richText, 'Website', $context);
		return 'richtext.twig';
	}
}
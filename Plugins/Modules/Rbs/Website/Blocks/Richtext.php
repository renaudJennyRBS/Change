<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
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
	 * Required Event method: getBlockLayout, getPresentationServices, getDocumentServices
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('content', Property::TYPE_LONGSTRING);
		$parameters->addParameterMeta('contentType', Property::TYPE_STRING, true, 'html');
		$parameters->setLayoutParameters($event->getBlockLayout());
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
		$richText = new \Change\Documents\RichtextProperty();
		$richText->setRawText($event->getBlockParameters()->getParameter('content'));
		$richText->setEditor($event->getBlockParameters()->getParameter('contentType'));
		$attributes['htmlContent'] = $event->getPresentationServices()
			->getRichTextManager()
			->setDocumentServices($event->getDocumentServices())
			->render($richText, "Website");
		return 'richtext.twig';
	}
}
<?php
namespace Rbs\Social\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Social\Blocks\SocialButtons
 */
class SocialButtons extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$websiteId = $event->getParam('website')->getId();
		$parameters->addParameterMeta('websiteId', $websiteId);
		$this->setParameterValueForDetailBlock($parameters, $event);
		$parameters->setLayoutParameters($event->getBlockLayout());

		// If target is not set take the page instead.
		if (!$parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME))
		{
			$page = $event->getParam('page');
			if ($page instanceof \Rbs\Website\Documents\StaticPage)
			{
				$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $event->getParam('page')->getId());
			}
		}

		return $parameters;
	}

	/**
	 * @api
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		return $document instanceof \Change\Documents\Interfaces\Publishable && $document->published();
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$document = $documentManager->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));
		if ($document)
		{
			$attributes['sharedTitle'] = $document->getDocumentModel()->getPropertyValue($document, 'title');

			$urlManager = $event->getUrlManager();
			$absoluteUrl = $urlManager->absoluteUrl(true);
			$attributes['sharedUrl'] = $urlManager->getCanonicalByDocument($document)->normalize()->toString();
			$urlManager->absoluteUrl($absoluteUrl);

			return 'social-buttons.twig';
		}
		return null;
	}
}
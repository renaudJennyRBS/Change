<?php
namespace Rbs\Event\Blocks;

/**
 * @name \Rbs\Event\Blocks\News
 */
class News extends \Rbs\Event\Blocks\Base\BaseEvent
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->getParameterMeta('templateName')->setDefaultValue('news.twig');
		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function checkDocumentType($document)
	{
		return ($document instanceof \Rbs\Event\Documents\News);
	}
}
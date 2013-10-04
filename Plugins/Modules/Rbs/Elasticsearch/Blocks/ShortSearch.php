<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Elasticsearch\Blocks\ShortSearch
 */
class ShortSearch extends Block
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
		$parameters->addParameterMeta('resultSectionId');
		$parameters->addParameterMeta('searchText');
		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$searchText = $request->getQuery('searchText');
		if ($searchText && is_string($searchText))
		{
			$parameters->setParameterValue('searchText', $searchText);
		}

		return $parameters;
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
		$resultSection = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters->getParameter('resultSectionId'));
		$attributes['resultSection'] = $resultSection;
		return 'short-search.twig';
	}
}
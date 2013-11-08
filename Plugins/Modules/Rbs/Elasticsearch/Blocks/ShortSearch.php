<?php
namespace Rbs\Elasticsearch\Blocks;

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
		$parameters->addParameterMeta('formAction');
		$parameters->addParameterMeta('sectionPageFunction', false);
		$parameters->setLayoutParameters($event->getBlockLayout());
		$resultSection = $event->getApplicationServices()->getDocumentManager()
			->getDocumentInstance($parameters->getParameter('resultSectionId'));
		$uri = $event->getUrlManager()->getByFunction('Rbs_Elasticsearch_Result', $resultSection);
		if ($uri)
		{
			$formAction = $uri->normalize()->toString();
			$query = $uri->getQueryAsArray();
			if (isset($query['sectionPageFunction']))
			{
				$parameters->setParameterValue('sectionPageFunction', $query['sectionPageFunction']);
			}

			$parameters->setParameterValue('formAction', $formAction);
			$request = $event->getHttpRequest();
			$searchText = $request->getQuery('searchText');
			if ($searchText && is_string($searchText))
			{
				$parameters->setParameterValue('searchText', $searchText);
			}
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
		if ($parameters->getParameter('formAction'))
		{
			return 'short-search.twig';
		}
		return null;
	}
}
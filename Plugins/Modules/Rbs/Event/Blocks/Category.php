<?php
namespace Rbs\Event\Blocks;

/**
 * @name \Rbs\Event\Blocks\Category
 */
class Category extends \Rbs\Event\Blocks\Base\BaseEventList
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
		$parameters->addParameterMeta('sectionRestriction', 'website');
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->getParameterMeta('templateName')->setDefaultValue('category.twig');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters = $this->setParameterValueForDetailBlock($parameters, $event);

		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Event\Documents\Category && $document->published())
		{
			return true;
		}
		return false;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @param \Rbs\Website\Documents\Website $website
	 * @param \Rbs\Website\Documents\Section $section
	 * @return string|null
	 */
	protected function doExecute($event, $attributes, $website, $section)
	{
		$parameters = $event->getBlockParameters();
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$category = $documentManager->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));
		if (!($category instanceof \Rbs\Event\Documents\Category))
		{
			return null;
		}
		$attributes['category'] = $category;

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$category = $documentManager->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));
		$query = $documentManager->getNewQuery('Rbs_Event_BaseEvent');
		$query->andPredicates($query->published(), $query->eq('categories', $category));
		$subQuery1 = $query->getPropertyBuilder('publicationSections');

		switch ($parameters->getParameter('sectionRestriction'))
		{
			case 'website':
				$treePredicateBuilder = new \Change\Documents\Query\TreePredicateBuilder($subQuery1, $event->getApplicationServices()->getTreeManager());
				$subQuery1->andPredicates(
					$subQuery1->getPredicateBuilder()->logicOr(
						$subQuery1->eq('id', $website->getId()),
						$treePredicateBuilder->descendantOf($website)
					)
				);
				break;
			case 'section':
				$subQuery1->andPredicates(
					$subQuery1->eq('id', $section->getId())
				);
				break;
			case 'sectionAndSubsections':
				$treePredicateBuilder = new \Change\Documents\Query\TreePredicateBuilder($subQuery1, $event->getApplicationServices()->getTreeManager());
				$subQuery1->andPredicates(
					$subQuery1->getPredicateBuilder()->logicOr(
						$subQuery1->eq('id', $section->getId()),
						$treePredicateBuilder->descendantOf($section)
					)
				);
		}
		$query->addOrder('date', false);

		$hasList = $this->renderList($event, $attributes, $query, $section, $website);
		return $hasList ? $parameters->getParameter('templateName') : null;
	}
}
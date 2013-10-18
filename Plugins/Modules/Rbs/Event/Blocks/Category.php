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
		$parameters->addParameterMeta('categoryId');
		$parameters->getParameterMeta('templateName')->setDefaultValue('category.twig');

		$parameters->setLayoutParameters($event->getBlockLayout());

		if ($parameters->getParameter('categoryId') === null)
		{
			$document = $event->getParam('document');
			if ($document instanceof \Rbs\Event\Documents\Category)
			{
				$parameters->setParameterValue('categoryId', $document->getId());
			}
		}
		return $parameters;
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
		$documentManager = $event->getDocumentServices()->getDocumentManager();
		$category = $documentManager->getDocumentInstance($parameters->getParameter('categoryId'));
		if (!($category instanceof \Rbs\Event\Documents\Category))
		{
			return null;
		}
		$attributes['category'] = $category;

		$documentManager = $event->getDocumentServices()->getDocumentManager();
		$category = $documentManager->getDocumentInstance($parameters->getParameter('categoryId'));
		$query = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Event_BaseEvent');
		$query->andPredicates($query->published(), $query->eq('categories', $category));
		$subQuery1 = $query->getPropertyBuilder('publicationSections');

		switch ($parameters->getParameter('sectionRestriction'))
		{
			case 'website':
				$subQuery1->andPredicates(
					$subQuery1->getPredicateBuilder()->logicOr(
						$subQuery1->eq('id', $website->getId()),
						$subQuery1->descendantOf($website)
					)
				);
				break;

			case 'section':
				$subQuery1->andPredicates(
					$subQuery1->eq('id', $section->getId())
				);

			case 'sectionAndSubsections':
				$subQuery1->andPredicates(
					$subQuery1->getPredicateBuilder()->logicOr(
						$subQuery1->eq('id', $section->getId()),
						$subQuery1->descendantOf($section)
					)
				);
		}
		$query->addOrder('date', false);

		$hasList = $this->renderList($event, $attributes, $query, $section, $website);
		return $hasList ? $parameters->getParameter('templateName') : null;
	}
}
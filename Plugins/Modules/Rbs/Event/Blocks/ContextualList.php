<?php
namespace Rbs\Event\Blocks;

/**
 * @name \Rbs\Event\Blocks\ContextualList
 */
class ContextualList extends \Rbs\Event\Blocks\Base\BaseEventList
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
		$parameters->addParameterMeta('includeSubSections', true);
		$parameters->getParameterMeta('templateName')->setDefaultValue('contextual-list.twig');

		$parameters->setLayoutParameters($event->getBlockLayout());
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
		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Event_BaseEvent');
		$query->andPredicates($query->published());
		$subQuery1 = $query->getPropertyBuilder('publicationSections');
		if ($parameters->getParameter('includeSubSections'))
		{
			$treePredicateBuilder = new \Change\Documents\Query\TreePredicateBuilder($subQuery1, $event->getApplicationServices()->getTreeManager());
			$subQuery1->andPredicates(
				$subQuery1->getPredicateBuilder()->logicOr(
					$subQuery1->eq('id', $section->getId()),
					$treePredicateBuilder->descendantOf($section)
				)
			);
		}
		else
		{
			$subQuery1->andPredicates($subQuery1->eq('id', $section->getId()));
		}
		$query->addOrder('date', false);

		$hasList = $this->renderList($event, $attributes, $query, $section, $website);
		return $hasList ? $parameters->getParameter('templateName') : null;
	}
}
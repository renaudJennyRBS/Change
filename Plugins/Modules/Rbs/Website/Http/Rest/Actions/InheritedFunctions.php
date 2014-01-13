<?php
namespace Rbs\Website\Http\Rest\Actions;

use Change\Documents\Query\Query;
use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Website\Http\Rest\Actions\InheritedFunctions
 */
class InheritedFunctions
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		if ($request->isGet())
		{
			$event->setResult($this->generateResult($event->getApplicationServices(), $request->getQuery('section')));
		}
	}


	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param $sectionId integer
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($applicationServices, $sectionId)
	{
		if (! $sectionId)
		{
			return;
		}

		$treeManager = $applicationServices->getTreeManager();
		$currentNode = $treeManager->getNodeById($sectionId, 'Rbs_Website');
		if (! $currentNode)
		{
			return;
		}

		$ancestorNodes = $treeManager->getAncestorNodes($currentNode);
		$dm = $applicationServices->getDocumentManager();
		$functionsByCode = array();
		$result = new \Change\Http\Rest\Result\ArrayResult();

		// Traverse the tree from root to deepest section.
		foreach ($ancestorNodes as $ancestor)
		{
			/* @var $section \Rbs\Website\Documents\Section */
			$section = $dm->getDocumentInstance($ancestor->getDocumentId());

			// Skip anything that is not a Section (ie. the root folder of the tree).
			if ($section instanceof \Rbs\Website\Documents\Section)
			{
				$query = $dm->getNewQuery('Rbs_Website_SectionPageFunction');
				$query->andPredicates($query->eq('section', $section->getId()));
				$functions = array();

				/* @var $spf \Rbs\Website\Documents\SectionPageFunction */
				foreach ($query->getDocuments() as $spf)
				{
					/* @var $page \Rbs\Website\Documents\Page */
					$page = $spf->getPage();
					$section = $spf->getSection();
					$code = $spf->getFunctionCode();

					// Skip special function 'Rbs_Website_Section' (index page).
					// Since we are traversing the tree from root to deepest section, Functions implemented
					// at a lower level will override any existing Function in the '$functionsByCode' array.
					if ($code !== 'Rbs_Website_Section')
					{
						$functionsByCode[$code] = array(
							'id' => $spf->getId(),
							'code' => $code,
							'page' => array(
								'id' => $page->getId(),
								'LCID' => $page->getCurrentLCID(),
								'model' => $page->getDocumentModelName(),
								'label' => $page->getLabel()
							),
							'section' => array(
								'id' => $section->getId(),
								'LCID' => $section->getCurrentLCID(),
								'model' => $section->getDocumentModelName(),
								'label' => $section->getLabel()
							)
						);
					}
				}
			}
		}

		$result->setArray($functionsByCode);

		return $result;
	}
}
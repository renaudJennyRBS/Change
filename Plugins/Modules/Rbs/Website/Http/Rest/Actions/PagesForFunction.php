<?php
namespace Rbs\Website\Http\Rest\Actions;

use Change\Documents\Query\Query;
use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * Returns a list of FunctionalPages that has the given function.
 *
 * @name \Rbs\Website\Http\Rest\Actions\PagesForFunction
 */
class PagesForFunction
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		$billingArea = null;
		if ($request->isGet())
		{
			$event->setResult($this->generateResult($event->getDocumentServices(), $request->getQuery('function')));
		}
	}


	/**
	 * @param $documentServices \Change\Documents\DocumentServices
	 * @param $function string
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($documentServices, $function)
	{
		$result = new \Change\Http\Rest\Result\ArrayResult();
		$pagesForFunction = array();

		$query = new Query($documentServices, 'Rbs_Website_FunctionalPage');
		$query->andPredicates($query->like('allowedFunctionsCode', '"' . $function . '"'));
		$pages = $query->getDocuments();

		if (count($pages))
		{
			$presentationServices = new \Change\Presentation\PresentationServices($documentServices->getApplicationServices());
			$blockManager = $presentationServices->getBlockManager();
			$allFunctions = array();
			foreach ($blockManager->getBlockNames() as $blockName)
			{
				$blockInfo = $blockManager->getBlockInformation($blockName);
				if ($blockInfo)
				{
					foreach ($blockInfo->getFunctions() as $name => $label)
					{
						$allFunctions[$name] = $label;
					}
				}
			}

			foreach ($pages as $page)
			{
				/* @var $page \Rbs\Website\Documents\FunctionalPage */
				$funcs = array();
				foreach($page->getAllowedFunctionsCode() as $code)
				{
					$query = new Query($documentServices, 'Rbs_Website_SectionPageFunction');
					$query->andPredicates($query->eq('functionCode', $code));
					$funcs[] = array(
						"code" => $code,
						"label" => $allFunctions[$code],
						"usage" => $query->getCountDocuments()
					);
				}

				$pagesForFunction[] = array(
					"id" => $page->getId(),
					"label" => $page->getLabel(),
					"website" => $page->getWebsite()->getLabel(),
					"functions" => $funcs
				);
			}

		}


		$result->setArray($pagesForFunction);

		return $result;
	}
}
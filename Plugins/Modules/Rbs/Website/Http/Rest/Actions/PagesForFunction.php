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
		foreach ($query->getDocuments() as $page)
		{
			$pagesForFunction[] = array(
				"id" => $page->getId(),
				"label" => $page->getLabel(),
				"website" => $page->getWebsite()->getLabel()
			);
		}

		$result->setArray($pagesForFunction);

		return $result;
	}
}
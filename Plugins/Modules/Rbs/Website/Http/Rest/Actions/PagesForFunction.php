<?php
namespace Rbs\Website\Http\Rest\Actions;

use Change\Http\Event;
use Change\Http\Request;
use Zend\Http\Response as HttpResponse;

/**
 * Returns a list of FunctionalPages that has the given function.
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
		if ($request->isGet())
		{
			$function = strval($request->getQuery('function'));
			$websiteId = intval($request->getQuery('websiteId'));
			$event->setResult($this->generateResult($event->getApplicationServices(), $function, $websiteId));
		}
		else
		{
			$result = $event->getController()->notAllowedError($request->getMethod(), [Request::METHOD_GET]);
			$event->setResult($result);
		}
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param string $function
	 * @param integer $websiteId
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($applicationServices, $function, $websiteId)
	{
		$pagesForFunction = array();
		$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Website_FunctionalPage');
		$query->andPredicates($query->eq('website', $websiteId), $query->like('supportedFunctionsCode', '"' . $function . '"'));
		$pages = $query->getDocuments();

		if (count($pages))
		{
			$allFunctions = $applicationServices->getPageManager()->getFunctions();
			foreach ($applicationServices->getPageManager()->getFunctions() as $function)
			{
				$allFunctions[$function['code']] = $function['label'];
			}

			foreach ($pages as $page)
			{
				/* @var $page \Rbs\Website\Documents\FunctionalPage */
				$funcs = array();
				foreach ($page->getAllSupportedFunctionsCode() as $code)
				{
					if (isset($allFunctions[$code]))
					{
						$funcs[] = ["code" => $code, "label" => $allFunctions[$code]];
					}
				}

				$pagesForFunction[] = array(
					"id" => $page->getId(),
					"label" => $page->getLabel(),
					"website" => $page->getWebsite()->getLabel(),
					"functions" => $funcs
				);
			}
		}

		$result = new \Change\Http\Rest\Result\ArrayResult();
		$result->setArray($pagesForFunction);
		return $result;
	}
}
<?php
namespace Rbs\Website\Http\Rest\Actions;

use Change\Documents\Query\Query;
use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * Returns the list of all the functions declared in the blocks.
 *
 * @name \Rbs\Website\Http\Rest\Actions\FunctionsList
 */
class FunctionsList
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
			$event->setResult($this->generateResult($event->getDocumentServices()));
		}
	}


	/**
	 * @param $documentServices \Change\Documents\DocumentServices
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($documentServices)
	{
		$result = new \Change\Http\Rest\Result\ArrayResult();

		$presentationServices = new \Change\Presentation\PresentationServices($documentServices->getApplicationServices());
		$blockManager = $presentationServices->getBlockManager();
		$parsedFunctions = array();
		foreach ($blockManager->getBlockNames() as $blockName)
		{
			$blockInfo = $blockManager->getBlockInformation($blockName);
			if ($blockInfo)
			{
				foreach ($blockInfo->getFunctions() as $funcName => $label)
				{
					$query = new Query($documentServices, 'Rbs_Website_SectionPageFunction');
					$query->andPredicates($query->eq('functionCode', $funcName));
					$parsedFunctions[] = array(
						"code"  => $funcName,
						"label" => $label,
						"usage" => $query->getCountDocuments()
					);
				}
			}
		}

		$result->setArray($parsedFunctions);

		return $result;
	}
}
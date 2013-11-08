<?php
namespace Rbs\Website\Http\Rest\Actions;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * Returns the list of all the functions declared in the blocks.
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
			$event->setResult($this->generateResult($event->getApplicationServices()));
		}
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($applicationServices)
	{
		$result = new \Change\Http\Rest\Result\ArrayResult();

		$blockManager = $applicationServices->getBlockManager();
		$parsedFunctions = array();
		foreach ($blockManager->getBlockNames() as $blockName)
		{
			$blockInfo = $blockManager->getBlockInformation($blockName);
			if ($blockInfo)
			{
				foreach ($blockInfo->getFunctions() as $funcName => $label)
				{
					$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Website_SectionPageFunction');
					$query->andPredicates($query->eq('functionCode', $funcName));
					$parsedFunctions[] = array(
						"code" => $funcName,
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
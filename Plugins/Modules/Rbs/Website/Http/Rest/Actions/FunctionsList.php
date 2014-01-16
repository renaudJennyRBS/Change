<?php
namespace Rbs\Website\Http\Rest\Actions;

use Change\Http\Event;
use Change\Http\Request;
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
		if (!$request->isGet())
		{
			$result = $event->getController()->notAllowedError($request->getMethod(), [Request::METHOD_GET]);
			$event->setResult($result);
			return;
		}
		else
		{
			$event->getController()->notAllowedError($request->getMethod(), [$request::METHOD_GET]);
		}

		$functions = $event->getApplicationServices()->getPageManager()->getFunctions();
		usort($functions, function($a , $b) {
			$grpA = isset($a['section']) ? $a['section'] : '';
			$grpB = isset($b['section']) ? $b['section'] : '';
			if ($grpA == $grpB)
			{
				$labA =  isset($a['label']) ? $a['label'] : '';
				$labB =  isset($b['label']) ? $b['label'] : '';
				if ($labA == $labB)
				{
					return 0;
				}
				return strcmp($labA, $labB);
			}
			return strcmp($grpA, $grpB);
		});

		$result = new \Change\Http\Rest\Result\ArrayResult();
		$result->setArray($functions);
		$event->setResult($result);
	}
}
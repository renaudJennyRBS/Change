<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Event;
use Change\Http\Request;
use Zend\Http\Response as HttpResponse;

/**
* @name \Change\Http\Rest\Actions\GetCollectionItems
*/
class GetCollectionItems
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
			$result = $event->getController()->notAllowedError($request->getMethod(), array(Request::METHOD_GET));
			$event->setResult($result);
			return;
		}

		$code = $request->getQuery('code');
		if (is_string($code) && !empty($code))
		{
			$cm = $event->getApplicationServices()->getCollectionManager();
			$parameters = $request->getQuery()->toArray();
			unset($parameters['code']);
			$collection = $cm->getCollection($code, $parameters);
			if ($collection !== null)
			{
				$event->setResult($this->generateResult($collection));
			}
			else
			{
				throw new \RuntimeException('Collection "' .$code .'" not found', 999999);
			}
		}
		else
		{
			throw new \RuntimeException('Parameter "code" is required', 999999);
		}
	}

	/**
	 * @param \Change\Collection\CollectionInterface $collection
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($collection)
	{
		$array = array('code' => $collection->getCode(), 'items' => array());
		$result = new \Change\Http\Rest\Result\ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		foreach ($collection->getItems() as $item)
		{
			$array['items'][$item->getValue()] = array('label' => $item->getLabel(), 'title' => $item->getTitle());
		}

		$result->setArray($array);
		return $result;
	}
}
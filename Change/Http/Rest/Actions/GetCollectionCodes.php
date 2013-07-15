<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Event;
use Change\Http\Request;
use Zend\Http\Response as HttpResponse;

/**
* @name \Change\Http\Rest\Actions\GetCollectionCodes
*/
class GetCollectionCodes
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$request = $event->getRequest();
		if (!$request->isGet())
		{
			$resolver = $event->getController()->getActionResolver();
			if ($resolver instanceof \Change\Http\Rest\Resolver)
			{
				$resolver->buildNotAllowedError($request->getMethod(), array(Request::METHOD_GET));
			}
			return;
		}

		$cm = new \Change\Collection\CollectionManager();
		$cm->setDocumentServices($event->getDocumentServices());
		$codes = $cm->getCodes($request->getQuery()->toArray());
		$event->setResult($this->generateResult($codes));
	}

	/**
	 * @param string[] $codes
	 * @return \Change\Http\Rest\Result\ArrayResult
	 */
	protected function generateResult($codes)
	{

		$result = new \Change\Http\Rest\Result\ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$result->setArray(array('codes' => $codes));
		return $result;
	}
}
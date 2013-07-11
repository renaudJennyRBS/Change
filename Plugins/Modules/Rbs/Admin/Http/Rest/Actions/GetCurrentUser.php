<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Http\Rest\Result\DocumentResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\GetCurrentUser
 */
class GetCurrentUser
{

	/**
	 * TODO WWW-Authenticate: OAuth realm="Rbs_Admin"
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{

		$result = new DocumentResult();
		$user = $event->getAuthenticationManager()->getCurrentUser();
		$properties = array(
			'id' => $user->getId(),
			'pseudonym' => $user->getName()
		);

		$result->setProperties($properties);
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$event->setResult($result);
	}
}
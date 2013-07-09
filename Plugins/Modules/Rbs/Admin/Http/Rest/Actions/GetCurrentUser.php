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
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$user = $event->getAuthenticationManager()->getCurrentUser();
		if ($user instanceof \Rbs\User\Documents\User)
		{
			$event->setResult($this->generateResult($event, $user));
		}

	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Rbs\User\Documents\User $user
	 * @return DocumentResult
	 */
	protected function generateResult($event, $user)
	{
		$result = new DocumentResult();

		$properties = array(
			'id' => $user->getId(),
			'pseudonym' => $user->getPseudonym()
		);

		$result->setProperties($properties);
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		return $result;
	}
}
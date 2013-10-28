<?php
namespace Rbs\Media\Http\Rest\Actions;

use Change\Http\Result;
use Change\Http\Event;
use Zend\Http\Response;

/**
 * @name \Rbs\Media\Http\Rest\Actions\Avatar
 */
class Avatar
{

	public function execute(Event $event)
	{
		$size = $event->getParam('size');
		$email = $event->getParam('email');
		$userId = $event->getParam('userId');
		$params = $event->getParam('params');

		/** @var \Rbs\Generic\GenericServices $genericServices */
		$genericServices = $event->getServices('genericServices');

		$user = null;
		if ($userId !== null)
		{
			$user = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($userId);
		}

		$avatarManager = $genericServices->getAvatarManager();
		$avatarManager->setUrlManager($event->getUrlManager());
		$url = $avatarManager->getAvatarUrl($size, $email, $user, $params);

		if ($url === null || \Change\Stdlib\String::isEmpty($url))
		{
			$event->setResult(new Result(Response::STATUS_CODE_404));
			return;
		}

		$result = new \Change\Http\Rest\Result\ArrayResult();
		$result->setArray(['href' => $url]);
		$event->setResult($result);
	}
}
<?php
namespace Rbs\Social\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Social\Http\Web\AddSocialCount
*/
class AddSocialCount extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$data = $event->getRequest()->getPost()->toArray();
			$websiteId = $data['websiteId'];
			$targetId = $data['targetId'];
			$socialType = $data['socialType'];
			$key = $data['key'];

			$session = new \Zend\Session\Container('Rbs_Social');
			$sessionKey = $socialType . '_' . $websiteId . '_' . $targetId;
			if (!isset($session[$sessionKey]) || $key != $session[$sessionKey])
			{
				if ($websiteId && $targetId && $socialType && $key)
				{
					//TODO get socialManager from services?
					$socialManager = new \Rbs\Social\SocialManager();
					$socialManager->setDocumentManager($event->getApplicationServices()->getDocumentManager());
					$eventManagerFactory = new \Change\Events\EventManagerFactory($event->getApplication());
					$eventManagerFactory->addSharedService('applicationServices', $event->getApplicationServices());
					$socialManager->setEventManagerFactory($eventManagerFactory);

					$socialManager->addSocialCount($websiteId, $targetId, $socialType);
					$session[$sessionKey] = $key;
				}
				else
				{
					$data['error'] = 'Invalid parameters';
				}
			}
			$result = new \Change\Http\Web\Result\AjaxResult($data);
			$event->setResult($result);
		}
	}
}
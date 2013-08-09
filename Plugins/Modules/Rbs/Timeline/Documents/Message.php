<?php
namespace Rbs\Timeline\Documents;
use Change\Presentation\PresentationServices;

/**
 * @name \Rbs\Timeline\Documents\Message
 */
class Message extends \Compilation\Rbs\Timeline\Documents\Message
{
	protected function onCreate()
	{
		if ($this->isPropertyModified('message'))
		{
			$this->transformMarkdownToHtml();
		}
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('message'))
		{
			$this->transformMarkdownToHtml();
		}
	}

	/**
	 * //TODO only markdown for now
	 */
	private function transformMarkdownToHtml()
	{
		$ps = new PresentationServices($this->getApplicationServices());
		$ps->getRichTextManager()->setDocumentServices($this->getDocumentServices())->render($this->getMessage(), 'Admin');
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('updateRestResult', function(\Change\Documents\Events\Event $event) {
			$result = $event->getParam('restResult');
			if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
			{
				/* @var $message \Rbs\Timeline\Documents\Message */
				$message = $event->getDocument();
				//For avatar
				$dm = $message->getDocumentManager();
				$user = $dm->getDocumentInstance($message->getAuthorId(), $dm->getModelManager()->getModelByName('Rbs_User_User'));

				//TODO hardcoded value for default avatar url
				$avatar = 'Rbs/Admin/img/user-default.png';
				if ($user)
				{
					/* @var $user \Rbs\User\Documents\User */
					$profile = $user->getMeta('profile_Rbs_Admin');
					if (isset($profile['avatar']) && $profile['avatar'] !== null)
					{
						$avatar = $profile['avatar'];
					}
				}
				$result->setProperty('avatar', $avatar);
			}
			else if ($result instanceof \Change\Http\Rest\Result\DocumentLink)
			{
				/* @var $message \Rbs\Timeline\Documents\Message */
				//Add the message
				$message = $event->getDocument();
				$result->setProperty('message', $message->getMessage());
				//Add AuthorName and AuthorId
				$result->setProperty('authorId', $message->getAuthorId());
				$result->setProperty('authorName', $message->getAuthorName());
				$result->setProperty('authorResumeLink', 'Rbs/Timeline/Resume/' . $message->getAuthorId());
				//For avatar & identifier
				$dm = $message->getDocumentManager();
				$user = $dm->getDocumentInstance($message->getAuthorId(), $dm->getModelManager()->getModelByName('Rbs_User_User'));

				//TODO hardcoded value for default avatar url
				$avatar = 'Rbs/Admin/img/user-default.png';
				if ($user)
				{
					/* @var $user \Rbs\User\Documents\User */
					$profile = $user->getMeta('profile_Rbs_Admin');
					if (isset($profile['avatar']) && $profile['avatar'] !== null)
					{
						$avatar = $profile['avatar'];
					}
					if($user->getIdentifier())
					{
						$result->setProperty('authorIdentifier', $user->getIdentifier());
					}
				}
				$result->setProperty('avatar', $avatar);
			}
		}, 5);
	}
}

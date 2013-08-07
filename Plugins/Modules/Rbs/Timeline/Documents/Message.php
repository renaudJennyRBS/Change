<?php
namespace Rbs\Timeline\Documents;
use Change\Presentation\Markdown\MarkdownParser;

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
		$mdParser = new MarkdownParser($this->getDocumentManager()->getDocumentServices());
		$this->getMessage()->setHtml($mdParser->transform($this->getMessage()->getRawText()));
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
		}, 5);
	}
}

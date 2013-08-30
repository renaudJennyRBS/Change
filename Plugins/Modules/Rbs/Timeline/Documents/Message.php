<?php
namespace Rbs\Timeline\Documents;
use Change\Presentation\PresentationServices;
use Change\User\ProfileManager;
use Rbs\User\Events\AuthenticatedUser;

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
		$this->notifyTargetedUsers();
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('message'))
		{
			$this->transformMarkdownToHtml();
		}
		$this->notifyTargetedUsers();
	}

	/**
	 * //TODO only markdown for now
	 */
	private function transformMarkdownToHtml()
	{
		$ps = new PresentationServices($this->getApplicationServices());
		$ps->getRichTextManager()->setDocumentServices($this->getDocumentServices())->render($this->getMessage(), 'Admin');
	}

	protected function notifyTargetedUsers()
	{
		//first find targeted users by their identifiers
		$matches = [];
		preg_match_all('/\B(@\+?)([a-z0-9_\-]+)/i', $this->getMessage()->getRawText(), $matches, PREG_SET_ORDER);
		$userIdentifiers = [];
		$groupIdentifiers = [];
		foreach($matches as $match)
		{
			if ($match[1] === '@')
			{
				$userIdentifiers[] = $match[2];
			}
			else if($match[1] === '@+')
			{
				$groupIdentifiers[] = $match[2];
			}
		}
		//now get user from user identifiers and send a mail
		foreach($userIdentifiers as $userIdentifier)
		{
			$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_User_User');
			$user = $dqb->andPredicates($dqb->eq('identifier', $userIdentifier))->getFirstDocument();
			if ($user)
			{
				/* @var $user \Rbs\User\Documents\User */
				$params = [
					'documentLabel' => $this->getContextIdInstance()->getLabel(),
					'authorName' => $this->getAuthorName(),
					'message' => $this->getMessage()->getRawText()
				];
				$jm = new \Change\Job\JobManager();
				$jm->setApplicationServices($this->getApplicationServices());
				$arguments = [
					'params' => $params,
					'to' => [$user->getEmail()],
					'templateCode' => 'timeline_mention_notification'
				];
				$jm->createNewJob('Rbs_Timeline_SendTemplateMail', $arguments);
			}
		}
		//TODO: do the same things for user group
	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $documentResult
	 */
	protected function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);
		/* @var $message \Rbs\Timeline\Documents\Message */
		$message = $documentResult->getDocument();
		//For avatar
		$dm = $message->getDocumentManager();
		$user = $dm->getDocumentInstance($message->getAuthorId(), $dm->getModelManager()->getModelByName('Rbs_User_User'));

		//FIXME hardcoded value for default avatar url
		$avatar = 'Rbs/Admin/img/user-default.png';
		if ($user)
		{
			/* @var $user \Rbs\User\Documents\User */
			$pm = new ProfileManager();
			$pm->setDocumentServices($message->getDocumentServices());
			$authenticatedUser = new AuthenticatedUser($user);
			$profile = $pm->loadProfile($authenticatedUser, 'Rbs_Admin');
			if ($profile && $profile->getPropertyValue('avatar'))
			{
				$avatar = $profile->getPropertyValue('avatar');
			}
		}
		$documentResult->setProperty('avatar', $avatar);
	}

	/**
	 * @param \Change\Http\Rest\Result\DocumentLink $documentLink
	 * @param $extraColumn
	 */
	protected function updateRestDocumentLink($documentLink, $extraColumn)
	{
		parent::updateRestDocumentLink($documentLink, $extraColumn);
		/* @var $message \Rbs\Timeline\Documents\Message */
		//Add the message
		$message = $documentLink->getDocument();
		$documentLink->setProperty('message', $message->getMessage());
		//Add AuthorName and AuthorId
		$documentLink->setProperty('authorId', $message->getAuthorId());
		$documentLink->setProperty('authorName', $message->getAuthorName());
		//Add contextModel if document exist
		$contextDocument = $message->getContextIdInstance();
		if ($contextDocument)
		{
			/* @var $contextDocument \Change\Documents\AbstractDocument */
			$documentLink->setProperty('contextModel', $contextDocument->getDocumentModelName());
		}
		//For avatar & identifier
		$dm = $message->getDocumentManager();
		$user = $dm->getDocumentInstance($message->getAuthorId(), $dm->getModelManager()->getModelByName('Rbs_User_User'));

		//FIXME hardcoded value for default avatar url
		$avatar = 'Rbs/Admin/img/user-default.png';
		if ($user)
		{
			/* @var $user \Rbs\User\Documents\User */
			$pm = new ProfileManager();
			$pm->setDocumentServices($message->getDocumentServices());
			$authenticatedUser = new AuthenticatedUser($user);
			$profile = $pm->loadProfile($authenticatedUser, 'Rbs_Admin');
			if ($profile && $profile->getPropertyValue('avatar'))
			{
				$avatar = $profile->getPropertyValue('avatar');
			}
			if($user->getIdentifier())
			{
				$documentLink->setProperty('authorIdentifier', $user->getIdentifier());
			}
		}
		$documentLink->setProperty('avatar', $avatar);
	}
}

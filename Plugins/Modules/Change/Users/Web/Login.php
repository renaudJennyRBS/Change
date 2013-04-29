<?php
namespace Change\Users\Web;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;
use Change\Documents\DocumentManager;
use Change\Documents\Interfaces\Publishable;

/**
* @name \Change\Users\Events\Login
*/
class Login
{
	/**
	 * @param Event $event
	 */
	public static function executeByName(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$a = new self();
			$a->authenticate($event);
		}
	}

	public function execute(Event $event)
	{
		$request = $event->getRequest();
		if ($request->getMethod() === 'POST')
		{
			if (strpos($request->getPath(), 'Action/Change/Users/HttpLogin') !== false)
			{
				$this->authenticate($event);
			}
		}
	}

	public function authenticate(Event $event)
	{
		$website = $event->getParam('website');
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			$datas = $event->getRequest()->getPost()->toArray();
			$realm = $datas['realm'];
			$login = $datas['login'];
			$password = $datas['password'];
			if ($realm && $login && $password)
			{
				$accessorId = $this->findAccessorId($realm, $login, $password, $event->getDocumentServices()->getDocumentManager());
				if ($accessorId)
				{
					$authentication = new \Change\Http\Web\Authentication();
					$authentication->save($website,$accessorId);
					$event->setAuthentication($authentication);
					$datas = array('accessorId' => $accessorId);
					$user = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($accessorId);
					if ($user instanceof \Change\Users\Documents\User)
					{
						$datas['pseudonym'] = $user->getPseudonym();
						$datas['email'] = $user->getEmail();
					}
				}
				else
				{
					$datas['error'] = 'Unable to Authenticate';
				}
			}
			else
			{
				$datas['error'] = 'Invalid parameters';
			}
		}
		else
		{
			$datas = array('error' => 'Invalid website');
		}

		$result = new \Change\Http\Web\Result\AjaxResult($datas);
		$event->setResult($result);
	}


	/**
	 * @param string $realm
	 * @param string $login
	 * @param string $password
	 * @param DocumentManager $documentManager
	 * @return integer|null
	 */
	protected function findAccessorId($realm, $login, $password, DocumentManager $documentManager)
	{
		$dbProvider = $documentManager->getApplicationServices()->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$gtb = $fb->getDocumentTable('Change_Users_Group');
		$utb = $fb->getDocumentTable('Change_Users_User');
		$rtb = $fb->getDocumentRelationTable('Change_Users_User');

		$sq = $qb->select()
			->addColumn($fb->alias($fb->getDocumentColumn('id', $utb), 'id'))
			->addColumn($fb->alias($fb->getDocumentColumn('model', $utb), 'model'))
			->from($utb)
			->innerJoin($rtb, $fb->eq($fb->getDocumentColumn('id', $utb), $fb->getDocumentColumn('id', $rtb)))
			->innerJoin($gtb, $fb->eq($fb->getDocumentColumn('id', $gtb), $fb->column('relatedid', $rtb)))
			->where(
				$fb->logicAnd(
					$fb->eq($fb->column('realm', $gtb), $fb->parameter('realm', $qb)),
					$fb->eq($fb->getDocumentColumn('login', $utb), $fb->parameter('login', $qb)),
					$fb->eq($fb->getDocumentColumn('publicationStatus', $utb), $fb->string(Publishable::STATUS_PUBLISHABLE))
				)
			)
			->query();
		$sq->bindParameter('realm', $realm);
		$sq->bindParameter('login', $login);

		$collection = new \Change\Documents\DocumentCollection($documentManager, $sq->getResults());
		foreach ($collection as $document)
		{
			if ($document instanceof \Change\Users\Documents\User)
			{
				if ($document->getPublishableFunctions()->published() && $document->checkPassword($password))
				{
					return $document->getId();
				}
			}
		}
		return null;
	}
}
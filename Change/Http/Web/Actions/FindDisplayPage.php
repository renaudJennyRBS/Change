<?php
namespace Change\Http\Web\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\Events\Event as DocumentEvent;
use Change\Http\Web\Event;
use Change\Http\Web\PathRule;
use Change\Presentation\Interfaces\Page;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Actions\FindDisplayPage
 */
class FindDisplayPage
{
	/**
	 * Use Required Event Params: pathRule
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		/* @var $pathRule PathRule */
		$pathRule = $event->getParam('pathRule');
		if (!($pathRule instanceof PathRule))
		{
			throw new \RuntimeException('Invalid Parameter: pathRule', 71000);
		}

		if ($pathRule->getQuery())
		{
			$requestQuery = $event->getRequest()->getQuery();
			$requestQuery->fromArray(\Zend\Stdlib\ArrayUtils::merge($pathRule->getQueryParameters(), $requestQuery->toArray()));
		}

		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($pathRule->getDocumentId());
		if ($document instanceof AbstractDocument)
		{
			$eventManager = $document->getEventManager();
			$e = new DocumentEvent(DocumentEvent::EVENT_DISPLAY_PAGE, $document, array('pathRule' => $pathRule));
			$result = $eventManager->trigger($e, function ($return)
			{
				return ($return instanceof Page);
			});
			$page = null;

			if ($result->stopped())
			{
				$page = $result->last();
			}

			if (!($page instanceof Page))
			{
				$page = $e->getParam('page');
			}
			$event->setParam('page', $page);
		}
	}
}

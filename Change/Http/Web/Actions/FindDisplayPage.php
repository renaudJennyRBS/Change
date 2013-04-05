<?php
namespace Change\Http\Web\Actions;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Http\Event;
use Change\Http\Web\PathRule;
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

		$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($pathRule->getDocumentId());
		if ($document)
		{
			$eventManager = $document->getEventManager();
			$eventManager->attach(DocumentEvent::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 5);

			$e = new DocumentEvent(DocumentEvent::EVENT_DISPLAY_PAGE, $document, array('pathRule' => $pathRule));
			$result = $eventManager->trigger($e, function ($return)
			{
				return ($return instanceof \Change\Website\Documents\Page);
			});

			if ($result->stopped())
			{
				$page = $result->last();
			}
			else
			{
				$page = $e->getParam('page');
			}
			$event->setParam('page', $page);
		}
	}

	/**
	 * @param DocumentEvent $event
	 * @return \Change\Website\Documents\Page
	 */
	public function onDocumentDisplayPage($event)
	{
		if ($event instanceof DocumentEvent)
		{
			$document = $event->getDocument();
			if ($document instanceof \Change\Website\Documents\Page)
			{
				$event->setParam('page', $document);
			}
			elseif ($document instanceof \Change\Website\Documents\Section)
			{
				$event->setParam('page', $document->getIndexPage());
			}
		}
	}
}

<?php
namespace Rbs\Media\Http\Web;

use Rbs\Media\Http\Web\Actions\GetImagestorageItemContent;
use Zend\EventManager\EventManagerInterface;

/**
 * @name \Rbs\Media\Http\Web\ListenerAggregate
 */
class ListenerAggregate implements \Zend\EventManager\ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$events->attach(\Change\Http\Event::EVENT_ACTION, array($this, 'registerActions'));
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 */
	public function registerActions(\Change\Http\Web\Event $event)
	{
		if (!$event->getAction())
		{
			$relativePath = $event->getParam('relativePath');
			if (preg_match('/^Imagestorage\/([A-Za-z0-9]+)\/([0-9]+)\/([0-9]+)(\/.+)$/', $relativePath, $matches))
			{
				$storageName = $matches [1];
				$maxWidth = intval($matches[2]);
				$maxHeight = intval($matches[3]);
				$path = $matches[4];

				$originalURI = $event->getApplicationServices()->getStorageManager()->buildChangeURI($storageName, $path);
				$changeURI = $event->getApplicationServices()->getStorageManager()->buildChangeURI($storageName, $path, array('max-width' => $maxWidth, 'max-height' => $maxHeight));

				$event->setParam('originalURI', $originalURI);
				$event->setParam('changeURI', $changeURI);
				$event->setParam('maxWidth', $maxWidth);
				$event->setParam('maxHeight', $maxHeight);
				$action = function($event) {
					$action = new GetImagestorageItemContent();
					$action->execute($event);
				};
				$event->setAction($action);
				return;
			}
		}
	}
}
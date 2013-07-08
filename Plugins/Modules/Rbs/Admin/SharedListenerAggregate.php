<?php
namespace Rbs\Admin;

use Zend\EventManager\SharedEventManagerInterface;

/**
 * @name \Rbs\Admin\RegisterListenerAggregate
 */
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{

	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$callback = function ($event)
		{
			/* @var $event \Change\Documents\Events\Event */

			/* @var $image \Rbs\Media\Documents\Image */
			$image = $event->getDocument();
			$publicURL = $image->getDocumentServices()->getApplicationServices()
				->getStorageManager()->getPublicURL($image->getPath());

			/* @var $restResult \Change\Http\Rest\Result\DocumentResult */
			$restResult = $event->getParam('restResult');
			$restResult->setProperty('publicURL' , $publicURL);
		};
		$events->attach('Rbs_Media_Image', 'updateRestResult', $callback);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		// TODO: Implement detachShared() method.
	}
}
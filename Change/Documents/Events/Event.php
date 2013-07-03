<?php
namespace Change\Documents\Events;

/**
 * @name \Change\Documents\Events\Event
 */
class Event extends \Zend\EventManager\Event
{
    const EVENT_LOADED      	= 'documents.loaded';
	const EVENT_CREATE      	= 'documents.create';
	const EVENT_CREATED      	= 'documents.created';
	const EVENT_UPDATE       	= 'documents.update';
	const EVENT_UPDATED       	= 'documents.updated';
	const EVENT_DELETE       	= 'documents.delete';
	const EVENT_DELETED       	= 'documents.deleted';

	const EVENT_DISPLAY_PAGE    = 'http.web.displayPage';

	/**
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractDocument
	 */
	public function getDocument()
	{
		if ($this->getTarget() instanceof \Change\Documents\AbstractDocument)
		{
			return $this->getTarget();
		}
		throw new \RuntimeException('Invalid document instance', 50000);
	}
}
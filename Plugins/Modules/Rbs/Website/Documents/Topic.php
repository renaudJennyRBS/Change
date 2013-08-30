<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Website\Documents\Topic
 */
class Topic extends \Compilation\Rbs\Website\Documents\Topic
{
	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		return array($this);
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('populatePathRule', array($this, 'onPopulatePathRule'), 5);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onPopulatePathRule(\Change\Documents\Events\Event $event)
	{
		/* @var $pathRule \Change\Http\Web\PathRule */
		$pathRule = $event->getParam('pathRule');
		if ($this->getPathPart())
		{
			$pathRule->setRelativePath($this->getPathPart() . '.' . $this->getId() . '/');
		}
	}
}
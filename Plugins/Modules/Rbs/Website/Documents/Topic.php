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

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onPopulatePathRule(\Change\Documents\Events\Event $event)
	{
		parent::onPopulatePathRule($event);
		/* @var $pathRule \Change\Http\Web\PathRule */
		$pathRule = $event->getParam('pathRule');
		if (!$pathRule->getRelativePath())
		{
			if ($this->getPathPart())
			{
				$pathRule->setRelativePath($this->getPathPart() . '/');
			}
			else
			{
				$pathRule->setRelativePath($pathRule->normalizePath($this->getTitle()) . '/');
			}
		}
	}
}
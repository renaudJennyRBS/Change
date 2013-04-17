<?php
namespace Change\Website\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Http\Web\Result\BlockResult;

/**
 * TODO Sample
 * @package \Change\Website\Blocks\Richtext
 */
class Richtext
{
	/**
	 * @param Event $event
	 */
	public function onConfiguration($event)
	{
		$parameters = new \Change\Presentation\Blocks\Parameters('Change_Website_Richtext');
		$parameters->addParameterMeta('content', \Change\Documents\Property::TYPE_LONGSTRING);
		$parameters->addParameterMeta('contentType', \Change\Documents\Property::TYPE_STRING, true, 'bbcode');
		$parameters->setUpdatedParametersValue($event->getBlockLayout()->getParameters());
		$event->setBlockParameters($parameters);
	}

	/**
	 * @param Event $event
	 */
	public function onExecute($event)
	{
		$blockLayout = $event->getBlockLayout();
		$result = new BlockResult($blockLayout->getId(), $blockLayout->getName());
		$result->setHtml($event->getBlockParameters()->getContent());
		$event->setBlockResult($result);
	}
}
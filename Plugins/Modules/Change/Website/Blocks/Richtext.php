<?php
namespace Change\Website\Blocks;

use Change\Http\Web\Blocks\Event;
use Change\Http\Web\Blocks\Result;

/**
 * @package \Change\Website\Blocks\Richtext
 */
class Richtext
{
	/**
	 * @param Event $event
	 */
	public function onConfiguration($event)
	{
		$parameters = new \Change\Http\Web\Blocks\Parameters('Change_Website_Richtext');
		$parameters->addParameterMeta('content', \Change\Documents\Property::TYPE_LONGSTRING);
		$parameters->addParameterMeta('contentType', \Change\Documents\Property::TYPE_STRING, true, 'bbcode');
		if (($blockLayout = $event->getBlockLayout()) !== null)
		{
			$parameters->setUpdatedParametersValue($blockLayout->getParameters());
		}
		$event->setBlockParameters($parameters);
	}

	/**
	 * @param Event $event
	 */
	public function onExecute($event)
	{
		$result = new Result();
		$result->setHtml($event->getBlockParameters()->getContent());
		$event->setBlockResult($result);
	}
}
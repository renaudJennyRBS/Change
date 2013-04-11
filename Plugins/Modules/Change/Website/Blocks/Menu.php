<?php
namespace Change\Website\Blocks;

use Change\Http\Web\Blocks\Event;
use Change\Http\Web\Blocks\Result;

/**
 * @package \Change\Website\Blocks\Menu
 */
class Menu
{

	/**
	 * @param Event $event
	 */
	public function onConfiguration($event)
	{
		$parameters = new \Change\Http\Web\Blocks\Parameters('Change_Website_Menu');
		$parameters->addParameterMeta('documentId', \Change\Documents\Property::TYPE_DOCUMENT);
		$parameters->addParameterMeta('maxLevel', \Change\Documents\Property::TYPE_INTEGER, true, 1);
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
		$result->setHtml('<h1>Menu: ' . $event->getBlockParameters()->getDocumentId() . '</h1>');
		$result->addHeadAsString('<meta name="description" content="Menu" />');
		$event->setBlockResult($result);
	}
}
<?php
namespace Change\Website\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Http\Web\Result\BlockResult;

/**
 * TODO Sample
 * @package \Change\Website\Blocks\Menu
 */
class Menu
{
	/**
	 * @param Event $event
	 */
	public function onConfiguration($event)
	{
		$parameters = new \Change\Presentation\Blocks\Parameters('Change_Website_Menu');
		$parameters->addParameterMeta('documentId', \Change\Documents\Property::TYPE_DOCUMENT);
		$parameters->addParameterMeta('maxLevel', \Change\Documents\Property::TYPE_INTEGER, true, 1);
		$parameters->setUpdatedParametersValue($event->getBlockLayout()->getParameters());

		$request = $event->getHttpRequest();
		if ($request)
		{
			//TODO Fill request parameters
		}
		$event->setBlockParameters($parameters);
	}

	/**
	 * @param Event $event
	 */
	public function onExecute($event)
	{
		$blockLayout = $event->getBlockLayout();
		$result = new BlockResult($blockLayout->getId(), $blockLayout->getName());
		$templatePath = $event->getPresentationServices()->getThemeManager()->getCurrent()->getBlocTemplatePath('Change_Website', 'menu.twig');
		$attributes = array('parameters' => $event->getBlockParameters());
		$templateManager = $event->getPresentationServices()->getTemplateManager();
		$callback = function () use ($templateManager, $templatePath, $attributes)
		{
			return $templateManager->renderTemplateFile($templatePath, $attributes);
		};
		$result->setHtmlCallback($callback);
		$result->addHeadAsString('<meta name="description" content="Menu" />');
		$event->setBlockResult($result);
	}
}
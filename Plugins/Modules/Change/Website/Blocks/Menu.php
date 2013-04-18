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
		$parameters->setLayoutParameters($event->getBlockLayout());

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
		$parameters = $event->getBlockParameters();
		$attributes = array('parameters' => $event->getBlockParameters());


		$templateName = $this->execute($parameters, $attributes, $header);

		$result->addHeads($header);
		$templatePath = $event->getPresentationServices()->getThemeManager()->getCurrent()->getBlocTemplatePath('Change_Website', $templateName);
		$templateManager = $event->getPresentationServices()->getTemplateManager();
		$callback = function () use ($templateManager, $templatePath, $attributes)
		{
			return $templateManager->renderTemplateFile($templatePath, $attributes);
		};
		$result->setHtmlCallback($callback);
		$event->setBlockResult($result);
	}

	/**
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @param array $attributes
	 * @param array $header
	 * @return string
	 */
	protected function execute($parameters, &$attributes, &$header)
	{
		$attributes['test'] = 'plop plob plop';
		$templateName = 'menu.twig';
		$header[] = '<meta name="description" content="AMenu" />';
		return $templateName;
	}
}
<?php
namespace Change\Presentation\Blocks\Standard;

use Change\Presentation\Blocks\Event;
use Change\Http\Web\Result\BlockResult;
use Change\Presentation\Blocks\Parameters;

/**
 * @api
 * Class Block
 * @package Change\Presentation\Blocks\Standard
 * @name \Change\Presentation\Blocks\Standard\Block
 */
class Block
{
	/**
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = new Parameters($event->getBlockLayout()->getName());
		return $parameters;
	}

	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getPresentationServices, getDocumentServices
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	public function onParameterize($event)
	{
		$parameters = $event->getBlockParameters();
		if (!($parameters instanceof Parameters))
		{
			$parameters = $this->parameterize($event);
			$event->setBlockParameters($parameters);
		}
	}

	/**
	 * @var string
	 */
	protected $templateModuleName;

	/**
	 * @param string $templateModuleName
	 */
	public function setTemplateModuleName($templateModuleName)
	{
		$this->templateModuleName = $templateModuleName;
	}

	/**
	 * @return string
	 */
	public function getTemplateModuleName()
	{
		return $this->templateModuleName;
	}

	/**
	 * @param Event $event
	 */
	public function onExecute($event)
	{
		$blockLayout = $event->getBlockLayout();
		$result = new BlockResult($blockLayout->getId(), $blockLayout->getName());
		$event->setBlockResult($result);

		$attributes = new  \ArrayObject(array('parameters' => $event->getBlockParameters()));
		$templateName = $this->execute($event, $attributes);

		if (is_string($templateName) && !$result->getHtmlCallback())
		{
			$presentationServices = $event->getPresentationServices();
			$templateModuleName = $this->getTemplateModuleName();
			if ($templateModuleName === null)
			{
				$sn = explode('_', $blockLayout->getName());
				$templateModuleName = $sn[0] . '_' . $sn[1];
			}
			$this->setTemplateRenderer($presentationServices, $result, $attributes->getArrayCopy(), $templateModuleName, $templateName);
		}
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		return null;
	}

	/**
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @param BlockResult $result
	 * @param array $attributes
	 * @param string $templateModuleName
	 * @param string $templateName
	 */
	protected function setTemplateRenderer($presentationServices, $result, $attributes, $templateModuleName, $templateName)
	{
		$templatePath = $presentationServices->getThemeManager()->getCurrent()
			->getBlockTemplatePath($templateModuleName, $templateName);
		$templateManager = $presentationServices->getTemplateManager();
		$callback = function () use ($templateManager, $templatePath, $attributes)
		{
			return $templateManager->renderTemplateFile($templatePath, $attributes);
		};
		$result->setHtmlCallback($callback);
	}
}
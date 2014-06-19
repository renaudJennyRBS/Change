<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Blocks\Standard;

use Change\Http\Web\Result\BlockResult;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Parameters;

/**
 * @api
 * @name \Change\Presentation\Blocks\Standard\Block
 */
class Block
{
	const DOCUMENT_TO_DISPLAY_PROPERTY_NAME = 'toDisplayDocumentId';

	const FULLY_QUALIFIED_TEMPLATE_PROPERTY_NAME = 'fullyQualifiedTemplateName';

	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = new Parameters($event->getBlockLayout()->getName());
		return $parameters;
	}

	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param \Change\Presentation\Blocks\Event $event
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
	 * @api
	 * Set the parameter to define the document id that must be displayed and check it's possible to display it.
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function setParameterValueForDetailBlock($parameters, $event)
	{
		if ($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME) === null)
		{
			$document = $event->getParam('document');
			if ($this->isValidDocument($document))
			{
				$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $document->getId());
			}
		}
		else
		{
			$document = $event->getApplicationServices()->getDocumentManager()
				->getDocumentInstance($parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME));
			if (!$this->isValidDocument($document))
			{
				$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, null);
			}
		}
		return $parameters;
	}

	/**
	 * @api
	 * Must be implemented in the final block class
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		return false;
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
	 * @param \Change\Presentation\Blocks\Event $event
	 */
	public function onExecute($event)
	{
		$blockLayout = $event->getBlockLayout();
		$result = new BlockResult($blockLayout->getId(), $blockLayout->getName());
		$event->setBlockResult($result);
		$attributes = $event->getParam('attributes', new \ArrayObject());
		$event->setParam('templateName', $this->execute($event, $attributes));
		$event->setParam('templateModuleName', $this->getTemplateModuleName());
		$fullyQualifiedTemplateName = $event->getBlockParameters()->getParameter(static::FULLY_QUALIFIED_TEMPLATE_PROPERTY_NAME);
		if (is_string($fullyQualifiedTemplateName) && strpos($fullyQualifiedTemplateName, ':'))
		{
			$parts = explode(':', $fullyQualifiedTemplateName);
			$event->setParam('templateModuleName', $parts[0]);
			$event->setParam('templateName', $parts[1]);
		}
	}

	/**
	 * @api
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout(), getBlockParameters(), getBlockResult(),
	 *        getApplication, getApplicationServices, getServices, getUrlManager()
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		return null;
	}

	/**
	 * @param integer $pageNumber
	 * @param integer $pageCount
	 * @return integer
	 */
	protected function fixPageNumber($pageNumber, $pageCount)
	{
		if (!is_numeric($pageNumber) || $pageNumber < 1 || $pageNumber > $pageCount)
		{
			return 1;
		}
		return $pageNumber;
	}
}
<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Blocks;

/**
* @name \Change\Presentation\Blocks\TemplateInformation
*/
class TemplateInformation
{
	/**
	 * @var string
	 */
	protected $moduleName;

	/**
	 * @var string
	 */
	protected $templateName;

	/**
	 * @var ParameterInformation[]
	 */
	protected $parametersInformation = array();

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @param string $moduleName
	 * @param string $templateName
	 */
	function __construct($moduleName, $templateName = null)
	{
		if (strpos($moduleName, ':'))
		{
			$this->setFullyQualifiedTemplateName($moduleName);
		}
		else
		{
			$this->setModuleName($moduleName);
			$this->setTemplateName($templateName);
		}
	}

	/**
	 * @param string $moduleName
	 * @return $this
	 */
	public function setModuleName($moduleName)
	{
		$this->moduleName = $moduleName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getModuleName()
	{
		return $this->moduleName;
	}

	/**
	 * @param string $templateName
	 * @return $this
	 */
	public function setTemplateName($templateName)
	{
		$this->templateName = $templateName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateName()
	{
		return $this->templateName;
	}

	/**
	 * @param string $fullyQualifiedTemplateName
	 * @return $this
	 */
	public function setFullyQualifiedTemplateName($fullyQualifiedTemplateName)
	{
		$parts = explode(':', $fullyQualifiedTemplateName);
		if (count($parts) == 2)
		{
			$this->setModuleName($parts[0]);
			$this->setTemplateName($parts[1]);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFullyQualifiedTemplateName()
	{
		return $this->getModuleName() . ':' . $this->getTemplateName();
	}

	/**
	 * @param string $name
	 * @param string $type
	 * @param bool $required
	 * @param mixed $defaultValue
	 * @return ParameterInformation
	 */
	public function addParameterInformation($name, $type = \Change\Documents\Property::TYPE_STRING, $required = false, $defaultValue = null)
	{
		$parameterInformation = new ParameterInformation($name, $type, $required, $defaultValue);
		$this->parametersInformation[$name] = $parameterInformation;
		return $parameterInformation;
	}

	/**
	 * @param \Change\Presentation\Blocks\ParameterInformation[] $parametersInformation
	 * @return $this
	 */
	public function setParametersInformation($parametersInformation)
	{
		$this->parametersInformation = $parametersInformation;
		return $this;
	}

	/**
	 * @return \Change\Presentation\Blocks\ParameterInformation[]
	 */
	public function getParametersInformation()
	{
		return array_values($this->parametersInformation);
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array('fullyQualifiedTemplateName' => $this->getFullyQualifiedTemplateName(), 'label' => $this->getLabel());
		$parameters = array();
		foreach($this->getParametersInformation() as $parameterInformation)
		{
			$parameters[] = $parameterInformation->toArray();
		}
		$array['parameters'] = $parameters;
		return $array;
	}
} 
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Presentation\Twig;

/**
 * @name \Rbs\Admin\Presentation\Twig\Extension
 */
class Extension implements \Twig_ExtensionInterface
{
	/**
	 * @var \Change\I18n\I18nManager
	 */
	protected $i18nManager;

	/**
	 * @var \Change\Documents\ModelManager
	 */
	protected $modelManager;

	/**
	 * @var \Rbs\Admin\AdminManager
	 */
	protected $adminManager;

	/**
	 * @param \Rbs\Admin\AdminManager $adminManager
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @param \Change\Documents\ModelManager $modelManager
	 */
	function __construct(\Rbs\Admin\AdminManager $adminManager = null, \Change\I18n\I18nManager $i18nManager = null, \Change\Documents\ModelManager $modelManager = null)
	{
		$this->i18nManager = $i18nManager;
		$this->modelManager = $modelManager;
		$this->adminManager = $adminManager;
	}

	/**
	 * Returns the name of the extension.
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'Rbs_Admin';
	}

	/**
	 * Initializes the runtime environment.
	 * This is where you can load some file that contains filter functions for instance.
	 * @param \Twig_Environment $environment The current Twig_Environment instance
	 */
	public function initRuntime(\Twig_Environment $environment)
	{
	}

	/**
	 * Returns the token parser instances to add to the existing list.
	 * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
	 */
	public function getTokenParsers()
	{
		return array();
	}

	/**
	 * Returns the node visitor instances to add to the existing list.
	 * @return array An array of Twig_NodeVisitorInterface instances
	 */
	public function getNodeVisitors()
	{
		return array();
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('snakeCase', array($this, 'snakeCase'))
		);
	}

	/**
	 * Returns a list of tests to add to the existing list.
	 * @return array An array of tests
	 */
	public function getTests()
	{
		return array();
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('createLinks', array($this, 'createLinks'), array('is_safe' => array('html'))),
			new \Twig_SimpleFunction('propertyKey', array($this, 'propertyKey')),
			new \Twig_SimpleFunction('modelKey', array($this, 'modelKey')),
			new \Twig_SimpleFunction('namedURL', array($this, 'namedURL'))
		);
	}

	/**
	 * Returns a list of operators to add to the existing list.
	 * @return array An array of operators
	 */
	public function getOperators()
	{
		return array();
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		return array();
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return $this
	 */
	public function setI18nManager($i18nManager)
	{
		$this->i18nManager = $i18nManager;
		return $this;
	}

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	/**
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return $this
	 */
	public function setModelManager($modelManager)
	{
		$this->modelManager = $modelManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\ModelManager
	 */
	protected function getModelManager()
	{
		return $this->modelManager;
	}

	/**
	 * @param string $string
	 * @param string $separator
	 * @return string
	 */
	public function snakeCase($string, $separator = '_')
	{
		if (is_string($string) && is_string($separator))
		{
			$string = preg_replace('/([a-z0-9])([A-Z])/', '$1' . $separator . '$2', $string);
			return preg_replace('/[^a-z0-9]/', $separator, strtolower($string));
		}
		return $string;
	}

	/**
	 * @param $modelName
	 * @return string
	 */
	public function createLinks($modelName)
	{
		$modelManager = $this->getModelManager();
		$model = $modelManager->getModelByName($modelName);
		if (!$model)
		{
			return null;
		}

		$models = array($model);
		foreach ($model->getDescendantsNames() as $descendantsName)
		{
			$models[] = $modelManager->getModelByName($descendantsName);
		}
		$links = array();
		$i18nManager = $this->getI18nManager();

		/* @var $lm \Change\Documents\AbstractModel */
		foreach ($models as $lm)
		{
			if (!$lm->isAbstract())
			{
				$titleKey = strtolower(implode('.',
					array('m', $lm->getVendorName(), $lm->getShortModuleName(), 'admin', $lm->getShortName() . '_create')));
				$link =
					'<a href ng-href="(= \'' . $lm->getName() . '\' | rbsURL:\'new\' =)">' . $i18nManager->trans($titleKey,
						array('html', 'ucf')) . '</a>';
				$links[] = $link;
			}
		}
		return implode(' ', $links);
	}

	/**
	 * Get the property translation key for label
	 * @param null|string $modelName
	 * @param null|string $propertyName
	 * @param null|string $suffix
	 * @return null|string
	 */
	public function propertyKey($modelName = null, $propertyName = null, $suffix = null)
	{
		$mm = $this->getModelManager();
		if ($modelName)
		{
			$model = $mm->getModelByName($modelName);
			if ($model && $model->hasProperty($propertyName))
			{
				$key = $model->getPropertyLabelKey($propertyName);
				return $suffix ? $key . '-' . $suffix : $key;
			}
		}
		return $propertyName;
	}

	/**
	 * @param null|string $modelName
	 * @param null|string $suffix
	 * @return null|string
	 */
	public function modelKey($modelName = null, $suffix = null)
	{
		$mm = $this->getModelManager();
		if ($modelName)
		{
			$model = $mm->getModelByName($modelName);
			if ($model)
			{
				$key = $model->getLabelKey();
				return $suffix ? $key . '-' . $suffix : $key;
			}
		}
		return $modelName;
	}

	/**
	 * @param string $model
	 * @param string $name
	 * @return string|null
	 */
	public function namedURL($model, $name)
	{
		$route = $this->adminManager->getNamedRoute($model, $name);
		if (is_array($route))
		{
			if (strpos($route['path'], '/:'))
			{
				return null;
			}
			$path = $route['path'];
			if (isset($route['rule']['redirectTo']))
			{
				$path = $route['rule']['redirectTo'];
			}
			return substr($path, 1);
		}
		return null;
	}
}
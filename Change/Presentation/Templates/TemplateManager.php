<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Templates;

/**
 * @api
 * @name \Change\Presentation\Templates\TemplateManager
 */
class TemplateManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const DEFAULT_IDENTIFIER = 'TemplateManager';
	const EVENT_REGISTER_EXTENSIONS = 'registerExtensions';

	/**
	 * @var string
	 */
	protected $cachePath;

	/**
	 * @var \Twig_ExtensionInterface[]
	 */
	protected $extensions;

	/**
	 * @var \Change\Presentation\Themes\ThemeManager
	 */
	protected $themeManager;


	/**
	 * @return \Change\Workspace
	 */
	protected function getWorkspace()
	{
		return $this->getApplication()->getWorkspace();
	}

	/**
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 * @return $this
	 */
	public function setThemeManager(\Change\Presentation\Themes\ThemeManager $themeManager)
	{
		$this->themeManager = $themeManager;
		return $this;
	}

	/**
	 * @return \Change\Presentation\Themes\ThemeManager
	 */
	protected function getThemeManager()
	{
		return $this->themeManager;
	}

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::DEFAULT_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/TemplateManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_REGISTER_EXTENSIONS, [$this, 'onDefaultRegisterExtensions'], 5);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultRegisterExtensions(\Change\Events\Event $event)
	{
		$extensions = $event->getParam('extensions');
		if ($extensions instanceof \ArrayObject)
		{
			$extensions[] = new Twig\Extension($event->getApplicationServices()->getI18nManager());
		}
	}

	/**
	 * @return \Twig_ExtensionInterface[]
	 */
	public function getExtensions()
	{
		if ($this->extensions === null)
		{
			$this->extensions = [];
			$em = $this->getEventManager();
			$arguments = $em->prepareArgs(['extensions' => new \ArrayObject()]);
			$this->getEventManager()->trigger(static::EVENT_REGISTER_EXTENSIONS, $this, $arguments);
			if ($arguments['extensions'] instanceof \ArrayObject)
			{
				$this->extensions = $arguments['extensions']->getArrayCopy();
			}
		}
		return $this->extensions;
	}

	/**
	 * @param \Twig_ExtensionInterface $extension
	 */
	public function addExtension($extension)
	{
		$this->getExtensions();
		$this->extensions[] = $extension;
	}

	/**
	 * @return string
	 */
	protected function getCachePath()
	{
		if ($this->cachePath === null)
		{
			$this->cachePath = $this->getWorkspace()
				->cachePath('Templates', 'Compiled');
			\Change\Stdlib\File::mkdir($this->cachePath);
		}
		return $this->cachePath;
	}

	/**
	 * @api
	 * @param string $pathName
	 * @param array $attributes
	 * @return string
	 */
	public function renderTemplateFile($pathName, array $attributes)
	{
		$loader = new \Twig_Loader_Filesystem(dirname($pathName));
		$twig = new \Twig_Environment($loader, ['cache' => $this->getCachePath(), 'auto_reload' => true]);
		foreach ($this->getExtensions() as $extension)
		{
			$twig->addExtension($extension);
		}
		return $twig->render(basename($pathName), $attributes);
	}

	/**
	 * @api
	 * @param string $twigTemplate
	 * @param array $attributes
	 * @return string
	 */
	public function renderTemplateString($twigTemplate, array $attributes)
	{
		$loader = new \Twig_Loader_String();
		$twig = new \Twig_Environment($loader);
		foreach ($this->getExtensions() as $extension)
		{
			$twig->addExtension($extension);
		}
		return $twig->render($twigTemplate, $attributes);
	}

	/**
	 * @param string $relativePath
	 * @param array $attributes
	 * @return string
	 */
	public function renderThemeTemplateFile($relativePath, array $attributes)
	{
		$paths = $this->getThemeManager()->getThemeTwigBasePaths();
		$loader = new \Twig_Loader_Filesystem($paths);

		$parentTheme = $this->getThemeManager()->getCurrent()->getParentTheme();
		while ($parentTheme && $parentTheme->getName() !== 'Rbs_Base')
		{
			$parentThemePaths = $this->getThemeManager()->getThemeTwigBasePaths($parentTheme);
			foreach ($parentThemePaths as $path)
			{
				$loader->addPath($path, $parentTheme->getName());
			}
			$parentTheme = $parentTheme->getParentTheme();
		}

		$parentTheme = $this->getThemeManager()->getByName('Rbs_Base');
		$parentThemePaths = $this->getThemeManager()->getThemeTwigBasePaths($parentTheme);
		foreach ($parentThemePaths as $path)
		{
			$loader->addPath($path, $parentTheme->getName());
		}

		$twig = new \Twig_Environment($loader, ['cache' => $this->getCachePath(), 'auto_reload' => true]);
		foreach ($this->getExtensions() as $extension)
		{
			$twig->addExtension($extension);
		}
		return $twig->render($relativePath, $attributes);
	}
}
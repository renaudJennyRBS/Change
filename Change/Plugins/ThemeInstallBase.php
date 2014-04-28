<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Plugins;

use Zend\Json\Json;
use Zend\Stdlib\Glob;

/**
 * @name \Change\Plugins\ThemeInstallBase
 */
class ThemeInstallBase extends InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function initialize($plugin)
	{
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Db\InterfaceSchemaManager $schemaManager
	 * @throws \RuntimeException
	 */
	public function executeDbSchema($plugin, $schemaManager)
	{
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$themePlugin = $applicationServices->getPluginManager()->getPlugin(Plugin::TYPE_MODULE, 'Rbs', 'Theme');
		if ($themePlugin === null || !$themePlugin->isAvailable())
		{
			return;
		}

		$transactionManager = $applicationServices->getTransactionManager();
		$theme = $this->createOrUpdateTheme($plugin, $applicationServices, $transactionManager);

		// Fetch and create theme if necessary
		foreach ($this->getTemplatesJsonDefinitions($plugin) as $filePath)
		{
			$this->createOrUpdateTemplate($theme, $filePath, $plugin, $applicationServices);
		}

		$themeManager = $applicationServices->getThemeManager();
		$themeManager->installPluginTemplates($plugin, $theme);
		$themeManager->installPluginAssets($plugin, $theme);
		$this->writeAssetic($theme, $themeManager);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param string $name
	 * @return \Rbs\Theme\Documents\Theme
	 */
	protected function getThemeByName($applicationServices, $name)
	{
		$themeModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Theme');
		$query = $applicationServices->getDocumentManager()->getNewQuery($themeModel);
		$query->andPredicates($query->eq('name', $name));
		return $query->getFirstDocument();
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param string $code
	 * @return \Rbs\Theme\Documents\Template
	 */
	protected function getTemplateByCode($applicationServices, $code)
	{
		$templateModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Template');
		$query = $applicationServices->getDocumentManager()->getNewQuery($templateModel);
		$query->andPredicates($query->eq('code', $code));
		return $query->getFirstDocument();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @return array
	 */
	protected function getTemplatesJsonDefinitions($plugin)
	{
		$templatesDir = $plugin->getAbsolutePath() . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'Templates';
		// Plugin Modules.
		$templatesPattern = $templatesDir . DIRECTORY_SEPARATOR . '*.json';
		return Glob::glob($templatesPattern, Glob::GLOB_NOESCAPE + Glob::GLOB_NOSORT);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param String $templateName
	 * @return string
	 */
	protected function buildTemplateCode($plugin, $templateName)
	{
		return $plugin->getName() . '_' . \Change\Stdlib\String::ucfirst($templateName);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @return string
	 */
	protected function buildThemeName($plugin)
	{
		return $plugin->getName();
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @return string
	 */
	protected function buildThemeLabelKey($plugin)
	{
		return 't.' . strtolower($plugin->getVendor()) . '.' . strtolower($plugin->getShortName()) . '.admin.label';
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param String $templateName
	 * @return string
	 */
	protected function buildTemplateLabelKey($plugin, $templateName)
	{
		return 't.' . strtolower($plugin->getVendor()) . '.' . strtolower($plugin->getShortName()) . '.admin.' . $templateName . '_label';
	}

	/**
	 * @param \Rbs\Theme\Documents\Theme $theme
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 */
	protected function writeAssetic($theme, $themeManager)
	{
		$configuration = $theme->getAssetConfiguration();
		$am = $themeManager->getAsseticManager($configuration);
		$writer = new \Assetic\AssetWriter($themeManager->getAssetRootPath());
		$writer->writeManagerAssets($am);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return \Rbs\Theme\Documents\Theme
	 * @throws
	 */
	protected function createOrUpdateTheme($plugin, $applicationServices, $transactionManager)
	{
		$themeName = $plugin->getName();
		$theme = $this->getThemeByName($applicationServices, $themeName);
		if ($theme === null)
		{
			$themeModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Theme');
			/* @var $theme \Rbs\Theme\Documents\Theme */
			$theme = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($themeModel);
			$theme->setName($themeName);
			$theme->setActive(true);
		}
		try
		{
			$transactionManager->begin();
			$theme->setLabel($applicationServices->getI18nManager()->trans($this->buildThemeLabelKey($plugin), ['ucf']));
			$theme->save();
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
		return $theme;
	}

	/**
	 * @param \Rbs\Theme\Documents\Theme $theme
	 * @param string $filePath
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws
	 */
	protected function createOrUpdateTemplate($theme, $filePath, $plugin, $applicationServices)
	{
		$basePath = $plugin->getAbsolutePath() . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'Templates';
		$filesMeta = [];
		$filesMeta['json'] = $filePath;
		$file = new \SplFileInfo($filePath);
		$fileName = $file->getFilename();
		$templateDefinition = Json::decode(file_get_contents($filePath), Json::TYPE_ARRAY);
		$templateName = substr($fileName, 0, mb_strlen($fileName) - 5);
		$templateCode = $this->buildTemplateCode($plugin, $templateName);
		$template = $this->getTemplateByCode($applicationServices, $templateCode);
		if ($template === null)
		{
			$templateModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Template');
			/* @var $template \Rbs\Theme\Documents\Template */
			$template = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($templateModel);
			$template->setCode($templateCode);
			$template->setTheme($theme);
			$template->setActive(true);
		}

		$html = '';
		$htmlFilePath = $basePath . DIRECTORY_SEPARATOR . $templateName . '.twig';
		if (file_exists($htmlFilePath))
		{
			$html = file_get_contents($htmlFilePath);
		}
		$filesMeta['html'] = $htmlFilePath;

		$boHtml = '';
		$boHtmlFilePath = $basePath . DIRECTORY_SEPARATOR . $templateName . '-bo.twig';
		if (file_exists($boHtmlFilePath))
		{
			$boHtml = file_get_contents($boHtmlFilePath);
		}
		$filesMeta['htmlForBackoffice'] = $boHtmlFilePath;

		$editableContent = isset($templateDefinition['editableContent'])
			&& is_array($templateDefinition['editableContent']) ? $templateDefinition['editableContent'] : [];

		$existingEditableContent = $template->getEditableContent();
		if (is_array($existingEditableContent))
		{
			$editableContent = array_merge($editableContent, $existingEditableContent);
		}
		$mailSuitable = isset($templateDefinition['mailSuitable'])
			&& is_bool($templateDefinition['mailSuitable']) ? $templateDefinition['mailSuitable'] : false;

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$template->setLabel($applicationServices->getI18nManager()->trans($this->buildTemplateLabelKey($plugin,
				$templateName), ['ucf']));
			$template->setHtmlData($html);
			$template->setEditableContent($editableContent);
			$template->setHtmlForBackoffice($boHtml);
			$template->setMailSuitable($mailSuitable);
			$template->save();
			$template->setMeta(\Rbs\Theme\Documents\Template::FILE_META_KEY, $filesMeta);
			$template->saveMetas();
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}
}
<?php
namespace Theme\Rbs\Base\Setup;

use Zend\Json\Json;

/**
 * @name \Theme\Rbs\Base\Setup
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		$themeManager = $applicationServices->getThemeManager();
		$pluginManager = $applicationServices->getPluginManager();
		$modules = $pluginManager->getModules();
		$themeManager->installPluginTemplates($plugin);
		$themeManager->installPluginAssets($plugin);
		foreach ($modules as $module)
		{
			if ($module->isAvailable())
			{
				//echo $module, PHP_EOL;
				$themeManager->installPluginTemplates($module);
				$themeManager->installPluginAssets($module);
			}
		}
		$configuration = $themeManager->getDefault()->getAssetConfiguration();
		$am = $themeManager->getAsseticManager($configuration);
		$writer = new \Assetic\AssetWriter($themeManager->getAssetRootPath());
		$writer->writeManagerAssets($am);

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$theme = $this->getTheme($applicationServices, 'Rbs_Base');
			$this->getTemplate($applicationServices, $theme, 'Rbs_Base_Popin_Page', 'popin', 'Popin');

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	* @param string $name
	* @return \Rbs\Theme\Documents\Theme
	*/
	protected function getTheme($applicationServices, $name)
	{
		$themeModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Theme');
		$query = $applicationServices->getDocumentManager()->getNewQuery($themeModel);
		$query->andPredicates($query->eq('name', $name));
		$theme = $query->getFirstDocument();
		if (!($theme instanceof \Rbs\Theme\Documents\Theme))
		{
			/* @var $theme \Rbs\Theme\Documents\Theme */
			$theme = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($themeModel);
			$theme->setLabel('Base');
			$theme->setName($name);
			$theme->setActive(true);
			$theme->save();
		}
		return $theme;
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param \Rbs\Theme\Documents\Theme $theme
	 * @param string $code
	 * @param string $name
	 * @param string $label
	 * @param boolean $mailSuitable
	 * @return \Rbs\Theme\Documents\Template
	 */
	protected function getTemplate($applicationServices, $theme, $code, $name, $label, $mailSuitable = false)
	{
		$templateModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Template');
		$query = $applicationServices->getDocumentManager()->getNewQuery($templateModel);
		$query->andPredicates($query->eq('code', $code));
		$template = $query->getFirstDocument();
		if (!($template instanceof \Rbs\Theme\Documents\Template))
		{
			/* @var $template \Rbs\Theme\Documents\Template */
			$template = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($templateModel);
			$template->setCode($code);
			$template->setTheme($theme);
			$template->setLabel($label);
			$html = file_get_contents(__DIR__ . '/Assets/' . $name . '.twig');
			$template->setHtml($html);
			$json = file_get_contents(__DIR__ . '/Assets/' . $name . '.json');
			$template->setEditableContent(Json::decode($json, Json::TYPE_ARRAY));
			$boHtml = file_get_contents(__DIR__ . '/Assets/' . $name . '-bo.twig');
			$template->setHtmlForBackoffice($boHtml);
			$template->setMailSuitable($mailSuitable);
			$template->setActive(true);
			$template->save();
		}
		return $template;
	}
}
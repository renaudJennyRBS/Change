<?php
namespace Theme\Rbs\Demo\Setup;

use Zend\Json\Json;

/**
 * @name \Theme\Rbs\Demo\Setup
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
		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$theme = $this->getTheme($applicationServices, 'Rbs_Demo');
			$themeManager->installPluginTemplates($plugin, $theme);
			$themeManager->installPluginAssets($plugin, $theme);
			$this->writeAssetic($theme, $themeManager);

			$this->getTemplate($applicationServices, $theme, 'Rbs_Demo_Sidebar_Page', 'sidebarpage', 'Sidebar');
			$this->getTemplate($applicationServices, $theme, 'Rbs_Demo_No_Sidebar_Page', 'nosidebarpage', 'No Sidebar');
			$this->getTemplate($applicationServices, $theme, 'Rbs_Demo_Mail', 'mail', 'Mail', true);

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
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
			$theme->setLabel('Demo');
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
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
		$themeModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Theme');
		$query = $applicationServices->getDocumentManager()->getNewQuery($themeModel);
		$query->andPredicates($query->eq('name', 'Rbs_Demo'));
		$theme = $query->getFirstDocument();
		$themeManager = $applicationServices->getThemeManager();
		if ($theme instanceof \Rbs\Theme\Documents\Theme)
		{
			$themeManager->installPluginTemplates($plugin, $theme);
			$themeManager->installPluginAssets($plugin, $theme);
			$this->writeAssetic($theme, $themeManager);
			return;
		}

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			/* @var $theme \Rbs\Theme\Documents\Theme */
			$theme = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($themeModel);
			$theme->setLabel('Demo');
			$theme->setName('Rbs_Demo');
			$theme->setActive(true);
			$theme->save();

			$themeManager->installPluginTemplates($plugin, $theme);
			$themeManager->installPluginAssets($plugin, $theme);
			$this->writeAssetic($theme, $themeManager);

			$pageTemplateModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Template');

			/* @var $pageTemplate \Rbs\Theme\Documents\Template */
			$pageTemplate = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($pageTemplateModel);
			$pageTemplate->setTheme($theme);
			$pageTemplate->setLabel('Sidebar');
			$html = file_get_contents(__DIR__ . '/Assets/sidebarpage.twig');
			$pageTemplate->setHtml($html);
			$json = file_get_contents(__DIR__ . '/Assets/sidebarpage.json');
			$pageTemplate->setEditableContent(Json::decode($json, Json::TYPE_ARRAY));
			$boHtml = file_get_contents(__DIR__ . '/Assets/sidebarpage-bo.twig');
			$pageTemplate->setHtmlForBackoffice($boHtml);
			$pageTemplate->setActive(true);
			$pageTemplate->save();

			/* @var $pageTemplate \Rbs\Theme\Documents\Template */
			$pageTemplate = $applicationServices->getDocumentManager()->getNewDocumentInstanceByModel($pageTemplateModel);
			$pageTemplate->setTheme($theme);
			$pageTemplate->setLabel('No Sidebar');
			$html = file_get_contents(__DIR__ . '/Assets/nosidebarpage.twig');
			$pageTemplate->setHtml($html);
			$json = file_get_contents(__DIR__ . '/Assets/nosidebarpage.json');
			$pageTemplate->setEditableContent(Json::decode($json, Json::TYPE_ARRAY));
			$boHtml = file_get_contents(__DIR__ . '/Assets/nosidebarpage-bo.twig');
			$pageTemplate->setHtmlForBackoffice($boHtml);
			$pageTemplate->setActive(true);
			$pageTemplate->save();

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
}
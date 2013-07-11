<?php
namespace Theme\Rbs\Demo\Setup;

use Zend\Json\Json;

/**
 * @name \Theme\Rbs\Demo\Setup
 */
class Install
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$themeModel = $documentServices->getModelManager()->getModelByName('Rbs_Theme_Theme');
		$query = new \Change\Documents\Query\Query($documentServices, $themeModel);
		$query->andPredicates($query->eq('name', 'Rbs_Demo'));
		$theme = $query->getFirstDocument();
		if ($theme instanceof \Rbs\Theme\Documents\Theme)
		{
			return;
		}

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();
			/* @var $theme \Rbs\Theme\Documents\Theme */
			$theme = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($themeModel);
			$theme->setLabel('Demo');
			$theme->setName('Rbs_Demo');
			$theme->setActive(true);
			$theme->save();

			$pageTemplateModel = $documentServices->getModelManager()->getModelByName('Rbs_Theme_PageTemplate');

			/* @var $pageTemplate \Rbs\Theme\Documents\PageTemplate */
			$pageTemplate = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($pageTemplateModel);
			$pageTemplate->setTheme($theme);
			$pageTemplate->setLabel('Sample');
			$html = file_get_contents(__DIR__ . '/Assets/Sample.twig');
			$pageTemplate->setHtml($html);
			$json = file_get_contents(__DIR__ . '/Assets/Sample.json');
			$pageTemplate->setEditableContent(Json::decode($json, Json::TYPE_ARRAY));
			$pageTemplate->setHtmlForBackoffice('<div data-editable-zone-id="zoneEditable1"></div>');
			$pageTemplate->setActive(true);
			$pageTemplate->save();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}
}
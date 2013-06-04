<?php
namespace Theme\Change\Demo\Setup;

/**
 * @name \Theme\Change\Demo\Setup
 */
class Install
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $documentServices, $presentationServices)
	{
		$themeModel = $documentServices->getModelManager()->getModelByName('Change_Theme_Theme');
		$query = new \Change\Documents\Query\Builder($documentServices, $themeModel);
		$query->andPredicates($query->eq('name', 'Change_Demo'));
		$theme = $query->getFirstDocument();
		if ($theme instanceof \Change\Theme\Documents\Theme)
		{
			return;
		}
		/* @var $theme \Change\Theme\Documents\Theme */
		$theme = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($themeModel);
		$theme->setLabel('Demo');
		$theme->setName('Change_Demo');
		$theme->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_DRAFT);
		$theme->save();

		$pageTemplateModel = $documentServices->getModelManager()->getModelByName('Change_Theme_PageTemplate');

		/* @var $pageTemplate \Change\Theme\Documents\PageTemplate */
		$pageTemplate = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($pageTemplateModel);
		$pageTemplate->setTheme($theme);

		$pageTemplate->setLabel('Sample');
		$html = file_get_contents(__DIR__ . '/Assets/Sample.twig');
		$pageTemplate->setHtml($html);
		$json = file_get_contents(__DIR__ . '/Assets/Sample.json');
		$pageTemplate->setEditableContent($json);
		$pageTemplate->setHtmlForBackoffice('<div data-editable-zone-id="zoneEditable1"></div>');
		$pageTemplate->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_DRAFT);
		$pageTemplate->save();
	}
}
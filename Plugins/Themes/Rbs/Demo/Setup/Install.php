<?php
namespace Theme\Rbs\Demo\Setup;

/**
 * @name \Theme\Rbs\Demo\Setup
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
		$themeModel = $documentServices->getModelManager()->getModelByName('Rbs_Theme_Theme');
		$query = new \Change\Documents\Query\Query($documentServices, $themeModel);
		$query->andPredicates($query->eq('name', 'Rbs_Demo'));
		$theme = $query->getFirstDocument();
		if ($theme instanceof \Rbs\Theme\Documents\Theme)
		{
			return;
		}
		/* @var $theme \Rbs\Theme\Documents\Theme */
		$theme = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($themeModel);
		$theme->setLabel('Demo');
		$theme->setName('Rbs_Demo');
		$theme->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_DRAFT);
		$theme->save();

		$pageTemplateModel = $documentServices->getModelManager()->getModelByName('Rbs_Theme_PageTemplate');

		/* @var $pageTemplate \Rbs\Theme\Documents\PageTemplate */
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
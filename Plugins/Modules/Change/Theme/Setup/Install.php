<?php
namespace Change\Theme\Setup;

/**
 * Class Install
 * @package Change\Theme\Setup
 * @name \Change\Theme\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Application $application
	 */
	public function executeApplication($application)
	{

	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function executeServices($applicationServices, $documentServices)
	{
		$pageTemplateModel = $documentServices->getModelManager()->getModelByName('Change_Theme_PageTemplate');
		$query = new \Change\Documents\Query\Builder($documentServices, $pageTemplateModel);
		$pageTemplate = $query->getFirstDocument();
		if (!$pageTemplate)
		{

			/* @var $pageTemplate \Change\Theme\Documents\PageTemplate */
			$pageTemplate = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($pageTemplateModel);
			$pageTemplate->setLabel('Exemple');
			$pageTemplate->setHtml('<!DOCTYPE html>
<html>
	<head>
		{% for headLine in pageResult.head %}
            {{ headLine|raw }}
        {% endfor %}
        <link rel="icon" type="image/png" href="/Changebo/img/rbschange-favicon.png" />
        <link href="/Changebo/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
	</head>
<body>
	<h2>Page: {{ pageResult.identifier }}</h2>
	<!-- zoneEditable1 -->
</body>
</html>');
			$pageTemplate->setEditableContent('{
    "zoneEditable1" : {
        "id"   : "zoneEditable1",
        "grid" : 12,
        "gridMode" : "fluid",
        "type" : "container",
        "parameters" : {
        }
    }
}');
			$pageTemplate->setHtmlForBackoffice('<div><h2>Template</h2><p>Ceci est dans le template de page et ne fait pas partie des zones éditables de la page.</p></div>
<div data-editable-zone-id="zoneEditable1"></div>');


			$pageTemplate->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_PUBLISHABLE);
			$pageTemplate->create();
		}
	}
}
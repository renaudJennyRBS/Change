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
	 * @throws \Exception
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
			$pageTemplate->setLabel('Sidebar');
			$html = file_get_contents(__DIR__ . '/Assets/sidebarpage.twig');
			$pageTemplate->setHtml($html);
			$json = file_get_contents(__DIR__ . '/Assets/sidebarpage.json');
			$pageTemplate->setEditableContent(Json::decode($json, Json::TYPE_ARRAY));
			$boHtml = file_get_contents(__DIR__ . '/Assets/sidebarpage-bo.twig');
			$pageTemplate->setHtmlForBackoffice($boHtml);
			$pageTemplate->setActive(true);
			$pageTemplate->save();

			$mailTemplateModel = $documentServices->getModelManager()->getModelByName('Rbs_Theme_MailTemplate');

			/* @var $mailTemplate \Rbs\Theme\Documents\MailTemplate */
			$mailTemplate = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($mailTemplateModel);
			$mailTemplate->setTheme($theme);
			$mailTemplate->setLabel('Timeline mention notification');
			$mailTemplate->setCode('timeline_mention_notification');
			$mailTemplate->setLCID('fr_FR');
			$mailTemplate->setSubject('Un message vous mentionne');
			$html = file_get_contents(__DIR__ . '/Assets/timeline-message-mail-notification-fr.twig');
			$mailTemplate->setContent($html);
			$mailTemplate->setActive(true);
			$mailTemplate->save();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}
}
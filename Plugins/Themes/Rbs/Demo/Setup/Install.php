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

			/* @var $pageTemplate \Rbs\Theme\Documents\PageTemplate */
			$pageTemplate = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($pageTemplateModel);
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

			$i18nManager = $applicationServices->getI18nManager();
			//mail template for Timeline message
			$mailTemplateModel = $documentServices->getModelManager()->getModelByName('Rbs_Theme_MailTemplate');
			$templatePath = __DIR__ . '/Assets/timeline-message-mail-' . $i18nManager->getLCID() . '.twig';
			if (file_exists($templatePath))
			{
				/* @var $mailTemplate \Rbs\Theme\Documents\MailTemplate */
				$mailTemplate = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($mailTemplateModel);
				$mailTemplate->setTheme($theme);
				$mailTemplate->setLabel('Timeline mention');
				$mailTemplate->setCode('timeline_mention');
				$mailTemplate->getCurrentLocalization()->setSubject($i18nManager->trans('t.rbs.demo.setup.timeline-message-mail-subject'));
				$html = file_get_contents($templatePath);
				$mailTemplate->getCurrentLocalization()->setContent($html);
				$mailTemplate->getCurrentLocalization()->setActive(true);
				$mailTemplate->save();
			}

			//mail template for Notifications
			$mailTemplateModel = $documentServices->getModelManager()->getModelByName('Rbs_Theme_MailTemplate');
			$availableLCID = $i18nManager->getSupportedLCIDs();
			/* @var $mailTemplate \Rbs\Theme\Documents\MailTemplate */
			$mailTemplate = $documentServices->getDocumentManager()->getNewDocumentInstanceByModel($mailTemplateModel);
			$mailTemplate->setTheme($theme);
			$mailTemplate->setLabel('Notifications');
			$mailTemplate->setCode('notifications');
			foreach ($availableLCID as $lcid)
			{
				$templatePath = __DIR__ . '/Assets/notification-mail-' . $lcid . '.twig';
				if (file_exists($templatePath))
				{
					$documentServices->getDocumentManager()->pushLCID($lcid);
					$currentLocalization = $mailTemplate->getCurrentLocalization();
					$currentLocalization->setSubject($i18nManager->transForLCID($lcid, 't.rbs.demo.setup.notification-mail-subject'));
					$html = file_get_contents($templatePath);
					$currentLocalization->setContent($html);
					$currentLocalization->setActive(true);
					$mailTemplate->save();
					$documentServices->getDocumentManager()->popLCID();
				}
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}
}
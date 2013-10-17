<?php
namespace Rbs\Website\Seo;

/**
 * @name \Rbs\Website\Seo\GetFunctionalPageVariables
 */
class GetFunctionalPageVariables
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function execute(\Zend\EventManager\Event $event)
	{
		$functions = $event->getParam('functions');
		if (in_array('Rbs_Website_FunctionalPage', $functions))
		{
			$documentServices = $event->getParam('documentServices');
			if ($documentServices instanceof \Change\Documents\DocumentServices)
			{
				$seoManager = new \Rbs\Seo\Services\SeoManager();
				$seoManager->setApplicationServices($documentServices->getApplicationServices());
				$seoManager->setDocumentServices($documentServices);

				$variables = ($event->getParam('variables')) ? $event->getParam('variables') : [];
				$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
				$variables = array_merge($variables, [
					'page.title' => $i18nManager->trans('m.rbs.website.documents.functionalpage.seo-page-title', ['ucf']),
					'page.website.title' => $i18nManager->trans('m.rbs.website.documents.functionalpage.seo-website-title', ['ucf']),
					'page.section.title' => $i18nManager->trans('m.rbs.website.documents.functionalpage.seo-section-title', ['ucf']),
					'document.title' => $i18nManager->trans('m.rbs.website.documents.functionalpage.seo-document-title', ['ucf']),
					'document.description' => $i18nManager->trans('m.rbs.website.documents.functionalpage.seo-document-description', ['ucf']),
					'document.keywords' => $i18nManager->trans('m.rbs.website.documents.functionalpage.seo-document-keywords', ['ucf'])
				]);

				$event->setParam('variables', $variables);
			}
		}
	}
}
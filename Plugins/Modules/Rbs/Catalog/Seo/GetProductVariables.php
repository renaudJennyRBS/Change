<?php
namespace Rbs\Catalog\Seo;

/**
 * @name \Rbs\Catalog\Seo\GetProductVariables
 */
class GetProductVariables
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function execute(\Zend\EventManager\Event $event)
	{
		$functions = $event->getParam('functions');
		$documentServices = $event->getParam('documentServices');
		if (in_array('Rbs_Catalog_Product', $functions))
		{
			$documentServices = $event->getParam('documentServices');
			$documentServices->getApplicationServices()->getLogging()->fatal(var_export($functions, true));
			if ($documentServices instanceof \Change\Documents\DocumentServices)
			{
				$variables = ($event->getParam('variables')) ? $event->getParam('variables') : [];
				$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
				$event->setParam('variables', array_merge($variables, [
					'document.title' => $i18nManager->trans('m.rbs.catalog.documents.product.seo-title', ['ucf']),
					'document.brand' => $i18nManager->trans('m.rbs.catalog.documents.product.seo-brand', ['ucf']),
					'document.description' => $i18nManager->trans('m.rbs.catalog.documents.product.seo-description', ['ucf'])
				]));
			}
		}
	}
}
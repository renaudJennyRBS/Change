<?php
namespace Rbs\Catalog\Seo;

/**
 * @name \Rbs\Catalog\Seo\GetProductVariables
 */
class GetProductVariables
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function execute(\Change\Events\Event $event)
	{
		$functions = $event->getParam('functions');
		if (in_array('Rbs_Catalog_Product', $functions))
		{
			$applicationServices = $event->getApplicationServices();
			if ($applicationServices)
			{
				$variables = ($event->getParam('variables')) ? $event->getParam('variables') : [];
				$i18nManager = $applicationServices->getI18nManager();
				$event->setParam('variables', array_merge($variables, [
					'document.title' => $i18nManager->trans('m.rbs.catalog.admin.product_seo_title', ['ucf']),
					'document.brand' => $i18nManager->trans('m.rbs.catalog.admin.product_seo_brand', ['ucf']),
					'document.description' => $i18nManager->trans('m.rbs.catalog.admin.product_seo_description', ['ucf'])
				]));
			}
		}
	}
}
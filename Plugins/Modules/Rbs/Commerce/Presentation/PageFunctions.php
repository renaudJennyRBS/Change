<?php
namespace Rbs\Commerce\Presentation;

/**
* @name \Rbs\Commerce\Presentation\PageFunctions
*/
class PageFunctions
{
	public function addFunctions(\Change\Events\Event $event)
	{
		$functions = $event->getParam('functions');
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$functions[] = ['code' => 'Rbs_Commerce_Cart', 'document' => false, 'block' => 'Rbs_Commerce_Cart',
			'label' => $i18nManager->trans('m.rbs.commerce.admin.cart_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.commerce.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Commerce_OrderProcess', 'document' => false, 'block' => 'Rbs_Commerce_OrderProcess',
			'label' => $i18nManager->trans('m.rbs.commerce.admin.order_process_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.commerce.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Commerce_PaymentReturn', 'document' => false, 'block' => 'Rbs_Commerce_PaymentReturn',
			'label' => $i18nManager->trans('m.rbs.commerce.admin.payment_return_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.commerce.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Commerce_TermsAndConditions', 'document' => false, 'block' => null,
			'label' => $i18nManager->trans('m.rbs.commerce.admin.terms_and_conditions_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.commerce.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Catalog_Product', 'document' => true, 'block' => 'Rbs_Catalog_Product',
			'label' => $i18nManager->trans('m.rbs.catalog.admin.product_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.commerce.admin.module_name', $ucf)];

		$event->setParam('functions', $functions);
	}
} 
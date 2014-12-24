<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Presentation;

/**
* @name \Rbs\Generic\Presentation\PageFunctions
*/
class PageFunctions
{
	public function addFunctions(\Change\Events\Event $event)
	{
		$functions = $event->getParam('functions');
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$functions[] = ['code' => 'Error_404', 'document' => false, 'block' => null,
			'label' => $i18nManager->trans('m.rbs.website.admin.function_error_404', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Error_401', 'document' => false, 'block' => 'Rbs_User_Login',
			'label' => $i18nManager->trans('m.rbs.user.admin.function_error_401', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Error_403', 'document' => false, 'block' => null,
			'label' => $i18nManager->trans('m.rbs.user.admin.function_error_403', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Error_500', 'document' => false, 'block' => 'Rbs_Website_Exception',
			'label' => $i18nManager->trans('m.rbs.website.admin.function_error_500', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Website_Website_SiteMap', 'document' => false, 'block' => 'Rbs_Website_SiteMap',
			'label' => $i18nManager->trans('m.rbs.website.admin.sitemap', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_User_CreateAccount', 'document' => false, 'block' => 'Rbs_User_CreateAccount',
			'label' => $i18nManager->trans('m.rbs.user.admin.function_create_account', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_User_CreateAccountSuccess', 'document' => false, 'block' => null,
			'label' => $i18nManager->trans('m.rbs.user.admin.function_create_account_success', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_User_Login', 'document' => false, 'block' => 'Rbs_User_Login',
			'label' => $i18nManager->trans('m.rbs.user.admin.function_login', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_User_ResetPassword', 'document' => false, 'block' => 'Rbs_User_ResetPassword',
			'label' => $i18nManager->trans('m.rbs.user.admin.function_reset_password', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Review_Review', 'document' => true, 'block' => 'Rbs_Review_ReviewDetail',
			'label' => $i18nManager->trans('m.rbs.review.front.review_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Simpleform_Form', 'document' => true, 'block' => 'Rbs_Simpleform_Form',
			'label' => $i18nManager->trans('m.rbs.simpleform.admin.block_form_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Elasticsearch_Result', 'document' => false, 'block' => 'Rbs_Elasticsearch_Result',
			'label' => $i18nManager->trans('m.rbs.elasticsearch.admin.result_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Elasticsearch_StoreResult', 'document' => false, 'block' => 'Rbs_Elasticsearch_StoreResult',
			'label' => $i18nManager->trans('m.rbs.elasticsearch.admin.storeresult_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$functions[] = ['code' => 'Rbs_Geo_ManageAddresses', 'document' => false, 'block' => 'Rbs_Geo_ManageAddresses',
			'label' => $i18nManager->trans('m.rbs.geo.admin.manage_addresses_function', $ucf),
			'section' => $i18nManager->trans('m.rbs.generic.admin.module_name', $ucf)];

		$event->setParam('functions', $functions);
	}
} 
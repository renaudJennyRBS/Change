<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace #namespace#;

/**
 * @name \#namespace#\#className#Information
 */
class #className#Information extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.#lowerVendor#.#lowerModule#.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.#lowerVendor#.#lowerModule#.admin.#localeName#_label', $ucf));

		// Block parameters.
		//$this->addParameterInformation('myParameterName', \Change\Documents\Property::TYPE_INTEGER)
		//	->setLabel($i18nManager->trans('m.#lowerVendor#.#lowerModule#.admin.#localeName#_my_parameter_name', $ucf));

		// Default template parameters.
		//$defaultInformation = $this->addDefaultTemplateInformation();
		//$defaultInformation->addParameterInformation('myParameterName', \Change\Documents\Property::TYPE_INTEGER)
		//	->setLabel($i18nManager->trans('m.#lowerVendor#.#lowerModule#.admin.#localeName#_my_parameter_name', $ucf));

		// Alternate template.
		//$templateInformation = $this->addTemplateInformation('#vendor#_#module#', 'my-template-name.twig');
		//$templateInformation->setLabel($i18nManager->trans('m.#lowerVendor#.#lowerModule#.admin.#localeName#_my_template_name_label', ['ucf']));
		//$templateInformation->addParameterInformation('myParameterName', \Change\Documents\Property::TYPE_INTEGER)
		//	->setLabel($i18nManager->trans('m.#lowerVendor#.#lowerModule#.admin.#localeName#_my_parameter_name', $ucf));
	}
}
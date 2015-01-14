<?php
namespace Rbs\Highlight\Blocks;

use Change\Presentation\Blocks\Event;

/**
 * @name \Rbs\Highlight\Blocks\Update
 */
class Update
{
	/**
	 * @see \Rbs\Website\Blocks\XhtmlTemplateInformation
	 * @param Event $event
	 */
	public function onUpdateTemplateInformation(Event $event)
	{
		$information = $event->getParam('information');
		if ($information instanceof \Change\Presentation\Blocks\Information)
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$templateInformation = $information->addTemplateInformation('Rbs_Highlight', 'carousel.twig');
			$templateInformation->setLabel($i18nManager->trans('m.rbs.highlight.admin.template_carousel_label', ['ucf']));

			$templateInformation->addParameterInformation('height', \Change\Documents\Property::TYPE_STRING, false, 240)
				->setLabel($i18nManager->trans('m.rbs.highlight.admin.template_carousel_height', ['ucf']))
				->setNormalizeCallback(function ($parametersValues) {
					$value = isset($parametersValues['height']) ? intval($parametersValues['height']) : 240;
					return ($value <= 50) ? 50 : $value;});
			$templateInformation->addParameterInformation('interval', \Change\Documents\Property::TYPE_INTEGER, 1000)
				->setLabel($i18nManager->trans('m.rbs.highlight.admin.template_carousel_interval', ['ucf']))
				->setNormalizeCallback(function ($parametersValues) {
					$value = isset($parametersValues['interval']) ? intval($parametersValues['interval']) : 1000;
					return ($value <= 500) ? 500 : $value;});
		}
	}
} 
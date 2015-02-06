<?php
namespace Rbs\Social\Blocks;

use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Social\Blocks\SocialButtonsInformation
 */
class SocialButtonsInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = ['ucf'];
		$this->setSection($i18nManager->trans('m.rbs.social.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.social.admin.socialbuttons_label', $ucf));
		$this->addParameterInformationForDetailBlock($this->getPublishableNonAbstractModelNames($event), $i18nManager);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return array
	 */
	protected function getPublishableNonAbstractModelNames(\Change\Events\Event $event)
	{
		$filters = [
			'publishable' => true,
			'abstract' => false,
			'inline' => false,
			'stateless' => false
		];
		return $event->getApplicationServices()->getModelManager()->getFilteredModelsNames($filters);
	}
}

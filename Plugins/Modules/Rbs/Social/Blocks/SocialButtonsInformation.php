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
		$allowedModelNames = [];
		$modelManager = $event->getApplicationServices()->getModelManager();
		foreach ($modelManager->getModelsNames() as $modelName)
		{
			$model = $modelManager->getModelByName($modelName);
			if ($model && !$model->isAbstract() && $model->isPublishable())
			{
				$allowedModelNames[] = $model->getName();
			}
		}
		return $allowedModelNames;
	}
}

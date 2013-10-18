<?php
namespace Rbs\Event\Blocks;

/**
 * @name \Rbs\Event\Blocks\EventInformation
 */
class EventInformation extends \Rbs\Event\Blocks\Base\BaseEventInformation
{
	/**
	 * @param string $name
	 * @param \Change\Presentation\Blocks\BlockManager $blockManager
	 */
	public function __construct($name, $blockManager)
	{
		parent::__construct($name, $blockManager);
		$ucf = array('ucf');
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		$this->setLabel($i18nManager->trans('m.rbs.event.blocks.event-label'));
		$this->getParameterInformation('docId')->setAllowedModelsNames('Rbs_Event_Event')
			->setLabel($i18nManager->trans('m.rbs.event.blocks.event-doc', $ucf));
		$this->getParameterInformation('templateName')->setDefaultValue('event.twig');
		$this->setFunctions(array('Rbs_Event_Event' => $i18nManager->trans('m.rbs.event.blocks.event-function', $ucf)));
	}
}

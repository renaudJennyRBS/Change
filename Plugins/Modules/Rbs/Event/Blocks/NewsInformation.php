<?php
namespace Rbs\Event\Blocks;

/**
 * @name \Rbs\Event\Blocks\NewsInformation
 */
class NewsInformation extends \Rbs\Event\Blocks\Base\BaseEventInformation
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
		$this->setLabel($i18nManager->trans('m.rbs.event.blocks.news-label'));
		$this->getParameterInformation('docId')->setAllowedModelsNames('Rbs_Event_News')
			->setLabel($i18nManager->trans('m.rbs.event.blocks.news-doc', $ucf));
		$this->getParameterInformation('templateName')->setDefaultValue('news.twig');
		$this->setFunctions(array('Rbs_Event_News' => $i18nManager->trans('m.rbs.event.blocks.news-function', $ucf)));
	}
}

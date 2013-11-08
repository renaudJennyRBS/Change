<?php
namespace Rbs\Website\Blocks;

/**
 * @name \Rbs\Website\Blocks\SiteMapInformation
 */
class SiteMapInformation extends MenuInformation
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.website.blocks.sitemap', $ucf));
		$this->getParameterInformation('templateName')->setDefaultValue('siteMap.twig');
		$this->getParameterInformation('maxLevel')->setDefaultValue(5);
		$this->removeParameterInformation('documentId');
		$this->setFunctions(array('Rbs_Website_Website_SiteMap' => 'Site map'));
	}
}
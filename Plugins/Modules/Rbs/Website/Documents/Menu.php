<?php
namespace Rbs\Website\Documents;

/**
 * @name \Rbs\Website\Documents\Menu
 */
class Menu extends \Compilation\Rbs\Website\Documents\Menu
{
	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		/** @var $document Product */
		$document = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$items = $restResult->getProperty('items');
			if (is_array($items))
			{
				$i18n = $event->getApplicationServices()->getI18nManager();
				foreach ($items as $index => $item)
				{
					if (isset($item['titleKey']))
					{
						$items[$index]['title'] = $i18n->trans($item['titleKey'], array('ucf'));
					}
				}
				$restResult->setProperty('items', $items);
			}
		}
	}
}
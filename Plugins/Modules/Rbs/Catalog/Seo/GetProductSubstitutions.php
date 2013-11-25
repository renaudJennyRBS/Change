<?php
namespace Rbs\Catalog\Seo;

/**
 * @name \Rbs\Catalog\Seo\GetProductSubstitutions
 */
class GetProductSubstitutions
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function execute(\Change\Events\Event $event)
	{
		$document = $event->getParam('document');
		if ($document instanceof \Rbs\Catalog\Documents\Product)
		{
			$variables = $event->getParam('variables');
			$substitutions = ($event->getParam('substitutions')) ? $event->getParam('substitutions') : [];
			foreach ($variables as $variable)
			{
				switch ($variable)
				{
					case 'document.title':
						$substitutions['document.title'] = $document->getCurrentLocalization()->getTitle();
						break;
					case 'document.brand':
						$brand = $document->getBrand();
						$substitutions['document.brand'] = ($brand) ? $brand->getCurrentLocalization()->getTitle() : '';
						break;
				}
			}
			$event->setParam('substitutions', $substitutions);
		}
	}
}
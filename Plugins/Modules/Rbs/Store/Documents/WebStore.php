<?php
namespace Rbs\Store\Documents;

/**
 * @name \Rbs\Store\Documents\WebStore
 */
class WebStore extends \Compilation\Rbs\Store\Documents\WebStore
{
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$document = $event->getDocument();
		if ($document instanceof WebStore && !$document->isNew())
		{
			$documentResult = $event->getParam('restResult');
			if ($documentResult instanceof \Change\Http\Rest\Result\DocumentResult)
			{
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Price_Price');
				$query->eq('webStore', $document);
				$documentResult->setProperty('countDefinedPrices', $query->getCountDocuments());
			}
		}
	}
}
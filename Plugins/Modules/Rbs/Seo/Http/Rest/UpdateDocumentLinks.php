<?php
namespace Rbs\Seo\Http\Rest;

/**
 * @name \Rbs\Seo\Http\Rest\UpdateDocumentLinks
 */
class UpdateDocumentLinks
{
	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function addLinks(\Change\Documents\Events\Event $event)
	{
		$result = $event->getParam('restResult');
		$document = $event->getDocument();

		if ($result instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			if ($document instanceof \Change\Documents\Interfaces\Publishable)
			{
				/* @var $document \Change\Documents\AbstractDocument */
				$urlManager = $event->getParam('urlManager');
				$query = new \Change\Documents\Query\Query($document->getDocumentServices(), 'Rbs_Seo_DocumentSeo');
				$query->andPredicates($query->eq('target', $document));
				$documentSeo = $query->getFirstDocument();
				if ($documentSeo)
				{
					/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
					$pathInfo = 'resources/Rbs/Seo/DocumentSeo/' . $documentSeo->getId();
					$l = new \Change\Http\Rest\Result\Link($urlManager, $pathInfo, 'seo');
					$result->addLink($l);
				}
			}
		}
	}
}
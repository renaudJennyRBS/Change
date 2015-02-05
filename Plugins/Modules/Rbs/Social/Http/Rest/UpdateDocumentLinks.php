<?php
namespace Rbs\Social\Http\Rest;

//TODO DELETE (useless, just for test)

/**
 * @name \Rbs\Social\Http\Rest\UpdateDocumentLinks
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

		if ($result instanceof \Change\Http\Rest\V1\Resources\DocumentResult && $document instanceof \Change\Documents\Interfaces\Publishable)
		{
			/* @var $document \Change\Documents\AbstractDocument */
			$urlManager = $event->getParam('urlManager');
			$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Seo_DocumentSeo');
			$query->andPredicates($query->eq('target', $document));
			$documentSeo = $query->getFirstDocument();
			if ($documentSeo)
			{
				/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
				$pathInfo = 'resources/Rbs/Seo/DocumentSeo/' . $documentSeo->getId();
				$l = new \Change\Http\Rest\V1\Link($urlManager, $pathInfo, 'seo');
				$result->addLink($l);
			}
			else
			{
				$pathInfo = 'Rbs/Seo/CreateSeoForDocument';
				$l = new \Change\Http\Rest\V1\Link($urlManager, $pathInfo, 'addSeo');
				$l->setQuery(['documentId' => $document->getId()]);
				$result->addAction($l);
			}
		}
	}
}
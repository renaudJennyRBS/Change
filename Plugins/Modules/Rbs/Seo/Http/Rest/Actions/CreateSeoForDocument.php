<?php
namespace Rbs\Seo\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;

/**
 * @see http://www.sitemaps.org/protocol.html
 * @name \Rbs\Seo\Http\Rest\Actions\CreateSeoForDocument
 */
class CreateSeoForDocument
{
	public function execute(\Change\Http\Event $event)
	{
		$result = new ArrayResult();
		$documentId = $event->getRequest()->getQuery('documentId');
		if ($documentId)
		{
			$document = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
			if ($document instanceof \Change\Documents\Interfaces\Publishable)
			{
				$seo = $event->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_DocumentSeo');
				/* @var $seo \Rbs\Seo\Documents\DocumentSeo */
				$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Website_Website');
				$websites = $dqb->getDocuments();
				$sitemapGenerateForWebsites = [];
				foreach ($websites as $website)
				{
					/* @var $website \Rbs\Website\Documents\Website */
					$sitemapGenerateForWebsites[$website->getId()] = [
						'label' => $website->getLabel(),
						'generate' => true
					];
				}
				$seo->setSitemapGenerateForWebsites($sitemapGenerateForWebsites);
				$seo->setTarget($document);
				$tm = $event->getApplicationServices()->getTransactionManager();
				try
				{
					$tm->begin();
					$seo->save();
					$tm->commit();
				}
				catch (\Exception $e)
				{
					throw $tm->rollBack($e);
				}

				$event->setParam('documentId', $seo->getId());
				$event->setParam('modelName', $seo->getDocumentModelName());
				$action = new \Change\Http\Rest\Actions\GetDocument();
				$action->execute($event);
				return;
			}
			else
			{
				$result->setArray([ 'error' => 'invalid document and/or document is not publishable' ]);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
			}

			$event->setResult($result);
		}
		else
		{
			$result->setArray([ 'error' => 'invalid document id' ]);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
		}
		$event->setResult($result);
	}
}
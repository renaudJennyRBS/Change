<?php
namespace Rbs\Elasticsearch\Services;

use Change\Documents\Interfaces\Publishable;
use Change\Documents\Query\Query;
use Change\Documents\RichtextProperty;
use Elastica\Document;
use Rbs\Elasticsearch\Events\Event;

/**
 * @name \Rbs\Elasticsearch\Services\WebsiteIndexManager
 */
class WebsiteIndexManager
{
	const DOCUMENT_TYPE = 'document';

	/**
	 * @var \Change\Documents\DocumentCollection
	 */
	protected $fullTextIndexes = null;

	/**
	 * @param Event $event
	 */
	public function onIndexDocument($event)
	{
		$indexManager = $event->getIndexManager();
		$LCID = $event->getParam('LCID');

		/* @var $model  \Change\Documents\AbstractModel */
		$model = $event->getParam('model');
		if ($model && $model->isPublishable())
		{
			/* @var $document \Change\Documents\AbstractDocument */
			$document = $event->getParam('document');
			if (!$document)
			{
				foreach ($this->getFullTextIndexes($indexManager) as $fulltext)
				{
					$indexManager->documentIdToDelete($fulltext->getClientName(), $fulltext->getName(), $event->getParam('id'));
				}
				return;
			}
			$publicationStatus = $model->getPropertyValue($document, 'publicationStatus', Publishable::STATUS_FILED);
			if ($publicationStatus == Publishable::STATUS_PUBLISHABLE)
			{
				$this->addDocument($indexManager, $document, $LCID);
				return;
			}
			elseif (in_array($publicationStatus, array(Publishable::STATUS_UNPUBLISHABLE, Publishable::STATUS_FROZEN, Publishable::STATUS_FILED)))
			{
				foreach ($this->getFullTextIndexes($indexManager) as $fulltext)
				{
					if ($fulltext->getAnalysisLCID() === $LCID)
					{
						$indexManager->documentIdToDelete($fulltext->getClientName(), $fulltext->getName(), $event->getParam('id'));
					}
				}
			}
		}
	}

	/**
	 * @param \Rbs\Elasticsearch\Services\IndexManager $indexManager
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document
	 * @param string $LCID
	 */
	protected function addDocument(\Rbs\Elasticsearch\Services\IndexManager $indexManager, $document, $LCID)
	{
		$websiteIds = array();
		foreach ($document->getPublicationSections() as $section)
		{
			$website = $section->getWebsite();
			if (!in_array($website->getId(), $websiteIds))
			{
				$websiteIds[] = $website->getId();
				foreach ($this->getFullTextIndexes($indexManager) as $fulltext)
				{
					if ($fulltext->getAnalysisLCID() === $LCID && $fulltext->getWebsiteId() == $website->getId())
					{
						$elasticaDocument = new Document($document->getId(), array(), static::DOCUMENT_TYPE, $fulltext->getName());
						$this->populateDocument($indexManager, $document, $elasticaDocument, $fulltext);

						$canonicalSection = $document->getCanonicalSection($website);
						if ($canonicalSection)
						{
							$elasticaDocument->set('canonicalSectionId', $canonicalSection->getId());
						}
						$indexManager->documentToAdd($fulltext->getClientName(), $elasticaDocument);
					}
				}
			}
		}
	}

	/**
	 * @param \Rbs\Elasticsearch\Services\IndexManager $indexManager
	 * @param \Change\Documents\AbstractDocument $document
	 * @param Document $elasticaDocument
	 * @param \Rbs\Elasticsearch\Documents\FullText $fulltext
	 */
	protected function populateDocument($indexManager, $document, $elasticaDocument, $fulltext)
	{
		$model = $document->getDocumentModel();
		$elasticaDocument->set('title', $model->getPropertyValue($document, 'title'));
		$elasticaDocument->set('model', $model->getName());
		$elasticaDocument->set('rootModel', $model->getRootName());
		$startPublication = $model->getPropertyValue($document, 'startPublication');

		if (!($startPublication instanceof \DateTime))
		{
			$startPublication = new \DateTime();
		}
		$elasticaDocument->set('startPublication', $startPublication->format(\DateTime::ISO8601));

		$endPublication = $model->getPropertyValue($document, 'endPublication');
		if (!($endPublication instanceof \DateTime))
		{
			$endPublication = $startPublication->add(new \DateInterval('P50Y'));
		}
		$elasticaDocument->set('endPublication', $endPublication->format(\DateTime::ISO8601));

		$indexManager->dispatchPopulateDocument($elasticaDocument, $document, $fulltext);
		$event = new \Change\Documents\Events\Event('fullTextContent', $document,
			array('elasticaDocument' => $elasticaDocument, 'indexManager' => $indexManager));
		$document->getEventManager()->trigger($event);
		$fullTextContent = $event->getParam('fullTextContent');

		if ($fullTextContent)
		{
			$elasticaDocument->set('content', $fullTextContent);
		}
	}

	/**
	 * @param \Rbs\Elasticsearch\Services\IndexManager $indexManager
	 * @return \Change\Documents\DocumentCollection|\Rbs\Elasticsearch\Documents\FullText[]
	 */
	protected function getFullTextIndexes(\Rbs\Elasticsearch\Services\IndexManager $indexManager)
	{
		if ($this->fullTextIndexes === null)
		{
			$query = new Query($indexManager->getDocumentServices(), 'Rbs_Elasticsearch_FullText');
			$query->andPredicates($query->activated());
			$this->fullTextIndexes = $query->getDocuments();
		}
		return $this->fullTextIndexes;
	}

	/**
	 * @param Event $event
	 */
	public function onPopulateDocument(Event $event)
	{
		$fulltext = $event->getParam('indexDefinition');
		if ($fulltext instanceof \Rbs\Elasticsearch\Documents\FullText)
		{
			$elasticaDocument = $event->getParam('elasticaDocument');
			$document = $event->getParam('document');
			if ($elasticaDocument instanceof Document && $document instanceof \Change\Documents\AbstractDocument)
			{
				$model = $document->getDocumentModel();
				$content = array();

				foreach ($model->getProperties() as $property)
				{
					if ($property->getType() === \Change\Documents\Property::TYPE_RICHTEXT)
					{
						$pv = $property->getValue($document);
						if ($pv instanceof RichtextProperty)
						{
							$content[] = $pv->getRawText();
						}
					}
				}

				if (count($content))
				{
					$elasticaDocument->set('content', implode(PHP_EOL, $content));
				}
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onFindIndexDefinition(Event $event)
	{
		$documentServices = $event->getIndexManager()->getDocumentServices();
		$indexName = $event->getParam('indexName');
		$clientName = $event->getParam('clientName');
		if ($indexName && $clientName)
		{
			$query = new Query($documentServices, 'Rbs_Elasticsearch_FullText');
			$query->andPredicates(
				$query->activated(),
				$query->eq('name', $indexName),
				$query->eq('clientName', $clientName)
			);

			if (($indexDefinition = $query->getFirstDocument()) !== null)
			{
				$event->setParam('indexDefinition', $indexDefinition);
			}
			return;
		}

		$website = $event->getParam('website');
		$mappingName = $event->getParam('mappingName');
		$analysisLCID = $event->getParam('analysisLCID');
		if ($mappingName === 'fulltext' && $analysisLCID && $website instanceof \Rbs\Website\Documents\Website || is_numeric($website))
		{
			$query = new Query($documentServices, 'Rbs_Elasticsearch_FullText');
			$query->andPredicates(
				$query->activated(),
				$query->eq('analysisLCID', $analysisLCID),
				$query->eq('website', $website)
			);
			if (($indexDefinition = $query->getFirstDocument()) !== null)
			{
				$event->setParam('indexDefinition', $indexDefinition);
			}
		}
	}
}
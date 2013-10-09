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
	 * @var \Rbs\Elasticsearch\Services\IndexManager
	 */
	protected $indexManager;

	/**
	 * @param \Rbs\Elasticsearch\Services\IndexManager $indexManager
	 */
	protected function setIndexManager($indexManager)
	{
		$this->indexManager = $indexManager;
	}

	/**
	 * @return \Rbs\Elasticsearch\Services\IndexManager
	 */
	protected function getIndexManager()
	{
		return $this->indexManager;
	}

	/**
	 * @param Event $event
	 */
	public function onIndexDocument($event)
	{
		$this->setIndexManager($event->getIndexManager());
		$LCID = $event->getParam('LCID');

		/* @var $model  \Change\Documents\AbstractModel */
		$model = $event->getParam('model');
		if ($model && $model->isPublishable())
		{
			/* @var $document \Change\Documents\AbstractDocument */
			$document = $event->getParam('document');
			if (!$document)
			{
				foreach ($this->getIndexManager()->getApplicationServices()->getI18nManager()->getSupportedLCIDs() as $LCID)
				{
					$this->deleteDocument($event->getParam('id'), $LCID);
				}
				return;
			}
			elseif ($event->getParam('deleted'))
			{
				$this->deleteDocument($event->getParam('id'), $LCID);
				return;
			}

			$publicationStatus = $model->getPropertyValue($document, 'publicationStatus');
			if ($publicationStatus == Publishable::STATUS_PUBLISHABLE)
			{
				$this->addDocument($document, $LCID);
				return;
			}
			if (in_array($publicationStatus,
				array(Publishable::STATUS_UNPUBLISHABLE, Publishable::STATUS_FROZEN, Publishable::STATUS_FILED))
			)
			{
				$this->deleteDocument($event->getParam('id'), $LCID);
				return;
			}
		}
	}

	/**
	 * @param integer $documentId
	 * @param string $LCID
	 */
	protected function deleteDocument($documentId, $LCID)
	{
		foreach ($this->getFullTextIndexes() as $fullText)
		{
			if ($fullText->getAnalysisLCID() == $LCID)
			{
				$client = $this->getIndexManager()->getClient($fullText->getClientName());
				if ($client)
				{
					$status = $client->getStatus();
					if ($status->indexExists($fullText->getName()))
					{
						$client->deleteIds(array($documentId), $fullText->getName(), static::DOCUMENT_TYPE);
					}
				}
			}
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document
	 * @param string $LCID
	 */
	protected function addDocument($document, $LCID)
	{
		$websiteIds = array();
		foreach ($document->getPublicationSections() as $section)
		{
			$website = $section->getWebsite();
			if (!in_array($website->getId(), $websiteIds))
			{
				$websiteIds[] = $website->getId();
				$fulltext = $this->getFullTextIndex($website->getId(), $LCID);

				if (!$fulltext)
				{
					$fulltext = $this->createFullText($website, $LCID, 'front');
				}

				if ($fulltext->activated())
				{
					$client = $this->getIndexManager()->getClient($fulltext->getClientName());
					if ($client)
					{
						$index = $client->getIndex($fulltext->getName());
						if (!$index->exists())
						{
							$this->getIndexManager()->createIndex($fulltext);
						}

						$elasticaDocument = new Document($document->getId(), array(), static::DOCUMENT_TYPE);
						$this->populateDocument($document, $elasticaDocument, $fulltext);

						$canonicalSection = $document->getCanonicalSection($website);
						if ($canonicalSection)
						{
							$elasticaDocument->set('canonicalSectionId', $canonicalSection->getId());
						}

						$index->addDocuments(array($elasticaDocument));
					}
				}
			}
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param Document $elasticaDocument
	 * @param \Rbs\Elasticsearch\Documents\FullText $fulltext
	 */
	protected function populateDocument($document, $elasticaDocument, $fulltext)
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

		$this->getIndexManager()->dispatchPopulateDocument($elasticaDocument, $document, $fulltext);
		$event = new \Change\Documents\Events\Event('fullTextContent', $document,
			array('elasticaDocument' => $elasticaDocument, 'indexManager' => $this->getIndexManager()));
		$document->getEventManager()->trigger($event);
		$fullTextContent = $event->getParam('fullTextContent');

		if ($fullTextContent)
		{
			$elasticaDocument->set('content', $fullTextContent);
		}
	}

	/**
	 * @return \Change\Documents\DocumentCollection|\Rbs\Elasticsearch\Documents\FullText[]
	 */
	protected function getFullTextIndexes()
	{
		if ($this->fullTextIndexes === null)
		{
			$query = new Query($this->getIndexManager()->getDocumentServices(), 'Rbs_Elasticsearch_FullText');
			$this->fullTextIndexes = $query->getDocuments();
		}
		return $this->fullTextIndexes;
	}

	/**
	 * @param $websiteId
	 * @param $LCID
	 * @return null|\Rbs\Elasticsearch\Documents\FullText
	 */
	protected function getFullTextIndex($websiteId, $LCID)
	{
		/* @var $fullText \Rbs\Elasticsearch\Documents\FullText */
		foreach ($this->getFullTextIndexes() as $fullText)
		{
			if ($fullText->getWebsiteId() == $websiteId && $fullText->getAnalysisLCID() == $LCID)
			{
				return $fullText;
			}
		}
		return null;
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
		$website = $event->getParam('website');
		$mappingName = $event->getParam('mappingName');
		$analysisLCID = $event->getParam('analysisLCID');
		if ($mappingName === 'fulltext' && $analysisLCID && $website instanceof \Rbs\Website\Documents\Website || is_numeric($website))
		{
			$query = new Query($event->getIndexManager()->getDocumentServices(), 'Rbs_Elasticsearch_FullText');
			$query->andPredicates(
				$query->eq('analysisLCID', $analysisLCID),
				$query->eq('website', $website)
			);

			if (($indexDefinition = $query->getFirstDocument()) !== null)
			{
				$event->setParam('indexDefinition', $indexDefinition);
			}
		}
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 * @param string $clientName
	 * @throws \Exception
	 * @return \Rbs\Elasticsearch\Documents\FullText
	 */
	public function createFullText($website, $LCID, $clientName)
	{
		/* @var $fullText \Rbs\Elasticsearch\Documents\FullText */
		$fullText = $this->getIndexManager()->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Elasticsearch_FullText');
		$tm = $this->getIndexManager()->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$fullText->setWebsite($website);
			$fullText->setAnalysisLCID($LCID);
			$fullText->setClientName($clientName);
			$fullText->create();

			$indexes = $this->getFullTextIndexes();
			$indexes[] = $fullText;
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $fullText;
	}
}
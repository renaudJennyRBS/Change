<?php
namespace Rbs\Elasticsearch\Index;

use Change\Documents\Interfaces\Publishable;
use Change\Documents\RichtextProperty;
use Elastica\Document;

/**
 * @name \Rbs\Elasticsearch\Index\FullTextIndexer
 */
class FullTextIndexer
{
	/**
	 * @var \Change\Documents\DocumentCollection
	 */
	protected $indexesDefinition = null;

	/**
	 * @var \Rbs\Elasticsearch\Index\IndexManager
	 */
	protected $indexManager;

	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param \Rbs\Elasticsearch\Index\IndexManager $indexManager
	 * @return $this
	 */
	public function setIndexManager(\Rbs\Elasticsearch\Index\IndexManager $indexManager)
	{
		$this->indexManager = $indexManager;
		return $this;
	}

	/**
	 * @return \Rbs\Elasticsearch\Index\IndexManager
	 */
	protected function getIndexManager()
	{
		return $this->indexManager;
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(\Change\Services\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
	}

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param Event $event
	 */
	protected function setEventContext(Event $event)
	{
		$this->setApplicationServices($event->getApplicationServices());
		$indexManager = $event->getIndexManager();
		if ($indexManager)
		{
			$this->setIndexManager($indexManager);
		}
	}

	/**
	 * @param Event $event
	 */
	public function onIndexDocument($event)
	{
		$this->setEventContext($event);
		$LCID = $event->getParam('LCID');

		/* @var $model  \Change\Documents\AbstractModel */
		$model = $event->getParam('model');
		if ($model && $model->isPublishable())
		{
			/* @var $document */
			$document = $event->getParam('document');
			if (!($document instanceof \Change\Documents\AbstractDocument))
			{
				$this->addDeleteDocumentId($event->getParam('id'));
				return;
			}
			$publicationStatus = $model->getPropertyValue($document, 'publicationStatus', Publishable::STATUS_FILED);
			if ($publicationStatus == Publishable::STATUS_PUBLISHABLE)
			{
				$this->addPublishableDocument($document, $LCID);
				return;
			}
			elseif (in_array($publicationStatus,
				array(Publishable::STATUS_UNPUBLISHABLE, Publishable::STATUS_FROZEN, Publishable::STATUS_FILED))
			)
			{
				$this->addDeleteDocumentId($event->getParam('id'), $LCID);
			}
		}
	}

	/**
	 * @param integer $documentId
	 * @param string $LCID
	 */
	protected function addDeleteDocumentId($documentId, $LCID = null)
	{
		foreach ($this->getIndexesDefinition() as $fulltext)
		{
			if ($LCID === null || $LCID == $fulltext->getAnalysisLCID())
			{
				$this->getIndexManager()->documentIdToDelete($fulltext->getClientName(), $fulltext->getName(), $documentId, $fulltext->getDefaultTypeName());
			}
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document
	 * @param string $LCID
	 */
	protected function addPublishableDocument($document, $LCID)
	{
		$websiteIds = array();
		foreach ($document->getPublicationSections() as $section)
		{
			$website = $section->getWebsite();
			if (!in_array($website->getId(), $websiteIds))
			{
				$websiteIds[] = $website->getId();
				/* @var $fulltext \Rbs\Elasticsearch\Documents\FullText */
				foreach ($this->getIndexesDefinition() as $fulltext)
				{
					if ($fulltext->getAnalysisLCID() === $LCID && $fulltext->getWebsiteId() == $website->getId())
					{
						$canonicalSection = $document->getCanonicalSection($website);
						if ($canonicalSection)
						{
							$elasticaDocument = new Document($document->getId(), array(), $fulltext->getDefaultTypeName(), $fulltext->getName());
							$this->populatePublishableDocument($document, $elasticaDocument, $fulltext);
							$elasticaDocument->set('canonicalSectionId', $canonicalSection->getId());
							$this->getIndexManager()->documentToAdd($fulltext->getClientName(), $elasticaDocument);
						}
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
	protected function populatePublishableDocument($document, $elasticaDocument, $fulltext)
	{
		$model = $document->getDocumentModel();
		$elasticaDocument->set('title', $model->getPropertyValue($document, 'title'));
		$elasticaDocument->set('model', $model->getName());
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
	}

	/**
	 * @return \Change\Documents\DocumentCollection|\Rbs\Elasticsearch\Documents\FullText[]
	 */
	protected function getIndexesDefinition()
	{
		if ($this->indexesDefinition === null)
		{
			$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Elasticsearch_FullText');
			$query->andPredicates($query->activated(), $query->eq('model', 'Rbs_Elasticsearch_FullText'));
			$this->indexesDefinition = $query->getDocuments();
		}
		return $this->indexesDefinition;
	}

	/**
	 * @param Event $event
	 */
	public function onPopulateDocument(Event $event)
	{
		$this->setEventContext($event);
		$fulltext = $event->getParam('indexDefinition');
		if ($fulltext instanceof \Rbs\Elasticsearch\Documents\FullText)
		{
			$elasticaDocument = $event->getParam('elasticaDocument');
			$document = $event->getParam('document');
			if ($elasticaDocument instanceof Document && $document instanceof \Change\Documents\AbstractDocument)
			{
				$parameters = array('elasticaDocument' => $elasticaDocument, 'indexManager' => $event->getIndexManager());
				$event = new \Change\Documents\Events\Event('fullTextContent', $document, $parameters);
				$document->getEventManager()->trigger($event);
				$fullTextContent = $event->getParam('fullTextContent');
				if ($fullTextContent)
				{
					$elasticaDocument->set('content', $fullTextContent);
				}
				else
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
	}

	/**
	 * @param string $clientName
	 * @param string $indexName
	 * @return \Rbs\Elasticsearch\Documents\FullText|null
	 */
	protected function getIndexDefinitionByName($clientName, $indexName)
	{
		$query = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Elasticsearch_FullText');
		$query->andPredicates(
			$query->activated(),
			$query->eq('name', $indexName),
			$query->eq('clientName', $clientName)
		);
		return $query->getFirstDocument();
	}

	/**
	 * @param string $mappingName
	 * @param $analysisLCID
	 * @param $websiteId
	 * @return \Rbs\Elasticsearch\Documents\FullText|null
	 */
	protected function getIndexDefinitionByMapping($mappingName, $analysisLCID, $websiteId)
	{
		/* @var $fullTextIndex \Rbs\Elasticsearch\Documents\FullText */
		foreach ($this->getIndexesDefinition() as $fullTextIndex)
		{
			if ($fullTextIndex->getMappingName() === $mappingName
				&& $fullTextIndex->getAnalysisLCID() === $analysisLCID
				&& $fullTextIndex->getWebsiteId() == $websiteId
			)
			{
				return $fullTextIndex;
			}
		}
		return null;
	}

	/**
	 * @param Event $event
	 */
	public function onFindIndexDefinition(Event $event)
	{
		$this->setEventContext($event);

		$indexName = $event->getParam('indexName');
		$clientName = $event->getParam('clientName');
		if ($indexName && $clientName)
		{
			$indexDefinition = $this->getIndexDefinitionByName($clientName, $indexName);
			if ($indexDefinition)
			{
				$event->setParam('indexDefinition', $indexDefinition);
			}
			return;
		}

		$website = $event->getParam('website');
		if ($website instanceof \Rbs\Website\Documents\Website)
		{
			$websiteId = $website->getId();
		}
		elseif (is_numeric($website))
		{
			$websiteId = intval($website);
		}
		else
		{
			$websiteId = null;
		}

		$mappingName = $event->getParam('mappingName');
		$analysisLCID = $event->getParam('analysisLCID');
		if ($mappingName === 'fulltext' && $analysisLCID && $websiteId)
		{
			$indexDefinition = $this->getIndexDefinitionByMapping($mappingName, $analysisLCID, $websiteId);
			if ($indexDefinition)
			{
				$event->setParam('indexDefinition', $indexDefinition);
			}
			return;
		}
	}

	/**
	 * @param Event $event
	 */
	public function onGetIndexesDefinition(Event $event)
	{
		$clientName = $event->getParam('clientName', null);
		$this->setEventContext($event);
		$indexesDefinition = $event->getParam('indexesDefinition', array());
		foreach ($this->getIndexesDefinition() as $def)
		{
			if (null == $clientName || $def->getClientName() == $clientName)
			{
				$indexesDefinition[] = $def;
			}
		}
		$event->setParam('indexesDefinition', $indexesDefinition);
	}
}
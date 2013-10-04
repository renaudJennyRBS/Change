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
	 * @var integer[]
	 */
	protected $websiteIds = null;

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
			if (in_array($publicationStatus, array(Publishable::STATUS_UNPUBLISHABLE, Publishable::STATUS_FROZEN, Publishable::STATUS_FILED)))
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
		$client = $this->getIndexManager()->getClient('front');
		if ($client)
		{
			$status = $client->getStatus();
			foreach ($this->getWebsiteIds() as $websiteId)
			{
				$indexName = $this->buildIndexNameForWebsiteAndLCID($websiteId, $LCID);
				if ($status->indexExists($indexName))
				{
					$client->deleteIds(array($documentId), $indexName, static::DOCUMENT_TYPE);
				}
			}
		}
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param Document $elasticaDocument
	 */
	protected function populateDocument($document, $elasticaDocument)
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

		$this->getIndexManager()->dispatchPopulateDocument($elasticaDocument, $document, 'fulltext');
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
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document
	 * @param string $LCID
	 */
	protected function addDocument($document, $LCID)
	{
		$client = $this->getIndexManager()->getClient('front');
		if ($client)
		{
			$websiteIds = array();
			$elasticaDocument = new Document($document->getId(), array(), static::DOCUMENT_TYPE);
			$this->populateDocument($document, $elasticaDocument);

			foreach($document->getPublicationSections() as $section)
			{
				$website = $section->getWebsite();
				if (!in_array($website->getId(), $websiteIds))
				{
					$websiteIds[] = $website->getId();
					$indexName = $this->buildIndexNameForWebsiteAndLCID($website->getId(), $LCID);
					$index = $client->getIndex($indexName);
					if (!$index->exists())
					{
						$this->getIndexManager()->createIndex($index, 'fulltext', $LCID);
					}
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

	/**
	 * @return integer[]
	 */
	protected function getWebsiteIds()
	{
		if ($this->websiteIds === null)
		{
			$query = new Query($this->getIndexManager()->getDocumentServices(), 'Rbs_Website_Website');
			$fb = $query->getFragmentBuilder();
			$sq = $query->dbQueryBuilder()->addColumn($fb->alias($query->getColumn('id'), 'id'))->query();
			$this->websiteIds = $sq->getResults($sq->getRowsConverter()->addIntCol('id')->singleColumn('id'));
		}
		return $this->websiteIds;
	}

	/**
	 * @param integer $websiteId
	 * @param string $LCID
	 * @return string
	 */
	protected function buildIndexNameForWebsiteAndLCID($websiteId, $LCID)
	{
		return 'fulltext_'. strtolower($websiteId . '_' . $LCID);
	}

	/**
	 * @param Event $event
	 */
	public function onGetMappingByName(Event $event)
	{
		$mappings = array('fulltext' => array(
			"document" => array(
				"properties" => array(
					"title" => array(
						"type" => "string",
						"boost" => 4,
						"analyzer" => "text_analyzer"
					),
					"content" => array(
						"type" => "string",
						"boost" => 2,
						"analyzer" => "text_analyzer"
					),
					"model" => array(
						"type" => "string",
						"index" => "not_analyzed"
					),
					"rootModel" => array(
						"type" => "string",
						"index" => "not_analyzed"
					),
					"canonicalSectionId" => array(
						"type" => "integer"
					),
					"startPublication" => array(
						"type" => "date"
					),
					"endPublication" => array(
						"type" => "date"
					)
				)
			)
		));

		$name = $event->getParam('name');
		if ($name && isset($mappings[$name]))
		{
			$event->setParam('mapping', $mappings[$name]);
		}
	}

	/**
	 * @param Event $event
	 */
	public function onGetAnalyzer(Event $event)
	{
		$analyzers = array(
			"fr_FR" => array(
				"analyzer" => array(
					"text_analyzer" => array(
						"tokenizer" => "standard",
						"filter" => array(
							"standard",
							"lowercase",
							"asciifolding",
							"elision",
							"text_snowball"
						)
					)
				),
				"filter" => array(
					"text_snowball" => array(
						"type" => "snowball",
						"name" => "french"
					)
				)
			)
		);

		$LCID = $event->getParam('LCID');
		if ($LCID && isset($analyzers[$LCID]))
		{
			$event->setParam('analyzer', $analyzers[$LCID]);
		}
	}

	/**
	 * @param Event $event
	 */
	public function onPopulateDocument(Event $event)
	{
		if ($event->getParam('mapping') === 'fulltext')
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
}
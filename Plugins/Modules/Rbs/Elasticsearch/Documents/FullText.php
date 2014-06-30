<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Documents;

use \Change\Documents\Interfaces\Publishable;

/**
 * @name \Rbs\Elasticsearch\Documents\FullText
 */
class FullText extends \Compilation\Rbs\Elasticsearch\Documents\FullText
{

	/**
	 * Attach specific document event
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('getDocumentIndexData', [$this, 'onDefaultGetDocumentIndexData'], 5);
		$eventManager->attach('getFacetsDefinition', [$this, 'onDefaultGetFacetsDefinition'], 5);
	}


	protected function onCreate()
	{
		$this->setCategory('fulltext');
		if (!$this->getName())
		{
			$this->setName($this->getCategory() . '_' . $this->getWebsiteId() . '_' . strtolower($this->getAnalysisLCID()));
		}
		parent::onCreate();
	}

	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return string
	 */
	public function composeRestLabel(\Change\I18n\I18nManager $i18nManager)
	{
		if ($this->getWebsite())
		{
			$key = 'm.rbs.elasticsearch.admin.fulltext_label_website';
			return $i18nManager->trans($key, array('ucf'), array('websiteLabel' => $this->getWebsite()->getLabel()));
		}
		return $this->getLabel();
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultGetFacetsDefinition(\Change\Documents\Events\Event $event)
	{
		$modelFacetDefinition = new \Rbs\Elasticsearch\Facet\ModelFacetDefinition('model');
		$modelFacetDefinition->setI18nManager($event->getApplicationServices()->getI18nManager());
		$modelFacetDefinition->setModelManager($event->getApplicationServices()->getModelManager());
		$event->setParam('facetsDefinition', [$modelFacetDefinition]);
	}

	/**
	 * @param \Rbs\Elasticsearch\Index\IndexManager $indexManager
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Documents\AbstractModel $model
	 * @return array [type => [propety => value]]
	 */
	public function getDocumentIndexData(\Rbs\Elasticsearch\Index\IndexManager $indexManager, $document, $model = null)
	{
		if ($this->getWebsite())
		{
			if ($document instanceof \Change\Documents\AbstractDocument)
			{
				if ($document instanceof \Change\Documents\Interfaces\Publishable)
				{
					$eventManager = $this->getEventManager();
					$args = $eventManager->prepareArgs(['document' => $document, 'indexManager' => $indexManager]);
					$eventManager->trigger('getDocumentIndexData', $this, $args);
					if (isset($args['documentData']) && is_array($args['documentData']))
					{
						return [$this->getDefaultTypeName() => $args['documentData']];
					}
				}
			}
			elseif ($model instanceof \Change\Documents\AbstractModel && is_numeric($document))
			{
				if ($model->isPublishable())
				{
					return [$this->getDefaultTypeName() => []];
				}
			}
		}
		return [];
	}

	/**
	 * @var \Rbs\Elasticsearch\Index\PublicationData
	 */
	protected $publicationData;

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetDocumentIndexData(\Change\Events\Event $event )
	{
		/** @var $document \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable */
		$document = $event->getParam('document');

		/** @var $index FullText */
		$index = $event->getTarget();

		$publicationStatus = $document->getDocumentModel()->getPropertyValue($document, 'publicationStatus', Publishable::STATUS_FILED);
		if ($publicationStatus == Publishable::STATUS_PUBLISHABLE)
		{
			if ($this->publicationData === null)
			{
				$publicationData = new \Rbs\Elasticsearch\Index\PublicationData();
				$publicationData->setDocumentManager($event->getApplicationServices()->getDocumentManager());
				$publicationData->setTreeManager($event->getApplicationServices()->getTreeManager());
			}
			else
			{
				$publicationData = $this->publicationData;
			}

			$canonicalSectionId = $publicationData->getCanonicalSectionId($document, $index->getWebsite());

			if ($canonicalSectionId)
			{
				$documentData = $event->getParam('documentData');
				if (!is_array($documentData)) {
					$documentData = [];
				}
				$documentData['canonicalSectionId'] = $canonicalSectionId;
				$documentData = $publicationData->addPublishableMetas($document, $documentData);

				$documentData = $publicationData->addPublishableContent($document, $index->getWebsite(), $documentData,
					$index, $event->getParam('indexManager'));

				$event->setParam('documentData', $documentData);
			}
		}
	}
}

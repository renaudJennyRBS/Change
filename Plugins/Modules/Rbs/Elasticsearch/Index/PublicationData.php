<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Index;

/**
* @name \Rbs\Elasticsearch\Index\PublicationData
*/
class PublicationData
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Change\Documents\TreeManager
	 */
	protected $treeManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Documents\TreeManager $treeManager
	 * @return $this
	 */
	public function setTreeManager($treeManager)
	{
		$this->treeManager = $treeManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\TreeManager
	 */
	protected function getTreeManager()
	{
		return $this->treeManager;
	}

	/**
	 * @param \Change\Documents\Interfaces\Publishable $document
	 * @param \Rbs\Website\Documents\Website $website
	 * @return integer|null
	 */
	public function getCanonicalSectionId($document, $website)
	{
		$canonicalSection = $document->getCanonicalSection($website);
		if ($canonicalSection)
		{
			return $canonicalSection->getId();
		}

		foreach ($document->getPublicationSections() as $section)
		{
			if ($section->getWebsite() === $website)
			{
				return $section->getId();
			}
		}
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Website_StaticPage');
		$query->andPredicates($query->eq('displayDocument', $document));

		/** @var $staticPage \Rbs\Website\Documents\StaticPage */
		foreach ($query->getDocuments() as $staticPage)
		{
			$node = $this->getTreeManager()->getNodeByDocument($staticPage);
			if ($node && in_array($website->getId(), $node->getAncestorIds()))
			{
				return $website->getId();
			}
		}
		return null;
	}

	/**
	 * @param \Change\Documents\Interfaces\Publishable|\Change\Documents\AbstractDocument $document
	 * @param array $documentData
	 * @return array
	 */
	public function addPublishableMetas($document, array $documentData)
	{
		$model = $document->getDocumentModel();
		if (!array_key_exists('model', $documentData))
		{
			$documentData['model'] = $model->getName();
		}
		if (!array_key_exists('title', $documentData))
		{
			$documentData['title'] = $model->getPropertyValue($document, 'title');
		}

		if (!array_key_exists('startPublication', $documentData))
		{
			$startPublication = $model->getPropertyValue($document, 'startPublication');

			if (!($startPublication instanceof \DateTime))
			{
				$startPublication = new \DateTime();
			}
			$documentData['startPublication'] =  $startPublication->format(\DateTime::ISO8601);

			$endPublication = $model->getPropertyValue($document, 'endPublication');
			if (!($endPublication instanceof \DateTime))
			{
				$endPublication = $startPublication->add(new \DateInterval('P50Y'));
			}
			$documentData['endPublication'] =  $endPublication->format(\DateTime::ISO8601);
		}

		return $documentData;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Rbs\Website\Documents\Website $website
	 * @param array $documentData
	 * @param \Rbs\Elasticsearch\Documents\Index $index
	 * @param \Rbs\Elasticsearch\Index\IndexManager $indexManager
	 * @return array
	 */
	public function addPublishableContent($document, $website, array $documentData,
		\Rbs\Elasticsearch\Documents\Index $index = null, \Rbs\Elasticsearch\Index\IndexManager $indexManager = null)
	{
		if (array_key_exists('content', $documentData))
		{
			return $documentData;
		}

		$event = new \Change\Documents\Events\Event('fullTextContent', $document, ['index' => $index, 'indexManager' => $indexManager]);

		$document->getEventManager()->trigger($event);
		$content = $event->getParam('fullTextContent');
		if (!$content)
		{
			$model = $document->getDocumentModel();
			$content = array();
			foreach ($model->getProperties() as $property)
			{
				if ($property->getType() === \Change\Documents\Property::TYPE_RICHTEXT)
				{
					$pv = $property->getValue($document);
					if ($pv instanceof \Change\Documents\RichtextProperty)
					{
						$context = ['website' => $website];
						$text = $event->getApplicationServices()->getRichTextManager()->render($pv, 'Website', $context);
						$text = trim(strip_tags($text, '<p><br>'));
						if ($text)
						{
							$content[] = $text;
						}
					}
				}
			}
		}

		if ($content)
		{
			if (is_array($content)) {
				$content = implode('<br />' . PHP_EOL, $content);
			}
			$documentData['content'] = $content;
		}

		return $documentData;
	}
} 
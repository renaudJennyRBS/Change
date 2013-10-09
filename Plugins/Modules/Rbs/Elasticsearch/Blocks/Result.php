<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Elasticsearch\Blocks\Result
 */
class Result extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('searchText');
		$parameters->addParameterMeta('fulltextIndex');
		$parameters->addParameterMeta('websiteId');
		$parameters->addParameterMeta('allowedSectionIds');
		$parameters->addParameterMeta('itemsPerPage', 10);
		$parameters->addParameterMeta('pageNumber', 1);

		$parameters->setLayoutParameters($event->getBlockLayout());
		$fulltextIndexId = $parameters->getParameter('fulltextIndex');
		if (is_array($fulltextIndexId))
		{
			$fulltextIndexId =  isset($fulltextIndexId['id']) ? $fulltextIndexId['id'] : null;
		}

		$fullTextIndex = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($fulltextIndexId);
		if ($fullTextIndex instanceof \Rbs\Elasticsearch\Documents\FullText && $fullTextIndex->activated())
		{
			$websiteId = $fullTextIndex->getWebsiteId();
			$allowedSectionIds = $event->getPermissionsManager()->getAllowedSectionIds($websiteId);
			$parameters->setParameterValue('websiteId', $websiteId);
			$parameters->setParameterValue('allowedSectionIds', $allowedSectionIds);
		}
		else
		{
			$fulltextIndexId = null;
		}
		$parameters->setParameterValue('fulltextIndex', $fulltextIndexId);

		$request = $event->getHttpRequest();
		$searchText = $request->getQuery('searchText');
		if ($searchText && is_string($searchText))
		{
			$parameters->setParameterValue('searchText', $searchText);
			$parameters->setParameterValue('pageNumber', intval($request->getQuery('pageNumber-' . $event->getBlockLayout()->getId(), 1)));
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$fullTextIndex = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters->getParameter('fulltextIndex'));
		if ($fullTextIndex instanceof \Rbs\Elasticsearch\Documents\FullText && $fullTextIndex->activated())
		{

			$searchText = trim($parameters->getParameter('searchText'), '');
			if ($searchText)
			{
				$allowedSectionIds = $parameters->getParameter('allowedSectionIds');
				$attributes['items'] = array();

				$query = $this->buildQuery($searchText, $allowedSectionIds);

				$attributes['pageNumber'] = $pageNumber = intval($parameters->getParameter('pageNumber'));
				$size = $parameters->getParameter('itemsPerPage');
				$from = ($pageNumber - 1) * $size;

				$query->setFrom($from);
				$query->setSize($size);

				$indexManager = new \Rbs\Elasticsearch\Services\IndexManager();
				$indexManager->setDocumentServices($event->getDocumentServices());

				$client = $indexManager->getClient($fullTextIndex->getClientName());
				if ($client)
				{
					$index = $client->getIndex($fullTextIndex->getName());
					if ($index->exists())
					{
						$searchResult = $index->getType('document')->search($query);
						$attributes['totalCount'] = $searchResult->getTotalHits();
						if ($attributes['totalCount'])
						{
							$maxScore = $searchResult->getMaxScore();
							$attributes['pageCount'] = ceil($attributes['totalCount'] / $size);
							/* @var $result \Elastica\Result */
							foreach ($searchResult->getResults() as $result)
							{
								$score = ceil(($result->getScore() / $maxScore) * 100);
								$attributes['items'][] = array('id' => $result->getId(), 'score' => $score, 'title' => $result->title);
							}
						}
					}
				}
			}
			else
			{
				$attributes['items'] = false;
			}
			return 'result.twig';
		}
		return null;
	}

	/**
	 * @param integer $pageNumber
	 * @param integer $pageCount
	 * @return integer
	 */
	protected function fixPageNumber($pageNumber, $pageCount)
	{
		if (!is_numeric($pageNumber) || $pageNumber < 1 || $pageNumber > $pageCount)
		{
			return 1;
		}
		return $pageNumber;
	}

	/**
	 * @param string $searchText
	 * @param integer[] $allowedSectionIds
	 * @return \Elastica\Query
	 */
	protected function buildQuery($searchText, $allowedSectionIds)
	{
		$now = (new \DateTime())->format(\DateTime::ISO8601);
		$multiMatch = new \Elastica\Query\MultiMatch();
		$multiMatch->setQuery($searchText);
		$multiMatch->setFields(array('title', 'content'));

		$bool = new \Elastica\Filter\Bool();
		$bool->addMust(new \Elastica\Filter\Range('startPublication', array('lte' => $now)));
		$bool->addMust(new \Elastica\Filter\Range('endPublication', array('gt' => $now)));

		if (is_array($allowedSectionIds))
		{
			$bool->addMust(new \Elastica\Filter\Terms('canonicalSectionId', $allowedSectionIds));
		}
		$filtered = new \Elastica\Query\Filtered($multiMatch, $bool);

		$query = new \Elastica\Query($filtered);
		$query->setFields(array('model', 'title'));

		return $query;
	}
}
<?php
namespace Rbs\Elasticsearch\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Elasticsearch\Http\Web\Suggest
 */
class Suggest extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		$result = array('items' => array());
		$searchText = $event->getRequest()->getQuery('searchText', '');
		if ($searchText)
		{
			//Elastic search special chars
			$t = str_replace(array('+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\',
				'/'), ' ', $searchText);

			//Other special chars
			$t = str_replace(array(',', '.'), ' ', $t);

			$t = array_map('trim', explode(' ', $t));
			$t = array_reduce($t, function ($r, $i)
			{
				if (mb_strlen($i) > 2)
				{
					$r[] = mb_substr($i, 0, 15);
				}
				return $r;
			}, array());
			$terms = array_unique($t);
			$result['terms'] = $terms; //array_map('utf8_encode', $terms);
			if (count($terms))
			{
				$LCID = $event->getRequest()->getLCID();
				$website = $event->getWebsite();
				$result['LCID'] = $LCID;

				$genericServices = $event->getServices('genericServices');
				if ($genericServices instanceof \Rbs\Generic\GenericServices)
				{
					$indexManager = $genericServices->getIndexManager();
					$indexDef = $indexManager->findIndexDefinitionByMapping('fulltext', $LCID, array('website' => $website));
					if ($indexDef)
					{
						$result['client'] = $indexDef->getClientName();
						$result['indexName'] = $indexDef->getName();
						$client = $indexManager->getClient($indexDef->getClientName());
						if ($client)
						{
							$index = $client->getIndex($indexDef->getName());
							$q = $this->buildQuery($terms);
							$result['q'] = $q->toArray();
							$resultSet = $index->getType($indexDef->getDefaultTypeName())->search($this->buildQuery($terms));
							if ($resultSet->count())
							{
								$items = array();
								foreach ($resultSet->getResults() as $r)
								{
									$items[] = $r->title;
								}
								$result['items'] = array_values(array_unique($items));
							}
						}
					}
				}
			}
		}
		$event->setResult($this->getNewAjaxResult($result));
	}

	/**
	 * @param string[] $terms
	 * @return \Elastica\Query
	 */
	protected function buildQuery($terms)
	{
		$bool = new \Elastica\Query\Bool();
		foreach ($terms as $term)
		{
			$bool->addShould(new \Elastica\Query\Field('title.autocomplete', $term));
		}
		$bool->setMinimumNumberShouldMatch(max(1, count($terms) - 1));
		$query = new \Elastica\Query($bool);
		$query->setFrom(0);
		$query->setSize(20);
		$query->setFields(array('title'));
		return $query;
	}
}
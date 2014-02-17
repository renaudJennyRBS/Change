<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\DocumentCollection;
use Change\Documents\Property;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetDocumentModelCollection
 */
class GetDocumentCollection
{
	/**
	 * Use Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$modelName = $event->getParam('modelName');
		$model = $event->getApplicationServices()->getModelManager()->getModelByName($modelName);
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}
		$this->generateResult($event, $model);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function executeFiltered($event)
	{
		$modelName = $event->getParam('modelName');
		$model = $event->getApplicationServices()->getModelManager()->getModelByName($modelName);
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}
		$this->generateResult($event, $model);
	}

	/**
	 * @param CollectionResult $result
	 * @return array
	 */
	protected function buildQueryArray($result)
	{
		$array = array('limit' => $result->getLimit(), 'offset' => $result->getOffset());
		if ($result->getSort())
		{
			$array['sort'] = $result->getSort();
			$array['desc'] = ($result->getDesc()) ? 'true' : 'false';
		}
		return $array;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Http\Rest\Result\CollectionResult
	 */
	protected function generateResult($event, $model)
	{
		$urlManager = $event->getUrlManager();
		$request = $event->getRequest();
		$params = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		$result = $this->getNewResult($params);

		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$selfLink->setQuery($this->buildQueryArray($result));
		$result->addLink($selfLink);
		if ($model->isStateless())
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$result->setCount(0);
			$event->setResult($result);
			return $result;
		}

		$docQuery = $event->getApplicationServices()->getDocumentManager()->getNewQuery($model);
		if (isset($params['filter']) && is_array($params['filter']))
		{
			$event->getApplicationServices()->getModelManager()->applyDocumentFilter($docQuery, $params['filter']);
		}
		$result->setCount($docQuery->getCountDocuments());

		if ($result->getOffset())
		{
			$prevLink = clone($selfLink);
			$prevOffset = max(0, $result->getOffset() - $result->getLimit());
			$query = $this->buildQueryArray($result);
			$query['offset'] = $prevOffset;
			$prevLink->setQuery($query);
			$prevLink->setRel('prev');
			$result->addLink($prevLink);
		}

		if ($result->getCount())
		{
			if ($result->getCount() > $result->getOffset() + $result->getLimit())
			{
				$nextLink = clone($selfLink);
				$nextOffset = min($result->getOffset() + $result->getLimit(), $result->getCount() - 1);
				$query = $this->buildQueryArray($result);
				$query['offset'] = $nextOffset;
				$nextLink->setQuery($query);
				$nextLink->setRel('next');
				$result->addLink($nextLink);
			}

			$sortInfo = explode('.', $result->getSort());
			if (count($sortInfo))
			{
				$childBuilder = null;
				$property = $model->getProperty(array_shift($sortInfo));
				if ($property && !$property->getStateless())
				{
					$orderColumn = null;
					$sortProperty = null;
					if (count($sortInfo) && $property->getType() === Property::TYPE_DOCUMENT)
					{
						$childBuilder = $docQuery->getPropertyBuilder($property);
						$sortModel = $childBuilder->getModel();
						$sortPropertyName = array_shift($sortInfo);
						if ($sortModel && !$sortModel->isStateless() && $sortModel->hasProperty($sortPropertyName))
						{
							$sortProperty = $sortModel->getProperty($sortPropertyName);
							if ($sortProperty->getStateless())
							{
								$sortProperty = null;
							}
						}
					}
					else
					{
						$sortProperty = $property;
					}

					if ($sortProperty)
					{
						$docQuery->addOrder($property->getName(), !$result->getDesc(), $childBuilder);
					}
				}
			}

			$qb = $docQuery->dbQueryBuilder();

			$fb = $qb->getFragmentBuilder();
			$qb->addColumn($fb->alias($fb->getDocumentColumn('id', $docQuery->getTableAliasName()), 'id'))
				->addColumn($fb->alias($fb->getDocumentColumn('model', $docQuery->getTableAliasName()), 'model'));

			$extraColumn =$request->getPost('column', $request->getQuery('column', array()));
			$sc = $qb->query();
			$sc->setMaxResults($result->getLimit());
			$sc->setStartIndex($result->getOffset());
			$collection = new DocumentCollection($event->getApplicationServices()->getDocumentManager(), $sc->getResults());
			foreach ($collection as $document)
			{
				$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, $extraColumn));
			}
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param array $params
	 * @return CollectionResult
	 */
	protected function getNewResult($params)
	{
		$result = new CollectionResult();
		if (isset($params['offset']) && (($offset = intval($params['offset'])) >= 0))
		{
			$result->setOffset($offset);
		}
		if (isset($params['limit']) && (($limit = intval($params['limit'])) > 0))
		{
			$result->setLimit($limit);
		}

		if (isset($params['sort']))
		{
			$result->setSort($params['sort']);
		}

		if (isset($params['desc']))
		{
			$result->setDesc($params['desc']);
			return $result;
		}
		return $result;
	}
}

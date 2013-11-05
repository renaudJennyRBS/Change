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
	 * @var string[]
	 */
	protected $sortablePropertyTypes = array(Property::TYPE_BOOLEAN, Property::TYPE_DATE, Property::TYPE_DECIMAL, Property::TYPE_DATETIME, Property::TYPE_FLOAT, Property::TYPE_INTEGER, Property::TYPE_STRING);

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
	 * @param \Change\Documents\AbstractModel $model
	 * @param \Change\Http\Rest\Result\CollectionResult $result
	 * @param \Change\Documents\ModelManager $mm
	 * @param bool $includeDoc
	 */
	protected function addSortablePropertiesForModel($model, $result, $mm, $parentName = null)
	{
		if ($model instanceof \Change\Documents\AbstractModel)
		{
			foreach ($model->getProperties() as $property)
			{
				if (!$property->getStateless())
				{
					if (in_array($property->getType(), $this->sortablePropertyTypes))
					{
						if (!$property->getLocalized() || $parentName === null)
						{
							// Localized properties are not sortable on sub model
							$name = $parentName ?  $parentName . '.' . $property->getName() : $property->getName();
							$result->addAvailableSort($name);
						}
					}
					else if (!$parentName && $property->getType() === Property::TYPE_DOCUMENT)
					{
						$this->addSortablePropertiesForModel($mm->getModelByName($property->getDocumentType()), $result, $mm, $property->getName());
					}
				}
			}
		}

	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Http\Rest\Result\CollectionResult
	 */
	protected function generateResult($event, $model)
	{
		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();
		if (($offset = $event->getRequest()->getQuery('offset')) !== null)
		{
			$result->setOffset(intval($offset));
		}
		if (($limit = $event->getRequest()->getQuery('limit')) !== null)
		{
			$result->setLimit(intval($limit));
		}
		if (($sort = $event->getRequest()->getQuery('sort')) !== null)
		{
			$result->setSort($sort);
		}

		if (($desc = $event->getRequest()->getQuery('desc')) !== null)
		{
			$result->setDesc($desc);
		}


		$this->addSortablePropertiesForModel($model, $result,  $event->getApplicationServices()->getModelManager());

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

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$table = $fb->getDocumentTable($model->getRootName());

		$qb->select()->addColumn($fb->alias($fb->func('count', $fb->getDocumentColumn('id')), 'count'))
			->from($table);

		if ($model->hasDescendants())
		{
			$qb->where($fb->in($fb->getDocumentColumn('model'), $model->getName(), $model->getDescendantsNames()));
		}
		else
		{
			$qb->where($fb->eq($fb->getDocumentColumn('model'), $fb->string($model->getName())));
		}

		$sc = $qb->query();

		$row = $sc->getFirstResult();
		if ($row && $row['count'])
		{
			$result->setCount(intval($row['count']));
		}

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

			$qb->select()
				->addColumn($fb->alias($fb->getDocumentColumn('id', $table), 'id'))
				->addColumn($fb->alias($fb->getDocumentColumn('model', $table), 'model'))
				->from($table);

			if ($model->hasDescendants())
			{
				$qb->where($fb->in($fb->getDocumentColumn('model', $table), $model->getName(), $model->getDescendantsNames()));
			}
			else
			{
				$qb->where($fb->eq($fb->getDocumentColumn('model', $table), $fb->string($model->getName())));
			}

			$sortInfo = explode('.', $result->getSort());
			if (count($sortInfo))
			{
				$property = $model->getProperty(array_shift($sortInfo));
				if ($property)
				{
					$orderColumn = null;
					if (count($sortInfo) && $property->getType() === Property::TYPE_DOCUMENT)
					{
						$sortModel = $event->getApplicationServices()->getModelManager()->getModelByName($property->getDocumentType());
						$sortPropertyName = array_shift($sortInfo);
						if ($sortModel && $sortModel->isEditable() && $sortModel->hasProperty($sortPropertyName))
						{
							$sortProperty = $sortModel->getProperty($sortPropertyName);
							if (!$sortProperty->getLocalized())
							{
								// Join on model table
								$modelTable = $fb->getDocumentTable($sortModel->getRootName());
								$qb->innerJoin($modelTable, $fb->eq(
									$fb->getDocumentColumn($property->getName(), $table),
									$fb->getDocumentColumn('id', $modelTable)
								));
								$orderColumn = $fb->getDocumentColumn($sortPropertyName, $modelTable);
							}
						}
					}
					else
					{
						if ($property->getLocalized())
						{
							$LCID = $event->getRequest()->getLCID();
							$i18nTable = $fb->getDocumentI18nTable($model->getRootName());

							$qb->leftJoin($i18nTable,
								$fb->logicAnd(
									$fb->eq($fb->getDocumentColumn('id', $table), $fb->getDocumentColumn('id', $i18nTable)),
									$fb->eq($fb->getDocumentColumn('LCID', $i18nTable), $fb->string($LCID))
								)
							);
						}

						if ($property->getName() == 'id' || $property->getName() == 'model')
						{
							$orderColumn = $fb->column($property->getName());
						}
						else
						{
							$orderColumn = $fb->getDocumentColumn($property->getName());
						}
					}


					if ($orderColumn)
					{
						if ($result->getDesc())
						{
							$qb->orderDesc($orderColumn);
						}
						else
						{
							$qb->orderAsc($orderColumn);
						}
					}

				}


			}
			$extraColumn = $event->getRequest()->getQuery('column', array());
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
}

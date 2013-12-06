<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\Property;
use Change\Http\Rest\Result\Link;
use Change\Http\Rest\Result\ModelLink;
use Change\Http\Rest\Result\ModelResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetModelInformation
 */
class GetModelInformation
{
	protected $sortablePropertyTypes = array(Property::TYPE_BOOLEAN, Property::TYPE_DATE, Property::TYPE_DECIMAL, Property::TYPE_DATETIME, Property::TYPE_FLOAT, Property::TYPE_INTEGER, Property::TYPE_STRING);

	/**
	 * Use Required Event Params: documentId, modelName
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$modelName = $event->getParam('modelName');
		if ($modelName)
		{
			$this->generateResult($event, $modelName);
		}
		return;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string $modelName
	 */
	protected function generateResult($event, $modelName)
	{
		$i18nm = $event->getApplicationServices()->getI18nManager();
		$mm = $event->getApplicationServices()->getModelManager();
		$model = $mm->getModelByName($modelName);
		if ($model)
		{
			$result = new ModelResult($event->getUrlManager());
			$result->addLink(new ModelLink($event->getUrlManager(), array('name' => $model->getName()), false));

			// Ancestors and descendants.
			foreach ($model->getDescendantsNames() as $descendantName)
			{
				$link = new ModelLink($event->getUrlManager(),
					array('name' => $descendantName), false);
				$link->setRel('descendant');
				$result->addLink($link);
			}
			if ($model->getParentName())
			{
				$parentName = $model->getParentName();
				$rootName = $model->getRootName();
				foreach ($model->getAncestorsNames() as $ancestorName)
				{
					$link = new ModelLink($event->getUrlManager(),
						array('name' => $ancestorName), false);
					if ($parentName == $ancestorName)
					{
						$link->setRel('extends');
					}
					elseif ($rootName == $ancestorName)
					{
						$link->setRel('root');
					}
					else
					{
						$link->setRel('ancestor');
					}
					$result->addLink($link);
				}
			}
			if (is_string($model->getTreeName()))
			{
				$pnl = new Link($event->getUrlManager(),
					'resourcestree/' . str_replace('_', '/', $model->getTreeName()) . '/', 'tree');
				$result->addLink($pnl);
			}
			$pnl = new Link($event->getUrlManager(),
				'resources/' . str_replace('_', '/', $model->getName()) . '/', 'collection');
			$result->addLink($pnl);

			// Meta.
			$result->setMeta('name', $model->getName());
			$result->setMeta('label', $i18nm->trans($model->getLabelKey(), array('ucf')));
			$result->setMeta('icon', $model->getIcon());
			$result->setMeta('parentName', $model->getParentName());
			$result->setMeta('rootName', $model->getRootName());
			$result->setMeta('treeName', $model->getTreeName());
			$result->setMeta('editable', $model->isEditable());
			$result->setMeta('indexable', $model->isIndexable());
			$result->setMeta('backofficeIndexable', $model->isBackofficeIndexable());
			$result->setMeta('frontofficeIndexable', $model->isFrontofficeIndexable());
			$result->setMeta('localized', $model->isLocalized());
			$result->setMeta('publishable', $model->isPublishable());
			$result->setMeta('activable', $model->isActivable());
			$result->setMeta('stateless', $model->isStateless());
			$result->setMeta('abstract', $model->isAbstract());
			$result->setMeta('useCorrection', $model->useCorrection());

			// Properties.
			foreach ($model->getProperties() as $property)
			{
				$infos = array();
				$infos['label'] = $i18nm->trans($model->getPropertyLabelKey($property->getName()), array('ucf'));
				$infos['type'] = $property->getType();
				if ($property->getDocumentType())
				{
					$infos['documentType'] = $property->getDocumentType();
				}
				$infos['indexed'] = $property->getIndexed();
				$infos['localized'] = $property->getLocalized();
				$infos['stateless'] = $property->getStateless();
				$infos['required'] = $property->getRequired();
				$infos['hasCorrection'] = $property->getHasCorrection();
				if ($property->getDefaultValue() !== null)
				{
					$infos['defaultValue'] = $property->getDefaultValue();
				}
				if ($infos['type'] == \Change\Documents\Property::TYPE_DOCUMENTARRAY)
				{
					$infos['minOccurs'] = $property->getMinOccurs();
					$infos['maxOccurs'] = $property->getMaxOccurs();
				}
				if ($property->hasConstraints())
				{
					$infos['constraints'] = $property->getConstraintArray();
				}
				$result->setProperty($property->getName(), $infos);
			}

			$this->addSortablePropertiesForModel($model, $result, $mm);

			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
		}
	}

	/**
	 * @param \Change\Documents\AbstractModel $model
	 * @param \Change\Http\Rest\Result\ModelResult $result
	 * @param \Change\Documents\ModelManager $mm
	 * @param string $parentName
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
							$result->setSortableBy($name);
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

}

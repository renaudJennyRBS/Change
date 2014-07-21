<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Models;

use Change\Documents\Property;
use Change\Http\Rest\V1\Link;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\V1\Models\GetModelInformation
 */
class GetModelInformation
{
	protected $sortablePropertyTypes = [Property::TYPE_BOOLEAN, Property::TYPE_DATE, Property::TYPE_DECIMAL,
		Property::TYPE_DATETIME, Property::TYPE_FLOAT, Property::TYPE_INTEGER, Property::TYPE_STRING];
	protected $ignoreDefaultValues = ['id', 'model', 'refLCID', 'LCID', 'authorName', 'authorId', 'modificationDate',
		'creationDate', 'documentVersion'];

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

			if (!$model->isInline())
			{
				$pnl = new Link($event->getUrlManager(),
					'resources/' . str_replace('_', '/', $model->getName()) . '/', 'collection');
				$result->addLink($pnl);
			}

			// Meta.
			$result->setMeta('name', $model->getName());
			$result->setMeta('label', $i18nm->trans($model->getLabelKey(), array('ucf')));
			$result->setMeta('parentName', $model->getParentName());
			$result->setMeta('rootName', $model->getRootName());
			$result->setMeta('treeName', $model->getTreeName());
			$result->setMeta('editable', $model->isEditable());
			$result->setMeta('inline', $model->isInline());
			$result->setMeta('localized', $model->isLocalized());
			$result->setMeta('publishable', $model->isPublishable());
			$result->setMeta('activable', $model->isActivable());
			$result->setMeta('stateless', $model->isStateless());
			$result->setMeta('abstract', $model->isAbstract());
			$result->setMeta('useCorrection', $model->useCorrection());

			$newInstance = null;
			if (!$model->isAbstract())
			{
				if ($model->isInline())
				{
					$newInstance = $event->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModel($model);
				}
				else
				{
					$newInstance = $event->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModel($model);
				}

			}
			// Properties.
			foreach ($model->getProperties() as $property)
			{
				if ($property->getInternal())
				{
					continue;
				}
				$infos = array();
				$infos['label'] = $i18nm->trans($model->getPropertyLabelKey($property->getName()), array('ucf'));
				$infos['type'] = $property->getType();
				if ($property->getDocumentType())
				{
					$infos['documentType'] = $property->getDocumentType();
				}
				if ($property->getInlineType())
				{
					$infos['inlineType'] = $property->getInlineType();
				}
				$infos['localized'] = $property->getLocalized();
				$infos['stateless'] = $property->getStateless();
				$infos['required'] = $property->getRequired();
				$infos['hasCorrection'] = $property->getHasCorrection();
				if ($newInstance && !in_array($property->getName(), $this->ignoreDefaultValues))
				{
					$converter = new \Change\Http\Rest\V1\PropertyConverter($newInstance, $property);
					$infos['defaultValue'] = $converter->convertToRestValue($property->getValue($newInstance));
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
	 * @param \Change\Http\Rest\V1\Models\ModelResult $result
	 * @param \Change\Documents\ModelManager $mm
	 * @param string $parentName
	 */
	protected function addSortablePropertiesForModel($model, $result, $mm, $parentName = null)
	{
		if ($model instanceof \Change\Documents\AbstractModel && !$model->isInline())
		{
			foreach ($model->getProperties() as $property)
			{
				if ($property->getStateless() || $property->getInternal())
				{
					continue;
				}

				if (in_array($property->getType(), $this->sortablePropertyTypes))
				{
					if (!$property->getLocalized() || $parentName === null)
					{
						// Localized properties are not sortable on sub model
						$name = $parentName ? $parentName . '.' . $property->getName() : $property->getName();
						$result->setSortableBy($name);
					}
				}
				else if (!$parentName && $property->getType() === Property::TYPE_DOCUMENT)
				{
					$this->addSortablePropertiesForModel($mm->getModelByName($property->getDocumentType()), $result, $mm,
						$property->getName());
				}
			}
		}
	}
}

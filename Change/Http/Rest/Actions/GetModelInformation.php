<?php
namespace Change\Http\Rest\Actions;

use Change\Http\Rest\Result\ModelLink;
use Change\Http\Rest\Result\ModelResult;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetModelInformation
 */
class GetModelInformation
{
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
	 * @return \Change\Http\Rest\Result\DocumentResult
	 */
	protected function generateResult($event, $modelName)
	{
		$i18nm = $event->getApplicationServices()->getI18nManager();
		$mm = $event->getDocumentServices()->getModelManager();
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
			$result->setMeta('stateless', $model->isStateless());

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

			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
			return $result;
		}
	}
}

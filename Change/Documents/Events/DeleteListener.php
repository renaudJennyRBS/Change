<?php
namespace Change\Documents\Events;

use Change\Documents\AbstractDocument;
use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Interfaces\Localizable;

/**
 * @name \Change\Documents\Events\DeleteListener
 */
class DeleteListener
{
	/**
	 * @param DocumentEvent $event
	 */
	public function onDelete($event)
	{
		if ($event instanceof DocumentEvent)
		{
			$document = $event->getDocument();
			if (!$document->getDocumentModel()->isStateless())
			{
				$backupData = $this->generateBackupData($document);
				if (count($backupData))
				{
					try
					{
						$document->getDocumentManager()->insertDocumentBackup($document, $backupData);
					}
					catch (\Exception $e)
					{
						//Unable to backup document
						$document->getDocumentServices()->getApplicationServices()->getLogging()->exception($e);
					}
				}
			}
		}
	}

	/**
	 * @param AbstractDocument $document
	 * @return array
	 */
	protected function generateBackupData($document)
	{
		$datas = array();
		$datas['metas'] = $document->getMetas();
		$model = $document->getDocumentModel();

		if ($model->useTree() && $document->getTreeName())
		{
			$node = $document->getDocumentServices()->getTreeManager()->getNodeByDocument($document);
			if ($node)
			{
				$datas['treeName'] = array($document->getTreeName(), $node->getParentId());
			}
		}

		$localized = array();
		foreach ($model->getProperties() as $propertyName => $property)
		{
			/* @var $property \Change\Documents\Property */
			if ($property->getLocalized())
			{
				$localized[$propertyName] = $property;
				continue;
			}
			$val = $property->getValue($document);
			if ($val instanceof AbstractDocument)
			{
				$datas[$propertyName] = array($val->getId(), $val->getDocumentModelName());
			}
			elseif ($val instanceof \DateTime)
			{
				$datas[$propertyName] = $val->format('c');
			}
			elseif (is_array($val))
			{
				foreach ($val as $doc)
				{
					if ($doc instanceof AbstractDocument)
					{
						$datas[$propertyName][] = array($doc->getId(), $doc->getDocumentModelName());
					}
				}
			}
			else
			{
				$datas[$propertyName] = $val;
			}
		}

		$dm = $document->getDocumentServices()->getDocumentManager();

		if (count($localized) && $document instanceof Localizable)
		{
			$datas['LCID'] = array();
			foreach ($document->getLocalizableFunctions()->getLCIDArray() as $LCID)
			{
				$dm->pushLCID($LCID);
				$datas['LCID'][$LCID] = array();
				foreach ($localized as $propertyName => $property)
				{
					/* @var $property \Change\Documents\Property */
					$val = $property->getValue($document);
					if ($val instanceof \DateTime)
					{
						$datas[$propertyName] = $val->format('c');
					}
					else
					{
						$datas['LCID'][$LCID][$propertyName] = $val;
					}
				}
				$dm->popLCID();
			}
		}
		return $datas;
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onDeleted($event)
	{
		if (!($event instanceof DocumentEvent))
		{
			return;
		}

		$document = $event->getDocument();
		if ($document === null|| ($model = $document->getDocumentModel()) === null || $model->isStateless())
		{
			return;
		}
		$documentServices = $document->getDocumentServices();

		//Remove TreeNode
		$documentServices->getTreeManager()->deleteDocumentNode($document);

		//Remove Metas
		$documentServices->getDocumentManager()->saveMetas($document, null);

		//Remove Relations
		$dbp = $documentServices->getApplicationServices()->getDbProvider();
		$modificationDate = new \DateTime();
		foreach ($model->getInverseProperties() as $property)
		{
			$relModel = $documentServices->getModelManager()->getModelByName($property->getRelatedDocumentType());
			if ($relModel)
			{
				$relProp = $relModel->getProperty($property->getRelatedPropertyName());
				if ($relProp)
				{
					if ($relProp->getType() === \Change\Documents\Property::TYPE_DOCUMENTARRAY)
					{
						//Decrement counter in document table
						$subSelect = $dbp->getNewQueryBuilder();
						$fb = $subSelect->getFragmentBuilder();

						$subSelect->select($fb->getDocumentColumn('id'));
						$subSelect->from($fb->getDocumentRelationTable($relModel->getRootName()));
						$subSelect->where(
							$fb->logicAnd(
								$fb->eq($fb->getDocumentColumn('relatedid'), $fb->number($document->getId())),
								$fb->eq($fb->getDocumentColumn('relname'), $fb->string($relProp->getName()))
							)
						);
						$subQuery = $subSelect->query();

						if ($relModel->isLocalized())
						{
							$qb = $dbp->getNewStatementBuilder();
							$qb->update($fb->getDocumentI18nTable($relModel->getRootName()));
							$qb->assign($fb->getDocumentColumn('modificationDate'), $fb->dateTimeParameter('modificationDate', $qb));
							$qb->where($fb->in($fb->getDocumentColumn('id'), $fb->subQuery($subQuery)));
							$uq = $qb->updateQuery();
							$uq->bindParameter('modificationDate', $modificationDate);
							$uq->execute();
						}

						$qb = $dbp->getNewStatementBuilder();
						$qb->update($fb->getDocumentTable($relModel->getRootName()));
						$column = $fb->getDocumentColumn($relProp->getName());

						$qb->assign($column, $fb->subtraction($column, $fb->number(1)));
						if (!$relModel->isLocalized())
						{
							$qb->assign($fb->getDocumentColumn('modificationDate'), $fb->dateTimeParameter('modificationDate', $qb));
						}
						$qb->where(
							$fb->logicAnd(
								$fb->gte($column, $fb->number(1)),
								$fb->in($fb->getDocumentColumn('id'), $fb->subQuery($subQuery))
							)
						);

						$uq = $qb->updateQuery();
						if (!$relModel->isLocalized())
						{
							$uq->bindParameter('modificationDate', $modificationDate);
						}
						$uq->execute();

						//Delete relation
						$qb = $dbp->getNewStatementBuilder();
						$qb->delete($fb->getDocumentRelationTable($relModel->getRootName()));
						$qb->where(
							$fb->logicAnd(
								$fb->eq($fb->getDocumentColumn('relatedid'), $fb->number($document->getId())),
								$fb->eq($fb->getDocumentColumn('relname'), $fb->string($relProp->getName()))
							)
						);
						$qb->deleteQuery()->execute();
					}
					elseif ($relProp->getType() === \Change\Documents\Property::TYPE_DOCUMENT)
					{

						if ($relModel->isLocalized())
						{
							$subSelect = $dbp->getNewQueryBuilder();
							$fb = $subSelect->getFragmentBuilder();
							$column = $fb->getDocumentColumn($relProp->getName());

							$subSelect->select($fb->getDocumentColumn('id'));
							$subSelect->from($fb->getDocumentTable($relModel->getRootName()));
							$subSelect->where($fb->eq($column, $fb->number($document->getId())));
							$subQuery = $subSelect->query();

							$qb = $dbp->getNewStatementBuilder();
							$qb->update($fb->getDocumentI18nTable($relModel->getRootName()));
							$qb->assign($fb->getDocumentColumn('modificationDate'), $fb->dateTimeParameter('modificationDate', $qb));
							$qb->where($fb->in($fb->getDocumentColumn('id'), $fb->subQuery($subQuery)));
							$uq = $qb->updateQuery();
							$uq->bindParameter('modificationDate', $modificationDate);
							$uq->execute();
						}

						$qb = $dbp->getNewStatementBuilder();
						$fb = $qb->getFragmentBuilder();
						$qb->update($fb->getDocumentTable($relModel->getRootName()));
						$column = $fb->getDocumentColumn($relProp->getName());
						$qb->assign($column, $fb->number(null));
						if (!$relModel->isLocalized())
						{
							$qb->assign($fb->getDocumentColumn('modificationDate'), $fb->dateTimeParameter('modificationDate', $qb));
						}
						$qb->where($fb->eq($column, $fb->number($document->getId())));
						$uq = $qb->updateQuery();
						if (!$relModel->isLocalized())
						{
							$uq->bindParameter('modificationDate', $modificationDate);
						}

						$uq->execute();
					}
				}
			}
		}
	}
}
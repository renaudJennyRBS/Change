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
				$applicationServices = $event->getApplicationServices();
				$backupData = $this->generateBackupData($document, $applicationServices);
				if (count($backupData))
				{
					try
					{
						$applicationServices->getDocumentManager()->insertDocumentBackup($document, $backupData);
					}
					catch (\Exception $e)
					{
						//Unable to backup document
						$applicationServices->getLogging()->exception($e);
					}
				}
			}
		}
	}

	/**
	 * @param AbstractDocument|\Change\Documents\Traits\DbStorage $document
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @return array
	 */
	protected function generateBackupData($document, $applicationServices)
	{
		$datas = array();
		$datas['metas'] = $document->getMetas();
		$model = $document->getDocumentModel();

		if ($model->useTree() && $document->getTreeName())
		{
			$node = $applicationServices->getTreeManager()->getNodeByDocument($document);
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

		$dm = $applicationServices->getDocumentManager();

		if (count($localized) && $document instanceof Localizable)
		{
			$datas['LCID'] = array();
			foreach ($document->getLCIDArray() as $LCID)
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
		if ($document === null || ($model = $document->getDocumentModel()) === null || $model->isStateless())
		{
			return;
		}
		$applicationServices = $event->getApplicationServices();

		//Remove TreeNode
		$applicationServices->getTreeManager()->deleteDocumentNode($document);

		$jobManager = $applicationServices->getJobManager();
		$jobManager->createNewJob('Change_Document_CleanUp',
			array('id' => $document->getId(), 'model' => $document->getDocumentModelName()));
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onLocalizedDeleted($event)
	{
		if (!($event instanceof DocumentEvent))
		{
			return;
		}

		$document = $event->getDocument();
		if ($document === null || ($model = $document->getDocumentModel()) === null || $model->isStateless())
		{
			return;
		}

		$applicationServices = $event->getApplicationServices();
		$jobManager = $applicationServices->getJobManager();
		$jobManager->createNewJob('Change_Document_LocalizedCleanUp',
			array('id' => $document->getId(), 'model' => $document->getDocumentModelName(),
				'LCID' => $applicationServices->getDocumentManager()->getLCID()));

	}

	public function onCleanUp(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();

		$documentId = $job->getArgument('id');
		$modelName = $job->getArgument('model');
		if (!is_numeric($documentId) || !is_string($modelName))
		{
			$event->failed('Invalid Arguments ' . $documentId . ', ' . $modelName);
			return;
		}

		$model = $applicationServices->getModelManager()->getModelByName($modelName);
		if (!$model)
		{
			$event->failed('Document Model ' . $modelName . ' not found');
			return;
		}

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$dbp = $applicationServices->getDbProvider();

			//Remove Relations
			foreach($model->getProperties() as $property)
			{
				if ($property->getType() === \Change\Documents\Property::TYPE_DOCUMENTARRAY)
				{
					$qb = $dbp->getNewStatementBuilder();
					$fb = $qb->getFragmentBuilder();
					$qb->delete($fb->getDocumentRelationTable($model->getRootName()));
					$qb->where($fb->eq($fb->getDocumentColumn('id'), $fb->number($documentId)));
					$qb->deleteQuery()->execute();
					break;
				}
			}

			//Remove Inverse Relations
			$modificationDate = new \DateTime();
			foreach ($model->getInverseProperties() as $property)
			{
				$relModel = $applicationServices->getModelManager()->getModelByName($property->getRelatedDocumentType());
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
									$fb->eq($fb->getDocumentColumn('relatedid'), $fb->number($documentId)),
									$fb->eq($fb->getDocumentColumn('relname'), $fb->string($relProp->getName()))
								)
							);
							$subQuery = $subSelect->query();

							if ($relModel->isLocalized())
							{
								$qb = $dbp->getNewStatementBuilder();
								$fb = $qb->getFragmentBuilder();
								$qb->update($fb->getDocumentI18nTable($relModel->getRootName()));
								$qb->assign($fb->getDocumentColumn('modificationDate'),
									$fb->dateTimeParameter('modificationDate'));
								$qb->where($fb->in($fb->getDocumentColumn('id'), $fb->subQuery($subQuery)));
								$uq = $qb->updateQuery();
								$uq->bindParameter('modificationDate', $modificationDate);
								$uq->execute();
							}

							$qb = $dbp->getNewStatementBuilder();
							$fb = $qb->getFragmentBuilder();
							$qb->update($fb->getDocumentTable($relModel->getRootName()));
							$column = $fb->getDocumentColumn($relProp->getName());

							$qb->assign($column, $fb->subtraction($column, $fb->number(1)));
							if (!$relModel->isLocalized())
							{
								$qb->assign($fb->getDocumentColumn('modificationDate'),
									$fb->dateTimeParameter('modificationDate'));
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
							$fb = $qb->getFragmentBuilder();
							$qb->delete($fb->getDocumentRelationTable($relModel->getRootName()));
							$qb->where(
								$fb->logicAnd(
									$fb->eq($fb->getDocumentColumn('relatedid'), $fb->number($documentId)),
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
								$subSelect->where($fb->eq($column, $fb->number($documentId)));
								$subQuery = $subSelect->query();

								$qb = $dbp->getNewStatementBuilder();
								$fb = $qb->getFragmentBuilder();
								$qb->update($fb->getDocumentI18nTable($relModel->getRootName()));
								$qb->assign($fb->getDocumentColumn('modificationDate'),
									$fb->dateTimeParameter('modificationDate'));
								$qb->where($fb->in($fb->getDocumentColumn('id'), $fb->subQuery($subQuery)));
								$uq = $qb->updateQuery();
								$uq->bindParameter('modificationDate', $modificationDate);
								$uq->execute();
							}

							$qb = $dbp->getNewStatementBuilder();
							$fb = $qb->getFragmentBuilder();
							$qb->update($fb->getDocumentTable($relModel->getRootName()));
							$column = $fb->getDocumentColumn($relProp->getName());
							$qb->assign($column, $fb->number(0));
							if (!$relModel->isLocalized())
							{
								$qb->assign($fb->getDocumentColumn('modificationDate'),
									$fb->dateTimeParameter('modificationDate'));
							}
							$qb->where($fb->eq($column, $fb->number($documentId)));
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

			if ($model->isPublishable())
			{
				$qb = $dbp->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->delete($fb->table('change_path_rule'));
				$qb->where(
					$fb->logicOr(
						$fb->eq($fb->column('website_id'), $fb->number($documentId)),
						$fb->eq($fb->column('section_id'), $fb->number($documentId)),
						$fb->eq($fb->column('document_id'), $fb->number($documentId)),
						$fb->eq($fb->column('document_alias_id'), $fb->number($documentId))
					));

				$dq = $qb->deleteQuery();
				$dq->execute();
			}

			$event->success();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}
}
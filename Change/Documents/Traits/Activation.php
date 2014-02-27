<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Traits;

/**
 * @name \Change\Documents\Traits\Activation
 * 
 * From \Change\Documents\AbstractDocument
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method \Change\Db\DbProvider getDbProvider()
 */
trait Activation
{
	/**
	 * @return boolean
	 */
	protected function getCurrentActiveState()
	{
		return $this->getDocumentModel()->getPropertyValue($this, 'active');
	}
	
	/**
	 * @return \DateTime|null
	 */
	protected function getCurrentStartActivation()
	{
		return $this->getDocumentModel()->getPropertyValue($this, 'startActivation');
	}

	/**
	 * @return \DateTime|null
	 */
	protected function getCurrentEndActivation()
	{
		return $this->getDocumentModel()->getPropertyValue($this, 'endActivation');
	}
	
	/**
	 * @param \DateTime $at
	 * @return boolean
	 */
	public function activated(\DateTime $at = null)
	{
		if ($this->getCurrentActiveState())
		{
			$st = $this->getCurrentStartActivation();
			$ep = $this->getCurrentEndActivation();
			$test = ($at === null) ? new \DateTime() : $at;
			return (null === $st || $st <= $test) && (null === $ep || $test < $ep);
		}
		return false;
	}
	
	/**
	 * @api
	 * @param boolean $newActivationStatus 
	 */
	public function updateActivationStatus($newActivationStatus)
	{
		if ($this->getCurrentActiveState() == $newActivationStatus)
		{
			return;
		}

		/** @var $activableDocument \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable|\Change\Documents\Interfaces\Localizable */
		$activableDocument = $this;
		$model = $activableDocument->getDocumentModel();
		$activeProperty = $model->getProperty('active');
		$modifiedProperty = $model->getProperty('modificationDate');
		$now = new \DateTime();

		if ($activableDocument instanceof \Change\Documents\Interfaces\Localizable)
		{
			/** @var $localisation \Change\Documents\AbstractLocalizedDocument */
			$localisation = $activableDocument->getCurrentLocalization();
			if ($localisation->isNew())
			{
				return;
			}

			$activeProperty->setLocalizedValue($localisation, $newActivationStatus);
			$modifiedProperty->setLocalizedValue($localisation, $now);
			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getDocumentI18nTable($model->getRootName()));
			$qb->assign($fb->getDocumentColumn('active'), $fb->parameter('active'));
			$qb->assign($fb->getDocumentColumn('modificationDate'), $fb->dateTimeParameter('modificationDate'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
					$fb->eq($fb->getDocumentColumn('LCID'), $fb->parameter('LCID'))
				)
			);

			$uq = $qb->updateQuery();
			$uq->bindParameter('active', $newActivationStatus);
			$uq->bindParameter('modificationDate', $now);
			$uq->bindParameter('id', $localisation->getId());
			$uq->bindParameter('LCID', $localisation->getLCID());
			$uq->execute();
			$localisation->removeOldPropertyValue('active');
			$localisation->removeOldPropertyValue('modificationDate');
		}
		else
		{
			if ($activableDocument->isNew())
			{
				return;
			}

			$activeProperty->setValue($activableDocument, $newActivationStatus);
			$modifiedProperty->setValue($activableDocument, $now);
			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getDocumentTable($model->getRootName()));
			$qb->assign($fb->getDocumentColumn('active'), $fb->parameter('active'));
			$qb->assign($fb->getDocumentColumn('modificationDate'), $fb->dateTimeParameter('modificationDate'));
			$qb->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
			$uq = $qb->updateQuery();
			$uq->bindParameter('active', $newActivationStatus);
			$uq->bindParameter('modificationDate', $now);
			$uq->bindParameter('id', $activableDocument->getId());
			$uq->execute();
			$activableDocument->removeOldPropertyValue('active');
			$activableDocument->removeOldPropertyValue('modificationDate');
		}

		$modifiedPropertyNames = ['active', 'modificationDate'];
		$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_UPDATED, $activableDocument,
			['modifiedPropertyNames' => $modifiedPropertyNames]);
		$activableDocument->getEventManager()->trigger($event);
	}
} 
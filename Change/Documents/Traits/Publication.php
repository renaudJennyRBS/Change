<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Traits;

use Change\Documents\Interfaces\Publishable;

/**
 * @name \Change\Documents\Traits\Publication
 *
 * From \Change\Documents\AbstractDocument
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method \Change\Db\DbProvider getDbProvider()
 * @method \Change\Presentation\Interfaces\Section[] getPublicationSections()
 */
trait Publication
{
	/**
	 * @return string|null
	 */
	protected function getCurrentPublicationStatus()
	{
		return $this->getDocumentModel()->getPropertyValue($this, 'publicationStatus');
	}

	/**
	 * @param string $publicationStatus
	 * @return $this
	 */
	protected function setCurrentPublicationStatus($publicationStatus)
	{
		$this->getDocumentModel()->setPropertyValue($this, 'publicationStatus', $publicationStatus);
		return $this;
	}

	/**
	 * @return \DateTime|null
	 */
	protected function getCurrentStartPublication()
	{
		return $this->getDocumentModel()->getPropertyValue($this, 'startPublication');
	}

	/**
	 * @return \DateTime|null
	 */
	protected function getCurrentEndPublication()
	{
		return $this->getDocumentModel()->getPropertyValue($this, 'endPublication');
	}

	/**
	 * @see \Change\Documents\Interfaces\Publishable::published
	 * @api
	 * @param \DateTime $at
	 * @return boolean
	 */
	public function published(\DateTime $at = null)
	{
		if (Publishable::STATUS_PUBLISHABLE === $this->getCurrentPublicationStatus())
		{
			$st = $this->getCurrentStartPublication();
			$ep = $this->getCurrentEndPublication();
			$test = ($at === null) ? new \DateTime(): $at;
			return (null === $st || $st <= $test) && (null === $ep || $test < $ep);
		}
		return false;
	}

	/**
	 * @api
	 * @see \Change\Documents\Interfaces\Publishable::isPublishable
	 * Return true if is publishable or a string for reason if is unpublishable
	 * @return string|boolean
	 */
	public function isPublishable()
	{
		return true;
	}

	/**
	 * @api
	 * @see \Change\Documents\Interfaces\Publishable::updatePublicationStatus
	 * @param string $newPublicationStatus
	 */
	public function updatePublicationStatus($newPublicationStatus)
	{
		if ($this->getCurrentPublicationStatus() == $newPublicationStatus)
		{
			return;
		}

		/** @var $publishableDocument \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable|\Change\Documents\Interfaces\Localizable */
		$publishableDocument = $this;
		$model = $publishableDocument->getDocumentModel();
		$statusProperty = $model->getProperty('publicationStatus');
		$modifiedProperty = $model->getProperty('modificationDate');
		$now = new \DateTime();

		if ($publishableDocument instanceof \Change\Documents\Interfaces\Localizable)
		{
			/** @var $localisation \Change\Documents\AbstractLocalizedDocument */
			$localisation = $publishableDocument->getCurrentLocalization();
			if ($localisation->isNew())
			{
				return;
			}

			$statusProperty->setLocalizedValue($localisation, $newPublicationStatus);
			$modifiedProperty->setLocalizedValue($localisation, $now);
			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getDocumentI18nTable($model->getRootName()));
			$qb->assign($fb->getDocumentColumn('publicationStatus'), $fb->parameter('publicationStatus'));
			$qb->assign($fb->getDocumentColumn('modificationDate'), $fb->dateTimeParameter('modificationDate'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')),
					$fb->eq($fb->getDocumentColumn('LCID'), $fb->parameter('LCID'))
				)
			);

			$uq = $qb->updateQuery();
			$uq->bindParameter('publicationStatus', $newPublicationStatus);
			$uq->bindParameter('modificationDate', $now);
			$uq->bindParameter('id', $localisation->getId());
			$uq->bindParameter('LCID', $localisation->getLCID());
			$uq->execute();
			$localisation->removeOldPropertyValue('publicationStatus');
			$localisation->removeOldPropertyValue('modificationDate');
		}
		else
		{
			if ($publishableDocument->isNew())
			{
				return;
			}

			$statusProperty->setValue($publishableDocument, $newPublicationStatus);
			$modifiedProperty->setValue($publishableDocument, $now);
			$qb = $this->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->getDocumentTable($model->getRootName()));
			$qb->assign($fb->getDocumentColumn('publicationStatus'), $fb->parameter('publicationStatus'));
			$qb->assign($fb->getDocumentColumn('modificationDate'), $fb->dateTimeParameter('modificationDate'));
			$qb->where($fb->eq($fb->getDocumentColumn('id'), $fb->integerParameter('id')));
			$uq = $qb->updateQuery();
			$uq->bindParameter('publicationStatus', $newPublicationStatus);
			$uq->bindParameter('modificationDate', $now);
			$uq->bindParameter('id', $publishableDocument->getId());
			$uq->execute();
			$publishableDocument->removeOldPropertyValue('publicationStatus');
			$publishableDocument->removeOldPropertyValue('modificationDate');
		}

		$modifiedPropertyNames = ['publicationStatus', 'modificationDate'];
		$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_UPDATED, $publishableDocument,
			['modifiedPropertyNames' => $modifiedPropertyNames]);
		$publishableDocument->getEventManager()->trigger($event);
	}

	/**
	 * @api
	 * @see \Change\Documents\Interfaces\Publishable::getValidPublicationStatusForCorrection
	 * @return array
	 */
	public function getValidPublicationStatusForCorrection()
	{
		return array(Publishable::STATUS_UNPUBLISHABLE, Publishable::STATUS_PUBLISHABLE, Publishable::STATUS_FROZEN);
	}

	/**
	 * If $website is null return the first section in getPublicationSections
	 * @api
	 * @see \Change\Documents\Interfaces\Publishable::getCanonicalSection
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @return \Change\Presentation\Interfaces\Section|null
	 */
	public function getCanonicalSection(\Change\Presentation\Interfaces\Website $website = null)
	{
		$sections = $this->getPublicationSections();
		if (count($sections) == 0)
		{
			return null;
		}
		if ($website == null)
		{
			return $sections[0];
		}

		foreach ($sections as $section)
		{
			if ($section->getWebsite() === $website)
			{
				return $section;
			}
		}
		return null;
	}
}
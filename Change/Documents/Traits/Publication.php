<?php
namespace Change\Documents\Traits;

use Change\Documents\Interfaces\Publishable;

/**
 * @name \Change\Documents\Traits\Publication
 *
 * From \Change\Documents\AbstractDocument
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method update()
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
		if ($this->getCurrentPublicationStatus() !== $newPublicationStatus)
		{
			$this->setCurrentPublicationStatus($newPublicationStatus);
			$this->update();
		}
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
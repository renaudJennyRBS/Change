<?php
namespace Change\Documents\Traits;

use Change\Documents\Interfaces\Publishable;

/**
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 * @method string getPublicationStatus()
 * @method setPublicationStatus($publicationStatus)
 * @method \DateTime|null getStartPublication()
 * @method \DateTime|null getEndPublication()
 * @method update()
 *
 * @method \Change\Presentation\Interfaces\Section[] getPublicationSections()
 * @name \Change\Documents\Traits\Publication
 */
trait Publication
{
	/**
	 * @see \Change\Documents\Interfaces\Publishable::published
	 * @api
	 * @param \DateTime $at
	 * @return boolean
	 */
	public function published(\DateTime $at = null)
	{
		if (Publishable::STATUS_PUBLISHABLE === $this->getPublicationStatus())
		{
			$st = $this->getStartPublication();
			$ep = $this->getEndPublication();
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
		if ($this->getPublicationStatus() !== $newPublicationStatus)
		{
			$this->setPublicationStatus($newPublicationStatus);
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
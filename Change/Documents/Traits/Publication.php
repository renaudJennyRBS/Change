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
	 * @api
	 * @return boolean
	 */
	public function published()
	{
		if (Publishable::STATUS_PUBLISHABLE === $this->getPublicationStatus())
		{
			$st = $this->getStartPublication();
			$ep = $this->getEndPublication();
			$now = new \DateTime();
			return (null === $st || $st <= $now) && (null === $ep || $now < $ep);
		}
		return false;
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function canStartValidation()
	{
		return ($this->getPublicationStatus() == Publishable::STATUS_DRAFT);
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function startValidation()
	{
		if (!$this->canStartValidation())
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}

		$this->setPublicationStatus(Publishable::STATUS_VALIDATION);
		$this->update();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function canStartPublication()
	{
		return ($this->getPublicationStatus() == Publishable::STATUS_VALIDATION);
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function startPublication()
	{
		if (!$this->canStartPublication())
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}
		$this->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$this->update();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function canDeactivate()
	{
		return ($this->getPublicationStatus() == Publishable::STATUS_PUBLISHABLE);
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function deactivate()
	{
		if (!$this->canDeactivate())
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}
		$this->setPublicationStatus(Publishable::STATUS_FROZEN);
		$this->update();
	}

	/**
	 * @api
	 * @return boolean
	 */
	public function canActivate()
	{
		return ($this->getPublicationStatus() == Publishable::STATUS_FROZEN);
	}

	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function activate()
	{
		if (!$this->canActivate())
		{
			throw new \RuntimeException('Invalid Publication status', 55000);
		}

		$this->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$this->update();
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $preferredWebsite
	 * @return \Change\Presentation\Interfaces\Section|null
	 */
	public function getDefaultSection(\Change\Presentation\Interfaces\Website $preferredWebsite = null)
	{
		$sections = $this->getPublicationSections();

		if (count($sections) == 0)
		{
			return null;
		}

		if ($preferredWebsite == null)
		{
			return $sections[0];
		}

		foreach ($sections as $section)
		{
			if ($section->getWebsite() === $preferredWebsite)
			{
				return $section;
			}
		}
		return $sections[0];
	}
}
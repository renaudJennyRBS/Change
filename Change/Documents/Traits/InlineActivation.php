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
 * @name \Change\Documents\Traits\InlineActivation
 * 
 * From \Change\Documents\AbstractInline
 * @method \Change\Documents\AbstractModel getDocumentModel()
 * @method \Change\Documents\DocumentManager getDocumentManager()
 */
trait InlineActivation
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
	 * @param boolean $newActivationStatus
	 */
	public function updateActivationStatus($newActivationStatus)
	{
		$this->getDocumentModel()->setPropertyValue($this, 'active', $newActivationStatus);
	}
} 
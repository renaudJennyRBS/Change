<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Editable
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 */
interface Editable
{
	/**
	 * @return string
	 */
	public function getLabel();
	
		/**
	 * @param string $label
	 */
	public function setLabel($label);

	/**
	 * @param \Change\User\UserInterface $user
	 * @return $this
	 */
	public function setOwnerUser(\Change\User\UserInterface $user);
}
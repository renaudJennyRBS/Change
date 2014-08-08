<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Profile;

/**
* @name \Rbs\User\Profile\Profile
*/
class Profile extends \Change\User\AbstractProfile
{
	function __construct()
	{
		$this->properties = array(
			'firstName' => '',
			'lastName' => '',
			'fullName' => '',
			'titleCode' => '',
			'birthDate' => null
		);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Rbs_User';
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue($name)
	{
		if ($name == 'fullName')
		{
			$lName = $this->getPropertyValue('lastName');
			$fName = $this->getPropertyValue('firstName');
			if ($lName == null && $fName == null)
			{
				return null;
			}

			$fullName = '';
			if ($lName == null)
			{
				$lName = '';
			}
			if ($fName == null)
			{
				$fName = '';
			}
			if (trim($lName) != '' && trim($fName) != '')
			{
				$fullName = ' ' . $lName;
			}
			return $fName . $fullName;
		}

		return parent::getPropertyValue($name);
	}

	/**
	 * @return string[]
	 */
	public function getPropertyNames()
	{
		return array('firstName', 'lastName', 'fullName', 'titleCode', 'birthDate');
	}
}
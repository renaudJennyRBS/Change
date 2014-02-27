<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Transaction;

/**
 * @name \Change\Transaction\RollbackException
 */
class RollbackException extends \Exception
{
	/**
	 * @param \Exception $previous
	 */
	public function __construct($previous)
	{
		if ($previous !== null)
		{
			parent::__construct("Transaction cancelled: ". $previous->getMessage(), 120000, $previous);
		}
		else
		{
			parent::__construct("Transaction cancelled: Unknown cause", 120000);
		}
	}
}
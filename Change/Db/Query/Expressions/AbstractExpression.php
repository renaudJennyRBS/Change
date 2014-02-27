<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\AbstractExpression
 * @api
 */
abstract class AbstractExpression implements \Change\Db\Query\InterfaceSQLFragment
{
	/**
	 * SQL Specific vendor options.
	 *
	 * @api
	 * @var array
	 */
	protected $options;
	
	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}
	
	/**
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}
}
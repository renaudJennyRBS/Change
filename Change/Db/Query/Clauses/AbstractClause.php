<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Clauses;

/**
 * @name \Change\Db\Query\Clauses\AbstractClause
 * @api
 */
abstract class AbstractClause implements \Change\Db\Query\InterfaceSQLFragment
{
	/**
	 * SQL Specific vendor options.
	 * 
	 * @api 
	 * @var array
	 */
	protected $options;
	
	/**
	 * @api
	 * @var string
	 */
	protected $name;
	
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
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

}
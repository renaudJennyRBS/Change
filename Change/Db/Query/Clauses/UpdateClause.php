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
 * @name \Change\Db\Query\Clauses\UpdateClause
 * @api
 */
class UpdateClause extends AbstractClause
{
	/**
	 * @var \Change\Db\Query\Expressions\Table
	 */
	protected $table;
	
	/**
	 * @param \Change\Db\Query\Expressions\Table $table
	 */
	public function __construct(\Change\Db\Query\Expressions\Table $table = null)
	{
		$this->setName('UPDATE');
		if ($table)  {$this->setTable($table);}
	}
	
	/**
	 * @return \Change\Db\Query\Expressions\Table|null
	 */
	public function getTable()
	{
		return $this->table;
	}
	
	/**
	 * @param \Change\Db\Query\Expressions\Table $table
	 */
	public function setTable(\Change\Db\Query\Expressions\Table $table)
	{
		$this->table = $table;
	}
	
	/**
	 * @api
	 * @throws \RuntimeException
	 */
	public function checkCompile()
	{
		if ($this->getTable() === null)
		{
			throw new \RuntimeException('Table can not be null', 42026);
		}
	}

	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{		
		$this->checkCompile();
		return 'UPDATE ' . $this->getTable()->toSQL92String();
	}
}

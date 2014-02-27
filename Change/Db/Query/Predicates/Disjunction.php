<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Predicates;

/**
 * @name \Change\Db\Query\Predicates\Disjunction
 */
class Disjunction extends \Change\Db\Query\Expressions\AbstractExpression implements InterfacePredicate
{
	/**
	 * @var array
	 */
	protected $arguments;
	
	/**
	 * @param \Change\Db\Query\InterfaceSQLFragment $_
	 */
	public function __construct()
	{
		$this->setArguments(func_get_args());
	}
	
	/**
	 * @return array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}
	
	/**
	 * @param \Change\Db\Query\InterfaceSQLFragment[] $arguments
	 */
	public function setArguments(array $arguments)
	{
		$this->arguments = array_map(function (\Change\Db\Query\InterfaceSQLFragment $item) {return $item;}, $arguments);
	}
	
	/**
	 * @param \Change\Db\Query\InterfaceSQLFragment[] $arguments
	 * @return \Change\Db\Query\Predicates\Conjunction
	 */
	public function addArgument(\Change\Db\Query\InterfaceSQLFragment $argument)
	{
		$this->arguments[] = $argument;
		return $this;
	}
	
	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function toSQL92String()
	{
		if (!count($this->arguments))
		{
			throw new \RuntimeException('Arguments can not be empty', 42034);
		}
		return '(' . implode(' OR ', array_map(function(\Change\Db\Query\InterfaceSQLFragment $item) {
			return $item->toSQL92String();
		}, $this->arguments)) . ')';
	}
}
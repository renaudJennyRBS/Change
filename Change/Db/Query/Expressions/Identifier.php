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
 * @name \Change\Db\Query\Expressions\Identifier
 */
class Identifier extends AbstractExpression
{
	/**
	 * @var array
	 */
	protected $parts = array();
	
	/**
	 * @param array $parts
	 */
	public function __construct($parts = array())
	{
		$this->setParts($parts);
	}
	
	/**
	 * @return string[]
	 */
	public function getParts()
	{
		return $this->parts;
	}
	
	/**
	 * @throws \InvalidArgumentException
	 * @param string[] $parts
	 */
	public function setParts($parts)
	{
		if (!is_array($parts))
		{
			throw new \InvalidArgumentException('Argument 1 must be a Array', 42033);
		}
		$this->parts = array();
		foreach ($parts as $value)
		{
			$part = trim(strval($value));
			if ($part !== '')
			{
				$this->parts[] = $part;
			}
		}
	}	
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return implode('.', array_map(function ($part) {
			return '"' . $part . '"';
		}, $this->parts));
	}
}
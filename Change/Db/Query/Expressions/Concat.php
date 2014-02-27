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
 * @name \Change\Db\Query\Expressions\Concat
 */
class Concat extends ExpressionList
{
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return implode(' || ', array_map(function (AbstractExpression $item) {
			return $item->toSQL92String();
		}, $this->getList()));
	}
}

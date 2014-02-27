<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\BinaryOperation;

/**
 * @name \Change\Db\Query\Predicates\BinaryPredicate
 */
class BinaryPredicate extends BinaryOperation implements InterfacePredicate
{
	const EQUAL = '=';
	const NOTEQUAL = '<>';
	const GREATERTHAN = '>';
	const LESSTHAN = '<';
	const GREATERTHANOREQUAL = '>=';
	const LESSTHANOREQUAL = '<=';
	
}
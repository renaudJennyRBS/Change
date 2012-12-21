<?php
namespace Change\Db\Query\Predicates;

use Change\Db\Query\Expressions\AbstractExpression;
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
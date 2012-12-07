<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\AllColumns
 */
class AllColumns extends AbstractExpression
{
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return '*';
	}
}
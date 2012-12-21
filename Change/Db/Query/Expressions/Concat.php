<?php
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

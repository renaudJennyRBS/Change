<?php

namespace Change\Db\Query\Expressions;

class AllColumns extends AbstractExpression
{
	public function toSQL92String()
	{
		return '*';
	}
}
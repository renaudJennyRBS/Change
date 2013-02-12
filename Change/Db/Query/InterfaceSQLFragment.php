<?php
namespace Change\Db\Query;

/**
 * @api
 * @name \Change\Db\Query\InterfaceSQLFragment
 */
interface InterfaceSQLFragment
{	
	/**
	 * @api
	 * @return string
	 */
	public function toSQL92String();
}
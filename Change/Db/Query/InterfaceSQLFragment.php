<?php
namespace Change\Db\Query;

/**
 * @name \Change\Db\Query\InterfaceSQLFragment
 */
interface InterfaceSQLFragment
{	
	/**
	 * @return string
	 */
	public function toSQL92String();
}
<?php
namespace Change\Db\Query\Clauses;

/**
 * @name \Change\Db\Query\Clauses\DeleteClause
 * @api
 */
class DeleteClause extends AbstractClause
{	

	public function __construct()
	{
		$this->setName('DELETE');
	}
		
	/**
	 * @api
	 * @return string
	 */
	public function toSQL92String()
	{
		return 'DELETE';
	}
}
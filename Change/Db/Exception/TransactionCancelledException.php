<?php
namespace Change\Db\Exception;

/**
 * @name \Change\Db\Exception\TransactionCancelledException
 */
class TransactionCancelledException extends \Exception
{
	/**
	 * @param Exception $previous
	 */
	public function __construct($previous)
	{
		if ($previous !== null)
		{
			parent::__construct("Transaction cancelled: ". $previous->getMessage(), null, $previous);
		}
		else
		{
			parent::__construct("Transaction cancelled (unknown cause)");
		}
	}
}
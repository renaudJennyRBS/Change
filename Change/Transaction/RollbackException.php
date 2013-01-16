<?php
namespace Change\Transaction;

/**
 * @name \Change\Transaction\RollbackException
 */
class RollbackException extends \Exception
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
<?php
namespace Change\Transaction;

/**
 * @name \Change\Transaction\RollbackException
 */
class RollbackException extends \Exception
{
	/**
	 * @param \Exception $previous
	 */
	public function __construct($previous)
	{
		if ($previous !== null)
		{
			parent::__construct("Transaction cancelled: ". $previous->getMessage(), 120000, $previous);
		}
		else
		{
			parent::__construct("Transaction cancelled: Unknown cause", 120000);
		}
	}
}
<?php
namespace Rbs\Payment\Documents;

/**
 * @name \Rbs\Payment\Documents\Transaction
 */
class Transaction extends \Compilation\Rbs\Payment\Documents\Transaction
{
	const STATUS_INITIATED = 'initiated';
	const STATUS_PROCESSING = 'processing';
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED = 'failed';
}
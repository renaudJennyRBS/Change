<?php

namespace ChangeTests\Rbs\Payment;

class PaymentManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testOnDefaultGetMailCode()
	{
		$commerceServices = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$paymentManager = $commerceServices->getPaymentManager();
		$this->assertInstanceOf('\Rbs\Payment\PaymentManager', $paymentManager);

		/* @var $transaction \Rbs\Payment\Documents\Transaction */
		$transaction = $this->getNewReadonlyDocument('Rbs_Payment_Transaction', 100);
		$code = $paymentManager->getMailCode($transaction, \Rbs\Payment\Documents\Transaction::STATUS_PROCESSING);
		$this->assertEquals('rbs_payment_transaction_processing', $code);

		$code = $paymentManager->getMailCode($transaction, \Rbs\Payment\Documents\Transaction::STATUS_SUCCESS);
		$this->assertEquals('rbs_payment_transaction_success', $code);

		$code = $paymentManager->getMailCode($transaction, \Rbs\Payment\Documents\Transaction::STATUS_FAILED);
		$this->assertEquals('rbs_payment_transaction_failed', $code);
	}
}
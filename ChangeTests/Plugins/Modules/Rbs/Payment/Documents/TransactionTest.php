<?php

class TransactionTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testGetLabel()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();

		$dm = $this->getApplicationServices()->getDocumentManager();
		$transaction = $dm->getNewDocumentInstanceByModelName('Rbs_Payment_Transaction');
		/* @var $transaction \Rbs\Payment\Documents\Transaction */
		$this->assertEquals($transaction->getId(), $transaction->getLabel());
		$transaction->save();
		$this->assertEquals($transaction->getId(), $transaction->getLabel());
		$transaction->setLabel('toto');
		$transaction->save();
		$this->assertEquals($transaction->getId(), $transaction->getLabel());

		$this->getApplicationServices()->getTransactionManager()->commit();
	}
}
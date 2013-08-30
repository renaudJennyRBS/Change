<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentManager;

class DocumentManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->closeDbConnection();
	}
	
	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getObject()
	{
		$manager = $this->getDocumentServices()->getDocumentManager();
		$manager->reset();
		return $manager;
	}
	

	public function testGetNewDocumentInstance()
	{
		$manager = $this->getObject();

		$document = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $document);
		$this->assertEquals(-1, $document->getId());
		$this->assertEquals(AbstractDocument::STATE_NEW, $document->getPersistentState());
		
		$model = $document->getDocumentModel();
		
		$document2 = $manager->getNewDocumentInstanceByModel($model);
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $document2);
		$this->assertEquals(-2, $document2->getId());
		$this->assertEquals(AbstractDocument::STATE_NEW, $document2->getPersistentState());

		/* @var $document3 \Project\Tests\Documents\DocStateless */
		$document3 = $manager->getNewDocumentInstanceByModelName('Project_Tests_DocStateless');
		$this->assertInstanceOf('\Project\Tests\Documents\DocStateless', $document3);
		$this->assertInstanceOf('\Project\Tests\Documents\DocStateless', $document3);
		$this->assertArrayHasKey('id', $document3->getData());
		$this->assertArrayHasKey('CreationDate', $document3->getData());
	}

	public function testTransaction()
	{
		$manager = $this->getObject();
		$this->assertFalse($manager->inTransaction());

		$manager->getApplicationServices()->getTransactionManager()->begin();
		$this->assertTrue($manager->inTransaction());
		$manager->getApplicationServices()->getTransactionManager()->commit();

		$this->assertFalse($manager->inTransaction());
	}

	public function testCache()
	{
		$manager = $this->getObject();

		$manager->getApplicationServices()->getTransactionManager()->begin();

		/* @var $document \Project\Tests\Documents\Basic */
		$document = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertLessThan(0, $document->getId());
		$this->assertFalse($manager->isInCache($document->getId()));
		$document->setPStr('Required');
		$document->create();
		$this->assertGreaterThan(0, $document->getId());
		$this->assertTrue($manager->isInCache($document->getId()));

		$manager->getApplicationServices()->getTransactionManager()->commit();
		$this->assertFalse($manager->isInCache($document->getId()));
	}

	/**
	 * Tests for:
	 *  - getLCIDStackSize
	 *  - getLCID
	 *  - pushLCID
	 *  - popLCID
	 */
	public function testLangStack()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		$config->addVolatileEntry('Change/I18n/supported-lcids' , null);
		$config->addVolatileEntry('Change/I18n/supported-lcids', array('fr_FR','en_GB','it_IT','es_ES','en_US'));
		
		$config->addVolatileEntry('Change/I18n/langs' , null);
		$config->addVolatileEntry('Change/I18n/langs', array('en_US' => 'us'));

		$i18nManger = $this->getApplicationServices()->getI18nManager();
		$manager = new DocumentManager();
		$manager->setApplicationServices($this->getApplicationServices());
		$manager->setDocumentServices($this->getDocumentServices());

		// There is no default value.
		$this->assertEquals(0, $manager->getLCIDStackSize());
		$this->assertEquals($i18nManger->getLCID(), $manager->getLCID());

		// Push/pop supported languages.
		$manager->pushLCID('it_IT');
		$this->assertEquals(1, $manager->getLCIDStackSize());
		$this->assertEquals('it_IT', $manager->getLCID());
		$manager->pushLCID('en_GB');
		$this->assertEquals(2, $manager->getLCIDStackSize());
		$this->assertEquals('en_GB', $manager->getLCID());
		$manager->popLCID();
		$this->assertEquals(1, $manager->getLCIDStackSize());
		$this->assertEquals('it_IT', $manager->getLCID());
		$manager->popLCID();
		$this->assertEquals(0, $manager->getLCIDStackSize());
		$this->assertEquals($i18nManger->getLCID(), $manager->getLCID());

		// Pop from an empty stack.
		try
		{
			$manager->popLCID();
			$this->fail('A LogicException should be thrown.');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals(0, $manager->getLCIDStackSize());
			$this->assertEquals($i18nManger->getLCID(), $manager->getLCID());
		}

		// Push not spported language.
		try
		{
			$manager->pushLCID('kl');
			$this->fail('A InvalidArgumentException should be thrown.');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals(0, $manager->getLCIDStackSize());
			$this->assertEquals($i18nManger->getLCID(), $manager->getLCID());
		}
	}

	public function testTransactionLangStack()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		$config->addVolatileEntry('Change/I18n/supported-lcids' , null);
		$config->addVolatileEntry('Change/I18n/supported-lcids', array('fr_FR','en_GB','it_IT','es_ES','en_US'));

		$manager = $this->getDocumentServices()->getDocumentManager();

		$manager->pushLCID('fr_FR');
		$manager->pushLCID('en_GB');
		$manager->pushLCID('it_IT');
		$this->getApplicationServices()->getTransactionManager()->begin();
		$manager->pushLCID('es_ES');
		$manager->pushLCID('en_US');
		$this->getApplicationServices()->getTransactionManager()->begin();
		$manager->pushLCID('en_GB');
		$this->assertEquals('en_GB', $manager->getLCID());

		try
		{
			$this->getApplicationServices()->getTransactionManager()->rollBack();
			$this->fail('RollbackException expected');
		}
		catch (\Change\Transaction\RollbackException $e)
		{

		}
		$this->assertEquals('en_US', $manager->getLCID());

		$this->getApplicationServices()->getTransactionManager()->rollBack();
		$this->assertEquals('it_IT', $manager->getLCID());
	}
}
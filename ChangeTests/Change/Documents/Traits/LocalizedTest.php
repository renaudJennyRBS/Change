<?php
namespace ChangeTests\Change\Documents\Traits;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentManager;

/**
* @name \ChangeTests\Change\Documents\Traits\LocalizedTest
*/
class LocalizedTest extends \ChangeTests\Change\TestAssets\TestCase
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
	protected function getDocumentManager()
	{
		$manager = $this->getDocumentServices()->getDocumentManager();
		$manager->reset();
		return $manager;
	}

	public function testI18nDocument()
	{

		$this->getApplicationServices()->getTransactionManager()->begin();

		$manager = $this->getDocumentManager();

		/* @var $localized \Project\Tests\Documents\Localized */
		$localized = $manager->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$localizedI18nPartFr = $localized->getCurrentLocalization();

		$tmpId = $localized->getId();
		$this->assertNotNull($localizedI18nPartFr);
		$this->assertEquals($tmpId, $localizedI18nPartFr->getId());
		$this->assertEquals('fr_FR', $localizedI18nPartFr->getLCID());
		$this->assertEquals(AbstractDocument::STATE_NEW, $localizedI18nPartFr->getPersistentState());

		$localized->setPStr('Required');
		$localizedI18nPartFr->setPLStr('Required');

		$localized->create();
		$this->assertEquals(AbstractDocument::STATE_LOADED, $localized->getPersistentState());

		$this->assertNotEquals($tmpId, $localized->getId());

		$this->assertEquals($localized->getId(), $localizedI18nPartFr->getId());
		$this->assertEquals(AbstractDocument::STATE_LOADED, $localizedI18nPartFr->getPersistentState());

		$localizedI18nPartFr->setPLStr('Localized Label');
		$this->assertTrue($localizedI18nPartFr->isPropertyModified('pLStr'));
		$this->assertTrue($localized->isPropertyModified('pLStr'));

		$localized->save();
		$this->assertFalse($localizedI18nPartFr->isPropertyModified('pLStr'));
		$this->assertFalse($localized->isPropertyModified('pLStr'));

		$localized->reset();

		$loaded = $localized->getCurrentLocalization();
		$this->assertNotSame($loaded, $localizedI18nPartFr);

		$this->assertEquals($localized->getId(), $loaded->getId());
		$this->assertEquals('fr_FR', $loaded->getLCID());
		$this->assertEquals('Localized Label', $loaded->getPLStr());
		$this->assertEquals(AbstractDocument::STATE_LOADED, $loaded->getPersistentState());

		$localized->delete();
		$this->assertEquals(AbstractDocument::STATE_DELETED, $loaded->getPersistentState());

		$deleted = $localized->getCurrentLocalization();
		$this->assertSame($loaded, $deleted);
		$this->getApplicationServices()->getTransactionManager()->commit();
	}
}
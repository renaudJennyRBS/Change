<?php
namespace ChangeTests\Change\Documents;

class DocumentManagerTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function testConstruct()
	{
		return \Change\Application::getInstance()->getDocumentServices()->getDocumentManager();
	}

	/**
	 * Tests for:
	 *  - getLangStackSize
	 *  - getLang
	 *  - pushLang
	 *  - popLang
	 * @depends testConstruct
	 */
	public function testLangStack()
	{
		$application = \Change\Application::getInstance();
		$configPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'I18n' . DIRECTORY_SEPARATOR . 'TestAssets' . DIRECTORY_SEPARATOR . 'project1.php';
		$config = new \ChangeTests\Change\Configuration\TestAssets\Configuration($application, $configPath);
		$i18nManger = new \Change\I18n\I18nManager($config, $application->getApplicationServices()->getDbProvider());
		$application->getApplicationServices()->instanceManager()->addSharedInstance($i18nManger, 'Change\I18n\I18nManager');
		$manager = new \Change\Documents\DocumentManager($application->getApplicationServices(), $application->getDocumentServices()->getModelManager());
		
		// There is no default value.
		$this->assertEquals(0, $manager->getLangStackSize());
		$this->assertEquals($i18nManger->getLang(), $manager->getLang());

		// Push/pop supported languages.
		$manager->pushLang('it');
		$this->assertEquals(1, $manager->getLangStackSize());
		$this->assertEquals('it', $manager->getLang());
		$manager->pushLang('en');
		$this->assertEquals(2, $manager->getLangStackSize());
		$this->assertEquals('en', $manager->getLang());
		$manager->popLang();
		$this->assertEquals(1, $manager->getLangStackSize());
		$this->assertEquals('it', $manager->getLang());
		$manager->popLang();
		$this->assertEquals(0, $manager->getLangStackSize());
		$this->assertEquals($i18nManger->getLang(), $manager->getLang());

		// Pop from an empty stack.
		try
		{
			$manager->popLang();
			$this->fail('A LogicException should be thrown.');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals(0, $manager->getLangStackSize());
			$this->assertEquals($i18nManger->getLang(), $manager->getLang());
		}

		// Push not spported language.
		try
		{
			$manager->pushLang('kl');
			$this->fail('A InvalidArgumentException should be thrown.');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals(0, $manager->getLangStackSize());
			$this->assertEquals($i18nManger->getLang(), $manager->getLang());
		}
	}
}
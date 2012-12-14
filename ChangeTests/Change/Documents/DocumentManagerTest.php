<?php
namespace ChangeTests\Change\Documents;

class DocumentManagerTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		// TODO - this code should be moved elsewhere and factored with the one contained in the compile-document command
		$compiler = new \Change\Documents\Generators\Compiler(\Change\Application::getInstance());
		$paths = array();
		$workspace = \Change\Application::getInstance()->getWorkspace();
		if (is_dir($workspace->pluginsModulesPath()))
		{
			$pattern = implode(DIRECTORY_SEPARATOR, array($workspace->pluginsModulesPath(), '*', '*', 'Documents', 'Assets', '*.xml'));
			$paths = array_merge($paths, \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT));
		}
	
		if (is_dir($workspace->projectModulesPath()))
		{
			$pattern = implode(DIRECTORY_SEPARATOR, array($workspace->projectModulesPath(), '*', '*', 'Documents', 'Assets', '*.xml'));
			$paths = array_merge($paths, \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT));
		}
	
		$nbModels = 0;
		foreach ($paths as $definitionPath)
		{
			$parts = explode(DIRECTORY_SEPARATOR, $definitionPath);
			$count = count($parts);
			$documentName = basename($parts[$count - 1], '.xml');
			$moduleName = $parts[$count - 4];
			$vendor = $parts[$count - 5];
			$compiler->loadDocument($vendor, $moduleName, $documentName, $definitionPath);
			$nbModels++;
		}
	
		$compiler->buildTree();
		$compiler->validateInheritance();
		$compiler->saveModelsPHPCode();
	}
	
	/**
	 * @return \Change\Documents\DocumentManager
	 */
	public function testConstruct()
	{
		return \Change\Application::getInstance()->getDocumentServices()->getDocumentManager();
	}

	/**
	 * Tests for:
	 *  - getLCIDStackSize
	 *  - getLCID
	 *  - pushLCID
	 *  - popLCID
	 * @depends testConstruct
	 */
	public function testLangStack()
	{
		$application = \Change\Application::getInstance();
		$configPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'I18n' . DIRECTORY_SEPARATOR . 'TestAssets' . DIRECTORY_SEPARATOR . 'project1.php';
		$config = new \ChangeTests\Change\Configuration\TestAssets\Configuration($application, $configPath);
		$i18nManger = new \Change\I18n\I18nManager($config, $application->getApplicationServices()->getDbProvider());
		$application->getApplicationServices()->instanceManager()->addSharedInstance($i18nManger, 'Change\I18n\I18nManager');
		$manager = new \Change\Documents\DocumentManager($application->getApplicationServices(), $application->getDocumentServices());
		
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
}
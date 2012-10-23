<?php
namespace ChangeTests\Change\I18n;

class I18nManagerTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		return \Change\Application::getInstance()->getApplicationServices()->getI18nManager();
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testGetSupportedLanguages()
	{
		$application = \Change\Application::getInstance();
		$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'TestAssets' . DIRECTORY_SEPARATOR . 'project1.php';
		$config = new \ChangeTests\Change\Configuration\TestAssets\Configuration($application, $configPath);
		$manager = new \Change\I18n\I18nManager($config, $application->getApplicationServices()->getDbProvider());
		
		$this->assertEquals(array('fr', 'en', 'it', 'es'), $manager->getSupportedLanguages());
		
		return $manager;
	}
	
	/**
	 * @depends testGetSupportedLanguages
	 */
	public function testGetDefaultLang(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('fr', $manager->getDefaultLang());
	}
	
	/**
	 * @depends testGetSupportedLanguages
	 */
	public function testGetLCID(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('fr_FR', $manager->getLCID('fr'));
		$this->assertEquals('it_IT', $manager->getLCID('it'));
		$this->assertEquals('en_GB', $manager->getLCID('en'));
	}
	
	/**
	 * @depends testGetSupportedLanguages
	 */
	public function testGetCode(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('fr', $manager->getCode('fr_FR'));
		$this->assertEquals('it', $manager->getCode('it_IT'));
		$this->assertEquals('en', $manager->getCode('en_GB'));
	}

	/**
	 * Tests for: 
	 *  - getUILang
	 *  - setUILang
	 * @depends testGetSupportedLanguages
	 */
	public function testGetSetUILang(\Change\I18n\I18nManager $manager)
	{
		// TODO: Test lang from session.
		// If no UI lang is set, use the default one.
		$this->assertEquals($manager->getDefaultLang(), $manager->getUILang());
		
		// Set/get supported languages.
		$manager->setUILang('it');
		$this->assertEquals('it', $manager->getUILang());
		$manager->setUILang('en');
		$this->assertEquals('en', $manager->getUILang());
		
		// Setting an unsupported language.
		try 
		{
			$manager->setUILang('kl');
			$this->fail('A InvalidArgumentException should be thrown.');
		}
		catch (\InvalidArgumentException $e)
		{
			// It's OK.
		}
	}

	/**
	 * Tests for:
	 *  - getLangStackSize
	 *  - getLang
	 *  - pushLang
	 *  - popLang
	 * @depends testGetSupportedLanguages
	 */
	public function testLangStack(\Change\I18n\I18nManager $manager)
	{
		// The is no default value.
		$this->assertEquals(0, $manager->getLangStackSize());
		$this->assertEquals($manager->getUILang(), $manager->getLang());
		
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
		$this->assertEquals($manager->getUILang(), $manager->getLang());
		
		// Pop from an empty stack.
		try 
		{
			$manager->popLang();
			$this->fail('A LogicException should be thrown.');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals(0, $manager->getLangStackSize());
			$this->assertEquals($manager->getUILang(), $manager->getLang());
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
			$this->assertEquals($manager->getUILang(), $manager->getLang());
		}
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testPrepareKeyFromTransString(\Change\I18n\I18nManager $manager)
	{
		$preparedKey = $manager->prepareKeyFromTransString('m.website.fo.test,ucf,toto=titi,attr');
		$this->assertEquals('m.website.fo.test', $preparedKey->getKey());
		$this->assertEquals(array('ucf', 'attr'), $preparedKey->getFormatters());
		$this->assertEquals(array('toto' => 'titi'), $preparedKey->getReplacements());
		
		// Keys and formatters are lower cased and spaces are cleaned.
		$preparedKey = $manager->prepareKeyFromTransString(' m.Website.Fo.test ,uCf , toTo= titI , aTTr');
		$this->assertEquals('m.website.fo.test', $preparedKey->getKey());
		$this->assertEquals(array('ucf', 'attr'), $preparedKey->getFormatters());
		$this->assertEquals(array('toTo' => 'titI'), $preparedKey->getReplacements());
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testTransformLab(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('test :', $manager->transformLab('test', 'fr'));
		$this->assertEquals('test:', $manager->transformLab('test', 'en'));
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testTransformUc(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('TEST', $manager->transformUc('test', 'fr'));
		$this->assertEquals('TEST', $manager->transformUc('tEsT', 'fr'));
		$this->assertEquals('ÉTÉ ÇA', $manager->transformUc('été ça', 'fr'));
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testTransformUcf(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('Test', $manager->transformUcf('test', 'fr'));
		$this->assertEquals('TEsT', $manager->transformUcf('tEsT', 'fr'));
		$this->assertEquals('Été ça', $manager->transformUcf('été ça', 'fr'));
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testTransformUcw(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('Test Test', $manager->transformUcw('test test', 'fr'));
		$this->assertEquals('Test', $manager->transformUcw('tEsT', 'fr'));
		$this->assertEquals('Été Ça', $manager->transformUcw('été ça', 'fr'));
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testTransformLc(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('test test', $manager->transformLc('test test', 'fr'));
		$this->assertEquals('test', $manager->transformLc('tEsT', 'fr'));
		$this->assertEquals('été ça été', $manager->transformLc('été ça Été', 'fr'));
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testTransformJs(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('test \"test\"', $manager->transformJs('test "test"', 'fr'));
		$this->assertEquals('tEsT \t \n \\\'test\\\' \\\\', $manager->transformJs("tEsT \t \n 'test' \\", 'fr'));
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testTransformHtml(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals("test <br />\n &lt;em&gt;toto&lt;/em&gt; &quot;test&quot;", $manager->transformHtml("test \n <em>toto</em> \"test\"", 'fr'));
	}
	
	// TODO
	/**
	 * @depends testConstruct
	 */
	/*public function testTransformText(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals("...", $manager->transformText("...", 'fr'));
	}*/
	
	// TODO
	/**
	 * @depends testConstruct
	 */
	/*public function testTransformAttr(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals("...", $manager->transformAttr("...", 'fr'));
	}*/
	
	/**
	 * @depends testConstruct
	 */
	public function testTransformSpace(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals(" test 3 ", $manager->transformSpace("test 3", 'fr'));
		$this->assertEquals(" ... ", $manager->transformSpace("...", 'fr'));
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testTransformEtc(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals("test 3...", $manager->transformEtc("test 3", 'fr'));
		$this->assertEquals("......", $manager->transformEtc("...", 'fr'));
	}
}
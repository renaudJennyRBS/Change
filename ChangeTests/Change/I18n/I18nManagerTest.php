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
		$config = $application->getApplicationServices()->getConfiguration();
		$config->addVolatileEntry('i18n/supported-lcids' , null);
		$config->addVolatileEntry('i18n/supported-lcids', array('fr_FR','en_GB','it_IT','es_ES','en_US'));
		
		$config->addVolatileEntry('i18n/langs' , null);
		$config->addVolatileEntry('i18n/langs', array('en_US' => 'us'));
		
		$manager = new \Change\I18n\I18nManager($application);

		$this->assertEquals(array('fr_FR','en_GB','it_IT','es_ES','en_US'), $manager->getSupportedLCIDs());

		return $manager;
	}

	/**
	 * @depends testGetSupportedLanguages
	 */
	public function testGetDefaultLang(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('fr_FR', $manager->getDefaultLCID());
	}

	/**
	 * @depends testGetSupportedLanguages
	 */
	public function testGetLangByLCID(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('fr', $manager->getLangByLCID('fr_FR'));
		$this->assertEquals('us', $manager->getLangByLCID('en_US'));
		$this->assertEquals('xx', $manager->getLangByLCID('xx_XX'));
		try
		{
			$manager->getLangByLCID('fr');
			$this->fail('A InvalidArgumentException should be thrown.');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals('Invalid LCID: fr', $e->getMessage());
		}
	}

	/**
	 * @depends testGetSupportedLanguages
	 */
	public function testGetLCIDByLang(\Change\I18n\I18nManager $manager)
	{
		$this->assertEquals('fr_FR', $manager->getLCIDByLang('fr'));
		$this->assertEquals('en_GB', $manager->getLCIDByLang('en'));
		$this->assertEquals('en_US', $manager->getLCIDByLang('us'));

	}

	/**
	 * Tests for:
	 *  - getUILang
	 *  - setUILang
	 * @depends testGetSupportedLanguages
	 */
	public function testGetSetLang(\Change\I18n\I18nManager $manager)
	{
		// TODO: Test lang from session.
		// If no UI lang is set, use the default one.
		$this->assertEquals($manager->getDefaultLCID(), $manager->getLCID());

		// Set/get supported languages.
		$manager->setLCID('it_IT');
		$this->assertEquals('it_IT', $manager->getLCID());
		$manager->setLCID('en_GB');
		$this->assertEquals('en_GB', $manager->getLCID());

		// Setting an unsupported language.
		try
		{
			$manager->setLCID('kl_KL');
			$this->fail('A InvalidArgumentException should be thrown.');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals('Not supported language: kl_KL', $e->getMessage());
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
	public function testTranslateNoKey(\Change\I18n\I18nManager $manager)
	{
		$a = "çé Té tutu";
		$this->assertEquals($a, $manager->trans($a));
	}

	// Dates.
	
	/**
	 * Tests for:
	 *  - resetProfile
	 *  - getDateFormat
	 *  - getDateTimeFormat
	 *  - getTimeZone
	 * @depends testConstruct
	 */
	public function testProfile(\Change\I18n\I18nManager $manager)
	{
		// TODO: needs sessions...
// 		$storage = \Change\Application::getInstance()->getApplicationServices()->getController()->getStorage();
		
// 		// If values are set in the session, these values are returned.
// 		$profile = array('dateformat' => 'test', 'datetimeformat' => 'toto', 'timezone' => '1');
// 		$storage->writeForUser('profilesvalues', $profile);
// 		$manager->resetProfile();
		
// 		$this->assertEquals('test', $manager->getDateFormat('fr'));
// 		$this->assertEquals('toto', $manager->getDateTimeFormat('fr'));
// 		$this->assertEquals('1', $manager->getTimeZone());
		
// 		// Else...
// 		$profile = null;
// 		$storage->writeForUser('profilesvalues', $profile);
		
// 		$manager->resetProfile();
// 		// TODO: needs database.
// 		/*foreach (array('fr', 'en') as $lang)
// 		{
// 			$this->assertEquals($manager->formatKey($lang, 'c.date.default-date-format'), $manager->getDateFormat($lang));
// 			$this->assertEquals($manager->formatKey($lang, 'c.date.default-datetime-format'), $manager->getDateTimeFormat($lang));
// 		}*/
// 		$this->assertEquals(DEFAULT_TIMEZONE, $manager->getTimeZone()->getName());
		
		// Admitted time zone for following tests.
		$this->assertEquals('Europe/Paris', $manager->getTimeZone()->getName());
		return $manager;
	}
	
	/**
	 * @depends testProfile
	 */
	public function testGetLocalDateTime(\Change\I18n\I18nManager $manager)
	{
		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
		$this->assertEquals('Europe/Paris', $date->getTimezone()->getName());
		$this->assertEquals('2012-10-16 09:00:00', $date->format('Y-m-d H:i:s'));
		return $date;
	}
	
	/**
	 * @depends testProfile
	 */
	public function testGetGMTDateTime(\Change\I18n\I18nManager $manager)
	{
		$date = $manager->getGMTDateTime('2012-10-16 09:00:00');
		$this->assertEquals('UTC', $date->getTimezone()->getName());
		$this->assertEquals('2012-10-16 09:00:00', $date->format('Y-m-d H:i:s'));
	}

	/**
	 * @depends testProfile
	 */
	public function testToGMTDateTime(\Change\I18n\I18nManager $manager)
	{
		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
		$manager->toGMTDateTime($date);
		$this->assertEquals('UTC', $date->getTimezone()->getName());
		$this->assertEquals('2012-10-16 07:00:00', $date->format('Y-m-d H:i:s'));
	}

	/**
	 * @depends testProfile
	 */
	public function testToLocalDateTime(\Change\I18n\I18nManager $manager)
	{
		$date = $manager->getGMTDateTime('2012-10-16 09:00:00');
		$manager->toLocalDateTime($date);
		$this->assertEquals('Europe/Paris', $date->getTimezone()->getName());
		$this->assertEquals('2012-10-16 11:00:00', $date->format('Y-m-d H:i:s'));
	}
	
	/**
	 * @depends testProfile
	 */
	public function testFormatDate(\Change\I18n\I18nManager $manager)
	{
		// Basical formating.
		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
		$this->assertEquals('16/10/2012 09:00', $manager->formatDate('fr', $date, 'dd/MM/yyyy HH:mm'));
		
		// The language is correctly used for days and months.
		$this->assertEquals('mardi, 16 octobre 2012', $manager->formatDate('fr', $date, 'EEEE, d MMMM yyyy'));
		$this->assertEquals('Tuesday, October 16 2012', $manager->formatDate('en', $date, 'EEEE, MMMM d yyyy'));

		// Without any specified time zone, the date is converted to the local timezone.
		$this->assertEquals('09:00 UTC+02:00', $manager->formatDate('fr', $date, 'HH:mm ZZZZ'));
		$gmtDate = $manager->getGMTDateTime('2012-10-16 09:00:00');
		$this->assertEquals('11:00 UTC+02:00', $manager->formatDate('fr', $gmtDate, 'HH:mm ZZZZ'));
		
		// If there is a specified time zone, the date is converted to it.
		$this->assertEquals('03:00 UTC-04:00', $manager->formatDate('fr', $date, 'HH:mm ZZZZ', new \DateTimeZone('America/New_York')));
		$this->assertEquals('18:00 UTC+09:00', $manager->formatDate('fr', $gmtDate, 'HH:mm ZZZZ', new \DateTimeZone('Asia/Tokyo')));
	}
	
	/**
	 * @depends testProfile
	 */
// 	public function testTransDate(\Change\I18n\I18nManager $manager)
// 	{
// 		// TODO: needs database or sessions.
// 		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
// 		$this->assertEquals('16/10/2012', $manager->transDate($date));
// 	}
	
	/**
	 * @depends testProfile
	 */
// 	public function testTransDateTime(\Change\I18n\I18nManager $manager)
// 	{
// 		// TODO: needs database or sessions.
// 		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
// 		$this->assertEquals('16/10/2012 09:00', $manager->transDateTime($date));
// 	}
	
	// Transformers.

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
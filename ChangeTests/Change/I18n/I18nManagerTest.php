<?php
namespace ChangeTests\Change\I18n;

use Change\I18n\I18nManager;

class I18nManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstruct()
	{
		$i18nm = $this->getApplicationServices()->getI18nManager();

		// Stop logging non existent keys.
		$callback = function (\Zend\EventManager\Event $event)
		{
			// Add '--' before key to validate that this callback is really used.
			return '--' . $event->getParam('preparedKey')->getKey();
		};
		$i18nm->getEventManager()->attach(I18nManager::EVENT_KEY_NOT_FOUND, $callback, 10);

		// Add specific formatter.
		$callback = function (\Zend\EventManager\Event $event)
		{
			$formatters = $event->getParam('formatters');
			if (in_array('required', $formatters))
			{
				if (in_array('html', $formatters))
				{
					$event->setParam('text', '<span class="required">*</span> ' . $event->getParam('text'));
				}
				else
				{
					$event->setParam('text', '* ' . $event->getParam('text'));
				}
			}
		};
		$i18nm->getEventManager()->attach(I18nManager::EVENT_FORMATTING, $callback, 2);

		return $i18nm;
	}

	/**
	 * @depends testConstruct
	 */
	public function testGetSupportedLCIDs()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		$config->addVolatileEntry('Change/I18n/supported-lcids', null);
		$config->addVolatileEntry('Change/I18n/supported-lcids', array('fr_FR','en_GB','it_IT','es_ES','en_US'));
		
		$config->addVolatileEntry('Change/I18n/langs', null);
		$config->addVolatileEntry('Change/I18n/langs', array('en_US' => 'us'));
		
		$manager = new I18nManager();
		$manager->setWorkspace($application->getWorkspace());
		$manager->setLogging($this->getApplicationServices()->getLogging());
		$manager->setConfiguration($config);

		$this->assertEquals(array('fr_FR','en_GB','it_IT','es_ES','en_US'), $manager->getSupportedLCIDs());

		return $manager;
	}

	/**
	 * @depends testConstruct
	 */
	public function testSupportsMultipleLCIDs()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		
		$config->addVolatileEntry('Change/I18n/supported-lcids', null);
		$manager = new I18nManager();
		$manager->setConfiguration($config);
		$this->assertFalse($manager->supportsMultipleLCIDs());
		
		$config->addVolatileEntry('Change/I18n/supported-lcids', array('fr_FR'));
		$manager = new I18nManager();
		$manager->setConfiguration($config);
		$this->assertFalse($manager->supportsMultipleLCIDs());

		$config->addVolatileEntry('Change/I18n/supported-lcids', array('fr_FR','en_GB'));
		$manager = new I18nManager();
		$manager->setConfiguration($config);
		$this->assertTrue($manager->supportsMultipleLCIDs());

		$config->addVolatileEntry('Change/I18n/supported-lcids', array('fr_FR','en_GB','it_IT','es_ES','en_US'));
		$manager = new I18nManager();
		$manager->setConfiguration($config);
		$this->assertTrue($manager->supportsMultipleLCIDs());
	}

	/**
	 * @depends testConstruct
	 */
	public function testHasGetI18nSynchro()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		$config->addVolatileEntry('Change/I18n/supported-lcids', array('fr_FR','en_GB','it_IT','es_ES','en_US'));
		$configArray = $config->getConfigArray();
		
		$config->addVolatileEntry('Change/I18n/synchro/keys', array());
		$manager = new I18nManager();
		$manager->setConfiguration($config);
		$this->assertFalse($manager->hasI18nSynchro());
		$this->assertEquals(array(), $manager->getI18nSynchro());
		
		$config->setConfigArray($configArray);
		$config->addVolatileEntry('Change/I18n/synchro/keys', array('en_GB' => array('fr_FR')));
		$manager = new I18nManager();
		$manager->setConfiguration($config);
		$this->assertTrue($manager->hasI18nSynchro());
		$this->assertEquals(array('en_GB' => array('fr_FR')), $manager->getI18nSynchro());
		
		$config->setConfigArray($configArray);
		$config->addVolatileEntry('Change/I18n/synchro/keys', array('en_GB' => array('fr_FR'), 'en_US' => array('en_GB', 'fr_FR')));
		$manager = new I18nManager();
		$manager->setConfiguration($config);
		$this->assertTrue($manager->hasI18nSynchro());
		$this->assertEquals(array('en_GB' => array('fr_FR'), 'en_US' => array('en_GB', 'fr_FR')), $manager->getI18nSynchro());
		
		// Unsupported LCIDs are ignored
		$config->setConfigArray($configArray);
		$config->addVolatileEntry('Change/I18n/synchro/keys', array('en_GB' => array('fr_FR', 'kl_KL'), 'to_TO' => array('fr_FR')));
		$manager = new I18nManager();
		$manager->setConfiguration($config);
		$this->assertTrue($manager->hasI18nSynchro());
		$this->assertEquals(array('en_GB' => array('fr_FR')), $manager->getI18nSynchro());
	}

	/**
	 * @depends testGetSupportedLCIDs
	 */
	public function testGetDefaultLang(I18nManager $manager)
	{
		$this->assertEquals('fr_FR', $manager->getDefaultLCID());
	}

	/**
	 * @depends testGetSupportedLCIDs
	 */
	public function testGetLangByLCID(I18nManager $manager)
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
			$this->assertEquals('Not supported LCID: fr', $e->getMessage());
		}
	}

	/**
	 * @depends testGetSupportedLCIDs
	 */
	public function testGetSetLCID(I18nManager $manager)
	{
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
			$this->assertEquals('Not supported LCID: kl_KL', $e->getMessage());
		}
	}

	/**
	 * @depends testConstruct
	 */
	public function testPrepareKeyFromTransString(I18nManager $manager)
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
	public function testTranslateNoKey(I18nManager $manager)
	{
		$a = "çé Té tutu";
		$this->assertEquals($a, $manager->trans($a));
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testGetDefinitionCollection(I18nManager $manager)
	{
		// In Change package.
		$collection = $manager->getDefinitionCollection('fr_FR', array('c', 'date'));
		$this->assertInstanceOf('\Change\I18n\DefinitionCollection', $collection);
		$collection = $manager->getDefinitionCollection('fr_FR', array('c', 'toto', 'titi'));
		$this->assertNull($collection);
		
		// In modules.
		$collection = $manager->getDefinitionCollection('fr_FR', array('m', 'project', 'tests', 'a', 'aa'));
		$this->assertInstanceOf('\Change\I18n\DefinitionCollection', $collection);
		$this->assertEquals('fr_FR', $collection->getLCID());
		$collection = $manager->getDefinitionCollection('en_GB', array('m', 'project', 'tests', 'a', 'aa'));
		$this->assertInstanceOf('\Change\I18n\DefinitionCollection', $collection);
		$this->assertEquals('en_GB', $collection->getLCID());
		$collection = $manager->getDefinitionCollection('fr_FR', array('m', 'project', 'tests', 'b'));
		$this->assertInstanceOf('\Change\I18n\DefinitionCollection', $collection);
		$this->assertEquals('fr_FR', $collection->getLCID());
		$collection = $manager->getDefinitionCollection('en_GB', array('m', 'project', 'tests', 'b'));
		$this->assertInstanceOf('\Change\I18n\DefinitionCollection', $collection);
		$this->assertEquals('en_GB', $collection->getLCID());
		$collection = $manager->getDefinitionCollection('fr_FR', array('m', 'project', 'testons', 'oupas'));
		$this->assertNull($collection);
		
		// In themes.
		// TODO
		
		return $manager;
	}
	
	/**
	 * @depends testGetDefinitionCollection
	 */
	public function testGetDefinitionKey(I18nManager $manager)
	{
		$key = $manager->getDefinitionKey('fr_FR', array('m', 'project', 'tests', 'a', 'aa'), 'plop');
		$this->assertInstanceOf('\Change\I18n\DefinitionKey', $key);
		$this->assertEquals('plop fr a.aa', $key->getText());
		$key = $manager->getDefinitionKey('fr_FR', array('m', 'project', 'tests', 'a', 'aa'), 'plip');
		$this->assertInstanceOf('\Change\I18n\DefinitionKey', $key);
		$this->assertEquals('plip fr b', $key->getText());
		$key = $manager->getDefinitionKey('fr_FR', array('m', 'project', 'tests', 'a', 'aa'), 'plap');
		$this->assertInstanceOf('\Change\I18n\DefinitionKey', $key);
		$this->assertEquals('plap fr a.aa', $key->getText());
		$key = $manager->getDefinitionKey('fr_FR', array('m', 'project', 'tests', 'a', 'aa'), 'plep');
		$this->assertNull($key);
		
		$key = $manager->getDefinitionKey('en_GB', array('m', 'project', 'tests', 'a', 'aa'), 'plop');
		$this->assertInstanceOf('\Change\I18n\DefinitionKey', $key);
		$this->assertEquals('plop en a.aa', $key->getText());
		$key = $manager->getDefinitionKey('en_GB', array('m', 'project', 'tests', 'a', 'aa'), 'plip');
		$this->assertInstanceOf('\Change\I18n\DefinitionKey', $key);
		$this->assertEquals('plip en a.aa', $key->getText());
		$key = $manager->getDefinitionKey('en_GB', array('m', 'project', 'tests', 'a', 'aa'), 'plap');
		$this->assertNull($key);
		$key = $manager->getDefinitionKey('en_GB', array('m', 'project', 'tests', 'a', 'aa'), 'plep');
		$this->assertNull($key);
		
		return $manager;
	}
	
	/**
	 * @depends testGetDefinitionKey
	 */
	public function testTransForLCID(I18nManager $manager)
	{
		// Key translation.
		$this->assertEquals('plop fr a.aa', $manager->transForLCID('fr_FR', 'm.project.tests.a.aa.plop'));
		$this->assertEquals('plip fr b', $manager->transForLCID('fr_FR', 'm.project.tests.a.aa.plip'));
		$this->assertEquals('plap fr a.aa', $manager->transForLCID('fr_FR', 'm.project.tests.a.aa.plap'));
		$this->assertEquals('--m.project.tests.a.aa.plep', $manager->transForLCID('fr_FR', 'm.project.tests.a.aa.plep'));
		
		$this->assertEquals('plop en a.aa', $manager->transForLCID('en_GB', 'm.project.tests.a.aa.plop'));
		$this->assertEquals('plip en a.aa', $manager->transForLCID('en_GB', 'm.project.tests.a.aa.plip'));
		$this->assertEquals('--m.project.tests.a.aa.plap', $manager->transForLCID('en_GB', 'm.project.tests.a.aa.plap'));
		$this->assertEquals('--m.project.tests.a.aa.plep', $manager->transForLCID('en_GB', 'm.project.tests.a.aa.plep'));
		
		$this->assertEquals('un texte quelconque', $manager->transForLCID('fr_FR', 'un texte quelconque'));
		
		// Converters.
		$this->assertEquals('Plop fr a.aa', $manager->transForLCID('fr_FR', 'm.project.tests.a.aa.plop', array('ucf')));
		$this->assertEquals('un texte quelconque', $manager->transForLCID('fr_FR', 'un texte quelconque', array('ucf')));
		$this->assertEquals('PLOP FR A.AA :', $manager->transForLCID('fr_FR', 'm.project.tests.a.aa.plop', array('uc', 'lab')));
		$this->assertEquals('un texte quelconque', $manager->transForLCID('fr_FR', 'un texte quelconque', array('uc', 'lab')));
		
		// Substitutions.
		$this->assertEquals('Withparams test {param2} fr a', $manager->transForLCID('fr_FR', 'm.project.tests.a.withparams', array('ucf'), array('param1' => 'test')));
		$this->assertEquals('withparams test youpi fr a', $manager->transForLCID('fr_FR', 'm.project.tests.a.withparams', array(), array('param1' => 'test', 'param2' => 'youpi')));
		
		// Key synchro.
		$config = $manager->getConfiguration();
		$this->assertFalse($manager->hasI18nSynchro());
		$this->assertEquals('plop en a.aa', $manager->transForLCID('en_GB', 'm.project.tests.a.aa.plop'));
		$this->assertEquals('--m.project.tests.a.aa.plop', $manager->transForLCID('en_US', 'm.project.tests.a.aa.plop'));
		
		$config->addVolatileEntry('Change/I18n/supported-lcids', array('en_US'));
		$config->addVolatileEntry('Change/I18n/synchro/keys', array('en_US' => array('en_GB')));
		$syncManager = new I18nManager();
		$syncManager->setConfiguration($config);
		$syncManager->setWorkspace($manager->getWorkspace());
		$this->assertTrue($syncManager->hasI18nSynchro());
		$this->assertEquals('plop en a.aa', $syncManager->transForLCID('en_GB', 'm.project.tests.a.aa.plop'));
		$this->assertEquals('plop en a.aa', $syncManager->transForLCID('en_US', 'm.project.tests.a.aa.plop'));
		
		return $manager;
	}
	
	/**
	 * @depends testConstruct
	 */
	public function testFormatText(I18nManager $manager)
	{
		$format = \Change\I18n\DefinitionKey::TEXT;
		
		// Converters.
		$this->assertEquals('Un texte quelconque', $manager->formatText('fr_FR', 'un texte quelconque', $format, array('ucf')));
		$this->assertEquals('UN TEXTE QUELCONQUE :', $manager->formatText('fr_FR', 'un texte quelconque', $format, array('uc', 'lab')));
		$this->assertEquals('UN TEXTE QUELCONQUE:', $manager->formatText('en_GB', 'un texte quelconque', $format, array('uc', 'lab')));
		
		// Substitutions.
		$this->assertEquals('Un texte pramétré test {param2}', $manager->formatText('fr_FR', 'un texte pramétré {param1} {param2}', $format, array('ucf'), array('param1' => 'test')));
		$this->assertEquals('un texte pramétré test youpi', $manager->formatText('fr_FR', 'un texte pramétré {param1} {param2}', $format, array(), array('param1' => 'test', 'param2' => 'youpi')));

		// Specific formatter.
		$this->assertEquals('* Un texte quelconque', $manager->formatText('fr_FR', 'un texte quelconque', $format, array('required', 'ucf')));
		$this->assertEquals('<span class="required">*</span> Un texte quelconque', $manager->formatText('fr_FR', 'un texte quelconque', $format, array('ucf', 'required', 'html')));
	}
	
	/**
	 * @depends testTransForLCID
	 */
	public function testTrans(I18nManager $manager)
	{
		$this->assertEquals('fr_FR', $manager->getLCID());
		
		// Key translation.
		$this->assertEquals('plop fr a.aa', $manager->trans('m.project.tests.a.aa.plop'));
		$this->assertEquals('plip fr b', $manager->trans('m.project.tests.a.aa.plip'));
		$this->assertEquals('plap fr a.aa', $manager->trans('m.project.tests.a.aa.plap'));
		$this->assertEquals('--m.project.tests.a.aa.plep', $manager->trans('m.project.tests.a.aa.plep'));
		$this->assertEquals('un texte quelconque', $manager->trans('un texte quelconque'));
		
		// Converters.
		$this->assertEquals('Plop fr a.aa', $manager->trans('m.project.tests.a.aa.plop', array('ucf')));
		$this->assertEquals('un texte quelconque', $manager->trans('un texte quelconque', array('ucf')));
		$this->assertEquals('PLOP FR A.AA :', $manager->trans('m.project.tests.a.aa.plop', array('uc', 'lab')));
		$this->assertEquals('un texte quelconque', $manager->trans('un texte quelconque', array('uc', 'lab')));
		
		// Substitutions.
		$this->assertEquals('Withparams test {param2} fr a', $manager->trans('m.project.tests.a.withparams', array('ucf'), array('param1' => 'test')));
		$this->assertEquals('withparams test youpi fr a', $manager->trans('m.project.tests.a.withparams', array(), array('param1' => 'test', 'param2' => 'youpi')));
	}

	// Dates.
	
	/**
	 * Tests for:
	 *  - get/setDateFormat
	 *  - get/setDateTimeFormat
	 *  - get/setTimeZone
	 * @depends testConstruct
	 */
	public function testGetSetDateFormatsAndTimeZone(I18nManager $manager)
	{
		// If no values set, use the default ones.
		$config = $this->getApplication()->getConfiguration();
		$this->assertEquals($config->getEntry('Change/I18n/default-timezone'), $manager->getTimeZone()->getName());
		foreach (array('fr_FR', 'en_GB') as $lang)
		{
			$this->assertEquals($manager->transForLCID($lang, 'c.date.default-date-format'), $manager->getDateFormat($lang));
			$this->assertEquals($manager->transForLCID($lang, 'c.date.default-datetime-format'), $manager->getDateTimeFormat($lang));
		}
		
		// If values are set, these values are returned.
		$manager->setDateFormat('test');
		$this->assertEquals('test', $manager->getDateFormat('fr_FR'));
		$this->assertEquals('test', $manager->getDateFormat('en_GB'));
		
		$manager->setDateTimeFormat('toto');
		$this->assertEquals('toto', $manager->getDateTimeFormat('fr_FR'));
		$this->assertEquals('toto', $manager->getDateTimeFormat('en_GB'));
		
		$manager->setTimeZone('Asia/Tokyo'); // Time zone may be set by code...
		$this->assertEquals('Asia/Tokyo', $manager->getTimeZone()->getName());
		$manager->setTimeZone(new \DateTimeZone('America/New_York')); // ... or by DateTimeZone instance.
		$this->assertEquals('America/New_York', $manager->getTimeZone()->getName());
		
		// Admitted values for following tests.
		$manager->setTimeZone('Europe/Paris');
		$this->assertEquals('Europe/Paris', $manager->getTimeZone()->getName());
		$manager->setDateFormat('dd/MM/yyyy');
		$this->assertEquals('dd/MM/yyyy', $manager->getDateFormat($manager->getLCID()));
		$manager->setDateTimeFormat('dd/MM/yyyy HH:mm');
		$this->assertEquals('dd/MM/yyyy HH:mm', $manager->getDateTimeFormat($manager->getLCID()));
		return $manager;
	}
	
	/**
	 * @depends testGetSetDateFormatsAndTimeZone
	 */
	public function testGetLocalDateTime(I18nManager $manager)
	{
		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
		$this->assertEquals('Europe/Paris', $date->getTimezone()->getName());
		$this->assertEquals('2012-10-16 09:00:00', $date->format('Y-m-d H:i:s'));
		return $date;
	}
	
	/**
	 * @depends testGetSetDateFormatsAndTimeZone
	 */
	public function testGetGMTDateTime(I18nManager $manager)
	{
		$date = $manager->getGMTDateTime('2012-10-16 09:00:00');
		$this->assertEquals('UTC', $date->getTimezone()->getName());
		$this->assertEquals('2012-10-16 09:00:00', $date->format('Y-m-d H:i:s'));
	}

	/**
	 * @depends testGetSetDateFormatsAndTimeZone
	 */
	public function testToGMTDateTime(I18nManager $manager)
	{
		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
		$manager->toGMTDateTime($date);
		$this->assertEquals('UTC', $date->getTimezone()->getName());
		$this->assertEquals('2012-10-16 07:00:00', $date->format('Y-m-d H:i:s'));
	}

	/**
	 * @depends testGetSetDateFormatsAndTimeZone
	 */
	public function testToLocalDateTime(I18nManager $manager)
	{
		$date = $manager->getGMTDateTime('2012-10-16 09:00:00');
		$manager->toLocalDateTime($date);
		$this->assertEquals('Europe/Paris', $date->getTimezone()->getName());
		$this->assertEquals('2012-10-16 11:00:00', $date->format('Y-m-d H:i:s'));
	}
	
	/**
	 * @depends testGetSetDateFormatsAndTimeZone
	 */
	public function testFormatDate(I18nManager $manager)
	{
		// Basical formating.
		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
		$this->assertEquals('16/10/2012 09:00', $manager->formatDate('fr_FR', $date, 'dd/MM/yyyy HH:mm'));
		
		// The language is correctly used for days and months.
		$this->assertEquals('mardi, 16 octobre 2012', $manager->formatDate('fr_FR', $date, 'EEEE, d MMMM yyyy'));
		$this->assertEquals('Tuesday, October 16 2012', $manager->formatDate('en_GB', $date, 'EEEE, MMMM d yyyy'));

		// Without any specified time zone, the date is converted to the local timezone.
		$this->assertEquals('09:00 UTC+02:00', $manager->formatDate('fr_FR', $date, 'HH:mm ZZZZ'));
		$gmtDate = $manager->getGMTDateTime('2012-10-16 09:00:00');
		$this->assertEquals('11:00 UTC+02:00', $manager->formatDate('fr_FR', $gmtDate, 'HH:mm ZZZZ'));
		
		// If there is a specified time zone, the date is converted to it.
		$this->assertEquals('03:00 UTC-04:00', $manager->formatDate('fr_FR', $date, 'HH:mm ZZZZ', new \DateTimeZone('America/New_York')));
		$this->assertEquals('18:00 UTC+09:00', $manager->formatDate('fr_FR', $gmtDate, 'HH:mm ZZZZ', new \DateTimeZone('Asia/Tokyo')));
	}
	
	/**
	 * @depends testGetSetDateFormatsAndTimeZone
	 */
	public function testTransDate(I18nManager $manager)
	{
		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
		$this->assertEquals('dd/MM/yyyy', $manager->getDateFormat($manager->getLCID()));
		$this->assertEquals('16/10/2012', $manager->transDate($date));
	}
	
	/**
	 * @depends testGetSetDateFormatsAndTimeZone
	 */
	public function testTransDateTime(I18nManager $manager)
	{
		$date = $manager->getLocalDateTime('2012-10-16 09:00:00');
		$this->assertEquals('dd/MM/yyyy HH:mm', $manager->getDateTimeFormat($manager->getLCID()));
		$this->assertEquals('16/10/2012 09:00', $manager->transDateTime($date));
	}
	
	// Transformers.

	/**
	 * @depends testConstruct
	 */
	public function testTransformLab(I18nManager $manager)
	{
		$this->assertEquals('test :', $manager->transformLab('test', 'fr_FR'));
		$this->assertEquals('test:', $manager->transformLab('test', 'en_GB'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformUc(I18nManager $manager)
	{
		$this->assertEquals('TEST', $manager->transformUc('test', 'fr_FR'));
		$this->assertEquals('TEST', $manager->transformUc('tEsT', 'fr_FR'));
		$this->assertEquals('ÉTÉ ÇA', $manager->transformUc('été ça', 'fr_FR'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformUcf(I18nManager $manager)
	{
		$this->assertEquals('Test', $manager->transformUcf('test', 'fr_FR'));
		$this->assertEquals('TEsT', $manager->transformUcf('tEsT', 'fr_FR'));
		$this->assertEquals('Été ça', $manager->transformUcf('été ça', 'fr_FR'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformUcw(I18nManager $manager)
	{
		$this->assertEquals('Test Test', $manager->transformUcw('test test', 'fr_FR'));
		$this->assertEquals('Test', $manager->transformUcw('tEsT', 'fr_FR'));
		$this->assertEquals('Été Ça', $manager->transformUcw('été ça', 'fr_FR'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformLc(I18nManager $manager)
	{
		$this->assertEquals('test test', $manager->transformLc('test test', 'fr_FR'));
		$this->assertEquals('test', $manager->transformLc('tEsT', 'fr_FR'));
		$this->assertEquals('été ça été', $manager->transformLc('été ça Été', 'fr_FR'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformJs(I18nManager $manager)
	{
		$this->assertEquals('test \"test\"', $manager->transformJs('test "test"', 'fr_FR'));
		$this->assertEquals('tEsT \t \n \\\'test\\\' \\\\', $manager->transformJs("tEsT \t \n 'test' \\", 'fr_FR'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformHtml(I18nManager $manager)
	{
		$this->assertEquals("test <br />\n &lt;em&gt;toto&lt;/em&gt; &quot;test&quot;", $manager->transformHtml("test \n <em>toto</em> \"test\"", 'fr_FR'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformText(I18nManager $manager)
	{
		$source = '<h1>Titre</h1><p>Un<br/>paragraphe</p><ul><li>item 1</li><li>item 2</li></ul><hr><div class="test">Contenu du div</div>';
		$expected = 'Titre'.PHP_EOL.'Un'.PHP_EOL.'paragraphe'.PHP_EOL.' * item 1'.PHP_EOL.' * item 2'.PHP_EOL.'------'.PHP_EOL.'Contenu du div';
		$this->assertEquals($expected, $manager->transformText($source, 'fr_FR'));
		
		$source = '<table><tr><th>Titre 1</th><th>Titre 2</th></tr><tr><td>Cellule 1</td><td>Cellule 2</td></tr><table>';
		$expected = "Titre 1\tTitre 2\t".PHP_EOL."Cellule 1\tCellule 2";
		$this->assertEquals($expected, $manager->transformText($source, 'fr_FR'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformAttr(I18nManager $manager)
	{
		$this->assertEquals("test&quot;sqdqs&quot; qsdqsd&lt;sdsdf&gt;", $manager->transformAttr('test"sqdqs" qsdqsd<sdsdf>', 'fr_FR'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformSpace(I18nManager $manager)
	{
		$this->assertEquals(" test 3 ", $manager->transformSpace("test 3", 'fr_FR'));
		$this->assertEquals(" ... ", $manager->transformSpace("...", 'fr_FR'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testTransformEtc(I18nManager $manager)
	{
		$this->assertEquals("test 3...", $manager->transformEtc("test 3", 'fr_FR'));
		$this->assertEquals("......", $manager->transformEtc("...", 'fr_FR'));
	}
}
<?php
namespace ChangeTests\Rbs\Geo;

class GeoManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testGetCountriesByZoneCode()
	{
		$genericServices = new \Rbs\Generic\GenericServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$geoManager = $genericServices->getGeoManager();
		$this->assertInstanceOf('\Rbs\Geo\GeoManager', $geoManager);

		// No zone code specified.
		
		$this->assertCount(0, $geoManager->getCountriesByZoneCode(null));

		$documentManager = $this->getApplicationServices()->getDocumentManager();
		$tm  = $this->getApplicationServices()->getTransactionManager();
		$tm->begin();

		/** @var \Rbs\Geo\Documents\Country $FR */
		$FR = $documentManager->getNewDocumentInstanceByModelName('Rbs_Geo_Country');
		$FR->setCode('FR');
		$FR->setActive(true);
		$FR->setLabel('France');
		$FR->save();

		/** @var \Rbs\Geo\Documents\Country $DE */
		$DE = $documentManager->getNewDocumentInstanceByModelName('Rbs_Geo_Country');
		$DE->setCode('DE');
		$DE->setActive(false);
		$DE->setLabel('Germany');
		$DE->save();

		/** @var \Rbs\Geo\Documents\Country $IT */
		$IT = $documentManager->getNewDocumentInstanceByModelName('Rbs_Geo_Country');
		$IT->setCode('IT');
		$IT->setActive(true);
		$IT->setLabel('Italy');
		$IT->save();

		$countries = $geoManager->getCountriesByZoneCode(null);
		$this->assertCount(2, $countries);
		$this->assertContains($FR, $countries);
		$this->assertContains($IT, $countries);

		// A zone code is specified.

		// A country matches...
		$this->assertCount(0, $geoManager->getCountriesByZoneCode('BE'));

		/** @var \Rbs\Geo\Documents\Country $BE */
		$BE = $documentManager->getNewDocumentInstanceByModelName('Rbs_Geo_Country');
		$BE->setCode('BE');
		$BE->setActive(false);
		$BE->setLabel('Belgium');
		$BE->save();

		$this->assertCount(0, $geoManager->getCountriesByZoneCode('BE'));

		$BE->setActive(true);
		$BE->save();

		$countries = $geoManager->getCountriesByZoneCode('BE');
		$this->assertCount(1, $countries);
		$this->assertContains($BE, $countries);

		// A zone matches...
		$this->assertCount(0, $geoManager->getCountriesByZoneCode('FR-CONTINENTAL'));

		/** @var \Rbs\Geo\Documents\Zone $FRC */
		$FRC = $documentManager->getNewDocumentInstanceByModelName('Rbs_Geo_Zone');
		$FRC->setCode('FR-CONTINENTAL');
		$FRC->setLabel('France Continental');
		$FRC->setCountry($FR);
		$FRC->save();

		$countries = $geoManager->getCountriesByZoneCode('FR-CONTINENTAL');
		$this->assertCount(1, $countries);
		$this->assertContains($FR, $countries);

		$FR->setActive(false);
		$FR->save();

		$this->assertCount(0, $geoManager->getCountriesByZoneCode('FR-CONTINENTAL'));
	}
}
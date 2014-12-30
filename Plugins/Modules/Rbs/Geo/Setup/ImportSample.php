<?php
require_once(getcwd() . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

class RbsGeoImportSample
{

	public function import(\Change\Events\Event $event)
	{
		$application = $event->getApplication();
		$applicationServices = $event->getApplicationServices();

		$LCID = 'fr_FR';
		$applicationServices->getI18nManager()->setLCID($LCID);

		/** @var \Rbs\Generic\GenericServices $genericServices */
		$genericServices = $event->getServices('genericServices');

		$geoManager = $genericServices->getGeoManager();

		$country = $geoManager->getCountyByCode('FR');
		if (!$country) {
			echo 'FR country not found', PHP_EOL;
			return;
		}
		$documentManager = $event->getApplicationServices()->getDocumentManager();


		$fileName = __DIR__ . '/Assets/Samples/fr_regions.json';
		echo 'Import ', $fileName, PHP_EOL;
		$json = json_decode(file_get_contents($fileName), true);
		echo 'Nb regions ', count($json), PHP_EOL;



		$tm = $event->getApplicationServices()->getTransactionManager();
		$tm->begin();

		$regions = [];
		foreach ($json as $row)
		{
			$code = $row[0];
			$query = $documentManager->getNewQuery('Rbs_Geo_TerritorialUnit');
			$query->andPredicates($query->eq('code', $code));

			/** @var \Rbs\Geo\Documents\TerritorialUnit $region */
			$region = $query->getFirstDocument();
			if (!$region instanceof \Rbs\Geo\Documents\TerritorialUnit)
			{
				$region = $documentManager->getNewDocumentInstanceByModelName('Rbs_Geo_TerritorialUnit');
				$region->setCode($code);
				$region->setUnitType('REGION');
			}
			$region->setLabel($row[1]);
			$region->setTitle($row[1]);
			$region->setCountry($country);

			$region->save();
			$regions[$code] = $region;
			echo $region, ' ', $region->getCode(), ' ', $region->getLabel(), PHP_EOL;
		}

		$fileName = __DIR__ . '/Assets/Samples/fr_departements.json';
		echo 'Import ', $fileName, PHP_EOL;
		$json = json_decode(file_get_contents($fileName), true);
		echo 'Nb Départements ', count($json), PHP_EOL;

		foreach ($json as $row)
		{
			$code = $row[0];
			$query = $documentManager->getNewQuery('Rbs_Geo_TerritorialUnit');
			$query->andPredicates($query->eq('code', $code));

			/** @var \Rbs\Geo\Documents\TerritorialUnit $departement */
			$departement = $query->getFirstDocument();
			if (!$departement instanceof \Rbs\Geo\Documents\TerritorialUnit)
			{
				$departement = $documentManager->getNewDocumentInstanceByModelName('Rbs_Geo_TerritorialUnit');
				$departement->setCode($code);
				$departement->setUnitType('DEPARTEMENT');
			}
			$departement->setLabel($row[1]);
			$departement->setTitle($row[1]);
			$departement->setCountry($country);
			$region = $regions[$row[2]];
			$departement->setUnitParent($region);
			$departement->save();
			echo $departement, ' ', $departement->getCode(), ' ', $departement->getLabel(), '(', $region->getLabel(), ')', PHP_EOL;
		}
		$tm->commit();
	}
}

$eventManager = $application->getNewEventManager('ImportSample');
$eventManager->attach('import', function (\Change\Events\Event $event)
{
	(new RbsGeoImportSample())->import($event);
});

$eventManager->trigger('import', null, []);
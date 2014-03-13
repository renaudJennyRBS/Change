<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\Setup;

/**
 * @name \Rbs\Payment\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application $application
	 * @param \Change\Configuration\EditableConfiguration $configuration
	 * @throws \RuntimeException
	 */
	public function executeApplication($plugin, $application, $configuration)
	{
		parent::executeApplication($plugin, $application, $configuration);

		$requestBinary = $configuration->getEntry('Rbs/Payment/AtosSips/RequestBinary');
		if (!$requestBinary)
		{
			$requestBinary = 'App/bin/request';
			$configuration->addPersistentEntry('Rbs/Payment/AtosSips/RequestBinary', $requestBinary);
		}

		$filePath = $application->getWorkspace()->composeAbsolutePath($requestBinary);
		if (!file_exists($filePath))
		{
			\Change\Stdlib\File::mkdir(dirname($filePath));
			copy(__DIR__ . '/Assets/AtosSips/linux64/request', $filePath);
		}

		$responseBinary = $configuration->getEntry('Rbs/Payment/AtosSips/ResponseBinary');
		if (!$responseBinary)
		{
			$responseBinary = 'App/bin/response';
			$configuration->addPersistentEntry('Rbs/Payment/AtosSips/ResponseBinary', $responseBinary);
		}

		$filePath = $application->getWorkspace()->composeAbsolutePath($responseBinary);
		if (!file_exists($filePath))
		{
			\Change\Stdlib\File::mkdir(dirname($filePath));
			copy(__DIR__ . '/Assets/AtosSips/linux64/response', $filePath);
		}

		$dataDirectory = $configuration->getEntry('Rbs/Payment/AtosSips/DataDirectory');
		if (!$dataDirectory)
		{
			$dataDirectory = 'App/AtosSips';
			$configuration->addPersistentEntry('Rbs/Payment/AtosSips/DataDirectory', $dataDirectory);
		}

		$directory = $application->getWorkspace()->composeAbsolutePath($dataDirectory);
		if (!is_dir($directory))
		{
			\Change\Stdlib\File::mkdir($directory);
		}

		$pathfilePath = $directory . '/pathfile';
		if (!file_exists($pathfilePath))
		{
			$pathfileContent = file_get_contents(__DIR__ . '/Assets/AtosSips/pathfile');
			$pathfileContent = str_replace('[DATA_DIRECTORY]', $directory, $pathfileContent);
			\Change\Stdlib\File::write($pathfilePath, $pathfileContent);
		}

		$parmcomDefautPath = $directory . '/parmcom.defaut';
		if (!file_exists($parmcomDefautPath))
		{
			copy(__DIR__ . '/Assets/AtosSips/parmcom.defaut', $parmcomDefautPath);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices)
	{
		try
		{
			$applicationServices->getTransactionManager()->begin();

			$sipsConnector = null;
			$docs = $applicationServices->getDocumentCodeManager()->getDocumentsByCode('ATOS_TEST', 'Rbs_Payment_Setup');
			if (count($docs))
			{
				$sipsConnector = $docs[0];
			}

			if (!($sipsConnector instanceof \Rbs\Payment\Documents\AtosSipsConnector))
			{
				$sipsConnector = $applicationServices->getDocumentManager()
					->getNewDocumentInstanceByModelName('Rbs_Payment_AtosSipsConnector');
			}

			$sipsConnector->setCode('ATOS_TEST');
			$sipsConnector->setLabel('Connecteur bancaire ATOS de test');
			$sipsConnector->getCurrentLocalization()->setTitle('Connecteur bancaire ATOS de test');
			$sipsConnector->setMerchantId('011223344551111');
			$sipsConnector->setMinAmount(0.1);
			$sipsConnector->setErrorMail(true);
			$sipsConnector->setProcessingMail(true);
			$sipsConnector->setSuccessMail(true);
			$sipsConnector->setMerchantCountry('fr');
			$sipsConnector->setTpeParmcomContent(file_get_contents(__DIR__ . '/Assets/AtosSips/parmcom.011223344551111'));
			$sipsConnector->setTpeCertifContent(file_get_contents(__DIR__ . '/Assets/AtosSips/certif.fr.011223344551111.php'));

			if ($sipsConnector->getVisual() == null)
			{
				$applicationServices->getStorageManager();
				/** @var $visual \Rbs\Media\Documents\Image */
				$visual = $applicationServices->getDocumentManager()
					->getNewDocumentInstanceByModelName('Rbs_Media_Image');
				$visual->setLabel('credit-card');
				$visual->getCurrentLocalization()->setActive(true);
				$visual->getCurrentLocalization()->setAlt('Connecteur bancaire ATOS de test');
				copy(__DIR__ . '/Assets/AtosSips/credit-card.png', 'change://images/Rbs_Payment_Setup_credit_card.png');
				$visual->setPath('change://images/Rbs_Payment_Setup_credit_card.png');
				$visual->save();
				$sipsConnector->setVisual($visual);
			}
			$sipsConnector->save();

			$applicationServices->getDocumentCodeManager()->addDocumentCode($sipsConnector, 'ATOS_TEST', 'Rbs_Payment_Setup');

			$applicationServices->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			throw $applicationServices->getTransactionManager()->rollBack($e);
		}

		$applicationServices->getThemeManager()->installPluginTemplates($plugin);
		$applicationServices->getThemeManager()->installPluginAssets($plugin);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}

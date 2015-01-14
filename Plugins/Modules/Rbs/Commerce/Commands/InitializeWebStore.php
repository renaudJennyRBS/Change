<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Commands;

/**
 * @name \Rbs\Commerce\Commands\InitializeWebStore
 */
class InitializeWebStore extends \Rbs\Generic\Commands\AbstractInitialize
{
	/**
	 * @param \Change\Commands\Events\Event $event
	 * @throws \Exception
	 */
	public function execute(\Change\Commands\Events\Event $event)
	{
		$response = $event->getCommandResponse();
		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();

		$params = new \Zend\Stdlib\Parameters($event->paramsToArray());
		$website = $documentManager->getDocumentInstance($params->get('websiteId'));
		$store = $documentManager->getDocumentInstance($params->get('storeId'));
		$LCID = $params->get('LCID');
		$sidebarTemplate = $documentManager->getDocumentInstance($params->get('sidebarTemplateId'));
		$noSidebarTemplate = $documentManager->getDocumentInstance($params->get('noSidebarTemplateId'));
		$userAccountTopic = $documentManager->getDocumentInstance($params->get('userAccountTopicId'));
		$override = $params->get('override') == 'true';

		if (!($website instanceof \Rbs\Website\Documents\Website))
		{
			if ($response)
			{
				$response->addErrorMessage('Invalid arguments: website is not valid');
			}
			throw new \RuntimeException('Invalid arguments: website is not valid', 999999);
		}
		if (!($store instanceof \Rbs\Store\Documents\WebStore))
		{
			if ($response)
			{
				$response->addErrorMessage('Invalid arguments: store is not valid');
			}
			throw new \RuntimeException('Invalid arguments: store is not valid', 999999);
		}
		if (!($sidebarTemplate instanceof \Rbs\Theme\Documents\Template))
		{
			if ($response)
			{
				$response->addErrorMessage('Invalid arguments: sidebarTemplate is not valid');
			}
			throw new \RuntimeException('Invalid arguments: sidebarTemplate is not valid', 999999);
		}
		if (!($noSidebarTemplate instanceof \Rbs\Theme\Documents\Template))
		{
			if ($response)
			{
				$response->addErrorMessage('Invalid arguments: noSidebarTemplate is not valid');
			}
			throw new \RuntimeException('Invalid arguments: noSidebarTemplate is not valid', 999999);
		}
		if (!$LCID)
		{
			if ($response)
			{
				$response->addErrorMessage('Invalid arguments: LCID is not valid');
			}
			throw new \RuntimeException('Invalid arguments: LCID is not valid', 999999);
		}

		$context = 'Rbs Commerce WebStore Initialize ' . $website->getId() . ' ' . $store->getId();
		if ($userAccountTopic instanceof \Rbs\Website\Documents\Topic)
		{
			$applicationServices->getDocumentCodeManager()
				->addDocumentCode($userAccountTopic, 'rbs_commerce_initialize_user_account_topic', $context);
		}

		$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'store.json';
		$json = json_decode(file_get_contents($filePath), true);
		$json['contextId'] = $context;

		$import = $this->getImport($applicationServices, $LCID, !$override);

		$resolveDocument = function ($id, $contextId, $jsonDocument) use (
			$website, $store, $sidebarTemplate, $noSidebarTemplate, $documentManager, $import
		)
		{
			$document = null;
			switch ($id)
			{
				case 'no_side_bar_template':
					$document = $noSidebarTemplate;
					break;
				case 'side_bar_template':
					$document = $sidebarTemplate;
					break;
				case 'website':
					$document = $website;
					break;
				case 'store':
					$document = $store;
					break;

				default:
					if (substr($id, 0, 3) == 'f::')
					{
						$document = $this->resolveSectionPageFunction($jsonDocument, $import, $documentManager);
					}
			}
			return $document;
		};
		$import->getOptions()->set('resolveDocument', $resolveDocument);

		try
		{
			$applicationServices->getTransactionManager()->begin();
			$documents = $import->fromArray($json);
			$applicationServices->getTransactionManager()->commit();
		}
		catch (\Exception $e)
		{
			throw $applicationServices->getTransactionManager()->rollBack($e);
		}

		$this->publishDocuments($documents, $documentManager, $applicationServices->getAuthenticationManager()->getCurrentUser());

		// Keep generic document code if it's useful.
		if (!$userAccountTopic)
		{
			$documents = $applicationServices->getDocumentCodeManager()
				->getDocumentsByCode('rbs_commerce_initialize_user_account_topic', $context);
			if (isset($documents[0]) && $documents[0] != null)
			{
				$applicationServices->getDocumentCodeManager()
					->addDocumentCode($documents[0], 'user_account_topic', 'Website_' . $website->getId());
			}
		}

		$this->addMenuToTemplates($website->getId(), $noSidebarTemplate, $sidebarTemplate, $applicationServices);

		if ($response)
		{
			$response->addInfoMessage('Done.');
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function getGenericSettingsStructures(\Change\Events\Event $event)
	{
		$structures = $event->getParam('structures', []);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$structures['Rbs_Commerce_WebStore'] = [
			'title' => $i18nManager->trans('m.rbs.commerce.admin.initialize_for_web_store', ['ucf']),
			'href' => 'Rbs/Generic/InitializeWebStore/',
			'description' => $i18nManager->trans('m.rbs.commerce.admin.initialize_for_web_store_description', ['ucf'])
		];
		$event->setParam('structures', $structures);
	}
}
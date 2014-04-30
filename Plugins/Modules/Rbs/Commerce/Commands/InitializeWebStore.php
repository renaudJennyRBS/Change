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

		$params = new \Zend\Stdlib\Parameters((array)$event->getParams());
		$website = $applicationServices->getDocumentManager()->getDocumentInstance($params->get('websiteId'));
		$store = $applicationServices->getDocumentManager()->getDocumentInstance($params->get('storeId'));
		$LCID = $params->get('LCID');
		$sidebarTemplate = $applicationServices->getDocumentManager()->getDocumentInstance($params->get('sidebarTemplateId'));
		$noSidebarTemplate = $applicationServices->getDocumentManager()->getDocumentInstance($params->get('noSidebarTemplateId'));
		$userAccountTopic = $applicationServices->getDocumentManager()->getDocumentInstance($params->get('userAccountTopicId'));

		if ($sidebarTemplate instanceof \Rbs\Theme\Documents\Template &&
			$noSidebarTemplate instanceof \Rbs\Theme\Documents\Template &&
			$website instanceof \Rbs\Website\Documents\Website && $store instanceof \Rbs\Store\Documents\WebStore && $LCID)
		{
			$context = 'Rbs Commerce WebStore Initialize ' . $website->getId() . ' ' . $store->getId();
			if ($userAccountTopic instanceof \Rbs\Website\Documents\Topic)
			{
				$applicationServices->getDocumentCodeManager()->addDocumentCode($userAccountTopic, 'rbs_commerce_initialize_user_account_topic', $context);
			}

			$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'store.json';
			$json = json_decode(file_get_contents($filePath), true);
			$json['contextId'] = $context;

			$import = $this->getImport($applicationServices, $LCID);

			$resolveDocument = function ($id, $contextId) use ($website, $store, $sidebarTemplate, $noSidebarTemplate)
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

			$this->publishDocuments($documents, $applicationServices->getDocumentManager(), $applicationServices->getAuthenticationManager()->getCurrentUser());

			//keep generic document code if it's useful
			if (!$userAccountTopic)
			{
				$documents = $applicationServices->getDocumentCodeManager()->getDocumentsByCode('rbs_commerce_initialize_user_account_topic', $context);
				if (isset($documents[0]) && $documents[0] != null)
				{
					$applicationServices->getDocumentCodeManager()->addDocumentCode($documents[0], 'user_account_topic', 'Website_' . $website->getId());
				}
			}

			$this->addMenuToTemplates($website->getId(), $noSidebarTemplate, $sidebarTemplate, $applicationServices);

			$response->addInfoMessage('Done.');
		}
		else
		{
			$response->addErrorMessage('templates, store or website are not valid');
			throw new \RuntimeException('Invalid arguments: templates, store or website are not valid', 999999);
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
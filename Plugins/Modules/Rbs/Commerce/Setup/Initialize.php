<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Setup;

/**
* @name \Rbs\Commerce\Setup\Import
*/
class Initialize
{
	public function execute(\Change\Http\Event $event)
	{
		//import the default structure for website
		$applicationServices = $event->getApplicationServices();

		$params = $event->getRequest()->getPost();
		$websiteId = isset($params['websiteId']) ? $params['websiteId'] : null;
		$storeId = isset($params['storeId']) ? $params['storeId'] : null;
		$sidebarTemplateId = isset($params['sidebarTemplateId']) ? $params['sidebarTemplateId'] : null;
		$noSidebarTemplateId = isset($params['noSidebarTemplateId']) ? $params['noSidebarTemplateId'] : null;
		$LCID = isset($params['LCID']) ? $params['LCID'] : null;

		$website = $applicationServices->getDocumentManager()->getDocumentInstance($websiteId);
		$store = $applicationServices->getDocumentManager()->getDocumentInstance($storeId);
		$sidebarTemplate = $applicationServices->getDocumentManager()->getDocumentInstance($sidebarTemplateId);
		$noSidebarTemplate = $applicationServices->getDocumentManager()->getDocumentInstance($noSidebarTemplateId);

		if ($sidebarTemplate instanceof \Rbs\Theme\Documents\Template &&
			$noSidebarTemplate instanceof \Rbs\Theme\Documents\Template &&
			$website instanceof \Rbs\Website\Documents\Website && $store instanceof \Rbs\Store\Documents\WebStore && $LCID)
		{
			$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'store.json';
			$json = json_decode(file_get_contents($filePath), true);
			$json['contextId'] = 'Rbs Commerce WebStore Initialize ' . $websiteId . ' ' . $storeId;

			$i18nManager = $applicationServices->getI18nManager();
			$i18nManager->setLCID($LCID);
			$import = new \Rbs\Generic\Json\Import($applicationServices->getDocumentManager(), $i18nManager);
			$import->addOnly(true);
			$import->setDocumentCodeManager($applicationServices->getDocumentCodeManager());

			$resolveDocument = function ($id, $contextId) use ($website, $store, $sidebarTemplate, $noSidebarTemplate)
			{
				switch ($id)
				{
					case 'no_side_bar_template':
						return $noSidebarTemplate;
						break;
					case 'side_bar_template':
						return $sidebarTemplate;
						break;
					case 'website':
						return $website;
						break;
					case 'store':
						return $store;
						break;
				}
				return null;
			};
			$import->getOptions()->set('resolveDocument', $resolveDocument);

			$documents = [];
			try
			{
				$applicationServices->getTransactionManager()->begin();
				$documents = $import->fromArray($json);
				$applicationServices->getTransactionManager()->commit();
			}
			catch (\Exception $e)
			{
				$applicationServices->getTransactionManager()->rollBack($e);
				$applicationServices->getLogging()->error($e->getMessage());

				$result = new \Change\Http\Rest\Result\ErrorResult($e->getCode(), $e->getMessage());
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
				$event->setResult($result);
			}

			$user = $event->getAuthenticationManager()->getCurrentUser();
			//publish all publishable documents
			while (count($tasks = $this->getTasks($documents, $event->getApplicationServices()->getDocumentManager())))
			{
				foreach ($tasks as $task)
				{
					$task->setUserId($user->getId());
					$task->execute();
				}
			}

			$result = new \Change\Http\Rest\Result\ArrayResult();
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
			$event->setResult($result);
		}
		else
		{
			$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'templates, store or website are not valid');
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
			$event->setResult($result);
		}
	}

	/**
	 * @param array $documents
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Rbs\Workflow\Documents\Task[]
	 */
	protected function getTasks($documents, $documentManager)
	{
		if (!count($documents))
		{
			return [];
		}
		$query = $documentManager->getNewQuery('Rbs_Workflow_Task');
		$query->andPredicates(
			$query->eq('status', 'EN'),
			$query->in('taskCode', array('requestValidation', 'contentValidation', 'publicationValidation')),
			$query->in('document', $documents)
		);
		$tasks = $query->getDocuments();
		return $tasks;
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
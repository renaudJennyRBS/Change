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
* @name \Rbs\Commerce\Setup\InitializeOrderProcess
*/
class InitializeOrderProcess
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
		$popinTemplateId = isset($params['popinTemplateId']) ? $params['popinTemplateId'] : null;
		$LCID = isset($params['LCID']) ? $params['LCID'] : null;
		$userAccountTopicId = isset($params['userAccountTopicId']) ? $params['userAccountTopicId'] : null;

		$website = $applicationServices->getDocumentManager()->getDocumentInstance($websiteId);
		$store = $applicationServices->getDocumentManager()->getDocumentInstance($storeId);
		$sidebarTemplate = $applicationServices->getDocumentManager()->getDocumentInstance($sidebarTemplateId);
		$noSidebarTemplate = $applicationServices->getDocumentManager()->getDocumentInstance($noSidebarTemplateId);
		$userAccountTopic = $applicationServices->getDocumentManager()->getDocumentInstance($userAccountTopicId);
		$popinTemplate = $applicationServices->getDocumentManager()->getDocumentInstance($popinTemplateId);

		if ($sidebarTemplate instanceof \Rbs\Theme\Documents\Template &&
			$noSidebarTemplate instanceof \Rbs\Theme\Documents\Template &&
			$popinTemplate instanceof \Rbs\Theme\Documents\Template &&
			$website instanceof \Rbs\Website\Documents\Website && $store instanceof \Rbs\Store\Documents\WebStore && $LCID)
		{
			$context = 'Rbs Commerce Order Process Initialize ' . $websiteId . ' ' . $storeId;
			if ($userAccountTopic instanceof \Rbs\Website\Documents\Topic)
			{
				$applicationServices->getDocumentCodeManager()->addDocumentCode($userAccountTopic, 'rbs_commerce_initialize_user_account_topic', $context);
			}

			$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'order-process.json';
			$json = json_decode(file_get_contents($filePath), true);
			$json['contextId'] = $context;

			$i18nManager = $applicationServices->getI18nManager();
			$i18nManager->setLCID($LCID);
			$import = new \Rbs\Generic\Json\Import($applicationServices->getDocumentManager(), $i18nManager);
			$import->addOnly(true);
			$import->setDocumentCodeManager($applicationServices->getDocumentCodeManager());

			$resolveDocument = function ($id, $contextId) use ($website, $store, $sidebarTemplate, $noSidebarTemplate, $popinTemplate)
			{
				switch ($id)
				{
					case 'no_side_bar_template':
						return $noSidebarTemplate;
						break;
					case 'side_bar_template':
						return $sidebarTemplate;
						break;
					case 'popin_template':
						return $popinTemplate;
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

			//set initialized process to given store
			$orderProcesses = $applicationServices->getDocumentCodeManager()->getDocumentsByCode('rbs_commerce_initialize_order_process', $context);
			if (isset($orderProcesses[0]) && $orderProcesses[0] instanceof \Rbs\Commerce\Documents\Process)
			{
				$store->setOrderProcess($orderProcesses[0]);
				try
				{
					$applicationServices->getTransactionManager()->begin();
					$store->save();
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

			//keep generic document code if it's useful
			if (!$userAccountTopic)
			{
				$documents = $applicationServices->getDocumentCodeManager()->getDocumentsByCode('rbs_commerce_initialize_user_account_topic', $context);
				if (isset($documents[0]) && $documents[0] != null)
				{
					$applicationServices->getDocumentCodeManager()->addDocumentCode($documents[0], 'user_account_topic', 'Website_' . $websiteId);
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
		$structures['Rbs_Commerce_OrderProcess'] = [
			'title' => $i18nManager->trans('m.rbs.commerce.admin.initialize_for_order_process', ['ucf']),
			'href' => 'Rbs/Generic/InitializeOrderProcess/',
			'description' => $i18nManager->trans('m.rbs.commerce.admin.initialize_for_order_process_description', ['ucf'])
		];
		$event->setParam('structures', $structures);
	}
} 
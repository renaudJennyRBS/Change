<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Setup;

/**
* @name \Rbs\Generic\Setup\Initialize
*/
class Initialize
{
	public function execute(\Change\Http\Event $event)
	{
		//import the default structure for website
		$applicationServices = $event->getApplicationServices();

		$params = $event->getRequest()->getPost();
		$websiteId = isset($params['websiteId']) ? $params['websiteId'] : null;
		$sidebarTemplateId = isset($params['sidebarTemplateId']) ? $params['sidebarTemplateId'] : null;
		$noSidebarTemplateId = isset($params['noSidebarTemplateId']) ? $params['noSidebarTemplateId'] : null;
		$LCID = isset($params['LCID']) ? $params['LCID'] : null;

		$website = $applicationServices->getDocumentManager()->getDocumentInstance($websiteId);
		$sidebarTemplate = $applicationServices->getDocumentManager()->getDocumentInstance($sidebarTemplateId);
		$noSidebarTemplate = $applicationServices->getDocumentManager()->getDocumentInstance($noSidebarTemplateId);

		if ($sidebarTemplate instanceof \Rbs\Theme\Documents\Template &&
			$noSidebarTemplate instanceof \Rbs\Theme\Documents\Template &&
			$website instanceof \Rbs\Website\Documents\Website && $LCID)
		{
			$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'generic.json';
			$json = json_decode(file_get_contents($filePath), true);
			$json['contextId'] = 'Rbs Generic Website Initialize ' . $websiteId;

			$i18nManager = $applicationServices->getI18nManager();
			$i18nManager->setLCID($LCID);
			$import = new \Rbs\Generic\Json\Import($applicationServices->getDocumentManager(), $i18nManager);
			$import->addOnly(true);
			$import->setDocumentCodeManager($applicationServices->getDocumentCodeManager());

			$resolveDocument = function ($id, $contextId) use ($website, $sidebarTemplate, $noSidebarTemplate)
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
			$result = new \Change\Http\Rest\Result\ErrorResult(999999, 'templates or website are not valid');
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
		$structures['Rbs_Generic_Website'] = [
			'title' => $i18nManager->trans('m.rbs.generic.admin.initialize_for_website', ['ucf']),
			'href' => 'Rbs/Generic/InitializeWebsite/',
			'description' => $i18nManager->trans('m.rbs.generic.admin.initialize_for_website_description', ['ucf'])
		];
		$event->setParam('structures', $structures);
	}
} 
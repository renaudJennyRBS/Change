<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Commands;

use Change\Presentation\Layout\Layout;

/**
* @name \Rbs\Generic\Commands\AbstractInitialize
*/
abstract class AbstractInitialize
{
	/**
	 * TODO change
	 * @param \Change\Commands\Events\Event $event
	 */
	abstract public function execute(\Change\Commands\Events\Event $event);

	/**
	 * @param \Change\Events\Event $event
	 */
	abstract public function getGenericSettingsStructures(\Change\Events\Event $event);

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param string $LCID
	 * @return \Rbs\Generic\Json\Import
	 */
	protected function getImport($applicationServices, $LCID)
	{
		$i18nManager = $applicationServices->getI18nManager();
		$i18nManager->setLCID($LCID);
		$import = new \Rbs\Generic\Json\Import($applicationServices->getDocumentManager(), $i18nManager);
		$import->addOnly(true);
		$import->setDocumentCodeManager($applicationServices->getDocumentCodeManager());
		return $import;
	}

	/**
	 * @param \Change\Documents\AbstractDocument[] $documents
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\User\UserInterface $user
	 */
	protected function publishDocuments($documents, $documentManager, $user)
	{
		while (count($tasks = $this->getTasks($documents, $documentManager)))
		{
			foreach ($tasks as $task)
			{
				$task->setUserId($user->getId());
				$task->execute();
			}
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
	 * @param integer $websiteId
	 * @param \Rbs\Theme\Documents\Template $noSidebarTemplate
	 * @param \Rbs\Theme\Documents\Template $sidebarTemplate
	 * @param \Change\Services\ApplicationServices $applicationServices
	 */
	protected function addMenuToTemplates($websiteId, $noSidebarTemplate, $sidebarTemplate, $applicationServices)
	{
		$this->updateTemplateMenuParameters($noSidebarTemplate, $websiteId);
		$this->updateTemplateMenuParameters($sidebarTemplate, $websiteId);

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$noSidebarTemplate->update();
			$sidebarTemplate->update();
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			$transactionManager->rollBack($e);
			$applicationServices->getLogging()->error('Error when trying to update templates in Generic Website initialization: ' . $e->getMessage());
		}
	}

	/**
	 * @param \Rbs\Theme\Documents\Template $noSidebarTemplate
	 * @param integer $websiteId
	 * @param integer $toDisplayDocumentId
	 */
	protected function updateTemplateMenuParameters($noSidebarTemplate, $websiteId, $toDisplayDocumentId = null)
	{
		if ($toDisplayDocumentId === null)
		{
			$toDisplayDocumentId = $websiteId;
		}
		$websiteContents = $noSidebarTemplate->getContentByWebsite();
		if (!is_array($websiteContents))
		{
			$websiteContents = [];
		}

		if (isset($websiteContents[$websiteId]))
		{
			$websiteContent = new Layout($websiteContents[$websiteId]);
		}
		else
		{
			$websiteContent = new Layout();
		}

		$blockMenu = $websiteContent->getBlockById('mainMenu');
		if (!$blockMenu)
		{
			$layout = new Layout($noSidebarTemplate->getEditableContent());
			$blockMenu = $layout->getBlockById('mainMenu');
		}

		if ($blockMenu && $blockMenu->getName() == "Rbs_Website_Menu")
		{
			$parameters = $blockMenu->getParameters();
			$parameters['toDisplayDocumentId'] = $toDisplayDocumentId;
			$blockMenu->setParameters($parameters);
			$websiteContent->addItem($blockMenu);
			$websiteContents[$websiteId] = $websiteContent->toArray();
			$noSidebarTemplate->setContentByWebsite($websiteContents);
		}
	}
} 
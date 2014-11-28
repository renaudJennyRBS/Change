<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Events;

use Change\Documents\AbstractDocument;
use Change\Documents\Events\Event;
use Change\Documents\Interfaces\Publishable;
use Rbs\Website\Documents\Website;

/**
 * @name \Rbs\Website\Events\PathRuleBuilder
 */
class PathRuleBuilder
{
	/**
	 * @api
	 * @param AbstractDocument|Publishable|integer $document
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Logging\Logging $logging
	 */
	public function refreshDocumentPathRules($document,
		\Change\Http\Web\PathRuleManager$pathRuleManager,
		\Change\Documents\DocumentManager $documentManager,
		\Change\Logging\Logging $logging)
	{
		if (is_numeric($document)) {
			$document = $documentManager->getDocumentInstance($document);
		}

		if (!($document instanceof Publishable) || $document instanceof Website)
		{
			return;
		}
		$this->updateDocumentPathRules($document, true, $pathRuleManager, $documentManager, $logging);
	}

	/**
	 * @param Event $event
	 */
	public function updatePathRules($event)
	{
		$document = $event->getDocument();
		if (!($document instanceof Publishable) || $document instanceof Website)
		{
			return;
		}
		$modifiedPropertyNames = $event->getParam('modifiedPropertyNames');
		$refresh = (is_array($modifiedPropertyNames) && in_array('publicationSections', $modifiedPropertyNames));


		$logging = $event->getApplication()->getLogging();
		$applicationServices = $event->getApplicationServices();
		$pathRuleManager = $applicationServices->getPathRuleManager();
		$documentManager = $applicationServices->getDocumentManager();

		$this->updateDocumentPathRules($document, $refresh, $pathRuleManager, $documentManager, $logging);
	}

	/**
	 * @param AbstractDocument|Publishable $document
	 * @param boolean $refresh
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Logging\Logging $logging
	 */
	protected function updateDocumentPathRules($document, $refresh, $pathRuleManager, $documentManager, $logging)
	{
		$websiteIds = [];
		$allSectionIds = [0];

		if ($document instanceof \Rbs\Website\Documents\StaticPage)
		{
			$section = $document->getSection();
			if ($section)
			{
				$website = $section->getWebsite();
				if ($website)
				{
					$websiteIds[$website->getId()] = [];
				}
			}
		}
		else if ($document instanceof \Rbs\Website\Documents\Topic)
		{
			$website = $document->getWebsite();
			if ($website)
			{
				$websiteIds[$website->getId()] = [];
			}
		}
		else
		{
			foreach ($document->getPublicationSections() as $section)
			{
				$sectionId = $section->getId();
				$website = $section->getWebsite();
				if ($website)
				{
					$websiteId = $website->getId();
					if (!isset($websiteIds[$websiteId]))
					{
						$websiteIds[$websiteId] = [];
					}

					if ($websiteId != $sectionId && !in_array($sectionId, $websiteIds[$websiteId]))
					{
						$websiteIds[$websiteId][] = $sectionId;
						$allSectionIds[] = $sectionId;
					}
				}
			}
		}

		if ($refresh)
		{
			$oldRules = $pathRuleManager->getAllForDocumentId($document->getId());

			$websiteIdsArray = array_keys($websiteIds);
			foreach ($oldRules as $oldRule)
			{
				if ($oldRule->getDocumentId() != $document->getId() || $oldRule->getHttpStatus() != 200)
				{
					continue;
				}

				if (!in_array($oldRule->getWebsiteId(), $websiteIdsArray))
				{
					$pathRuleManager->updateRuleStatus($oldRule->getRuleId(), 301);
				}
				elseif (!in_array($oldRule->getSectionId(), $allSectionIds))
				{
					$pathRuleManager->updateRuleStatus($oldRule->getRuleId(), 301);
				}
			}
		}

		foreach ($websiteIds as $websiteId => $sectionIds)
		{
			/** @var $website \Rbs\Website\Documents\Website */
			$website = $documentManager->getDocumentInstance($websiteId);
			foreach ($website->getLCIDArray() as $LCID)
			{
				$documentManager->pushLCID($LCID);
				$publicationStatus = $document->getDocumentModel()->getPropertyValue($document, 'publicationStatus');
				if ($publicationStatus != Publishable::STATUS_DRAFT)
				{
					$this->updatePathRule($document, $LCID, $websiteId, 0, $documentManager, $pathRuleManager, $logging);
					foreach ($sectionIds as $sectionId)
					{
						$this->updatePathRule($document, $LCID, $websiteId, $sectionId, $documentManager, $pathRuleManager, $logging);
					}
				}
				$documentManager->popLCID();
			}
		}
	}

	/**
	 * @param \Rbs\Website\Documents\SectionPageFunction $sectionPageFunction
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 * @param \Change\Logging\Logging $logging
	 */
	public function updatePathRuleForSectionPageFunction(\Rbs\Website\Documents\SectionPageFunction $sectionPageFunction,
		\Change\Documents\DocumentManager $documentManager, \Change\Http\Web\PathRuleManager $pathRuleManager, $logging)
	{
		$page = $sectionPageFunction->getPage();
		if (!($page instanceof \Rbs\Website\Documents\FunctionalPage))
		{
			return;
		}

		$section = $sectionPageFunction->getSection();
		if ($section instanceof \Rbs\Website\Documents\Website)
		{
			$website = $section;
			$section = null;
		}
		elseif ($section instanceof \Rbs\Website\Documents\Topic)
		{
			$website = $section->getWebsite();
			if (!$website)
			{
				return;
			}
		}
		else
		{
			return;
		}
		$sectionId = $section ? $section->getId() : 0;

		foreach ($website->getLCIDArray() as $LCID)
		{
			$documentManager->pushLCID($LCID);
			$this->updatePathRule($page, $LCID, $website->getId(), $sectionId, $documentManager, $pathRuleManager, $logging);
			$documentManager->popLCID();
		}
	}

	/**
	 * @param AbstractDocument $document
	 * @param string $LCID
	 * @param integer $websiteId
	 * @param integer $sectionId
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 * @param \Change\Logging\Logging $logging
	 */
	protected function updatePathRule($document, $LCID, $websiteId, $sectionId, $documentManager, $pathRuleManager, $logging)
	{
		$tmpRule = $pathRuleManager->getNewRule($websiteId, $LCID,
			$pathRuleManager->getDefaultRelativePath($document, $sectionId),
			$document->getId(), 200, $sectionId);

		$newRule = $pathRuleManager->populatePathRuleByDocument($tmpRule, $document);
		if ($newRule === null)
		{
			return;
		}

		$newRuleQuery = $newRule->getQuery();
		$currentRule = null;

		/** @var integer[] $oldAliasedDocumentIds */
		$oldAliasedDocumentIds = [];

		$existingRules = $pathRuleManager->findPathRules($websiteId, $LCID, $document->getId(), $sectionId);

		foreach ($existingRules as $rule)
		{
			if ($rule->getDocumentId() != $document->getId())
			{
				continue;
			}

			if ($rule->getDocumentAliasId() != $newRule->getDocumentAliasId() && $newRule->getDocumentAliasId() != $document->getId())
			{
				$oldAliasedDocumentIds[] = $rule->getDocumentAliasId();
				$oldAliasedDocumentIds[] = $newRule->getDocumentAliasId();
				$rule->setDocumentAliasId($newRule->getDocumentAliasId());
				$pathRuleManager->updatePathRule($rule);
			}

			if ($rule->getQuery() == $newRuleQuery)
			{
				if ($rule->getUserEdited() || $rule->getRelativePath() == $newRule->getRelativePath())
				{
					$currentRule = $rule;
				}
			}
		}

		if (!$currentRule)
		{
			$successRuleId = -1;
			$ruleByPath = $pathRuleManager->getPathRule($websiteId, $LCID, $newRule->getRelativePath());
			if ($ruleByPath)
			{
				if ($ruleByPath->getDocumentId() == $document->getId() || $ruleByPath->getDocumentAliasId() == $document->getId())
				{
					$ruleByPath->setDocumentId($newRule->getDocumentId());
					$ruleByPath->setDocumentAliasId($newRule->getDocumentAliasId());
					$ruleByPath->setHttpStatus(200);
					$ruleByPath->setUserEdited(false);
					$ruleByPath->setQuery($newRuleQuery);
					$pathRuleManager->updatePathRule($ruleByPath);
					$successRuleId = $ruleByPath->getRuleId();
				}
				else
				{
					$newRule->setRelativePath($document->getId() . '/' . $newRule->getRelativePath());
					$ruleByPath = $pathRuleManager->getPathRule($websiteId, $LCID, $newRule->getRelativePath());
					if ($ruleByPath)
					{
						if ($ruleByPath->getDocumentId() == $document->getId()
							|| $ruleByPath->getDocumentAliasId() == $document->getId()
						)
						{
							$ruleByPath->setDocumentId($newRule->getDocumentId());
							$ruleByPath->setDocumentAliasId($newRule->getDocumentAliasId());
							$ruleByPath->setHttpStatus(200);
							$ruleByPath->setUserEdited(false);
							$ruleByPath->setQuery($newRuleQuery);
							$pathRuleManager->updatePathRule($ruleByPath);
							$successRuleId = $ruleByPath->getRuleId();
						}
						else
						{
							$logging->error('Duplicate relative path rule ' . $ruleByPath->getRuleId() . ' for document '
								. $document);
							return;
						}
					}
					else
					{
						$pathRuleManager->insertPathRule($newRule);
					}
				}
			}
			else
			{
				$pathRuleManager->insertPathRule($newRule);
			}

			foreach ($existingRules as $rule)
			{
				if ($rule->getDocumentId() != $document->getId())
				{
					continue;
				}
				if ($rule->getQuery() == $newRuleQuery && $rule->getRuleId() != $successRuleId)
				{
					$pathRuleManager->updateRuleStatus($rule->getRuleId(), 301);
				}
			}
		}

		foreach ($oldAliasedDocumentIds as $id)
		{
			if ($id)
			{
				$this->refreshDocumentPathRules($id, $pathRuleManager, $documentManager, $logging);
			}
		}
	}
} 
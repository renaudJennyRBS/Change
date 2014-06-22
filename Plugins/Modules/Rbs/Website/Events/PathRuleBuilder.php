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
	 * @param Event $event
	 */
	public function updatePathRules($event)
	{
		$document = $event->getDocument();
		if (!($document instanceof Publishable) || $document instanceof Website)
		{
			return;
		}

		$websiteIds = [];
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
		else
		{
			foreach ($document->getPublicationSections() as $section)
			{
				$website = $section->getWebsite();
				if ($website)
				{
					$websiteId = $website->getId();
					if (!isset($websiteIds[$websiteId]))
					{
						$websiteIds[$websiteId] = [$section->getId()];
					}
					elseif (!in_array($section->getId(), $websiteIds[$websiteId]))
					{
						$websiteIds[$websiteId][] = $section->getId();
					}
				}
			}
		}

		if (!count($websiteIds))
		{
			return;
		}

		$pathRuleManager = $event->getApplicationServices()->getPathRuleManager();
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$logging = $event->getApplicationServices()->getLogging();
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
					$this->updatePathRule($document, $LCID, $websiteId, 0, $pathRuleManager, $logging);
					foreach ($sectionIds as $sectionId)
					{
						$this->updatePathRule($document, $LCID, $websiteId, $sectionId, $pathRuleManager, $logging);
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
			$this->updatePathRule($page, $LCID, $website->getId(), $sectionId, $pathRuleManager, $logging);
			$documentManager->popLCID();
		}
	}

	/**
	 * @param AbstractDocument $document
	 * @param string $LCID
	 * @param integer $websiteId
	 * @param integer $sectionId
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 * @param \Change\Logging\Logging $logging
	 */
	protected function updatePathRule($document, $LCID, $websiteId, $sectionId, $pathRuleManager, $logging)
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
		$existingRules = $pathRuleManager->findPathRules($websiteId, $LCID, $document->getId(), $sectionId);
		foreach ($existingRules as $rule)
		{
			if ($rule->getDocumentId() != $newRule->getDocumentId()
				|| $rule->getDocumentAliasId() != $newRule->getDocumentAliasId()
			)
			{
				$rule->setDocumentId($newRule->getDocumentId());
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

		if ($currentRule !== null)
		{
			return;
		}

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
			if ($rule->getQuery() == $newRuleQuery && $rule->getRuleId() != $successRuleId)
			{
				$pathRuleManager->updateRuleStatus($rule->getRuleId(), 301);
			}
		}
	}
} 
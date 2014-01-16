<?php
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
				if ($website) {
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
					if (!isset($websiteIds[$websiteId])) {
						$websiteIds[$websiteId] = [$section->getId()];
					}
					elseif (!in_array($section->getId(), $websiteIds[$websiteId]))
					{
						$websiteIds[$websiteId][] = $section->getId();
					}
				}
			}
		}

		if (!count($websiteIds)) {
			return;
		}

		$pathRuleManager = $event->getApplicationServices()->getPathRuleManager();
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$logging = $event->getApplicationServices()->getLogging();
		foreach ($websiteIds as $websiteId => $sectionIds)
		{
			/** @var $website \Rbs\Website\Documents\Website */
			$website = $documentManager->getDocumentInstance($websiteId);
			foreach($website->getLCIDArray() as $LCID)
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
	 * @param AbstractDocument $document
	 * @param string $LCID
	 * @param integer $websiteId
	 * @param integer $sectionId
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 * @param \Change\Logging\Logging $logging
	 */
	protected function updatePathRule($document, $LCID, $websiteId, $sectionId, $pathRuleManager, $logging)
	{
		$tmpRule = $pathRuleManager->getNewRule($websiteId, $LCID, $pathRuleManager->getDefaultRelativePath($document, $sectionId),
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
			if ($rule->getDocumentId() != $newRule->getDocumentId() || $rule->getDocumentAliasId() != $newRule->getDocumentAliasId())
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
			}
			else
			{
				$newRule->setRelativePath($document->getId() . '/' . $newRule->getRelativePath());
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
					}
					else
					{
						$logging->error('Duplicate relative path rule '. $ruleByPath->getRuleId(). ' for document ' . $document);
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
			if ($rule->getQuery() == $newRuleQuery)
			{
				$pathRuleManager->updateRuleStatus($rule->getRuleId(), 301);
			}
		}
	}
} 
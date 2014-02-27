<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Actions;

use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Change\Http\Rest\Actions\PathRules
 */
class PathRules
{
	/**
	 * Use Event Params: documentId, modelName, [LCID]
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function get($event)
	{
		$result = new \Change\Http\Rest\Result\ArrayResult();
		$documentId = intval($event->getParam('documentId'));

		$dm = $event->getApplicationServices()->getDocumentManager();
		$document = $dm->getDocumentInstance($documentId);
		if (!($document instanceof \Change\Documents\Interfaces\Publishable))
		{
			return;
		}

		$pathRuleManager = $event->getApplicationServices()->getPathRuleManager();
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$pathRules = $pathRuleManager->getAllForDocumentId($documentId);
		$rules = [];
		$displayRules = [];
		foreach ($pathRules as $pathRule)
		{
			$key = $pathRule->getSectionId() . '-' . $pathRule->getWebsiteId() . '-' . $pathRule->getLCID();
			$ruleInfos = array(
				'id' => $pathRule->getRuleId(),
				'sectionId' => $pathRule->getSectionId(),
				'relativePath' => $pathRule->getRelativePath(),
				'query' => strval($pathRule->getQuery()),
				'userEdited' => $pathRule->getUserEdited(),
				'httpStatus' => $pathRule->getHttpStatus(),
			);
			$rules[] = $ruleInfos;
			switch ($pathRule->getHttpStatus())
			{
				case 200:
					$displayRules[$key]['urls'][] = $ruleInfos;
					break;
				case 301:
					$ruleInfos['permanent'] = true;
					$displayRules[$key]['redirects'][] = $ruleInfos;
					break;
				case 302:
					$ruleInfos['permanent'] = false;
					$displayRules[$key]['redirects'][] = $ruleInfos;
					break;
			}
		}

		$locations = array();
		$sections = $document->getPublicationSections();
		if ($sections instanceof \Change\Documents\DocumentArrayProperty)
		{
			$sections = $sections->toArray();
		}

		/* @var $section  \Change\Presentation\Interfaces\Section */
		foreach ($sections as $section)
		{
			/** @var $website \Change\Presentation\Interfaces\Website|\Change\Documents\Interfaces\Localizable */
			$website = $section->getWebsite();
			$LCIDs = $website->getLCIDArray();

			if (!($website instanceof \Change\Documents\Interfaces\Localizable))
			{
				continue;
			}
			$canonical = ($document->getCanonicalSection($website) == $section);

			if ($document instanceof \Change\Documents\Interfaces\Localizable)
			{
				$LCIDs = array_intersect($LCIDs, $document->getLCIDArray());
			}

			foreach ($LCIDs as $LCID)
			{
				try
				{
					$dm->pushLCID($LCID);
					$um = $website->getUrlManager($LCID);
					$um->setPathRuleManager($pathRuleManager);
					$um->setAbsoluteUrl(true);
					$baseUrl = $um->getByPathInfo(null)->normalize()->toString();
					if ($document instanceof \Change\Presentation\Interfaces\Page ||
						$document instanceof \Change\Presentation\Interfaces\Section)
					{
						$sectionId = 0;
					}
					else
					{
						$sectionId = $section->getId();
					}

					$location = array(
						'sectionId' => $sectionId,
						'sectionLabel' => $section->getTitle(),
						'websiteId' => $website->getId(),
						'baseUrl' => $baseUrl,
						'LCID' => $LCID,
						'canonical' => $canonical,
						'publication' => array(),
						'urls' => array(),
						'redirects' => array()
					);

					if ($canonical)
					{
						$key = '0-' . $website->getId() . '-' . $LCID;

						/* @var $document \Change\Documents\AbstractDocument */
						$pathRule = new \Change\Http\Web\PathRule();
						$pathRule->setWebsiteId($website->getId())
							->setLCID($LCID)->setDocumentId($document->getId())->setSectionId(0)
							->setHttpStatus(200);

						$generatedPathRule = $pathRuleManager->populatePathRuleByDocument($pathRule, $document);
						$defaultUrl = array(
							'relativePath' => $generatedPathRule ? $generatedPathRule->getRelativePath() : '',
							'sectionId' => 0,
							'query' => ''
						);
						$location['defaultCanonicalUrl'] = $defaultUrl;

						if (isset($displayRules[$key]['urls']))
						{
							$location['urls'] = array_merge($location['urls'], $displayRules[$key]['urls']);
						}

						if (isset($displayRules[$key]['redirects']))
						{
							$location['redirects'] = array_merge($location['redirects'], $displayRules[$key]['redirects']);
						}
					}

					if ($sectionId)
					{
						$key = $sectionId . '-' . $website->getId() . '-' . $LCID;

						/* @var $document \Change\Documents\AbstractDocument */
						$pathRule = new \Change\Http\Web\PathRule();
						$pathRule->setWebsiteId($website->getId())
							->setLCID($LCID)->setDocumentId($document->getId())->setSectionId($sectionId)
							->setHttpStatus(200);

						$generatedPathRule = $pathRuleManager->populatePathRuleByDocument($pathRule, $document);
						$defaultUrl = array(
							'relativePath' => $generatedPathRule ? $generatedPathRule->getRelativePath() : '',
							'sectionId' => $sectionId,
							'query' => ''
						);
						$location['defaultUrl'] = $defaultUrl;

						if (isset($displayRules[$key]['urls']))
						{
							$location['urls'] = array_merge($location['urls'], $displayRules[$key]['urls']);
						}

						if (isset($displayRules[$key]['redirects']))
						{
							$location['redirects'] = array_merge($location['redirects'], $displayRules[$key]['redirects']);
						}
					}

					$this->updateLocation($location, $document, $section, $website, $LCID, $i18nManager, $pathRuleManager);
					$locations[] = $location;

					$dm->popLCID();
				}
				catch (\Exception $e)
				{
					$dm->popLCID($e);
				}
			}
		}

		$result->setArray(['rules' => $rules, 'locations' => $locations]);
		$event->setResult($result);
	}

	/**
	 * @param array $location
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document
	 * @param \Change\Presentation\Interfaces\Section|\Change\Documents\Interfaces\Publishable $section
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 */
	protected function updateLocation(&$location, $document, $section, $website, $LCID, $i18nManager, $pathRuleManager)
	{
		$published = true;
		if ($document->published())
		{
			$location['publication'][] = array(
				'ok' => true,
				'message' => $i18nManager->trans('m.rbs.admin.admin.document_published_in_lang')
			);
		}
		else
		{
			$published = false;
			$location['publication'][] = array(
				'ok' => false,
				'message' => $i18nManager->trans('m.rbs.admin.admin.document_not_published_in_lang')
			);
		}

		if ($section->published())
		{
			$location['publication'][] = array(
				'ok' => true,
				'message' => $i18nManager->trans('m.rbs.admin.admin.section_published_in_lang')
			);
		}
		else
		{
			$published = false;
			$location['publication'][] = array(
				'ok' => false,
				'message' => $i18nManager->trans('m.rbs.admin.admin.section_not_published_in_lang')
			);
		}

		if (!($document instanceof \Change\Presentation\Interfaces\Page))
		{
			/* @var $document \Change\Documents\AbstractDocument */
			$pathRule = $pathRuleManager->getNewRule($website->getId(), $LCID, 'NULL', $document->getId(), 200,
				$section->getId());
			$params = array('website' => $website, 'pathRule' => $pathRule);
			$documentEvent = new DocumentEvent(DocumentEvent::EVENT_DISPLAY_PAGE, $document, $params);
			$document->getEventManager()->trigger($documentEvent);
			$page = $documentEvent->getParam('page');
			if ($page instanceof \Change\Presentation\Interfaces\Page)
			{
				if (!($page instanceof \Change\Documents\Interfaces\Publishable)
					|| ($page instanceof \Change\Documents\Interfaces\Publishable && $page->published())
				)
				{
					$location['publication'][] = array(
						'ok' => true,
						'message' => $i18nManager->trans('m.rbs.admin.admin.detail_function_provided_in_lang')
					);
				}
				else
				{
					$published = false;
					$location['publication'][] = array(
						'ok' => false,
						'message' => $i18nManager->trans('m.rbs.admin.admin.detail_function_not_published_in_lang')
					);
				}
			}
			else
			{
				$published = false;
				$location['publication'][] = array(
					'ok' => false,
					'message' => $i18nManager->trans('m.rbs.admin.admin.detail_function_not_provided_in_lang')
				);
			}
		}
		$location['published'] = $published;
	}

	/**
	 * Use Event Params: documentId, modelName, [LCID]
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function set($event)
	{
		$checkRule = $event->getRequest()->getPost('checkRule');
		if (is_array($checkRule))
		{
			$pathRuleManager = $event->getApplicationServices()->getPathRuleManager();
			$webstiteId = isset($checkRule['websiteId']) ? intval($checkRule['websiteId']) : false;
			$LCID = isset($checkRule['LCID']) ? strval($checkRule['LCID']) : false;
			$relativePath = isset($checkRule['relativePath']) ? strval($checkRule['relativePath']) : false;
			if ($webstiteId && $LCID && $relativePath)
			{
				$pathRule = $pathRuleManager->getPathRule($webstiteId, $LCID, $relativePath);
				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setArray($pathRule ? $pathRule->toArray() : ['checked' => true]);
				$event->setResult($result);
				return;
			}
			throw new \InvalidArgumentException('Arguments (websiteId, LCID, relativePath) required', 999999);
		}

		$documentId = intval($event->getParam('documentId'));
		$dm = $event->getApplicationServices()->getDocumentManager();
		$document = $dm->getDocumentInstance($documentId);
		$rules = $event->getRequest()->getPost('rules');
		if (is_array($rules))
		{
			$pathRuleManager = $event->getApplicationServices()->getPathRuleManager();
			$toAdd = [];
			$toUpdate = [];
			/** @var $existingRules \Change\Http\Web\PathRule[] */
			$existingRules = [];
			$pathRuleDocumentId = $documentId;
			$pathRuleDocumentAliasId = 0;

			foreach ($pathRuleManager->getAllForDocumentId($documentId) as $pathRule)
			{
				$existingRules[$pathRule->getRuleId()] = $pathRule;

				if ($pathRule->getHttpStatus() === 200)
				{
					$pathRuleDocumentId = $pathRule->getDocumentId();
					if ($pathRule->getDocumentAliasId())
					{
						$pathRuleDocumentAliasId = $pathRule->getDocumentAliasId();
					}
				}
				elseif ($pathRule->getDocumentAliasId() && !$pathRuleDocumentAliasId)
				{
					$pathRuleDocumentAliasId = $pathRule->getDocumentAliasId();
				}
			}

			foreach ($rules as $rule)
			{
				$ruleId = intval($rule['id']);
				if (isset($rule['updated']) && $rule['updated'] && isset($existingRules[$ruleId]))
				{
					$toUpdate[] = $rule;
				}
				elseif ($ruleId < 0 && $rule['relativePath'])
				{
					$defaultRelativePath = $pathRuleManager->getDefaultRelativePath($document, $rule['sectionId']);
					if ($defaultRelativePath != $rule['relativePath'])
					{
						$toAdd[] = $rule;
					}
				}
			}

			if (count($toUpdate) || count($toAdd))
			{
				$transactionManager = $event->getApplicationServices()->getTransactionManager();
				try
				{
					$transactionManager->begin();

					foreach ($toUpdate as $rule)
					{
						$pathRuleManager->updateRuleStatus($rule['id'], $rule['httpStatus']);
					}

					foreach ($toAdd as $rule)
					{
						$pathRule = $pathRuleManager->getNewRule($rule['websiteId'], $rule['LCID'], $rule['relativePath'],
							$pathRuleDocumentId, $rule['httpStatus'], $rule['sectionId'], $rule['query'], true);
						$pathRule->setDocumentAliasId($pathRuleDocumentAliasId);
						$pathRuleManager->insertPathRule($pathRule);
					}

					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}
		}
		$this->get($event);
	}
} 
<?php
namespace Rbs\Seo\Documents;

use Zend\Http\Response as HttpResponse;
use Change\Documents\Events\Event as DocumentEvent;

/**
 * @name \Rbs\Seo\Documents\DocumentSeo
 */
class DocumentSeo extends \Compilation\Rbs\Seo\Documents\DocumentSeo
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->getTarget())
		{
			return $this->getTarget()->getDocumentModel()->getPropertyValue($this->getTarget(), 'label', '-');
		}
		return '-';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		// Do nothing.
		return $this;
	}

	/**
	 * @var array $rules
	 */
	protected $rules;

	/**
	 * @return array
	 */
	public function getRules()
	{
		if ($this->rules === null)
		{
			$this->rules = $this->loadRules();
		}
		return $this->rules;
	}

	/**
	 * @param array $rules
	 * @return $this
	 */
	public function setRules($rules)
	{
		$this->rules = $rules;
		return $this;
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(DocumentEvent::EVENT_UPDATE, array($this, 'onDefaultUpdate'), 10);
	}

	public function onDefaultUpdate(DocumentEvent $event)
	{
		if (is_array($this->rules))
		{
			$pathRuleManager = new \Change\Http\Web\PathRuleManager($event->getApplicationServices()->getDbProvider());
			$toAdd = array();
			$toUpdate = array();
			foreach ($this->rules as $rule)
			{
				// Never save a rule with the default relative path.
				if ($pathRuleManager->getDefaultRelativePath($this->getTarget(), $rule['section_id']) == $rule['relative_path'])
				{
					continue;
				}
				elseif ($rule['rule_id'] < 0)
				{
					$toAdd[] = $rule;
				}
				elseif (isset($rule['updated']))
				{
					$toUpdate[] = $rule;
				}
			}

			foreach ($toUpdate as $rule)
			{
				$pathRuleManager->updateRuleStatus($rule['rule_id'], $rule['http_status']);
			}
			foreach ($toAdd as $rule)
			{
				$pathRule = $pathRuleManager->getNewRule($rule['website_id'], $rule['lcid'], $rule['relative_path'],
					$this->getTargetId(), $rule['http_status'], $rule['section_id'], $rule['query']);
				$pathRuleManager->insertPathRule($pathRule);
			}
			$this->rules = null;
		}
	}

	/**
	 * @return array
	 */
	protected function loadRules()
	{
		$dbProvider = $this->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder('DocumentSeo.getUrlInfos');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('rule_id'),
				$fb->column('lcid'), $fb->column('website_id'), $fb->column('section_id'),
				$fb->column('relative_path'), $fb->column('http_status'), $fb->column('query')
			);
			$qb->from($qb->getSqlMapping()->getPathRuleTable());
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('document_id'), $fb->integerParameter('documentId'))
			));
			$qb->orderAsc($fb->column('query'));
			$qb->orderDesc($fb->column('rule_id'));
		}

		$sq = $qb->query();
		$sq->bindParameter('documentId', $this->getTargetId());
		return $sq->getResults($sq->getRowsConverter()
			->addIntCol('rule_id', 'website_id', 'section_id', 'http_status')
			->addStrCol('lcid', 'relative_path', 'query'));
	}

	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		/** @var $document DocumentSeo */
		$document = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$documentResult = $restResult;
			$target = $document->getTarget();
			if (!($target instanceof \Change\Documents\Interfaces\Publishable))
			{
				return;
			}

			$rules = array();
			foreach ($document->getRules() as $rule)
			{
				$key = $rule['section_id'] . '-' . $rule['website_id'] . '-' . $rule['lcid'];
				$ruleInfos = array(
					'id' => $rule['rule_id'],
					'relativePath' => $rule['relative_path'],
					'query' => strval($rule['query'])
				);
				switch ($rule['http_status'])
				{
					case '200':
						$rules[$key]['urls'][] = $ruleInfos;
						break;

					case '301':
						$ruleInfos['permanent'] = true;
						$rules[$key]['redirects'][] = $ruleInfos;
						break;

					case '302':
						$ruleInfos['permanent'] = false;
						$rules[$key]['redirects'][] = $ruleInfos;
						break;
				}
			}

			$dm = $document->getDocumentManager();
			$locations = array();

			$sections = $target->getPublicationSections();
			if ($sections instanceof \Change\Documents\DocumentArrayProperty)
			{
				$sections = $sections->toArray();
			}

			/* @var $section \Rbs\Website\Documents\Section */
			foreach ($sections as $section)
			{
				$website = $section->getWebsite();
				if ($target instanceof \Change\Documents\Interfaces\Localizable)
				{
					$LCIDs = $target->getLCIDArray();
				}
				else
				{
					$LCIDs = $section->getLCIDArray();
				}
				foreach ($LCIDs as $LCID)
				{
					try
					{
						$dm->pushLCID($LCID);

						$canonical = $section == $target->getCanonicalSection($website);
						$um = $website->getUrlManager($LCID);
						$um->setAbsoluteUrl(true);
						$baseUrl = $um->getByPathInfo(null)->normalize()->toString();
						$sectionId = $canonical ? 0 : $section->getId();
						$location = array(
							'sectionId' => $sectionId,
							'sectionLabel' => $section->getTitle(),
							'websiteId' => $website->getId(),
							'websiteLabel' => $baseUrl,
							'baseUrl' => $baseUrl,
							'LCID' => $LCID,
							'canonical' => $canonical,
							'publication' => array(),
							'urls' => array(),
							'redirects' => array()
						);

						/* @var $target \Change\Documents\AbstractDocument */
						$um->setAbsoluteUrl(true);
						$pathRule = new \Change\Http\Web\PathRule();
						$pathRule->setWebsiteId($website->getId())
							->setLCID($LCID)
							->setDocumentId($target->getId())
							->setSectionId($canonical ? 0 : $section->getId())
							->setHttpStatus(200);
						$defaultUrl = array(
							'id' => 'auto',
							'relativePath' => $um->evaluateRelativePath($target, $pathRule),
							'query' => '',
							'defaultRelativePath' => $um->getDefaultDocumentPathInfo($target, ($canonical ? null : $section))
						);
						$location['defaultUrl'] = $defaultUrl;

						$key = $sectionId . '-' . $website->getId() . '-' . $LCID;
						if (isset($rules[$key]['urls']))
						{
							$location['urls'] = $rules[$key]['urls'];
						}

						$addDefault = true;
						foreach ($location['urls'] as $url)
						{
							if ($url['query'] == '')
							{
								$addDefault = false;
							}
						}
						if ($addDefault)
						{
							array_unshift($location['urls'], $defaultUrl);
						}

						if (isset($rules[$key]['redirects']))
						{
							$location['redirects'] = $rules[$key]['redirects'];
						}

						$document->updateLocation($event->getApplicationServices(), $location, $target, $section, $website, $LCID);

						if (!array_key_exists('published', $location))
						{
							$location['published'] = true;
							foreach ($location['publication'] as $row)
							{
								if (!$row['ok'])
								{
									$location['published'] = false;
									break;
								}
							}
						}

						$locations[] = $location;

						$dm->popLCID();
					}
					catch (\Exception $e)
					{
						$dm->popLCID($e);
					}
				}
			}

			$documentResult->setProperty('locations', $locations);
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$extraColumn = $event->getParam('extraColumn');
			$documentLink = $restResult;
			if (in_array('targetModelLabel', $extraColumn))
			{
				$i18n = $event->getApplicationServices()->getI18nManager();
				$label = $document->getTarget() ? $i18n->trans($document->getTarget()->getDocumentModel()->getLabelKey(), array('ucf')) : '';
				$documentLink->setProperty('targetModelLabel', $label);
			}
		}
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param array $location
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $target
	 * @param \Rbs\Website\Documents\Section $section
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 */
	protected function updateLocation($applicationServices, &$location, $target, $section, $website, $LCID)
	{
		$i18n = $applicationServices->getI18nManager();
		if ($target->published())
		{
			$location['publication'][] = array(
				'ok' => true,
				'message' => $i18n->trans('m.rbs.seo.admin.document-published-in-lang')
			);
		}
		else
		{
			$location['publication'][] = array(
				'ok' => false,
				'message' => $i18n->trans('m.rbs.seo.admin.document-not-published-in-lang')
			);
		}

		if ($section->published())
		{
			$location['publication'][] = array(
				'ok' => true,
				'message' => $i18n->trans('m.rbs.seo.admin.section-published-in-lang')
			);
		}
		else
		{
			$location['publication'][] = array(
				'ok' => false,
				'message' => $i18n->trans('m.rbs.seo.admin.section-not-published-in-lang')
			);
		}

		if (!($target instanceof \Rbs\Website\Documents\StaticPage))
		{
			/* @var $target \Change\Documents\AbstractDocument */
			$prm = new \Change\Http\Web\PathRuleManager($applicationServices->getDbProvider());
			$pathRule = $prm->getNewRule($website->getId(), $LCID, 'NULL', $target->getId(), 200, $section->getId());
			$params = array('website' => $website, 'pathRule' => $pathRule);
			$documentEvent = new DocumentEvent(DocumentEvent::EVENT_DISPLAY_PAGE, $target, $params);
			$target->getEventManager()->trigger($documentEvent);
			$page = $documentEvent->getParam('page');
			if ($page instanceof \Rbs\Website\Documents\FunctionalPage ||
				($page instanceof \Rbs\Website\Documents\StaticPage && $page->published()))
			{
				$location['publication'][] = array(
					'ok' => true,
					'message' => $i18n->trans('m.rbs.seo.admin.detail-function-provided-in-lang')
				);
			}
			else
			{
				$location['publication'][] = array(
					'ok' => false,
					'message' => $i18n->trans('m.rbs.seo.admin.detail-function-not-provided-in-lang')
				);
			}
		}
	}
}

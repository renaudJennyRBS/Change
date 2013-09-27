<?php
namespace Rbs\Seo\Documents;

use Zend\Http\Response as HttpResponse;

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
		return $this->getTarget() ? $this->getTarget()->getLabel() : '-';
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

	protected function onUpdate()
	{
		if (is_array($this->rules))
		{
			$this->updateDBRules();
		}
	}

	protected function updateDBRules()
	{
		$toAdd = array();
		$toUpdate = array();
		foreach ($this->rules as $rule)
		{
			if ($rule['rule_id'] < 0)
			{
				$toAdd[] = $rule;
			}
			elseif (isset($rule['updated']))
			{
				$toUpdate[] = $rule;
			}
		}

		$pathRuleManager = new \Change\Http\Web\PathRuleManager($this->getApplicationServices());
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

	/**
	 * @return array
	 */
	protected function loadRules()
	{
		$dbProvider = $this->getApplicationServices()->getDbProvider();
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

	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $documentResult
	 */
	protected function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);

		$target = $this->getTarget();
		if (!($target instanceof \Change\Documents\Interfaces\Publishable))
		{
			return;
		}

		$rules = array();
		foreach ($this->getRules() as $rule)
		{
			$key = $rule['section_id'] . '-' . $rule['website_id'] . '-' . $rule['lcid'];
			$ruleInfos = array(
				'id' => $rule['rule_id'],
				'relativePath' => $rule['relative_path'],
				'query' => $rule['query']
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

		$dm = $this->getDocumentServices()->getDocumentManager();
		$locations = array();
		foreach ($target->getPublicationSections() as $section)
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

					$key = $sectionId . '-' . $website->getId() . '-' . $LCID;
					if (isset($rules[$key]['urls']))
					{
						$location['urls'] = $rules[$key]['urls'];
					}
					else
					{
						/* @var $target \Change\Documents\AbstractDocument */
						$um->setAbsoluteUrl(true);
						$pathRule = new \Change\Http\Web\PathRule();
						$pathRule->setWebsiteId($website->getId())
							->setLCID($LCID)
							->setDocumentId($target->getId())
							->setSectionId($canonical ? 0 : $section->getId())
							->setHttpStatus(200);
						$location['urls'][] = $ruleInfos = array(
							'id' => 'auto',
							'relativePath' => $um->evaluateRelativePath($target, $pathRule),
							'query' => '',
							'defaultRelativePath' => $um->getDefaultDocumentPathInfo($target, ($canonical ? null : $section))
						);
					}

					if (isset($rules[$key]['redirects']))
					{
						$location['redirects'] = $rules[$key]['redirects'];
					}

					$this->updateLocation($location, $target, $section, $website, $LCID);

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

	/**
	 * @param array $location
	 * @param \Change\Documents\AbstractDocument $target
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param string $LCID
	 */
	protected function updateLocation(&$location, $target, $section, $website, $LCID)
	{
		// TODO event.
		$i18n = $this->getApplicationServices()->getI18nManager();
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

		if (true)
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

	/**
	 * @param \Change\Http\Rest\Result\DocumentLink $documentLink
	 * @param $extraColumn
	 */
	protected function updateRestDocumentLink($documentLink, $extraColumn)
	{
		parent::updateRestDocumentLink($documentLink, $extraColumn);

		if (in_array('targetModelLabel', $extraColumn))
		{
			$i18n = $this->getApplicationServices()->getI18nManager();
			$label = $this->getTarget() ? $i18n->trans($this->getTarget()->getDocumentModel()->getLabelKey(), array('ucf')) : '';
			$documentLink->setProperty('targetModelLabel', $label);
		}
	}
}

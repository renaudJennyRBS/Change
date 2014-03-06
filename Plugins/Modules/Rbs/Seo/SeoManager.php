<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Seo;

/**
 * @name \Rbs\Seo\SeoManager
 */
class SeoManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'SeoManager';

	const VARIABLE_REGEXP = '/\{([a-z][A-Za-z0-9.]*\.[a-z][A-Za-z0-9.]*)\}/';

	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager($transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}


	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Seo/Events/SeoManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getMetaVariables', array($this, 'onDefaultGetMetaVariables'), 5);
		$eventManager->attach('getMetaSubstitutions', array($this, 'onDefaultGetMetaSubstitutions'), 5);
		$eventManager->attach('getMetas', array($this, 'onDefaultGetMetas'), 5);
	}

	/**
	 * @param \Change\Presentation\Interfaces\Page $page
	 * @param \Change\Documents\AbstractDocument $document
	 * @return array
	 */
	public function getMetas($page, $document)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'page' => $page,
			'document' => $document
		));
		$eventManager->trigger('getMetas', $this, $args);
		return isset($args['metas']) ? $args['metas'] : array();
	}

	/**
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document
	 * @param \Change\Presentation\Interfaces\Page $page
	 * @param array $variables
	 * @return array
	 */
	public function getMetaSubstitutions($document, $page, $variables)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'variables' => $variables,
			'page' => $page,
			'document' => $document
		));
		$eventManager->trigger('getMetaSubstitutions', $this, $args);
		return isset($args['substitutions']) ? $args['substitutions'] : [];
	}

	/**
	 * @param string[] $functions
	 * @return array
	 */
	public function getMetaVariables($functions)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'functions' => $functions
		));
		$eventManager->trigger('getMetaVariables', $this, $args);
		return isset($args['variables']) ? $args['variables'] : [];
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 * @return array
	 */
	public function onDefaultGetMetaVariables($event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices instanceof \Change\Services\ApplicationServices)
		{
			$variables = ($event->getParam('variables')) ? $event->getParam('variables') : [];
			$i18nManager = $applicationServices->getI18nManager();
			$event->setParam('variables', array_merge($variables, [
				'document.title' => $i18nManager->trans('m.rbs.seo.admin.meta_variable_document_title', ['ucf']),
				'page.title' => $i18nManager->trans('m.rbs.seo.admin.meta_variable_page_title', ['ucf']),
				'page.website.title' => $i18nManager->trans('m.rbs.seo.admin.meta_variable_website_title', ['ucf']),
				'page.section.title' => $i18nManager->trans('m.rbs.seo.admin.meta_variable_section_title', ['ucf'])
			]));
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 * @return array
	 */
	public function onDefaultGetMetaSubstitutions($event)
	{
		$page = $event->getParam('page');
		$document = $event->getParam('document');
		if ($page instanceof \Rbs\Website\Documents\Page && $document instanceof \Change\Documents\Interfaces\Publishable)
		{
			$variables = $event->getParam('variables');
			$substitutions = ($event->getParam('substitutions')) ? $event->getParam('substitutions') : [];
			foreach ($variables as $variable)
			{
				switch ($variable)
				{
					case 'document.title':
						$substitutions['document.title'] = $document->getDocumentModel()->getPropertyValue($document, 'title');
						break;
					case 'page.title':
						$substitutions['page.title'] = $page->getCurrentLocalization()->getTitle();
						break;
					case 'page.website.title':
						$website = $page->getSection()->getWebsite();
						if ($website instanceof \Rbs\Website\Documents\Website)
						{
							$substitutions['page.website.title'] = $website->getTitle();
						}
						break;
					case 'page.section.title':
						$substitutions['page.section.title'] = $page->getSection()->getTitle();
						break;
				}
			}
			$event->setParam('substitutions', $substitutions);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetMetas(\Change\Events\Event $event)
	{
		$page = $event->getParam('page');
		$document = $event->getParam('document');

		/* @var $seoManager \Rbs\Seo\SeoManager */
		$seoManager = $event->getTarget();
		if ($page instanceof \Change\Presentation\Interfaces\Page && $document instanceof \Change\Documents\Interfaces\Publishable)
		{
			/* @var $document \Change\Documents\Interfaces\Publishable|\Change\Documents\AbstractDocument */
			$metas = [ 'title' => null, 'description' => null, 'keywords' => null ];

			$applicationServices = $event->getApplicationServices();
			$regExp = static::VARIABLE_REGEXP;
			$availableVariables = $seoManager->getMetaVariables(array_merge($document->getDocumentModel()->getAncestorsNames(), [$document->getDocumentModelName()]));

			$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Seo_DocumentSeo');
			$dqb->andPredicates($dqb->eq('target', $document));
			$documentSeo = $dqb->getFirstDocument();
			/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */

			$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Seo_ModelConfiguration');
			$dqb->andPredicates($dqb->eq('modelName', $document->getDocumentModelName()));

			/* @var $modelConfiguration \Rbs\Seo\Documents\ModelConfiguration */
			$modelConfiguration = $dqb->getFirstDocument();


			if ($documentSeo)
			{
				$foundVariables = $this->getAllVariablesFromDocumentSeo($regExp, $documentSeo);
				if ($modelConfiguration)
				{
					$foundVariables = array_merge($foundVariables, $this->getAllVariablesFromModelConfiguration($regExp, $modelConfiguration));
				}
				$variables = array_filter($foundVariables, function ($foundVariable) use ($availableVariables){
					return array_key_exists($foundVariable, $availableVariables);
				});

				$substitutions = $seoManager->getMetaSubstitutions($document, $page, $variables);

				$metaTitle = $documentSeo->getCurrentLocalization()->getMetaTitle();
				if ($metaTitle)
				{
					$metas['title'] = $this->getSubstitutedString($documentSeo->getCurrentLocalization()->getMetaTitle(), $substitutions, $regExp);
				}
				else if ($modelConfiguration)
				{
					$metas['title'] = $this->getSubstitutedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaTitle(), $substitutions, $regExp);
				}

				$metaDescription = $documentSeo->getCurrentLocalization()->getMetaDescription();
				if ($metaDescription)
				{
					$metas['description'] = $this->getSubstitutedString($metaDescription, $substitutions, $regExp);
				}
				elseif ($modelConfiguration)
				{
					$metas['description'] = $this->getSubstitutedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaDescription(), $substitutions, $regExp);
				}

				$metaKeywords = $documentSeo->getCurrentLocalization()->getMetaKeywords();
				if ($metaKeywords)
				{
					$metas['keywords'] = $this->getSubstitutedString($metaKeywords, $substitutions, $regExp);
				}
				elseif ($modelConfiguration)
				{
					$metas['keywords'] = $this->getSubstitutedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaKeywords(), $substitutions, $regExp);
				}
			}
			else
			{
				if ($modelConfiguration)
				{
					$foundVariables = $this->getAllVariablesFromModelConfiguration($regExp, $modelConfiguration);
					$variables = array_filter($foundVariables, function ($foundVariable) use ($availableVariables){
						return array_key_exists($foundVariable, $availableVariables);
					});
					$substitutions = $this->getMetaSubstitutions($document, $page, $variables);

					$defaultMetaTitle = $modelConfiguration->getCurrentLocalization()->getDefaultMetaTitle();
					if ($defaultMetaTitle)
					{
						$metas['title'] = $this->getSubstitutedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaTitle(), $substitutions, $regExp);
					}

					$defaultMetaDescription = $modelConfiguration->getCurrentLocalization()->getDefaultMetaDescription();
					if ($defaultMetaDescription)
					{
						$metas['description'] = $this->getSubstitutedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaDescription(), $substitutions, $regExp);
					}

					$defaultMetaKeywords = $modelConfiguration->getCurrentLocalization()->getDefaultMetaKeywords();
					if ($defaultMetaKeywords)
					{
						$metas['keywords'] = $this->getSubstitutedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaKeywords(), $substitutions, $regExp);
					}
				}
			}
			if (!$metas['title'])
			{
				$metas['title'] = $document->getDocumentModel()->getPropertyValue($document, 'title');
			}

			$event->setParam('metas', $metas);
		}
	}

	/**
	 * @param string $meta
	 * @param array $substitutions
	 * @param string $regExp
	 * @return string|null
	 */
	protected function getSubstitutedString($meta, $substitutions, $regExp)
	{
		if ($meta)
		{
			if (count($substitutions))
			{
				$meta = preg_replace_callback($regExp, function ($matches) use ($substitutions)
				{
					if (array_key_exists($matches[1], $substitutions))
					{
						return $substitutions[$matches[1]];
					}
					return '';
				}, $meta);
			}
		}
		return ($meta) ? $meta : null;
	}

	/**
	 * @param string $regExp
	 * @param \Rbs\Seo\Documents\DocumentSeo $documentSeo
	 * @return array
	 */
	protected function getAllVariablesFromDocumentSeo($regExp, $documentSeo)
	{
		$matches = [];
		preg_match_all($regExp, $documentSeo->getCurrentLocalization()->getMetaTitle(), $matches);
		$variables = $matches[1];
		preg_match_all($regExp, $documentSeo->getCurrentLocalization()->getMetaDescription(), $matches);
		$variables = array_merge($variables, $matches[1]);
		preg_match_all($regExp, $documentSeo->getCurrentLocalization()->getMetaKeywords(), $matches);
		$variables = array_merge($variables, $matches[1]);
		return $variables;
	}

	/**
	 * @param string $regExp
	 * @param \Rbs\Seo\Documents\ModelConfiguration $modelConfiguration
	 * @return array
	 */
	protected function getAllVariablesFromModelConfiguration($regExp, $modelConfiguration)
	{
		$matches = [];
		preg_match_all($regExp, $modelConfiguration->getCurrentLocalization()->getDefaultMetaTitle(), $matches);
		$variables = $matches[1];
		preg_match_all($regExp, $modelConfiguration->getCurrentLocalization()->getDefaultMetaDescription(), $matches);
		$variables = array_merge($variables, $matches[1]);
		preg_match_all($regExp, $modelConfiguration->getCurrentLocalization()->getDefaultMetaKeywords(), $matches);
		$variables = array_merge($variables, $matches[1]);
		return $variables;
	}

	/**
	 * @param \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable $document
	 * @return \Rbs\Seo\Documents\DocumentSeo
	 * @throws \Exception
	 */
	public function createSeoDocument($document)
	{
		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			$seo = $this->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_DocumentSeo');
			/* @var $seo \Rbs\Seo\Documents\DocumentSeo */
			$dqb = $this->getDocumentManager()->getNewQuery('Rbs_Website_Website');
			$websites = $dqb->getDocuments();
			$sitemapGenerateForWebsites = [];
			foreach ($websites as $website)
			{
				/* @var $website \Rbs\Website\Documents\Website */
				$sitemapGenerateForWebsites[$website->getId()] = [
					'label' => $website->getLabel(),
					'generate' => true
				];
			}
			$seo->setSitemapGenerateForWebsites($sitemapGenerateForWebsites);
			$seo->setTarget($document);
			$tm = $this->getTransactionManager();
			try
			{
				$tm->begin();
				$seo->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}

			return $seo;
		}
		return null;
	}

}
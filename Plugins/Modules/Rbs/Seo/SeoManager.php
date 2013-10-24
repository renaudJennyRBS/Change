<?php
namespace Rbs\Seo;

use Change\Events\EventsCapableTrait;

/**
 * @name \Rbs\Seo\SeoManager
 */
class SeoManager implements \Zend\EventManager\EventsCapableInterface
{
	use EventsCapableTrait, \Change\Services\DefaultServicesTrait {
		EventsCapableTrait::attachEvents as defaultAttachEvents;
	}

	const EVENT_MANAGER_IDENTIFIER = 'SeoManager';

	const VARIABLE_REGEXP = '/\{([a-z][A-Za-z0-9.]*\.[a-z][A-Za-z0-9.]*)\}/';

	/**
	 * @return \Change\Events\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		if ($this->sharedEventManager === null)
		{
			$this->sharedEventManager = $this->getApplication()->getSharedEventManager();
		}
		return $this->sharedEventManager;
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
		$config = $this->getApplication()->getConfiguration('Change/Events/SeoManager');
		return is_array($config) ? $config : array();
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 */
	protected function attachEvents(\Zend\EventManager\EventManager $eventManager)
	{
		$this->defaultAttachEvents($eventManager);
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
			'functions' => $functions,
			'documentServices' => $this->getDocumentServices()
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
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$variables = ($event->getParam('variables')) ? $event->getParam('variables') : [];
			$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
			$event->setParam('variables', array_merge($variables, [
				'document.title' => $i18nManager->trans('m.rbs.seo.services.seomanager.variable-document-title', ['ucf']),
				'page.title' => $i18nManager->trans('m.rbs.seo.services.seomanager.variable-page-title', ['ucf']),
				'page.website.title' => $i18nManager->trans('m.rbs.seo.services.seomanager.variable-website-title', ['ucf']),
				'page.section.title' => $i18nManager->trans('m.rbs.seo.services.seomanager.variable-section-title', ['ucf'])
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
	 * @param \Zend\EventManager\Event $event
	 */
	public function onDefaultGetMetas(\Zend\EventManager\Event $event)
	{
		$page = $event->getParam('page');
		$document = $event->getParam('document');
		/* @var $seoManager \Rbs\Seo\SeoManager */
		$seoManager = $event->getTarget();
		if ($page instanceof \Change\Presentation\Interfaces\Page && $document instanceof \Change\Documents\Interfaces\Publishable)
		{
			/* @var $document \Change\Documents\Interfaces\Publishable|\Change\Documents\AbstractDocument */
			$metas = [ 'title' => null, 'description' => null, 'keywords' => null ];

			$documentServices = $document->getDocumentServices();
			$regExp = static::VARIABLE_REGEXP;
			$availableVariables = $seoManager->getMetaVariables(array_merge($document->getDocumentModel()->getAncestorsNames(), [$document->getDocumentModelName()]));

			$dqb = new \Change\Documents\Query\Query($documentServices, 'Rbs_Seo_DocumentSeo');
			$dqb->andPredicates($dqb->eq('target', $document));
			$documentSeo = $dqb->getFirstDocument();
			/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */

			$dqb = new \Change\Documents\Query\Query($documentServices, 'Rbs_Seo_ModelConfiguration');
			$dqb->andPredicates($dqb->eq('modelName', $document->getDocumentModelName()));
			$modelConfiguration = $dqb->getFirstDocument();
			/* @var $modelConfiguration \Rbs\Seo\Documents\ModelConfiguration */

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
			$seo = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_DocumentSeo');
			/* @var $seo \Rbs\Seo\Documents\DocumentSeo */
			$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Website_Website');
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
			$tm = $this->getApplicationServices()->getTransactionManager();
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
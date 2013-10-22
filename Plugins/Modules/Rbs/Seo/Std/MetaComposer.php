<?php
namespace Rbs\Seo\Std;

/**
 * @name \Rbs\Seo\Std\MetaComposer
 */
class MetaComposer
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onGetMetas(\Zend\EventManager\Event $event)
	{
		$page = $event->getParam('page');
		$document = $event->getParam('document');
		/* @var $seoManager \Rbs\Seo\Services\SeoManager */
		$seoManager = $event->getTarget();
		if ($page instanceof \Change\Presentation\Interfaces\Page && $document instanceof \Change\Documents\Interfaces\Publishable)
		{
			/* @var $document \Change\Documents\Interfaces\Publishable|\Change\Documents\AbstractDocument */
			$metas = [ 'title' => null, 'description' => null, 'keywords' => null ];

			$documentServices = $document->getDocumentServices();
			$regExp = '/\{([a-z][A-Za-z0-9.]*\.[a-z][A-Za-z0-9.]*)\}/';
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
				$substitutions = $this->getSubstitutions($variables, $seoManager, $document, $page);

				$metaTitle = $documentSeo->getCurrentLocalization()->getMetaTitle();
				if ($metaTitle)
				{
					$metas['title'] = $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaTitle(), $substitutions, $regExp);
				}
				else if ($modelConfiguration)
				{
					$metas['title'] = $this->getSubstituedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaTitle(), $substitutions, $regExp);
				}

				$metaDescription = $documentSeo->getCurrentLocalization()->getMetaDescription();
				if ($metaDescription)
				{
					$metas['description'] = $this->getSubstituedString($metaDescription, $substitutions, $regExp);
				}
				elseif ($modelConfiguration)
				{
					$metas['description'] = $this->getSubstituedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaDescription(), $substitutions, $regExp);
				}

				$metaKeywords = $documentSeo->getCurrentLocalization()->getMetaKeywords();
				if ($metaKeywords)
				{
					$metas['keywords'] = $this->getSubstituedString($metaKeywords, $substitutions, $regExp);
				}
				elseif ($modelConfiguration)
				{
					$metas['keywords'] = $this->getSubstituedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaKeywords(), $substitutions, $regExp);
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
					$substitutions = $this->getSubstitutions($variables, $seoManager, $document, $page);

					$defaultMetaTitle = $modelConfiguration->getCurrentLocalization()->getDefaultMetaTitle();
					if ($defaultMetaTitle)
					{
						$metas['title'] = $this->getSubstituedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaTitle(), $substitutions, $regExp);
					}

					$defaultMetaDescription = $modelConfiguration->getCurrentLocalization()->getDefaultMetaDescription();
					if ($defaultMetaDescription)
					{
						$metas['description'] = $this->getSubstituedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaDescription(), $substitutions, $regExp);
					}

					$defaultMetaKeywords = $modelConfiguration->getCurrentLocalization()->getDefaultMetaKeywords();
					if ($defaultMetaKeywords)
					{
						$metas['keywords'] = $this->getSubstituedString($modelConfiguration->getCurrentLocalization()->getDefaultMetaKeywords(), $substitutions, $regExp);
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
	protected function getSubstituedString($meta, $substitutions, $regExp)
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
	 * @param array $variables
	 * @param \Rbs\Seo\Services\SeoManager $seoManager
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Presentation\Interfaces\Page $page
	 * @return array
	 */
	protected function getSubstitutions($variables, $seoManager, $document, $page)
	{
		$substitutions = [];
		if(count($variables))
		{
			$substitutions = array_merge(
				$seoManager->getMetaSubstitutions($document, $variables),
				$seoManager->getMetaSubstitutions($page, $variables)
			);
		}
		return $substitutions;
	}
}
<?php
namespace Rbs\Seo\Std;

/**
 * @name \Rbs\Seo\Std\MetaComposer
 */
class MetaComposer
{
	public function onGetMetas(\Zend\EventManager\Event $event)
	{
		$page = $event->getParam('page');
		$document = $event->getParam('document');
		/* @var $seoManager \Rbs\Seo\Services\SeoManager */
		$seoManager = $event->getTarget();
		if ($page instanceof \Change\Presentation\Interfaces\Page && $document instanceof \Change\Documents\Interfaces\Publishable)
		{
			$metas = [ 'title' => null, 'description' => null, 'keywords' => null ];

			$documentServices = $document->getDocumentServices();
			$documentSeoModel = $documentServices->getModelManager()->getModelByName('Rbs_Seo_DocumentSeo');
			$dqb = new \Change\Documents\Query\Query($documentServices, $documentSeoModel);
			$dqb->andPredicates($dqb->eq('target', $page));
			$pageSeo = $dqb->getFirstDocument();
			if ($pageSeo)
			{
				/* @var $pageSeo \Rbs\Seo\Documents\DocumentSeo */
				$pageRegExp = '/\{(page\.[a-z][A-Za-z0-9.]*)\}/';
				$documentRegExp = '/\{(document\.[a-z][A-Za-z0-9.]*)\}/';
				$pageVariables = $this->getAllVariables($pageRegExp, $pageSeo);
				$pageSubstitutions = (count($pageVariables)) ? $seoManager->getMetaSubstitutions($page, $pageVariables) : [];
				$documentVariables = $this->getAllVariables($documentRegExp, $pageSeo);
				$documentSubstitutions = (count($documentVariables)) ? $this->getDocumentMetaSubstitution($document, $documentRegExp, $documentVariables, $seoManager) : [];

				$metaTitle = $pageSeo->getCurrentLocalization()->getMetaTitle();
				if ($metaTitle)
				{
					if (count($pageSubstitutions))
					{
						$metaTitle = $this->getSubstituedString($metaTitle, $pageRegExp, $pageSubstitutions);
					}
					if (count($documentSubstitutions))
					{
						$metaTitle = $this->getSubstituedString($metaTitle, $documentRegExp, $documentSubstitutions);
					}
					$metas['title'] = $metaTitle;
				}
				$metaDescription = $pageSeo->getCurrentLocalization()->getMetaDescription();
				if ($metaDescription)
				{
					$metaDescription = $this->getSubstituedString($metaDescription, $pageRegExp, $pageSubstitutions);
					$metaDescription = $this->getSubstituedString($metaDescription, $documentRegExp, $documentSubstitutions);
					$metas['description'] = $metaDescription;
				}
				$metaKeywords = $pageSeo->getCurrentLocalization()->getMetaKeywords();
				if ($metaKeywords)
				{
					$metaKeywords = $this->getSubstituedString($metaKeywords, $pageRegExp, $pageSubstitutions);
					$metaKeywords = $this->getSubstituedString($metaKeywords, $documentRegExp, $documentSubstitutions);
					$metas['keywords'] = $metaKeywords;
				}
			}
			else
			{
				$metas['title'] = $document->getDocumentModel()->getPropertyValue($document, 'title');
			}
			$event->setParam('metas', $metas);
		}
	}

	/**
	 * @param string $meta
	 * @param string $regExp
	 * @param array $substitutions
	 * @return mixed
	 */
	protected function getSubstituedString($meta, $regExp, $substitutions)
	{
		if (!$meta)
		{
			return $meta;
		}
		return preg_replace_callback($regExp, function ($matches) use ($substitutions)
		{
			if (array_key_exists($matches[1], $substitutions))
			{
				return $substitutions[$matches[1]];
			}
			else
			{
				//TODO: no substitution was found, should we log a warning?
				return '';
			}
		}, $meta);
	}

	/**
	 * @param string $regExp
	 * @param \Rbs\Seo\Documents\DocumentSeo $documentSeo
	 * @return array
	 */
	protected function getAllVariables($regExp, $documentSeo)
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
	 * @param \Change\Documents\AbstractDocument $document
	 * @param string $regExp
	 * @param string[] $variables
	 * @param \Rbs\Seo\Services\SeoManager $seoManager
	 * @return array
	 */
	protected function getDocumentMetaSubstitution($document, $regExp, $variables, $seoManager)
	{
		/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
		$documentSeo = null;
		if (in_array('document.title', $variables) || in_array('document.description', $variables) || in_array('document.keywords', $variables))
		{
			$dqb = new \Change\Documents\Query\Query($document->getDocumentServices(), 'Rbs_Seo_DocumentSeo');
			$dqb->andPredicates($dqb->eq('target', $document));
			$documentSeo = $dqb->getFirstDocument();
			if ($documentSeo)
			{
				$variables = array_merge($variables, $this->getAllVariables($regExp, $documentSeo));
			}
		}

		$substitutions = [];
		if (count($variables))
		{
			$substitutions = $seoManager->getMetaSubstitutions($document, $variables);
		}

		if ($documentSeo)
		{
			$seoSubstitutions = [];
			$metaTitle = $documentSeo->getCurrentLocalization()->getMetaTitle();
			if ($metaTitle)
			{
				$seoSubstitutions['document.title'] = $this->getSubstituedString($metaTitle, $regExp, $substitutions);
			}
			$metaDescription = $documentSeo->getCurrentLocalization()->getMetaDescription();
			if ($metaDescription)
			{
				$seoSubstitutions['document.description'] = $this->getSubstituedString($metaDescription, $regExp, $substitutions);
			}
			$metaKeywords = $documentSeo->getCurrentLocalization()->getMetaKeywords();
			if ($metaKeywords)
			{
				$seoSubstitutions['document.keywords'] = $this->getSubstituedString($metaKeywords, $regExp, $substitutions);
			}
			$substitutions = array_merge($substitutions, $seoSubstitutions);
		}
		return $substitutions;
	}
}
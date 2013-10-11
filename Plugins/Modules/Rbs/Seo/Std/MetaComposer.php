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
			$documentSeo = $dqb->getFirstDocument();
			if ($documentSeo)
			{
				/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
				$regExp = '/\{([A-Za-z][A-Za-z0-9.]*)\}/';
				$variables = $this->getAllVariables($regExp, $documentSeo);
				if (count($variables))
				{
					$substitutions = $seoManager->getMetaSubstitutions($document, $variables);
					$substitutions = array_merge($substitutions, $seoManager->getMetaSubstitutions($page->getSection()->getWebsite(), $variables));
					$substitutions = array_merge($substitutions, $seoManager->getMetaSubstitutions($page->getSection(), $variables));

					$metas['title'] = $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaTitle(), $regExp, $substitutions);
					$metas['description'] = $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaDescription(), $regExp, $substitutions);
					$metas['keywords'] = $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaKeywords(), $regExp, $substitutions);

					//if $document has a document SEO, add his metas to
					$dqb = new \Change\Documents\Query\Query($documentServices, $documentSeoModel);
					$dqb->andPredicates($dqb->eq('target', $document));
					$documentSeo = $dqb->getFirstDocument();
					if ($documentSeo)
					{
						$variables = $this->getAllVariables($regExp, $documentSeo);
						if (count($variables))
						{
							$substitutions = $seoManager->getMetaSubstitutions($document, $variables);
							$metas['title'] .= ' ' . $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaTitle(), $regExp, $substitutions);
							$metas['description'] .= ' ' . $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaDescription(), $regExp, $substitutions);
							$metas['keywords'] .= ' ' . $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaKeywords(), $regExp, $substitutions);
						}
					}
				}
				else
				{
					$metas['title'] = $documentSeo->getCurrentLocalization()->getMetaTitle();
					$metas['description'] = $documentSeo->getCurrentLocalization()->getMetaDescription();
					$metas['keywords'] = $documentSeo->getCurrentLocalization()->getMetaKeywords();
				}
			}
			else
			{
				$metas['title'] = $document->getDocumentModel()->getPropertyValue($document, 'title');
			}


			/* @var $document \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable */
/*
			$dqb = new \Change\Documents\Query\Query($documentServices, $documentSeoModel);
			$dqb->andPredicates($dqb->eq('target', $document));
			$documentSeo = $dqb->getFirstDocument();
			if ($documentSeo)
			{
				/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
/*				$regExp = '/\{([A-Za-z][A-Za-z0-9.]*)\}/';
				$matches = [];
				preg_match_all($regExp, $documentSeo->getCurrentLocalization()->getMetaTitle(), $matches);
				$variables = $matches[1];
				preg_match_all($regExp, $documentSeo->getCurrentLocalization()->getMetaDescription(), $matches);
				$variables = array_merge($variables, $matches[1]);
				preg_match_all($regExp, $documentSeo->getCurrentLocalization()->getMetaKeywords(), $matches);
				$variables = array_merge($variables, $matches[1]);
				$substitutions = $seoManager->getMetaSubstitutions($document, $variables);

				$metas['title'] = $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaTitle(), $regExp, $substitutions);
				$metas['description'] = $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaDescription(), $regExp, $substitutions);
				$metas['keywords'] = $this->getSubstituedString($documentSeo->getCurrentLocalization()->getMetaKeywords(), $regExp, $substitutions);
			}
			else
			{
				$metas['title'] = $document->getDocumentModel()->getPropertyValue($document, 'title');
			}
*/
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
}
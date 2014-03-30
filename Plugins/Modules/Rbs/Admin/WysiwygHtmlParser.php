<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin;
use Change\Presentation\RichText\ParserInterface;

/**
 * @name \Rbs\Admin\WysiwygHtmlParser
 */
class WysiwygHtmlParser implements ParserInterface
{

	/**
	 * @var \Rbs\Website\Documents\Website|null
	 */
	protected $website;

	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 */
	public function __construct(\Change\Services\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @param null|\Rbs\Website\Documents\Website $website
	 */
	public function setWebsite($website)
	{
		$this->website = $website;
	}

	/**
	 * @return null|\Rbs\Website\Documents\Website
	 */
	public function getWebsite()
	{
		return $this->website;
	}

	/**
	 * @param integer $documentId
	 * @return string
	 */
	protected function getUrl($documentId)
	{
		$website = $this->website;
		$document = $this->applicationServices->getDocumentManager()->getDocumentInstance($documentId);
		if ($document instanceof \Rbs\Website\Documents\StaticPage)
		{
			$website = $document->getSection() ? $document->getSection()->getWebsite() : null;
		}
		elseif ($document instanceof \Change\Presentation\Interfaces\Section)
		{
			$website = $document->getWebsite();
		}

		if ($website)
		{
			$urlManager = $website->getUrlManager($this->applicationServices->getI18nManager()->getLCID());
			$urlManager->setPathRuleManager($this->applicationServices->getPathRuleManager());
			return $urlManager->getCanonicalByDocument($documentId)->normalize()->toString();
		}

		return 'javascript:;';
	}

	/**
	 * @param integer $documentId
	 * @param integer $width
	 * @param integer $height
	 * @return string|null
	 */
	protected function getImageUrl($documentId, $width, $height)
	{
		if ($this->applicationServices)
		{
			$image = $this->applicationServices->getDocumentManager()->getDocumentInstance($documentId);
			if ($image instanceof \Rbs\Media\Documents\Image)
			{
				return $image->getPublicURL($width, $height);
			}
		}
		return null;
	}

	/**
	 * @param string $rawText
	 * @param array $context
	 * @return string
	 */
	public function parse($rawText, $context)
	{
		if (isset($context['website']))
		{
			$this->website = $context['website'];
		}

		$replacements = array('<ul>' => '<ul class="bullet">');
		$rawText = strtr($rawText, $replacements);

		$rawText = $this->replaceHref($rawText);
		$rawText = $this->replaceSrc($rawText);

		return $rawText;
	}

	/**
	 * @param string $rawText
	 * @return string
	 */
	protected function replaceHref($rawText)
	{
		if (preg_match_all('/<a\s[^>]+>/i', $rawText, $matches))
		{
			$links = array_unique($matches[0]);
			foreach ($links as $link)
			{
				if (preg_match('/data-document-id="([0-9]+)"/', $link, $match))
				{
					$replaceLink = $link;
					$documentId = intval($match[1]);
					$href = $this->getUrl($documentId);

					if (preg_match('/href="([^"]+)"/', $link, $hrefMatch))
					{
						$replaceLink = str_replace($hrefMatch[0], '', $replaceLink);
					}

					$replaceLink = str_replace($match[0], $match[0] . ' href="' . \Change\Stdlib\String::attrEscape($href) . '"', $replaceLink);
					$rawText = str_replace($link, $replaceLink, $rawText);
				}
			}
			return $rawText;
		}
		return $rawText;
	}

	/**
	 * @param string $rawText
	 * @return string
	 */
	protected function replaceSrc($rawText)
	{
		if (preg_match_all('/<img\s[^>]+>/i', $rawText, $matches))
		{
			$links = array_unique($matches[0]);
			foreach ($links as $link)
			{
				if (preg_match('/data-document-id="([0-9]+)"/', $link, $match))
				{
					$width = 0;
					if (preg_match('/data-resize-width="([0-9]+)"/', $link, $dataMatch))
					{
						$width = intval($dataMatch[1]);
					}

					$height = 0;
					if (preg_match('/data-resize-height="([0-9]+)"/', $link, $dataMatch))
					{
						$height = intval($dataMatch[1]);
					}

					$replaceLink = $link;
					$documentId = intval($match[1]);
					$href = $this->getImageUrl($documentId, $width, $height);

					if (preg_match('/src="([^"]+)"/', $link, $dataMatch))
					{
						$replaceLink = str_replace($dataMatch[0], '', $replaceLink);
					}

					$replaceLink = str_replace($match[0], $match[0] . ' src="' . \Change\Stdlib\String::attrEscape($href) . '"', $replaceLink);
					$rawText = str_replace($link, $replaceLink, $rawText);
				}
			}
			return $rawText;
		}
		return $rawText;
	}

}
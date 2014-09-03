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
	 * @param \Change\Documents\AbstractDocument $document
	 * @return string
	 */
	protected function getUrl($document)
	{
		$website = $this->website;
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
			$urlManager = $website->getUrlManager($website->getLCID());
			return $urlManager->getCanonicalByDocument($document)->normalize()->toString();
		}

		return 'javascript:;';
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return string
	 */
	protected function getDownloadUrl($document)
	{
		$website = $this->website;
		if ($website)
		{
			$urlManager = $website->getUrlManager($website->getLCID());
			return $urlManager->getAjaxURL('Rbs_Media', 'Download', ['documentId' => $document->getId()]);
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
	 * @return string
	 */
	protected function replaceHref($rawText)
	{
		if (!preg_match_all('/<a\s[^>]+>.*<\/a>/Usi', $rawText, $matches))
		{
			return $rawText;
		}

		$links = array_unique($matches[0]);
		foreach ($links as $link)
		{
			if (!preg_match('/data-document-id="([0-9]+)"/', $link, $match))
			{
				continue;
			}

			$documentId = intval($match[1]);
			$document = $this->applicationServices->getDocumentManager()->getDocumentInstance($documentId);
			if ($document instanceof \Rbs\Media\Documents\File)
			{
				$href = $this->getDownloadUrl($document);
				$itemInfo = $document->getItemInfo();
				$size = $this->applicationServices->getI18nManager()->transFileSize($itemInfo->getSize());
				$suffix = '  [' . strtoupper($itemInfo->getExtension()) . ' — ' . $size . ']';
			}
			elseif ($document instanceof \Change\Documents\AbstractDocument && $document->getDocumentModel()->isPublishable())
			{
				$href = $this->getUrl($document);
				$suffix = '';
			}
			else
			{
				$href= 'javascript:;';
				$suffix = '';
			}
			$href = \Change\Stdlib\String::attrEscape($href);

			$replaceLink = $link;

			if (preg_match('/href="([^"]+)"/', $link, $hrefMatch))
			{
				$replaceLink = str_replace($hrefMatch[0], '', $replaceLink);
			}

			$replaceLink = str_replace($match[0], $match[0] . ' href="' . $href . '"', $replaceLink) . $suffix;
			$rawText = str_replace($link, $replaceLink, $rawText);
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
					if (preg_match('/width="([0-9]+)"/', $link, $dataMatch))
					{
						$width = intval($dataMatch[1]);
					}

					$height = 0;
					if (preg_match('/height="([0-9]+)"/', $link, $dataMatch))
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

					$replaceLink = str_replace($match[0], $match[0] . ' src="' . \Change\Stdlib\String::attrEscape($href) . '"',
						$replaceLink);
					$rawText = str_replace($link, $replaceLink, $rawText);
				}
			}
			return $rawText;
		}
		return $rawText;
	}
}
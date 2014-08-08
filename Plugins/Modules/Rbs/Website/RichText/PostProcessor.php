<?php
/**
 * Copyright (C) 2014 Gaël PORT
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\RichText;

/**
 * @name \Rbs\Website\RichText\MarkdownParser
 */
class PostProcessor
{
	/**
	 * @var \Rbs\Website\Documents\Website
	 */
	protected $website;

	/**
	 * @param string $html
	 * @param array $context
	 * @return string
	 */
	public function process($html, $context)
	{
		if(isset($context['currentURI']) && $context['currentURI'] instanceof \Zend\Uri\Http)
		{
			/* @var $currentURI \Zend\Uri\Http */
			$currentURI = $context['currentURI'];
			return preg_replace_callback(
				'/(<a[^>]*) href="#([^ ?&">\']+)" ([^>]*>)/si',
				function ($matches) use ($currentURI)
				{
					$currentURI->setFragment($matches[2]);
					return $matches[1] . ' href="' . $currentURI->normalize()->toString() . '" ' . $matches[3];
				},
				$html
			);
		}
		else
		{
			return preg_replace_callback(
				'/(<a[^>]*) href="#([^ ?&">\']+)" ([^>]*>)/si',
				function ($matches)
				{
					return $matches[1] . ' href="javascript:;" data-rbs-anchor="' . $matches[2] . '" ' . $matches[3];
				},
				$html
			);
		}
		// Fix links to current page's anchors to deal with <base href="..." />.


		/*return preg_replace_callback(
			'/(<a [^>]*href="#([^ ?&">\']+)")([^>]*>)/si',
			function ($matches)
			{
				if (preg_match('/onclick=".*"/si', $matches[0]))
				{
					return $matches[0];
				}
				return $matches[1] . ' onclick="document.location.hash = \'' . $matches[2] . '\'; return false;"' . $matches[3];
			},
			$html
		);*/
	}
}
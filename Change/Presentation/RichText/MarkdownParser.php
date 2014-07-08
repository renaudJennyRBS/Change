<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\RichText;

/**
 * @name \Change\Presentation\RichText\MarkdownParser
 */
class MarkdownParser extends \Michelf\Markdown
{
	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param \Change\Services\ApplicationServices|null $applicationServices
	 */
	public function __construct($applicationServices)
	{
		parent::__construct();
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @param $matches
	 * @return string
	 */
	protected function _doImages_inline_callback($matches)
	{
		$mediaId = $matches[3] == '' ? $matches[4] : $matches[3];
		$title = isset($matches[7]) ? $matches[7] : null;
		$alt_text = $matches[2];
		$alt_text = $this->encodeAttribute($alt_text);

		$params = explode(',', $mediaId);
		$id = $params[0];
		if (count($params) === 2)
		{
			$params = $params[1];
		}
		else
		{
			$params = '';
		}

		// If the id is not numeric, this is an external image, so use de default image rendering.
		if (!is_numeric($id))
		{
			return parent::_doImages_inline_callback($matches);
		}

		$media = $this->applicationServices->getDocumentManager()->getDocumentInstance($id);
		if ($media instanceof \Rbs\Media\Documents\Image)
		{
			return $this->doParseImageTag($params, $media, $title, $alt_text, $mediaId);
		}
		elseif ($media instanceof \Rbs\Media\Documents\Video)
		{
			return $this->doParseVideoTag($params, $media, $title, $alt_text, $mediaId);
		}
		else
		{
			return $this->hashPart('<span class="label label-danger">Invalid Rbs\Media\Image: ' . $mediaId . '</span>');
		}
	}

	/**
	 * @param String $params
	 * @param \Rbs\Media\Documents\Image $image
	 * @param String $title
	 * @param String $alt_text
	 * @param String $mediaId
	 * @return string
	 */
	protected function doParseImageTag($params, $image, $title, $alt_text, $mediaId)
	{
		$matches = array();
		if ($params && preg_match('/^(\d+)[x\*](\d+)$/', $params, $matches))
		{
			$url = $image->getPublicURL($matches[1], $matches[2]);
		}
		else
		{
			$url = $image->getPublicURL();
		}

		if (!$url)
		{
			return $this->hashPart('<span class="label label-danger">No public URL for Rbs\Media\Image: ' . $mediaId . '</span>');
		}

		$result = '<img src="' . $url . '" alt="' . $alt_text . '"';
		if (isset($title))
		{
			$title = $this->encodeAttribute($title);
			$result .= ' title="' . $title . '"'; // $title already quoted
		}
		if ($params)
		{
			$result .= ' style="' . $params . '"';
		}
		$result .= $this->empty_element_suffix;

		return $this->hashPart($result);
	}

	/**
	 * @param String $params
	 * @param \Rbs\Media\Documents\Video $video
	 * @param String $title
	 * @param String $alt_text
	 * @param String $mediaId
	 * @return string
	 */
	protected function doParseVideoTag($params, $video, $title, $alt_text, $mediaId)
	{
		$matches = array();
		if ($params && preg_match('/^(\d+)[x\*](\d+)$/', $params, $matches))
		{
			$url = $video->getPublicURL($matches[1], $matches[2]);
		}
		else
		{
			$url = $video->getPublicURL();
		}

		if (!$url)
		{
			return $this->hashPart('<span class="label label-danger">No public URL for Rbs\Media\Video: ' . $mediaId . '</span>');
		}

		$result = '<video src="' . $url . '" preload="auto" controls="controls"';
		if (isset($title))
		{
			$title = $this->encodeAttribute($title);
			$result .= ' title="' . $title . '"'; // $title already quoted
		}
		if ($params)
		{
			$result .= ' style="' . $params . '"';
		}
		$result .= '>' . $alt_text . '</video>';

		return $this->hashPart($result);
	}
}
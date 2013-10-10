<?php

namespace Change\Presentation\RichText;

use Change\Documents\DocumentServices;
use Change\Http\Web\UrlManager;

/**
 * Class MarkdownParser
 * @package Change\Presentation\Markdown
 */
class MarkdownParser extends \Michelf\Markdown {


	/**
	 * @var DocumentServices|null
	 */
	protected $documentServices;

	/**
	 * @var \Rbs\Website\Documents\Website|null
	 */
	protected $website;


	/**
	 * @param DocumentServices|null $documentServices
	 */
	public function __construct($documentServices)
	{
		parent::__construct();
		$this->documentServices = $documentServices;
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
	 * @param $matches
	 * @return string
	 */
	protected function _doImages_inline_callback($matches)
	{
		$alt_text = $matches[2];
		$mediaId  = $matches[3] == '' ? $matches[4] : $matches[3];
		$title    = isset($matches[7]) ? $matches[7] : null;
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

		/* @var $image \Rbs\Media\Documents\Image */
		$image = $this->documentServices->getDocumentManager()->getDocumentInstance($id);
		if (!$image)
		{
			return $this->hashPart('<span class="label label-danger">Invalid Rbs\Media\Image: ' . $mediaId . '</span>');
		}

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

		$result = "<img src=\"$url\" alt=\"$alt_text\"";
		if (isset($title))
		{
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\""; # $title already quoted
		}
		if ($params)
		{
			$result .=  " style=\"$params\"";
		}
		$result .= $this->empty_element_suffix;

		return $this->hashPart($result);
	}

}
<?php

namespace Change\Presentation\Markdown;

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
	protected function _doAnchors_inline_callback($matches)
	{
		$whole_match	=  $matches[1];
		$link_text		=  $this->runSpanGamut($matches[2]);
		$documentId 	=  $matches[3] == '' ? $matches[4] : $matches[3];
		$title			=& $matches[7];

		$params = explode(',', $documentId);
		$model = null;

		/**
		 * @var \Change\Documents\AbstractDocument
		 */
		$document = null;

		if (count($params) === 1)
		{
			$id = $params[0];
		}
		elseif (count($params) === 2)
		{
			$model = $this->documentServices->getModelManager()->getModelByName($params[0]);
			$id = $params[1];
		}

		$document = $this->documentServices->getDocumentManager()->getDocumentInstance($id, $model);

		if (!$document)
		{
			return $this->hashPart('<span class="label label-important">Invalid Document: ' . $documentId . '</span>');
		}

		if ($this->website)
		{
			$url = $this->website->getUrlManager($this->website->getLCID())->getCanonicalByDocument($document);
		}
		else
		{
			$url = "javascript:;";
		}


		$result = "<a href=\"$url\"";
		if (isset($title)) {
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\"";
		}

		$link_text = $this->runSpanGamut($link_text);
		$result .= ">$link_text</a>";

		return $this->hashPart($result);
	}



	/**
	 * @param $matches
	 * @return string
	 */
	protected function _doImages_inline_callback($matches)
	{
		$alt_text		= $matches[2];
		$mediaId		= $matches[3] == '' ? $matches[4] : $matches[3];
		$title			=& $matches[7];
		$alt_text = $this->encodeAttribute($alt_text);

		$params = explode(',', $mediaId);
		$model = null;

		if (count($params) === 2)
		{
			if (is_numeric($params[0]))
			{
				$id = $params[0];
				$params = $params[1];
			}
			else
			{
				$model = $this->documentServices->getModelManager()->getModelByName($params[0]);
				$id = $params[1];
				$params = null;
			}
		}
		elseif (count($params) === 3)
		{
			$model = $this->documentServices->getModelManager()->getModelByName($params[0]);
			$id = $params[1];
			$params = $params[2];
		}

		/**
		 * @var \Rbs\Media\Documents\Image
		 */
		$document = $this->documentServices->getDocumentManager()->getDocumentInstance($id, $model);

		if (!$document)
		{
			return $this->hashPart('<span class="label label-important">Invalid Rbs\Media\Image: ' . $mediaId . '</span>');
		}

		// FIXME Generate real Media URL
		$url = '/rest.php/storage/' . substr($document->getPath(), 9) . '?content=1';

		$result = "<img src=\"$url\" alt=\"$alt_text\"";
		if (isset($title)) {
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\""; # $title already quoted
		}
		if ($params) {
			$result .=  " style=\"$params\"";
		}
		$result .= $this->empty_element_suffix;

		return $this->hashPart($result);
	}

}
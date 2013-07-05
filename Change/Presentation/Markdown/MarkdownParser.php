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

		$url = $this->generateDocumentLink($documentId);

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
	 * @param $documentId string
	 */
	protected function generateDocumentLink($documentId)
	{
		$params = explode(',', $documentId);
		$modelName = $params[0];
		$id = $params[1];

		$model = $this->documentServices->getModelManager()->getModelByName($modelName);
		/**
		 * @var \Change\Documents\AbstractDocument
		 */
		$document = $this->documentServices->getDocumentManager()->getDocumentInstance($id, $model);

		if ($this->website)
		{
			return $this->website->getUrlManager($this->website->getLCID())->getCanonicalByDocument($document);
		}
		else
		{
			return "javascript:;";
		}
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
		$modelName = $params[0];
		$id = $params[1];
		if (count($params) === 3)
		{
			$params = $params[2];
		}
		else
		{
			$params = null;
		}

		$model = $this->documentServices->getModelManager()->getModelByName($modelName);
		/**
		 * @var \Rbs\Media\Documents\Image
		 */
		$document = $this->documentServices->getDocumentManager()->getDocumentInstance($id, $model);

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
<?php
namespace Rbs\Website\RichText;
use Change\Presentation\RichText\ParserInterface;

/**
 * @name \Rbs\Admin\MarkdownParser
 */
class MarkdownParser extends \Change\Presentation\RichText\MarkdownParser implements ParserInterface
{
	/**
	 * @var \Rbs\Website\Documents\Website
	 */
	protected $website;

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
		return $this->transform($rawText);
	}


	/**
	 * @param $matches
	 * @return string
	 */
	protected function _doAnchors_inline_callback($matches)
	{
		$link_text  = $this->runSpanGamut($matches[2]);
		$documentId = $matches[3] == '' ? $matches[4] : $matches[3];
		$title      = $matches[7];

		$params = explode(',', $documentId);
		$model = null;

		if (count($params) === 1)
		{
			$id = $params[0];
		}
		elseif (count($params) === 2)
		{
			$model = $this->documentServices->getModelManager()->getModelByName($params[0]);
			$id = $params[1];
		}

		/* @var $document \Change\Documents\AbstractDocument */
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
		if (isset($title))
		{
			$title = $this->encodeAttribute($title);
			$result .=  " title=\"$title\"";
		}

		$link_text = $this->runSpanGamut($link_text);
		$result .= ">$link_text</a>";

		return $this->hashPart($result);
	}


}
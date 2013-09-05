<?php
namespace Rbs\Website\RichText;
use Change\Presentation\RichText\ParserInterface;

/**
 * @name \Rbs\Website\RichText\MarkdownParser
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

		// If the id is not numeric, this is an external link, so use de default link rendering.
		if (!is_numeric($id))
		{
			return parent::_doAnchors_inline_callback($matches);
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

	/**
	 * @param $matches
	 * @return string
	 */
	protected function _doLists_callback($matches)
	{
		// Re-usable patterns to match list item bullets and number markers:
		$marker_ul_re  = '[*+-]';
		$marker_ol_re  = '\d+[\.]';

		$list = $matches[1];
		$list_type = preg_match("/$marker_ul_re/", $matches[4]) ? "ul" : "ol";

		$marker_any_re = ($list_type == "ul" ? $marker_ul_re : $marker_ol_re);

		$list .= "\n";
		$result = $this->processListItems($list, $marker_any_re);
		$result = $this->hashBlock("<$list_type class=\"bullet\">\n" . $result . "</$list_type>");
		return "\n". $result ."\n\n";
	}
}
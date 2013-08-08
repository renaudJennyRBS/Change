<?php
namespace Rbs\Admin;
use Change\Documents\Interfaces\Editable;
use Change\Presentation\RichText\ParserInterface;

/**
 * @name \Rbs\Admin\MarkdownParser
 */
class MarkdownParser extends \Change\Presentation\RichText\MarkdownParser implements ParserInterface
{
	/**
	 * @param string $rawText
	 * @param array $context
	 * @return string
	 */
	public function parse($rawText, $context)
	{
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

		$result = "<a document-href=\"" . $document->getDocumentModelName() . "," . $document->getId() . "\"";

		$link_text = $this->runSpanGamut($link_text);
		if (! $link_text && $document instanceof Editable)
		{
			/* @var $document Editable */
			$link_text = $document->getLabel();
		}
		$result .= ">$link_text</a>";

		return $this->hashPart($result);
	}

}
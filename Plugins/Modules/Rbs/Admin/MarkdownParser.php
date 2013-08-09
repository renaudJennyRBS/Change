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

		$rawText = preg_replace_callback('/\B(@\+?)([a-z0-9_\-]+)/i', function ($matches){
			if ($matches[1] === '@')
			{
				$model = 'Rbs_User_User';
			}
			else if ($matches[1] === '@+')
			{
				$model = 'Rbs_User_Group';
			}
			$dqb = new \Change\Documents\Query\Query($this->documentServices, $model);
			$pb = $dqb->getPredicateBuilder();
			$dqb->andPredicates($pb->eq($pb->columnProperty('identifier'), $matches[2]));
			$result = $dqb->getFirstDocument();

			if ($result)
			{
				return '['. $matches[1] . $matches[2] . '](' . $result->getId() . ',profile "' . $matches[1] . $matches[2] . '")';
			}
			return $matches[1] . $matches[2];
		}, $rawText);
		return $this->transform($rawText);
	}


	/**
	 * @param $matches
	 * @return string
	 */
	protected function _doAnchors_inline_callback($matches)
	{
		$link_text  = $this->runSpanGamut($matches[2]);
		$url = $matches[3] == '' ? $matches[4] : $matches[3];
		$params = array();
		if (!preg_match('/^(\d+)(,[a-z0-9\-_]+)?$/i', $url, $params))
		{
			return parent::_doAnchors_inline_callback($matches);
		}
		$id = $params[1];

		/* @var $document \Change\Documents\AbstractDocument */
		$document = $this->documentServices->getDocumentManager()->getDocumentInstance($id);

		if (!$document)
		{
			return $this->hashPart('<span class="label label-important">Invalid Document: ' . $url . '</span>');
		}

		$result = '<a href rbs-document-href="' . $document->getDocumentModelName() . ',' . $document->getId();
		if (count($params) === 3)
		{
			$result .= ',' . $params[2];
		}
		$result .= '"';

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
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
		//replace @xxx and @+xxx by a linked user or user group if exist.
		$rawText = preg_replace_callback('/\B(@\+?)([a-z0-9_\-]+)/i', function ($matches){
			//                                1111  222222222222
			$model = 'Rbs_User_User';
			if ($matches[1] === '@+')
			{
				$model = 'Rbs_User_Group';
			}
			$dqb = $this->applicationServices->getDocumentManager()->getNewQuery($model);
			$dqb->andPredicates($dqb->eq('login', $matches[2]));
			if ($model === 'Rbs_User_User')
			{
				$dqb->andPredicates($dqb->activated());
			}
			$result = $dqb->getFirstDocument();

			if ($result)
			{
				return '['. $matches[1] . $matches[2] . '](' . $result->getId() . ',public-profile "' . $matches[1] . $matches[2] . '")';
			}
			return $matches[1] . $matches[2];
		}, $rawText);

		//replace #xxx by a linked resource if exist.
		$rawText = preg_replace_callback('/\B(#)(\d+)\b/', function ($matches){
			//                                1  222
			$document = $this->applicationServices->getDocumentManager()->getDocumentInstance($matches[2]);
			if ($document)
			{
				return '['. $matches[1] . $matches[2] . '](' . $document->getId() . ' "' . $matches[1] . $matches[2] . '")';
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
		if (!preg_match('/^(\d+)(,([a-z]{2}_[A-Z]{2}))?(,([a-z0-9\-_]+))?$/i', $url, $params))
			//              111    33333333333333333      555555555555
		{
			return parent::_doAnchors_inline_callback($matches);
		}
		$id = $params[1];
		$lcid = $params[3];
		$route = $params[5];

		/* @var $document \Change\Documents\AbstractDocument */
		$document = $this->applicationServices->getDocumentManager()->getDocumentInstance($id);

		if (!$document)
		{
			return $this->hashPart('<span class="label label-important">Invalid Document: ' . $url . '</span>');
		}

		$result = '<a href rbs-document-popover rbs-document-href="' . $document->getDocumentModelName() . ',' . $document->getId();
		if ($lcid)
		{
			$result .= ',' . $lcid;
		}
		if ($route)
		{
			$result .= ',' . $route;
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
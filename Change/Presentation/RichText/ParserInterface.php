<?php
namespace Change\Presentation\RichText;


interface ParserInterface
{
	/**
	 * @param string $rawText
	 * @param array $context
	 * @return string
	 */
	public function parse($rawText, $context);
}
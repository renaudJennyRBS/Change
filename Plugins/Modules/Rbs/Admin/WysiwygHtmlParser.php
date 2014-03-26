<?php
namespace Rbs\Admin;
use Change\Presentation\RichText\ParserInterface;

/**
 * @name \Rbs\Admin\WysiwygHtmlParser
 */
class WysiwygHtmlParser implements ParserInterface
{

	/**
	 * @var \Rbs\Website\Documents\Website|null
	 */
	protected $website;

	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @param \Change\Services\ApplicationServices|null $applicationServices
	 */
	public function __construct($applicationServices)
	{
		$this->applicationServices = $applicationServices;
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
	 * @param string $rawText
	 * @param array $context
	 * @return string
	 */
	public function parse($rawText, $context)
	{
		// TODO Should we sanitize $rawText?
		$replacements = array('<ul>' => '<ul class="bullet">');
		$rawText = strtr($rawText, $replacements);
		return $rawText;
	}

}
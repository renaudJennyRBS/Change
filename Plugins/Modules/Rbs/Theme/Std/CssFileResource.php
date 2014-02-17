<?php
namespace Rbs\Theme\Std;

/**
 * @name \Rbs\Theme\Std\CssFileResource
 */
class CssFileResource extends \Change\Presentation\Themes\FileResource
{
	/**
	 * @var array
	 */
	private $variables;

	function __construct($filePath, $variables)
	{
		parent::__construct($filePath);
		$this->variables = $variables;
	}

	/**
	 * @return string
	 */
	public function getContent()
	{
		$content = parent::getContent();
		if ($content && count($this->variables))
		{
			return str_replace(array_keys($this->variables), array_values($this->variables), $content);
		}
		return $content;
	}
}
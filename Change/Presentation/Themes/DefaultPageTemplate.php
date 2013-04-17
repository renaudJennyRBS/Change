<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Layout\Layout;

class DefaultPageTemplate implements \Change\Presentation\Interfaces\PageTemplate
{
	/**
	 * @var \Change\Presentation\Interfaces\Theme
	 */
	protected $theme;

	/**
	 * @param \Change\Presentation\Interfaces\Theme $theme
	 */
	function __construct($theme)
	{
		$this->theme = $theme;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Theme
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * @return string
	 */
	public function getHtml()
	{
		return '<!DOCTYPE html>
<html>
<head>
	{% for headLine in pageResult.head %}
	{{ headLine|raw }}
	{% endfor %}
</head>
<body>
<!-- zoneEditable1 -->
</body>
</html>';
	}

	/**
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout()
	{
		$config = json_decode('{
	"zoneEditable1" : {
        "id"   : "zoneEditable1",
        "grid" : 12,
        "gridMode" : "fluid",
        "type" : "container",
        "parameters" : {}
    }
}', true);
		return new Layout($config);
	}
}
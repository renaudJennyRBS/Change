<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\PageTemplate;
use Change\Presentation\Layout\Layout;

class DefaultPageTemplate implements PageTemplate
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
	<link rel="icon" type="image/png" href="Theme/Change/Default/img/favicon.png" />
	<link rel="stylesheet" href="Theme/Change/Default/css/bootstrap.min.css" />
	<link rel="stylesheet" href="Theme/Change/Default/css/bootstrap-responsive.min.css" />
</head>
<body>
<!-- blockEditable1 -->
</body>
</html>';
	}

	/**
	 * @return \Change\Presentation\Layout\Layout
	 */
	public function getContentLayout()
	{
		$config = json_decode('{
	"blockEditable1" : {
        "id"   : "blockEditable1",
        "type":"block",
        "name":"Change_Website_Richtext",
        "parameters" : {
			"content":"Oups!"
        }
    }
}', true);
		return new Layout($config);
	}
}
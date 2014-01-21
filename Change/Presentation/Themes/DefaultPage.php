<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\Page;
use Change\Presentation\Interfaces\Template;
use Change\Presentation\Layout\Layout;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Presentation\Themes\DefaultPage
 */
class DefaultPage implements Page
{
	/**
	 * @var ThemeManager
	 */
	protected $themeManager;

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var \Change\Presentation\Interfaces\ThemeResource
	 */
	protected $layoutResource;

	/**
	 * @var \Change\Presentation\Interfaces\Section
	 */
	protected $section;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var integer
	 */
	protected $TTL = 0;

	/**
	 * @param ThemeManager $themeManager
	 * @param string $identifier
	 */
	function __construct(ThemeManager $themeManager, $identifier = 'default')
	{
		$this->themeManager = $themeManager;
		$this->identifier = $identifier;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	protected function getLayoutResource()
	{
		if ($this->layoutResource === null)
		{
			$this->layoutResource = $this->themeManager->getDefault()->getResource('Layout/Page/' . $this->getIdentifier()
			. '.json');
			if (!$this->layoutResource->isValid())
			{
				throw new \RuntimeException($this->getIdentifier() . '.json resource not found', 999999);
			}
		}
		return $this->layoutResource;
	}

	/**
	 * @return \Datetime
	 */
	public function getModificationDate()
	{
		return $this->getLayoutResource()->getModificationDate();
	}

	/**
	 * @api
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->themeManager->getDefault()->getPageTemplate('default');
	}

	/**
	 * @return Layout
	 */
	public function getContentLayout()
	{
		$config = $this->getLayoutResource()->getContent();
		return new Layout(json_decode($config, true));
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @return $this
	 */
	public function setSection($section)
	{
		$this->section = $section;
		return $this;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section
	 */
	public function getSection()
	{
		return $this->section;
	}

	/**
	 * @param integer $TTL
	 * @return $this
	 */
	public function setTTL($TTL)
	{
		$this->TTL = $TTL;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getTTL()
	{
		return $this->TTL;
	}
}
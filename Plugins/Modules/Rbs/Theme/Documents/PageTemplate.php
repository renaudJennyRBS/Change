<?php
namespace Rbs\Theme\Documents;

use Change\Presentation\Layout\Layout;

/**
 * @name \Rbs\Theme\Documents\PageTemplate
 */
class PageTemplate extends \Compilation\Rbs\Theme\Documents\PageTemplate implements \Change\Presentation\Interfaces\PageTemplate
{
	/**
	 * @return Layout
	 */
	public function getContentLayout()
	{
		return new Layout($this->getEditableContent());
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->getId();
	}

	/**
	 * @var \Assetic\AssetManager
	 */
	protected $cssAssetManager = null;

	/**
	 * @var \Assetic\AssetManager
	 */
	protected $jsAssetManager = null;

	/**
	 * @return \Assetic\AssetManager
	 */
	public function getCssAssetManager()
	{
		if ($this->cssAssetManager === null)
		{
			$this->cssAssetManager = new \Assetic\AssetManager();
		}
		return $this->cssAssetManager;
	}

	/**
	 * @return \Assetic\AssetManager
	 */
	public function getJsAssetManager()
	{
		if ($this->jsAssetManager === null)
		{
			$this->jsAssetManager = new \Assetic\AssetManager();
		}
		return $this->jsAssetManager;
	}
}
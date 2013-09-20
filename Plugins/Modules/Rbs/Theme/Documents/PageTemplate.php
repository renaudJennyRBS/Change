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
	 * @var \Assetic\Asset\AssetCollection|null
	 */
	protected $cssAssetCollection = null;

	/**
	 * @var \Assetic\Asset\AssetCollection|null
	 */
	protected $jsAssetCollection = null;

	/**
	 * @return \Assetic\Asset\AssetCollection
	 */
	public function getCssAssetCollection()
	{
		if ($this->cssAssetCollection === null)
		{
			$this->cssAssetCollection = new \Assetic\Asset\AssetCollection();
		}
		return $this->cssAssetCollection;
	}

	/**
	 * @return \Assetic\Asset\AssetCollection
	 */
	public function getJsAssetCollection()
	{
		if ($this->jsAssetCollection === null)
		{
			$this->jsAssetCollection = new \Assetic\Asset\AssetCollection();
		}
		return $this->jsAssetCollection;
	}
}
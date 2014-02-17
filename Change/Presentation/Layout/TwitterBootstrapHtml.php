<?php
namespace Change\Presentation\Layout;

/**
 * @name \Change\Presentation\Layout\TwitterBootstrapHtml
 */
class TwitterBootstrapHtml
{
	/**
	 * @var string
	 */
	protected $prefixKey = '<!-- ';

	/**
	 * @var string
	 */
	protected $suffixKey = ' -->';

	/**
	 * @var bool
	 */
	protected $fluid = true;

	/**
	 * @param string $prefixKey
	 * @return $this
	 */
	public function setPrefixKey($prefixKey)
	{
		$this->prefixKey = $prefixKey;
		return $this;
	}

	/**
	 * @param string $suffixKey
	 * @return $this
	 */
	public function setSuffixKey($suffixKey)
	{
		$this->suffixKey = $suffixKey;
		return $this;
	}

	/**
	 * @param Block $item
	 * @return null
	 */
	public function getBlockClass(\Change\Presentation\Layout\Block $item)
	{
		$vi = $item->getVisibility();
		if ($vi == 'raw')
		{
			return 'raw';
		}
		elseif ($vi)
		{
			$classes = array();
			$vi = $item->getVisibility();
			for($i = 0; $i < strlen($vi); $i++)
			{
				switch ($vi[$i])
				{
					case 'X' : $classes[] = 'visible-xs'; break;
					case 'S' : $classes[] = 'visible-sm'; break;
					case 'M' : $classes[] = 'visible-md'; break;
					case 'L' : $classes[] = 'visible-lg'; break;
				}
			}
			if (count($classes))
			{
				return implode(' ', $classes);
			}
		}
		return null;
	}

	/**
	 * @param \Change\Presentation\Layout\Layout $templateLayout
	 * @param \Change\Presentation\Layout\Layout $pageLayout
	 * @param Callable $callableBlockHtml
	 * @return array
	 */
	public function getHtmlParts($templateLayout, $pageLayout, $callableBlockHtml)
	{
		$prefixKey = $this->prefixKey;
		$suffixKey = $this->suffixKey;

		$twigLayout = array();
		foreach ($templateLayout->getItems() as $item)
		{
			$twigPart = null;
			if ($item instanceof Block)
			{
				$twigPart = $callableBlockHtml($item);
			}
			elseif ($item instanceof Container)
			{
				$container = $pageLayout->getById($item->getId());
				if ($container instanceof Container)
				{
					$twigPart = $this->getItemHtml($container, $callableBlockHtml);
				}
			}

			if ($twigPart)
			{
				$twigLayout[$prefixKey . $item->getId() . $suffixKey] = $twigPart;
			}
		}
		return $twigLayout;
	}

	/**
	 * @param \Change\Presentation\Layout\Layout $templateLayout
	 * @param \Change\Presentation\Layout\Layout $pageLayout
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param boolean $developmentMode
	 * @return array
	 */
	public function getResourceParts($templateLayout, $pageLayout, $themeManager, $applicationServices, $developmentMode)
	{
		$blockNames = array();
		foreach($templateLayout->getBlocks() as $block)
		{
			$blockNames[$block->getName()] = true;
		}
		foreach($pageLayout->getBlocks() as $block)
		{
			$blockNames[$block->getName()] = true;
		}

		$configuration = $themeManager->getDefault()->getAssetConfiguration();
		$configuration = $themeManager->getCurrent()->getAssetConfiguration($configuration);
		$am = $themeManager->getAsseticManager($configuration);

		$jsNames = $themeManager->getJsAssetNames($configuration, $blockNames);
		$jsFooter = array();
		$assetBaseUrl = ($developmentMode) ? '' : $themeManager->getAssetBaseUrl();
		foreach($jsNames as $jsName)
		{
			try
			{
				$a = $am->get($jsName);
				$jsFooter[] = '<script type="text/javascript" src="' .$assetBaseUrl . $a->getTargetPath() . '"></script>';
			}
			catch (\Exception $e)
			{
				$applicationServices->getLogging()->warn('asset resource name not found: ' . $jsName);
			}
		}

		$cssNames = $themeManager->getCssAssetNames($configuration, $blockNames);
		$cssHead = [];
		foreach($cssNames as $cssName)
		{
			try
			{
				$a = $am->get($cssName);
				$cssHead[] = '<link rel="stylesheet" type="text/css" href="' . $assetBaseUrl . $a->getTargetPath() . '">';
			}
			catch (\Exception $e)
			{
				$applicationServices->getLogging()->warn('asset resource name not found: ' . $cssName);
			}
		}

		return array('<!-- cssHead -->' => implode(PHP_EOL, $cssHead), '<!-- jsFooter -->' => implode(PHP_EOL, $jsFooter));
	}

	/**
	 * @param Item $item
	 * @param Callable $callableTwigBlock
	 * @return string|null
	 */
	protected function getItemHtml($item, $callableTwigBlock)
	{
		if ($item instanceof Block)
		{
			return $callableTwigBlock($item);
		}

		$innerHTML = '';
		foreach ($item->getItems() as $childItem)
		{
			$innerHTML .= $this->getItemHtml($childItem, $callableTwigBlock);
		}
		if ($item instanceof Cell)
		{
			return '<div data-id="' . $item->getId() . '" class="col-lg-' . $item->getSize() . '">' . $innerHTML . '</div>';
		}
		elseif ($item instanceof Row)
		{
			$class = 'row';
			return
				'<div class="' . $class . '" data-id="' . $item->getId() . '" data-grid="' . $item->getGrid() . '">' . $innerHTML
				. '</div>';
		}
		elseif ($item instanceof Container)
		{
			//return '<div data-id="' . $item->getId() . '" data-grid="' . $item->getGrid() . '">' . $innerHTML . '</div>';
			return $innerHTML;
		}
		return (!empty($innerHTML)) ? $innerHTML : null;
	}
}
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
				$this->fluid = $item->getGridMode() == 'fluid';
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
	 * @param Item $item
	 * @param Container $container
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
			return '<div data-id="' . $item->getId() . '" class="span' . $item->getSize() . '">' . $innerHTML . '</div>';
		}
		elseif ($item instanceof Row)
		{
			$class = $this->fluid ? 'row-fluid' : 'row';
			return
				'<div class="' . $class . '" data-id="' . $item->getId() . '" data-grid="' . $item->getGrid() . '">' . $innerHTML
				. '</div>';
		}
		elseif ($item instanceof Container)
		{
			$class = $this->fluid ? 'container-fluid' : 'container';
			return
				'<div class="' . $class . '" data-id="' . $item->getId() . '" data-grid="' . $item->getGrid() . '">' . $innerHTML
				. '</div>';
		}
		return (!empty($innerHTML)) ? $innerHTML : null;
	}
}
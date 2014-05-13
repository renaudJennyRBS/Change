<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Themes;

use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Util\LessUtils;

/**
 * @name \Change\Presentation\Themes\AsseticLessFilter
 */
class AsseticLessFilter implements \Assetic\Filter\DependencyExtractorInterface
{
	private $presets = array();
	private $formatter;
	private $preserveComments;

	/**
	 * Lessphp Load Paths
	 * @var array
	 */
	protected $loadPaths = array();

	/**
	 * Adds a load path to the paths used by lessphp
	 * @param string $path Load Path
	 */
	public function addLoadPath($path)
	{
		$this->loadPaths[] = $path;
	}

	/**
	 * Sets load paths used by lessphp
	 * @param array $loadPaths Load paths
	 */
	public function setLoadPaths(array $loadPaths)
	{
		$this->loadPaths = $loadPaths;
	}

	public function setPresets(array $presets)
	{
		$this->presets = $presets;
	}

	/**
	 * @param string $formatter One of "lessjs", "compressed", or "classic".
	 */
	public function setFormatter($formatter)
	{
		$this->formatter = $formatter;
	}

	/**
	 * @param boolean $preserveComments
	 */
	public function setPreserveComments($preserveComments)
	{
		$this->preserveComments = $preserveComments;
	}

	public function filterLoad(AssetInterface $asset)
	{
		$root = $asset->getSourceRoot();
		$path = $asset->getSourcePath();

		$options = [];
		switch ($this->formatter)
		{
			case 'compressed':
				$options['compress'] = true;
				break;
		}

		$parser = new \Less_Parser($options);
		if ($root && $path)
		{
			$this->loadPaths[] = dirname($root . '/' . $path);
		}
		$parser->SetImportDirs($this->loadPaths);
		$parser->parse($asset->getContent());
		$asset->setContent($parser->getCss());
	}

	public function filterDump(AssetInterface $asset)
	{
	}

	public function getChildren(AssetFactory $factory, $content, $loadPath = null)
	{
		$loadPaths = $this->loadPaths;
		if (null !== $loadPath)
		{
			$loadPaths[] = $loadPath;
		}

		if (empty($loadPaths))
		{
			return array();
		}

		$children = array();
		foreach (LessUtils::extractImports($content) as $reference)
		{
			if ('.css' === substr($reference, -4))
			{
				// skip normal css imports
				// todo: skip imports with media queries
				continue;
			}

			if ('.less' !== substr($reference, -5))
			{
				$reference .= '.less';
			}

			foreach ($loadPaths as $loadPath)
			{
				if (file_exists($file = $loadPath . '/' . $reference))
				{
					$coll = $factory->createAsset($file, array(), array('root' => $loadPath));
					foreach ($coll as $leaf)
					{
						$leaf->ensureFilter($this);
						$children[] = $leaf;
						goto next_reference;
					}
				}
			}

			next_reference:
		}
		return $children;
	}
}

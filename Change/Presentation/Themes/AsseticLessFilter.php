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
	/**
	 * @var string
	 */
	private $formatter;

	/**
	 * @var string
	 */
	protected $cacheDir;

	function __construct($cacheDir = null)
	{
		if ($cacheDir && is_dir($cacheDir))
		{
			$this->cacheDir = $cacheDir;
		}
	}

	/**
	 * @param string $formatter One of "lessjs", "compressed", or "classic".
	 */
	public function setFormatter($formatter)
	{
		$this->formatter = $formatter;
	}

	public function filterLoad(AssetInterface $asset)
	{
		$root = $asset->getSourceRoot();
		$path = $asset->getSourcePath();
		if (!$root || !$path)
		{
			return;
		}
		$lessFilePath = $root . '/' . $path;
		$options = [];
		switch ($this->formatter)
		{
			case 'compressed':
				$options['compress'] = true;
				break;
		}

		if ($this->cacheDir)
		{
			\Less_Cache::$cache_dir = $this->cacheDir;
			$to_cache = [$lessFilePath => ''];
			$css_file_name = \Less_Cache::Get($to_cache, $options);
			$css = file_get_contents($this->cacheDir . DIRECTORY_SEPARATOR . $css_file_name);
			$asset->setContent($css);
		}
		else
		{
			$parser = new \Less_Parser($options);
			$parser->SetImportDirs([dirname($lessFilePath) => '']);
			$parser->parse($asset->getContent());
			$asset->setContent($parser->getCss());
		}
	}

	public function filterDump(AssetInterface $asset)
	{
	}

	public function getChildren(AssetFactory $factory, $content, $loadPath = null)
	{
		$loadPaths = [];
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

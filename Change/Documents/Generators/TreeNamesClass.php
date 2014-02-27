<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\TreeNamesClass
 */
class TreeNamesClass
{
	/**
	 * @var \Change\Documents\Generators\Compiler
	 */
	protected $compiler;

	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model[] $models
	 * @param string $compilationPath
	 * @return boolean
	 */
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, $models, $compilationPath)
	{
		$code = $this->getPHPCode($compiler, $models);
		$nsParts = array('Change', 'Documents','TreeNames.php');
		array_unshift($nsParts, $compilationPath);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, $nsParts), $code);
		return true;
	}

	/**
	 * @param string[] $names
	 * @return string
	 */
	protected function escapeTreeNames($names)
	{
		return 'array(\'' . implode('\', \'', $names) . '\')';
	}

	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model[] $models
	 * @return string
	 */
	public function getPHPCode(\Change\Documents\Generators\Compiler $compiler, $models)
	{
		$treeNames = array();
		foreach ($models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if (is_string($model->getTreeName()))
			{
				$treeNames[] = $model->getTreeName();
			}
		}
		$treeNames = array_unique($treeNames);


		$this->compiler = $compiler;
		$code = '<'. '?php
namespace Compilation\Change\Documents;

/**
 * @name \Compilation\Change\Documents\TreeNames
 */
class TreeNames extends \ArrayObject
{
	public function __construct()
	{
		parent::__construct('. $this->escapeTreeNames($treeNames) .');
	}
}';
		$this->compiler = null;
		return $code;
	}
}
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
 * @name \Change\Documents\Generators\ModelsNamesClass
 */
class ModelsNamesClass
{
	/**
	 * @var Compiler
	 */
	protected $compiler;

	/**
	 * @param Compiler $compiler
	 * @param \Change\Documents\Generators\Model[] $models
	 * @param string $compilationPath
	 * @return boolean
	 */
	public function savePHPCode(Compiler $compiler, $models, $compilationPath)
	{
		$code = $this->getPHPCode($compiler, $models);
		$nsParts = ['Change', 'Documents', 'ModelsInfos.php'];
		array_unshift($nsParts, $compilationPath);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, $nsParts), $code);
		return true;
	}

	/**
	 * @param array $modelsInfos
	 * @return string
	 */
	protected function escapeModelsNames($modelsInfos)
	{
		return str_replace([' ', PHP_EOL, 'array(', ')'], ['', '', '[', ']'], var_export($modelsInfos, true));
	}

	/**
	 * @param Compiler $compiler
	 * @param \Change\Documents\Generators\Model[] $models
	 * @return string
	 */
	public function getPHPCode(Compiler $compiler, $models)
	{
		$modelsInfos = [];
		foreach ($models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if (!$model->getReplace())
			{
				$isLeaf = true;
				foreach ($models as $otherModel)
				{
					if ($otherModel->getParent() && $otherModel->getParent()->getName() == $model->getName())
					{
						$isLeaf = false;
						break;
					}
				}

				$modelsInfos[$model->getName()] = [
					'pu' => $model->getPublishable() == true || $model->checkAncestorPublishable(),
					'ac' => $model->getActivable() == true || $model->checkAncestorActivable(),
					'lo' => $model->getLocalized() == true || $model->rootLocalized(),
					'ed' => $model->getEditable() == true,
					'ab' => $model->getAbstract() == true,
					'in' => $model->getInline() == true,
					'st' => $model->getStateless() == true || $model->rootStateless(),
					'co' => $model->checkHasCorrection(),
					'ro' => $model->getParent() === null,
					'le' => $isLeaf,
					'pa' => is_string($model->getExtends()) ? $model->getExtends() : null
				];
			}
		}

		$this->compiler = $compiler;
		$code = '<'. '?php
namespace Compilation\Change\Documents;

/**
 * @name \Compilation\Change\Documents\ModelsInfos
 */
class ModelsInfos
{
	protected $modelsInfos = '. $this->escapeModelsNames($modelsInfos) . ';

	/**
	 * @return string[]
	 */
	public function getNames()
	{
		return array_keys($this->modelsInfos);
	}

	/**
	 * @return array
	 */
	public function getInfos()
	{
		return $this->modelsInfos;
	}
}';
		$this->compiler = null;
		return $code;
	}


}
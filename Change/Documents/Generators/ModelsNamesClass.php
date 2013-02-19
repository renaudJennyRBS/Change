<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\ModelsNamesClass
 */
class ModelsNamesClass
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
		$nsParts = array('Change', 'Documents','ModelsNames.php');
		array_unshift($nsParts, $compilationPath);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, $nsParts), $code);
		return true;
	}

	/**
	 * @param string[] $names
	 * @return string
	 */
	protected function escapeModelsNames($names)
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
		$rm = array();
		foreach ($models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			if (!$model->getInject())
			{
				$rm[] = $model->getName();
			}
		}

		$this->compiler = $compiler;
		$code = '<'. '?php
namespace Compilation\Change\Documents;

/**
 * @name \Compilation\Change\Documents\ModelsNames
 */
class ModelsNames extends \ArrayObject
{
	public function __construct()
	{
		parent::__construct('. $this->escapeModelsNames($rm) .');
	}
}';
		$this->compiler = null;
		return $code;
	}
}
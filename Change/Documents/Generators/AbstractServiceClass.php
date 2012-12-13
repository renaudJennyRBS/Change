<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\AbstractServiceClass
 */
class AbstractServiceClass
{
	/**
	 * @var \Change\Documents\Generators\Compiler
	 */
	protected $compiler;
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @param string $compilationPath
	 * @return boolean
	 */
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model, $compilationPath)
	{
		$code = $this->getPHPCode($compiler, $model);
		$nsParts = explode('\\', $model->getNameSpace());
		$nsParts[] = $model->getShortAbstractServiceClassName() . '.php';
		array_unshift($nsParts, $compilationPath);
		\Change\Stdlib\File::write(implode(DIRECTORY_SEPARATOR, $nsParts), $code);
		return true;
	}
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	public function getPHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model)
	{
		$this->compiler = $compiler;
		$code = '<'. '?php' . PHP_EOL . 'namespace ' . $model->getCompilationNameSpace() . ';' . PHP_EOL;
		
		$extendModel = '';
		if ($model->getExtend())
		{
			$pm = $this->compiler->getModelByName($model->getExtend());
			$extend =  $model->getServiceClassName();
		}
		else
		{
			$extend = '\Change\Documents\AbstractService';
		}
		
		$code .= 'abstract class ' . $model->getShortAbstractServiceClassName() . ' extends ' . $extend . PHP_EOL;
		$code .= '{'. PHP_EOL;
		$code .= $this->getDefaultCode($model);
		$code .= '}'. PHP_EOL;
		$this->compiler = null;
		return $code;	
	}
	
	/**
	 * @param mixed $value
	 * @return string
	 */
	protected function escapePHPValue($value, $removeSpace = true)
	{
		if ($removeSpace)
		{
			return str_replace(array(PHP_EOL, ' ', "\t"), '', var_export($value, true));
		}
		return var_export($value, true);
	}
	

	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getDefaultCode($model)
	{
		$docClassName = $model->getDocumentClassName();
		
		$code = '
	/**
	 * @param '.$docClassName.' $document
	 * @return string
	 */
	public function save('.$docClassName.' $document)
	{
		throw new \LogicException(\'not implemented\');
	}'. PHP_EOL;
		
		if (!$model->getInject())
		{
		$code .= '
	/**
	 * @return string
	 */
	public function getModelName()
	{
		return '.$this->escapePHPValue($model->getName()).';
	}'. PHP_EOL;
		}
		return $code;
	}
}
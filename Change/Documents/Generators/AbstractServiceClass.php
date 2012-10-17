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
	 * @return boolean
	 */
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, \Change\Documents\Generators\Model $model)
	{
		$code = $this->getPHPCode($compiler, $model);
		$nsParts = explode('\\', $model->getNameSpace());
		$nsParts[] = $this->getClassName($model) . '.php';
		$path  = \Change\Stdlib\Path::compilationPath(implode(DIRECTORY_SEPARATOR, $nsParts));
		\Change\Stdlib\File::write($path, $code);
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
		$code = '<'. '?php' . PHP_EOL . 'namespace Compilation\\' . $model->getNameSpace() . ';' . PHP_EOL;
		$code .= 'abstract class ' . $this->getClassName($model) . ' extends ' . $this->getExtendClassName($model) . PHP_EOL;
		$code .= '{'. PHP_EOL;
		$code .= $this->getDefaultCode($model);
		$code .= '}'. PHP_EOL;		
		$this->compiler = null;
		return $code;	
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param string $className
	 * @return string
	 */
	protected function addNameSpace($model, $className)
	{
		return '\Compilation\\' . $model->getNameSpace() . '\\' . $className;
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
	 * @param boolean $withNameSpace
	 * @return string
	 */
	protected function getClassName($model, $withNameSpace = false)
	{
		$cn = 'Abstract' .ucfirst($model->getDocumentName()) . 'Service';
		return ($withNameSpace) ? $this->addNameSpace($model, $cn) :  $cn;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getExtendClassName($model)
	{
		if ($model->getExtend())
		{
			$pm = $this->compiler->getModelByFullName($model->getExtend());
			return $this->getFinalClassName($pm);
		}
		return '\Change\Documents\AbstractService';
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getFinalClassName($model)
	{
		return $this->getFinalClassNameByCode($model->getVendor(), $model->getModuleName(), $model->getDocumentName());
	}

	/**
	 * @param string $moduleName
	 * @param string $documentName
	 * @return string
	 */
	protected function getFinalClassNameByCode($vendor, $moduleName, $documentName)
	{
		return '\\'. ucfirst($vendor).'\\' .  ucfirst($moduleName) . '\Documents\\' . ucfirst($documentName) . 'Service';
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @param boolean $withNameSpace
	 * @return string
	 */
	protected function getModelClassName($model, $withNameSpace = false)
	{
		$cn = ucfirst($model->getDocumentName()) . 'Model';
		return ($withNameSpace) ? $this->addNameSpace($model, $cn) :  $cn;
	}
	
	/**
	 * @param \Change\Documents\Generators\Model $model
	 * @return string
	 */
	protected function getDefaultCode($model)
	{
		$code = '
	/**
	 * @return string
	 */			
	public function getModelName()
	{
		return '.$this->escapePHPValue($model->getFullName()).';	
	}'. PHP_EOL;
		return $code;
	}
}
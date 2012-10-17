<?php
namespace Change\Documents\Generators;

/**
 * @name \Change\Documents\Generators\AbstractDocumentServicesClass
 */
class AbstractDocumentServicesClass
{
	/**
	 * @var \Change\Documents\Generators\Compiler
	 */
	protected $compiler;
	
	/**
	 * @param \Change\Documents\Generators\Compiler $compiler
	 * @param \Change\Documents\Generators\Model[] $models
	 * @return boolean
	 */
	public function savePHPCode(\Change\Documents\Generators\Compiler $compiler, $models)
	{
		$code = $this->getPHPCode($compiler, $models);
		$nsParts = array('Change', 'Documents','AbstractDocumentServices.php');
		$path  = \Change\Stdlib\Path::compilationPath(implode(DIRECTORY_SEPARATOR, $nsParts));
		\Change\Stdlib\File::write($path, $code);
		return true;
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
				$rm[] = $model;
			}
		}
		
		$this->compiler = $compiler;
		$code = '<'. '?php' . PHP_EOL . 'namespace Compilation\Change\Documents;' . PHP_EOL;
		$code .= 'abstract class AbstractDocumentServices extends \Zend\Di\Di' . PHP_EOL;
		$code .= '{'. PHP_EOL;
		
		$code .= $this->getDefaultCode($rm);
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
		return  ucfirst($vendor).'\\' .  ucfirst($moduleName) . '\Documents\\' . ucfirst($documentName) . 'Service';
	}
	
	/**
	 * @param \Change\Documents\Generators\Model[] $models
	 * @return string
	 */
	protected function getDefaultCode($models)
	{
		$cl = array();
		$code = '
	/**
	 * @param \Zend\Di\DefinitionList $dl
	 * @param \Change\Application\ApplicationServices $application
	 */
	public function __construct(\Zend\Di\DefinitionList $dl, \Change\Application\ApplicationServices $applicationServices)
	{'. PHP_EOL;
		
		foreach ($models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$cn = $this->escapePHPValue($this->getFinalClassName($model));
			$code .= '
		$cl = new \Zend\Di\Definition\ClassDefinition('.$cn.');
			$cl->setInstantiator(\'__construct\')->addMethod(\'__construct\', true)
				->addMethodParameter(\'__construct\', \'applicationServices\', array(\'type\' => \'Change\\Application\\ApplicationServices\', \'required\' => true))
				->addMethodParameter(\'__construct\', \'documentServices\', array(\'type\' => \'Change\\Documents\\DocumentServices\', \'required\' => true));
		$dl->addDefinition($cl);';
		}	
		$code .= '
			
		parent::__construct($dl);
		$params = array(\'applicationServices\' => $applicationServices, \'documentServices\' => $this);

		$im = $this->instanceManager();'. PHP_EOL;
		foreach ($models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$cn = $this->escapePHPValue($this->getFinalClassName($model));
			$an = $this->escapePHPValue($model->getFullName());
			$code .= '		$im->addAlias('.$an.', '.$cn.', $params);'. PHP_EOL;
		}	
		$code .= '
	}'. PHP_EOL;
		
		foreach ($models as $model)
		{
			/* @var $model \Change\Documents\Generators\Model */
			$cn = '\\' . $this->getFinalClassName($model);
			$an = $this->escapePHPValue($model->getFullName());
			if ($model->getVendor() === 'Change')
			{
				$fn = 'get' . ucfirst($model->getModuleName()) . ucfirst($model->getDocumentName());
			}
			else
			{
				$fn = 'get' . ucfirst($model->getVendor()) . ucfirst($model->getModuleName()) . ucfirst($model->getDocumentName());
			}
			$code .= '
	/**
	 * @return '.$cn.'
	 */
	public function '.$fn.'()
	{
		return $this->get('.$an.');
	}'. PHP_EOL;
		}		
		
		return $code;
	}
}
<?php
namespace Rbs\Elasticsearch\Std;

/**
 * @name \Rbs\Elasticsearch\Std\FacetDefinitionInterface
 */
interface FacetDefinitionInterface
{
	const TYPE_STRING = 'string';
	const TYPE_INTEGER = 'integer';
	const TYPE_FLOAT = 'float';
	const TYPE_BOOLEAN = 'boolean';
	const TYPE_OBJECT = 'object';

	/**
	 * @return string
	 */
	public function getFieldName();

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return string
	 */
	public function getFieldType();

	/**
	 * @return boolean
	 */
	public function isFieldArray();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters();
}
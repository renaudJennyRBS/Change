<?php
namespace Rbs\Elasticsearch\Facet;

/**
 * @name \Rbs\Elasticsearch\Facet\FacetDefinitionInterface
 */
interface FacetDefinitionInterface
{
	const TYPE_TERM = 'term';
	const TYPE_RANGE = 'range';

	const PARAM_MULTIPLE_CHOICE = 'multipleChoice';
	const PARAM_COLLECTION_CODE = 'collectionCode';

	/**
	 * @return integer
	 */
	public function getId();

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
	public function getFacetType();

	/**
	 * @return boolean
	 */
	public function getShowEmptyItem();

	/**
	 * @return \Zend\Stdlib\Parameters
	 */
	public function getParameters();
}
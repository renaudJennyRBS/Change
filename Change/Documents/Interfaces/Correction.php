<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Correction
 */
interface Correction
{
	/**
	 * @api
	 * @return integer
	 */
	public function getId();

	/**
	 * @api
	 * @return \Change\Documents\AbstractModel
	 */
	public function getDocumentModel();

	/**
	 * @api
	 * @return \Change\Documents\DocumentServices
	 */
	public function getDocumentServices();

	/**
	 * @return boolean
	 */
	public function useCorrection();

	/**
	 * @return boolean
	 */
	public function hasCorrection();

	/**
	 * @return \Change\Documents\Correction|null
	 */
	public function getCurrentCorrection();

	/**
	 * @return boolean
	 * @throws \InvalidArgumentException
	 */
	public function mergeCurrentCorrection();
}
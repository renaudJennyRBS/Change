<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Correction
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 */
interface Correction
{
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


	public function updateMergedDocument();
}
<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Correction
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
	 * @throws \RuntimeException
	 * @return boolean
	 */
	public function saveCorrections();

	/**
	 * @param \Change\Documents\Correction $correction
	 * @throws \RuntimeException
	 */
	public function saveCorrection(\Change\Documents\Correction $correction);

	/**
	 * @return boolean
	 * @throws \InvalidArgumentException
	 */
	public function publishCorrection();

	/**
	 * @param \DateTime $publicationDate
	 * @throws \RuntimeException
	 * @return \Change\Documents\Correction|null
	 */
	public function startCorrectionValidation(\DateTime $publicationDate = null);


	/**
	 * @throws \RuntimeException
	 * @throws \Exception
	 * @return \Change\Documents\Correction|null
	 */
	public function startCorrectionPublication();

}
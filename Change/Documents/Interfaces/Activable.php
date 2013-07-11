<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Activable
 */
interface Activable
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
	 * @api
	 * @return boolean
	 */
	public function getActive();

	/**
	 * @api
	 * @return \DateTime|null
	 */
	public function getStartActivation();
	
	/**
	 * @api
	 * @return \DateTime|null
	 */
	public function getEndActivation();

	/**
	 * @param \DateTime $at
	 * @return boolean
	 */
	public function activated(\DateTime $at = null);
}
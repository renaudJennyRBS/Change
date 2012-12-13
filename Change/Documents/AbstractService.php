<?php
namespace Change\Documents;

/**
 * @name \Change\Documents\AbstractService
 */
abstract class AbstractService
{
	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;
	
	/**
	 * \Change\Documents\DocumentServices
	 */
	protected $documentServices;
	
	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function __construct(\Change\Application\ApplicationServices $applicationServices, \Change\Documents\DocumentServices $documentServices)
	{
		$this->applicationServices = $applicationServices;
		$this->documentServices = $documentServices;
	}	
}
<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Publishable
 */
interface Publishable
{
	const STATUS_DRAFT = 'DRAFT';
	
	const STATUS_VALIDATION = 'VALIDATION';
	
	const STATUS_PUBLISHABLE = 'PUBLISHABLE';
	
	const STATUS_UNPUBLISHABLE = 'UNPUBLISHABLE';
	
	const STATUS_DEACTIVATED = 'DEACTIVATED';
	
	const STATUS_FILED = 'FILED';
	
	/**
	 * @return string
	 */
	public function getPublicationStatus();
	
	/**
	 * @param string $publicationStatus
	 */
	public function setPublicationStatus($publicationStatus);
	
	/**
	 * @return string|null
	 */
	public function getStartPublication();
		
	/**
	 * @param string|null $startPublication
	 */
	public function setStartPublication($startPublication);
	
	/**
	 * @return string|null
	 */
	public function getEndPublication();
	
	/**
	 * @param string|null $endPublication
	 */
	public function setEndPublication($endPublication);	
}
<?php
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Editable
 */
interface Editable
{	
	/**
	 * @return string
	 */
	public function getLabel();
	
		/**
	 * @param string $label
	 */
	public function setLabel($label);

	/**
	 * @return string
	 */
	public function getAuthorName();
	
	
	/**
	 * @param string $authorName
	 */
	public function setAuthorName($authorName);
	
	/**
	 * @return integer
	 */
	public function getAuthorId();
	
	/**
	 * @param integer $authorId
	 */
	public function setAuthorId($authorId);
	
	/**
	 * @return integer
	 */
	public function getDocumentVersion();

	/**
	 * @return integer
	 */
	public function getDocumentVersionOldValue();
	
	/**
	 * @param integer $documentVersion
	 */
	public function setDocumentVersion($documentVersion);

	/**
	 * @return integer
	 */
	public function nextDocumentVersion();
}
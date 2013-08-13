<?php
namespace Change\Http\Rest;

/**
 * @name \Change\Http\Rest\RestfulDocumentInterface
 */
interface RestfulDocumentInterface
{
	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $documentResult
	 */
	public function populateRestDocumentResult($documentResult);

	/**
	 * @param \Change\Http\Rest\Result\DocumentLink $documentLink
	 * @param Array
	 */
	public function populateRestDocumentLink($documentLink, $extraColumn);
}
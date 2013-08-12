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
	public function updateRestDocumentResult($documentResult);

	/**
	 * @param \Change\Http\Rest\Result\DocumentLink $documentLink
	 * @param Array
	 */
	public function updateRestDocumentLink($documentLink, $extraColumn);
}
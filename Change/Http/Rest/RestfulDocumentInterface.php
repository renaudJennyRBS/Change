<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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

	/**
	 * @param \Change\Http\Event  $event
	 * @return $this|false on error
	 */
	public function populateDocumentFromRestEvent(\Change\Http\Event $event);
}
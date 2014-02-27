<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Publishable
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 */
interface Publishable
{
	const STATUS_DRAFT = 'DRAFT';
	
	const STATUS_VALIDATION = 'VALIDATION';

	const STATUS_VALIDCONTENT = 'VALIDCONTENT';

	const STATUS_VALID = 'VALID';
	
	const STATUS_PUBLISHABLE = 'PUBLISHABLE';
	
	const STATUS_UNPUBLISHABLE = 'UNPUBLISHABLE';
	
	const STATUS_FROZEN = 'FROZEN';
	
	const STATUS_FILED = 'FILED';

	/**
	 * Return valid PublicationStatus for correction system
	 * @api
	 * @return string[]
	 */
	public function getValidPublicationStatusForCorrection();

	/**
	 * @api
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections();

	/**
	 * @api
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @return \Change\Presentation\Interfaces\Section
	 */
	public function getCanonicalSection(\Change\Presentation\Interfaces\Website $website = null);

	/**
	 * @param \DateTime $at
	 * @return boolean
	 */
	public function published(\DateTime $at = null);

	/**
	 * Return true if is publishable or a string for reason if is unpublishable
	 * @return string|boolean
	 */
	public function isPublishable();


	/**
	 * @param string $newPublicationStatus
	 */
	public function updatePublicationStatus($newPublicationStatus);
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator;

use Change\Application;
use Change\Services\ApplicationServices;

/**
 * @name \Rbs\Storelocator\StorelocatorServices
 */
class StorelocatorServices extends \Zend\Di\Di
{
	use \Change\Services\ServicesCapableTrait;

	/**
	 * @return array<alias => className>
	 */
	protected function loadInjectionClasses()
	{
		$classes = $this->getApplication()->getConfiguration('Rbs/Storelocator/Services');
		return is_array($classes) ? $classes : array();
	}

	/**
	 * @param Application $application
	 * @param ApplicationServices $applicationServices
	 */
	public function __construct(Application $application, ApplicationServices $applicationServices)
	{
		$this->setApplication($application);

		$definitionList = new \Zend\Di\DefinitionList(array());

		//StoreManager : Application, DocumentManager,
		$storeManagerClassName = $this->getInjectedClassName('StoreManager', 'Rbs\Storelocator\StoreManager');
		$classDefinition = $this->getClassDefinition($storeManagerClassName);
		$this->addApplicationClassDefinition($classDefinition);
		$classDefinition
			->addMethod('setDocumentManager', true)
				->addMethodParameter('setDocumentManager', 'documentManager', ['required' => true]);
		$definitionList->addDefinition($classDefinition);

		parent::__construct($definitionList);
		$im = $this->instanceManager();

		$documentManager = function() use ($applicationServices) {return $applicationServices->getDocumentManager();};

		$im->addAlias('StoreManager', $storeManagerClassName,
			['application' => $application, 'documentManager' => $documentManager]);
	}

	/**
	 * @api
	 * @return \Rbs\Storelocator\StoreManager
	 */
	public function getStoreManager()
	{
		return $this->get('StoreManager');
	}
}
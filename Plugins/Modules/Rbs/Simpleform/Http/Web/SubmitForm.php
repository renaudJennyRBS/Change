<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Simpleform\Http\Web\SubmitForm
 */
class SubmitForm extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$i18nManager = $applicationServices->getI18nManager();
		$documentManager = $applicationServices->getDocumentManager();

		/* @var $genericServices \Rbs\Generic\GenericServices */
		$genericServices = $event->getServices('genericServices');
		$fieldManager = $genericServices->getFieldManager();
		$securityManager = $genericServices->getSecurityManager();

		$request = $event->getRequest();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		if (!isset($arguments['formId']))
		{
			throw new \RuntimeException('No form id', 999999);
		}

		$form = $documentManager->getDocumentInstance($arguments['formId']);
		if (!($form instanceof \Rbs\Simpleform\Documents\Form))
		{
			throw new \RuntimeException('Bad form id ' . $arguments['formId'], 999999);
		}

		$dataKey = $form->getName();
		if (!isset($arguments[$dataKey]))
		{
			throw new \RuntimeException('No data for form id ' . $arguments['formId'], 999999);
		}

		$data = $arguments[$dataKey];
		$files = $request->getFiles($dataKey);
		$result = $this->getNewAjaxResult();
		$event->setResult($result);

		if (!$securityManager->checkCSRFToken($arguments['CSRFToken']))
		{
			// Return an error.
			$message = $i18nManager->trans('m.rbs.simpleform.front.bad_csrf_token', array('ucf'));
			$result->setEntry('errors', array('global' => array($message)));
			$result->setEntry('inputData', $data);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500); // TODO is it the good error code?
			return;
		}

		$response = $this->parseData($form, $data, $files, $fieldManager, $documentManager, $i18nManager);
		if ($response instanceof \Rbs\Simpleform\Converter\Validation\Errors)
		{
			// Return an error.
			$result->setEntry('errors', array('fields' => $response->toArray()));
			$result->setEntry('inputData', $data);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
		}
		else
		{
			if ($form->getUseCaptcha())
			{
				$user = $event->getAuthenticationManager()->getCurrentUser();
				if (!$user->authenticated() && (!isset($data['captcha']) || !$securityManager->validateCaptcha($data['captcha'])))
				{
					$message = $i18nManager->trans('m.rbs.simpleform.front.bad_captcha', array('ucf'));
					$result->setEntry('errors', array('global' => array($message)));
					$result->setEntry('inputData', $data);
					$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
					return;
				}
			}

			// Success case, handle the response.
			$this->handleResponse($form, $response, $applicationServices);

			$context = array('website' => $event->getUrlManager()->getWebsite());
			$message = $form->getCurrentLocalization()->getConfirmationMessage();
			$message = $applicationServices->getRichTextManager()->render($message, 'Website', $context);
			$result->setEntry('successMessage', $message);
			$result->setEntry('parsedData', $response->getFieldsInfos());
		}
	}

	/**
	 * @param \Rbs\Simpleform\Documents\Form $form
	 * @param array $data
	 * @param array $files
	 * @param \Rbs\Simpleform\Field\FieldManager $fieldManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\I18n\I18nManager $i18n
	 * @return \Rbs\Simpleform\Converter\Validation\Error|\Rbs\Simpleform\Documents\Response
	 */
	protected function parseData($form, $data, $files, $fieldManager, $documentManager, $i18n)
	{
		/* @var \Rbs\Simpleform\Documents\Response $response */
		$response = $documentManager->getNewDocumentInstanceByModelName('Rbs_Simpleform_Response');
		$response->setForm($form);

		$parsedData = array();
		$errors = array();
		foreach ($form->getFields() as $field)
		{
			$fieldType = $fieldManager->getFieldType($field->getFieldTypeCode());
			$converter = $fieldType->getConverter();
			$parameters = $field->getParameters();
			if ($parameters === null)
			{
				$parameters = array();
			}
			$fieldName = $field->getName();
			$fieldData = isset($data[$fieldName]) ? $data[$fieldName] : (isset($files[$fieldName]) ? $files[$fieldName] : null);
			if ($fieldData !== null && !$converter->isEmptyFromUI($fieldData, $parameters))
			{
				$value = $converter->parseFromUI($fieldData, $parameters);
				if ($value instanceof \Rbs\Simpleform\Converter\Validation\Error)
				{
					$value->setField($field);
					$errors[] = $value;
				}
				else
				{
					$parsedData[$fieldName] = array(
						'name' => $fieldName,
						'title' => $field->getTitle(),
						'formattedValue' => $converter->formatValue($value, $parameters)
					);
					if ($value instanceof \Rbs\Simpleform\Converter\File\TmpFile)
					{
						$parsedData[$fieldName]['value'] = $value->getName();
						$parsedData[$fieldName]['filePath'] = $value->getPath();
					}
					else
					{
						$parsedData[$fieldName]['value'] = $value;
					}
				}
			}
			elseif (!$field->getRequired())
			{
				$parsedData[$fieldName] = null;
			}
			else
			{
				$message = $i18n->trans('m.rbs.simpleform.front.field_required', array('ucf'));
				$errors[] = new \Rbs\Simpleform\Converter\Validation\Error(array($message), $field);
			}
		}

		if (count($errors))
		{
			return new \Rbs\Simpleform\Converter\Validation\Errors($errors);
		}
		$response->setFieldsInfos($parsedData);
		return $response;
	}

	/**
	 * @param \Rbs\Simpleform\Documents\Form $form
	 * @param \Rbs\Simpleform\Documents\Response $response
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @throws \RuntimeException
	 */
	protected function handleResponse($form, $response, $applicationServices)
	{
		// Save response if needed.
		if ($form->getSaveResponses())
		{
			$storageManager = $applicationServices->getStorageManager();
			try
			{
				$applicationServices->getTransactionManager()->begin();

				$infos = $response->getFieldsInfos();
				foreach ($infos as $index => $info)
				{
					if (isset($info['filePath']))
					{
						$infos[$index]['filePath'] = $this->storeFile($form, $storageManager, $info['filePath'], $info['value']);
					}
				}
				$response->setFieldsInfos($infos);
				$response->save();

				$applicationServices->getTransactionManager()->commit();
			}
			catch (\Exception $e)
			{
				$applicationServices->getTransactionManager()->rollBack($e);
			}
		}

		// Send mail.
		// TODO
	}

	/**
	 * @param \Rbs\Simpleform\Documents\Form $form
	 * @param \Change\Storage\StorageManager $storageManager
	 * @param string $tmpPath
	 * @param string $fileName
	 * @throws \RuntimeException
	 * @return string
	 */
	protected function storeFile($form, $storageManager, $tmpPath, $fileName)
	{
		$storageName = 'Rbs_Simpleform';
		$storageEngine = $storageManager->getStorageByName($storageName);
		$resourceParts = array($form->getName(), uniqid() . '_' . trim($fileName));
		$storagePath = $storageEngine->normalizePath(implode('/', $resourceParts));
		$destinationPath = \Change\Storage\StorageManager::DEFAULT_SCHEME . '://' . $storageName . '/' . $storagePath;
		if (move_uploaded_file($tmpPath, $destinationPath))
		{
			if ($storageManager->getItemInfo($destinationPath) === null)
			{
				throw new \RuntimeException('Unable to find: ' . $destinationPath, 999999);
			}
		}
		else
		{
			throw new \RuntimeException('Unable to move "' . $tmpPath . '" in "' . $destinationPath . '"', 999999);
		}
		return $destinationPath;
	}
}
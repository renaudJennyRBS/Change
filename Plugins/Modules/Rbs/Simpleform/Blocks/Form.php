<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Simpleform\Blocks\Form
 */
class Form extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->setNoCache();
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('errId');
		$parameters->addParameterMeta('success');
		$parameters->addParameterMeta('authenticated');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$this->setParameterValueForDetailBlock($parameters, $event);

		$request = $event->getHttpRequest();
		$blockId = $event->getBlockLayout()->getId();
		$parameters->setParameterValue('errId', $request->getQuery('errId'));
		$parameters->setParameterValue('success', $request->getQuery('success-' . $blockId) === 'true');

		$user = $event->getAuthenticationManager()->getCurrentUser();
		$parameters->setParameterValue('authenticated', $user->authenticated());
		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Simpleform\Documents\Form && $document->published())
		{
			return true;
		}
		return false;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$formId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($formId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$form = $documentManager->getDocumentInstance($formId);
			if ($form instanceof \Rbs\Simpleform\Documents\Form && $form->published())
			{
				$attributes['form'] = $form;
				if ($parameters->getParameter('success'))
				{
					return 'form-success.twig';
				}

				/* @var $genericServices \Rbs\Generic\GenericServices */
				$genericServices = $event->getServices('genericServices');
				$fieldManager = $genericServices->getFieldManager();

				$fields = array();
				foreach ($form->getValidFields() as $field)
				{
					/* @var $field \Rbs\Simpleform\Field\FieldInterface */
					$type = $fieldManager->getFieldType($field->getFieldTypeCode());
					$fields[$field->getName()] = array('field' => $field, 'type' => $type);
				}

				// Handle errors.
				$errId = $parameters->getParameterValue('errId');
				if ($parameters->getParameterValue('errId'))
				{
					$session = new \Zend\Session\Container('Change_Errors');
					if (isset($session[$errId]) && is_array($session[$errId]))
					{
						if (isset($session[$errId]['inputData']))
						{
							foreach ($session[$errId]['inputData'] as $key => $value)
							{
								if (isset($fields[$key]))
								{
									$fields[$key]['value'] = $value;
								}
							}
						}

						if (isset($session[$errId]['errors']['global']))
						{
							$attributes['globalErrors'] = $session[$errId]['errors']['global'];
						}

						if (isset($session[$errId]['errors']['fields']))
						{
							$attributes['hasFieldErrors'] = true;
							foreach ($session[$errId]['errors']['fields'] as $error)
							{
								$key = $error['name'];
								if (isset($fields[$key]))
								{
									$fields[$key]['errors'] = $error['messages'];
								}
							}
						}
					}
				}

				$attributes['fieldsInfos'] = $fields;

				// Handle CAPTCHA.
				$attributes['useCaptcha'] = $form->getUseCaptcha() && !$parameters->getParameterValue('authenticated');

				// Success URL.
				if ($form->getConfirmationMode() == 'page' && $form->getConfirmationPage())
				{
					$urlManager = $event->getUrlManager();
					$absoluteUrl = $urlManager->absoluteUrl(true);
					$attributes['successURL'] = $urlManager->getCanonicalByDocument($form->getConfirmationPage());
					$urlManager->absoluteUrl($absoluteUrl);
				}

				// Cross Site Request Forgery prevention.
				$attributes['CSRFToken'] = $genericServices->getSecurityManager()->getCSRFToken();

				return 'form-input.twig';
			}
		}
		return null;
	}
}
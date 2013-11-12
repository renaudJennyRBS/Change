<?php
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
		$parameters->addParameterMeta('formId');
		$parameters->addParameterMeta('errId');
		$parameters->addParameterMeta('success');
		$parameters->addParameterMeta('authenticated');

		$parameters->setLayoutParameters($event->getBlockLayout());
		if ($parameters->getParameter('formId') === null)
		{
			$document = $event->getParam('document');
			if ($document instanceof \Rbs\Simpleform\Documents\Form)
			{
				$parameters->setParameterValue('formId', $document->getId());
			}
		}

		$request = $event->getHttpRequest();
		$blockId = $event->getBlockLayout()->getId();
		$parameters->setParameterValue('errId', $request->getQuery('errId'));
		$parameters->setParameterValue('success', $request->getQuery('success-' . $blockId) === 'true');

		$user = $event->getAuthenticationManager()->getCurrentUser();
		$parameters->setParameterValue('authenticated', $user->authenticated());
		return $parameters;
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
		$formId = $parameters->getParameter('formId');
		if ($formId)
		{
			$documentManager = $event->getDocumentServices()->getDocumentManager();
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
					if (!$type)
					{
						var_dump($field->getLabel());
					}
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
					$absoluteUrl = $event->getUrlManager()->getAbsoluteUrl();
					$event->getUrlManager()->setAbsoluteUrl(true);
					$attributes['successURL'] = $event->getUrlManager()->getCanonicalByDocument($form->getConfirmationPage());
					$event->getUrlManager()->setAbsoluteUrl($absoluteUrl);
				}

				// Cross Site Request Forgery prevention.
				$attributes['CSRFToken'] = $genericServices->getSecurityManager()->getCSRFToken();

				return 'form-input.twig';
			}
		}
		return null;
	}
}
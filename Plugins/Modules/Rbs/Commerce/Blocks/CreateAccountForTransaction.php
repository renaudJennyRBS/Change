<?php
namespace Rbs\Commerce\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;

/**
 * @name \Rbs\Commerce\Blocks\CreateAccountForTransaction
 */
class CreateAccountForTransaction extends \Rbs\User\Blocks\CreateAccount
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);

		$request = $event->getHttpRequest();
		$transactionId = $request->getQuery('transactionId');
		$transaction = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($transactionId);
		if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
		{
			$data = $transaction->getContextData();
			if (isset($data['email']))
			{
				$parameters->addParameterMeta('transactionId');
				$parameters->setParameterValue('transactionId', $transactionId);
				$parameters->setParameterValue('formAction', 'Action/Rbs/Commerce/CreateAccountRequest?transactionId=' . $transactionId);

				$initialValues = $parameters->getParameterValue('initialValues');
				$initialValues['email'] = $data['email'];
				$parameters->setParameterValue('initialValues', $initialValues);
				$readonlyNames = $parameters->getParameterValue('readonlyNames');
				$readonlyNames['email'] = true;
				$parameters->setParameterValue('readonlyNames', $readonlyNames);
			}
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters, getApplication, getApplicationServices, getServices, getHttpRequest
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		if (!$parameters->getParameterValue('transactionId'))
		{
			return null;
		}
		$this->setTemplateModuleName('Rbs_User');
		return parent::execute($event, $attributes);
	}
}
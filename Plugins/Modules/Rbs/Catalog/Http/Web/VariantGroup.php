<?php
namespace Rbs\Catalog\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Catalog\Http\Web\VariantGroup
*/
class VariantGroup extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$this->getProducts($event);
		}
	}

	/**
	 * @param Event $event
	 */
	public function getProducts(Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$data = $event->getRequest()->getPost()->toArray();
		$variantGroupId = $data['variantGroupId'];
		$parentVariant = $data['parentVariant'];

		$variantGroup = $dm->getDocumentInstance($variantGroupId);
		$responseData = null;

		if ($variantGroup instanceof \Rbs\Catalog\Documents\VariantGroup)
		{
			$responseData['axesValues'] = $variantGroup->getAxesValuesByParentId($parentVariant);
		}
		else
		{
			$responseData['error'] = '$variantGroupId is not a Variant Group !';
		}

		$result = new \Change\Http\Web\Result\AjaxResult($responseData);
		$event->setResult($result);
	}
}
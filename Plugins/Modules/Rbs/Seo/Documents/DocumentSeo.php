<?php
namespace Rbs\Seo\Documents;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Seo\Documents\DocumentSeo
 */
class DocumentSeo extends \Compilation\Rbs\Seo\Documents\DocumentSeo
{
	/**
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->getTarget())
		{
			return $this->getTarget()->getDocumentModel()->getPropertyValue($this->getTarget(), 'label', '-');
		}
		return '-';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		// Do nothing.
		return $this;
	}

	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$extraColumn = $event->getParam('extraColumn');
			$documentLink = $restResult;
			if (in_array('targetModelLabel', $extraColumn))
			{
				/** @var $document DocumentSeo */
				$document = $event->getDocument();
				$i18n = $event->getApplicationServices()->getI18nManager();
				$label = $document->getTarget() ? $i18n->trans($document->getTarget()->getDocumentModel()->getLabelKey(), array('ucf')) : '';
				$documentLink->setProperty('targetModelLabel', $label);
			}
		}
	}
}

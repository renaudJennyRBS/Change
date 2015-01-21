<?php
namespace Rbs\Storeshipping\Documents;

/**
 * @name \Rbs\Storeshipping\Documents\RelayMode
 */
class RelayMode extends \Compilation\Rbs\Storeshipping\Documents\RelayMode
{
	/**
	 * @return string
	 */
	public function getCategory()
	{
		return static::CATEGORY_RELAY;
	}

	public function onDefaultGetModeData(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultGetModeData($event);
		$modeData = $event->getParam('modeData');
		$modeData['editor'] = [
			'defaultLatitude' => 48.856578,
			'defaultLongitude' => 2.351828,
			'defaultZoom' => 11
		];
		$commercialSign = $this->getCommercialSign();
		if ($commercialSign)
		{
			$modeData['editor']['commercialSign'] = $commercialSign->getCurrentLocalization()->getTitle();
			$modeData['editor']['commercialSignId'] = $commercialSign->getId();
			$marker = $commercialSign->getMarker();
			if ($marker)
			{
				$modeData['editor']['marker'] = [
					'url' => $marker->getPublicURL(),
					'width' => $marker->getWidth(),
					'height' => $marker->getHeight()
				];
			}
		}
		$event->setParam('modeData', $modeData);
	}
}

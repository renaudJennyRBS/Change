<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Review\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Review\Http\Web\UpdateReview
*/
class UpdateReview extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$data = $event->getRequest()->getPost()->toArray();
			$reviewId = $data['reviewId'];
			$review = $documentManager->getDocumentInstance($reviewId);
			if ($review instanceof \Rbs\Review\Documents\Review)
			{
				$rating = $data['rating'];
				$content = $data['content'];
				if ($rating && $content)
				{
					$review->setRating($rating);
					$review->setContent($content);
					$review->setReviewDate(new \DateTime());
					$tm = $event->getApplicationServices()->getTransactionManager();
					try
					{
						$tm->begin();
						$review->update();
						$tm->commit();
					}
					catch(\Exception $e)
					{
						throw $tm->rollBack($e);
					}
				}
				else
				{
					$data['error'] = 'Invalid parameters';
				}
			}
			else
			{
				$data = [ 'error' => 'Invalid review' ];
			}
			$result = new \Change\Http\Web\Result\AjaxResult($data);
			if ($data['redirectLocation'])
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_302);
				$result->setHeaderLocation($data['redirectLocation']);
			}
			$event->setResult($result);
		}
	}
}
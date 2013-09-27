<?php
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
			$documentManager = $event->getDocumentServices()->getDocumentManager();
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
					//freeze the document pending validation
					$review->setPublicationStatus(\Change\Documents\Interfaces\Publishable::STATUS_FROZEN);
					//TODO change review date or add a new one?
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
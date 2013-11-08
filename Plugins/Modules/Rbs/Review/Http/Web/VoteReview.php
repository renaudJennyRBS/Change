<?php
namespace Rbs\Review\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Review\Http\Web\VoteReview
 */
class VoteReview extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param Event $event
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
				$vote = $data['vote'];
				$voted = false;
				if ($vote == 'up')
				{
					$review->setUpvote($review->getUpvote() + 1);
					$voted = true;
				}
				elseif ($vote == 'down')
				{
					$review->setDownvote($review->getDownvote() + 1);
					$voted = true;
				}
				else
				{
					$data['error'] = 'Invalid parameters';
				}
				if ($voted)
				{
					$data['upvote'] = $review->getUpvote();
					$data['downvote'] = $review->getDownvote();
					$tm = $event->getApplicationServices()->getTransactionManager();
					try
					{
						$tm->begin();
						$review->update();
						$tm->commit();
					}
					catch (\Exception $e)
					{
						throw $tm->rollBack($e);
					}
				}
			}
			else
			{
				$data = ['error' => 'Invalid review'];
			}
			$result = new \Change\Http\Web\Result\AjaxResult($data);
			$event->setResult($result);
		}
	}
}
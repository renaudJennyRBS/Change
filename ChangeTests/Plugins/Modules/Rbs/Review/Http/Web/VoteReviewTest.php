<?php

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

class VoteReviewTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	public function testExecute()
	{
		$review = $this->getNewReview();

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$event->setDocumentServices($this->getDocumentServices());

		$this->assertEquals(0, $review->getUpvote());
		$this->assertEquals(0, $review->getDownvote());

		//Vote up first
		$request = new \Change\Http\Request();
		$request->setMethod('POST');
		$data = [ 'reviewId' => $review->getId(), 'vote' => 'up' ];
		$request->getPost()->fromArray($data);

		$event->setRequest($request);

		$postReview = new \Rbs\Review\Http\Web\VoteReview();
		$postReview->execute($event);
		$result = $event->getResult();
		$this->assertInstanceOf('\Change\Http\Web\Result\AjaxResult', $result);
		/* @var $result \Change\Http\Web\Result\AjaxResult */
		$data = $result->toArray();
		$this->assertFalse(array_key_exists('error', $data));
		$this->assertEquals(1, $data['upvote']);
		$this->assertEquals(0, $data['downvote']);
		//check the updated review
		$model = $this->getDocumentServices()->getModelManager()->getModelByName('Rbs_Review_Review');
		$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), $model);
		$query = $dqb->andPredicates($dqb->eq('id', $review->getId()));
		$reviews = $query->getDocuments();
		$this->assertCount(1, $reviews);
		$updatedReview = $reviews[0];
		/* @var $updatedReview \Rbs\Review\Documents\Review */
		$this->assertEquals(1, $updatedReview->getUpvote());
		$this->assertEquals(0, $updatedReview->getDownvote());

		//Vote down
		$request = new \Change\Http\Request();
		$request->setMethod('POST');
		$data = [ 'reviewId' => $review->getId(), 'vote' => 'down' ];
		$request->getPost()->fromArray($data);

		$event->setRequest($request);

		$postReview = new \Rbs\Review\Http\Web\VoteReview();
		$postReview->execute($event);
		$result = $event->getResult();
		$this->assertInstanceOf('\Change\Http\Web\Result\AjaxResult', $result);
		/* @var $result \Change\Http\Web\Result\AjaxResult */
		$data = $result->toArray();
		$this->assertFalse(array_key_exists('error', $data));
		$this->assertEquals(1, $data['upvote']);
		$this->assertEquals(1, $data['downvote']);
		//check the updated review
		$model = $this->getDocumentServices()->getModelManager()->getModelByName('Rbs_Review_Review');
		$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), $model);
		$query = $dqb->andPredicates($dqb->eq('id', $review->getId()));
		$reviews = $query->getDocuments();
		$this->assertCount(1, $reviews);
		$updatedReview = $reviews[0];
		/* @var $updatedReview \Rbs\Review\Documents\Review */
		$this->assertEquals(1, $updatedReview->getUpvote());
		$this->assertEquals(1, $updatedReview->getDownvote());

		//Vote up again
		$request = new \Change\Http\Request();
		$request->setMethod('POST');
		$data = [ 'reviewId' => $review->getId(), 'vote' => 'up' ];
		$request->getPost()->fromArray($data);

		$event->setRequest($request);

		$postReview = new \Rbs\Review\Http\Web\VoteReview();
		$postReview->execute($event);
		$result = $event->getResult();
		$this->assertInstanceOf('\Change\Http\Web\Result\AjaxResult', $result);
		/* @var $result \Change\Http\Web\Result\AjaxResult */
		$data = $result->toArray();
		$this->assertFalse(array_key_exists('error', $data));
		$this->assertEquals(2, $data['upvote']);
		$this->assertEquals(1, $data['downvote']);
		//check the updated review
		$model = $this->getDocumentServices()->getModelManager()->getModelByName('Rbs_Review_Review');
		$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), $model);
		$query = $dqb->andPredicates($dqb->eq('id', $review->getId()));
		$reviews = $query->getDocuments();
		$this->assertCount(1, $reviews);
		$updatedReview = $reviews[0];
		/* @var $updatedReview \Rbs\Review\Documents\Review */
		$this->assertEquals(2, $updatedReview->getUpvote());
		$this->assertEquals(1, $updatedReview->getDownvote());
	}

	/**
	 * @return \Rbs\Catalog\Documents\Product
	 * @throws Exception
	 */
	protected function getNewTarget()
	{
		$target = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		/* @var $target \Rbs\Catalog\Documents\Product */
		$target->setLabel('Nintendo NES');
		$target->getCurrentLocalization()->setTitle('Nintendo NES');
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$target->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $target;
	}

	/**
	 * @return \Rbs\Website\Documents\Website
	 * @throws Exception
	 */
	protected function getNewWebsite()
	{
		$website = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');
		/* @var $website \Rbs\Website\Documents\Website */
		$website->setLabel('test');
		$website->getCurrentLocalization()->setTitle('test');
		$website->setBaseurl('http://test.rbs.fr');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$website->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $website;
	}

	/**
	 * @return \Rbs\Review\Documents\Review
	 * @throws Exception
	 */
	protected function getNewReview()
	{
		$review = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Review_Review');
		/* @var $review \Rbs\Review\Documents\Review */
		$review->setRating(60);
		$review->setContent('test for vote');
		$review->setPseudonym('Luigi Bros');
		$review->setTarget($this->getNewTarget());
		$review->setSection($this->getNewWebsite());
		$review->setReviewDate(new \DateTime());
		$review->setPromoted(false);

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$review->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $review;
	}
}
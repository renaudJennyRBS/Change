<?php
namespace Rbs\Price\Job;

/**
 * @name \Rbs\Price\Job\UpdateTax
 */
class UpdateTax
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();

		$applicationServices = $event->getApplicationServices();
		$documentManager = $applicationServices->getDocumentManager();
		$price = $documentManager->getDocumentInstance($job->getArgument('basePriceId'));
		if (!($price instanceof \Rbs\Price\Documents\Price))
		{
			$event->setResultArgument('inputArgument', 'Price not found');
			$event->success();
			return;
		}

		if ($price->getMeta('Job_UpdateTax') != $job->getId())
		{
			$event->setResultArgument('inputArgument', 'Not current Job');
			$event->success();
			return;
		}

		$tm = $applicationServices->getTransactionManager();

		try
		{
			$tm->begin();

			$taxCategories = $price->getTaxCategories();

			/** @var $basedOnPrice \Rbs\Price\Documents\Price */
			foreach ($price->getPricesBasedOn() as $basedOnPrice)
			{
				$basedOnPrice->setTaxCategories($taxCategories);
				$basedOnPrice->save();
			}

			$price->setMeta('Job_UpdateTax', null);
			$price->saveMetas();
			$event->success();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$event->failed($e->getMessage());
			$tm->rollBack();
		}

	}

}
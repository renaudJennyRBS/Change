<?php
namespace Rbs\Workflow\Setup;

use  Rbs\Workflow\Documents;
use  Rbs\Workflow\Std;

/**
* @name \Rbs\Workflow\Setup\CorrectionPublicationProcessWorkflow
*/
class CorrectionPublicationProcessWorkflow
{
	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	public function __construct($documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return Documents\Workflow
	 * @throws \RuntimeException
	 */
	public function install()
	{
		/* @var $workflow Documents\Workflow */
		$workflow = $this->documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Workflow_Workflow');

		$workflow->setStartTask('correctionPublicationProcess')->setActive(true);
		$workflow->setLabel('Correction publication Process');

		$draft = $workflow->getNewPlace()->setName('Draft')->setType(Std\Place::TYPE_START);
		$validation = $workflow->getNewPlace()->setName('Validation');
		$validContent = $workflow->getNewPlace()->setName('ValidContent');
		$valid = $workflow->getNewPlace()->setName('Valid');
		$publishable = $workflow->getNewPlace()->setName('Publishable');
		$filed = $workflow->getNewPlace()->setName('Filed')->setType(Std\Place::TYPE_END);

		$requestValidation = $workflow->getNewTransition()->setName('Request Validation')->setTaskCode('requestValidation')
			->setTrigger(Std\Transition::TRIGGER_USER)->setRole('Creator')->setShowInDashboard(true);

		$contentValidation = $workflow->getNewTransition()->setName('Content Validation')->setTaskCode('contentValidation')
			->setTrigger(Std\Transition::TRIGGER_USER)->setRole('Editor')->setShowInDashboard(true);

		$publicationValidation = $workflow->getNewTransition()->setName('Publication Validation')->setTaskCode('publicationValidation')
			->setTrigger(Std\Transition::TRIGGER_USER)->setRole('Publisher')->setShowInDashboard(true);


		$contentMerging = $workflow->getNewTransition()->setName('Content Merging')->setTaskCode('contentMerging')
			->setTrigger(Std\Transition::TRIGGER_AUTO);

		$delayedContentMerging = $workflow->getNewTransition()->setName('Delayed Content Merging')->setTaskCode('contentMerging')
			->setTrigger(Std\Transition::TRIGGER_TIME)->setTimeLimit('P10Y');

		$cancel = $workflow->getNewTransition()->setName('Cancel')->setTaskCode('cancel')
			->setTrigger(Std\Transition::TRIGGER_USER)->setRole('Publisher');

		$workflow->getNewArc()->connect($draft, $requestValidation);
		$workflow->getNewArc()->connect($requestValidation, $validation);
		$workflow->getNewArc()->connect($validation, $contentValidation);
		$workflow->getNewArc()->connect($contentValidation, $draft)->setPreCondition('NO');
		$workflow->getNewArc()->connect($contentValidation, $validContent)->setPreCondition(Std\Arc::PRECONDITION_DEFAULT);
		$workflow->getNewArc()->connect($validContent, $publicationValidation);

		$workflow->getNewArc()->connect($publicationValidation, $publishable)->setPreCondition(Std\Arc::PRECONDITION_DEFAULT);
		$workflow->getNewArc()->connect($publishable, $contentMerging);
		$workflow->getNewArc()->connect($contentMerging, $filed);

		$workflow->getNewArc()->connect($publicationValidation, $valid)->setPreCondition('DELAYED');

		$workflow->getNewArc()->connect($valid, $delayedContentMerging)->setType(Std\Arc::TYPE_IMPLICIT_OR_SPLIT);
		$workflow->getNewArc()->connect($delayedContentMerging, $filed);

		$workflow->getNewArc()->connect($valid, $cancel)->setType(Std\Arc::TYPE_IMPLICIT_OR_SPLIT);
		$workflow->getNewArc()->connect($cancel, $draft);

		if ($workflow->isValid())
		{
			$workflow->save();
			return $workflow;
		}
		else
		{
			throw new \RuntimeException($workflow->getErrors());
		}
	}
}
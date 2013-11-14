<?php
namespace Rbs\Admin\Profile;


/**
* @name \Rbs\Admin\Profile\Profile
*/
class Profile extends \Change\User\AbstractProfile
{
	function __construct()
	{
		$this->properties = array(
			'avatar' => 'Rbs/Admin/img/chuck.jpg',
			'pagingSize' => 10,
			'documentListViewMode' => 'list',
			'editorActionAfterSave' => 'stay',
			'sendNotificationMailImmediately' => false,
			'notificationMailInterval' => '',
			'notificationMailAt' => '',
			'dateOfLastNotificationMailSent' => 0
		);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Rbs_Admin';
	}

	/**
	 * @return string[]
	 */
	public function getPropertyNames()
	{
		return array('avatar', 'pagingSize', 'documentListViewMode', 'editorActionAfterSave', 'dashboard',
			'sendNotificationMailImmediately', 'notificationMailInterval', 'notificationMailAt', 'dateOfLastNotificationMailSent');
	}
}
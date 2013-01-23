<?php
namespace ChangeTests\Documents\Constraints;

/**
 * @name \ChangeTests\Documents\Constraints\ConstraintsManagerTest
 */
class ConstraintsManagerTest extends \PHPUnit_Framework_TestCase
{	
	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return \Change\Application::getInstance();
	}
	
	
	public function testConstruct()
	{
		$constraintsManager = $this->getApplication()->getDocumentServices()->getConstraintsManager();
		$this->assertInstanceOf('\Change\Documents\Constraints\ConstraintsManager', $constraintsManager);
		
		$this->assertEquals('c.constraints', \Zend\Validator\AbstractValidator::getDefaultTranslatorTextDomain());
		
		$t = \Zend\Validator\AbstractValidator::getDefaultTranslator();
		$this->assertInstanceOf('\Change\Documents\Constraints\Translator', $t);
		$this->assertInstanceOf('\Change\I18n\I18nManager', $t->getI18nManager());
		
		return $constraintsManager;
	}
	
	/**
	 * @depends testConstruct
	 * @param \Change\Documents\Constraints\ConstraintsManager $constraintsManager
	 */
	public function testDefaultConstraint($constraintsManager)
	{
		$this->assertTrue($constraintsManager->hasDefaultConstraint('domain'));
		$this->assertTrue($constraintsManager->hasDefaultConstraint('url'));
		$this->assertTrue($constraintsManager->hasDefaultConstraint('unique'));
		$this->assertTrue($constraintsManager->hasDefaultConstraint('enum'));
		
		$this->assertFalse($constraintsManager->hasDefaultConstraint('test'));
		$constraintsManager->registerConstraint('test', '\ChangeTests\Documents\Constraints\ConstraintNotFound');
		$this->assertTrue($constraintsManager->hasDefaultConstraint('test'));
		
		return $constraintsManager;
	}
	
	/**
	 * @depends testDefaultConstraint
	 * @param \Change\Documents\Constraints\ConstraintsManager $constraintsManager
	 */
	public function testGetByName($constraintsManager)
	{
		$this->assertInstanceOf('\Change\Documents\Constraints\Domain', $constraintsManager->getByName('domain'));
		$this->assertInstanceOf('\Change\Documents\Constraints\Min', $constraintsManager->getByName('min', array('min' => 1)));
	
		$c1 = $constraintsManager->getByName('email');
		$c2 = $constraintsManager->getByName('email');
		
		$this->assertNotNull($c1);
		$this->assertNotNull($c2);
		$this->assertNotSame($c1, $c2);
		try 
		{
			$constraintsManager->getByName('test');
			$this->fail('Constraint test not found');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Constraint test not found', $e->getMessage());
		}
		
		try
		{
			$constraintsManager->getByName('invalidconstraint');
			$this->fail('Constraint invalidconstraint not found');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertStringStartsWith('Constraint invalidconstraint not found', $e->getMessage());
		}
		
		return $constraintsManager;
	}	
	
	/**
	 * @depends testGetByName
	 * @param \Change\Documents\Constraints\ConstraintsManager $constraintsManager
	 */
	public function testEmail($constraintsManager)
	{
		include __DIR__ . '/TestAssets/Translator.php';
		$t = \ChangeTests\Documents\Constraints\TestAssets\Translator::factory(array());
		\Zend\Validator\AbstractValidator::setDefaultTranslator($t);
		

		$constraint = $constraintsManager->getByName('email');
		$this->assertTrue($constraint->isValid('noreplay@rbschange.fr'));
		
		$constraint = $constraintsManager->getByName('email');
		$this->assertFalse($constraint->isValid('rbschange.fr'));
		
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('emailAddressInvalidFormat', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['emailAddressInvalidFormat']);
		
		return $constraintsManager;
	}
	

	/**
	 * @depends testEmail
	 * @param \Change\Documents\Constraints\ConstraintsManager $constraintsManager
	 */
	public function testEmails($constraintsManager)
	{
	
		$constraint = $constraintsManager->getByName('emails');
		$this->assertTrue($constraint->isValid('noreplay@rbschange.fr'));
		$this->assertTrue($constraint->isValid('noreplay@rbschange.fr, admin@rbschange.fr'));
	
		$constraint = $constraintsManager->getByName('emails');
	
		$this->assertFalse($constraint->isValid('noreplay@rbschange.fr,,rbschange.fr'));
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('emailsAddressInvalid', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['emailsAddressInvalid']);
		return $constraintsManager;
	}
	
	/**
	 * @depends testEmails
	 * @param \Change\Documents\Constraints\ConstraintsManager $constraintsManager
	 */
	public function testInteger($constraintsManager)
	{
	
		$constraint = $constraintsManager->getByName('integer');
		$this->assertTrue($constraint->isValid('5'));

		$constraint = $constraintsManager->getByName('integer');
	
		$this->assertFalse($constraint->isValid('5.3'));
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('notDigits', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['notDigits']);
		return $constraintsManager;
	}
	
	/**
	 * @depends testInteger
	 * @param \Change\Documents\Constraints\ConstraintsManager $constraintsManager
	 */
	public function testMatches($constraintsManager)
	{
		$constraint = $constraintsManager->getByName('matches', array('pattern' => '/^[0-5]+$/'));
		$this->assertTrue($constraint->isValid('4'));
		$this->assertTrue($constraint->isValid('45550'));
	
		$constraint = $constraintsManager->getByName('matches', array('pattern' => '/^[0-5]+$/', 'message' => 'test'));
	
		$this->assertFalse($constraint->isValid('46'));
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('regexNotMatch', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['regexNotMatch']);
		return $constraintsManager;
	}
	
	/**
	 * @depends testMatches
	 * @param \Change\Documents\Constraints\ConstraintsManager $constraintsManager
	 */
	public function testRange($constraintsManager)
	{
		$constraint = $constraintsManager->getByName('range', array('min' => 5, 'max' => 10));
		$this->assertTrue($constraint->isValid('5'));
		$this->assertTrue($constraint->isValid('6'));
		$this->assertTrue($constraint->isValid('10'));
	
		$constraint = $constraintsManager->getByName('range', array('min' => 5, 'max' => 10));
	
		$this->assertFalse($constraint->isValid('46'));
		$messages = $constraint->getMessages();

		$this->assertArrayHasKey('notBetween', $messages);
		$this->assertStringStartsWith('c.constraints.', $messages['notBetween']);
		
		
		$constraint = $constraintsManager->getByName('range', array('min' => 5, 'max' => 10, 'inclusive' => false));
		$this->assertFalse($constraint->isValid('5'));
		$messages = $constraint->getMessages();
		$this->assertArrayHasKey('notBetweenStrict', $messages);
		
		
		return $constraintsManager;
	}
}

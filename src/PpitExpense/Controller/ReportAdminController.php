<?php
namespace PpitExpense\Controller;

use PpitContact\Model\Vcard;
use PpitCore\Model\Csrf;
use PpitCore\Model\Context;
use PpitCore\Model\Instance;
use PpitCore\Model\Link;
use PpitMasterData\Model\Agent;
use PpitMasterData\Model\AgentAttachment;
use PpitMasterData\Model\OrgUnit;
use PpitPil\Model\Event;
use PpitUser\Model\User;
use PpitUser\Model\UserPerimeter;
use PpitUser\Model\UserPerimeterLinker;
use PpitUser\Model\UserRoleLinker;
use PpitCore\Form\CsrfForm;
use Zend\Db\Sql\Expression;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;

class ReportAdminController extends AbstractActionController
{
    public function subscribeAction()
    {
    	// Retrieve the current user
    	$context = Context::getCurrent();
    	
    	$instance = new Instance();
	    $vcard = new Vcard();
    	$user = new User();
    	$userRL = new UserRoleLinker();
    	$orgUnit = new OrgUnit();

    	// Retrieve the available locales list
    	$locales = $context->getConfig()['ppitUserSettings']['locales'];
    	$event = new Event();

    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('csrf');

    	$error = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    		
    		if ($csrfForm->isValid()) { // CSRF check
    			// Check for duplicate contact
	    		$vcard->n_title = $request->getPost('n_title');
	    		$vcard->n_last = $request->getPost('n_last');
	    		$vcard->n_first = $request->getPost('n_first');
//	    		$vcard->org = $instance->caption;
	    		$vcard->email = $request->getPost('email');
	    		$vcard->tel_work = $request->getPost('tel_work');
	    		$vcard->tel_cell = $request->getPost('tel_cell');
	    		$user->username = $request->getPost('username');

    			// same first name, last name and email
    			$select = Vcard::getTable()->getSelect()
    				->where(array('n_first' => $vcard->n_first, 'n_last' => $vcard->n_last, 'email' => $vcard->email));
		        if (count(Vcard::getTable()->selectWith($select)) > 0) $error = 'Duplicate contact';
		        else {
    				// same first name, last name and cellular
		            $select = Vcard::getTable()->getSelect()
		            	->where(array('n_first' => $vcard->n_first, 'n_last' => $vcard->n_last, 'tel_cell' => $vcard->tel_cell));
		            if (count(Vcard::getTable()->selectWith($select)) > 0) $error = 'Duplicate contact';
					else {
						// Duplicate user check
			            $select = User::getTable()->getSelect()
			            	->where(array('username' => $context->getUsername()));
			            if (count(User::getTable()->selectWith($select)) > 0) $error = 'Duplicate user';
			            else try {
		    				/**
		    				 * @var \Zend\Db\Adapter\Driver\Pdo\Connection
		    				 */
			    			$connection = User::getTable()->getAdapter()->getDriver()->getConnection();
			    			$connection->beginTransaction();

			    			// Creates the instance
			    			$instance->id = NULL;
			    			$instance->caption = $request->getPost('instance_caption');
				    		$instance->checkIntegrity();
			    			$instance->id = Instance::getTable()->save($instance);
			    			$context->instance_id = $instance->id;
			
			    			// Contact
			    			$vcard->id = NULL;
			    			$vcard->n_fn = $vcard->n_last.', '.$vcard->n_first;
			    			$vcard->id = Vcard::getTable()->save($vcard);
			    				 
				    		// User
				    		$user->user_id = null;
				    		$user->contact_id = $vcard->id;
				    		$user->locale = $request->getPost('locale');
				    		$user->state = 1;
				    		$user->password = md5(uniqid(rand(), true));
				    		$user->checkIntegrity($locales);
				    		$user_id = User::getTable()->save($user);

				    		// Create the fs root link
				    		$link = new Link();
				    		$link->id = null;
				    		$link->owner_id = $user_id;
				    		$link->parent_id = 0;
				    		$link->name = $vcard->n_fn.' - '.(($vcard->email) ? $vcard->email : $vcard->tel_cell);
				    		Link::getTable()->save($link);
				    			 
				    		// Set the role
				    		$userRL->user_id = $user_id;
				    		$userRL->role_id = 'admin';
				    		UserRoleLinker::getTable()->save($userRL);
				    			
				    		// Organisational unit
				    		$orgUnit->id = null;
				    		$orgUnit->level = 1;
				    		$orgUnit->type = '1';
				    		$orgUnit->parent_id = 0;
				    		$orgUnit->identifier = ($request->getPost('identifier')) ? $request->getPost('identifier') : $instance->caption;
				    		$orgUnit->caption = ($request->getPost('org_unit_caption')) ? $request->getPost('org_unit_caption') : $instance->caption;
				    		$orgUnit->checkIntegrity();
				    		$orgUnit->id = OrgUnit::getTable()->save($orgUnit);
			
				    		// Perimeter
				    		$perimeter = new UserPerimeter();
				    		$perimeter->id = null;
					    	$perimeter->entity = 'expense_report_row';
					    	$perimeter->column = 'org_unit_id';
					    	$perimeter->value = $orgUnit->id;
				    		$perimeter->id = UserPerimeter::getTable()->save($perimeter);

				    		// Perimeter linker
				    		$perimeterLinker = new UserPerimeterLinker();
				    		$perimeterLinker->user_id = $user_id;
				    		$perimeterLinker->perimeter_id = $perimeter->id;
				    		UserPerimeterLinker::getTable()->save($perimeterLinker);
				    		
				    		// Events
				    		$event->year = date('Y');
					    	$event->indicator = 'expense_amount';
					    	$event->expected_value = $request->getPost('monthly_budget');
					    	$event->actual_value = 0;
					    	$event->entity = 'user_perimeter';
					    	$event->entity_id = $perimeter->id;
					    	
					    	// Annual budget
					    	$event->id = null;
					    	$event->period = null;
					    	$event->checkIntegrity();
					    	Event::getTable()->save($event);
				    		 
						    // Per month budget
				    		$captions = array();
					    	$captions[] = date('Y').'-'.'01';
					    	$captions[] = date('Y').'-'.'02';
					    	$captions[] = date('Y').'-'.'03';
					    	$captions[] = date('Y').'-'.'04';
					    	$captions[] = date('Y').'-'.'05';
					    	$captions[] = date('Y').'-'.'06';
					    	$captions[] = date('Y').'-'.'07';
					    	$captions[] = date('Y').'-'.'08';
					    	$captions[] = date('Y').'-'.'09';
					    	$captions[] = date('Y').'-'.'10';
					    	$captions[] = date('Y').'-'.'11';
					    	$captions[] = date('Y').'-'.'12';
					    	$periods = array();
						    foreach ($captions as $caption) {
						    	$event->id = null;
						    	$event->period = $caption;
					    		$event->checkIntegrity();
					    		Event::getTable()->save($event);
						    }
			
				    		// Create the default agent
				    		$agent = new Agent();
				    		$agent->id = null;
				    		$agent->contact_id = $vcard->id;
				    		$agent->start_date = date('Y-m-d');
				    		$agent->id = Agent::getTable()->save($agent);
				    			
				    		// Create the default agent attachment
				    		$agentAttachment = new AgentAttachment();
				    		$agentAttachment->id = null;
				    		$agentAttachment->agent_id = $agent->id;
				    		$agentAttachment->org_unit_id = $orgUnit->id;
				    		$agent->effective_date = date('Y-m-d');
				    		$agentAttachment->id = AgentAttachment::getTable()->save($agentAttachment);
/*
				    		// Send the email to the user
					    	$translator = $this->getServiceLocator()->get('translator');
				    		$url = $this->getServiceLocator()->get('viewhelpermanager')->get('url');
				    		$email_body = $translator->translate('You have requested a trial account on P-PIT expense reports. ', 'ppit-expense', $context->getLocale());
				    		$email_body .= $translator->translate('In order to set your password for your new identifier: %s, please follow this link: ', 'ppit-user', $context->getLocale());
				    		$email_body = sprintf($email_body, $context->getUsername());
				    		$email_body .= $coreSettings['domainName'].$url('reportAdmin/initpassword', array('id' => $user_id)).'?hash='.$user->password;
							$email_title = $translator->translate('P-PIT: Your credentials', 'ppit-user', $currentUser->locale);
				    		Message::sendMail($context, $coreSettings, $vcard->email, $email_body, $email_title, null);*/
		
				    		$connection->commit();
				    			
				    		// Redirect
//				    		return $this->redirect()->toRoute('zfcuser/login');
			    		}
					    catch (Exception $e) {
					    	$connection->rollback();
					    	throw $e;
					    }
			        }
				}
    		}
    	}

    	return array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'csrfForm' => $csrfForm,
    		'instance' => $instance,
    		'vcard' => $vcard,
    		'user' => $user,
    		'orgUnit' => $orgUnit,
    		'event' => $event,
    		'locales' => $locales,
    		'error' => $error,
    	);
    }
}

<?php
namespace PpitExpense\Controller;

use PpitCore\Form\CsrfForm;
use PpitCore\Model\Context;
use PpitCore\Model\Csrf;
use PpitCore\Model\Link;
use PpitExpense\Model\ReportRow;
use PpitExpense\Form\ReportRowForm;
use PpitPil\Model\Event;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class ApprobationController extends AbstractActionController
{	    
    public function indexAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();

    	$captions = array();
    	$captions[] = date('Y').'-'.date('01');
    	$captions[] = date('Y').'-'.date('02');
    	$captions[] = date('Y').'-'.date('03');
    	$captions[] = date('Y').'-'.date('04');
    	$captions[] = date('Y').'-'.date('05');
    	$captions[] = date('Y').'-'.date('06');
    	$captions[] = date('Y').'-'.date('07');
    	$captions[] = date('Y').'-'.date('08');
    	$captions[] = date('Y').'-'.date('09');
    	$captions[] = date('Y').'-'.date('10');
    	$captions[] = date('Y').'-'.date('11');
    	$captions[] = date('Y').'-'.date('12');
    	
    	$periods = array();
    	if (date('m') == '01') {
			$caption = (date('Y') - 1).'/'.date('12');
    		$periods[] = Event::getPeriodAchievedSum($caption, 'expense_report_row');
    	}
    	foreach ($captions as $caption) {
    		
    		// Retrieve the period achieved sums
    		$periods[] = Event::getPeriodAchievedSum($caption, 'expense_report_row');
    	}
    	// Retrieve the year achieved sums
    	$periods[] = Event::getYearAchievedSum(substr($caption, 0, 4), 'expense_report_row');

    	// Prepare the SQL request
    	$major = $this->params()->fromQuery('major', NULL);
    	if (!$major) $major = 'expense_date';
    	$dir = $this->params()->fromQuery('dir', NULL);
    	if (!$dir) $dir = 'DESC';

    	$subSelect = UserPerimeterLinker::getTable()->getSelect()
	    	->columns(array())
	    	->join('user_perimeter', 'user_perimeter_linker.perimeter_id = user_perimeter.id', array('value'), 'left')
	    	->where(array('user_perimeter_linker.user_id' => $currentUser->user_id, 'user_perimeter.entity' => 'expense_report_row', 'user_perimeter.column' => 'org_unit_id'));
    	
    	$select = ReportRow::getTable()->getSelect();
    	$select->order(array($major.' '.$dir, 'expense_date DESC'))
	    	->join('md_agent', 'expense_report_row.owner_id = md_agent.id', array(), 'left')
	    	->join('contact_vcard', 'md_agent.contact_id = contact_vcard.id', array('agent_n_fn' => 'n_fn'), 'left')
	    	->join('core_link', 'expense_report_row.document_id = core_link.id', array('name'), 'left')
	    	->where->in('expense_report_row.org_unit_id', $subSelect);

    	$mode = $this->params()->fromQuery('mode', 1);
    	if ($mode != 'total') $select->where(array('status' => 'Submitted'));
    	else $select->where(array('status <> ?' => 'New'));
    	 
    	// Execute the request
    	$cursor = ReportRow::getTable()->selectWith($select);
    	$reportRows = array();
    	$before_vat_sum = 0; $including_vat_sum = 0;
    	foreach ($cursor as $row) {
    		 
    		// Compute the amount for non-approved mileage allowances
    		if ($row->category == 'Mileage allowance' && ($row->status == 'New' || $row->status == 'Submitted' || $row->status == 'Rejected' )) {
    			$row->retrieveMileageProperties(date('Y'));
    		}
    		$including_vat_sum += $row->including_vat_amount;
    		$row->period_caption = ReportRow::$periodCaptions[substr($row->expense_date, 5, 2)];
    		$reportRows[] = $row;
    	}

    	$header = array(
    			'approver' => $context->getUsername(),
    			'before_vat_sum' => $before_vat_sum,
    			'including_vat_sum' => $including_vat_sum,
    	);

    	// Batch approbation
    	$message = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {

    		// Check the input values
    		$comment = trim(strip_tags($request->getPost('comment')));

    		$approve = $request->getPost('approve', 'No');

    		// Update the selected rows
    		$sumPerAgent = array();
    		foreach ($reportRows as $reportRow) {

    			if (!isset($sumPerAgent[$reportRow->owner_id])) $sumPerAgent[$reportRow->owner_id] = 0;
    			$sumPerAgent[$reportRow->owner_id] += $reportRow->capped_amount;

    			if ($request->getPost('row'.$reportRow->id)) { // Row selected

    				if ($approve != 'No') {
	    				$reportRow->approver_id = $context->user_id;
	    				$reportRow->status = 'Approved';
	    				$reportRow->approval_date = Date('Y-m-d');
	    				$reportRow->capped_amount = $reportRow->including_vat_amount;
	    				ReportRow::getTable()->save($reportRow);
	
			    		// Add the amount to the piloting event
			    		Event::update($reportRow->expense_date, $reportRow->capped_amount, 'expense_report_row');
    				}
    				else {

	    				$reportRow->approver_id = $context->user_id;
    					$reportRow->status = 'Rejected';
	    				$reportRow->approval_date = Date('Y-m-d');
		    			$reportRow->audit[] = array('status' => $reportRow->status, 'date' => Date('Y-m-d'), 'user' => $context->getUsername(), 'comment' => $comment);
		    			ReportRow::getTable()->save($reportRow);
    				}
    			}
    		}

    		// Send a mail to the agent
    		foreach ($sumPerAgent as $agent_id => $sum) {
    			$agent = Agent::getTable()->get($agent_id);
    			$vcard = Vcard::getTable()->get($agent->contact_id);
    			$user = User::getTable()->get($agent->contact_id, 'contact_id');
    			$config = $this->getServiceLocator()->get('config');
	    		$coreSettings = $config['ppitCoreSettings'];
	    		$emailTexts = $config['emailTexts'];
	    		$translator = $this->getServiceLocator()->get('translator');
	    		$url = $this->getServiceLocator()->get('viewhelpermanager')->get('url');
	    		Message::envoiMail(
		    		$vcard->email,
				    sprintf($translator->translate($emailTexts[($approve != 'No') ? 'Expense report registered' : 'Expense report rejected']['text'], 'ppit-expense', $currentUser->locale),
		    			$context->getUsername(),
			    		$user->formatFloat($sum, 2).' '.$context->getCurrencySymbol(),
		    			$coreSettings['domainName'].$url('ppitUser/login').'?redirect=reportRow'),
		    		$translator->translate($emailTexts[($approve != 'No') ? 'Expense report registered' : 'Expense report rejected']['title'], 'ppit-expense', $context->getLocale()));
    		}

    		$message = 'OK';
    	}

    	// Return the report list
    	return new ViewModel(array(
    			'context' => $context,
				'config' => $context->getconfig(),
    			'periods' => $periods,
    			'major' => $major,
    			'dir' => $dir,
    			'reportRows' => $reportRows,
    			'header' => $header,
    			'mode' => $mode,
    			'message' => $message,
    	));
    }

    public function approbeAction()
    {
    	// Check the presence of the id parameter for the entity to update
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if (!$id) {
    		return $this->redirect()->toRoute('index');
    	}

    	// Retrieve the context
    	$context = Context::getCurrent();
    
    	// Retrieve the object and control access
    	$reportRow = ReportRow::getTable()->get($id);
        // Compute the amount for non-approved mileage allowances
    	if ($reportRow->category == 'Mileage allowance') {
    		$reportRow->retrieveMileageProperties(date('Y'), $this->getMileageScaleTable());
       	}
    	
    	if (!$context->isAllowed('approbation/approbe', 'expense_report_row', 'org_unit_id', $reportRow->org_unit_id)) {
    		return $this->redirect()->toRoute('index');
    	}
    	$agent = Agent::getTable()->get($reportRow->owner_id);
    	$reportRow->agent_n_fn = Vcard::getTable()->get($agent->contact_id)->n_fn;
    	if ($reportRow->document_id) $reportRow->document_name = Link::getTable()->get($reportRow->document_id)->name;

    	// Compute the VAT and before VAT amount
    	$reportRow->exempted_amount = $reportRow->including_vat_amount - $reportRow->amount_vat_1 - $reportRow->amount_vat_2;
    	$reportRow->vat_1 = $reportRow->amount_vat_1 - round($reportRow->amount_vat_1 / (1 + $reportRow->vat_rate_1), 2);
    	$reportRow->vat_2 = $reportRow->amount_vat_2 - round($reportRow->amount_vat_2 / (1 + $reportRow->vat_rate_2), 2);
    	$reportRow->before_vat_amount = $reportRow->including_vat_amount - $reportRow->vat_1 - $reportRow->vat_2;

    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('csrf');

    	$error = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    		
    		if ($csrfForm->isValid()) { // CSRF check
    			 
    			// Check if no change has been applied on the database on this row since the transaction begin
    			if ($reportRow->status != 'Submitted') {
    		
    				$error = 'Isolation';
    			}
    			else {
		    		
		    		$approve = $request->getPost('approve', 'No');
		    		$emailTexts = $context->getConfig()['emailTexts'];
		    		$translator = $this->getServiceLocator()->get('translator');
		    		$url = $this->getServiceLocator()->get('viewhelpermanager')->get('url');
		    		$agent = Agent::getTable()->get($reportRow->owner_id);
		    		$vcard = Vcard::getTable()->get($agent->contact_id);
		    		$user = User::getTable()->get($agent->contact_id, 'contact_id');
		    		
		    		if ($approve != 'No') {

		    			// Double the client controls
		    			if ($request->getPost('capped_amount') <= 0 || $request->getPost('capped_amount') > $reportRow->including_vat_amount) {
		    				 
		    				throw new \Exception('Client error');
		    			}
		    			$reportRow->approver_id = $context->getUserId();
		    			$reportRow->status = 'Approved';
		    			$reportRow->approval_date = Date('Y-m-d');
		    			$reportRow->capped_amount = $request->getPost('capped_amount');
			    		$comment = trim(strip_tags($request->getPost('comment')));
		    			$reportRow->audit[] = array('status' => $reportRow->status, 'date' => Date('Y-m-d'), 'user' => $context->getUsername(), 'comment' => $comment);
		    			ReportRow::getTable()->save($reportRow);

		    			// Add the amount to the piloting event
		    			Event::update($reportRow->expense_date, $reportRow->capped_amount, 'expense_report_row');
		    		
			    		// Send a mail to the accountant on the organisational unit for this expense
			    		$toList = ContactMessage::getToList('accountant', 'expense_report_row', 'org_unit_id', $reportRow->org_unit_id);
			    		foreach ($toList as $addressee) {
			    			ContactMessage::sendMail(
				    			$addressee->email,
				    			sprintf($translator->translate($emailTexts['Expense report to be registered']['text'], 'ppit-expense', $context->getLocale()),
				    					$context->getUsername(),
										$context->getConfig()['ppitCoreSettings']['domainName'].$url('ppitUser/login').'?redirect=report/todo'),
						    	$translator->translate($emailTexts['Expense report to be registered']['title'], 'ppit-expense', $context->getLocale()));
			    		}

			    		// Send a mail to the agent
		    			ContactMessage::sendMail(
		    				$vcard->email,
						    sprintf($translator->translate($emailTexts['Expense report approved']['text'], 'ppit-expense', $context->getLocale()),
				    			$context->getUsername(),
					    		$user->formatFloat($reportRow->capped_amount, 2).' '.$context->getCurrencySymbol(),
				    			$context->getConfig()['ppitCoreSettings']['domainName'].$url('ppitUser/login').'?redirect=reportRow'),
				    		$translator->translate($emailTexts['Expense report approved']['title'], 'ppit-expense', $context->getLocale()));
		    		}
		    		else {

		    			$reportRow->status = 'Rejected';
		    			$reportRow->approval_date = null;
			    		$comment = trim(strip_tags($request->getPost('comment')));
		    			$reportRow->audit[] = array('status' => $reportRow->status, 'date' => Date('Y-m-d'), 'user' => $context->getUsername(), 'comment' => $comment);
		    			ReportRow::getTable()->save($reportRow);

			    		// Send a mail to the agent
		    			ContactMessage::sendMail(
			    			$vcard->email,
						    sprintf($translator->translate($emailTexts['Expense report rejected']['text'], 'ppit-expense', $currentUser->locale),
				    			$context->getUsername(),
						    	$user->formatFloat($reportRow->capped_amount, 2).' '.$context->getCurrencySymbol(),
				    			$context->getConfig()['ppitCoreSettings']['domainName'].$url('ppitUser/login').'?redirect=reportRow'),
				    		$translator->translate($emailTexts['Expense report rejected']['title'], 'ppit-expense', $context->getLocale()));
		    		}

		    		return $this->redirect()->toRoute('approbation');
    			}
    		}
    	}
    	return array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'reportRow' => $reportRow,
 	  		'csrfForm' => $csrfForm,
    		'id' => $id,
    		'error' => $error,
    	);
    }
}

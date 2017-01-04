<?php
namespace PpitExpense\Controller;

use PpitCore\Model\Csrf;
use PpitCore\Model\Context;
use PpitCore\Model\Link;
use PpitCore\Form\CsrfForm;
use PpitExpense\Model\ReportRow;
use PpitPil\Model\Event;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use DOMPDFModule\View\Model\PdfModel;
use Zend\Mvc\Controller\AbstractActionController;

class ReportController_old extends AbstractActionController
{
   	public function indexAction()
   	{
   		// Retrieve the context
   		$context = Context::getCurrent();

   		// Retrieve the available reports
    	$major = $this->params()->fromQuery('major', NULL);
    	if (!$major) $major = 'period';
    	$dir = $this->params()->fromQuery('dir', NULL);
    	if (!$dir) $dir = 'DESC';
   		
   		$select = ReportRow::getTable()->getSelect();
   		$select
   			->columns(array('period', 'owner_id'))
   			->join('md_agent', 'expense_report_row.owner_id = md_agent.id', array(), 'left')
			->join('core_vcard', 'md_agent.contact_id = core_vcard.id', array('agent_n_fn' => 'n_fn'), 'left')
   			->order(array($major.' '.$dir, 'period', 'owner_id'))
   			->quantifier(Select::QUANTIFIER_DISTINCT)
			->where->in('status', array('Approved', 'registered'));
   		$reports = ReportRow::getTable()->selectWith($select);

    	// Return the report list
    	return new ViewModel(array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'reports' => $reports,
    		'major' => $major,
    		'dir' => $dir,
    	));
   	}
   	
   	public function detailAction()
    {
        // Check the presence of the period and owner_id parameter
    	$period = $this->params()->fromRoute('period', 0);
    	if (!$period) {
    		return $this->redirect()->toRoute('index');
    	}
        $owner_id = $this->params()->fromRoute('owner_id', 0);
    	if (!$owner_id) {
    		return $this->redirect()->toRoute('index');
    	}
    	// Retrieve the context
    	$context = Context::getCurrent();

    	// Retrieve the agent
		$agent = Agent::getTable()->get($owner_id);
		$contact = Vcard::getTable()->get($agent->contact_id);
		$agent->n_fn = $contact->n_fn;		 
		
    	// Retrieve the rows
		$select = ReportRow::getTable()->getSelect();
    	$select
	    	->order(array('expense_date'))
	    	->join('core_link', 'expense_report_row.document_id = core_link.id', array('name'), 'left')
    		->where(array('expense_report_row.owner_id' => $owner_id, 'expense_report_row.period' => $period))
			->where->in('status', array('Approved', 'registered'));
    	$cursor = ReportRow::getTable()->selectWith($select);
    	$reportRows = array();

    	// Prepare the header
    	$capped_sum = 0; $transport_sum = 0; $invitation_sum = 0; $reception_sum = 0; $meal_sum = 0; $overnight_stay_sum = 0; $supply_sum = 0; $mileage_allowance_sum = 0; 
    	$including_vat_sum = 0; $exempted_sum = 0; $vat_1_sum = 0; $vat_2_sum = 0; $before_vat_sum = 0;
    	foreach ($cursor as $row) {
    		$row->agent_username = $context->getUsername();
    		$row->agent_n_fn = $context->getUsername();
    		$capped_sum += $row->capped_amount;
    		if ($row->category == 'Transport') $transport_sum += $row->capped_amount;
    		elseif ($row->category == 'Invitation') $invitation_sum += $row->capped_amount;
    		elseif ($row->category == 'Reception') $reception_sum += $row->capped_amount;
    		elseif ($row->category == 'Meal') $meal_sum += $row->capped_amount;
    		elseif ($row->category == 'Overnight stay') $overnight_stay_sum += $row->capped_amount;
    		elseif ($row->category == 'Supply') $supply_sum += $row->capped_amount;
    		elseif ($row->category == 'Mileage allowance') $mileage_allowance_sum += $row->capped_amount;
    		$including_vat_sum += $row->including_vat_amount;
    		$row->exempted_amount = $row->including_vat_amount - $row->amount_vat_1 - $row->amount_vat_2;
    		$exempted_sum += $row->exempted_amount;
//    		$row->vat_1 = $row->amount_vat_1 - round($row->amount_vat_1 / (1 + $row->vat_rate_1), 2);
//    		$row->vat_2 = $row->amount_vat_2 - round($row->amount_vat_2 / (1 + $row->vat_rate_2), 2);
    		$vat_1_sum += $row->amount_vat_1;
//    		$vat_2_sum += $row->vat_2;
    		$row->before_vat_amount = $row->including_vat_amount - $row->amount_vat_1/* - $row->vat_2*/;
    		$before_vat_sum += $row->before_vat_amount;
    		$reportRows[] = $row;
    	}

    	$pdf = new PdfModel();
    	$pdf->setOption('filename', 'report-row-'.$period);
    	$pdf->setOption("paperSize", "a4"); //Defaults to 8x11
 		$pdf->setOption("paperOrientation", "landscape"); //Defaults to portrait
     	$pdf->setVariables(array(
    		'context' => $context,
			'config' => $context->getconfig(),
     		'period' => $period,
    		'agent' => $agent,
    		'reportRows' => $reportRows,
    		'capped_sum' => $capped_sum,
    		'transport_sum' => $transport_sum,
    		'invitation_sum' => $invitation_sum,
    		'reception_sum' => $reception_sum,
     		'meal_sum' => $meal_sum,
    		'overnight_stay_sum' => $overnight_stay_sum,
    		'supply_sum' => $supply_sum,
    		'mileage_allowance_sum' => $mileage_allowance_sum,
     		'including_vat_sum' => $including_vat_sum,
    		'exempted_sum' => $exempted_sum,
    		'vat_1_sum' => $vat_1_sum,
    		'vat_2_sum' => $vat_2_sum,
    		'before_vat_sum' => $before_vat_sum,
      	));
    	return $pdf;
    }

    public function todoAction()
    {
    	// Retrieve the current user
    	$context = Context::getCurrent();
    
    	// Prepare the SQL request
    	$major = $this->params()->fromQuery('major', NULL);
    	if (!$major) $major = 'expense_date';
    	$dir = $this->params()->fromQuery('dir', NULL);
    	if (!$dir) $dir = 'DESC';
    
    	$subSelect = UserPerimeterLinker::getTable()->getSelect()
 		   	->columns(array())
    		->join('user_perimeter', 'user_perimeter_linker.perimeter_id = user_perimeter.id', array('value'), 'left')
    		->where(array('user_perimeter_linker.user_id' => $context->getUserId(), 'user_perimeter.entity' => 'expense_report_row', 'user_perimeter.column' => 'org_unit_id'));
    
    	$select = ReportRow::getTable()->getSelect();
    	$select->order(array($major.' '.$dir, 'expense_date DESC'))
	    	->join('user', 'expense_report_row.owner_id = user.user_id', array(), 'left')
    		->join('core_vcard', 'user.contact_id = core_vcard.id', array('agent_n_fn' => 'n_fn'), 'left')
    		->join('core_link', 'expense_report_row.document_id = core_link.id', array('name'), 'left')
    		->where(array('status' => 'Approved'))
    		->where->in('expense_report_row.org_unit_id', $subSelect);
    
    	// Execute the request
    	$cursor = ReportRow::getTable()->selectWith($select);
    	$reportRows = array();
    	$before_vat_sum = 0; $including_vat_sum = 0;
    	foreach ($cursor as $row) {
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
    		 
    		// Update the selected rows
    		$sumPerAgent = array();
    		foreach ($reportRows as $reportRow) {
    			
    			if (!isset($sumPerAgent[$reportRow->owner_id])) $sumPerAgent[$reportRow->owner_id] = 0;
    			$sumPerAgent[$reportRow->owner_id] += $reportRow->capped_amount;

    			if ($request->getPost('row'.$reportRow->id)) { // Row selected
    				 
    				$reportRow->status = 'Registered';
    				$reportRow->registration_date = Date('Y-m-d');
    				ReportRow::getTable()->save($reportRow);
    			}
    		}
    		// Send a mail to each agent
    		foreach ($sumPerAgent as $agent_id => $sum) {
    			$agent = Agent::getTable()->get($agent_id);
    			$vcard = Vcard::getTable()->get($agent->contact_id);
    			$user = User::getTable()->get($agent->contact_id, 'contact_id');
	    		$emailTexts = $context->getConfig()['emailTexts'];
	    		$translator = $this->getServiceLocator()->get('translator');
	    		$url = $this->getServiceLocator()->get('viewhelpermanager')->get('url');
	    		Message::sendMail(
		    		$context,
	    			$coreSettings,
	    			$vcard->email,
	    			sprintf($translator->translate($emailTexts['Expense report registered']['text'], 'ppit-expense', $context->getLocale()),
	  	  				$context->getUsername(),
			    		$context->formatFloat($sum, 2).' '.$context->getCurrencySymbol(),
	    				$coreSettings['domainName'].$url('ppitUser/login').'?redirect=reportRow'),
		    		$translator->translate($emailTexts['Expense report registered']['title'], 'ppit-expense', $context->getLocale()));
    		}

    		$message = 'OK';
    	}

    	// Return the report list
    	return new ViewModel(array(
    			'context' => $context,
				'config' => $context->getconfig(),
    			'major' => $major,
    			'dir' => $dir,
    			'reportRows' => $reportRows,
    			'header' => $header,
    			'message' => $message,
    	));
    }

    public function registerAction()
    {
    	// Check the presence of the id parameter for the entity to update
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if (!$id) {
    		return $this->redirect()->toRoute('index');
    	}
    
    	// Retrieve the current user
    	$context = Context::getCurrent();
    
    	// Retrieve the object and control access
    	$reportRow = ReportRow::getTable()->get($id);
    	if (!$context->isAllowed('approbation/register', 'expense_report_row', 'org_unit_id', $reportRow->org_unit_id)) {
    		return $this->redirect()->toRoute('index');
    	}
    	$agent = Agent::getTable()->get($reportRow->owner_id);
    	$reportRow->agent_n_fn = Vcard::getTable()->get($agent->contact_id)->n_fn;
    	if ($reportRow->document_id) $reportRow->document_name = Link::getTable()->get($reportRow->document_id)->name;
    
    	// Retrieve the approver
    	$approver = User::getTable()->get($reportRow->approver_id);
    	$vcard = Vcard::getTable()->get($approver->contact_id);
    	$reportRow->approver_n_fn = $vcard->n_fn;
    
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
    			if ($reportRow->status != 'Approved') {
    
    				$error = 'Isolation';
    			}
    			else {
        
    				$reportRow->status = 'Registered';
    				$reportRow->registration_date = Date('Y-m-d');
    				$comment = trim(strip_tags($request->getPost('comment')));
    				$reportRow->audit[] = array('status' => $reportRow->status, 'date' => Date('Y-m-d'), 'user' => $context->getUsername(), 'comment' => $comment);
    				ReportRow::getTable()->save($reportRow);
    
    				// Send a mail to the agent
		    		$emailTexts = $context->getConfig()['emailTexts'];
    				$translator = $this->getServiceLocator()->get('translator');
    				$url = $this->getServiceLocator()->get('viewhelpermanager')->get('url');
		    		$agent = Agent::getTable()->get($reportRow->owner_id);
		    		$vcard = Vcard::getTable()->get($agent->contact_id);
		    		$user = User::getTable()->get($agent->contact_id, 'contact_id');
    				Message::sendMail(
			    		$agent->email,
			    		sprintf($translator->translate($emailTexts['Expense report registered']['text'], 'ppit-expense', $context->getLocale()),
			    					$reportRow->agent_n_fn,
			    					$reportRow->capped_amount,
			    					$context->getConfig()['ppitCoreSettings']['domainName'].$url('ppitUser/login').'?redirect=reportRow'),
			    		$translator->translate($emailTexts['Expense report Registered']['title'], 'ppit-expense', $context->getLocale()));
    
    				return $this->redirect()->toRoute('report/todo');
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

    public function exportAction()
    {
    	// Retrieve the current user
    	$context = Context::getCurrent();
    
    	// Retrieve the rows
    	$major = $this->params()->fromQuery('major', NULL);
    	if (!$major) $major = 'expense_date';
    	$dir = $this->params()->fromQuery('dir', NULL);
    	if (!$dir) $dir = 'DESC';
    
    	$select = ReportRow::getTable()->getSelect();
    	$select
	    	->order(array($major.' '.$dir, 'expense_date DESC'))
    		->join('core_link', 'expense_report_row.document_id = core_link.id', array('name'), 'left')
    		->join('md_org_unit', 'expense_report_row.org_unit_id = md_org_unit.id', array('org_unit_caption' => 'caption'), 'left')
    		->join(array('agent' => 'md_agent'), 'expense_report_row.owner_id = agent.id', array(), 'left')
    		->join(array('agent_vcard' => 'core_vcard'), 'agent.contact_id = agent_vcard.id', array('agent_n_fn' => 'n_fn'), 'left')
    		->join(array('approver' => 'user'), 'expense_report_row.approver_id = approver.user_id', array(), 'left')
   		 	->join(array('approver_vcard' => 'core_vcard'), 'approver.contact_id = approver_vcard.id', array('approver_n_fn' => 'n_fn'), 'left')
   	 		->where(array('expense_report_row.status' => 'registered'));
    	$cursor = ReportRow::getTable()->selectWith($select);
    
    	// Compute the additional amounts
    	$reportRows = array();
    	$including_vat_sum = 0;
    	foreach ($cursor as $reportRow) {
    		 
    		// Compute the VAT and before VAT amount
    		$reportRow->exempted_amount = $reportRow->including_vat_amount - $reportRow->amount_vat_1 - $reportRow->amount_vat_2;
    		$reportRow->vat_1 = $reportRow->amount_vat_1 - round($reportRow->amount_vat_1 / (1 + $reportRow->vat_rate_1), 2);
    		$reportRow->vat_2 = $reportRow->amount_vat_2 - round($reportRow->amount_vat_2 / (1 + $reportRow->vat_rate_2), 2);
    		$reportRow->before_vat_amount = $reportRow->including_vat_amount - $reportRow->vat_1 - $reportRow->vat_2;
    		$reportRows[] = $reportRow;
    	}
    
    	// Return the report list
    	$view = new ViewModel(array(
    			'context' => $context,
				'config' => $context->getconfig(),
    			'reportRows' => $reportRows,
    	));
    	$view->setTerminal(true);
    	return $view;
    }
}

<?php
namespace PpitExpense\Controller;

use PpitCore\Model\Csrf;
use PpitCore\Model\Context;
use PpitCore\Model\Link;
use PpitCore\Form\CsrfForm;
use PpitExpense\Model\MileageScale;
use PpitExpense\Model\ReportRow;
use PpitPil\Model\Event;
use Zend\Db\Sql\Expression;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;

class ReportRowController extends AbstractActionController
{
   	public function indexAction()
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
	    	->order(array($major.' '.$dir, 'expense_date DESC', 'id DESC'))
	    	->join('core_link', 'expense_report_row.document_id = core_link.id', array('name'), 'left')
    		->where(array('expense_report_row.owner_id' => $context->getContactId()));
    	$cursor = ReportRow::getTable()->selectWith($select);
    	$reportRows = array();

    	// Prepare the header
    	$vatRates = array();
    	$capped_sum = 0;
    	foreach ($cursor as $row) {
    		$row->agent_username = $context->getUsername();
    		$row->agent_n_fn = $context->getUsername();
    		
    		// Compute the amount for non-approved mileage allowances
    		if ($row->category == 'Mileage allowance' && ($row->status == 'New' || $row->status == 'Submitted' || $row->status == 'Rejected' )) {
    			$row->retrieveMileageProperties(date('Y'));
       		}
    		if ($row->status != 'Registered' && $row->status != 'Rejected') $capped_sum += $row->capped_amount;
       		$reportRows[] = $row;
    	}
    	
    	// Set the header
    	$header = array(
    		'issuer' => $context->getUsername(),
    		'capped_sum' => $capped_sum,
    	);
    	
    	// Return the report list
    	$view = new ViewModel(array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'major' => $major,
    		'dir' => $dir,
    		'reportRows' => $reportRows,
    		'header' => $header,
        ));
   		if ($context->isSpaMode()) $view->setTerminal(true);
   		return $view;
    }

    public function addAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();

    	$id = (int) $this->params()->fromRoute('id', 0);
    	if ($id) $reportRow = ReportRow::getTable()->get($id);
    	else $reportRow = new ReportRow;

    	// Retrieve the parent link
    	$id = (int) $this->params()->fromRoute('id', null);
    	$parent = Link::getTable()->get($id, 'parent_id');

    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('csrf');
    	$message = null;
    	$error = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    		
    		if ($csrfForm->isValid()) { // CSRF check
		    		
	    		$reportRow = new ReportRow();
	    		$link = new Link();
	    		$nonFiles = $this->getRequest()->getPost()->toArray();
	    		$files = $this->getRequest()->getFiles()->toArray();
	    		// Pour ZF 2.2.x uniquement
	    		$data = array_merge_recursive(
	    				$nonFiles,
	    				$files
	    		);
				if ($files['name']['size'] > $context->getConfig()['ppitCoreSettings']['maxUploadSize']) $error = 'Size';
    			else {
    				$name = $files['name']['name'];
    				$extension = substr($name, strpos($name, '.')+1);
    				$type = $files['name']['type'];

    				// Write the link in the database
    				$link->id = NULL;
    				$session = new Container('context');
    				$link->owner_id = $context->getUserId();
    				$link->parent_id = $context->getFsRootId();
	    			if ($context->getConfig()['ppitCoreSettings']['compressGifPngToJpg'] && ($extension == 'gif' || $extension == 'png')) {
	    				$link->type = 'image/jpeg';
	    				$link->name = ((strpos($name, '.')) ? substr($name, 0, strpos($name, '.')) : $name).'.jpg';
	    			} else {
	    				$link->type = $type;
    					$link->name = $name;
	    			}
    				$link->uploaded_time = date("Y-m-d H:i:s");
    				$link->object_id = NULL;
    				$link->id = Link::getTable()->save($link);

    				$adapter = new \Zend\File\Transfer\Adapter\Http();
    				
    				if ($link->id) { // $link->id is 0 an demo mode
	    				// Create the file on the file system with $id as a name
	    				if (!$context->isDemoMode()) $adapter->addFilter('Rename', 'data/documents/'.$link->id);
	    				if ($adapter->receive($files['name']['name'])) {

	    					$src = 'data/documents/'.$link->id;
	    					$destination = 'data/documents/'.$link->id.'.jpg';

	    					if ($context->getConfig()['ppitCoreSettings']['compressGifPngToJpg'] && ($extension == 'gif' || $extension == 'png')) {
		    					// Compress the image
							    $info = getimagesize($src);
	    						if ($info['mime'] == 'image/gif')
		    					{
		    						$image = imagecreatefromgif($src);
		    					}
		    					elseif ($info['mime'] == 'image/png')
		    					{
		    						$image = imageCreateFromPng($src);
		    					}

		    					//compress and save file to jpg
		    					imagejpeg($image, $destination, 75);
		    					unlink('data/documents/'.$link->id);
		    					rename('data/documents/'.$link->id.'.jpg', 'data/documents/'.$link->id);
	    					}
	    					
	    					// Write the report in the database
	    					$reportRow->id = NULL;
	    					$reportRow->owner_id = $context->agent_id;
	    					$reportRow->org_unit_id = $currentUser->org_unit_id;
	    					$reportRow->status = 'New';
	    					$reportRow->period = date('Y-m');
	    					$reportRow->expense_date = date('Y-m-d');
	    					$reportRow->document_id = $link->id;
	    					$reportRow->id = ReportRow::getTable()->save($reportRow);

	    					// Redirect to update
	    					return $this->redirect()->toRoute('reportRow/update', array('id' => $reportRow->id));
	    				}
    				}
    				else {
    					// Redirect to update
    					return $this->redirect()->toRoute('reportRow/update', array('id' => $reportRow->id));
       				}
    			}
    		}
    	}

    	$view = new ViewModel(array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'csrfForm' => $csrfForm,
    		'message' => $message,
    		'error' => $error,
    		'reportRow' => $reportRow,
    	));
   		if ($context->isSpaMode()) $view->setTerminal(true);
   		return $view;
    }

    public function mileageAction()
    {
    	// Retrieve the current user
    	$context = Context::getCurrent();
    
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if ($id) {
    		$reportRow = ReportRow::getTable()->get($id);
    	}
    	else {
    		$reportRow = new ReportRow;
    		
    		// Retrieve the last declared horsepower if exists
    		$select = ReportRow::getTable()->getSelect()
    			->columns(array('id' => new Expression('MAX(id)')))
    			->where(array('category' => 'Mileage allowance', 'owner_id' => $context->agent_id));
    		$cursor = ReportRow::getTable()->selectWith($select);
    		if ($cursor->current()->id) {
    			$recent = ReportRow::getTable()->get($cursor->current()->id);
    			$reportRow->horsepower = $recent->horsepower;
    		}
    	}
    	$reportRow->retrieveMileageProperties(date('Y'));

    	// Instanciate the csrf form
    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('csrf');
    	$error = null;
    	$message = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    		 
    		if ($csrfForm->isValid()) { // CSRF check
    
    			// Double the client controls
    			$reportRow->loadMileageData($request);
    			$reportRow->retrieveMileageProperties(date('Y'));

    			// Atomically save
    			$connection = ReportRow::getTable()->getAdapter()->getDriver()->getConnection();
    			$connection->beginTransaction();
    			try {
    				ReportRow::getTable()->save($reportRow);
    
    				$connection->commit();
    
    				$message = 'OK';
    			}
    			catch (\Exception $e) {
    				$connection->rollback();
    				throw $e;
    			}
    				    		
	    		// Retrieve the approvers on the bill unit for this expense
	    		$toList = ContactMessage::getToList('approver', 'expense_report_row', 'org_unit_id', $reportRow->org_unit_id);
	    		$emailTexts = $context->getConfig()['emailTexts'];
	    		$translator = $this->getServiceLocator()->get('translator');
	    		$url = $this->getServiceLocator()->get('viewhelpermanager')->get('url');
	    		foreach ($toList as $addressee) {
	    			ContactMessage::envoiMail(
		    			$addressee->email,
		    			sprintf($translator->translate($emailTexts['Expense report submitted']['text'], 'ppit-expense', $context->getLocale()),
		    						$context->getUsername(),
		    						$coreSettings['domainName'].$url('ppitUser/login').'?redirect=approbation'),
		    			$translator->translate($emailTexts['Expense report submitted']['title'], 'ppit-expense', $context->getLocale()));
	    		}
    		}
    	}
    	$view = new ViewModel(array(
    			'context' => $context,
				'config' => $context->getconfig(),
    			'id' => $id,
    			'reportRow' => $reportRow,
    			'csrfForm' => $csrfForm,
    			'error' => $error,
    			'message' => $message
    	));
   		if ($context->isSpaMode()) $view->setTerminal(true);
   		return $view;
    }

    public function detailAction()
    {
    	// Check the presence of the id parameter for the entity to update
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if (!$id) {
    		return $this->redirect()->toRoute('index');
    	}
    	// Retrieve the current user
    	$context = Context::getCurrent();
 
    	// Retrieve the report row and control access
    	$reportRow = ReportRow::getTable()->get($id);
    	if ($reportRow->owner_id != $currentUser->getAgentId() &&
    		!$context->isAllowed('reportRow/detail', 'expense_report_row', 'org_unit_id', $reportRow->org_unit_id)) {
    		return $this->redirect()->toRoute('index');
    	}

    	// Compute the amount for non-approved mileage allowances
    	if ($reportRow->category == 'Mileage allowance' && ($reportRow->status == 'New' || $reportRow->status == 'Submitted' || $reportRow->status == 'Rejected' )) {
    		$reportRow->retrieveMileageProperties(date('Y'));
    	}

    	$agent = Agent::getTable()->get($reportRow->owner_id);
    	$reportRow->agent_n_fn = Vcard::getTable()->get($agent->contact_id)->n_fn;
    	if ($reportRow->document_id) $reportRow->document_name = Link::getTable()->get($reportRow->document_id)->name;

    	// Retrieve the approver
    	if ($reportRow->approver_id) {
	    	$approver = User::getTable()->get($reportRow->approver_id);
	    	$vcard = Vcard::getTable()->get($approver->contact_id);
	    	$reportRow->approver_n_fn = $vcard->n_fn;
    	}
    	 
    	// Compute the VAT and before VAT amount
    	$reportRow->exempted_amount = $reportRow->including_vat_amount - $reportRow->amount_vat_1 - $reportRow->amount_vat_2;
    	$reportRow->vat_1 = $reportRow->amount_vat_1 - round($reportRow->amount_vat_1 / (1 + $reportRow->vat_rate_1), 2);
    	$reportRow->vat_2 = $reportRow->amount_vat_2 - round($reportRow->amount_vat_2 / (1 + $reportRow->vat_rate_2), 2);
    	$reportRow->before_vat_amount = $reportRow->including_vat_amount - $reportRow->vat_1 - $reportRow->vat_2;

    	$view = new ViewModel(array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'reportRow' => $reportRow,
    		'returnUrl' => $this->params()->fromQuery('ctrl', 'reportRow'),
    	));
   		if ($context->isSpaMode()) $view->setTerminal(true);
   		return $view;
    }
    	 
    public function updateAction()
    {
    	// Check the presence of the id parameter for the entity to update
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if (!$id) {
    		return $this->redirect()->toRoute('index');
    	}
    	// Retrieve the current user
    	$context = Context::getCurrent();

    	// Retrieve the report row and control access
    	$reportRow = ReportRow::getTable()->get($id);
    	if ($reportRow->owner_id != $context->getAgentId()) {
    		return $this->redirect()->toRoute('index');
    	}
    	$agent = Agent::getTable()->get($reportRow->owner_id);
    	$reportRow->agent_n_fn = Vcard::getTable()->get($agent->contact_id)->n_fn;
    	$reportRow->document_name = Link::getTable()->get($reportRow->document_id)->name;
    	if ($reportRow->including_vat_amount == 0) $reportRow->including_vat_amount = '';

    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('csrf');

    	$error = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    		
    		if ($csrfForm->isValid()) { // CSRF check
	    		
	    		// Check if no change has been applied on the database on this row since the transaction begin
	    		if ($reportRow->expense_date != $request->getPost('db_expense_date') ||
	    			$reportRow->category != $request->getPost('db_category') ||
	    			$reportRow->including_vat_amount != $request->getPost('db_including_vat_amount')) {
	
					$error = 'Isolation';
	    		}
	    		else {

	    			$reportRow->expense_date = $request->getPost('expense_date');
		    		$reportRow->period = substr($reportRow->expense_date, 0, 7);
	    			$reportRow->category = $request->getPost('category');
	    			$reportRow->destination = $request->getPost('destination');
	    			$reportRow->justification = $request->getPost('justification');
	    			$reportRow->including_vat_amount = $request->getPost('including_vat_amount');
//					$i = 0;
//					foreach ($settings['vatRates'] as $rate => $caption) {
//						$i++;
//						if ($i == 1) {
//							$reportRow->vat_rate_1 = $rate;
							$reportRow->amount_vat_1 = $request->getPost('amount_');
/*						}
						else {
							$reportRow->vat_rate_2 = $rate;
							$reportRow->amount_vat_2 = $request->getPost('amount_2');
						}*/
//					}
					$reportRow->capped_amount = $reportRow->including_vat_amount;
		    		$reportRow->status = 'Submitted';
		    		$comment = trim(strip_tags($request->getPost('comment')));
		    		$reportRow->audit[] = array('status' => (($reportRow->status == 'Submitted') ? 'Corrected' : $reportRow->status), 'date' => Date('Y-m-d'), 'user' => $context->getUsername(), 'comment' => $comment);
		    		ReportRow::getTable()->save($reportRow);
		    		
		    		// Retrieve the approvers on the bill unit for this expense
		    		$toList = ContactMessage::getToList('approver', 'expense_report_row', 'org_unit_id', $reportRow->org_unit_id);
		    		$emailTexts = $context->getConfig()['emailTexts'];
		    		$translator = $this->getServiceLocator()->get('translator');
		    		$url = $this->getServiceLocator()->get('viewhelpermanager')->get('url');
		    		foreach ($toList as $addressee) {
		    			Message::sendMail(
			    			$addressee->email,
			    			sprintf($translator->translate($emailTexts['Expense report submitted']['text'], 'ppit-expense', $context->getLocale()),
			    						$context->getUsername(),
			    						$context->getConfig()['ppitCoreSettings']['domainName'].$url('ppitUser/login').'?redirect=approbation'),
			    			$translator->translate($emailTexts['Expense report submitted']['title'], 'ppit-expense', $context->getLocale()));
		    		}
		    		
		    		// Redirect
				   	return $this->redirect()->toRoute('reportRow');
	    		}
    		}
       	}
    	$view = new ViewModel(array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'reportRow' => $reportRow,
 	  		'csrfForm' => $csrfForm,
    		'id' => $id,
    		'error' => $error
    	));
   		if ($context->isSpaMode()) $view->setTerminal(true);
   		return $view;
    }

    public function deleteAction()
    {
    	// Check the presence of the id parameter for the entity to delete
    	$id = (int) $this->params()->fromRoute('id', 0);
    	if (!$id) {
    		return $this->redirect()->toRoute('index');
    	}
    	// Retrieve the current user
    	$context = Context::getCurrent();
    
    	// Retrieve the report and control access
    	$reportRow = ReportRow::getTable()->get($id);
    	if ($reportRow->owner_id != $context->getAgentId()) {
    		return $this->redirect()->toRoute('index');
    	}

    	$agent = Agent::getTable()->get($reportRow->owner_id);
    	$reportRow->agent_n_fn = Vcard::getTable()->get($agent->contact_id)->n_fn;
    	if ($reportRow->document_id) $reportRow->document_name = $context->getLinkTable()->get($reportRow->document_id)->name;

    	// Compute the VAT and before VAT amount
    	$reportRow->exempted_amount = $reportRow->including_vat_amount - $reportRow->amount_vat_1 - $reportRow->amount_vat_2;
    	$reportRow->vat_1 = $reportRow->amount_vat_1 - round($reportRow->amount_vat_1 / (1 + $reportRow->vat_rate_1), 2);
    	$reportRow->vat_2 = $reportRow->amount_vat_2 - round($reportRow->amount_vat_2 / (1 + $reportRow->vat_rate_2), 2);
    	$reportRow->before_vat_amount = $reportRow->including_vat_amount - $reportRow->vat_1 - $reportRow->vat_2;

    	$csrfForm = new CsrfForm();
    	$csrfForm->addCsrfElement('expense_update_csrf');
    	
    	$error = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$csrfForm->setInputFilter((new Csrf('expense_update_csrf'))->getInputFilter());
    		$csrfForm->setData($request->getPost());
    		
    		if ($csrfForm->isValid()) { // CSRF check
    			 
    			// Check if no change has been applied on the database on this row since the transaction begin
    			if ($reportRow->expense_date != $request->getPost('db_expense_date') ||
    				$reportRow->category != $request->getPost('db_category') ||
    				$reportRow->including_vat_amount != $request->getPost('db_including_vat_amount')) {
    		
    				$error = 'Isolation';
    			}
    			else {
				    		
			    	// Delete the document and the report row
			    	if ($reportRow->document_id) Link::getTable()->delete($reportRow->document_id);
			   		ReportRow::getTable()->delete($id);

			   		// Delete the file on the file system
			   		if ($reportRow->document_id) unlink($file = 'data/documents/'.$reportRow->document_id);
			   		
			   		// Redirect
			   		return $this->redirect()->toRoute('reportRow');
			   	}
    		}	
    	}

    	$view = new ViewModel(array(
    		'context' => $context,
			'config' => $context->getconfig(),
    		'reportRow' => $reportRow,
 	  		'csrfForm' => $csrfForm,
    		'id' => $id,
    		'error' => $error
    	));
   		if ($context->isSpaMode()) $view->setTerminal(true);
   		return $view;
    }
}

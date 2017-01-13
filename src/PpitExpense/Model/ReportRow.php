<?php
namespace PpitExpense\Model;

use PpitAccounting\Model\Journal;
use PpitCore\Model\Context;
use PpitCore\Model\Generic;
use PpitExpense\Model\MileageScale;
use Zend\db\sql\Where;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class ReportRow implements InputFilterAwareInterface
{
    public $id;
    public $owner_id;
    public $org_unit_id;
    public $approver_id;
    public $expense_date;
    public $issue_date;
    public $approval_date;
    public $registration_date;
    public $status;
    public $category;
    public $caption;
    public $destination;
    public $justification;
    public $horsepower;
    public $quantity;
    public $document;
    public $tax_inclusive;
    public $tax_amount;
    public $capped_amount;
    public $non_deductible;
    public $audit;
    public $update_time;
    
	// Transient fields
	public $horsepowers;
	public $mileageScales;
	public $properties;

    // Static fields
    public static $periodCaptions = array(
    	'01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sept', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'	
    );
    private static $table;
    
    protected $inputFilter;
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->owner_id = (isset($data['owner_id'])) ? $data['owner_id'] : null;
        $this->org_unit_id = (isset($data['org_unit_id'])) ? $data['org_unit_id'] : null;
        $this->approver_id = (isset($data['approver_id'])) ? $data['approver_id'] : null;
        $this->expense_date = (isset($data['expense_date'])) ? $data['expense_date'] : null;
        $this->issue_date = (isset($data['issue_date'])) ? $data['issue_date'] : null;
        $this->approval_date = (isset($data['approval_date'])) ? $data['approval_date'] : null;
        $this->registration_date = (isset($data['registration_date'])) ? $data['registration_date'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->category = (isset($data['category'])) ? $data['category'] : null;
        $this->caption = (isset($data['caption'])) ? $data['caption'] : null;
        $this->destination = (isset($data['destination'])) ? $data['destination'] : null;
        $this->justification = (isset($data['justification'])) ? $data['justification'] : null;
        $this->horsepower = (isset($data['horsepower'])) ? $data['horsepower'] : null;
        $this->quantity = (isset($data['quantity'])) ? $data['quantity'] : null;
        $this->document = (isset($data['document'])) ? $data['document'] : null;
        $this->tax_inclusive = (isset($data['tax_inclusive'])) ? $data['tax_inclusive'] : null;
        $this->tax_amount = (isset($data['tax_amount'])) ? $data['tax_amount'] : null;
        $this->capped_amount = (isset($data['capped_amount'])) ? $data['capped_amount'] : null;
        $this->non_deductible = (isset($data['non_deductible'])) ? $data['non_deductible'] : null;
        $this->audit = (isset($data['audit'])) ? json_decode($data['audit'], true) : null;
    }

    public function toArray()
    {
    	$data = array();
    	$data['id'] = (int) $this->id;
    	$data['owner_id'] = (int) $this->owner_id;
    	$data['org_unit_id'] = (int) $this->org_unit_id;
    	$data['approver_id'] = (int) $this->approver_id;
    	$data['expense_date'] = $this->expense_date;
    	$data['issue_date'] = ($this->issue_date) ? $this->issue_date : null;
    	$data['approval_date'] = ($this->approval_date) ? $this->approval_date : null;
    	$data['registration_date'] = ($this->registration_date) ? $this->registration_date : null;
    	$data['status'] = $this->status;
    	$data['category'] = $this->category;
    	$data['caption'] = $this->caption;
    	$data['destination'] = $this->destination;
    	$data['justification'] = $this->justification;
    	$data['horsepower'] = $this->horsepower;
    	$data['quantity'] = $this->quantity;
    	$data['document'] = $this->document;
    	$data['tax_inclusive'] = $this->tax_inclusive;
    	$data['tax_amount'] = $this->tax_amount;
    	$data['capped_amount'] = $this->capped_amount;
    	$data['non_deductible'] = $this->non_deductible;
    	$data['audit'] = json_encode($this->audit);
    	$data['update_time'] = $this->update_time;
    	return $data;
    }

    public static function getList($params, $major, $dir, $mode = 'todo')
    {
    	$context = Context::getCurrent();
    
    	$select = ReportRow::getTable()->getSelect()
	    	->order(array($major.' '.$dir, 'expense_date', 'tax_inclusive DESC'));
    	$where = new Where;
    	$where->notEqualTo('expense_report_row.status', 'deleted');
    
    	// Todo list vs search modes
    	if ($mode == 'todo') {
    		$where->notEqualTo('expense_report_row.status', 'recorded');
    	}
    	else {
    		// Set the filters
    		foreach ($params as $propertyId => $property) {
				if (substr($propertyId, 0, 4) == 'min_') $where->greaterThanOrEqualTo('expense_report_row.'.substr($propertyId, 4), $params[$propertyId]);
    			elseif (substr($propertyId, 0, 4) == 'max_') $where->lessThanOrEqualTo('expense_report_row.'.substr($propertyId, 4), $params[$propertyId]);
    			else $where->like('expense_report_row.'.$propertyId, '%'.$params[$propertyId].'%');
    		}
    	}
    	$select->where($where);
    	$cursor = ReportRow::getTable()->selectWith($select);
    	$expenses = array();
    
    	foreach ($cursor as $expense) {
    		if (!$expense->isUpdatable()) $expense->status = 'recorded';
    		$expense->properties = $expense->toArray('flat');
    		$expenses[] = $expense;
    	}
    	return $expenses;
    }
    
    public static function get($id, $column = 'id')
    {
    	$expense = ReportRow::getTable()->get($id, $column);
    	if (!$expense) return null;
    	$expense->properties = $expense->toArray('flat');
    	return $expense;
    }
    
    public static function instanciate()
    {
    	$expense = new ReportRow;
    	$expense->status = 'new';
    	$expense->non_deductible = 1;
    	$expense->audit = array();
    	return $expense;
    }
    
    public function loadData($data) {
    
    	$context = Context::getCurrent();
    
    	if (array_key_exists('status', $data)) {
    		$this->status = trim(strip_tags($data['status']));
    		if (!$this->status || strlen($this->status) > 255) return 'Integrity';
    	}
    	if (array_key_exists('owner_id', $data)) $this->owner_id = (int) $data['owner_id'];
    	if (array_key_exists('org_unit_id', $data)) $this->org_unit_id = (int) $data['org_unit_id'];
    	if (array_key_exists('approver_id', $data)) $this->approver_id = (int) $data['approver_id'];
    	if (array_key_exists('expense_date', $data)) {
    		$this->expense_date = trim(strip_tags($data['expense_date']));
    		if ($this->expense_date && !checkdate(substr($this->expense_date, 5, 2), substr($this->expense_date, 8, 2), substr($this->expense_date, 0, 4))) return 'Integrity';
    	}
    	if (array_key_exists('approval_date', $data)) {
    		$this->approval_date = trim(strip_tags($data['approval_date']));
    		if ($this->approval_date && !checkdate(substr($this->approval_date, 5, 2), substr($this->approval_date, 8, 2), substr($this->approval_date, 0, 4))) return 'Integrity';
    	}
        if (array_key_exists('registration_date', $data)) {
    		$this->registration_date = trim(strip_tags($data['registration_date']));
    		if ($this->registration_date && !checkdate(substr($this->registration_date, 5, 2), substr($this->registration_date, 8, 2), substr($this->registration_date, 0, 4))) return 'Integrity';
    	}
        if (array_key_exists('category', $data)) {
    		$this->category = trim(strip_tags($data['category']));
    		if (!$this->category || strlen($this->category) > 255) return 'Integrity';
    	}
        if (array_key_exists('caption', $data)) {
    		$this->caption = trim(strip_tags($data['caption']));
    		if (strlen($this->caption) > 255) return 'Integrity';
    	}
    	if (array_key_exists('destination', $data)) {
    		$this->destination = trim(strip_tags($data['destination']));
    		if (strlen($this->destination) > 255) return 'Integrity';
    	}
        if (array_key_exists('justification', $data)) {
    		$this->justification = trim(strip_tags($data['justification']));
    		if (strlen($this->justification) > 255) return 'Integrity';
    	}
        if (array_key_exists('horsepower', $data)) {
    		$this->horsepower = trim(strip_tags($data['horsepower']));
    		if (strlen($this->horsepower) > 255) return 'Integrity';
    	}
        if (array_key_exists('quantity', $data)) $this->quantity = (float) $data['quantity'];
        if (array_key_exists('document', $data)) {
    		$this->document = trim(strip_tags($data['document']));
    		if (strlen($this->document) > 255) return 'Integrity';
    	}
        if (array_key_exists('tax_inclusive', $data)) $this->tax_inclusive = (float) $data['tax_inclusive'];
        if (array_key_exists('tax_amount', $data)) $this->tax_amount = (float) $data['tax_amount'];
        if (array_key_exists('capped_amount', $data)) $this->capped_amount = (float) $data['capped_amount'];
        if (array_key_exists('non_deductible', $data)) $this->non_deductible = (int) $data['non_deductible'];
        if (array_key_exists('update_time', $data)) $this->update_time = $data['update_time'];
    	$this->properties = $this->toArray();
    
    	// Update the audit
    	$this->audit[] = array(
    			'time' => Date('Y-m-d G:i:s'),
    			'n_fn' => $context->getFormatedName(),
    	);
    
    	return 'OK';
    }

    public function retrieveMileageProperties($year)
    {
    	$context = Context::getCurrent();
    	if (!$this->owner_id) $this->owner_id = $context->getContactId();
    	//    	if (!$this->org_unit_id) $this->org_unit_id = $context->getOrgUnitId();
    
    	// Store the mileage scale in a 2-dim array
    	$select = MileageScale::getTable()->getSelect()
    	->where(array('year' => $year, 'category' => 'Mileage allowance'))
    	->order(array('horsepower', 'annual_stage'));
    	$ms_cursor = MileageScale::getTable()->selectWith($select);
    	$mileageScales = array();
    	$this->horsepowers = array();
    	$horsepower = null;
    	foreach ($ms_cursor as $scale) {
    		if ($scale->horsepower != $horsepower) {
    			$horsepower = $scale->horsepower;
    			$this->horsepowers[] = $horsepower;
    			$mileageScales[$horsepower] = array();
    			$stages = array();
    			$sum = 0;
    		}
    		$sum += $scale->annual_stage;
    		$stages[] = $sum;
    		$scale->annual_sum = 0;
    		$mileageScales[$horsepower][$sum] = $scale;
    	}
    
    	// Retrieve the annual cumul for the agent
    	$select = ReportRow::getTable()->getSelect();
    	$where = new Where();
    	$where->equalTo('category', 'Mileage allowance');
    	$where->equalTo('owner_id', $this->owner_id);
    	$where->like('period', date('Y').'%');
    	$select->where($where);
    	$rr_cursor = ReportRow::getTable()->selectWith($select);
    	$annual_sum = 0;
    	$i = 0;
    	$currentStage = $stages[$i];
    	foreach ($rr_cursor as $row) {
    		$scale = $mileageScales[$row->horsepower][$currentStage];
    		if ($annual_sum + $row->quantity > $currentStage) {
    			$firstBracket = $currentStage - $annual_sum;
    			$row->quantity -= $firstBracket;
    			$annual_sum += $firstBracket;
    			$scale->annual_sum = $scale->annual_stage;
    			if ($this->id == $row->id) {
    				$this->capped_amount += $firstBracket * $scale->variable_scale;
    			}
    
    			$currentStage = $stages[++$i];
    			$scale = $mileageScales[$row->horsepower][$currentStage];
    		}
    		$annual_sum += $row->quantity;
    		$scale->annual_sum += $row->quantity;
    		if ($this->id == $row->id) {
    			$this->capped_amount = $row->quantity * $scale->variable_scale;
    		}
    	}
    	$this->mileageScales = $mileageScales;
    	$this->including_vat_amount = $this->capped_amount;
    }
    
    public function loadMileageData($request) {
    	$this->expense_date = $request->getPost('expense_date');
    	$this->horsepower = $request->getPost('horsepower');
    	$this->quantity = (float) $request->getPost('quantity');
    	$this->destination = $request->getPost('destination');
    	$this->justification = $request->getPost('justification');
    
    	if (!$this->expense_date
    			||	!$this->horsepower
    			||	!$this->quantity
    			||	!$this->destination
    			||	!$this->justification) {
    				throw new \Exception('View error');
    			}
    
    			$this->owner_id = $context->getAgentId();
    			$this->org_unit_id = $context->getOrgUnitId();
    			$this->status = 'Submitted';
    			$this->category = 'Mileage allowance';
    			$this->period = date('Y-m');
    			$this->expense_date = date('Y-m-d');
    }
    
    public function register($update_time)
    {
    	$context = Context::getCurrent();
    	$accountingChart = $context->getConfig('journal/accountingChart/expense')[$this->category];

    	$journalEntry = Journal::instanciate();
    	$data = array();
		$data['operation_date'] = $this->expense_date;
		$data['reference'] = $this->justification;
		$data['caption'] = $this->caption;
		$data['expense_id'] = $this->id;
		$data['proof_url'] = $this->document;
		$data['rows'] = array();
		foreach ($accountingChart as $account => $rule) {
			if ($rule['source'] == 'excluding_tax') $amount = $this->tax_inclusive - $this->tax_amount;
			else $amount = $this->properties[$rule['source']];
			if ($amount > 0) {
				$row = array();
				if ($this->non_deductible && $rule['source'] == 'tax_amount') {
					foreach ($accountingChart as $account2 => $rule2) {
						if ($rule2['source'] == 'excluding_tax') $row['account'] = $account2;
						break;
					}
				}
				else $row['account'] = $account;
				$row['direction'] = $rule['direction'];
				$row['amount'] = $amount;
				$data['rows'][] = $row;
			}
		}
		$journalEntry->loadData($data);
		if ($this->id) Journal::getTable()->multipleDelete(array('expense_id' => $this->id));
		$journalEntry->add();
    }
    
    public function add()
    {
    	$context = Context::getCurrent();
    	$this->id = null;
    	ReportRow::getTable()->save($this);
    	return ('OK');
    }
    
    public function update($update_time)
    {
    	$context = Context::getCurrent();
    	$expense = ReportRow::get($this->id);
    
    	// Isolation check
    	if ($update_time && $expense->update_time > $update_time) return 'Isolation';
    	ReportRow::getTable()->save($this);
    	return 'OK';
    }

    public function isUpdatable()
    {
		$select = Journal::getTable()->getSelect()->where(array('expense_id' => $this->id, 'status' => 'registered'));
		$cursor = Journal::getTable()->selectWith($select);
		if (count($cursor) > 0) return false;
    	return true;
    }
    
    public function isDeletable()
    {
    	return true;
    }
    
    public function delete($update_time)
    {
    	$context = Context::getCurrent();
    	$expense = ReportRow::get($this->id);
    
    	// Isolation check
    	if ($update_time && $expense->update_time > $update_time) return 'Isolation';
    	if ($this->id) Journal::getTable()->multipleDelete(array('expense_id' => $this->id));
    	$this->status = 'deleted';
    	ReportRow::getTable()->save($this);
    
    	return 'OK';
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }

    public function getInputFilter()
    {
        throw new \Exception("Not used");
    }

    public static function getTable()
    {
    	if (!ReportRow::$table) {
    		$sm = Context::getCurrent()->getServiceManager();
    		ReportRow::$table = $sm->get('PpitExpense\Model\ReportRowTable');
    	}
    	return ReportRow::$table;
    }
}

<?php
namespace PpitExpense\Model;

use PpitCore\Model\Context;
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
    public $period;
    public $expense_date;
    public $issue_date;
    public $approval_date;
    public $registration_date;
    public $status;
    public $category;
    public $destination;
    public $justification;
    public $horsepower;
    public $quantity;
    public $document_id;
    public $including_vat_amount;
    public $vat_rate_1;
    public $amount_vat_1;
    public $vat_rate_2;
    public $amount_vat_2;
    public $capped_amount;
    public $audit;
    
    // Additional fields
    public $org_unit_caption;
    public $agent_n_fn;
    public $approver_n_fn;
	public $document_name;
	public $currency;
	public $exempted_amount;
	public $vat_1;
	public $vat_2;
	public $before_vat_mount;
	public $name;

	// Transient fields
	public $period_caption;
	public $horsepowers;
	public $mileageScales;

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
        $this->period = (isset($data['period'])) ? $data['period'] : null;
        $this->expense_date = (isset($data['expense_date'])) ? $data['expense_date'] : null;
        $this->issue_date = (isset($data['issue_date'])) ? $data['issue_date'] : null;
        $this->approval_date = (isset($data['approval_date'])) ? $data['approval_date'] : null;
        $this->registration_date = (isset($data['registration_date'])) ? $data['registration_date'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->category = (isset($data['category'])) ? $data['category'] : null;
        $this->destination = (isset($data['destination'])) ? $data['destination'] : null;
        $this->justification = (isset($data['justification'])) ? $data['justification'] : null;
        $this->horsepower = (isset($data['horsepower'])) ? $data['horsepower'] : null;
        $this->quantity = (isset($data['quantity'])) ? $data['quantity'] : null;
        $this->document_id = (isset($data['document_id'])) ? $data['document_id'] : null;
        $this->including_vat_amount = (isset($data['including_vat_amount'])) ? $data['including_vat_amount'] : null;
        $this->vat_rate_1 = (isset($data['vat_rate_1'])) ? $data['vat_rate_1'] : null;
        $this->amount_vat_1 = (isset($data['amount_vat_1'])) ? $data['amount_vat_1'] : null;
        $this->vat_rate_2 = (isset($data['vat_rate_2'])) ? $data['vat_rate_2'] : null;
        $this->amount_vat_2 = (isset($data['amount_vat_2'])) ? $data['amount_vat_2'] : null;
        $this->capped_amount = (isset($data['capped_amount'])) ? $data['capped_amount'] : null;
        $this->audit = (isset($data['audit'])) ? ((json_decode($data['audit'])) ? json_decode($data['audit']) : array()) : null;
        
    	// Joined table columns and computed values
        $this->org_unit_caption = (isset($data['org_unit_caption'])) ? $data['org_unit_caption'] : null;
        $this->agent_n_fn = (isset($data['agent_n_fn'])) ? $data['agent_n_fn'] : null;
        $this->approver_n_fn = (isset($data['approver_n_fn'])) ? $data['approver_n_fn'] : null;
        $this->document_name = (isset($data['document_name'])) ? $data['document_name'] : null;
        $this->currency = '€';
        $this->exempted_amount = (isset($data['exempted_amount'])) ? $data['exempted_amount'] : null;
        $this->vat_1 = (isset($data['vat_1'])) ? $data['vat_1'] : null;
        $this->vat_2 = (isset($data['vat_2'])) ? $data['vat_2'] : null;
        $this->before_vat_amount = (isset($data['before_vat_amount'])) ? $data['before_vat_amount'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
    }

    public function toArray()
    {
    	$data = array();
    	$data['id'] = (int) $this->id;
    	$data['owner_id'] = (int) $this->owner_id;
    	$data['org_unit_id'] = (int) $this->org_unit_id;
    	$data['approver_id'] = (int) $this->approver_id;
    	$data['period'] = $this->period;
    	$data['expense_date'] = $this->expense_date;
    	$data['issue_date'] = ($this->issue_date) ? $this->issue_date : null;
    	$data['approval_date'] = ($this->approval_date) ? $this->approval_date : null;
    	$data['registration_date'] = ($this->registration_date) ? $this->registration_date : null;
    	$data['status'] = $this->status;
    	$data['category'] = $this->category;
    	$data['destination'] = $this->destination;
    	$data['justification'] = $this->justification;
    	$data['horsepower'] = $this->horsepower;
    	$data['quantity'] = $this->quantity;
    	$data['document_id'] = $this->document_id;
    	$data['including_vat_amount'] = (float) $this->including_vat_amount;
		$data['vat_rate_1'] = (float) $this->vat_rate_1;
    	$data['amount_vat_1'] = (float) $this->amount_vat_1;
    	$data['vat_rate_2'] = (float) $this->vat_rate_2;
    	$data['amount_vat_2'] = (float) $this->amount_vat_2;
    	$data['capped_amount'] = (float) $this->capped_amount;
    	if ($this->audit) {
	    	$data['audit'] = json_encode($this->audit);
	    	$this->audit = json_decode($data['audit']);
    	}
    	else $data['audit'] = '';
       	return $data;
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

<?php
namespace PpitExpense\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Report implements InputFilterAwareInterface
{
    public $id;
    public $instance_id;
    public $period;
    public $owner_id;
    public $report_date;
    public $issue_date;
    public $validation_date;
    public $comment;
    public $status;
    
    // Additional fields
    public $n_fn;
    public $including_vat_sum;
        
    protected $inputFilter;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->instance_id = (isset($data['instance_id'])) ? $data['instance_id'] : null;
        $this->period = (isset($data['period'])) ? $data['period'] : null;
        $this->owner_id = (isset($data['owner_id'])) ? $data['owner_id'] : null;
        $this->report_date = (isset($data['report_date'])) ? $data['report_date'] : null;
        $this->issue_date = (isset($data['issue_date'])) ? $data['issue_date'] : null;
        $this->validation_date = (isset($data['validation_date'])) ? $data['validation_date'] : null;
        $this->comment = (isset($data['comment'])) ? $data['comment'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;

    	// Additional fields
        $this->n_fn = (isset($data['n_fn'])) ? $data['n_fn'] : null;
        $this->including_vat_sum = (isset($data['including_vat_sum'])) ? $data['including_vat_sum'] : null;
    }

    // Add content to this method:
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory     = new InputFactory();

            $inputFilter->add($factory->createInput(array(
            		'name'     => 'csrf',
            		'required' => false,
            )));

            $inputFilter->add($factory->createInput(array(
            		'name'     => 'comment',
            		'required' => FALSE,
            		'filters'  => array(
            				array('name' => 'StripTags'),
            				array('name' => 'StringTrim'),
            		),
            		'validators' => array(
            				array(
            						'name'    => 'StringLength',
            						'options' => array(
            								'encoding' => 'UTF-8',
            								'min'      => 1,
            								'max'      => 2047,
            						),
            				),
            		),
            )));
            
            $this->inputFilter = $inputFilter;
        }
        return $this->inputFilter;
    }
}

<?php
namespace PpitExpense\Model;

use PpitCore\Model\Context;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class MileageScale implements InputFilterAwareInterface
{
    public $id;
    public $year;
    public $owner_id;
    public $horsepower;
    public $annual_stage;
    public $annual_stage_caption;
    public $fix_scale;
    public $variable_scale;
    public $scale_caption;
    
    // Transitional fields
    public $annual_sum;
    
    protected $inputFilter;

    // Static fields
    private static $table;
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->year = (isset($data['year'])) ? $data['year'] : null;
        $this->owner_id = (isset($data['owner_id'])) ? $data['owner_id'] : null;
        $this->horsepower = (isset($data['horsepower'])) ? $data['horsepower'] : null;
        $this->annual_stage = (isset($data['annual_stage'])) ? $data['annual_stage'] : null;
        $this->annual_stage_caption = (isset($data['annual_stage_caption'])) ? $data['annual_stage_caption'] : null;
        $this->fix_scale = (isset($data['fix_scale'])) ? $data['fix_scale'] : null;
        $this->variable_scale = (isset($data['variable_scale'])) ? $data['variable_scale'] : null;
        $this->scale_caption = (isset($data['scale_caption'])) ? $data['scale_caption'] : null;
    }

    public function toArray()
    {
    	$data = array();
    	$data['id'] = (int) $this->id;
    	$data['year'] = (int) $this->year;
    	$data['owner_id'] = (int) $this->owner_id;
    	$data['horsepower'] = $this->horsepower;
    	$data['annual_stage'] = (int) $this->annual_stage;
    	$data['annual_stage_caption'] = (int) $this->annual_stage_caption;
    	$data['fix_scale'] = $this->fix_scale;
    	$data['variable_scale'] = $this->variable_scale;
    	$data['scale_caption'] = $this->scale_caption;
    	return $data;
    }
    
    // Add content to this method:
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
    	if (!MileageScale::$table) {
    		$sm = Context::getCurrent()->getServiceManager();
    		MileageScale::$table = $sm->get('PpitExpense\Model\MileageScaleTable');
    	}
    	return MileageScale::$table;
    }
}

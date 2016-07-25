<?php
namespace PpitExpense;

use PpitExpense\Model\Report;
use PpitExpense\Model\ReportRow;
use PpitExpense\Model\ReportTable;
use PpitCore\Model\GenericTable;
use PpitExpense\Model\MileageScale;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Authentication\Storage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;

class Module //implements AutoloaderProviderInterface, ConfigProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
            	'PpitExpense\Model\MileageScaleTable' =>  function($sm) {
                    $tableGateway = $sm->get('MileageScaleTableGateway');
                    $table = new GenericTable($tableGateway);
                    return $table;
                },
                'MileageScaleTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new MileageScale());
                    return new TableGateway('expense_mileage_scale', $dbAdapter, null, $resultSetPrototype);
                },
            	'PpitExpense\Model\ReportRowTable' =>  function($sm) {
                    $tableGateway = $sm->get('ReportRowTableGateway');
                    $table = new GenericTable($tableGateway);
                    return $table;
                },
                'ReportRowTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new ReportRow());
                    return new TableGateway('expense_report_row', $dbAdapter, null, $resultSetPrototype);
                },
            ),
        );
    }
}

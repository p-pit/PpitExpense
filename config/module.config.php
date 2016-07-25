<?php
namespace PpitExpense;

return array(
    'controllers' => array(
        'invokables' => array(
        	'PpitExpense\Controller\Approbation' => 'PpitExpense\Controller\ApprobationController',
        	'PpitExpense\Controller\Report' => 'PpitExpense\Controller\ReportController',
        	'PpitExpense\Controller\ReportRow' => 'PpitExpense\Controller\ReportRowController',
        	'PpitExpense\Controller\ReportAdmin' => 'PpitExpense\Controller\ReportAdminController',
        ),
    ),

	'router' => array(
		'routes' => array(
			'index' => array(
				'type' => 'literal',
				'options' => array(
					'route'    => '/',
					'defaults' => array(
						'controller' => 'PpitExpense\Controller\ReportRow',
						'action'     => 'index',
					),
				),
				'may_terminate' => true,
				'child_routes' => array(
					'index' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/index',
							'defaults' => array(
								'action' => 'index',
							),
						),
					),
				),
			),
			'report' => array(
				'type'    => 'literal',
				'options' => array(
					'route'    => '/report',
					'defaults' => array(
						'controller' => 'PpitExpense\Controller\Report',
						'action'     => 'index',
					),
				),
				'may_terminate' => true,
				'child_routes' => array(
					'index' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/index',
							'defaults' => array(
								'action' => 'index',
							),
						),
					),
					'detail' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/detail[/:period][/:owner_id]',
							'defaults' => array(
								'action' => 'detail',
							),
						),
					),
					'todo' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/todo',
							'defaults' => array(
								'action' => 'todo',
							),
						),
					),
					'register' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/register[/:id]',
							'constraints' => array(
								'id'     => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'register',
							),
						),
					),
					'export' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/export',
							'defaults' => array(
								'action' => 'export',
							),
						),
					),
				),
			),
			'reportRow' => array(
				'type'    => 'literal',
				'options' => array(
					'route'    => '/report-row',
					'defaults' => array(
						'controller' => 'PpitExpense\Controller\ReportRow',
						'action'     => 'index',
					),
				),
				'may_terminate' => true,
				'child_routes' => array(
					'index' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/index',
							'defaults' => array(
								'action' => 'index',
							),
						),
					),
					'detail' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/detail[/:id]',
							'constraints' => array(
								'id'     => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'detail',
							),
						),
					),
					'add' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/add[/:id]',
							'constraints' => array(
								'id'     => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'add',
							),
						),
					),
					'mileage' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/mileage[/:id]',
							'constraints' => array(
								'id'     => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'mileage',
							),
						),
					),
					'update' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/update[/:id]',
							'constraints' => array(
								'id'     => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'update',
							),
						),
					),
					'delete' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/delete[/:id]',
							'constraints' => array(
								'id'     => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'delete',
							),
						),
					),
				),
			),
			'approbation' => array(
				'type'    => 'literal',
				'options' => array(
					'route'    => '/approbation',
					'defaults' => array(
						'controller' => 'PpitExpense\Controller\Approbation',
						'action'     => 'index',
					),
				),
				'may_terminate' => true,
				'child_routes' => array(
					'index' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/index',
							'defaults' => array(
								'action' => 'index',
							),
						),
					),
					'todo' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/todo',
							'defaults' => array(
								'action' => 'todo',
							),
						),
					),
					'approbe' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/approbe[/:id]',
							'constraints' => array(
								'id'     => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'approbe',
							),
						),
					),
					'register' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/register[/:id]',
							'constraints' => array(
								'id'     => '[0-9]*',
							),
							'defaults' => array(
								'action' => 'register',
							),
						),
					),
				),
			),
			'reportAdmin' => array(
				'type'    => 'literal',
				'options' => array(
					'route'    => '/report-admin',
					'defaults' => array(
						'controller' => 'PpitExpense\Controller\ReportAdmin',
						'action'     => 'subscribe',
					),
				),
				'may_terminate' => true,
				'child_routes' => array(
					'subscribe' => array(
						'type' => 'segment',
						'options' => array(
							'route' => '/subscribe',
							'defaults' => array(
								'action' => 'subscribe',
							),
						),
					),
	       			'initpassword' => array(
	                    'type' => 'segment',
	                    'options' => array(
	                        'route' => '/initpassword[/:id]',
		                    'constraints' => array(
		                    	'id'     => '[0-9]*',
		                    ),
	                    	'defaults' => array(
	                            'action' => 'initpassword',
	                        ),
	                    ),
	                ),
				),
			),
		),
	),
	
	'bjyauthorize' => array(
		// Guard listeners to be attached to the application event manager
		'guards' => array(
			'BjyAuthorize\Guard\Route' => array(

				// Reports
				array('route' => 'report', 'roles' => array('accountant')),
				array('route' => 'report/index', 'roles' => array('accountant')),
				array('route' => 'report/detail', 'roles' => array('accountant')),
				array('route' => 'report/todo', 'roles' => array('accountant')),
				array('route' => 'report/register', 'roles' => array('accountant')),
				array('route' => 'report/export', 'roles' => array('accountant')),

				array('route' => 'reportRow', 'roles' => array('user')),
				array('route' => 'reportRow/index', 'roles' => array('user')),
				array('route' => 'reportRow/detail', 'roles' => array('user')),
				array('route' => 'reportRow/add', 'roles' => array('user')),
				array('route' => 'reportRow/mileage', 'roles' => array('user')),
				array('route' => 'reportRow/update', 'roles' => array('user')),
				array('route' => 'reportRow/delete', 'roles' => array('user')),
				array('route' => 'reportRow/export', 'roles' => array('accountant')),
						
				array('route' => 'approbation', 'roles' => array('approver')),
				array('route' => 'approbation/index', 'roles' => array('approver')),
				array('route' => 'approbation/todo', 'roles' => array('approver')),
				array('route' => 'approbation/approbe', 'roles' => array('approver')),
				array('route' => 'approbation/register', 'roles' => array('accountant')),

				array('route' => 'reportAdmin/subscribe', 'roles' => array('guest')),
				array('route' => 'reportAdmin/initpassword', 'roles' => array('guest')),
			)
		)
	),
	'emailTexts' => array(
			'Expense report approved' => array(
					'title' => 'P-PIT: Expense report approved',
					'text' => 'Your expense reports have been approved by %s for a global amount of %s. You can check them from this link: %s'
			),
			'Expense report registered' => array(
					'title' => 'P-PIT: Expense report registered',
					'text' => 'Your expense reports have been registered by %s for a global amount of %s. You can check them from this link: %s'
			),
			'Expense report rejected' => array(
					'title' => 'P-PIT: Expense report rejected',
					'text' => 'Your expense reports have been rejected by %s for a global amount of %s. You can update or cancel them from this link: %s'
			),
			'Expense report submitted' => array(
					'title' => 'P-PIT: Expense report submitted',
					'text' => 'An expense report have been submitted by %s. You can check and approve it from this link: %s'
			),
			'Expense report to be registered' => array(
					'title' => 'P-PIT: Expense report to be registered',
					'text' => 'Expense reports have been approved by %s. You can check and register them at this link: %s'
			),
	),
	'ppitExpenseSettings' => array(
		'categories' => array(
			'Invitation' => 'Invitation',
			'Meal' => 'Meal',
			'Overnight stay' => 'Overnight stay',
			'Reception' => 'Reception',
			'Supply' => 'Supply',
			'Transport' => 'Transport',
		),
	
		'vatRates' => array(
			'0.055' => '5.5 %',
			'0.2' => '20 %',
		),
	),

    'view_manager' => array(
    	'strategies' => array(
    			'ViewJsonStrategy',
    	),
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',       // On défini notre doctype
        'not_found_template'       => 'error/404',   // On indique la page 404
        'exception_template'       => 'error/index', // On indique la page en cas d'exception
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
        ),
        'template_path_stack' => array(
            'ppit-expense' => __DIR__ . '/../view',
        ),
    ),
	'translator' => array(
		'locale' => 'fr_FR',
		'translation_file_patterns' => array(
			array(
				'type'     => 'phparray',
				'base_dir' => __DIR__ . '/../language',
				'pattern'  => '%s.php',
				'text_domain' => 'ppit-expense'
			),
	       	array(
	            'type' => 'phparray',
	            'base_dir' => './vendor/zendframework/zendframework/resources/languages/',
	            'pattern'  => 'fr/Zend_Validate.php',
	        ),
		),
	),

	'ppitRoles' => array(
			'approver' => array(
					'show' => true,
					'labels' => array(
							'en_US' => 'Approver',
							'fr_FR' => 'Valideur',
					),
			),
	),
);

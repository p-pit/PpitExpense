<?php
namespace PpitExpense;

return array(
    'controllers' => array(
        'invokables' => array(
        	'PpitExpense\Controller\Expense' => 'PpitExpense\Controller\ExpenseController',
        ),
    ),

	'router' => array(
		'routes' => array(
			'index' => array(
				'type' => 'literal',
				'options' => array(
					'route'    => '/',
					'defaults' => array(
						'controller' => 'PpitExpense\Controller\Expense',
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
        	'expense' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/expense',
                    'defaults' => array(
                        'controller' => 'PpitExpense\Controller\Expense',
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
        						'search' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/search',
        										'defaults' => array(
        												'action' => 'search',
        										),
        								),
        						),
        						'list' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/list',
        										'defaults' => array(
        												'action' => 'list',
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
	       						'detail' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/detail[/:id]',
        										'constraints' => array(
        												'id' => '[0-9]*',
        										),
        										'defaults' => array(
        												'action' => 'detail',
        										),
        								),
        						),
		        				'update' => array(
		        						'type' => 'segment',
		        						'options' => array(
		        								'route' => '/update[/:id][/:act]',
		        								'constraints' => array(
		        										'id'     => '[0-9]*',
		        								),
		        								'defaults' => array(
		        										'action' => 'update',
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
				array('route' => 'expense', 'roles' => array('user')),
				array('route' => 'expense/index', 'roles' => array('user')),
				array('route' => 'expense/search', 'roles' => array('user')),
				array('route' => 'expense/list', 'roles' => array('user')),
				array('route' => 'expense/detail', 'roles' => array('user')),
				array('route' => 'expense/add', 'roles' => array('user')),
				array('route' => 'expense/mileage', 'roles' => array('user')),
				array('route' => 'expense/update', 'roles' => array('user')),
				array('route' => 'expense/delete', 'roles' => array('user')),
				array('route' => 'expense/export', 'roles' => array('accountant')),
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
			'PpitExpense' => array(
					'approver' => array(
							'show' => true,
							'labels' => array(
									'en_US' => 'Approver',
									'fr_FR' => 'Valideur',
							),
					),
			),
	),
	
	'expense' => array(
			'statuses' => array(),
			'properties' => array(
					'status' => array(
							'type' => 'select',
							'modalities' => array(
									'new' => array('fr_FR' => 'Nouveau', 'en_US' => 'New'),
									'approved' => array('fr_FR' => 'Validé', 'en_US' => 'Approved'),
									'registered' => array('fr_FR' => 'Comptabilisé', 'en_US' => 'Registered'),
							),
							'labels' => array(
									'en_US' => 'Status',
									'fr_FR' => 'Statut',
							),
					),
					'expense_date' => array(
							'type' => 'date',
							'labels' => array(
									'en_US' => 'Expense date',
									'fr_FR' => 'Date dépense',
							),
					),
					'approval_date' => array(
							'type' => 'date',
							'labels' => array(
									'en_US' => 'Approval date',
									'fr_FR' => 'Date validation',
							),
					),
					'registration_date' => array(
							'type' => 'date',
							'labels' => array(
									'en_US' => 'Registration date',
									'fr_FR' => 'Date enregistrement',
							),
					),
					'category' => array(
							'type' => 'select',
							'modalities' => array(
									'transport' => array('fr_FR' => 'Transport', 'en_US' => 'Transport'),
									'meal' => array('fr_FR' => 'Repas', 'en_US' => 'Meal'),
									'phone' => array('fr_FR' => 'Téléphone', 'en_US' => 'Phone'),
									'mail' => array('fr_FR' => 'Courrier', 'en_US' => 'Mail'),
									'office' => array('fr_FR' => 'Bureautique', 'en_US' => 'Office'),
									'library' => array('fr_FR' => 'Librairie', 'en_US' => 'Library'),
									'invitation' => array('fr_FR' => 'Invitation', 'en_US' => 'Invitation'),
									'gift' => array('fr_FR' => 'Cadeau', 'en_US' => 'Gift'),
									'miscellaneous' => array('fr_FR' => 'Divers', 'en_US' => 'Miscellaneous'),
							),
							'labels' => array(
									'en_US' => 'Category',
									'fr_FR' => 'Categorie',
							),
					),
					'tax_inclusive' => array(
							'type' => 'number',
							'minValue' => 0,
							'maxValue' => 99999999,
							'labels' => array(
									'en_US' => 'Tax inclusive',
									'fr_FR' => 'Montant TTC',
							),
					),
					'tax_amount' => array(
							'type' => 'number',
							'minValue' => 0,
							'maxValue' => 99999999,
							'labels' => array(
									'en_US' => 'Tax amount',
									'fr_FR' => 'Montant TVA',
							),
					),
					'capped_amount' => array(
							'type' => 'number',
							'minValue' => 0,
							'maxValue' => 99999999,
							'labels' => array(
									'en_US' => 'Capped amount',
									'fr_FR' => 'Montant plafonné',
							),
					),
					'document' => array(
							'type' => 'dropbox',
							'labels' => array(
									'en_US' => 'Attachment',
									'fr_FR' => 'Justificatif',
							),
					),
			),
	),
	'expense/index' => array(
			'title' => array('en_US' => 'P-PIT Finance', 'fr_FR' => 'P-PIT Finance'),
	),
	'expense/search' => array(
			'title' => array('en_US' => 'Expenses', 'fr_FR' => 'Dépenses'),
			'todoTitle' => array('en_US' => 'todo list', 'fr_FR' => 'todo list'),
			'main' => array(
				'expense_date' => 'date',
				'status' => 'select',
				'category' => 'select',
				'tax_inclusive' => 'range',
			),
	),
	'expense/list' => array(
			'expense_date' => 'date',
			'status' => 'select',
			'category' => 'select',
			'tax_inclusive' => 'number',
	),
	'expense/detail' => array(
			'title' => array('en_US' => 'Expense detail', 'fr_FR' => 'Détail de la dépense'),
			'displayAudit' => true,
	),
	'expense/update' => array(
			'status' => array('mandatory' => true),
			'expense_date' => array('mandatory' => false),
			'approval_date' => array('mandatory' => false),
			'registration_date' => array('mandatory' => false),
			'category' => array('mandatory' => true),
			'tax_inclusive' => array('mandatory' => true),
			'tax_amount' => array('mandatory' => false),
			'document' => array('mandatory' => false),
	),

	'demo' => array(
			'expense/search/title' => array(
					'en_US' => '
<h4>Term list</h4>
<p>As a default, all the current expenses (to be registered) are presented in the list.</p>
<p>As soon as a criterion below is specified, the list switch in search mode.</p>
',
					'fr_FR' => '
<h4>Liste des dépenses</h4>
<p>Par défaut, toutes les dépenses en cours (à comptabiliser) sont présentées dans la liste.</p>
<p>Dès lors qu\'un des critères ci-dessous est spécifié, le mode de recherche est automatiquement activé.</p>
',
			),
			'expense/search/x' => array(
					'en_US' => '
<h4>Return in default mode</h4>
<p>The <code>x</code> button reinitializes all the search criteria and reset the list filtered on current expenses.</p>
',
					'fr_FR' => '
<h4>Retour au mode par défaut</h4>
<p>Le bouton <code>x</code> réinitialise tous les critères de recherche et ré-affiche la liste filtrée sur les dépenses en cours.</p>
',
			),
			'expense/search/export' => array(
					'en_US' => '
<h4>List export</h4>
<p>The list can be exported to Excel as it is presented: defaulting list or list resulting of a multi-criteria search.</p>
',
					'fr_FR' => '
<h4>Export de la liste</h4>
<p>La liste peut être exportée sous Excel telle que présentée : liste par défaut ou liste résultant d\'une recherche multi-critère.</p>
',
			),
			'expense/list/ordering' => array(
					'en_US' => '
<h4>Ordering</h4>
<p>The list can be sorted according to each column in ascending or descending order.</p>
',
					'fr_FR' => '
<h4>Classement</h4>
<p>La liste peut être triée selon chaque colonne en ordre ascendant ou descendant.</p>
',
			),
			'expense/list/detail' => array(
					'en_US' => '',
					'fr_FR' => '
<h4>Détail d\'une dépense</h4>
<p>Le bouton zoom permet d\'accéder au détail d\'une dépense.</p>
					',
			),
			'expense/update' => array(
					'en_US' => '',
					'fr_FR' => '
<h4>Gestion du statut et des attributs de la dépense</h4>
<p>L\'accès au détail d\'une dépense permet de consulter et éventuellement en rectifier les données.</p>
<p>Il permet également d\'en actualiser la statut et y associer une pièce jointe (ex. photo ou scan de facture).</p>
					',
			),
	),
);

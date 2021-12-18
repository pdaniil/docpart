<?php 

	$dictionary = [];
	
	$dictionary["notifications"] = 
	[
		"params" => [ 'email_subject', 'email_body', 'sms_body', 'email_on', 'sms_on', 'name' ],
	];
	
	$dictionary["sms"] = 
	[
		"params" => [ 'parameter_values', 'handler'],
	];
	
	$dictionary["geo"] = 
	[
		"params" => [ 'id', 'count', 'level', 'value', 'parent', 'order' ],
	];
	
	$dictionary["offices"] =
	[
		"params" => [ 'id', 'caption', 'country', 'region', 'city', 'address', 'phone', 'email', 'coordinates', 'description', 'users', 'timetable', 'pay_system_id', 'pay_system_parameters', 'arr_geo_id' ],
		
		"sub_tables" =>
		[
			"geo" 		=> 
			[
				"params" => [ 'geo_id' ],
			],
			
			"storages"  => 
			[ 
				"params" => ['storage_id', 'additional_time' ],
				
				"sub_tables" => 
				[
					"groups" =>
					[
						"params" => [ 'group_id' ],
						"sub_tables" => 
						[
							"markups" => 
							[
								"params" => [ 'min_point', 'max_point', 'markup' ],
							],
						],
					],
				],
			],
		],
		
		"sub_tables_param" =>
		[
			"geo_param" => [ 'id' ],
		],
	];

?>
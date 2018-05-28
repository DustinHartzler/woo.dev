<?php
/**
 * SAPO international Rates
 */

function wc_sapo_get_ips_rates() {
	return array(
		'A' => array(
			'Air' => array(
				'Base' => 133.30,
				'Additional' => 4.10
			),
			'Surface' => array(
				'Base' => 126.60,
				'Additional' => 1.65
			)
		),
		'B' => array(
			'Air' => array(
				'Base' => 196.60,
				'Additional' => 5.15
			),
			'Surface' => array(
				'Base' => 196.60,
				'Additional' => 3.15
			)
		),
		'C' => array(
			'Air' => array(
				'Base' => 196.60,
				'Additional' => 18.40
			),
			'Surface' => array(
				'Base' => 183.00,
				'Additional' => 5.15
			)
		),
		'D' => array(
			'Air' => array(
				'Base' => 203.40,
				'Additional' => 16.80
			),
			'Surface' => array(
				'Base' => 192.00,
				'Additional' => 3.60
			)
		),
		'E' => array(
			'Air' => array(
				'Base' => 151.30,
				'Additional' => 26.20
			),
			'Surface' => array(
				'Base' => 151.30,
				'Additional' => 5.75
			)
		),
		'F' => array(
			'Air' => array(
				'Base' => 144.50,
				'Additional' => 23.40
			),
			'Surface' => array(
				'Base' => 141.35,
				'Additional' => 3.60
			)
		),
		'SMALL_PACKET' => array(
			'Air' => array(
				'Base' => 0.00,
				'Additional' => 36.60
			),
			'Surface' => array(
				'Base' => 0.00,
				'Additional' => 18.35
			)
		),
	);
}

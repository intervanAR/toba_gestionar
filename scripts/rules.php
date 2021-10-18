<?php

/**
 * Contiene las reglas de validaci�n
 * utilizadas por los PHP CS de cada proyecto.
 *
 * @author lgraziani
 * @version 1.0.0
 */
$rules = [
	'@Symfony' => true,
	'array_syntax' => ['syntax' => 'short'],
	// Deprecada desde la versi�n 2.8.0
	'pre_increment' => false,
	'ordered_class_elements' => true,
];

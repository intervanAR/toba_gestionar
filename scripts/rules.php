<?php

/**
 * Contiene las reglas de validación
 * utilizadas por los PHP CS de cada proyecto.
 *
 * @author lgraziani
 * @version 1.0.0
 */
$rules = [
	'@Symfony' => true,
	'array_syntax' => ['syntax' => 'short'],
	// Deprecada desde la versión 2.8.0
	'pre_increment' => false,
	'ordered_class_elements' => true,
];

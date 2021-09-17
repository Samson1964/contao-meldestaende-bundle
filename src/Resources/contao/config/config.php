<?php

$GLOBALS['BE_MOD']['content']['meldestaende'] = array(
	'tables'         => array('tl_meldestaende'),
	'icon'           => 'bundles/contaomeldestaende/images/icon.png',
	'import'         => array('Schachbulle\ContaoMeldestaendeBundle\Classes\Import', 'run'),
);

/**
 * Frontend-Module
 */
$GLOBALS['FE_MOD']['titelnormen'] = array
(
	'meldestaende' => 'Schachbulle\ContaoMeldestaendeBundle\Modules\Meldestaende',
);  

/**
 * Inhaltselemente
 */
 
//$GLOBALS['TL_CTE']['includes']['titelnormen'] = 'Schachbulle\ContaotitelnormenBundle\ContentElements\Adresse';

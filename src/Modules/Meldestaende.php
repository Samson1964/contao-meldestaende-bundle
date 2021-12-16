<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package   DeWIS
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

namespace Schachbulle\ContaoMeldestaendeBundle\Modules;

class Meldestaende extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_meldestaende';

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### MELDESTÄNDE ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}

		return parent::generate(); // Weitermachen mit dem Modul
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{

		// Aktive Meldestände laden
		$objResult = \Database::getInstance()->prepare('SELECT * FROM tl_meldestaende WHERE published = ? ORDER BY dateidatum DESC')
		                                     ->execute(1);

		$daten = '';
		// Dateien auslesen
		if($objResult->numRows)
		{
			while($objResult->next())
			{
				$datei = TL_ROOT.'/files/meldestaende/'.$objResult->dateiname;
				$content = file($datei);
				$row = 0;
				$daten .= '<h3>'.$objResult->titel.'</h3>';
				$daten .= '<span>Aktualisiert am '.date('d.m.Y H:i', $objResult->dateidatum).'</span>';
				$daten .= '<table>';
				foreach($content as $zeile)
				{
					if(!\Schachbulle\ContaoHelperBundle\Classes\Helper::is_utf8($zeile))
					{
						// String ist kein UTF-8, jetzt unwandeln
						$zeile = utf8_encode($zeile);
					}
					if($row) $zelle = 'td';
					else $zelle = 'th';
					$spalten = explode(';', trim($zeile));
					$daten .= '<tr>';
					foreach($spalten as $spalte)
					{
						$daten .= '<'.$zelle.'>';
						$daten .= $spalte;
						$daten .= '</'.$zelle.'>';
					}
					$daten .= '</tr>';
					$row++;
				}
				$daten .= '</table>';
			}
		}

		// Ausgabe
		$this->Template->daten = $daten;

	}

}

<?php

namespace Schachbulle\ContaoMeldestaendeBundle\Classes;

/**
 * Class Import
  */
class Import extends \Backend
{

	function __construct()
	{
	}

	/**
	 * Importiert eine Mitgliederliste
	 */
	public function run()
	{

		if(\Input::get('key') != 'import')
		{
			// Beenden, wenn der Parameter nicht übereinstimmt
			return '';
		}

		// Objekt BackendUser importieren
		$this->import('BackendUser','User');
		$class = $this->User->uploader;

		// See #4086
		if (!class_exists($class))
		{
			$class = 'FileUpload';
		}

		$objUploader = new $class();

		// Formular wurde abgeschickt, Meldestände hochladen
		if (\Input::post('FORM_SUBMIT') == 'tl_meldestaende_import')
		{
			$arrUploaded = $objUploader->uploadTo('system/tmp');

			if(empty($arrUploaded))
			{
				\Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
				$this->reload();
			}

			$this->import('Database');

			// Zielverzeichnis anlegen
			$zielordner = TL_ROOT.'/files/meldestaende';
			@mkdir($zielordner, 777);

			foreach($arrUploaded as $txtFile)
			{
				$objFile = new \File($txtFile, true);
				if ($objFile->extension != 'txt')
				{
					\Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension));
					continue;
				}

				$quelldatei = TL_ROOT.'/'.$txtFile;
				$zieldatei = TL_ROOT.'/files/meldestaende/'.basename($txtFile);
				copy($quelldatei, $zieldatei); // Datei kopieren
				
				// Nach Datei in Datenbank suchen
				$objSuche = \Database::getInstance()->prepare("SELECT * FROM tl_meldestaende WHERE dateiname = ?")
				                                    ->execute(basename($txtFile));

				if($objSuche->numRows)
				{
					// Datei bereits vorhanden, dann überschreiben
					$set = array
					(
						'dateidatum' => filemtime($zieldatei),
					);
					$objUpdate = \Database::getInstance()->prepare("UPDATE tl_meldestaende %s WHERE id = ?")
					                                     ->set($set)
					                                     ->execute($objSuche->id);
					\Controller::createNewVersion('tl_meldestaende', $objSuche->id);
				}
				else
				{
					// Neue Datei
					$set = array
					(
						'tstamp'     => time(),
						'dateidatum' => filemtime($zieldatei),
						'dateiname'  => basename($txtFile),
						'titel'      => '',
						'published'  => false
					);
					$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_meldestaende %s")
					                                     ->set($set)
					                                     ->execute();
				}
			}

			// Cookie setzen und zurückkehren zur Adressenliste (key=import aus URL entfernen)
			\System::setCookie('BE_PAGE_OFFSET', 0, 0);
			$this->redirect(str_replace('&key=import', '', \Environment::get('request')));
		}

		// Return form
		return '
<div class="content">
<div id="tl_buttons">
<a href="'.ampersand(str_replace('&key=import', '', \Environment::get('request'))).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">'.$GLOBALS['TL_LANG']['tl_meldestaende_import']['headline'].'</h2>
<p style="margin: 18px;">'.$GLOBALS['TL_LANG']['tl_meldestaende_import']['format'].'</div>
'.\Message::generate().'
<form action="'.ampersand(\Environment::get('request'), true).'" id="tl_wortliste_import" class="tl_form" method="post" enctype="multipart/form-data">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_meldestaende_import">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">

<div class="widget">
  <h3>'.$GLOBALS['TL_LANG']['MSC']['source'][0].'</h3>'.$objUploader->generateMarkup().(isset($GLOBALS['TL_LANG']['tl_meldestaende_import']['source'][0]) ? '
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['tl_meldestaende_import']['source'][1].'</p>' : '').'
</div>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  <input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['tl_meldestaende_import']['submit']).'">
</div>

</div>
</form>
</div>';

	}

	public function is_utf8($str)
	{
	    $strlen = strlen($str);
	    for ($i = 0; $i < $strlen; $i++) {
	        $ord = ord($str[$i]);
	        if ($ord < 0x80) continue; // 0bbbbbbb
	        elseif (($ord & 0xE0) === 0xC0 && $ord > 0xC1) $n = 1; // 110bbbbb (exkl C0-C1)
	        elseif (($ord & 0xF0) === 0xE0) $n = 2; // 1110bbbb
	        elseif (($ord & 0xF8) === 0xF0 && $ord < 0xF5) $n = 3; // 11110bbb (exkl F5-FF)
	        else return false; // ungültiges UTF-8-Zeichen
	        for ($c=0; $c<$n; $c++) // $n Folgebytes? // 10bbbbbb
	            if (++$i === $strlen || (ord($str[$i]) & 0xC0) !== 0x80)
	                return false; // ungültiges UTF-8-Zeichen
	    }
	    return true; // kein ungültiges UTF-8-Zeichen gefunden
	}

}

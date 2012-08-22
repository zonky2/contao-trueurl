<?php

class TrueURLBackend extends Backend {

	public function hookLoadDataContainer($strTable) {
		if($strTable == 'tl_page') {
			$GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'] = $GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'];
			$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'] = array('TrueURLBackend', 'labelPage');
		}
	}

	public function buttonAlias($strHREF, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot) {
		if($this->Session->get('bbit_turl_alias')) {
			$strLabel = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasHide'][0];
			$strTitle = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasHide'][1];
			$blnState = 0;
		} else {
			$strLabel = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow'][0];
			$strTitle = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow'][1];
			$blnState = 1;
		}
		return sprintf('<br/><br/><a href="%s" class="%s" title="%s"%s>%s</a> ',
			$this->addToUrl($strHREF . '&amp;state=' . $blnState),
			$strClass,
			specialchars($strTitle),
			$strAttributes,
			$strLabel
		);
	}

	public function buttonRegenerate($strHREF, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot) {
		return $this->User->isAdmin ? sprintf(' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
			$this->addToUrl($strHREF),
			$strClass,
			specialchars($strTitle),
			$strAttributes,
			$strLabel
		) : '';
	}
	
	public function buttonRepair($strHREF, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot) {
		return $this->User->isAdmin ? sprintf(' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
			$this->addToUrl($strHREF),
			$strClass,
			specialchars($strTitle),
			$strAttributes,
			$strLabel
		) : '';
	}
	
	public function buttonAutoInherit($arrRow, $strHREF, $strLabel, $strTitle, $strIcon, $strAttributes, $strTable, $arrRootIDs, $arrChildRecordIDs, $blnCircularReference, $strPrevious, $strNext) {
		return $this->User->isAdmin ? sprintf('<a href="%s" title="%s"%s>%s</a> ',
			$this->addToUrl($strHREF . '&amp;id=' . $arrRow['id']),
			specialchars($strTitle),
			$strAttributes,
			$this->generateImage($strIcon, $strLabel)
		) : '';
	}
	
	private $blnRecurse = false;
	
	public function labelPage($row, $label, DataContainer $dc=null, $imageAttribute='', $blnReturnImage=false, $blnProtected=false) {
		$blnWasRecurse = $this->blnRecurse;
		$arrCallback = $blnWasRecurse ? array('tl_page', 'addIcon') : $GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'];
		
		$this->blnRecurse = true;
		$this->import($arrCallback[0]);
		$label = $this->$arrCallback[0]->$arrCallback[1]($row, $label, $dc, $imageAttribute, $blnReturnImage, $blnProtected);
		$this->blnRecurse = false;
		
		if($blnWasRecurse) {
			return $label;
		}
		
		if(!$this->Session->get('bbit_turl_alias')) {
			return $label;
		}
		
		$arrAlias = $this->objTrueURL->splitAlias($row);
		 
		if(!$arrAlias) {
			$label .= ' <span style="color:#CC5555;">[';
			$label .= $GLOBALS['TL_LANG']['tl_page']['errNoAlias'];
			$label .= ']</span>';
			return $label;
		}
		
		$label .= ' <span style="color:#b3b3b3;">[';
		if($arrAlias['root']) {
			$label .= '<span style="color:#0C0;">' . $arrAlias['root'] . '</span>';
			$strConnector = '/';
		}
		if($arrAlias['parent']) {
			$label .= $strConnector . $arrAlias['parent'];
			$strConnector = '/';
		}
		if($arrAlias['fragment']) {
			$label .= $strConnector . '<span style="color:#5C9AC9;">' . $arrAlias['fragment'] . '</span>';
		}
		$label .= ']</span>';
		
		if($row['type'] == 'root') {
			$strTitle = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInherit'][0] . ': ';
			switch($row['bbit_turl_rootInherit']) {
				default:
				case 'normal': $label .= $this->makeImage('link.png', $strTitle . $GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions']['normal']); break;
				case 'always': $label .= $this->makeImage('link_add.png', $strTitle . $GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions']['always']); break;
				case 'never': $label .= $this->makeImage('link_delete.png', $strTitle . $GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions']['never']); break;
			}
			
		} else {
			$row['bbit_turl_inherit'] && $label .= $this->makeImage('link.png', $GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit'][0]);
			$row['bbit_turl_transparent'] && $label .= $this->makeImage('link_go.png', $GLOBALS['TL_LANG']['tl_page']['bbit_turl_transparent'][0]);
			$row['bbit_turl_ignoreRoot'] && $label .= $this->makeImage('link_break.png', $GLOBALS['TL_LANG']['tl_page']['bbit_turl_ignoreRoot'][0]);
		}
		
		if(!$arrAlias['err']) {
			return $label;
		}
		
		foreach($arrAlias['err'] as $strError => &$strLabel) {
			$strLabel = $GLOBALS['TL_LANG']['tl_page'][$strError];
		}
		$label .= $this->makeImage('link_error.png', implode(' - ', $arrAlias['err']));
		
		return $label;
	}
	
	protected function makeImage($strImage, $strTitle) {
		return ' ' . $this->generateImage(
			'system/modules/backboneit_trueurl/html/images/' . $strImage,
			$strTitle, ' title="' . specialchars($strTitle) . '"'
		);
	}
	
    public function hookAddCustomRegexp($strRegexp, $varValue, Widget $objWidget) {
        if($strRegexp == 'trueurl') {
            if(!preg_match('/^[\pN\pL \.\/_-]*$/u', $varValue)) {
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alnum'], $objWidget->label));
            }
            return true;
        }
        return false;
    }
	
	public function keyAlias() {
		$this->Session->set('bbit_turl_alias', $this->Input->get('state') == 1);
		$this->redirect($this->getReferer());
	}
	
	public function keyRegenerate() {
		$this->objTrueURL->regeneratePageRoots();
		$this->redirect($this->getReferer());
	}
	
	public function keyRepair() {
		$this->objTrueURL->repair();
		$this->redirect($this->getReferer());
	}
	
	public function keyAutoInherit() {
		$this->objTrueURL->update($this->Input->get('id'), null, true);
		$this->redirect($this->getReferer());
	}
	
	public function saveAlias($strAlias) {
		return trim($strAlias, ' /');
	}
	
	public function loadRootInherit($varValue, $objDC) {
		return $objDC->activeRecord->bbit_turl_rootInherit;
	}
	
	protected $arrRootInherit = array();
	
	public function saveRootInherit($strNew, $objDC) {
		if($objDC->activeRecord) {
			$strOld = $objDC->activeRecord->bbit_turl_rootInherit
				? $objDC->activeRecord->bbit_turl_rootInherit
				: 'normal';
			if($strOld != $strNew) {
				$this->arrRootInherit[$objDC->id] = array($strOld, $strNew);
			}
		}
		return null;
	}
    
	public function oncreatePage($strTable, $intID, $arrSet, $objDC) {
		$objParent = $this->getPageDetails($arrSet['pid']);
		$intRootID = $objParent->type == 'root' ? $objParent->id : $objParent->rootId;
		$this->Database->prepare(
			'UPDATE tl_page SET bbit_turl_root = ?, bbit_turl_inherit = (SELECT bbit_turl_defaultInherit FROM tl_page WHERE id = ?) WHERE id = ?'
		)->execute($intRootID, $intRootID, $intID);
	}
	
	public function onsubmitPage($objDC) {
		if(!$objDC->activeRecord) {
			return;
		}
		
		$strAlias = $objDC->activeRecord->alias;
		if(!strlen($strAlias)) {
			$tl_page = new tl_page();
			$strAlias = $tl_page->generateAlias('', $objDC);
		}
		
		if(isset($this->arrRootInherit[$objDC->id])) {
			list($strOld, $strNew) = $this->arrRootInherit[$objDC->id];
			unset($this->arrRootInherit[$objDC->id]);
			
			if($objDC->activeRecord->type == 'root' && $strNew == 'always') {
				$this->Database->prepare(
					'UPDATE	tl_page
					SET		bbit_turl_rootInherit = ?
					WHERE	id = ?'
				)->execute($strNew, $objDC->id);
				$this->Database->prepare(
					'UPDATE	tl_page
					SET		bbit_turl_fragment = SUBSTRING(bbit_turl_fragment, ?)
					WHERE	bbit_turl_root = ?
					AND		bbit_turl_fragment LIKE ?
					AND		bbit_turl_fragment = alias'
				)->execute(strlen($strAlias) + 2, $objDC->id, $strAlias . '/%');
			}
		}
		
		$strFragment = $this->objTrueURL->extractFragment($objDC->id, $strAlias);
		$this->objTrueURL->update($objDC->id, $strFragment);
	}
	
	public function oncopyPage($intID) {
		$this->objTrueURL->update($intID);
		$this->objTrueURL->regeneratePageRoots($intID);
	}
	
	public function oncutPage($objDC) {
		$this->objTrueURL->update($objDC->id);
		$this->objTrueURL->regeneratePageRoots($objDC->id);
	}
	
	public function onrestorePage($intID) {
		$this->objTrueURL->update($intID);
		$this->objTrueURL->regeneratePageRoots($intID);
	}

	public function generateArticle($objDC) {
		if(!$objDC->activeRecord) {
			return;
		}

		$strAlias = $objDC->activeRecord->alias;
		$arrAlias = explode('/', $strAlias);

		$objDC->activeRecord->alias = array_pop($arrAlias);
		$tl_page = new tl_page();
		$tl_page->generateArticle($objDC);
		$objDC->activeRecord->alias = $strAlias;
	}
	
	protected $objTrueURL;
	
	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
		$this->objTrueURL = new TrueURL();
	}
	
}

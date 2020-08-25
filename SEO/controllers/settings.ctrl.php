<?php

/***************************************************************************
 *   Copyright (C) 2009-2011 by Geo Varghese(www.seopanel.in)  	           *
 *   sendtogeo@gmail.com   												   *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 *   This program is distributed in the hope that it will be useful,       *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *   GNU General Public License for more details.                          *
 *                                                                         *
 *   You should have received a copy of the GNU General Public License     *
 *   along with this program; if not, write to the                         *
 *   Free Software Foundation, Inc.,                                       *
 *   59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             *
 ***************************************************************************/

# class defines all settings controller functions
class SettingsController extends Controller{
	
	var $layout = 'ajax';
	
	# function to show system settings
	function showSystemSettings($category='system') {		
	    $category = addslashes($category);
		$this->set('list', $this->__getAllSettings(true, 1, $category));
		
		if ($category == 'system') {		
    		$langCtrler = New LanguageController();
    		$langList = $langCtrler->__getAllLanguages(" where translated=1");
    		$this->set('langList', $langList);
    		
    		$timezoneCtrler = New TimeZoneController();
    		$timezoneList = $timezoneCtrler->__getAllTimezones();
    		$this->set('timezoneList', $timezoneList);
    		
    		$currencyCtrler = new CurrencyController();
	    	$this->set('currencyList', $currencyCtrler->__getAllCurrency(" and paypal=1 and status=1 and name!=''"));
			
		}
		
		$this->set('category', $category);
		
		// if report settings page
		if ($category == 'report') {		    
		    
            $spTextReport = $this->getLanguageTexts('report', $_SESSION['lang_code']);
            $this->set('spTextReport', $spTextReport);		    
		    $scheduleList = array(
    			1 => $_SESSION['text']['label']['Daily'],
    			2 => $spTextReport['2 Days'],
    			7 => $_SESSION['text']['label']['Weekly'],
    			30 => $_SESSION['text']['label']['Monthly'],
    		);
		    $this->set('scheduleList', $scheduleList);		    
	        $this->render('settings/showreportsettings');
	        
		} else if ($category == 'proxy') {		    
		    
            $spTextProxy = $this->getLanguageTexts('proxy', $_SESSION['lang_code']);
            $this->set('spTextProxy', $spTextProxy);		    
	        $this->render('settings/showproxysettings');
		} else {
			
			$spTextPanel = $this->getLanguageTexts('panel', $_SESSION['lang_code']);

			// switch through category
			switch ($category) {
				
				case "api":
					$this->set('headLabel', $spTextPanel['API Settings']);					
					break;
				
				case "moz":
					$this->set('headLabel', $spTextPanel['MOZ Settings']);					
					break;
				
				case "google":
					$this->set('headLabel', $spTextPanel['Google Settings']);					
					break;
					
				case "mail":
				    $this->set('headLabel', $spTextPanel['Mail Settings']);
				    break;
					
				default:					
					break;
				
			}
			
		    $this->render('settings/showsettings');
		}
	}
	
	function updateSystemSettings($postInfo) {
		
		$setList = $this->__getAllSettings(true, 1, $postInfo['category']);
		foreach($setList as $setInfo){

			switch($setInfo['set_name']){
				
				case "SP_PAGINGNO":
					$postInfo[$setInfo['set_name']] = intval($postInfo[$setInfo['set_name']]);
					$postInfo[$setInfo['set_name']] = empty($postInfo[$setInfo['set_name']]) ? SP_PAGINGNO_DEFAULT : $postInfo[$setInfo['set_name']];				
					break;
				
				case "SP_CRAWL_DELAY":
				case "SP_USER_GEN_REPORT":
				case "SA_CRAWL_DELAY_TIME":
				case "SA_MAX_NO_PAGES":
				case "SP_NUMBER_KEYWORDS_CRON":
					$postInfo[$setInfo['set_name']] = intval($postInfo[$setInfo['set_name']]);
					break;

				case "SP_SMTP_HOST":
			    case "SP_SMTP_USERNAME":
		        case "SP_SMTP_PASSWORD":		            
			        // if smtp mail enabled then check all smtp details entered
			        if (empty($postInfo[$setInfo['set_name']]) && !empty($postInfo['SP_SMTP_MAIL'])) {
			            $this->set('errorMsg', $this->spTextSettings['entersmtpdetails']);
	                    $this->showSystemSettings($postInfo['category']);
	                    exit;
			        }
				    break;				    
				    
		        case "SP_SYSTEM_REPORT_INTERVAL":
		            // update users report schedule if system report schedule is greater than them
		            $postInfo[$setInfo['set_name']] = intval($postInfo[$setInfo['set_name']]);
		            $sql = "Update reports_settings set report_interval=".$postInfo[$setInfo['set_name']]." where report_interval<".$postInfo[$setInfo['set_name']];
		            $userList = $this->db->query($sql);
		            break;
			}
			
			$sql = "update settings set set_val='".addslashes($postInfo[$setInfo['set_name']])."' where set_name='".addslashes($setInfo['set_name'])."'";
			$this->db->query($sql);
		}
		
		$this->set('saved', 1);
		$this->showSystemSettings($postInfo['category']);
	}
	
	# func to show about us of seo panel
	function showAboutUs($info) {
	    
	    $blogContent = getCustomizerPage('aboutus');
	    if (!empty($blogContent['blog_content'])) {
	        $this->set('blogContent', $blogContent);
	    } else {
	    	
	    	if ($info['subsec'] != "sponsors") {
	    		$sql = "select t.*,l.lang_name from translators t,languages l where t.lang_code=l.lang_code";
	    		$transList = $this->db->select($sql); 
	    		$this->set('transList', $transList);
	    	}
    		
    		include_once(SP_CTRLPATH."/information.ctrl.php");
    		$infoCtrler = new InformationController();
    		$this->set('sponsors', $infoCtrler->getSponsors());
    		$this->set('subSec', $info['subsec']);
	    }
	    
		$this->render('settings/aboutus');
	}	
	
	# func to show version of seo panel
	function showVersion() {
		$this->render('settings/version');
	}
	
	# function to check version
	function checkVersion() {
	    $content = $this->spider->getContent(SP_VERSION_PAGE);
	    $content['page'] = str_replace('Version:', '', $content['page']);
	    $latestVersion = str_replace('.', '', $content['page']);
	    $installVersion = str_replace('.', '', SP_VERSION_NUMBER);
	    if ($latestVersion > $installVersion) {
	        echo showErrorMsg($this->spTextSettings['versionnotuptodatemsg']."({$content['page']}) from <a href='".SP_DOWNLOAD_LINK."' target='_blank'>".SP_DOWNLOAD_LINK."</a>", false);
	    } else {
	        echo showSuccessMsg($this->spTextSettings["Your Seo Panel installation is up to date"], false);
	    }
	}

	// show google api settings notification
	public static function showCheckCategorySettings($category, $printMsg = false) {
		$ctrler = new SettingsController();
		$spTextSettings = $ctrler->getLanguageTexts('settings', $_SESSION['lang_code']);
		$showMsg = '';
		$notSet = false;
		
		// if category is google
		if ($category == 'google') {
			$settingInfo = $ctrler->__getSettingInfo('SP_GOOGLE_API_KEY');
			
			if (empty($settingInfo['set_val'])) {
				$notSet = true;
				$msgStr = $spTextSettings['Please update google settings to get the results'];
			}
			
		} else if ($category == 'moz') {

			$accessInfo = $ctrler->__getSettingInfo('SP_MOZ_API_ACCESS_ID');
			$secretInfo = $ctrler->__getSettingInfo('SP_MOZ_API_SECRET');
			
			if (empty($accessInfo['set_val']) || empty($secretInfo['set_val'])) {
				$notSet = true;
				$msgStr = $spTextSettings['Please update MOZ settings to get complete results'];
			}
			
		}
				
		// check whether settings is empty
		if ($notSet) {
			$settingUrl = isAdmin() ? SP_WEBPATH . "/admin-panel.php?menu_selected=settings&start_script=settings&category=$category" : "#";
			$showMsg = '
			<div id="topnewsbox">
				<a class="bold_link" href="' . $settingUrl . '">'. $msgStr .' &gt;&gt;
				</a>
			</div>';
			
			// if print message is enabled
			if ($printMsg) {
				echo $showMsg;
				exit;
			}
			
		}
		
		return $showMsg;
		
	}
	
	// function to get settings info
	function __getSettingInfo($setName) {
		$setInfo = $this->dbHelper->getRow('settings', "set_name='".addslashes($setName)."'");
		return $setInfo;
	}
	
	// function to show test email
	function showTestEmailSettings() {
		$this->render('settings/show_test_email');
	}
	
	// fucntion to send test email
	function sendTestEmail($info) {
		$errMsg = formatErrorMsg($this->validate->checkEmail($info['test_email']));
		
		if(!$this->validate->flagErr){
			
			$userController =  New UserController();
			$adminInfo = $userController->__getAdminInfo();
			$adminName = $adminInfo['first_name']."-".$adminInfo['last_name'];
			$this->set('adminName', $adminName);
			$content = $this->getViewContent('email/test_email');
			
			if (!sendMail($adminInfo['email'], $adminName, $info['test_email'], "Test email from " . SP_COMPANY_NAME, $content)) {
				showErrorMsg('An internal error occured while sending mail!');
			} else {
				showSuccessMsg("Email send successfully to " . $info['test_email']);
			}			
			
		} else {
			showErrorMsg($errMsg);
		}
		
	}
	
}
?>
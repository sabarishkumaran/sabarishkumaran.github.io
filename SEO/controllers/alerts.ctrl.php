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

// class defines all alert controller functions
class AlertController extends Controller {
    
    var $alertCategory;
    var $alertType;
    var $tableName = "alerts";
    var $pgScriptPath = "alerts.php";
    
    function __construct() {
        parent::__construct();
        $this->alertCategory = array(
            'general' => $_SESSION['text']['common']["General"],
            'reports' => $_SESSION['text']['common']["Reports"],
        );
        
        $this->alertType = array(
            'success',
            'info',
            'warning',
            'danger',
            'primary',
            'secondary',
            'light',
            'dark',
        );
    }
	
	// func to get all Alerts
	function __getAllAlerts($cond = '') {
		$sql = "select * from $this->tableName where 1=1 $cond";
		$langList = $this->db->select($sql);
		return $langList;
	}
	
	// func to get alert info
	function __getAlertInfo($value, $col='id') {
		$sql = "select * from $this->tableName where $col = '". addslashes($value) ."'";
		$langInfo = $this->db->select($sql, true);
		return $langInfo;
	}
	
	/**
	 * Function to display alert details
	 * @param Array $info	Contains all search details
	 */
	function listAlerts($info = '') {
	    $userId = isLoggedIn();
	    $fromTime = !empty($info['from_time']) ? $info['from_time'] : date('Y-m-d', strtotime('-30 days'));
	    $toTime = !empty($info['to_time']) ? $info['to_time'] : date('Y-m-d');
	    $this->set('fromTime', $fromTime);
	    $this->set('toTime', $toTime);
	    
	    $sql = "select * from $this->tableName t where t.user_id=$userId and t.alert_time>='".addslashes("$fromTime 00:00:00")."'
		        and t.alert_time<='" . addslashes("$toTime 23:59:59") . "'";
	    $sql .= !empty($info['keyword']) ? " and (alert_subject like '%".addslashes($info['keyword'])."%' or alert_message like '%".addslashes($info['keyword'])."%')" : "";
	    $sql .= " order by alert_time DESC";
	    
	    // pagination setup
	    $this->db->query($sql, true);
	    $this->paging->setDivClass('pagingdiv');
	    $this->paging->loadPaging($this->db->noRows, SP_PAGINGNO);
	    $pagingDiv = $this->paging->printPages($this->pgScriptPath, 'listform', 'scriptDoLoadPost', 'content', '' );
	    $this->set('pagingDiv', $pagingDiv);
	    $sql .= " limit ".$this->paging->start .",". $this->paging->per_page;
	    
	    $alertList = $this->db->select($sql);
	    $this->set('pageNo', $info['pageno']);
	    $this->set('pgScriptPath', $this->pgScriptPath);
	    $this->set('post', $info);
	    $this->set('list', $alertList);
	    $this->set('alertCategory', $this->alertCategory);
	    $this->render('alerts/alert_list');
	}
	
	/**
	 * function to delete alert
	 */
	function deleteAlert($alertId) {
	    $sql = "delete from $this->tableName where id=".intval($alertId);
	    $this->db->query($sql);
	}
	
	function showAlertInfo($alertId) {
	    $alertInfo = $this->__getAlertInfo($alertId);
	    $this->set('listInfo', $alertInfo);
	    $this->set('alertCategory', $this->alertCategory);
	    $this->render('alerts/alert_info');
	}
	
	function fetchAlerts($info) {
	    $userId = isLoggedIn();
	    if(isset($info['view'])) {
	        
	        if(!empty($info["view"])) {
	            $sql = "UPDATE $this->tableName SET visited=1 WHERE visited=0 and user_id=$userId";
	            $this->db->query($sql);
	        }
	        
	        $alertList = $this->__getAllAlerts("and user_id=$userId order by visited ASC, alert_time DESC limit " . SP_PAGINGNO);
	        $this->set('alertList', $alertList);
	        $output = $this->getViewContent('alerts/alert_box');
	        
	        $sql = "SELECT count(*) count FROM $this->tableName WHERE visited=0 and user_id=$userId";
	        $countInfo = $this->db->select($sql, true);
	        $data = array(
	            'notification' => $output,
	            'unseen_notification'  => $countInfo['count'],
	        );
	        
	        echo json_encode($data);
	    }
	    
	}
	
	function createAlert($alertInfo, $userId = false, $adminAlert = false) {
		
		if (!$userId && $adminAlert) {
			$userCtler = new UserController();
			$adminInfo = $userCtler->__getAdminInfo();
			$userId = $adminInfo['id'];
		}
		
		$dataList = array(
			'user_id|int' => $userId,
			'alert_subject' => $alertInfo['alert_subject'],
			'alert_message' => $alertInfo['alert_message'],
			'alert_url' => !empty($alertInfo['alert_url']) ? $alertInfo['alert_url'] : "",
			'alert_time' => date("Y-m-d H:i:s"),
		);
		
		if (!empty($alertInfo['alert_category'])) {
			$dataList['alert_category'] = $alertInfo['alert_category'];
		}
		
		if (!empty($alertInfo['alert_type'])) {
			$dataList['alert_type'] = $alertInfo['alert_type'];
		}
		
		// check whether alert already exists
		$cond = "user_id=" . intval($userId) . " and alert_subject='".addslashes($dataList['alert_subject'])."'";
		$cond .= " and alert_message='".addslashes($dataList['alert_message'])."'";
		$cond .= " and date(alert_time)='".date('Y-m-d')."'";
		$existInfo = $this->dbHelper->getRow($this->tableName, $cond, "id");
		
		// if alert not exists
		if (empty($existInfo['id'])) {		
			$this->dbHelper->insertRow($this->tableName, $dataList);
		}
	}
	
}
?>
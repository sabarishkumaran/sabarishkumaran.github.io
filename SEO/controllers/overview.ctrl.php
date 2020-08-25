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

/* class defines all overview controller functions */
class OverviewController extends Controller {
    var $baseUrl = "overview.php"; 
	
	function showOverView($searchInfo = '') {		
	    $userId = isLoggedIn();
	    $websiteCtrler = New WebsiteController();
	    $websiteList = $websiteCtrler->__getAllWebsites($userId, true);
	    
	    if (empty($websiteList)) {
	        showErrorMsg($_SESSION['text']['common']['nowebsites']);
	    }
	    
	    $this->set('siteList', $websiteList);
	    $websiteId = isset($searchInfo['website_id']) ? intval($searchInfo['website_id']) : $websiteList[0]['id'];
	    $this->set('websiteId', $websiteId);
	    
	    $fromTime = !empty($searchInfo['from_time']) ? addslashes($searchInfo['from_time']) : date('Y-m-d', strtotime('-2 days'));
	    $toTime = !empty($searchInfo['to_time']) ? addslashes($searchInfo['to_time']) : date('Y-m-d', strtotime('-1 days'));
	    $this->set('fromTime', $fromTime);
	    $this->set('toTime', $toTime);
	    
		$this->set("custSubMenu", "overview");
		$this->render('user/userhome');
	}
	
	function showPageOverview($websiteId, $fromDate, $toDate) {
	    $keywordController = new KeywordController();
	    $seLIst = $keywordController->getUserKeywordSearchEngineList("", $websiteId);
	    
	    if (empty($seLIst)) {
	        showErrorMsg($_SESSION['text']['common']['No Records Found']);
	    }
	    
	    $this->set("seList", $seLIst);
	    $pageOVUrl = SP_WEBPATH . "/$this->baseUrl?sec=page-overview-data&website_id=$websiteId&from_time=$fromDate&to_time=$toDate";
	    $this->set("pageOVUrl", $pageOVUrl);
	    $this->render('report/page_overview');
	}
	
	function showPageOverviewData($seachInfo) {
	    $websiteId = intval($seachInfo['website_id']);
	    $seId = intval($seachInfo['se_id']);
	    
	    $conditions = !empty($seachInfo['from_time']) ? " and sr.result_date>='".addslashes($seachInfo['from_time'])."'" : "";
	    $conditions .= !empty($seachInfo['to_time']) ? " and sr.result_date<='".addslashes($seachInfo['to_time'])."'" : "";
	    
		$sql = "select * from (
                    select distinct srd.url, sr.rank,sr.result_date, sr.keyword_id, k.name as keyword 
                    from searchresults sr, searchresultdetails srd, keywords k 
                    where sr.id=srd.searchresult_id and sr.keyword_id=k.id and k.website_id=$websiteId and sr.searchengine_id=$seId $conditions
                    order by rank asc, result_date DESC
                ) as p group by p.url limit " . SP_PAGINGNO;
		
		$pageResultList = $this->db->select($sql);
		$this->set("pageResultList", $pageResultList);
		$this->render('report/page_overview_data');
	}
	
	function showKeywordOverview($websiteId, $fromDate, $toDate) {
	    $keywordController = new KeywordController();
	    $seLIst = $keywordController->getUserKeywordSearchEngineList("", $websiteId);
	    
	    if (empty($seLIst)) {
	        showErrorMsg($_SESSION['text']['common']['No Records Found']);
	    }
	    
	    $this->set("seList", $seLIst);
	    $keywordOVUrl = SP_WEBPATH . "/$this->baseUrl?sec=keyword-overview-data&website_id=$websiteId&from_time=$fromDate&to_time=$toDate";
	    $this->set("keywordOVUrl", $keywordOVUrl);
	    $this->render('report/keyword_overview');
	}
	
	function showKeywordOverviewData($seachInfo) {
	    $websiteId = intval($seachInfo['website_id']);
	    $seId = intval($seachInfo['se_id']);
	    
	    $conditions = !empty($seachInfo['from_time']) ? " and sr.result_date>='".addslashes($seachInfo['from_time'])."'" : "";
	    $conditions .= !empty($seachInfo['to_time']) ? " and sr.result_date<='".addslashes($seachInfo['to_time'])."'" : "";
	    
	    $sql = "select * from (
                    select distinct sr.keyword_id, srd.url, sr.rank,sr.result_date, k.name as keyword
                    from searchresults sr, searchresultdetails srd, keywords k
                    where sr.id=srd.searchresult_id and sr.keyword_id=k.id and k.website_id=$websiteId and sr.searchengine_id=$seId $conditions
                    order by rank asc, result_date DESC
                ) as p group by p.keyword_id limit " . SP_PAGINGNO;
	    
	    $keywordResultList = $this->db->select($sql);
	    $this->set("keywordResultList", $keywordResultList);
	    $this->render('report/keyword_overview_data');
	}
	
}
?>
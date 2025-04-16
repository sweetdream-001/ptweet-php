<?php 
# @*************************************************************************@
# @ Software author: JOOJ Team (JOOJ.us)							@
# @ Author_url 1: https://jooj.us                       @
# @ Author_url 2: http://jooj.us/twitter-clone                      @
# @ Author E-mail: sales@jooj.us                                    @
# @*************************************************************************@
# @ JOOJ Talk - The Ultimate Modern Social Media Sharing Platform           @
# @ Copyright (c) 2020 - 2023 JOOJ Talk. All rights reserved.               @
# @*************************************************************************@

function cl_get_guest_feed($offset = false, $limit = 3000) {
	global $db, $cl;

    if (isset($_GET['ad_stat']) && $_GET['ad_stat'] == 1) {
        $ad_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    }
	$data        = array();
	$sql         = cl_sqltepmlate("apps/feed/sql/fetch_feed", array(
		"t_pubs" => T_PUBS,
		"offset" => $offset,
		"limit"  => $limit
 	));

	$query_res = $db->rawQuery($sql);
    $counter   = 0;

	if (cl_queryset($query_res)) {

        if ($cl['config']['advertising_system'] == 'on') {
            if (cl_is_feed_nad_allowed()) {
                $ad = cl_get_timeline_ads();
                if (not_empty($ad)) {
                    $data[] = $ad;
                }
            }
        }
        
		foreach ($query_res as $row) {
            $counter += 1;
            $postData = cl_post_data($row);
			$data[] = $postData;

            if ($cl['config']['advertising_system'] == 'on') {
                if (cl_is_feed_nad_allowed()) {
                    if ($counter == 12) {
                        $counter = 0;
                        $ad      = cl_get_timeline_ads();

                        if (not_empty($ad)) {
                            $data[] = $ad;
                        }
                    }
                }
            }
		}
       
        if ($cl['config']['advertising_system'] == 'on') {
            if (cl_is_feed_nad_allowed()) {
        	    if(!empty($ad_id)) {
                    $ad = cl_get_timeline_ads($ad_id);
                } else {
                    $ad = cl_get_timeline_ads();
                }
                if (not_empty($ad)) {
                    $data[] = $ad;
                }
            }
        }
	}

    $data = array_reverse($data);
	return $data;
}
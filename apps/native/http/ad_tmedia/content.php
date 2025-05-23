<?php 
# @*************************************************************************@
# @ Software author: JOOJ Team (JOOJ.us)                                    @
# @ Author_url 1: https://jooj.us                                           @
# @ Author_url 2: http://jooj.us/twitter-clone                              @
# @ Author E-mail: sales@jooj.us                                            @
# @*************************************************************************@
# @ JOOJ Talk - The Ultimate Modern Social Media Sharing Platform           @
# @ Copyright (c) 2020 - 2021 JOOJ Talk. All rights reserved.               @
# @*************************************************************************@

// RESTRICT USER CODE
$visitor_ip = $_SERVER['REMOTE_ADDR'];
cl_check_ip_restriction($visitor_ip);

require_once(cl_full_path("core/apps/ad_thread/app_ctrl.php"));

$thread_id         = fetch_or_get($_GET["ad_id"], false);
$thread_id         = cl_text_secure($thread_id);
$cl['thread_data'] = cl_get_thread_data($thread_id);
// $cl['current_ad']  = cl_get_timeline_ads($thread_id);
$cl['random_ads_main']  = array();
if (empty($cl['thread_data']['post'])) {
	cl_redirect("404");
}
$cl['random_ads_main'] = cl_get_random_ads($thread_id);
// array_unshift($cl['random_ads'], $cl['current_ad']);  // prepend
$cl["right_sidebar"]  = cl_template('main/no_sidebar');
$cl["page_title"] = cl_translate("post_seo_title", array("user_name" => $cl['thread_data']['post']['owner']['name'], "site_name" => $cl["config"]["name"], "post_url" => $cl['thread_data']['post']["url"]));
$cl["page_desc"]  = $cl['thread_data']['post']['og_text'];
$cl["page_kw"]    = $cl["config"]["keywords"];
$cl["page_image"] = $cl['thread_data']['post']['og_image'];
$cl["page_url"]   = cl_link(cl_strf("ad_tmedia/%d", $thread_id));
$cl["pn"]         = "ad_thread";
$cl["sbr"]        = true;
$cl["sbl"]        = true;

// $cl['thread_data']['parent'] = cl_get_thread_parent_posts($cl['thread_data']['post']);

if (not_empty($cl['thread_data']['parent'])) {
	$cl['thread_data']['parent'] = array_reverse($cl['thread_data']['parent']);
}

$cl["http_res"] = cl_template("ad_thread/media");



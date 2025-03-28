<?php 
# @*************************************************************************@
# @ Software author: JOOJ Team (JOOJ.us)                           @
# @ Author_url 1: https://jooj.us                       @
# @ Author_url 2: http://jooj.us/twitter-clone                      @
# @ Author E-mail: sales@jooj.us                                   @
# @*************************************************************************@
# @ JOOJ Talk - The Ultimate Modern Social Media Sharing Platform           @
# @ Copyright (c) 2020 - 2021 JOOJ Talk. All rights reserved.               @
# @*************************************************************************@

require_once(cl_full_path("core/apps/cpanel/ads/app_ctrl.php"));

// Determine the number of ads to display per page
$limit = (isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0) 
         ? intval($_GET['limit']) : 10;

// Determine the current page number from the URL; default is 1
$page  = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) 
         ? intval($_GET['page']) : 1;

// Calculate the offset for the query based on the current page
$offset = ($page - 1) * $limit;

// Get the search term from the URL if available
$search = $_GET['search'] ?? '';

// Build the SQL query to count matching ads
$sql_count = "
    SELECT COUNT(a.id) as count 
    FROM " . T_ADS . " a
    INNER JOIN " . T_USERS . " u ON a.user_id = u.id
    WHERE a.status != 'orphan'
      AND a.target = 'ad'
      AND u.active = '1'
";

if (!empty($search)) {
    // Use cl_text_secure() to properly escape the search term
    $sql_count .= " AND a.company LIKE '%" . cl_text_secure($search) . "%'";
}

// Execute the count query (assuming $db->rawQueryOne returns a single row as an associative array)
$count_result = $db->rawQueryOne($sql_count);
$total_ads = $count_result['count'];

// Calculate the total number of pages based on the count and the limit per page
$total_pages = ceil($total_ads / $limit);

// Now fetch the actual ads using your existing function (which should use the same filters)
$cl['user_ads']     = cl_admin_get_user_ads(array('limit' => $limit, 'offset' => $offset));
$cl['current_page'] = $page;
$cl['total_pages']  = $total_pages;

// Render your template
$cl['http_res']     = cl_template("cpanel/assets/manage_ads/content");
?>
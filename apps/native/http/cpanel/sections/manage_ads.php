<?php
# @*************************************************************************@
# @ Software author: JOOJ Team (JOOJ.us)                           @
# @ Author_url: https://jooj.us                                    @
# @ Copyright (c) 2020 - 2021 JOOJ Talk. All rights reserved.      @
# @*************************************************************************@

require_once(cl_full_path("core/apps/cpanel/ads/app_ctrl.php"));

// Get regular limit from URL parameter or use default
$regular_limit = (isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0) 
                ? intval($_GET['limit']) : 10;

// Get the search term from the URL if available
$search = isset($_GET['search']) ? $_GET['search'] : '';

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
    $sql_count .= " AND a.company LIKE '%" . cl_text_secure($search) . "%'";
}

// Execute the count query
$count_result = $db->rawQueryOne($sql_count);
$total_ads = $count_result['count'];

// Calculate first page limit as remainder to make all other pages full
$first_page_limit = $total_ads % $regular_limit;
// If remainder is 0, use regular limit for first page too
if ($first_page_limit == 0) {
    $first_page_limit = $regular_limit;
}

// Calculate total pages
$total_pages = ceil($total_ads / $regular_limit);

// Get current page
$current_page = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) 
                ? intval($_GET['page']) : 1;
                
// CRITICAL FIX: Calculate limit and offset for current page
if ($current_page == 1) {
    $limit = $first_page_limit;
    $offset = 0;
} else {
    $limit = $regular_limit;
    // Fixed offset calculation
    $offset = $first_page_limit + ($current_page - 2) * $regular_limit;
}

// Store values in global array for use in templates
$cl['total_ads'] = $total_ads;
$cl['first_page_limit'] = $first_page_limit;
$cl['regular_limit'] = $regular_limit;
$cl['user_ads'] = cl_admin_get_user_ads([
    'limit' => $limit,
    'offset' => $offset,
    'search' => $search
]);
$cl['current_page'] = $current_page;
$cl['total_pages'] = $total_pages;

// Debug info (remove in production)
// echo "Total: $total_ads, First limit: $first_page_limit, Regular limit: $regular_limit<br>";
// echo "Current page: $current_page, Limit: $limit, Offset: $offset<br>";

// Render your template
$cl['http_res'] = cl_template("cpanel/assets/manage_ads/content");
?>

<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

use Tygh\Registry;
use Tygh\Enum\UsergroupTypes;
use Tygh\Languages\Helper as LanguageHelper;
use Tygh\Languages\Languages;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suffix = '';

    fn_trusted_vars(
        'department_data'
    );

    // 
    // Update/Delete department/departments
    // 

    if ($mode == 'update_department') {
        $department_id = !empty($_REQUEST['department_id']) ? $_REQUEST['department_id'] : 0;
        $data = !empty($_REQUEST['department_data']) ? $_REQUEST['department_data'] : [];

        $department_id = fn_update_department($data, $department_id);

        if ($department_id) {
            // $suffix = ".update_department?department_id={$department_id}";
            $suffix = ".update_department?department_id={$department_id}";
        } else {
            $suffix = ".add_department";
        }

        // fn_print_die($_REQUEST);

    } elseif ($mode == 'update_departments') {

        fn_print_die($_REQUEST);

    } elseif ($mode == 'delete_department') {

        $department_id = !empty($_REQUEST['department_id']) ? $_REQUEST['department_id'] : 0;
        fn_delete_department($department_id);

        $suffix = '.manage_departments';

    } elseif ($mode == 'delete_departments') {

        fn_print_die($_REQUEST);

    }


    //
    // Create/Update usergroups
    //
    if ($mode == 'update') {

        $usergroup_id = fn_update_usergroup($_REQUEST['usergroup_data'], $_REQUEST['usergroup_id'], DESCR_SL);

        if ($usergroup_id == false) {
            fn_delete_notification('changes_saved');
        }

        $suffix .= '.manage';
    }

    //
    // Delete selected usergroups
    //
    if ($mode == 'm_delete') {
        if (!empty($_REQUEST['usergroup_ids'])) {
            fn_delete_usergroups($_REQUEST['usergroup_ids']);
        }

        $suffix .= '.manage';
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['usergroup_ids'])
        && is_array($_REQUEST['usergroup_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = (string) $_REQUEST['status'];

        foreach ($_REQUEST['usergroup_ids'] as $usergroup_id) {
            fn_tools_update_status([
                'table'             => 'usergroups',
                'status'            => $status_to,
                'id_name'           => 'usergroup_id',
                'id'                => $usergroup_id,
                'show_error_notice' => false
            ]);
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('usergroups.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if ($mode == 'bulk_update_status') {
        if (!empty($_REQUEST['link_ids'])) {
            $new_status = $action == 'approve' ? 'A' : 'D';
            db_query("UPDATE ?:usergroup_links SET status = ?s WHERE link_id IN(?n)", $new_status, $_REQUEST['link_ids']);

            $force_notification = fn_get_notification_rules($_REQUEST);
            if (!empty($force_notification['C'])) {
                $usergroup_links = db_get_hash_multi_array("SELECT * FROM ?:usergroup_links WHERE link_id IN(?n)", array('user_id', 'usergroup_id'), $_REQUEST['link_ids']);
                foreach ($usergroup_links as $u_id => $val) {
                    fn_send_usergroup_status_notification($u_id, array_keys($val), $new_status);
                }
            }
        }

        $suffix = ".requests";
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['usergroup_id'])) {
            fn_delete_usergroups((array) $_REQUEST['usergroup_id']);
        }

        return array(CONTROLLER_STATUS_REDIRECT, 'usergroups.manage');

    }

    if ($mode == 'update_status') {

        $user_data = fn_get_user_info($_REQUEST['user_id']);

        $group_type = db_get_field("SELECT type FROM ?:usergroups WHERE usergroup_id = ?i", $_REQUEST['id']);

        if (empty($group_type) || ($group_type == 'A' && !in_array($user_data['user_type'], array('A','V')))) {
            fn_set_notification('E', __('error'), __('access_denied'));
            exit;
        }

        $old_status = db_get_field("SELECT status FROM ?:usergroup_links WHERE user_id = ?i AND usergroup_id = ?i", $_REQUEST['user_id'], $_REQUEST['id']);

        $result = fn_change_usergroup_status($_REQUEST['status'], $_REQUEST['user_id'], $_REQUEST['id'], fn_get_notification_rules($_REQUEST));
        if ($result) {
            fn_set_notification('N', __('notice'), __('status_changed'));
        } else {
            fn_set_notification('E', __('error'), __('error_status_not_changed'));
            Tygh::$app['ajax']->assign('return_status', empty($old_status) ? 'F' : $old_status);
        }

        exit;
    }

    return array(CONTROLLER_STATUS_OK, 'usergroups' . $suffix);
}

$exclude_groups = [];

if (!fn_check_current_user_access('manage_admin_usergroups')) {
    $exclude_groups = [UsergroupTypes::TYPE_ADMIN];
}
$usergroup_types = UsergroupTypes::getList($exclude_groups);

if ($mode === 'manage') {
    $exclude_types = $exclude_groups;

    if (fn_allowed_for('ULTIMATE')) {
        $customer_usergroups = fn_get_usergroups(['exclude_types' => $exclude_types, 'type' => UsergroupTypes::TYPE_CUSTOMER], DESCR_SL);
        $exclude_types[] = UsergroupTypes::TYPE_CUSTOMER;
    }

    $usergroups = fn_get_usergroups(['exclude_types' => $exclude_types], DESCR_SL);

    if (fn_allowed_for('ULTIMATE')) {
        $usergroups = array_merge($usergroups, $customer_usergroups);
    }
    $privileges_data = (array) fn_get_usergroup_privileges(['type' => UsergroupTypes::TYPE_ADMIN]);
    $grouped_privileges = fn_group_usergroup_privileges($privileges_data);

    Tygh::$app['view']->assign(array(
        'usergroups'         => $usergroups,
        'usergroup_types'    => $usergroup_types,
        'grouped_privileges' => $grouped_privileges,
    ));

    Registry::set('navigation.tabs', array (
        'general_0' => array (
            'title' => __('general'),
            'js' => true
        ),
    ));

} elseif ($mode == 'update') {

    $usergroup_id = isset($_REQUEST['usergroup_id']) ? $_REQUEST['usergroup_id'] : null;
    $usergroups = fn_get_usergroups(array('usergroup_id' => $usergroup_id), DESCR_SL);
    $usergroup = $usergroups[$usergroup_id];

    Tygh::$app['view']->assign('usergroup', $usergroup);


    $show_privileges_tab = fn_check_can_usergroup_have_privileges($usergroup);

    /* Privilege section */
    /** @var array $auth */
    if (!fn_check_current_user_access('manage_admin_usergroups')) {
        $requested_mtype = db_get_field('SELECT type FROM ?:usergroups WHERE usergroup_id = ?i', $usergroup_id);
        if ($requested_mtype === UsergroupTypes::TYPE_ADMIN) {
            return [CONTROLLER_STATUS_DENIED];
        }
    }

    $usergroup_name = db_get_field('SELECT usergroup FROM ?:usergroup_descriptions WHERE usergroup_id = ?i AND lang_code = ?s', $usergroup_id, DESCR_SL);
    $usergroup_privileges = db_get_hash_single_array('SELECT privilege FROM ?:usergroup_privileges WHERE usergroup_id = ?i', array('privilege', 'privilege'), $usergroup_id);
    $privileges_data = (array) fn_get_usergroup_privileges($usergroup);
    $grouped_privileges = fn_group_usergroup_privileges($privileges_data);

    Tygh::$app['view']->assign([
        'usergroup_privileges' => $usergroup_privileges,
        'usergroup_name'       => $usergroup_name,
        'grouped_privileges'   => $grouped_privileges,
        'usergroup_types'      => $usergroup_types,
        'show_privileges_tab'  => $show_privileges_tab,
    ]);

    Registry::set('navigation.tabs', [
        'general_' . $usergroup_id => [
            'title' => __('general'),
            'js' => true,
        ],
    ]);

} elseif ($mode == 'requests') {

    list($requests, $search) = fn_get_usergroup_requests($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign('usergroup_requests', $requests);
    Tygh::$app['view']->assign('search', $search);
}
if ($mode === 'get_privileges') {
    $usergroup = [
        'type' => $_REQUEST['type'],
    ];
    $show_privileges_tab = fn_check_can_usergroup_have_privileges($usergroup);
    $grouped_privileges = [];
    if ($show_privileges_tab) {
        $privileges_data = (array) fn_get_usergroup_privileges($usergroup);
        $grouped_privileges = fn_group_usergroup_privileges($privileges_data);
    }
    Tygh::$app['view']->assign('grouped_privileges', $grouped_privileges);
    Tygh::$app['view']->assign('id', 0);
    Tygh::$app['view']->assign('show_privileges_tab', $usergroup['type'] !== UsergroupTypes::TYPE_CUSTOMER);
    Tygh::$app['view']->display('views/usergroups/components/get_privileges.tpl');
    return [CONTROLLER_STATUS_NO_CONTENT];

} elseif ($mode == 'add_department' || $mode == 'update_department') {

    // fn_print_die('end');
    $department_id = !empty($_REQUEST['department_id']) ? $_REQUEST['department_id'] : 0;
    $department_data = fn_get_department_data($department_id, DESCR_SL);

    if (empty($department_data) && $mode == 'update') {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    Tygh::$app['view']->assign([
        'department_data' => $department_data,
        'manager_info' => !empty($department_data['manager_id']) ? fn_get_user_short_info($department_data['manager_id']) : [],

    ]);

} elseif ($mode == 'manage_departments') {

    list($departments, $search) = fn_get_departments($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    // $page = $search['page'];
    // $valid_page = db_get_valid_page($page, $search['items_per_page'], $search['total_items']);

    // if ($page > $valid_page) {
    //     $_REQUEST['page'] = $valid_page;
    //     return [CONTROLLER_STATUS_REDIRECT, Registry::get('config.current_url')];
    // }
    // $has_select_permission = fn_check_permissions('products', 'm_delete', 'admin')
    //     || fn_check_permissions('products', 'export_range', 'admin');

    Tygh::$app['view']->assign('departments', $departments);
    Tygh::$app['view']->assign('search', $search);


    // fn_print_die('end');

}

function fn_get_department_data ($department_id = 0, $lang_code = CART_LANGUAGE) 
{
    $department = [];

    if(!empty($department_id)) {
        list($departments) = fn_get_departments([
            'department_id' => $department_id,
        ], 1, $lang_code);

        if (!empty($departments)) {
            $department = reset($departments);
            $department['users_ids'] = fn_department_get_users($department['department_id']);
        }
    }

    return $department;
}

function fn_get_departments($params = array(),$items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    // Set default values to input params
    $default_params = array(
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    if (AREA == 'C') {
        $params['status'] = 'A';
    }

    $sortings = array(
        'timestamp' => '?:departments.timestamp',
        'name' => '?:department_descriptions.department',
        'status' => '?:departments.status',
    );

    $condition = $limit = $join = '';

    if (!empty($params['limit'])) {
        $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
    }

    $sorting = db_sort($params, $sortings, 'name', 'asc');

    if (!empty($params['item_ids'])) {
        $condition .= db_quote(' AND ?:departments.department_id IN (?n)', explode(',', $params['item_ids']));
    }

    if (!empty($params['department_id'])) {
        $condition .= db_quote(' AND ?:departments.department_id = ?i', $params['department_id']);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND ?:departments.status = ?s', $params['status']);
    }

    // if (!empty($params['period']) && $params['period'] != 'A') {
    //     list($params['time_from'], $params['time_to']) = fn_create_periods($params);
    //     $condition .= db_quote(' AND (?:departments.timestamp >= ?i AND ?:departments.timestamp <= ?i)', $params['time_from'], $params['time_to']);
    // }

    $fields = array (
        '?:departments.*',
        '?:department_descriptions.department',
        '?:department_descriptions.description',
    );

    $join .= db_quote(' LEFT JOIN ?:department_descriptions ON ?:department_descriptions.department_id = ?:departments.department_id AND ?:department_descriptions.lang_code = ?s', $lang_code);
  
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:departments $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $departments = db_get_hash_array(
        "SELECT ?p FROM ?:departments " .
        $join .
        "WHERE 1 ?p ?p ?p",
        'department_id', implode(', ', $fields), $condition, $sorting, $limit
    );

    $departments_image_ids = array_keys($departments);
    $images = fn_get_image_pairs($departments_image_ids, 'department', 'M', true, false, $lang_code);

    foreach ($departments as $department_id => $department) {
        $departments[$department_id]['main_pair'] = !empty($images[$department_id]) ? reset($images[$department_id]) : array();
    }

    return array($departments, $params);
}
function fn_add_department($data, $lang_code = DESCR_SL) {
    
}
function fn_update_department($data, $department_id, $lang_code = DESCR_SL)
{
    if (isset($data['timestamp'])) {
        $data['timestamp'] = fn_parse_date($data['timestamp']);
    }

    if (!empty($department_id)) {
        db_query("UPDATE ?:departments SET ?u WHERE department_id = ?i", $data, $department_id);
        db_query("UPDATE ?:department_descriptions SET ?u WHERE department_id = ?i AND lang_code = ?s", $data, $department_id, $lang_code);
    } else {
        $department_id = $data['department_id'] = db_replace_into('departments', $data);
        
        db_query("REPLACE INTO ?:departments ?e", $data);

        foreach (Languages::getAll() as $data['lang_code'] => $v) {
            db_query("REPLACE INTO ?:department_descriptions ?e", $data);
        }
    }

    if (!empty($department_id)) {
        fn_attach_image_pairs('department', 'department', $department_id, $lang_code);
    }



    $users_ids = !empty($_REQUEST['department_data']['users_ids']) ? $_REQUEST['department_data']['users_ids'] : [];

    // fn_print_die($data, $_REQUEST);

    fn_department_delete_users($department_id);
    fn_department_add_users($department_id, $users_ids);

    return $department_id;
}

function fn_delete_department ($department_id) {
    if (!empty($department_id)) {
        $res = db_query("DELETE FROM ?:departments WHERE department_id = ?i", $department_id);
        db_query("DELETE FROM ?department_description WHERE department_id =?i", $department_id);

    }
}

function fn_department_delete_users ($department_id) {
    db_query("DELETE FROM ?:department_users WHERE department_id = ?i", $department_id);
}

function fn_department_add_users($department_id, $users_ids) {
    if (!empty($department_id)) {

        $users_ids = explode(',', $users_ids);

        foreach ($users_ids as $user_id) {
            db_query("REPLACE INTO ?:department_users ?e", [
                'department_id' => $department_id,
                'user_id' => $user_id,
            ]);
        }
    }
}

function fn_department_get_users($department_id) {
    return !empty($department_id) ? db_get_fields('SELECT `user_id` FROM `?:department_users` WHERE `department_id` = ?i', $department_id) : [];
}
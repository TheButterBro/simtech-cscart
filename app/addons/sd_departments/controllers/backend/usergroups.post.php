<?php

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
    // Update/Delete departments
    // 

    if ($mode === 'update_department') {
        $department_id = !empty($_REQUEST['department_id']) ? $_REQUEST['department_id'] : 0;
        $data = !empty($_REQUEST['department_data']) ? $_REQUEST['department_data'] : [];

        if ($department_id) {
            $suffix = ".update_department?department_id={$department_id}";
        } else {
            $suffix = '.manage_departments';
        }

        $department_id = fn_update_department($data, $department_id);

    } elseif ($mode === 'delete_department') {

        $department_id = !empty($_REQUEST['department_id']) ? $_REQUEST['department_id'] : 0;
        fn_delete_department($department_id);

        $suffix = '.manage_departments';

    }
    return [CONTROLLER_STATUS_OK, 'usergroups' . $suffix];
}

if ($mode === 'add_department' || $mode === 'update_department') {
    $department_id = !empty($_REQUEST['department_id']) ? $_REQUEST['department_id'] : 0;
    $department_data = fn_get_department_data($department_id, DESCR_SL);

    if (empty($department_data) && $mode == 'update') {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    Tygh::$app['view']->assign([
        'department_data' => $department_data,
        'manager_info' => !empty($department_data['manager_id']) 
            ? fn_get_user_short_info($department_data['manager_id']) 
            : [],
    ]);

} elseif ($mode === 'manage_departments') {

    list($departments, $search) = fn_get_departments(
        $_REQUEST, 
        Registry::get('settings.Appearance.admin_elements_per_page'), 
        DESCR_SL
    );

    $page = $search['page'];
    $valid_page = db_get_valid_page($page, $search['items_per_page'], $search['total_items']);

    if ($page > $valid_page) {
        $_REQUEST['page'] = $valid_page;
        return [CONTROLLER_STATUS_REDIRECT, Registry::get('config.current_url')];
    }
    $has_select_permission = fn_check_permissions('products', 'm_delete', 'admin')
        || fn_check_permissions('products', 'export_range', 'admin');

    Tygh::$app['view']->assign('departments', $departments);
    Tygh::$app['view']->assign('search', $search);

}


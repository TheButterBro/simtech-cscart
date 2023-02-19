<?php 

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Tools\Url;

if ($mode === 'departments') {
    // Save current url to session for 'Continue shopping' button
    Tygh::$app['session']['continue_url'] = 'profiles.departments';

    $params = $_REQUEST;

    list($departments, $search) = fn_get_departments($params, Registry::get('settings.Appearance.products_per_page'), CART_LANGUAGE);

    if (isset($search['page']) && ($search['page'] > 1) && empty($departments)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    Tygh::$app['view']->assign('is_selected_filters', !empty($params['features_hash']));

    Tygh::$app['view']->assign('departments', $departments);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('columns', 3);

    fn_add_breadcrumb(__('departments'));

} elseif ($mode === 'department') {

    $department_data = [];
    $department_id = !empty($_REQUEST['department_id']) ? $_REQUEST['department_id'] : 0;
    $department_data = fn_get_department_data($department_id, CART_LANGUAGE);

    if (empty($department_data)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    Tygh::$app['view']->assign('department_data', $department_data);

    fn_add_breadcrumb(__('departments'), 'profiles.departments');
    fn_add_breadcrumb($department_data['department']);

    $params = $_REQUEST;
    $params['extend'] = ['description'];

    if (isset($search['page']) && ($search['page'] > 1) && empty($department) && (!defined('AJAX_REQUEST'))) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    Tygh::$app['view']->assign('users_info', fn_get_users_short_info($department_data['users_ids']));
}
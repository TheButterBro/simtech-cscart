<?php

use Tygh\Languages\Languages;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Acces denied');

/**
 * Get department data
 * 
 * @param int $department_id 
 * @param string $lang_code 
 *
 * @return array $department
 */
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
            $department['manager_info'] = fn_get_user_short_info($department['manager_id']);
        }
    }

    return $department;
}

/**
 * Get departments data
 * 
 * @param array $params
 * @param int $items_per_page 
 * @param string $lang_code 
 *
 * @return array ($departments, $params)
 */
function fn_get_departments($params = [], $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    $cache_key = __FUNCTION__ . md5(serialize(func_get_args()));

    Registry::registerCache(
        $cache_key,
        ['departments', 'departments_description'],
        Registry::cacheLevel('locale_auth'),
        true
    );

    $cache = Registry::get($cache_key);

    // Set default values to input params
    $default_params = [
        'page' => 1,
        'items_per_page' => $items_per_page
    ];

    $params = array_merge($default_params, $params);

    if (AREA == 'C') {
        $params['status'] = 'A';
    }

    $sortings = [
        'timestamp' => '?:departments.timestamp',
        'name' => '?:department_descriptions.department',
        'status' => '?:departments.status',
    ];

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

    if (!empty($params['name'])) {
        $condition .= db_quote(' AND ?:department_descriptions.department LIKE ?s', '%' . $params['name'] . '%');
    }

    if (!empty($params['user_id'])) {
        $condition .= db_quote(' AND ?:departments.department_id = ?i', $params['user_id']);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND ?:departments.status = ?s', $params['status']);
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $condition .= db_quote(
            ' AND (?:departments.timestamp >= ?i AND ?:departments.timestamp <= ?i)', 
            $params['time_from'], 
            $params['time_to']);
    }

    $fields = [
        '?:departments.*',
        '?:department_descriptions.department',
        '?:department_descriptions.description',
    ];

    $join .= db_quote(' LEFT JOIN ?:department_descriptions 
    ON ?:department_descriptions.department_id = ?:departments.department_id 
    AND ?:department_descriptions.lang_code = ?s', $lang_code);

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:departments $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }
        
    if (!empty($cache)) {
        $departments = $cache;
    } else {
        $departments = db_get_hash_array(
            'SELECT ?p FROM ?:departments ' .
            $join .
            'WHERE 1 ?p ?p ?p',
            'department_id', implode(', ', $fields), $condition, $sorting, $limit
        );

        $departments_image_ids = array_keys($departments);
        $images = fn_get_image_pairs($departments_image_ids, 'department', 'M', true, false, $lang_code);

        foreach ($departments as $department_id => $department) {
            $departments[$department_id]['main_pair'] = !empty($images[$department_id]) ? reset($images[$department_id]) : [];
            $departments[$department_id]['manager_info'] = fn_get_user_short_info($department['manager_id']);
        }
        
        if (!empty($departments)) {
            Registry::set($cache_key, $departments);
        }

    }

    return [$departments, $params];
}

/**
 * Create/update department
 * 
 * @param array $data
 * @param int $department_id 
 * @param string $lang_code 
 *
 * @return int $department_id
 */
function fn_update_department($data, $department_id, $lang_code = DESCR_SL)
{
    if (isset($data['timestamp'])) {
        $data['timestamp'] = fn_parse_date($data['timestamp']);
    }
    if (isset($data['upd_timestamp'])) {
        $data['upd_timestamp'] = fn_parse_date($data['upd_timestamp']);
    }

    if (!empty($department_id)) {
        db_query('UPDATE ?:departments SET ?u WHERE department_id = ?i', $data, $department_id);
        db_query('UPDATE ?:department_descriptions SET ?u WHERE department_id = ?i AND lang_code = ?s', $data, $department_id, $lang_code);
    } else {
        $department_id = $data['department_id'] = db_replace_into('departments', $data);

        db_query('REPLACE INTO ?:departments ?e', $data);

        foreach (Languages::getAll() as $data['lang_code'] => $v) {
            db_query('REPLACE INTO ?:department_descriptions ?e', $data);
        }
    }

    if (!empty($department_id)) {
        fn_attach_image_pairs('department', 'department', $department_id, $lang_code);
    }



    $users_ids = !empty($_REQUEST['department_data']['users_ids']) ? $_REQUEST['department_data']['users_ids'] : [];

    fn_department_delete_users($department_id);
    fn_department_add_users($department_id, $users_ids);

    return $department_id;
}

/**
 * Delete department
 * 
 * @param int $department_id 
 *
 * @return void
 */
function fn_delete_department ($department_id) {
    if (!empty($department_id)) {
        db_query('DELETE FROM ?:departments WHERE department_id = ?i', $department_id);
        db_query('DELETE FROM ?:department_descriptions WHERE department_id = ?i', $department_id);
    }
}

/**
 * Delete users associated with the department
 *
 * @param int $department_id 
 *
 * @return void
 */
function fn_department_delete_users ($department_id) {
    db_query('DELETE FROM ?:department_users WHERE department_id = ?i', $department_id);
}

/**
 * Creates users associated with the department
 *
 * @param int $department_id 
 * @param array $users_ids 
 *
 * @return void
 */
function fn_department_add_users($department_id, $users_ids) {
    if (!empty($department_id)) {

        $users_ids = explode(',', $users_ids);

        foreach ($users_ids as $user_id) {
            db_query('REPLACE INTO ?:department_users ?e', [
                'department_id' => $department_id,
                'user_id' => $user_id,
            ]);
        }
    }
}

/**
 * Get list with users ids
 *
 * @param int $department_id 
 *
 * @return array (user_id)
 */
function fn_department_get_users($department_id) {
    return !empty($department_id) ? db_get_fields('SELECT `user_id` FROM `?:department_users` WHERE `department_id` = ?i', $department_id) : [];
}

/**
 * Get list with users short info
 *
 * @param array $users_ids Users identifier
 *
 * @return array (user_id, user_login, company_id, firstname, lastname, email, user_type)
 */
 function fn_get_users_short_info ($users_ids) {
    $condition = db_quote('user_id IN (?n) AND status = ?s', $users_ids, 'A');
    $join = '';
    $group_by = '';
    $fields = ['user_id', 'helpdesk_user_id', 'user_login', 'company_id', 'firstname', 'lastname', 'email', 'user_type', 'password_change_timestamp'];

    /**
     * Actions before getting short user data
     *
     * @param array  $users_ids  Users identifier
     * @param array  $fields    Fields to be retrieved
     * @param string $condition Conditions
     * @param string $join      Joins
     * @param string $group_by  Group by condition
     */
    fn_set_hook('get_user_short_info_pre', $users_ids, $fields, $condition, $join, $group_by);

    $fields = implode(', ', $fields);

    return db_get_array('SELECT ?p FROM ?:users ?p WHERE ?p ?p', $fields, $join, $condition, $group_by);
}
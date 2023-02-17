{** departments section **}

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" id="departments_form" name="departments_form" enctype="multipart/form-data">
<input type="hidden" name="fake" value="1" />
{include hidden=true file="common/pagination.tpl" save_current_page=true save_current_url=true div_id="pagination_contents_departments" show_pagination_open=false}

{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

{$rev=$smarty.request.content_id|default:"pagination_contents_departments"}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
{$department_statuses=""|fn_get_default_statuses:true}
{$has_permission = fn_check_permissions("departments", "update_status", "admin", "POST")}

{if $departments}
    {capture name="departments_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table class="table table-middle table--relative table-responsive">
            <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
            <tr>

                <th width="25%">{__("image")}</th>
                <th><a class="cm-ajax" href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("name")}{if $search.sort_by === "name"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=timestamp&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("creation_date")}{if $search.sort_by === "timestamp"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

                {hook name="departments:manage_header"}
                {/hook}

                <th width="6%" class="mobile-hide">&nbsp;</th>
                <th width="10%" class="right"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("status")}{if $search.sort_by === "status"}{$c_icon nofilter}{/if}</a></th>
            </tr>
            </thead>
            {foreach from=$departments item=department}
            <tr class="cm-row-status-{$department.status|lower} cm-longtap-target"
                {if $has_permission}
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$department.department_id}"
                {/if}
            >
                {$allow_save=true}

                {if $allow_save}
                    {$no_hide_input="cm-no-hide-input"}
                {else}
                    {$no_hide_input=""}
                {/if}

                {hook name="departments:manage_data"}
                {/hook}
                
                <td width="25%" class="products-list__image">
                    {include
                            file="common/image.tpl"
                            image=$department.main_pair
                            image_id=$department_data.main_pair.image_id
                            image_width="100%"
                            image_height=$image_height
                            href="products.update?product_id=`$product.product_id`"|fn_url
                            image_css_class="products-list__image--img"
                            link_css_class="products-list__image--link"
                            lazy_load=true
                    }
                </td>
                <td class="{$no_hide_input}" data-th="{__("name")}">
                    <a class="row-status" href="{"usergroups.update_department?department_id=`$department.department_id`"|fn_url}">{$department.department}</a>
                    {include file="views/companies/components/company_name.tpl" object=$department}
                </td>
                <td width="15%" data-th="{__("creation_date")}">
                    {$department.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                </td>

                <td width="6%" class="mobile-hide">
                    {capture name="tools_list"}
                        <li>{btn type="list" text=__("edit") href="usergroups.update_department?department_id=`$department.department_id`"}</li>
                    {if $allow_save}
                        <li>{btn type="list" class="cm-confirm" text=__("delete") href="usergroups.delete_department?department_id=`$department.department_id`" method="POST"}</li>
                    {/if}
                    {/capture}
                    <div class="hidden-tools">
                        {dropdown content=$smarty.capture.tools_list}
                    </div>
                </td>
                <td width="10%" class="right" data-th="{__("status")}">
                    {include file="common/select_popup.tpl" id=$department.department_id status=$department.status hidden=true object_id_name="department_id" table="departments" popup_additional_class="`$no_hide_input` dropleft"}
                </td>
            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="departments_form"
        object="departments"
        items=$smarty.capture.departments_table
        has_permissions=$has_permission
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id="pagination_contents_departments" hidden=true}

{capture name="adv_buttons"}
    {hook name="departments:adv_buttons"}
    {include file="common/tools.tpl" tool_href="usergroups.add_department" prefix="top" hide_tools="true" title=__("add_department") icon="icon-plus"}
    {/hook}
{/capture}

</form>

{/capture}

{capture name="sidebar"}
    {hook name="departments:manage_sidebar"}
    {include file="common/saved_search.tpl" dispatch="usergroups.manage_departments" view_type="departments"}
    {include file="addons/sd_departments/views/usergroups/components/departments_search_form.tpl" dispatch="usergroups.manage_departments"}
    {/hook}
{/capture}

{hook name="departments:manage_mainbox_params"}
    {$page_title = __("departments")}
    {$select_languages = true}
{/hook}

{include 
    file="common/mainbox.tpl" 
    title=$page_title 
    content=$smarty.capture.mainbox 
    adv_buttons=$smarty.capture.adv_buttons 
    select_languages=$select_languages 
    sidebar=$smarty.capture.sidebar
}

{** ad section **}

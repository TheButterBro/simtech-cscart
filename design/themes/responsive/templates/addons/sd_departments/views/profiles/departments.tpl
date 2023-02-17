{if $departments}

    {script src="js/tygh/exceptions.js"}
    

    {if !$no_pagination}
        {include file="common/pagination.tpl"}
    {/if}


    {if !$show_empty}
        {split data=$departments size=$columns|default:"2" assign="splitted_departments"}
    {else}
        {split data=$departments size=$columns|default:"2" assign="splitted_departments" skip_complete=true}
    {/if}

    {math equation="100 / x" x=$columns|default:"2" assign="cell_width"}

    {* FIXME: Don't move this file *}
    {script src="js/tygh/product_image_gallery.js"}

    <div class="grid-list">
        {strip}
            {foreach from=$splitted_departments item="departments"}
                {foreach from=$departments item="department"}
                    <div class="ty-column{$columns}">
                        {if $department}
                            {assign var="obj_id" value=$department.department_id}
                            {assign var="obj_id_prefix" value="`$obj_prefix``$department.department_id`"}
                            
                            <div class="ty-grid-list__item ty-quick-view-button__wrapper">
                                <div class="ty-grid-list__image">
                                    <a href="{"profiles.department?department_id={$department.department_id}"|fn_url}">
                                        {include file="common/image.tpl" 
                                            no_ids=true 
                                            images=$department.main_pair
                                            image_width=$settings.Thumbnails.product_lists_thumbnail_width 
                                            image_height=$settings.Thumbnails.product_lists_thumbnail_height 
                                        }
                                    </a>
                                </div>
                                <div class="ty-grid-list__item-name">
                                    {assign var="name" value="name_$obj_id"}
                                    <bdi>{$smarty.capture.$name nofilter}</bdi>
                                </div>
                                <div>            
                                     <a 
                                        href="{"profiles.department?department_id={$department.department_id}"|fn_url}" 
                                        class="product-title" 
                                        title={$department.department}>
                                            {$department.department}
                                    </a>    
                                </div>
                                <bdi>            
                                    <p title={$department.manager_info.email}>
                                        {$department.manager_info.firstname} {$department.manager_info.lastname}
                                    </p>                       
                                </bdi>
                            </div>
                        {/if}
                    </div>
                {/foreach}
            {/foreach}
        {/strip}
    </div>

    {if !$no_pagination}
        {include file="common/pagination.tpl"}
    {/if}
{else}
    <p class="ty-no-items cm-pagination-container  ">{__("no_data")}</p>
{/if}

{capture name="mainbox_title"}{$title}{/capture}
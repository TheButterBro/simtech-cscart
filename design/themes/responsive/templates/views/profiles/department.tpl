<div id="product_features_{$block.block_id}">

<div class="ty-feature">
    {if $department_data.main_pair}
        <div data class="ty-feature__image">
            {include 
                file="common/image.tpl" 
                images=$department_data.main_pair 

                image_height=280
            }
        </div>
    {/if}
    <div class="ty-feature__description ty-wysiwyg-content">
        {$department_data.description nofilter}
    </div>
</div>

<div class="ty-feature ">
    <div class="control-group">
        <label for="manager_info" class="control-label"><strong>{__("manager")}:</strong></label>
        <div id="manager_info" class="controls">
            <p>{$department_data.manager_info.firstname} {$department_data.manager_info.lastname} <strong>({$department_data.manager_info.email})</strong></p>
        </div>
    </div>
</div>

<div class="ty-feature">
    <div class="control-group">
        <label for="users_info" class="control-label"><strong>{__("workers")}:</strong></label>
        <ul id="users_info" class="controls">
            {foreach from=$users_info item=user}
                <li>{$user.firstname} {$user.lastname} <strong>({$user.email})</strong></li>
            {/foreach}
        </ul>
    </div>
</div>

<!--product_features_{$block.block_id}--></div>
{capture name="mainbox_title"}{$department_data.department nofilter}{/capture}
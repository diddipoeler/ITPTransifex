<?php
/**
 * @package      ITPTransifex
 * @subpackage   Components
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2015 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
?>
<form action="<?php echo JRoute::_('index.php?option=com_itptransifex&view=export'); ?>" method="post" name="adminForm" id="adminForm">
    <?php if(!empty( $this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
    <?php else : ?>
	<div id="j-main-container">
    <?php endif;?>
    
        <div id="filter-bar" class="btn-toolbar">
            <div class="filter-search btn-group pull-left">
    			<label for="filter_search" class="element-invisible"><?php echo JText::_('COM_ITPTRANSIFEX_SEARCH_IN_NAME');?></label>
    			<input type="text" name="filter_search" class="hasTooltip" id="filter_search" placeholder="<?php echo JText::_('COM_ITPTRANSIFEX_SEARCH_IN_NAME'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_ITPTRANSIFEX_SEARCH_IN_PROJECT_TOOLTIP'); ?>" />
    		</div>
    		<div class="btn-group pull-left">
    			<button class="btn hasTooltip" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
    			<button class="btn hasTooltip" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>" id="js-search-filter-clear"><i class="icon-remove"></i></button>
    		</div>
    		<div class="btn-group pull-right hidden-phone">
    			<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
    			<?php echo $this->pagination->getLimitBox(); ?>
    		</div>
    		<div class="btn-group pull-right hidden-phone">
    			<label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC');?></label>
    			<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
    				<option value=""><?php echo JText::_('JFIELD_ORDERING_DESC');?></option>
    				<option value="asc" <?php if ($this->listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING');?></option>
    				<option value="desc" <?php if ($this->listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING');?></option>
    			</select>
    		</div>
    		<div class="btn-group pull-right hidden-phone">
    			<label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY');?></label>
    			<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
    				<option value=""><?php echo JText::_('JGLOBAL_SORT_BY');?></option>
    				<?php echo JHtml::_('select.options', $this->sortFields, 'value', 'text', $this->listOrder);?>
    			</select>
    		</div>
    		
        </div>
        <div class="clearfix"> </div>
    
        <table class="table table-striped" id="exportList">
           <thead><?php echo $this->loadTemplate('head');?></thead>
    	   <tfoot><?php echo $this->loadTemplate('foot');?></tfoot>
    	   <tbody><?php echo $this->loadTemplate('body');?></tbody>
    	</table>

        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="filter_order" value="<?php echo $this->listOrder; ?>" id="filter_order" />
        <input type="hidden" name="filter_order_Dir" value="<?php echo $this->listDirn; ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>

<div class="modal hide fade" id="js-cfe-modal">

    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3><?php echo JText::_("COM_ITPTRANSIFEX_EXPORT_PROJECT");?></h3>
    </div>
    <div class="modal-body">

        <form action="<?php echo JRoute::_('index.php?option=com_itptransifex&task=export.download'); ?>" method="get" name="languageForm" id="languageForm">

            <div class="row-fluid">
                <div class="span12">
                    <div class="control-group">
                        <div class="control-label">
                            <?php echo JText::_("COM_ITPTRANSIFEX_LANGUAGE"); ?>
                        </div>
                        <div class="controls">
                            <?php echo JHtml::_('select.genericlist', $this->languages, 'js-cfe-language'); ?>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <img src="../media/com_itptransifex/images/ajax-loader.gif" width="16" height="16" style="display: none;" id="js-ajaxloader" />
        <a href="#" class="btn btn-primary" id="js-btn-cfe-submit"><?php echo JText::_("COM_ITPTRANSIFEX_SUBMIT");?></a>
        <a href="#" class="btn" id="js-btn-cfe-cancel"><?php echo JText::_("COM_ITPTRANSIFEX_CANCEL");?></a>
    </div>
</div>
<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user      = JFactory::getUser();
$userId    = $user->get('id');
#$listOrder = $this->escape($this->filter_order);
#$listDirn  = $this->escape($this->filter_order_Dir);
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn    = $this->escape($this->state->get('list.direction'));

?>
<form action="<?php echo JRoute::_('index.php?option=com_siteareas&view=siteareas'); ?>" method="post" id="adminForm" name="adminForm">

    <?php if (!empty( $this->sidebar)) : ?>
    <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="span10">
    <?php else : ?>
    <div id="j-main-container">
    <?php endif;?>
        <?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
        <div class="clearfix"> </div>
        <?php if (empty($this->items)) : ?>
        <div class="sitearea sitearea-no-items">
            <?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); #COM_SITEAREAS_NO_RECORDS ?>
        </div>
        <?php else : ?>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th width="2%"><?php echo JText::_('COM_SITEAREAS_NUM'); ?></th>
                    <th width="4%">
                        <?php echo JHtml::_('grid.checkall'); ?>
                    </th>
                    <th width="40%">
                        <?php echo JHtml::_('searchtools.sort', 'COM_SITEAREAS_RECORDS_NAME', 'a.name', $listDirn, $listOrder); ?>
                    </th>
                    <th width="34%">
                        <?php echo JHtml::_('searchtools.sort', 'COM_SITEAREAS_RECORDS_OWNER', 'owner_name', $listDirn, $listOrder); ?>
                    </th>
                    <th width="10%">
                        <?php echo JHtml::_('searchtools.sort', 'COM_SITEAREAS_PUBLISHED', 'a.state', $listDirn, $listOrder); ?>
                    </th>
                    <th width="10%">
                        <?php echo JHtml::_('searchtools.sort', 'COM_SITEAREAS_ID', 'a.id', $listDirn, $listOrder); ?>
                    </th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="6">
                        <?php echo $this->pagination->getListFooter(); ?>
                    </td>
                </tr>
            </tfoot>
            <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <?php $canCreate      = $user->authorise('core.create',     'com_siteareas.' . $item->id); ?>
                <?php $canEdit        = $user->authorise('core.edit',       'com_siteareas.' . $item->id); ?>
                <?php $canCheckin     = $user->authorise('core.manage',     'com_checkin') || $item->checked_out == $user->id || $item->checked_out == 0; ?>
                <?php $canEditOwn     = $user->authorise('core.edit.own',   'com_siteareas.' . $item->id) && $item->created_by == $user->id; ?>
                <?php $canChange      = $user->authorise('core.edit.state', 'com_siteareas.' . $item->id) && $canCheckin; ?>

                <tr>
                    <td><?php echo $this->pagination->getRowOffset($i); ?></td>
                    <td>
                        <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                    </td>
                    <td class="nowrap has-context">
                        <?php if ($item->checked_out) : ?>
                            <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'siteareas.', $canCheckin); ?>
                        <?php endif; ?>
                        <?php if ($canEdit || $canEditOwn) : ?>
                            <a href="<?php echo JRoute::_('index.php?option=com_siteareas&task=sitearea.edit&id=' . (int) $item->id); ?>" title="<?php echo JText::_('COM_SITEAREAS_EDIT_RECORD'); ?>">
                                <?php echo $this->escape($item->name); ?></a>
                        <?php else : ?>
                                <?php echo $this->escape($item->name); ?>
                        <?php endif; ?>
                        <span class="small">
                            <?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                        </span>
                    </td>
                    <td align="center">
                        <a href="mailto:<?php echo $item->owner_email; ?>"><?php echo $item->owner_name; ?></a>
                    </td>
                    <td align="center">
                        <?php echo JHtml::_('jgrid.published', $item->state, $i, 'siteareas.', true, 'cb'); ?>
                    </td>
                    <td align="center">
                        <?php echo $item->id; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>

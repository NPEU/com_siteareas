<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_siteareas
 *
 * @copyright   Copyright (C) NPEU 2019.
 * @license     MIT License; see LICENSE.md
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');

$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->filter_order);
$listDirn  = $this->escape($this->filter_order_Dir);

?>
<form action="index.php?option=com_siteareas&view=records" method="post" id="adminForm" name="adminForm">
    <?php if (!empty( $this->sidebar)) : ?>
    <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="span10">
    <?php else : ?>
    <div id="j-main-container">
    <?php endif;?>

    <div class="row-fluid">
        <div class="span6">
            <?php echo JText::_('COM_SITEAREAS_RECORDS_FILTER'); ?>
            <?php
                echo JLayoutHelper::render(
                    'joomla.searchtools.default',
                    array('view' => $this)
                );
            ?>
        </div>
    </div>
    <?php if (!empty($this->items)): ?>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th width="2%"><?php echo JText::_('COM_SITEAREAS_NUM'); ?></th>
                <th width="4%">
                    <?php echo JHtml::_('grid.checkall'); ?>
                </th>
                <th width="40%">
                    <?php echo JHtml::_('grid.sort', 'COM_SITEAREAS_RECORDS_NAME', 'name', $listDirn, $listOrder); ?>
                </th>
                <th width="40%">
                    <?php echo JHtml::_('grid.sort', 'COM_SITEAREAS_RECORDS_OWNER', 'owner_user_id', $listDirn, $listOrder); ?>
                </th>
                <th width="10%">
                    <?php echo JHtml::_('grid.sort', 'COM_SITEAREAS_PUBLISHED', 'state', $listDirn, $listOrder); ?>
                </th>
                <th width="4%">
                    <?php echo JHtml::_('grid.sort', 'COM_SITEAREAS_ID', 'id', $listDirn, $listOrder); ?>
                </th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="5">
                    <?php echo $this->pagination->getListFooter(); ?>
                </td>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($this->items as $i => $item) :
            $link = JRoute::_('index.php?option=com_siteareas&task=record.edit&id=' . $item->id);
            $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->get('id') || $item->checked_out == 0;
        ?>
            <tr>
                <td><?php echo $this->pagination->getRowOffset($i); ?></td>
                <td>
                    <?php echo JHtml::_('grid.id', $i, $item->id); ?>
                </td>
                <td>
                    <div class="pull-left break-word">
                        <?php if ($item->checked_out) : ?>
                            <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'records.', $canCheckin); ?>
                        <?php endif; ?>
                        <a href="<?php echo $link; ?>" class="hasTooltip" title="<?php echo JText::_('COM_SITEAREAS_EDIT_RECORD'); ?>">
                            <?php echo $item->name; ?>
                        </a>
                        <span class="small">(<?php echo JText::_('COM_SITEAREAS_RECORDS_ALIAS'); ?>: <?php echo $item->alias; ?>)</span>
                    </div>
                </td>
                <td align="center">
                    <?php echo $item->owner_user_id; ?>
                </td>
                <td align="center">
                    <?php echo JHtml::_('jgrid.published', $item->state, $i, 'records.', true, 'cb'); ?>
                </td>
                <td align="center">
                    <?php echo $item->id; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="alert alert-no-items">
        <?php echo JText::_('COM_SITEAREAS_NO_RECORDS'); ?>
    </div>

    <?php endif; ?>
    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="boxchecked" value="0"/>
    <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
    <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
    <?php echo JHtml::_('form.token'); ?>
</form>

<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
?>
<div class="login">
	<?php if ($this->params->get('show_page_heading')) : ?>
	<h4 class="page-header">
		<?php echo $this->escape($this->params->get('page_heading')); ?>
	</h4>
	<?php endif; ?>

	<?php if (($this->params->get('logindescription_show') == 1 && str_replace(' ', '', $this->params->get('login_description')) != '') || $this->params->get('login_image') != '') : ?>
	<div class="login-description">
	<?php endif; ?>

		<?php if ($this->params->get('logindescription_show') == 1) : ?>
			<?php echo $this->params->get('login_description'); ?>
		<?php endif; ?>

		<?php if (($this->params->get('login_image') != '')) :?>
			<img src="<?php echo $this->escape($this->params->get('login_image')); ?>" class="login-image" alt="<?php echo JTEXT::_('COM_USERS_LOGIN_IMAGE_ALT')?>"/>
		<?php endif; ?>

	<?php if (($this->params->get('logindescription_show') == 1 && str_replace(' ', '', $this->params->get('login_description')) != '') || $this->params->get('login_image') != '') : ?>
	</div>
	<?php endif; ?>

	<form action="<?php echo JRoute::_('index.php?option=com_users&task=user.login'); ?>" method="post" class="form-validate">
		<fieldset>
			<?php foreach ($this->form->getFieldset('credentials') as $field) : ?>
				<?php if (!$field->hidden) : ?>
					<div class="form-group">
						<?php echo $field->label; ?><br />
						<?php echo $field->input; ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>

			<?php if ($this->tfa): ?>
				<div class="control-group">
					<div class="control-label">
						<?php echo $this->form->getField('secretkey')->label; ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getField('secretkey')->input; ?>
					</div>
				</div>
			<?php endif; ?>

			<button type="submit" class="btn btn-primary base-icon-login pull-left right-space"> <?php echo JText::_('JLOGIN'); ?> </button>

			<?php if (JPluginHelper::isEnabled('system', 'remember')) : ?>
			<div class="checkbox pull-left">
				<label><input id="remember" type="checkbox" name="remember" class="inputbox" value="yes"/> <?php echo JText::_('COM_USERS_LOGIN_REMEMBER_ME') ?></label>
			</div>
			<?php endif; ?>

			<input type="hidden" name="return" value="<?php echo base64_encode($this->params->get('login_redirect_url', $this->form->getValue('return'))); ?>" />
			<?php echo JHtml::_('form.token'); ?>
			<div class="clear"></div>
			<hr class="hr-sm" />
			<?php
			$usersConfig = JComponentHelper::getParams('com_users');
			if ($usersConfig->get('allowUserRegistration')) :
			?>
				<a class="btn btn-default right-space-sm bottom-space-sm" href="<?php echo JRoute::_('index.php?option=com_users&view=registration&Itemid=' . UsersHelperRoute::getRegistrationRoute()); ?>">
					<span class="base-icon-user-add"></span> <?php echo JText::_('COM_USERS_LOGIN_REGISTER'); ?>
				</a>
			<?php endif; ?>
			<ul class="list-unstyled text-sm no-margin">
				<li class="text-sm">
					<a href="<?php echo JRoute::_('index.php?option=com_users&view=reset&Itemid=' . UsersHelperRoute::getResetRoute()); ?>">
					<?php echo JText::_('COM_USERS_LOGIN_RESET'); ?></a>
				</li>
				<!-- <li>
					<a href="<?php echo JRoute::_('index.php?option=com_users&view=remind&Itemid=' . UsersHelperRoute::getRemindRoute()); ?>">
					<?php echo JText::_('COM_USERS_LOGIN_REMIND'); ?></a>
				</li> -->
			</ul>
		</fieldset>
	</form>
</div>

<?php
defined('_JEXEC') or die;

$query = 'SELECT * FROM '. $db->quoteName('#__'.$cfg['project'].'_providers') .' WHERE state = 1 ORDER BY name';
$db->setQuery($query);
$providers = $db->loadObjectList();

$query = 'SELECT * FROM '. $db->quoteName('#__'.$cfg['project'].'_contacts') .' WHERE state = 1 ORDER BY name';
$db->setQuery($query);
$contacts = $db->loadObjectList();

// FORM
?>
<div class="row">
	<div class="col-sm-10">
		<div class="form-group field-required">
			<label><?php echo JText::_('FIELD_LABEL_PROVIDER'); ?></label>
			<select name="provider_id" id="<?php echo $APPTAG?>-provider_id" class="form-control field-id">
				<option value="0">- <?php echo JText::_('TEXT_SELECT'); ?> -</option>
				<?php
					foreach ($providers as $obj) {
						echo '<option value="'.$obj->id.'">'.baseHelper::nameFormat($obj->name).'</option>';
					}
				?>
			</select>
		</div>
	</div>
	<div class="col-sm-10">
		<div class="form-group field-required">
			<label><?php echo JText::_('FIELD_LABEL_CONTACT'); ?></label>
			<div class="input-group">
				<select name="contact_id" id="<?php echo $APPTAG?>-contact_id" class="form-control field-id">
					<option value="0">- <?php echo JText::_('TEXT_SELECT'); ?> -</option>
					<?php
						foreach ($contacts as $obj) {
							echo '<option value="'.$obj->id.'">'.baseHelper::nameFormat($obj->name).'</option>';
						}
					?>
				</select>
				<span class="input-group-btn">
					<button type="button" onclick="_contacts_setRelation(<?php echo $APPTAG?>rID)" class="base-icon-plus btn btn-success hasTooltip" title="<?php echo JText::_('TEXT_ADD')?>" data-toggle="modal" data-target="#modal-_contacts" data-backdrop="static" data-keyboard="false"></button>
					<button type="button" class="base-icon-pencil btn btn-warning hasTooltip" title="<?php echo JText::_('TEXT_EDIT')?>" onclick="<?php echo $APPTAG?>_editContact()"></button>
				</span>
			</div>
		</div>
	</div>
	<div class="col-sm-6">
		<div class="form-group">
			<label><?php echo JText::_('FIELD_LABEL_DEPARTMENT'); ?></label>
			<input type="text" name="department" id="<?php echo $APPTAG?>-department" class="form-control upper" />
		</div>
	</div>
	<div class="col-sm-4">
		<div class="form-group">
			<label>&#160;</label>
			<div class="btn-group w-100" data-toggle="buttons">
				<label class="btn btn-block btn-warning btn-active-success hasTooltip" title="<?php echo JText::_('FIELD_LABEL_MAIN_DESC'); ?>">
					<span class="base-icon-cancel"></span>
					<input type="checkbox" name="main" id="<?php echo $APPTAG?>-main" value="1" />
					<?php echo JText::_('FIELD_LABEL_MAIN'); ?>
				</label>
			</div>
		</div>
	</div>
</div>

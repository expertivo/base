<?php
defined('_JEXEC') or die;

$query = 'SELECT * FROM '. $db->quoteName($cfg['mainTable'].'_groups') .' WHERE state = 1 ORDER BY name';
$db->setQuery($query);
$grps = $db->loadObjectList();

$query = '
	SELECT T1.id, T1.name, T1.email
	FROM '. $db->quoteName('#__users') .' T1
		LEFT OUTER JOIN '. $db->quoteName($cfg['mainTable']) .' T2
		ON T2.user_id = T1.id
	WHERE T2.name IS NULL
	ORDER BY T1.name
';
$db->setQuery($query);
$users = $db->loadObjectList();

$query = 'SELECT * FROM '. $db->quoteName('#__usergroups') .' WHERE '. $db->quoteName('id') .' NOT IN (1, 3)';
$db->setQuery($query);
$userGrps = $db->loadObjectList();
?>

<form name="form-<?php echo $APPTAG?>" id="form-<?php echo $APPTAG?>" method="post" enctype="multipart/form-data">

	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title">
			<?php
				echo JText::_('FORM_TITLE');
				if($cfg['showFormDesc']) :
					echo '<div class="small font-condensed">'.JText::_('FORM_DESCRIPTION').'</div>';
				endif;
			?>
		</h4>
	</div>
	<div class="modal-body">
		<fieldset>
			<div class="row">
				<div id="<?php echo $APPTAG?>-formPaginator" class="col-sm-4 hide">
					<div class="form-group field-required">
						<span class="input-group">
							<span class="input-group-btn">
								<button id="btn-<?php echo $APPTAG?>-prev" class="base-icon-left-open btn btn-sm btn-default" disabled></button>
							</span>
							<input type="text" name="id" id="<?php echo $APPTAG?>-id" class="form-control input-sm" readonly="readonly" />
							<input type="hidden" name="relationId" id="<?php echo $APPTAG?>-relationId" value="<?php echo $_SESSION[$RTAG.'RelId']?>" />
							<input type="hidden" name="user_id" id="<?php echo $APPTAG?>-user_id" value="0" />
							<input type="hidden" name="<?php echo $APPTAG?>-prev" id="<?php echo $APPTAG?>-prev" />
							<input type="hidden" name="<?php echo $APPTAG?>-next" id="<?php echo $APPTAG?>-next" />
							<span class="input-group-btn">
								<button id="btn-<?php echo $APPTAG?>-next" class="base-icon-right-open btn btn-sm btn-default" disabled></button>
							</span>
						</span>
					</div>
				</div>
				<div id="<?php echo $APPTAG?>-fieldState" class="col-sm-4">
					<div class="form-group">
						<span id="<?php echo $APPTAG?>-state-group" class="btn-group btn-group-justified" data-toggle="buttons">
							<label class="btn btn-sm btn-default btn-active-success strong">
								<span class="base-icon-unset"></span>
								<input type="radio" name="state" id="<?php echo $APPTAG?>-state-1" value="1" />
								<?php echo JText::_('TEXT_ACTIVE'); ?>
							</label>
							<label class="btn btn-sm btn-default btn-active-danger strong">
								<span class="base-icon-unset"></span>
								<input type="radio" name="state" id="<?php echo $APPTAG?>-state-0" value="0" /> <?php echo JText::_('TEXT_INACTIVE'); ?>
							</label>
						</span>
					</div>
				</div>
				<div id="<?php echo $APPTAG?>-restart" class="col-sm-4 hide">
					<div class="form-group">
						<button type="button" id="btn-<?php echo $APPTAG?>-restart" class="base-icon-cw btn btn-sm btn-default btn-block">
							 <?php echo JText::_('TEXT_RESTART'); ?>
						</button>
					</div>
				</div>
			</div>

			<hr class="hr-xs" />

			<div class="row">
				<div class="col-sm-6 pull-right">
					<div id="<?php echo $APPTAG?>-group-relation" class="form-group hide">
						<div class="hidden-xs hidden-sm"><label>&#160;</label></div>
						<span class="btn-group btn-group-justified">
							<a href="#" class="base-icon-location btn btn-warning hasTooltip" onclick="<?php echo $APPTAG?>_viewAddresses()" data-toggle="modal" data-target="#modal-list-addresses" title="<?php echo JText::_('MSG_VIEW_ADDRESS')?>"></a>
							<a href="#" class="base-icon-phone-squared btn btn-warning hasTooltip" onclick="<?php echo $APPTAG?>_viewPhones()" data-toggle="modal" data-target="#modal-list-phones" title="<?php echo JText::_('MSG_VIEW_PHONE')?>"></a>
							<a href="#" class="base-icon-chat-empty btn btn-warning hasTooltip" onclick="<?php echo $APPTAG?>_viewSocials()" data-toggle="modal" data-target="#modal-list-webSocials" title="<?php echo JText::_('MSG_VIEW_SOCIAL')?>"></a>
							<a href="#" class="base-icon-bank btn btn-warning hasTooltip" onclick="<?php echo $APPTAG?>_viewBanks()" data-toggle="modal" data-target="#modal-list-banksAccounts" title="<?php echo JText::_('MSG_VIEW_BANKS_ACCOUNTS')?>"></a>
						</span>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group field-required">
						<label><?php echo JText::_('FIELD_LABEL_GROUP'); ?></label>
						<div class="input-group">
							<select name="group_id" id="<?php echo $APPTAG?>-group_id" class="form-control field-id auto-tab" data-target="<?php echo $APPTAG?>-name">
								<option value="0"><?php echo JText::_('TEXT_SELECT'); ?></option>
								<?php
									foreach ($grps as $obj) {
										echo '<option value="'.$obj->id.'">'.baseHelper::nameFormat($obj->name).'</option>';
									}
								?>
							</select>
							<span class="input-group-btn">
								<button type="button" class="base-icon-plus btn btn-success hasTooltip" title="<?php echo JText::_('TEXT_ADD')?>" data-toggle="modal" data-target="#modal-<?php echo $APPTAG?>Groups" data-backdrop="static" data-keyboard="false"></button>
								<button type="button" class="base-icon-cog btn btn-primary hasTooltip" title="<?php echo JText::_('TEXT_EDIT')?>" onclick="<?php echo $APPTAG?>Groups_listReload(false)" data-toggle="modal" data-target="#modal-list-<?php echo $APPTAG?>Groups" data-backdrop="static" data-keyboard="false"></button>
							</span>
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group field-required">
						<label class="field-required"><?php echo JText::_('FIELD_LABEL_NAME'); ?></label>
						<input type="text" name="name" id="<?php echo $APPTAG?>-name" class="form-control upper" />
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label>E-mail</label>
						<div class="input-group">
							<input type="text" name="email" id="<?php echo $APPTAG?>-email" class="form-control field-email" />
							<input type="hidden" name="cmail" id="<?php echo $APPTAG?>-cmail" />
							<span class="input-group-btn">
								<button id="setEmailOptional" type="button" class="base-icon-plus btn btn-success hasTooltip" title="<?php echo JText::_('MSG_ADD_EMAIL_OPTIONAL')?>"></button>
							</span>
						</div>
					</div>
				</div>
				<div id="group-email_optional" class="col-sm-6 hide">
					<div class="form-group">
						<label>E-mail <?php echo JText::_('TEXT_OPTIONAL'); ?></label>
						<input type="text" name="email_optional" id="<?php echo $APPTAG?>-email_optional" class="form-control field-email" />
					</div>
				</div>
			</div>
			<hr class="hr-sm" />
			<div class="row">
				<div class="col-sm-4">
					<div class="form-group">
						<label>CNPJ</label>
						<input type="text" name="cnpj" id="<?php echo $APPTAG?>-cnpj" class="form-control field-cnpj" />
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						<label>Insc. Estadual</label>
						<input type="text" name="insc_estadual" id="<?php echo $APPTAG?>-insc_estadual" class="form-control" />
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						<label>Insc. Municipal</label>
						<input type="text" name="insc_municipal" id="<?php echo $APPTAG?>-insc_municipal" class="form-control" />
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						<label class="iconTip hasTooltip" title="<?php echo JText::_('FIELD_LABEL_DUE_DATE_DESC')?>"><?php echo JText::_('FIELD_LABEL_DUE_DATE'); ?></label>
						<select name="due_date" id="<?php echo $APPTAG?>-due_date" class="form-control">
							<option value="0"><?php echo JText::_('TEXT_SELECT'); ?></option>
							<?php
							for($i = 1; $i <= 31; $i++) {
								$d = $i < 10 ? '0'.$i : $i;
								echo '<option value="'.$d.'">'.$d.'</option>';
							}
							?>
						</select>
					</div>
					<div class="form-group">
						<label>Logomarca</label><br />
						<span id="<?php echo $APPTAG?>-display-img"></span>
						<span class="btn-group">
							<button type="button" class="base-icon-search btn btn-default set-file-action hasTooltip" title="<?php echo JText::_('TEXT_FILE_SELECT'); ?>"></button>
						</span>
						<input type="file" name="file[0]" id="<?php echo $APPTAG?>-file0" class="form-control field-image element-invisible" />
					</div>
				</div>
				<div class="col-sm-8">
					<div class="form-group">
						<label><?php echo JText::_('FIELD_LABEL_DESCRIPTION'); ?></label>
						<textarea name="description" rows="5" id="<?php echo $APPTAG?>-description" class="form-control"></textarea>
					</div>
				</div>
				<div class="col-sm-12">
					<div class="form-group">
						<label>Website (URL)</label>
						<input type="text" name="website" id="<?php echo $APPTAG?>-website" class="form-control field-url" placeholder="http://" />
					</div>
				</div>
			</div>
			<hr class="hr-sm" />
			<div id="registrationFormGroup">
				<span class="btn-group btn-justified width-full" data-toggle="buttons">
					<label id="<?php echo $APPTAG?>-user-create" class="btn btn-warning btn-active-success width-half" onclick="toggleFieldsetEmbed(this, '#registrationFieldsGroup', 0, '#<?php echo $APPTAG?>-username')">
						<span class="base-icon-user-add"></span>
						<input type="checkbox" name="userRegistration" id="<?php echo $APPTAG?>-userRegistration" value="1" />
						<?php echo JText::_('FIELD_LABEL_REGISTRATION'); ?>
					</label>
					<label id="<?php echo $APPTAG?>-user-assign" class="btn btn-primary btn-active-success width-half" onclick="toggleFieldsetEmbed(this, '#assignUserGroup')">
						<span class="base-icon-user"></span>
						<input type="checkbox" name="userRegistration" id="<?php echo $APPTAG?>-userRegistration" value="1" />
						<?php echo JText::_('FIELD_LABEL_ASSIGN_USER'); ?>
					</label>
				</span>
				<fieldset id="registrationFieldsGroup" class="fieldset-embed closed top-expand-sm" data-group="fe-registration">
					<div class="row">
						<div class="col-sm-6">
							<div class="form-group">
								<label class="display-block">
									<?php echo JText::_('FIELD_LABEL_PASSWORD'); ?>
									<small class="text-live font-condensed pull-right top-expand-xs"><?php echo JText::_('TEXT_OPTIONAL'); ?></small>
								</label>
								<div class="input-group">
									<input type="password" name="password" id="<?php echo $APPTAG?>-password" class="form-control" />
									<span class="input-group-addon hasPopover" data-content="<?php echo JText::_('MSG_PASSWORD_INFO'); ?>">
										<span class="base-icon-info-circled"></span>
									</span>
								</div>
							</div>
						</div>
						<div class="col-sm-6">
							<div class="form-group">
								<label class="iconTip hasTooltip" title="<?php echo JText::_('MSG_PASSWORD2'); ?>"><?php echo JText::_('FIELD_LABEL_PASSWORD2'); ?></label>
								<input type="password" name="password2" id="<?php echo $APPTAG?>-password2" class="form-control" />
							</div>
						</div>
						<div class="col-sm-6">
							<div class="checkbox no-margin">
								<label><input type="checkbox" name="emailConfirm" id="<?php echo $APPTAG?>-emailConfirm" value="1"> <?php echo JText::_('FIELD_LABEL_REGISTRATION_EMAIL_CONFIRM'); ?></label>
							</div>
						</div>
					</div>
				</fieldset>
				<fieldset id="assignUserGroup" class="fieldset-embed closed top-expand-sm" data-group="fe-registration">
					<div class="row">
						<div class="col-sm-8">
							<div class="form-group no-margin">
								<label><?php echo JText::_('TEXT_USER'); ?></label>
								<select name="assing_id" id="<?php echo $APPTAG?>-assing_id" class="form-control">
									<option value="0"><?php echo JText::_('TEXT_SELECT'); ?></option>
									<?php
										foreach ($users as $obj) {
											echo '<option value="'.$obj->id.'" data-name="'.$obj->name.'" data-email="'.$obj->email.'">'.baseHelper::nameFormat($obj->name).'</option>';
										}
									?>
								</select>
							</div>
						</div>
					</div>
				</fieldset>
			</div>
			<div id="contactIsUser" class="alert alert-success no-margin hide">
				<?php echo JText::_('MSG_CONTACT_IS_USER'); ?>
				<div id="<?php echo $APPTAG?>-block-0" class="text-danger strong">
					<hr class="hr-sm" />
					<div class="base-icon-attention"> <?php echo JText::_('TEXT_USER_UNLOCK_DESC')?></div>
					<button type="button" class="base-icon-lock-open btn btn-sm btn-warning top-space-xs" onclick="<?php echo $APPTAG?>_userBlock(0)"> <?php echo JText::_('TEXT_USER_UNLOCK')?></button>
				</div>
				<button type="button" id="<?php echo $APPTAG?>-block-1" class="base-icon-lock btn btn-sm btn-danger top-space-xs" onclick="<?php echo $APPTAG?>_userBlock(1)"> <?php echo JText::_('TEXT_USER_LOCK')?></button>
			</div>
		</fieldset>
	</div>
	<div class="modal-footer">
		<div class="pull-left bottom-space-xs text-left text-overflow">
			<span class="base-icon-ok-circled2 set-success text-success hide"></span>
			<span class="base-icon-cancel-circled set-error text-danger hide"></span>
			<span class="ajax-loader hide"></span>
		</div>
		<div class="pull-right">
			<button name="btn-<?php echo $APPTAG?>-save" id="btn-<?php echo $APPTAG?>-save" class="base-icon-ok btn btn-success btn-sm" onclick="<?php echo $APPTAG?>_save();"> <?php echo JText::_('TEXT_SAVE'); ?></button>
			<button name="btn-<?php echo $APPTAG?>-save-new" id="btn-<?php echo $APPTAG?>-save-new" class="base-icon-ok btn btn-success btn-sm" onclick="<?php echo $APPTAG?>_save('reset');"> <?php echo JText::_('TEXT_SAVENEW'); ?></button>
			<button name="btn-<?php echo $APPTAG?>-delete" id="btn-<?php echo $APPTAG?>-delete" class="base-icon-trash btn btn-danger btn-sm hide" onclick="<?php echo $APPTAG?>_del(0, true)"> <?php echo JText::_('TEXT_DELETE'); ?></button>
			<button name="btn-<?php echo $APPTAG?>-cancel" id="btn-<?php echo $APPTAG?>-cancel" class="base-icon-cancel btn btn-default btn-sm" data-dismiss="modal"> <?php echo JText::_('TEXT_CANCEL'); ?></button>
		</div>
	</div>

</form>

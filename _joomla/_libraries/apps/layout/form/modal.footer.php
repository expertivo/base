<div class="modal-footer text-right">
	<div class="btn-group">
		<button name="btn-<?php echo $APPTAG?>-save" id="btn-<?php echo $APPTAG?>-save" class="base-icon-ok btn btn-success" onclick="<?php echo $APPTAG?>_save()"> <?php echo JText::_('TEXT_SAVE'); ?></button>
		<button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
		<div class="dropdown-menu dropdown-menu-right">
			<a class="dropdown-item" href="#" name="btn-<?php echo $APPTAG?>-save-new" id="btn-<?php echo $APPTAG?>-save-new" onclick="<?php echo $APPTAG?>_save('reset')"> <?php echo JText::_('TEXT_SAVENEW'); ?></a>
		</div>
	</div>
	<button name="btn-<?php echo $APPTAG?>-save-close" id="btn-<?php echo $APPTAG?>-save-close" class="base-icon-ok btn btn-success" onclick="<?php echo $APPTAG?>_save('close')"> <?php echo JText::_('TEXT_SAVECLOSE')?></button>
	<button name="btn-<?php echo $APPTAG?>-cancel" id="btn-<?php echo $APPTAG?>-cancel" class="base-icon-cancel btn btn-default" data-dismiss="modal"> <?php echo JText::_('TEXT_CLOSE'); ?></button>
</div>

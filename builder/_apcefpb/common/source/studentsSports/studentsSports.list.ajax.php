<?php
// BLOCK DIRECT ACCESS
if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) AND strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") :

	// load Joomla's framework
	require_once('../load.joomla.php');
	$app = JFactory::getApplication('site');

	defined('_JEXEC') or die;
	$ajaxRequest = true;
	require('config.php');
	// IMPORTANTE: Carrega o arquivo 'helper' do template
	JLoader::register('baseHelper', JPATH_BASE.'/templates/base/source/helpers/base.php');

	// Carrega o arquivo de tradução
	// OBS: para arquivos externos com o carregamento do framework 'load.joomla.php' (geralmente em 'ajax')
	// a language 'default' não é reconhecida. Sendo assim, carrega apenas 'en-GB'
	// Para possibilitar o carregamento da language 'default' de forma dinâmica,
	// é necessário passar na sessão ($_SESSION[$APPTAG.'langDef'])
	if(isset($_SESSION[$APPTAG.'langDef']))
	$lang->load('base_'.$APPNAME, JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);

	//joomla get request data
	$input      = $app->input;

	// params requests
	$APPTAG			= $input->get('aTag', $APPTAG, 'str');
	$RTAG				= $input->get('rTag', $APPTAG, 'str');
	$oCHL				= $input->get('oCHL', 0, 'bool');
	$oCHL				= $_SESSION[$RTAG.'OnlyChildList'] ? $_SESSION[$RTAG.'OnlyChildList'] : $oCHL;
	$rNID       = $input->get('rNID', '', 'str');
	$rNID				= !empty($_SESSION[$RTAG.'RelListNameId']) ? $_SESSION[$RTAG.'RelListNameId'] : $rNID;
	$rID      	= $input->get('rID', 0, 'int');
	$rID				= !empty($_SESSION[$RTAG.'RelListId']) ? $_SESSION[$RTAG.'RelListId'] : $rID;

	// get current user's data
	$user = JFactory::getUser();
	$groups = $user->groups;

	// verifica o acesso
	$hasGroup = array_intersect($groups, $cfg['groupId']['viewer']); // se está na lista de grupos permitidos
	$hasAdmin = array_intersect($groups, $cfg['groupId']['admin']); // se está na lista de administradores permitidos

	// database connect
	$db = JFactory::getDbo();

	// GET DATA
	$noReg = true;
	$query = '
	SELECT
		'. $db->quoteName('T1.id') .',
		'. $db->quoteName('T2.name') .' name,
		'. $db->quoteName('T3.name') .' sport,
		'. $db->quoteName('T2.gender') .',
		'. $db->quoteName('T2.has_disease') .',
		'. $db->quoteName('T2.disease_desc') .',
		'. $db->quoteName('T2.has_allergy') .',
		'. $db->quoteName('T2.allergy_desc') .',
		'. $db->quoteName('T1.registry_date') .',
		'. $db->quoteName('T1.note') .',
		'. $db->quoteName('T1.coupon_free') .',
		'. $db->quoteName('T1.state')
	;
	if(!empty($rID) && $rID !== 0) :
		if(isset($_SESSION[$RTAG.'RelTable']) && !empty($_SESSION[$RTAG.'RelTable'])) :
			$query .= ' FROM
				'. $db->quoteName($cfg['mainTable']) .' T1
				JOIN '. $db->quoteName('#__apcefpb_students') .' T2
				ON T2.id = T1.student_id
				JOIN '. $db->quoteName('#__apcefpb_sports') .' T3
				ON T3.id = T1.sport_id
				JOIN '. $db->quoteName($_SESSION[$RTAG.'RelTable']) .' T4
				ON '.$db->quoteName('T4.'.$_SESSION[$RTAG.'AppNameId']) .' = T1.id
			WHERE '.
				$db->quoteName('T4.'.$_SESSION[$RTAG.'RelNameId']) .' = '. $rID
			;
		else :
			$query .= ' FROM
				'. $db->quoteName($cfg['mainTable']) .' T1
				JOIN '. $db->quoteName('#__apcefpb_students') .' T2
				ON T2.id = T1.student_id
				JOIN '. $db->quoteName('#__apcefpb_sports') .' T3
				ON T3.id = T1.sport_id
			WHERE '. $db->quoteName($rNID) .' = '. $rID;
		endif;
	else :
		$query .= ' FROM
			'. $db->quoteName($cfg['mainTable']) .' T1
			JOIN '. $db->quoteName('#__apcefpb_students') .' T2
			ON T2.id = T1.student_id
			JOIN '. $db->quoteName('#__apcefpb_sports') .' T3
			ON T3.id = T1.sport_id';
		if($oCHL) :
			$query .= ' WHERE 1=0';
			$noReg = false;
		endif;
	endif;
	$query .= ' ORDER BY '. $db->quoteName('T1.registry_date') .' DESC';
	try {

		$db->setQuery($query);
		$db->execute();
		$num_rows = $db->getNumRows();
		$res = $db->loadObjectList();

	} catch (RuntimeException $e) {
		 echo $e->getMessage();
		 return;
	}

	$html = '<span class="ajax-loader hide"></span>';

	if($num_rows) : // verifica se existe
		$html .= '<ul class="list list-striped list-hover">';
		foreach($res as $item) {

			if($cfg['hasUpload']) :
				JLoader::register('uploader', JPATH_BASE.'/templates/base/source/helpers/upload.php');
				$files[$item->id] = uploader::getFiles($cfg['fileTable'], $item->id);
				$listFiles = '';
				for($i = 0; $i < count($files[$item->id]); $i++) {
					if(!empty($files[$item->id][$i]->filename)) :
						$listFiles .= '
							<a href="'.$_root.'get-file?fn='.base64_encode($files[$item->id][$i]->filename).'&mt='.base64_encode($files[$item->id][$i]->mimetype).'&tag='.base64_encode($APPNAME).'">
								<span class="base-icon-attach hasTooltip" title="'.$files[$item->id][$i]->filename.'<br />'.((int)($files[$item->id][$i]->filesize / 1024)).'kb"></span>
							</a>
						';
					endif;
				}
			endif;

			$free = ($item->coupon_free == 1 ? '<span class="base-icon-flag cursor-help text-success hasTooltip" title="'.JText::_('FIELD_LABEL_COUPON_FREE').'"></span> ' : '';
			$gender = '<span '.($item->gender == 1 ? 'class="base-icon-male-symbol cursor-help text-primary hasTooltip" title="'.JText::_('FIELD_LABEL_GENDER_MALE').'"' : 'class="base-icon-female-symbol cursor-help text-danger hasTooltip" title="'.JText::_('FIELD_LABEL_GENDER_FEMALE').'"').'"></span> ';
			$info = !empty($item->note) ? '<span class="base-icon-info-circled text-live cursor-help hasPopover" data-placement="top" title="<strong>'.JText::_('FIELD_LABEL_NOTE').'</strong>" data-content="<small class=\'font-featured\'>'.$item->note.'</small>"></span> ' : '';
			if($item->has_disease == 1) :
				$info .= !empty($item->disease_desc) ? '<span class="base-icon-attention text-danger cursor-help hasPopover" data-placement="top" title="<strong>'.JText::_('FIELD_LABEL_DISEASE').'</strong>" data-content="'.$item->disease_desc.'"></span> ' : '';
			endif;
			if($item->has_allergy == 1) :
				$info .= !empty($item->allergy_desc) ? '<span class="base-icon-attention text-live cursor-help hasPopover" data-placement="top" title="<strong>'.JText::_('FIELD_LABEL_ALLERGY').'</strong>" data-content="'.$item->allergy_desc.'"></span> ' : '';
			endif;
			$info = !empty($info) ? '<br />'.$info : '';
			$btnState = $hasAdmin ? '<a href="#" onclick="'.$APPTAG.'_setState('.$item->id.')" id="'.$APPTAG.'-state-'.$item->id.'"><span class="'.($item->state == 1 ? 'base-icon-ok text-success' : 'base-icon-cancel text-danger').' hasTooltip" title="'.JText::_('MSG_ACTIVE_INACTIVE_ITEM').'"></span></a> ' : '';
			$btnEdit = $hasAdmin ? '<a href="#" class="base-icon-pencil text-live hasTooltip" title="'.JText::_('TEXT_EDIT').'" onclick="'.$APPTAG.'_loadEditFields('.$item->id.', false, false)"></a> ' : '';
			$btnDelete = $hasAdmin ? '<a href="#" class="base-icon-trash text-danger hasTooltip" title="'.JText::_('TEXT_DELETE').'" onclick="'.$APPTAG.'_del('.$item->id.', false)"></a>' : '';
			$rowState = $item->state == 0 ? 'danger' : '';
			$html .= '
				<li class="'.$rowState.'">
					<div class="pull-right">'.$btnState.$btnEdit.$btnDelete.'</div>
					'.$free.$gender.baseHelper::nameFormat($item->name).'
					<div class="small text-muted font-featured">
						'.baseHelper::nameFormat($item->sport).' - <span class="base-icon-calendar cursor-help hasTooltip" title="'.JText::_('FIELD_LABEL_REGISTRY_DATE').'"> '.baseHelper::dateFormat($item->registry_date).'</span> - '.$info.'
					</div>
				</li>
			';
		}
		$html .= '</ul>';
	else :
		if($noReg) $html = '<p class="base-icon-info-circled alert alert-info no-margin"> '.JText::_('MSG_LISTNOREG').'</p>';
	endif;

	echo $html;

else :

	# Otherwise, bad request
	header('status: 400 Bad Request', true, 400);

endif;

?>

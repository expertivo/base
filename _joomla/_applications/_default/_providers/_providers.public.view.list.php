<?php
/* SISTEMA PARA CADASTRO DE TELEFONES
 * AUTOR: IVO JUNIOR
 * EM: 18/02/2016
*/
defined('_JEXEC') or die;
$ajaxRequest = false;
require('config.php');

// ACESSO
$cfg['isPublic'] = true; // Público -> acesso aberto a todos

// IMPORTANTE: Carrega o arquivo 'helper' do template
JLoader::register('baseHelper', JPATH_CORE.DS.'helpers/base.php');
JLoader::register('baseAppHelper', JPATH_CORE.DS.'helpers/apps.php');

$app = JFactory::getApplication('site');

// GET CURRENT USER'S DATA
$user = JFactory::getUser();
$groups = $user->groups;

// init general css/js files
require(JPATH_CORE.DS.'apps/_init.app.php');

// Get request data
$p = $app->input->get('p', 0, 'int');

// Carrega o arquivo de tradução
// OBS: para arquivos externos com o carregamento do framework '_init.joomla.php' (geralmente em 'ajax')
// a language 'default' não é reconhecida. Sendo assim, carrega apenas 'en-GB'
// Para possibilitar o carregamento da language 'default' de forma dinâmica,
// é necessário passar na sessão ($_SESSION[$APPTAG.'langDef'])
if(isset($_SESSION[$APPTAG.'langDef'])) :
	$lang->load('base_apps', JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
	$lang->load('base_'.$APPNAME, JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
endif;

if(isset($user->id) && $user->id) :

	// DATABASE CONNECT
	$db = JFactory::getDbo();

	// GET DATA
	$query	= '
		SELECT
			'. $db->quoteName('T1.*') .',
			'. $db->quoteName('T2.name') .' grp
		FROM '.
			$db->quoteName($cfg['mainTable']) .' T1
			JOIN '. $db->quoteName($cfg['mainTable'].'_groups') .' T2
			ON T2.id = T1.group_id AND T2.state = 1
		WHERE '. $db->quoteName('T1.id') .' = '. $p
	;
	try {
		$db->setQuery($query);
		$db->execute();
		$num_rows = $db->getNumRows();
		$res = $db->loadObjectList();
	} catch (RuntimeException $e) {
		echo $e->getMessage();
		return;
	}

	$html = '';
	if($num_rows) : // verifica se existe
		$list = '';
		foreach($res as $item) {
			$list .= '
				<li>
					'.$item->phone_number.'
					<div class="text-sm text-muted mt-1"><span class="badge badge-primary">'.$item->provider.'</span> '.baseHelper::nameFormat($item->plan).'</div>
				</li>
			';
		}
		$html .= '<ul class="set-list bordered list-striped list-hover">'.$list.'</ul>';
	else :
		$html = '<p class="base-icon-info-circled alert alert-info m-0"> '.JText::_('MSG_LISTNOREG').'</p>';
	endif;

	echo $html;

else :

	echo '<h4 class="alert alert-warning">'.JText::_('MSG_NOT_PERMISSION').'</h4>';

endif;
?>
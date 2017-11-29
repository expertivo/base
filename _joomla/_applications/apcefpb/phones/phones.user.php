<?php
/* SISTEMA PARA CADASTRO DE TELEFONES
 * AUTOR: IVO JUNIOR
 * EM: 18/02/2016
*/
defined('_JEXEC') or die;
$ajaxRequest = false;
require('config.php');

// ACESSO: Libera o acesso aos clients
// Atribui aos clientes o perfil de visualizador só para esse código
unset($cfg['groupId']['viewer']); // Limpa os valores padrão
$cfg['groupId']['viewer'][]	= 11; // Associado -> Efetivo
$cfg['groupId']['viewer'][]	= 12; // Associado -> Aposentado
$cfg['groupId']['viewer'][]	= 13; // Associado -> Contribuinte

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
$uID = $app->input->get('uID', 0, 'int');
$uID = ($hasAdmin && $uID > 0) ? $uID : $user->id;

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
	$query = '
	SELECT
		'. $db->quoteName('T1.id') .',
		'. $db->quoteName('T2.id') .' plan_id,
		'. $db->quoteName('T2.name') .' plan,
		'. $db->quoteName('T2.description') .' plan_desc,
		'. $db->quoteName('T4.name') .' provider,
		'. $db->quoteName('T2.price') .',
		'. $db->quoteName('T1.phone_number') .',
		'. $db->quoteName('T1.state') .'
	FROM '.$db->quoteName($cfg['mainTable']).' T1
		JOIN '. $db->quoteName($cfg['mainTable'].'_plans') .' T2
		ON T2.id = T1.plan_id AND T2.state = 1
		JOIN '. $db->quoteName('#__'.$cfg['project'].'_clients') .' T3
		ON T3.id = T1.client_id AND T3.state = 1
		JOIN '. $db->quoteName('#__base_providers') .' T4
		ON T4.id = T2.provider_id AND T4.state = 1
	WHERE
		'.$db->quoteName('T3.user_id') .' = '. $uID . ' AND
		'.$db->quoteName('T1.state') .' = 1
	';
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

		$html .= '<ul class="set-list bordered list-trim">';
		foreach($res as $item) {
			$plan = '<small class="text-muted"><span class="badge badge-primary">'.$item->provider.'</span> '.baseHelper::nameFormat($item->plan).'</small>';
			if(!empty($item->plan_desc)) :
				$plan = '
				<a href="#modal-'.$APPTAG.'-plan'.$item->plan_id.'-desc" data-toggle="modal">
					'.$plan.'
				</a>
				';
			endif;
			// LINK TO INVOICE
			$urlToInvoice = JURI::root().'apps/clients/phonesinvoices?pID='.$item->id.($uID != $user->id ? '&uID='.$uID : '');
			$html .= '
				<li>
					<h5 class="mb-1">
						'.$item->phone_number.'
						<a href="'.$urlToInvoice.'" class="btn btn-info btn-xs float-right base-icon-doc-text-inv hasTooltip" title="'.JText::_('TEXT_VIEW_INVOICES').'"> '.JText::_('TEXT_INVOICES').'</a>
					</h5>
					'.$plan.'
					<div class="modal fade" id="modal-phones-plan'.$item->plan_id.'-desc" tabindex="-1" role="dialog" aria-labelledby="modal-'.$APPTAG.'-plan'.$item->plan_id.'-descLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title">'.JText::_('FIELD_LABEL_PLAN').': '.baseHelper::nameFormat($item->plan).'</h5>
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								</div>
								<div class="modal-body">
									'.$item->plan_desc.'
								</div>
							</div>
						</div>
					</div>
				</li>
			';
		}
		$html .= '</ul>';
	else :
		$html = '<p class="base-icon-info-circled alert alert-warning m-0"> '.JText::_('MSG_NO_PHONES').'</p>';
	endif;

	echo $html;

	?>

<?php
else :

	echo '<h4 class="alert alert-warning">'.JText::_('MSG_NOT_PERMISSION').'</h4>';

endif;
?>

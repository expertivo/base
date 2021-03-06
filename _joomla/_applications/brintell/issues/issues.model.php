<?php
// BLOCK DIRECT ACCESS
if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) AND strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") :

	header( 'Cache-Control: no-cache' );
	header( 'content-type: application/json; charset=utf-8' );

	function is_valid_callback($subject) {
		$identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';

		$reserved_words = array(
			'break', 'do', 'instanceof', 'typeof', 'case',
			'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue',
			'for', 'switch', 'while', 'debugger', 'function', 'this', 'with',
			'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum',
			'extends', 'super', 'const', 'export', 'import', 'implements', 'let',
			'private', 'public', 'yield', 'interface', 'package', 'protected',
			'static', 'null', 'true', 'false'
		);

		return preg_match($identifier_syntax, $subject) && ! in_array(mb_strtolower($subject, 'UTF-8'), $reserved_words);
	}

	// load Joomla's framework
	// _DIR_ => apps/THIS_APP
	require(__DIR__.'/../../libraries/envolute/_init.joomla.php');
	defined('_JEXEC') or die;
	$app = JFactory::getApplication('site');

	$ajaxRequest = true;
	require('config.php');

	// IMPORTANTE: Carrega o arquivo 'helper' do template
	JLoader::register('baseHelper', JPATH_CORE.DS.'helpers/base.php');
    // classes customizadas para usuários Joomla
    JLoader::register('baseUserHelper',  JPATH_CORE.DS.'helpers/user.php');

	// get current user's data
	$user		= JFactory::getUser();
	$groups		= $user->groups;

	//joomla get request data
	$input      = $app->input;

	// Default Params
	$APPTAG		= $input->get('aTag', $APPTAG, 'str');
	$RTAG		= $input->get('rTag', $APPTAG, 'str');
	$task       = $input->get('task', null, 'str');
	$data       = array();

	if($task != null) :

		// database connect
		$db = JFactory::getDbo();

		// Carrega o arquivo de tradução
		// OBS: para arquivos externos com o carregamento do framework '_init.joomla.php' (geralmente em 'ajax')
		// a language 'default' não é reconhecida. Sendo assim, carrega apenas 'en-GB'
		// Para possibilitar o carregamento da language 'default' de forma dinâmica,
		// é necessário passar na sessão ($_SESSION[$APPTAG.'langDef'])
		if(isset($_SESSION[$APPTAG.'langDef'])) :
			$lang->load('base_apps', JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
			$lang->load('base_'.$APPNAME, JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
		endif;

		// params requests
		$id         = $input->get('id', 0, 'int');

		// fields 'List' requests
		$listIds    = $input->get($APPTAG.'_ids', array(), 'array');
		$ids        = (count($listIds) > 0) ? implode($listIds, ',') : $id;
		$state      = $input->get('st', 2, 'int');
		$pID		= $input->get('pID', 0, 'int');

		// upload actions
		$fileMsg 	= '';
		if($cfg['hasUpload']) :
			$fname		= $input->get('fname', '', 'string');
			$fileId		= $input->get('fileId', 0, 'int');
			// image groups
			$fileGrp	= isset($_POST[$cfg['fileField'].'Group']) ? $_POST[$cfg['fileField'].'Group'] : '';
			$fileGtp	= isset($_POST[$cfg['fileField'].'Gtype']) ? $_POST[$cfg['fileField'].'Gtype'] : '';
			$fileCls	= isset($_POST[$cfg['fileField'].'Class']) ? $_POST[$cfg['fileField'].'Class'] : '';
			// image description
			$fileLbl	= isset($_POST[$cfg['fileField'].'Label']) ? $_POST[$cfg['fileField'].'Label'] : '';
			// load 'uploader' class
			JLoader::register('uploader', JPATH_CORE.DS.'helpers/files/upload.php');
		endif;

		// fields 'Form' requests
		$request						= array();
		// default
		$request['relationId']   		= $input->get('relationId', 0, 'int');
		$request['state']				= $input->get('state', 1, 'int');
		// app
		$request['project_id']			= $input->get('project_id', 0, 'int');
		$request['type']				= $input->get('type', 0, 'int');
		$request['ctype']				= $input->get('ctype', 0, 'int');
	  	$request['subject']				= $input->get('subject', '', 'string');
	  	$request['description']			= $input->get('description', '', 'string');
		$request['priority']			= $input->get('priority', 0, 'int');
	  	$request['deadline']			= $input->get('deadline', '', 'string');
	  	$request['timePeriod']			= $input->get('timePeriod', '', 'string');
		$tags							= $input->get('tags', array(), 'array');
		$request['tags']				= implode(',', $tags); // FIND_IN_SET
		$request['author']				= $input->get('author', 0, 'int');
		if($request['author'] == 0) $request['author'] = $user->id;

		// CUSTOM -> default vars for registration e-mail
		$config			= JFactory::getConfig();
		$sitename		= $config->get('sitename');
		$domain			= baseHelper::getDomain();
		$mailFrom		= $config->get('mailfrom');

	    // CUSTOM -> Copy To-Do List
	    function copyTodoList($requestID, $taskID, $userID) {
			if(!empty($requestID) && $requestID != 0) :
				// database connect
				$db = JFactory::getDbo();
				$query = '
					INSERT INTO '. $db->quoteName($cfg['mainTable'].'_todo') .' (
						task_id,
						subject,
						description,
						state,
						created_by
					)
					SELECT
						'.$taskID.',
						subject,
						description,
						'. $db->quote('1') .',
						'. $db->quote($userID) .'
					FROM
						'. $db->quoteName('#__'.$cfg['project'].'_issues_todo') .'
					WHERE issue_id = '.$requestID.'
				';
				$db->setQuery($query);
				$db->execute();
				return true;
			else :
				return false;
			endif;
	    }
	    // CUSTOM -> Copy Task from Request
	    function createTaskFromRequest($requestID, $requestType, $pID, $userID, $cfg) {
			if(!empty($requestID) && $requestID != 0) :
				// database connect
				$db = JFactory::getDbo();
				$query = '
					INSERT INTO '. $db->quoteName($cfg['mainTable']) .' (
						project_id,
						type,
						issues,
						subject,
						description,
						priority,
						deadline,
						timePeriod,
						created_by
					)
					SELECT
						'.$pID.',
						'.$requestID.',
						'.$requestType.',
						subject,
						description,
						priority,
						deadline,
						timePeriod,
						'.$userID.'
					FROM '. $db->quoteName('#__'.$cfg['project'].'_issues_todo') .'
					WHERE id = '.$requestID
				;
				$db->setQuery($query);
				$db->execute();
				$taskID = $db->insertid();
				copyTodoList($requestID, $taskID, $userID);
				return true;
			else :
				return false;
			endif;
	    }

		// SAVE CONDITION
		// Condição para inserção e atualização dos registros
		$save_condition = ($request['project_id'] > 0 && !empty($request['subject']));

		if($id || (!empty($ids) && $ids != 0)) :  //UPDATE OR DELETE

			$exist = 0;
			if($id) :
				// GET FORM DATA
				$query = 'SELECT * FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' = '. $id;
				$db->setQuery($query);
				$item	= $db->loadObject();
	    		$exist	= (isset($item->id) && !empty($item->id) && $item->id > 0);
				// get previous ID
				$query = 'SELECT MAX(id) FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' < '. $id;
				$db->setQuery($query);
				$prev = $db->loadResult();
				$prev = !empty($prev) ? $prev : 0;
				// get next ID
				$query = 'SELECT MIN(id) FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' > '. $id;
				$db->setQuery($query);
				$next = $db->loadResult();
				$next = !empty($next) ? $next : 0;
				if($cfg['hasUpload']) :
					// get files
					$query = 'SELECT *, TO_BASE64('. $db->quoteName('filename') .') fn, TO_BASE64('. $db->quoteName('mimetype') .') mt FROM '. $db->quoteName($cfg['fileTable']) .' WHERE '. $db->quoteName('id_parent') .' = '. $id . ' ORDER BY '. $db->quoteName('index');
					$db->setQuery($query);
					$listFiles = $db->loadAssocList();
				endif;
			else :
				// COUNT LIST IDS
				$query = 'SELECT COUNT(*) FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' IN ('.$ids.')';
				$db->setQuery($query);
				$exist = $db->loadResult();
			endif;

			if($exist) : // verifica se existe

				// GET DATA
				if($task == 'get') :

					$data[] = array(
						// Default Fields
						'id'				=> $item->id,
						'state'				=> $item->state,
						'prev'				=> $prev,
						'next'				=> $next,
						// App Fields
						'project_id'		=> $item->project_id,
						'type'				=> $item->type,
						'subject'			=> $item->subject,
						'description'		=> $item->description,
						'priority'			=> $item->priority,
						'deadline'			=> $item->deadline,
						'timePeriod'		=> $item->timePeriod,
						'tags'				=> explode(',', $item->tags),
						'author'			=> $item->created_by,
						'files'				=> $listFiles
					);

				// UPDATE
				elseif($task == 'save' && $id) :

					if($save_condition) {

						$query  = 'UPDATE '.$db->quoteName($cfg['mainTable']).' SET ';
						$query .=
							$db->quoteName('project_id')		.'='. $request['project_id'] .','.
							$db->quoteName('type')				.'='. $request['type'] .','.
							$db->quoteName('subject')			.'='. $db->quote($request['subject']) .','.
							$db->quoteName('description')		.'='. $db->quote($request['description']) .','.
							$db->quoteName('priority')			.'='. $request['priority'] .','.
							$db->quoteName('deadline')			.'='. $db->quote($request['deadline']) .','.
							$db->quoteName('timePeriod')		.'='. $db->quote($request['timePeriod']) .','.
							$db->quoteName('tags')				.'='. $db->quote($request['tags']) .','.
							$db->quoteName('state')				.'='. $request['state'] .','.
							$db->quoteName('created_by')		.'='. $request['author'] .','.
							$db->quoteName('alter_date')		.'= NOW(),'.
							$db->quoteName('alter_by')			.'='. $user->id
						;
						$query .= ' WHERE '. $db->quoteName('id') .'='. $id;

						try {

							$db->setQuery($query);
							$db->execute();

							// Upload
							if($cfg['hasUpload'])
							$fileMsg = uploader::uploadFile($id, $cfg['fileTable'], $_FILES[$cfg['fileField']], $fileGrp, $fileGtp, $fileCls, $fileLbl, $cfg);

							// UPDATE FIELD
							$element = $elemVal = $elemLabel = '';
							if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
								$element = $_SESSION[$RTAG.'FieldUpdated'];
								$elemVal = $id;
								$query = 'SELECT '. (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $_SESSION[$RTAG.'TableField']) ? $db->quoteName($_SESSION[$RTAG.'TableField']) : $_SESSION[$RTAG.'TableField']) .' FROM '. $db->quoteName($cfg['mainTable']).' WHERE '. $db->quoteName('id') .' = '.$id.' AND state = 1';
								$db->setQuery($query);
								$elemLabel = $db->loadResult();
							endif;

				            // CUSTOM -> Copy To-Do List
				            if($request['template'] > 0) copyTodoList($request['template'], $id, $user->id);

							$data[] = array(
								'status'			=> 2,
								'msg'				=> JText::_('MSG_SAVED'),
								'uploadError'		=> $fileMsg,
								'parentField'		=> $element,
								'parentFieldVal'	=> $elemVal,
								'parentFieldLabel'	=> baseHelper::nameFormat($elemLabel)
							);

						} catch (RuntimeException $e) {

							// Error treatment
							switch($e->getCode()) {
								case '1062':
								$sqlErr = JText::_('MSG_SQL_DUPLICATE_KEY');
								break;
								default:
								$sqlErr = 'Erro: '.$e->getCode().'. '.$e->getMessage();
							}

							$data[] = array(
								'status'			=> 0,
								'msg'				=> $sqlErr,
								'uploadError'		=> $fileMsg
							);

						}

					} else {

						$data[] = array(
							'status'				=> 0,
							'msg'					=> JText::_('MSG_ERROR'),
							'uploadError'			=> $fileMsg
						);

					}

				// DELETE
				elseif($task == 'del') :

					$query = 'DELETE FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' IN ('.$ids.')';

					$setIds = explode(',', $ids);

					try {

						$db->setQuery($query);
						$db->execute();

						// FILE: remove o(s) arquivo(s)
						if($cfg['hasUpload'] && !empty($ids) && $ids != 0)
						$fileMsg = uploader::deleteFiles($ids, $cfg['fileTable'], $cfg['uploadDir'], JText::_('MSG_FILEERRODEL'));

						// DELETE RELATIONSHIP
						if(!empty($_SESSION[$RTAG.'RelTable']) && !empty($_SESSION[$RTAG.'AppNameId'])) :
							$query = 'DELETE FROM '. $db->quoteName($_SESSION[$RTAG.'RelTable']) .' WHERE '. $db->quoteName($_SESSION[$RTAG.'AppNameId']) .' IN ('.$ids.')';
							$db->setQuery($query);
							$db->execute();
						endif;
						// FORCE DELETE RELATIONSHIPS
						// força a exclusão do(s) relacionamento(s) caso os parâmetros não sejam setados
						// isso é RECOMENDÁVEL sempre que houver um ou mais relacionamentos
						// SAMPLES -> remove os registros relacionados aos exemplos
						// $query = 'DELETE FROM '. $db->quoteName('#__'.$cfg['project'].'_app_sample') .' WHERE '. $db->quoteName('type_id') .' IN ('.$ids.')';
						// $db->setQuery($query);
						// $db->execute();

						// UPDATE FIELD
						// executa apenas com valores individuais
						$element = $elemVal = $elemLabel = '';
						if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
							$element = $_SESSION[$RTAG.'FieldUpdated'];
							$elemVal = $ids;
						endif;

						if(count($setIds) > 1) :
							$_SESSION[$APPTAG.'baseAlert']['message'] = JText::_('MSG_ITEMS_DELETED_SUCCESS');
							$_SESSION[$APPTAG.'baseAlert']['context'] = 'success';
						endif;

						$data[] = array(
							'status'			=> 3,
							'ids'				=> $setIds,
							'msg'				=> JText::_('MSG_DELETED'),
							'uploadError'		=> $fileMsg,
							'parentField'		=> $element,
							'parentFieldVal'	=> $elemVal
						);

					} catch (RuntimeException $e) {

						$data[] = array(
							'status'			=> 0,
							'msg'				=> $e->getMessage(),
							'uploadError'		=> $fileMsg
						);

					}

				// STATE
				elseif($task == 'state') :

					$stateVal = ($state == 2 ? 'IF(state = 1, 0, 1)' : $state);
					$query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET '. $db->quoteName('state') .' = '.$stateVal.', '. $db->quoteName('alter_date') .' = NOW() WHERE '. $db->quoteName('id') .' IN ('.$ids.')';

					try {

						$db->setQuery($query);
						$db->execute();

						// UPDATE FIELD
						// executa apenas com valores individuais
						$element = $elemVal = $elemLabel = '';
						if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
							$element = $_SESSION[$RTAG.'FieldUpdated'];
							$elemVal = $ids;
							$query = 'SELECT '. (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $_SESSION[$RTAG.'TableField']) ? $db->quoteName($_SESSION[$RTAG.'TableField']) : $_SESSION[$RTAG.'TableField']) .' FROM '. $db->quoteName($cfg['mainTable']).' WHERE '. $db->quoteName('id') .' = '.$ids;
							$db->setQuery($query);
							$elemLabel = $db->loadResult();
						endif;

						$setIds = explode(',', $ids);
						if(count($setIds) > 1) :
							$_SESSION[$APPTAG.'baseAlert']['message'] = JText::_('MSG_ITEMS_ALTER_SUCCESS');
							$_SESSION[$APPTAG.'baseAlert']['context'] = 'success';
						endif;

						$data[] = array(
							'status'			=> 4,
							'state'				=> $state,
							'ids'				=> $setIds,
							'msg'				=> '',
							'parentField'		=> $element,
							'parentFieldVal'	=> $elemVal,
							'parentFieldLabel'	=> baseHelper::nameFormat($elemLabel)
						);

					} catch (RuntimeException $e) {

						$data[] = array(
							'status'			=> 0,
							'msg'				=> $e->getMessage()
						);

					}

				// DELETE FILE
				elseif($cfg['hasUpload'] && $task == 'delFile' && $fname) :

					// FILE: remove o arquivo
					$fileMsg = uploader::deleteFile($fname, $cfg['fileTable'], $cfg['uploadDir'], JText::_('MSG_FILEERRODEL'));

					$data[] = array(
						'status'				=> 5,
						'msg'					=> JText::_('MSG_FILE_DELETED'),
						'uploadError'			=> $fileMsg
					);

					// DELETE FILES
					elseif($cfg['hasUpload'] && $task == 'delFiles' && $fileId) :

					// FILE: remove o arquivo
					$fileMsg = uploader::deleteFiles($fileId, $cfg['fileTable'], $cfg['uploadDir'], JText::_('MSG_FILEERRODEL'));

					$data[] = array(
						'status'				=> 6,
						'msg'					=> JText::_('MSG_FILES_DELETED'),
						'uploadError'			=> $fileMsg
					);

				// TEMPLATES SERVICE
				elseif($task == 'createTask') :

					try {

						$setIds = explode(',', $ids);
						// CREATE TASK FROM REQUEST
						for($i = 0; $i < count($setIds); $i++) {
							// SELECT PROJECT
							$query = 'SELECT project_id FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' = '.$setIds[$i];
							$db->setQuery($query);
							$pID = $db->loadResult();
							// CREATE TASK
							createTaskFromRequest($setIds[$i], $state, $pID, $user->id, $cfg);
						}

						$data[] = array(
							'status' => 1,
							'msg'	=> ''
						);

					} catch (RuntimeException $e) {

						$data[] = array(
							'status'=> 0,
							'msg'	=> $e->getMessage()
						);

					}

				// STATUS
				elseif($task == 'type') :

					$query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET '. $db->quoteName('type') .' = '.$state.' WHERE '. $db->quoteName('id') .' = '.$id;

					try {
						$db->setQuery($query);
						$db->execute();

						$data[] = array(
							'status'		=> 1,
							'newType'		=> $state,
							'id'			=> $id,
							'msg'			=> ''
						);

					} catch (RuntimeException $e) {

						$data[] = array(
							'status'=> $query,
							'msg'	=> $e->getMessage()
						);

					}

				endif; // end task

			endif; // num rows

		else :

			// INSERT
			if($task == 'save') :

				// validation
				if($save_condition) :

					// Prepare the insert query
					$query  = '
						INSERT INTO '. $db->quoteName($cfg['mainTable']) .'('.
							$db->quoteName('project_id') .','.
							$db->quoteName('type') .','.
							$db->quoteName('subject') .','.
							$db->quoteName('description') .','.
							$db->quoteName('priority') .','.
							$db->quoteName('deadline') .','.
							$db->quoteName('timePeriod') .','.
							$db->quoteName('tags') .','.
							$db->quoteName('state') .','.
							$db->quoteName('created_by')
						.') VALUES ('.
							$request['project_id'] .','.
							$request['type'] .','.
							$db->quote($request['subject']) .','.
							$db->quote($request['description']) .','.
							$request['priority'] .','.
							$db->quote($request['deadline']) .','.
							$db->quote($request['timePeriod']) .','.
							$db->quote($request['tags']) .','.
							$request['state'] .','.
							$request['author']
						.')
					';

					try {

						$db->setQuery($query);
						$db->execute();
						$id = $db->insertid();
						// Upload
						if($cfg['hasUpload'] && $id)
						$fileMsg = uploader::uploadFile($id, $cfg['fileTable'], $_FILES[$cfg['fileField']], $fileGrp, $fileGtp, $fileCls, $fileLbl, $cfg);

						// CREATE RELATIONSHIP
						if(!empty($_SESSION[$RTAG.'RelTable']) && !empty($_SESSION[$RTAG.'RelNameId']) && !empty($_SESSION[$RTAG.'AppNameId']) && !empty($request['relationId'])) :
							$query  = '
								INSERT INTO '. $db->quoteName($_SESSION[$RTAG.'RelTable']) .'('.
								$db->quoteName($_SESSION[$RTAG.'AppNameId']) .','.
								$db->quoteName($_SESSION[$RTAG.'RelNameId'])
								.') VALUES ('.
								$id .','.
								$request['relationId']
							.')';
							$db->setQuery($query);
							$db->execute();
						endif;

						// UPDATE FIELD
						$element = $elemVal = $elemLabel = '';
						if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
							$element = $_SESSION[$RTAG.'FieldUpdated'];
							$elemVal = $id;
							$query = 'SELECT '. (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $_SESSION[$RTAG.'TableField']) ? $db->quoteName($_SESSION[$RTAG.'TableField']) : $_SESSION[$RTAG.'TableField']) .' FROM '. $db->quoteName($cfg['mainTable']).' WHERE '. $db->quoteName('id') .' = '.$id.' AND state = 1';
							$db->setQuery($query);
							$elemLabel = $db->loadResult();
						endif;

						// NOTIFY ANALYSTS

						// Get brintell analysts data
						$query = 'SELECT name, nickname, email FROM '. $db->quoteName('#__'.$cfg['project'].'_staff') .' WHERE '. $db->quoteName('usergroup') .' IN (11, 12) AND ' . $db->quoteName('access') .' = 1 AND ' . $db->quoteName('state') .' = 1';
						$db->setQuery($query);
						$users = $db->loadObjectList();

						// Email Template
						$boxStyle	= array('bg' => '#fafafa', 'color' => '#555', 'border' => 'border: 4px solid #eee');
						$headStyle	= array('bg' => '#fff', 'color' => '#5EAB87', 'border' => '1px solid #eee');
						$bodyStyle	= array('bg' => '');
						$mailLogo	= 'logo-news.png';

						foreach ($users as $obj) {
							// se a senha for gerada pelo sistema, envia a senha. Senão, não envia...
							$name = !empty($obj->nickname) ? $obj->nickname : $obj->name;
							$url = $_ROOT.'/apps/issues/view?vID='.$id;
						    $subject = JText::sprintf('MSG_EMAIL_NOTIFY_SUBJECT', $sitename, $id);
							$eBody = JText::sprintf('MSG_EMAIL_NOTIFY_BODY', baseHelper::nameFormat($name), baseHelper::nameFormat($user->name), $id, $request['subject'], $url);
							$mailHtml	= baseHelper::mailTemplateDefault($eBody, JText::_('MSG_EMAIL_NOTIFY_TITLE'), '', $mailLogo, $boxStyle, $headStyle, $bodyStyle, $_ROOT);
							// envia o email
							baseHelper::sendMail($mailFrom, $obj->email, $subject, $mailHtml);
						}

						$data[] = array(
							'status'			=> 1,
							'msg'				=> JText::_('MSG_SAVED'),
							'regID'				=> $id,
							'uploadError'		=> $fileMsg,
							'parentField'		=> $element,
							'parentFieldVal'	=> $elemVal,
							'parentFieldLabel'	=> baseHelper::nameFormat($elemLabel)
						);

					} catch (RuntimeException $e) {

						// Error treatment
						switch($e->getCode()) {
							case '1062':
							$sqlErr = JText::_('MSG_SQL_DUPLICATE_KEY');
							break;
							default:
							$sqlErr = 'Erro: '.$e->getCode().'. '.$e->getMessage();
						}

						$data[] = array(
							'status'			=> 0,
							'msg'				=> $sqlErr,
							'uploadError'		=> $fileMsg
						);

					}

				else :

					$data[] = array(
						'status'				=> 0,
						'msg'					=> JText::_('MSG_ERROR'),
						'uploadError'			=> $fileMsg
					);

				endif; // end validation

			// CUSTOM: get authors list of client
			elseif($task == 'aList' && $pID != 0) :

				// Get Client of Project
				$query = 'SELECT client_id FROM '. $db->quoteName('#__'.$cfg['project'].'_projects') .' WHERE '. $db->quoteName('id') .' = '.$pID;
				$db->setQuery($query);
				$cID = $db->loadResult();
				// get client_id of project
				// get contacts list of project's client
				$query = '
					SELECT user_id, name, nickname
					FROM '. $db->quoteName('vw_'.$cfg['project'].'_teams') .'
					WHERE '. $db->quoteName('client_id') .' = '.$cID
				;

				try {
					$db->setQuery($query);
					$db->execute();
					$num_itens = $db->getNumRows();
					$list = $db->loadObjectList();

					if($num_itens) :
						foreach($list as $item) {
							$data[] = array(
								// Default Fields
								'status'		=> 1,
								'client'		=> $cID,
								// App Fields
								'id'			=> $item->user_id,
								'name'			=> baseHelper::nameFormat((!empty($item->nickname) ? $item->nickname : $item->name))
							);
						}
					else :
						$data[] = array(
							'status'			=> 2
						);
					endif;

				} catch (RuntimeException $e) {

					$data[] = array(
						'status'				=> 0,
						'msg'					=> $e->getMessage()
					);

				}

			endif; // end 'task'

		endif; // end 'id'

		$json = json_encode($data);

		# JSON if no callback
		if(!isset($_GET['callback'])) exit($json);

		# JSONP if valid callback
		if(is_valid_callback($_GET['callback'])) exit("{$_GET['callback']}($json)");

		# Otherwise, bad request
		header('status: 400 Bad Request', true, 400);

	endif;

else :

	# Otherwise, bad request
	header('status: 400 Bad Request', true, 400);

endif;

?>

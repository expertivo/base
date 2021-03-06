<?php
// BLOCK DIRECT ACCESS
if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) AND strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") :

header( 'Cache-Control: no-cache' );
header( 'content-type: application/json; charset=utf-8' );

  function is_valid_callback($subject) {
      $identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';

      $reserved_words = array('break', 'do', 'instanceof', 'typeof', 'case',
        'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue',
        'for', 'switch', 'while', 'debugger', 'function', 'this', 'with',
        'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum',
        'extends', 'super', 'const', 'export', 'import', 'implements', 'let',
        'private', 'public', 'yield', 'interface', 'package', 'protected',
        'static', 'null', 'true', 'false');

      return preg_match($identifier_syntax, $subject)
          && ! in_array(mb_strtolower($subject, 'UTF-8'), $reserved_words);
  }

  // load Joomla's framework
  require_once('../load.joomla.php');
	$app = JFactory::getApplication('site');

  defined('_JEXEC') or die;
  $ajaxRequest = true;
  require('config.php');
  // IMPORTANTE: Carrega o arquivo 'helper' do template
  JLoader::register('baseHelper', JPATH_BASE.'/templates/base/source/helpers/base.php');

  // get current user's data
  $user = JFactory::getUser();
  $groups = $user->groups;

  //joomla get request data
  $input      = $app->input;

  // Default Params
  $APPTAG			= $input->get('aTag', $APPTAG, 'str');
  $RTAG				= $input->get('rTag', $APPTAG, 'str');
  $task       = $input->get('task', null, 'str');
  $data       = array();

  if($task != null) :

  	// database connect
  	$db = JFactory::getDbo();

    // Carrega o arquivo de tradução
    // OBS: para arquivos externos com o carregamento do framework 'load.joomla.php' (geralmente em 'ajax')
    // a language 'default' não é reconhecida. Sendo assim, carrega apenas 'en-GB'
    // Para possibilitar o carregamento da language 'default' de forma dinâmica,
    // é necessário passar na sessão ($_SESSION[$APPTAG.'langDef'])
    if(isset($_SESSION[$APPTAG.'langDef']))
    $lang->load('base_'.$APPNAME, JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);

    // params requests
    $id         = $input->get('id', 0, 'int');

  	// fields 'List' requests
    $listIds    = $input->get($APPTAG.'_ids', array(), 'array');
    $ids        = (count($listIds) > 0) ? implode($listIds, ',') : $id;
    $state      = $input->get('st', 2, 'int');

    // upload actions
    $fileMsg  = '';
    if($cfg['hasUpload']) :
      $fname    = $input->get('fname', '', 'string');
      $fileId   = $input->get('fileId', 0, 'int');
      // load 'uploader' class
      JLoader::register('uploader', JPATH_BASE.'/templates/base/source/helpers/upload.php');
    endif;

  	// fields 'Form' requests
    $request                 = array();
    // default
    $request['relationId']   = $input->get('relationId', 0, 'int');
  	$request['state']        = $input->get('state', 1, 'int');
    // app
  	$request['parent_id']    = $input->get('parent_id', 0, 'int');
    $request['cparent_id']   = $input->get('cparent_id', 0, 'int');
  	$request['name']         = $input->get('name', '', 'string');
  	$request['description']  = $input->get('description', '', 'string');
  	$request['price']        = $input->get('price', 0.00, 'float');
    $request['ordering']     = $input->get('ordering', 0, 'int');
    $request['cordering']    = $input->get('cordering', 0, 'int');

    // CUSTOM -> Set Order
    function setOrder($ID, $parentId, $cparentId, $ord, $cord, $cfg) {
      if(!empty($ID) && $ID != 0) :
        // database connect
      	$db = JFactory::getDbo();
        // last item
        $query = 'SELECT MAX('.$db->quoteName('ordering').') FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '.$db->quoteName('id').' != '.$ID.' AND '. $db->quoteName('parent_id') .' = '. $parentId;
    		$db->setQuery($query);
        $max = $db->loadResult();
        if(!empty($ord) && $ord != 0 && ($ord != $cord || $parentId != $cparentId)) :
          // this item position
          $query = 'SELECT COUNT(*) FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '.$db->quoteName('ordering').' = '.$ord.' AND  '. $db->quoteName('id') .' != '. $ID .' AND '. $db->quoteName('parent_id') .' = '. $parentId;
      		$db->setQuery($query);
          $exist = $db->loadResult();
          if($ord <= $max) :
            // define a posição quando a ordem é definida no insert, altera todos a partir da nova ordem
            if($cord == 0 || $parentId != $cparentId) :
              $query = '
              UPDATE '.$db->quoteName($cfg['mainTable']).' SET '.
              $db->quoteName('ordering').' = ('.$db->quoteName('ordering').' + 1)
              WHERE '.
              $db->quoteName('ordering') .' >= ' .$ord.' AND '. // ordem ocupada
              $db->quoteName('id') .' != '. $ID.' AND '.
              $db->quoteName('parent_id') .' = '. $parentId;
          		$db->setQuery($query);
              $db->execute();
              return true;
            // se o item subir na ordem, altera todos os que estão entre a nova ordem e a anterior
            elseif($exist && $ord < $cord) :
              $query = '
              UPDATE '.$db->quoteName($cfg['mainTable']).' SET '.
              $db->quoteName('ordering').' = ('.$db->quoteName('ordering').' + 1)
              WHERE '.
              $db->quoteName('ordering') .' >= ' .$ord.' AND '. // ordem ocupada
              $db->quoteName('ordering') .' < ' .$cord.' AND '. // ordem anterior -> abaixo
              $db->quoteName('id') .' != '. $ID.' AND '.
              $db->quoteName('parent_id') .' = '. $parentId;
          		$db->setQuery($query);
              $db->execute();
              return true;
            // se o item descer na ordem, altera todos os que estão entre a nova ordem e a anterior
            elseif($exist && $ord > $cord) :
              $query = '
              UPDATE '.$db->quoteName($cfg['mainTable']).' SET '.
                $db->quoteName('ordering').' = ('.$db->quoteName('ordering').' - 1)
              WHERE '.
                $db->quoteName('ordering') .' <= ' .$ord.' AND '. // ordem ocupada
                $db->quoteName('ordering') .' > ' .$cord.' AND '.  // ordem anterior -> acima
                $db->quoteName('id') .' != '. $ID.' AND '.
                $db->quoteName('parent_id') .' = '. $parentId;
          		$db->setQuery($query);
              $db->execute();
              return true;
            else :
              return false;
            endif;
          // se o item for maior do que o máx, seta o máximo e define a ordem
          else :
            $nord = ($cord >= $max) ? $max + 1 : $max; // caso exista outro item na mesma posição
            $query = '
            UPDATE '.$db->quoteName($cfg['mainTable']).' SET '.
              $db->quoteName('ordering').' = '.$nord.'
            WHERE '.
              $db->quoteName('id') .' = '. $ID;
        		$db->setQuery($query);
            $db->execute();
            if($cord < $max) setOrder($ID, $parentId, $cparentId, $nord, $cord, $cfg);
            return true;
          endif;
        elseif(empty($ord) || $ord == 0) :
          $query = 'UPDATE '.$db->quoteName($cfg['mainTable']).' SET '.$db->quoteName('ordering').' = ('.$max.' + 1) WHERE '. $db->quoteName('id') .' = '. $ID;
      		$db->setQuery($query);
          $db->execute();
          if($ord != $cord && $cord <= $max) :// this item position
            // verifica se existe outro item na mesma posição
            $query = 'SELECT COUNT(*) FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '.$db->quoteName('ordering').' = '.$cord.' AND '. $db->quoteName('id') .' != '. $ID .' AND '. $db->quoteName('parent_id') .' = '. $parentId;
            $db->setQuery($query);
            $cexist = $db->loadResult();
            if(!$cexist) setOrder($ID, $parentId, $parentId, $max + 1, $cord, $cfg);
          endif;
          return true;
        else :
          return false;
        endif;
      else :
        return false;
      endif;
    }
    // CUSTOM -> Re-Order after delete item
    function reOrder($ID, $parentId, $ord, $cfg) {
      if(!empty($ID) && $ID != 0) :
        // database connect
      	$db = JFactory::getDbo();
        // last item
        $query = 'SELECT MAX('.$db->quoteName('ordering').') FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '.$db->quoteName('id').' != '.$ID.' AND '. $db->quoteName('parent_id') .' = '. $parentId;
    		$db->setQuery($query);
        $max = $db->loadResult();
        if(!empty($ord) && $ord != 0 && $ord < $max) :
          // altera todos os que estão abaixo
          $query = '
          UPDATE '.$db->quoteName($cfg['mainTable']).' SET '.
            $db->quoteName('ordering').' = ('.$db->quoteName('ordering').' - 1)
          WHERE '.
            $db->quoteName('ordering') .' > ' .$ord.' AND '. // ordem ocupada
            $db->quoteName('parent_id') .' = '. $parentId;
      		$db->setQuery($query);
          $db->execute();
          return true;
        else :
          return false;
        endif;
      else :
        return false;
      endif;
    }

  	if($id || (!empty($ids) && $ids != 0)) :  //UPDATE OR DELETE

      $num_rows = 0;
      if($id) :
        // GET FORM DATA
    		$query = 'SELECT * FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' = '. $id;
    		$db->setQuery($query);
    		$db->execute();
    		$num_rows = $db->getNumRows();
    		$list = $db->loadObjectList();
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

  		if($num_rows || $exist) : // verifica se existe

  			// GET DATA
  			if($task == 'get' && $list) :

          foreach($list as $item) {
      			$data[] = array(
              // Default Fields
      				'id'		      => $item->id,
      				'state'       => $item->state,
        			'prev'	      => $prev,
          		'next'	      => $next,
              // App Fields
      				'parent_id'	  => $item->parent_id,
      				'name'	      => $item->name,
      				'description' => $item->description,
      				'price'	      => $item->price,
      				'ordering'    => $item->ordering
      			);
      		}

  			// UPDATE
  			elseif($task == 'save' && $id) :

  				$query  = 'UPDATE '.$db->quoteName($cfg['mainTable']).' SET ';
  				$query .=
            $db->quoteName('parent_id') .'='. $request['parent_id'] .','.
            $db->quoteName('name') 	.'='. $db->quote($request['name']) .','.
  					$db->quoteName('description') .'='. $db->quote($request['description']) .','.
  					$db->quoteName('price') .'='. $db->quote($request['price']) .','.
  					$db->quoteName('ordering') .'='. $request['ordering'] .','.
  					$db->quoteName('state') .'='. $request['state']
  				;
  				$query .= ' WHERE '. $db->quoteName('id') .'='. $id;

  				try {

  					$db->setQuery($query);
  					$db->execute();
            // Upload
            if($cfg['hasUpload'])
            $fileMsg = uploader::uploadFile($id, $cfg['fileTable'], $_FILES[$cfg['fileField']], $cfg);

            // UPDATE FIELD
            $element = $elemVal = $elemLabel = '';
            if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
              $element = $_SESSION[$RTAG.'FieldUpdated'];
              $elemVal = $id;
              $query = 'SELECT '. $db->quoteName($_SESSION[$RTAG.'TableField']) .' FROM '. $db->quoteName($cfg['mainTable']).' WHERE id='.$id.' AND state = 1';
              $db->setQuery($query);
              $elemLabel = $db->loadResult();
            endif;

            // SET ORDER
            setOrder($id, $request['parent_id'], $request['cparent_id'], $request['ordering'], $request['cordering'], $cfg);
            if($request['parent_id'] != $request['cparent_id']) :
              // RE-ORDER
              reOrder($id, $request['cparent_id'], $request['cordering'], $cfg);
            endif;

  					$data[] = array(
  						'status' => 2,
  						'msg'	=> JText::_('MSG_SAVED'),
              'uploadError' => $fileMsg,
    					'parentField'	=> $element,
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
    					'status'=> 0,
    					'msg'	=> $sqlErr,
              'uploadError' => $fileMsg
    				);

  				}

  			// DELETE
  			elseif($task == 'del') :

  				$query = 'DELETE FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' IN ('.$ids.')';

          $Ids = explode(',', $ids);

          // RE-ORDER ITEMS
          for($i = 0; $i < count($Ids); $i++) {
            // GET PARENT INFO
            $queryOrder = 'SELECT parent_id, ordering FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' = '.$Ids[$i];
            $db->setQuery($queryOrder);
            $obj = $db->loadObject();
            // RE-ORDER
            reOrder($Ids[$i], $obj->parent_id, $obj->ordering, $cfg);
          }

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
            else :
              // FORCE DELETE RELATIONSHIPS
              // força a exclusão do(s) relacionamento(s) caso os parâmetros não sejam setados
              // isso é RECOMENDÁVEL sempre que houver um ou mais relacionamentos
                // CLIENTS PLANS -> remove os registros relacionados aos clientes
                // $query = 'DELETE FROM '. $db->quoteName('#__envolute_clients_hosts') .' WHERE '. $db->quoteName('plan_id') .' IN ('.$ids.')';
                // $db->setQuery($query);
      					// $db->execute();
            endif;

            // UPDATE FIELD
            // executa apenas com valores individuais
            $element = $elemVal = $elemLabel = '';
            if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
              $element = $_SESSION[$RTAG.'FieldUpdated'];
              $elemVal = $ids;
            endif;

  					$data[] = array(
  						'status'=> 3,
              'ids'	=> $Ids,
  						'msg'	=> JText::_('MSG_DELETED').$query,
              'uploadError' => $fileMsg,
    					'parentField'	=> $element,
    					'parentFieldVal'	=> $elemVal
  					);

  				} catch (RuntimeException $e) {

  					$data[] = array(
  						'status'=> 0,
  						'msg'	=> $e->getMessage().$query,
              'uploadError' => $fileMsg
  					);

  				}

        // STATE
        elseif($task == 'state') :

          $stateVal = ($state == 2 ? 'IF(state = 1, 0, 1)' : $state);
          $query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET '. $db->quoteName('state') .' = '.$stateVal.' WHERE '. $db->quoteName('id') .' IN ('.$ids.')';

          try {
            $db->setQuery($query);
            $db->execute();

            // UPDATE FIELD
            // executa apenas com valores individuais
            $element = $elemVal = $elemLabel = '';
            if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
              $element = $_SESSION[$RTAG.'FieldUpdated'];
              $elemVal = $ids;
              $query = 'SELECT '. $db->quoteName($_SESSION[$RTAG.'TableField']) .' FROM '. $db->quoteName($cfg['mainTable']).' WHERE '. $db->quoteName('id') .' = '.$ids;
              $db->setQuery($query);
              $elemLabel = $db->loadResult();
            endif;

            $data[] = array(
              'status' => 4,
              'state' => $state,
              'ids'	=> explode(',', $ids),
              'msg'	=> '',
    					'parentField'	=> $element,
    					'parentFieldVal'	=> $elemVal,
    					'parentFieldLabel'	=> baseHelper::nameFormat($elemLabel)
            );

          } catch (RuntimeException $e) {

            $data[] = array(
              'status'=> 0,
              'msg'	=> $e->getMessage()
            );

          }

  			// DELETE FILE
  			elseif($cfg['hasUpload'] && $task == 'delFile' && $fname) :

            // FILE: remove o arquivo
            $fileMsg = uploader::deleteFile($fname, $cfg['fileTable'], $cfg['uploadDir'], JText::_('MSG_FILEERRODEL'));

  					$data[] = array(
  						'status'=> 5,
  						'msg'	=> JText::_('MSG_FILE_DELETED'),
              'uploadError' => $fileMsg
  					);

  			// DELETE FILES
  			elseif($cfg['hasUpload'] && $task == 'delFiles' && $fileId) :

            // FILE: remove o arquivo
            $fileMsg = uploader::deleteFiles($fileId, $cfg['fileTable'], $cfg['uploadDir'], JText::_('MSG_FILEERRODEL'));

  					$data[] = array(
  						'status'=> 6,
  						'msg'	=> JText::_('MSG_FILE_DELETED'),
              'uploadError' => $fileMsg
  					);

  			endif; // end task

  		endif; // num rows

  	else :

  		// INSERT
  		if($task == 'save') :

        // validation
        if(!empty($request['name'])) :

          // Prepare the insert query
    			$query  = '
          INSERT INTO '. $db->quoteName($cfg['mainTable']) .'('.
            $db->quoteName('parent_id') .','.
            $db->quoteName('name') .','.
    				$db->quoteName('description') .','.
    				$db->quoteName('price') .','.
    				$db->quoteName('ordering') .','.
    				$db->quoteName('state') .','.
    				$db->quoteName('created_by')
    			.') VALUES ('.
            $request['parent_id'] .','.
            $db->quote($request['name']) .','.
    				$db->quote($request['description']) .','.
    				$db->quote($request['price']) .','.
    				$request['ordering'] .','.
    				$request['state'] .','.
    				$user->id
    			.')';

    			try {

    				$db->setQuery($query);
    				$db->execute();
            $id = $db->insertid();
            // Upload
            if($cfg['hasUpload'] && $id)
            $fileMsg = uploader::uploadFile($id, $cfg['fileTable'], $_FILES[$cfg['fileField']], $cfg);

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
              $query = 'SELECT '. $db->quoteName($_SESSION[$RTAG.'TableField']) .' FROM '. $db->quoteName($cfg['mainTable']).' WHERE id='.$id.' AND state = 1';
              $db->setQuery($query);
              $elemLabel = $db->loadResult();
            endif;

            // SET ORDER
            setOrder($id, $request['parent_id'], $request['cparent_id'], $request['ordering'], $request['cordering'], $cfg);

    				$data[] = array(
    					'status'=> 1,
    					'msg'	=> JText::_('MSG_SAVED'),
              'regID'	=> $id,
              'uploadError' => $fileMsg,
    					'parentField'	=> $element,
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
    					'status'=> 0,
    					'msg'	=> $sqlErr,
              'uploadError' => $fileMsg
    				);

    			}

        else :

          $data[] = array(
            'status'=> 0,
            'msg'	=> JText::_('MSG_ERROR'),
            'uploadError' => $fileMsg
          );

        endif; // end validation

      // CUSTOM: get contacts list of client
      elseif($task == 'pList') :

        // get contacts list
        $query = 'SELECT * FROM '. $db->quoteName($cfg['mainTable']) .' WHERE parent_id = 0 AND state = 1 ORDER BY name';

        try {
          $db->setQuery($query);
      		$db->execute();
      		$num_itens = $db->getNumRows();
      		$list = $db->loadObjectList();

          if($num_itens) :
            foreach($list as $item) {
        			$data[] = array(
                // Default Fields
        				'status'  => 1,
                // App Fields
        				'id'	    => $item->id,
        				'name'    => baseHelper::nameFormat($item->name)
        			);
        		}
          else :
            $data[] = array(
              'status'  => 2
            );
          endif;

        } catch (RuntimeException $e) {

          $data[] = array(
            'status'=> 0,
            'msg'	=> $e->getMessage()
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

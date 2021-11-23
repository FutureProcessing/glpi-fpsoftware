<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginFpsoftwareCommon extends CommonDBRelation {

   static private $front_url = '/plugins/fpsoftware';

   // From CommonDBRelation
   static public $itemtype_1 = 'User';
   static public $items_id_1 = 'users_id';

   static public $itemtype_2 = 'SoftwareLicense';
   static public $items_id_2 = 'softwarelicenses_id';

   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab[4]['table']           = 'glpi_softwarelicenses';
      $tab[4]['field']           = 'name';
      $tab[4]['name']            = _n('License', 'Licenses', 1);
      $tab[4]['datatype']        = 'dropdown';
      $tab[4]['massiveaction']   = false;

      $tab[5]['table']           = 'glpi_users';
      $tab[5]['field']           = 'name';
      $tab[5]['name']            = _n('User', 'Users', 1);
      $tab[5]['massiveaction']   = false;
      $tab[5]['datatype']        = 'dropdown';

      return $tab;
   }

    /**
     * Add relationship user -> license to database
     * @param array $input
     * @param array $options
     * @param bool $history
     * @return bool
     */
   function add(array $input, $options=array(), $history=true) {
       if ( (int) $input['softwarelicenses_id'] <= 0 || (int) $input['users_id'] <= 0) {
           return false;
       }

        global $DB;

        $users_id = (int) $input['users_id'];
        $softwarelicenses_id = (int) $input['softwarelicenses_id'];
        $added = date("Y-m-d H:i:s");

        $query = "INSERT INTO glpi_users_softwarelicenses (users_id, softwarelicenses_id,added) VALUES($users_id,$softwarelicenses_id,'$added')";
        $DB->query($query);

        return true;
   }

    /**
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     * @param MassiveAction $ma
     * @param CommonDBTM $item
     * @param array $ids
     * @return void
     */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {
        switch ($ma->getAction()) {
            case 'deleteSelected':
                if (isset($_POST['items']['PluginFpsoftwareCommon']) && is_array($_POST['items']['PluginFpsoftwareCommon'])) {
                    foreach($_POST['items']['PluginFpsoftwareCommon'] as $id => $val) {
                        self::deleteItem($id);
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    }
                }
            break;
        }
   }

    /**
     * @see CommonDBTM::doSpecificMassiveActions()
     * @param array $input
     * @return array
     */
   function doSpecificMassiveActions($input=array()) {
	   $res = array(
		   'ok'			=> 0,
		   'ko'			=> 0,
		   'noright'	=> 0
	   );
        switch ($input['action']) {
            case 'deleteSelected':
                if (isset($_POST['itemtype'])
						&& $_POST['itemtype'] == 'PluginFpsoftwareCommon'
						&& isset($_POST['item'])
						&& is_array($_POST['item'])) {
                    foreach($_POST['item'] as $id => $val) {
                        self::deleteItem($id);
						$res['ok']++;
                    }
				} else {
					$res['ko']++;
				}
				break;
			default :
				return parent::doSpecificMassiveActions($input);
        }
		return $res;
   }



   /**
    * Delete from database
    * @param int $id
    */
   static function deleteItem($id) {
        global $DB;

        $id = (int) $id;

        if ($id > 0) {
            $query = "DELETE FROM glpi_users_softwarelicenses WHERE id = $id LIMIT 1";
            $DB->query($query);
        }
   }

   /**
    * Count how many users have this license assigned.
    *
    * @global type $DB
    * @param type $softwarelicenses_id
    * @return int
    */
   static function countForLicense($softwarelicenses_id) {
      global $DB;

      $query = "SELECT COUNT(`glpi_users_softwarelicenses`.`id`)
                FROM `glpi_users_softwarelicenses`
                INNER JOIN `glpi_users`
                      ON (`glpi_users_softwarelicenses`.`users_id` = `glpi_users`.`id`)
                WHERE `glpi_users_softwarelicenses`.`softwarelicenses_id` = '$softwarelicenses_id'";

      $result = $DB->query($query);

      if ($DB->numrows($result) != 0) {
         return $DB->result($result, 0, 0);
      }
      return 0;
   }

   /**
    * Show users linked to a License
    *
    * @param $license SoftwareLicense object
    *
    * @return nothing
   **/
   static function showForLicense(SoftwareLicense $license) {
      global $DB, $CFG_GLPI;

      $searchID = $license->getField('id');

      if (!Software::canView() || !$searchID) {
         return false;
      }

      $canedit = PluginFpsoftwareVersionhelper::checkRights("software", array(CREATE, UPDATE, DELETE, PURGE), "Or");
      $canshowuser = User::canView();


      if (isset($_GET["start"])) {
         $start = $_GET["start"];
      } else {
         $start = 0;
      }

      if (isset($_GET["order"]) && ($_GET["order"] == "DESC")) {
         $order = "DESC";
      } else {
         $order = "ASC";
      }

      //SoftwareLicense ID
      $query_number = "SELECT COUNT(*) AS cpt
                       FROM `glpi_users_softwarelicenses`
                       INNER JOIN `glpi_users`
                           ON (`glpi_users_softwarelicenses`.`users_id`
                                 = `glpi_users`.`id`)
                       WHERE `glpi_users_softwarelicenses`.`softwarelicenses_id` = '$searchID'";

      $number = 0;
      if ($result = $DB->query($query_number)) {
         $number = $DB->result($result, 0, 0);
      }

      echo "<div class='center'>";

      if ($canedit) {
         echo "<form method='post' action='".
                $CFG_GLPI["root_doc"].self::$front_url."/front/user_softwarelicense.form.php'>";
         echo "<input type='hidden' name='softwarelicenses_id' value='$searchID'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'>";
         echo "<td>";
		 //FOR NOW ALL USERS ARE SHOWN, DON'T KNOW IF THERE SHOULD BE ANY RESTRICTION.
		 //ALSO IT CAUSES A POSSIBILITY TO ONE USER MANY TIMES.
         User::dropdown(array('right' => 'all'));

         echo "</td>";
         echo "<td><input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
      }

      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No item found')."</th></tr>";
         echo "</table></div>\n";
         return;
      }

      // Display the pager
      Html::printAjaxPager(__('Affected users'), $start, $number);

      $query = "SELECT `glpi_users_softwarelicenses`.*,
                       `glpi_users`.`name` AS username,
                       `glpi_users`.`id` AS userid,
                       `glpi_users`.`realname` AS userrealname,
                       `glpi_users`.`firstname` AS userfirstname,
                       `glpi_softwarelicenses`.`name` AS license,
                       `glpi_softwarelicenses`.`id` AS lID
                FROM `glpi_users_softwarelicenses`
                INNER JOIN `glpi_softwarelicenses`
                     ON (`glpi_users_softwarelicenses`.`softwarelicenses_id`
                          = `glpi_softwarelicenses`.`id`)
                INNER JOIN `glpi_users`
                     ON (`glpi_users_softwarelicenses`.`users_id` = `glpi_users`.`id`)
                WHERE `glpi_softwarelicenses`.`id` = '$searchID'
                LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      $rand = mt_rand();

      if ($result = $DB->query($query)) {
         if ($data = $DB->fetchAssoc($result)) {

            if ($canedit) {
               $rand = mt_rand();
               Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
			   list($higher_version, $massiveactionparams) = PluginFpsoftwareVersionhelper::massiveActionParams($rand, __CLASS__);

               // Options to update license
               $massiveactionparams['extraparams']['options']['move']['used'] = array($searchID);
               $massiveactionparams['extraparams']['options']['move']['softwares_id']
                                                                   = $license->fields['softwares_id'];

               Html::showMassiveActions($higher_version ? $massiveactionparams : __CLASS__, $massiveactionparams);
            }

            $soft       = new Software();
            $soft->getFromDB($license->fields['softwares_id']);

            $text = sprintf(__('%1$s = %2$s'), Software::getTypeName(1), $soft->fields["name"]);
            $text = sprintf(__('%1$s - ID %2$s'), $text, $license->fields['softwares_id']);
            Session::initNavigateListItems('User', $text);

            echo "<table class='tab_cadre_fixehov'>";

            $columns = array('username'          => __('Username'),
                             'userrealname'            => __('Surname'),
                             'userfirstname'            => __('First name'),
                            'added' => __('Added'));

            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
               $header_begin  .= "<th width='10'>";
               $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
               $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
               $header_end    .= "</th>";
            }

            foreach ($columns as $key => $val) {
               // Non order column
               $header_end .= "<th>$val</th>";
            }

            $header_end .= "</tr>\n";
            echo $header_begin.$header_top.$header_end;

            do {
               Session::addToNavigateListItems('User',$data["userid"]);

               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
               }

               if ($canshowuser) {
                  echo "<td><a href='user.form.php?id=".$data['userid']."'>".$data['username']."</a></td>";
               } else {
                  echo "<td>".$data['username']."</td>";
               }

               echo "<td>".$data['userrealname']."</td>";
               echo "<td>".$data['userfirstname']."</td>";
               echo "<td style=\"text-align:center;\">".$data['added']."</td>";
               echo "</tr>\n";

            } while ($data=$DB->fetchAssoc($result));
            echo $header_begin.$header_bottom.$header_end;
            echo "</table>\n";
            if ($canedit) {
               $massiveactionparams['ontop'] = false;
               Html::showMassiveActions($massiveactionparams);
               Html::closeForm();
            }

         } else { // Not found
            _e('No item found');
         }
      } // Query
      Html::printAjaxPager(__('Affected users'), $start, $number);

      echo "</div>\n";

   }

    /**
     * @see CommonGLPI::getTabNameForItem()
     **/
    function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

        switch ($item->getType()) {
            case 'SoftwareLicense' :
                if (!$withtemplate) {
                    $nb = 0;
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForLicense($item->getID());
                    }

                    return array(
                        1 => self::createTabEntry(User::getTypeName(2), $nb)
                    );
                }
            break;
        }

        return '';
    }


   /**
    * DON'T KNOW IF $TABNUM IS USED ANYWHERE, IT SHOULD CHANGE DISPLAYING (MAKE
    * IT BY ENTITY), BUT THE METHOD IS NOT PREPARED. $WITHTEMPLATE IS NOT USED
    * EITHER.
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if ($item->getType() == 'SoftwareLicense') {
         self::showForLicense($item);
      }
      return true;
   }

   public static function getFrontUrl(): string
   {
      return self::$front_url;
   }
}

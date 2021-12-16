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
    * Returns the list of users not assigned to a license.
    *
    * @param int $license_id
    *
    * @return array
    */
   private static function usersUnassignedToALicense(int $license_id): array
   {
      global $DB;

      $result = $DB->request(
         'glpi_users_softwarelicenses',
         ['softwarelicenses_id' => $license_id]
      );

      $users_assigned_to_a_license = [];
      while ($data = $result->next()) {
         $users_assigned_to_a_license[] = $data['users_id'];
      }

      if (empty($users_assigned_to_a_license)) {
         $result = $DB->request('glpi_users');
      } else {
         $result = $DB->request(
            'glpi_users',
            ['NOT' => ['id' => $users_assigned_to_a_license]]
         );
      }

      $users_unassigned_to_a_license = [];
      while ($data = $result->next()) {
         $users_unassigned_to_a_license[] = $data['id'];
      }

      return $users_unassigned_to_a_license;
   }

   /**
    * Returns form with users assigned to license.
    *
    * @param SoftwareLicense $license
    * @param bool $can_edit
    *
    * @throws GlpitestSQLError
    */
   private static function usersAssignedToLicenseForm(
      SoftwareLicense $license,
      bool $can_edit
   ): void {
      global $DB;
      global $CFG_GLPI;

      $start = isset($_GET['start']) ? $_GET['start'] : 0;
      $order = isset($_GET['order']) && ($_GET['order'] === 'DESC') ? $_GET['order'] : 'ASC';
      $sort = !empty($_GET['sort']) ? $_GET['sort'] : 'username';

      $license_id = $license->getField('id');
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
                WHERE `glpi_softwarelicenses`.`id` = '$license_id'
                ORDER BY $sort $order                
                LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      $rand = mt_rand();
      if ($result = $DB->query($query)) {
         if ($data = $DB->fetchAssoc($result)) {
            $parameters = "sort=$sort&amp;order=$order";
            $license_page_url = $CFG_GLPI['url_base'] . '/front/softwarelicense.form.php' . '?id=' .
                          $license_id;
            $number_of_items = self::numberOfUsersAssignedToLicense($license_id);
            Html::printPager($start, $number_of_items, $license_page_url, $parameters);

            if ($can_edit) {
               Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
               list($higher_version, $massive_action_params) =
                  PluginFpsoftwareVersionhelper::massiveActionParams($rand, __CLASS__);

               $massive_action_params['extraparams']['options']['move']['used'] = [$license_id];
               $massive_action_params['extraparams']['options']['move']['softwares_id']
                  = $license->fields['softwares_id'];
               Html::showMassiveActions($higher_version ? $massive_action_params : __CLASS__);
            }

            $software = new Software();
            $software->getFromDB($license->fields['softwares_id']);

            $text = sprintf(__('%1$s = %2$s'), Software::getTypeName(1), $software->fields["name"]);
            $text = sprintf(__('%1$s - ID %2$s'), $text, $license->fields['softwares_id']);
            Session::initNavigateListItems('User', $text);

            echo "<table class='tab_cadre_fixehov'>";
            $columns = [
               'username' => __('Username'),
               'userrealname' => __('Surname'),
               'userfirstname' => __('First name'),
               'added' => __('Added')
            ];

            $header_begin = "<thead><tr>";
            $header_top = '';
            $header_bottom = '';
            $header_end = '';
            if ($can_edit) {
               $header_begin .= "<th class='select-all' width='10'>";
               $header_top .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
               $header_bottom .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
               $header_end .= "</th>";
            }

            foreach ($columns as $key => $value) {
               $header_end .= "<th $value";

               if ($sort === $key) {
                  $header_end .= " class='order_$order' ";
               }

               $header_end .= "><a href='$license_page_url&sort=$key&amp;order=";
               $header_end .= (($order === "ASC") ? "DESC" : "ASC") . "'>" . $value . "</a></th>";
            }

            $header_end .= "</tr></thead>\n";
            echo $header_begin . $header_top . $header_end;

            do {
               Session::addToNavigateListItems('User', $data["userid"]);
               echo "<tr class='tab_bg_2'>";
               if ($can_edit) {
                  echo "<td>" . Html::getMassiveActionCheckBox(__CLASS__, $data["id"]) . "</td>";
               }

               $can_show_user = User::canView();
               if ($can_show_user) {
                  echo "<td><a href='user.form.php?id=" . $data['userid'] . "'>" . $data['username'] . "</a></td>";
               } else {
                  echo "<td>" . $data['username'] . "</td>";
               }

               echo "<td>" . $data['userrealname'] . "</td>";
               echo "<td>" . $data['userfirstname'] . "</td>";
               echo "<td>" . $data['added'] . "</td>";
               echo "</tr>\n";
            } while ($data = $DB->fetchAssoc($result));

            echo $header_begin . $header_bottom . $header_end;
            echo "</table>\n";

            if ($can_edit) {
               $massive_action_params['ontop'] = false;
               Html::showMassiveActions($massive_action_params);
               Html::closeForm();
            }
         } else {
            _e('No item found');
         }
      }

      echo "</div>\n";
   }

   /**
    * Returns number of users assigned to license.
    *
    * @param int $license_id
    *
    * @return int
    * @throws GlpitestSQLError
    */
   private static function numberOfUsersAssignedToLicense(int $license_id): int
   {
      global $DB;

      $query_number = "SELECT COUNT(*) AS cpt
                       FROM `glpi_users_softwarelicenses`
                       INNER JOIN `glpi_users`
                           ON (`glpi_users_softwarelicenses`.`users_id`
                                 = `glpi_users`.`id`)
                       WHERE `glpi_users_softwarelicenses`.`softwarelicenses_id` = '$license_id'";

      $number = 0;
      if ($result = $DB->query($query_number)) {
         $number = $DB->result($result, 0, 0);
      }

      return $number;
   }

   /**
    * Displays form with available users.
    *
    * @param int $license_id
    */
   private static function addUserForm(int $license_id): void
   {
      global $CFG_GLPI;

      $users = self::usersUnassignedToALicense($license_id);

      echo "<form method='post' action='" .
           $CFG_GLPI["root_doc"] . self::$front_url . "/front/user_softwarelicense.form.php'>";
      echo "<input type='hidden' name='softwarelicenses_id' value='$license_id'>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2 center'>";
      echo "<td>";
      Dropdown::show(
         'User',
         [
            'width' => '80%',
            'addicon' => false,
            'condition' => ['id' => $users]
         ]
      );
      echo "</td>";
      echo "<td><input type='submit' name='add' value=\"" . _sx(
            'button',
            'Add'
         ) . "\" class='submit'>";
      echo "</td></tr>";

      echo "</table>";
      Html::closeForm();
   }

   /**
    * Displays content for Users tab on license page.
    *
    * @param $license SoftwareLicense object
    *
    * @return void
    *
    * @throws GlpitestSQLError
    */
   static function showForLicense(SoftwareLicense $license): void
   {
      $license_id = $license->getField('id');
      if (!Software::canView() || !$license_id) {
         return;
      }

      echo "<div class='center'>";
      $can_edit = PluginFpsoftwareVersionhelper::checkRights(
         "software",
         [CREATE, UPDATE, DELETE, PURGE],
         "Or"
      );

      if ($can_edit) {
         self::addUserForm($license_id);
      }

      $number = self::numberOfUsersAssignedToLicense($license_id);
      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>" . __('No item found') . "</th></tr>";
         echo "</table></div>\n";

         return;
      }

      self::usersAssignedToLicenseForm($license, $can_edit);
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

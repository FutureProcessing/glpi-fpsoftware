<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginFpsoftwareUserdetails extends CommonDBRelation {

   /**
    * Count how many assigned licenses have user.
    * @param int $user_id
    * @return int
    */
   static function countForUserLicense($user_id) {
      global $DB;

      $user_id = (int) $user_id;

      $query = "SELECT COUNT(`glpi_users_softwarelicenses`.`id`)
                FROM `glpi_users_softwarelicenses`
                INNER JOIN `glpi_users` ON (`glpi_users_softwarelicenses`.`users_id` = `glpi_users`.`id`)
                WHERE `glpi_users_softwarelicenses`.`users_id` = '$user_id'";

      $result = $DB->query($query);

      if ($DB->numrows($result) != 0) {
         return $DB->result($result, 0, 0);
      }

      return 0;
   }

   /**
    * Displays a table with the licenses linked with the user.
    *
    * @param User $user
    *
    * @return bool
    */
   static function showLicenses(User $user): bool
   {
      global $DB;
      global $CFG_GLPI;

      $id = $user->getField('id');
      $licenses = PluginFpsoftwareUsersLicenses::getLicensesUnassignedToUser($id);

      echo "<form method='post' action='" .
           $CFG_GLPI["root_doc"] . PluginFpsoftwareCommon::$front_url .
           "/front/user_softwarelicense.form.php'>";
      echo "<input type='hidden' name='users_id' value='$id'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2 center'>";
      echo "<td>";

      Dropdown::show(
         'SoftwareLicense',
         [
            'width' => '80%',
            'addicon' => false,
            'condition' => ['id' => $licenses]
         ]
      );

      echo "</td>";
      echo "<td><input type='submit' name='add' value=\"" . _sx(
            'button',
            __('Add')
         ) . "\" class='submit'>";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();

      $query = "SELECT
                ul.added,
                sl.name AS licenses_name,
                s.name AS software_name,
                sl.id AS licenses_id,
                s.id AS software_id,
                ul.id AS softwarelicenses_id
            FROM
                glpi_users_softwarelicenses ul
                JOIN glpi_softwarelicenses sl ON (sl.id = ul.softwarelicenses_id)
                JOIN glpi_softwares s ON (s.id = sl.softwares_id)
            WHERE
                ul.users_id = '$id'
            ORDER BY
                ul.added DESC";

      $result = $DB->query($query);
      if ($DB->numrows($result) <= 0) {
         echo '<div><table class="tab_cadre_fixe"><tr><th>' . __('No items found.') . '</th></tr></table></div>';

         return true;
      }

      $rand = mt_rand();
      Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
      list($higher_version, $massive_action_params) = PluginFpsoftwareVersionhelper::massiveActionParams(
         $rand, __CLASS__);
      Html::showMassiveActions($higher_version ? $massive_action_params : __CLASS__);
      echo '<div class="spaced"><table class="tab_cadre_fixehov">';
      $header = '<tr>';
      $header .= '<th style="width:10%">' . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . '</th>';
      $header .= '<th>' . __('Software') . '</th>';
      $header .= '<th>' . __('Licenses') . '</th>';
      $header .= '<th>' . __('Added') . '</th></tr>';
      echo $header;

      while ($data = $DB->fetchAssoc($result)) {
         echo '<tr class="tab_bg_1">';
         echo '<td>' . Html::getMassiveActionCheckBox(__CLASS__, $data['softwarelicenses_id']) . '</td>';
         $software_link = '"software.form.php?id=' . $data['software_id'] . '"';
         $license_link = '"softwarelicense.form.php?id=' . $data['licenses_id'] . '"';
         echo '<td><a href=' . $software_link . '>' . $data['software_name'] . '</a></td>';
         echo '<td><a href=' . $license_link . '>' . $data['licenses_name'] . '</a></td>';
         echo '<td style="width:20%">' . $data['added'] . '</td>';
         echo '</tr>';
      }

      echo '</table>';
      $massive_action_params['ontop'] = false;
      Html::showMassiveActions($massive_action_params);
      Html::closeForm();
      echo '</div>' . PHP_EOL;

      return true;
   }

    /**
     * @see CommonGLPI::getTabNameForItem()
     **/
    function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

        switch ($item->getType()) {
            case 'User' :
                if (!$withtemplate) {
                    $nb = 0;
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForUserLicense($item->getID());
                    }

                    return array(
                        1 => self::createTabEntry(SoftwareLicense::getTypeName(2), $nb)
                    );
                }
            break;
        }

        return '';
    }

    /**
     * @see CommonGLPI::displayTabContentForItem()
     */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if ($item->getType() == 'User') {
         self::showLicenses($item);
      }

      return true;
   }

   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
   {
      if ($ma->getAction() === 'deleteSelected' && isset($_POST['items']['PluginFpsoftwareUserdetails']) && is_array(
            $_POST['items']['PluginFpsoftwareUserdetails'])) {
         foreach (array_keys($_POST['items']['PluginFpsoftwareUserdetails']) as $id) {
            PluginFpsoftwareCommon::deleteItem($id);
            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
         }
      }
   }

}

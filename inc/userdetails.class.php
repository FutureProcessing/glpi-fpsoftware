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
    * Show table wiht linked licenses to user
    * @param User $user
    */
   static function showLicenses(User $user) {
        global $DB;

		$ID = $user->getField('id');

		echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
		$header = "<tr><th>".__('Software')."</th>";
		$header .= "<th>".__('Licenses')."</th>";
		$header .= "<th>".__('Added')."</th></tr>";
		echo $header;

        $query = "SELECT
                ul.added,
                sl.name AS licenses_name,
                s.name AS software_name,
				sl.id AS licenses_id,
				s.id AS software_id
            FROM
                glpi_users_softwarelicenses ul
                JOIN glpi_softwarelicenses sl ON (sl.id = ul.softwarelicenses_id)
                JOIN glpi_softwares s ON (s.id = sl.softwares_id)
            WHERE
                ul.users_id = '$ID'
            ORDER BY
                ul.added DESC";

        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetch_assoc($result)) {
                echo "<tr class='tab_bg_1'>";
                echo "<td class='center'><a href='software.form.php?id=".$data['software_id']."'>".$data["software_name"]."</a></td>";
                echo "<td class='center'><a href='softwarelicense.form.php?id=".$data['licenses_id']."'>".$data["licenses_name"]."</a></td>";
                echo "<td class='center' style='width:20%'>".$data["added"]."</td>";
                echo "</tr>";
            }
        }  else {
            echo "<tr class='tab_bg_1'><td class='center' colspan='3'>No results.</td></tr>";
        }

		echo "</table></div>";

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

}
<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

abstract class SortingOrder
{
    const ASCENDING = "ASC";
    const DESCENDING = "DESC";

    public static function getFromString($order="") {
        return strtoupper($order) == self::DESCENDING ? self::DESCENDING : self::ASCENDING;
    }

    public static function isAscending($order="") {
        return strtoupper($order) == self::ASCENDING;
    }
}

class PluginFpsoftwareUsersLicenses extends CommonDBRelation {

    private static function isSoftwareTabActive(CommonGLPI $item) {
        return $item->getType() == 'Software' && $item instanceof Software;
    }

    private static function getCountQuery($softwareId=0) {
        $options = PluginFpsoftwareConfig::getConfigValues(array('group_by_users'));

        if ($options['group_by_users']) {
            return "SELECT
                        COUNT(*) AS rows_number
                      FROM
                        glpi_users_softwarelicenses users_softwarelicenses
                        JOIN glpi_softwarelicenses softwarelicenses ON users_softwarelicenses.softwarelicenses_id = softwarelicenses.id
                        JOIN glpi_users users ON users_softwarelicenses.users_id = users.id
                        LEFT JOIN glpi_softwarelicensetypes softwarelicensetypes ON softwarelicenses.softwarelicensetypes_id = softwarelicensetypes.id
                      WHERE
                        softwarelicenses.softwares_id = '$softwareId'";
        }
        else {
            return "SELECT
                    COUNT(*) AS rows_number
                  FROM
                    glpi_users_softwarelicenses users_softwarelicenses
	                JOIN glpi_softwarelicenses softwarelicenses ON users_softwarelicenses.softwarelicenses_id = softwarelicenses.id
                    JOIN glpi_users users ON users_softwarelicenses.users_id = users.id
                    LEFT JOIN glpi_computers computers ON users.id = computers.users_id
                    LEFT JOIN glpi_locations locations ON computers.locations_id = locations.id
                    LEFT JOIN glpi_softwarelicensetypes softwarelicensetypes ON softwarelicenses.softwarelicensetypes_id = softwarelicensetypes.id
                  WHERE
                    softwarelicenses.softwares_id = '$softwareId'";
        }
    }

    private static function getDataQuery($softwareId=0, $start, $sort, $order) {
        $options = PluginFpsoftwareConfig::getConfigValues(array('group_by_users'));

        if ($options['group_by_users']) {
            return "SELECT
                        softwarelicenses.id AS license_id,
                        softwarelicenses.name AS license_name,
                        softwarelicenses.serial AS license_serial,
                        softwarelicensetypes.name AS license_type,
                        users.id AS user_id,
                        users.name AS user_name,
                        GROUP_CONCAT(computers.id SEPARATOR ';|;') AS computer_ids,
                        GROUP_CONCAT(computers.name SEPARATOR ';|;') AS computer_names,
                        locations.id AS location_id,
                        locations.name AS location_name
                      FROM
                        glpi_users_softwarelicenses users_softwarelicenses
                        JOIN glpi_softwarelicenses softwarelicenses ON users_softwarelicenses.softwarelicenses_id = softwarelicenses.id
                        JOIN glpi_users users ON users_softwarelicenses.users_id = users.id
                        LEFT JOIN glpi_computers computers ON users.id = computers.users_id
                        LEFT JOIN glpi_locations locations ON computers.locations_id = locations.id
                        LEFT JOIN glpi_softwarelicensetypes softwarelicensetypes ON softwarelicenses.softwarelicensetypes_id = softwarelicensetypes.id
                      WHERE
                        softwarelicenses.softwares_id = '$softwareId'
                      GROUP BY user_id
                      ORDER BY $sort $order
                      LIMIT ".intval($start).",".intval($_SESSION['glpilist_limit']);
        } else {
            return "SELECT
                        softwarelicenses.id AS license_id,
                        softwarelicenses.name AS license_name,
                        softwarelicenses.serial AS license_serial,
                        softwarelicensetypes.name AS license_type,
                        users.id AS user_id,
                        users.name AS user_name,
                        computers.id AS computer_id,
                        computers.name AS computer_name,
                        locations.id AS location_id,
                        locations.name AS location_name
                      FROM
                        glpi_users_softwarelicenses users_softwarelicenses
                        JOIN glpi_softwarelicenses softwarelicenses ON users_softwarelicenses.softwarelicenses_id = softwarelicenses.id
                        JOIN glpi_users users ON users_softwarelicenses.users_id = users.id
                        LEFT JOIN glpi_computers computers ON users.id = computers.users_id
                        LEFT JOIN glpi_locations locations ON computers.locations_id = locations.id
                        LEFT JOIN glpi_softwarelicensetypes softwarelicensetypes ON softwarelicenses.softwarelicensetypes_id = softwarelicensetypes.id
                      WHERE
                        softwarelicenses.softwares_id = '$softwareId'
                      ORDER BY $sort $order
                      LIMIT " . intval($start). "," . intval($_SESSION['glpilist_limit']);
        }
    }

    private static function countLicenses(Software $software) {
        global $DB;

        $softwareId = $software->getField("id");
        $result = $DB->query(self::getCountQuery($softwareId));
        $row = $DB->fetchAssoc($result);

        return $row ? $row['rows_number'] : 0;
    }

    private static function printTableBegin() {
        return "<div class='spaced'><table class='tab_cadre_fixehov'>";
    }

    private static function getColumns() {
        return array(
            'license_name' => __('License'),
            'user_name' => __('User'),
            'computer_name' => __('Computer'),
            'location_name' => __('Location')
        );
    }

    private static function printGridColumnsHeaders($sortingOrder, $sortingColumn) {
        global $CFG_GLPI;

        $sortingOrderIndicatorImage = "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" .
            (($sortingOrder == "DESC") ? "puce-down.png" : "puce-up.png") ."\" alt='' title=''>";

        $htmlOutput = "";

        foreach (self::getColumns() as $key => $value) {
            $htmlOutput .=
                "<th>"
                .(($sortingColumn == "`$key`") ?$sortingOrderIndicatorImage:"")
                ."<a href='javascript:reloadTab(\"sort=$key&amp;order="
                .(SortingOrder::isAscending($sortingOrder) ? SortingOrder::DESCENDING : SortingOrder::ASCENDING)
                ."&amp;start=0\");'>$value</a>"
                ."</th>";
        }

        return $htmlOutput;
    }

    private static function printTableEnd() {
        return "</table></div>";
    }

    /**
     * Show table with linked licenses to user
     * @param Software $software
     * @return bool
     */
    private static function showUsersLicenses(Software $software) {
        global $DB;

        $softwareId = $software->getField("id");
        $totalRecordsCount = self::countLicenses($software);
        $currentPage = isset($_GET["start"]) ? $_GET["start"] : 0;
        $sortingOrder = SortingOrder::getFromString($_GET["order"]);
        $columnKeys = array_keys(self::getColumns());
        $sortingColumn = array_key_exists($_GET["sort"], self::getColumns()) ? $_GET["sort"] : reset($columnKeys);
        $queryResult = $DB->query(self::getDataQuery($softwareId, $currentPage, $sortingColumn, $sortingOrder));

        $options = PluginFpsoftwareConfig::getConfigValues(array('group_by_users'));

        Html::printAjaxPager(self::getTypeName(2), $currentPage, $totalRecordsCount);
        echo self::printTableBegin();
        echo self::printGridColumnsHeaders($sortingOrder, $sortingColumn);

        if ($totalRecordsCount > 0) {
            while ($data = $DB->fetchAssoc($queryResult)) {
                echo "<tr class='tab_bg_1'>";
                echo "<td class='left'><a href='softwarelicense.form.php?id=".$data['license_id']."'>".$data["license_name"]."</a> - ".$data["license_serial"]." (".$data["license_type"].") "."</td>";
                echo "<td class='left'><a href='user.form.php?id=".$data['user_id']."'>".$data["user_name"]."</a></td>";
                if ($options['group_by_users']) {
                    $computers = array();
                    if ($data['computer_ids']) {
                        $computer_ids = explode(';|;', $data['computer_ids']);
                        $computer_names = explode(';|;', $data['computer_names']);
                        foreach ($computer_ids as $index => $computer_id) {
                            $computers[] = " <a href='computer.form.php?id=".$computer_id."'>".$computer_names[$index]."</a>";
                        }
                    }
                    echo "<td class='left'>";
                    echo implode("<br /><br />", $computers)."</td>";
                }
                else {
                    echo "<td class='left'><a href='computer.form.php?id=".$data['computer_id']."'>".$data["computer_name"]."</a></td>";
                }
                echo "<td class='left'><a href='location.form.php?id=".$data['location_id']."'>".$data["location_name"]."</a></td>";
                echo "</tr>";
            }
        }  else {
            echo "<tr class='tab_bg_1'><td class='center' colspan='4'>No results.</td></tr>";
        }

        Html::printAjaxPager(self::getTypeName(2), $currentPage, $totalRecordsCount);
        echo self::printTableEnd();

        return true;
    }

    /**
     * @see CommonGLPI::getTabNameForItem()
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
        if ($withtemplate || !self::isSoftwareTabActive($item)) {
            return '';
        }

        $recordsCount = $_SESSION['glpishow_count_on_tabs'] ? self::countLicenses($item) : 0;

        return self::createTabEntry(SoftwareLicense::getTypeName(2) . ' - ' . User::getTypeName(2), $recordsCount);
    }

    /**
     * DON'T KNOW IF $TABNUM IS USED ANYWHERE, IT SHOULD CHANGE DISPLAYING (MAKE
     * IT BY ENTITY), BUT THE METHOD IS NOT PREPARED. $WITHTEMPLATE IS NOT USED
     * EITHER.
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
        if (self::isSoftwareTabActive($item)) {
            self::showUsersLicenses($item);
        }

        return true;
    }

   /**
    * Returns the ids of user's licenses.
    *
    * @param int $user_id
    *
    * @return array
    */
   public static function getUserLicenses(int $user_id): array
   {
      global $DB;

      $result = $DB->request(
         ['FROM' => 'glpi_users_softwarelicenses', 'WHERE' => ['users_id' => $user_id]]
      );

      $user_licenses = [];
      while ($data = $result->next()) {
         $user_licenses[] = $data['softwarelicenses_id'];
      }

      return $user_licenses;
   }

   /**
    * Returns the ids of licenses unassigned to user.
    *
    * @param int $user_id
    *
    * @return array
    */
   public static function getLicensesUnassignedToUser(int $user_id): array
   {
      global $DB;

      $user_licenses = self::getUserLicenses($user_id);
      if (empty($user_licenses)) {
         $result = $DB->request('glpi_softwarelicenses');
      } else {
         $result = $DB->request(
            'glpi_softwarelicenses',
            ['NOT' => ['id' => $user_licenses]]
         );
      }

      $licenses = [];
      while ($data = $result->next()) {
         $licenses[] = $data['id'];
      }

      return $licenses;
   }
}

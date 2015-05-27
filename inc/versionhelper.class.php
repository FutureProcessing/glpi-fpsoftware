<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

if (!defined('READ')) { define("READ", "r"); }
if (!defined('UPDATE')) { define("UPDATE", "w"); }
if (!defined('CREATE')) { define("CREATE", "w"); }
if (!defined('DELETE')) { define("DELETE", "w"); }
if (!defined('PURGE')) { define("PURGE", "w"); }
if (!defined('ALLSTANDARDRIGHT')) { define("ALLSTANDARDRIGHT", "1"); }
if (!defined('READNOTE')) { define("READNOTE", "1"); }
if (!defined('UPDATENOTE')) { define("UPDATENOTE", "1"); }

/**
 * Checking session rights to make this plugin work for both 0.84 and 0.85 versions.
 */
class PluginFpsoftwareVersionhelper extends CommonDBRelation {

	static function checkRights($module, $rights, $logicalOperator = "And") {
		$higher_version = (version_compare(GLPI_VERSION, '0.85', 'ge')) ? true : false;

		if ( ! is_array($rights)) {
			return Session::haveRight($module, $rights);
		} else {
			$method = "haveRights".$logicalOperator;
			$class = $higher_version ? "Session" : "PluginFpsoftwareVersionhelper";
			return $class::$method($module, $rights);
		}
	}

   static function haveRightsAnd($module, $rights=array()) {

      foreach ($rights as $right) {
         if (!Session::haveRight($module, $right)) {
            return false;
         }
      }
      return true;
   }

   static function haveRightsOr($module, $rights=array()) {

      foreach ($rights as $right) {
         if (Session::haveRight($module, $right)) {
            return true;
         }
      }
      return false;
   }

   static function massiveActionParams($rand, $class) {
		$higher_version = (version_compare(GLPI_VERSION, '0.85', 'ge')) ? true : false;
	   if ($higher_version) {
			return array($higher_version,
				array(
					'num_displayed' => $_SESSION['glpilist_limit'],
					 'container' => 'mass'.$class.$rand,
					 'specific_actions'
						=> array($class.MassiveAction::CLASS_ACTION_SEPARATOR.'deleteSelected'
							=> _x('button', 'Delete')
						   )
				 )
		   );
	   } else {
		   return array($higher_version,
				array(
                   'num_displayed' => $_SESSION['glpilist_limit'],
                   'specific_actions' => array('deleteSelected' => _x('button', 'Delete'))
                )
		   );
	   }

   }

}
<?php

include ('../../../inc/includes.php');

PluginFpsoftwareVersionhelper::checkRights("software", UPDATE);

$usl = new PluginFpsoftwareCommon();

if (isset($_POST["add"])) {
   if ($_POST['softwarelicenses_id'] > 0 ) {
      if ($usl->add($_POST)) {
         Event::log($_POST['softwarelicenses_id'], "softwarelicense", 4, "inventory",
                    //TRANS: %s is the user login
                    sprintf(__('%s associates an user and a license'), $_SESSION["glpiname"]));
      }
   }
   Html::back();

}

Html::displayErrorAndDie('Lost');

?>
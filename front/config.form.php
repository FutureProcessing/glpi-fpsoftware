<?php

include('../../../inc/includes.php');
PluginFpsoftwareVersionhelper::checkRights("config", READ);

$config = new PluginFpsoftwareConfig();

if (isset($_POST["update"])) {
    if (isset($_POST['group_by_users'])) {
        $config->setConfigValues(array('group_by_users' => (int)(bool)$_POST['group_by_users']));
    }
    HTML::back();
}


HTML::header('Configuration of FP Software plugin');

$config->showFormDisplay();

HTML::footer();


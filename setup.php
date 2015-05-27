<?php

/*
   ------------------------------------------------------------------------
   FPSoftware - Full consumables listing in User details
   Copyright (C) 2014 by Future Processing
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FPFutures project.

   FPSoftware Plugin is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FPSoftware is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with FPSoftware. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FPFutures
   @author    Future Processing
   @co-author
   @copyright Copyright (c) 2014 by Future Processing
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @since     2014

   ------------------------------------------------------------------------
 */

/**
 * Definition of the plugin version and its compatibility with the version of core
 *
 * @return array
 */
function plugin_version_fpsoftware() {
	return array('name' => "FP Software",
		'version' => '1.0.0',
		'author' => 'Future Processing',
		'license' => 'GPLv2+',
		'homepage' => 'http://future-processing.com',
		'minGlpiVersion' => '0.84'); // For compatibility / no install in version < 0.80
}

/**
 * Blocking a specific version of GLPI.
 * GLPI constantly evolving in terms of functions of the heart, it is advisable
 * to create a plugin blocking the current version, quite to modify the function
 * to a later version of GLPI. In this example, the plugin will be operational
 * with the 0.84 and 0.85 versions of GLPI.
 *
 * @return boolean
 */
function plugin_fpsoftware_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '0.84', 'lt') || version_compare(GLPI_VERSION, '0.85', 'gt')) {
		echo "This plugin requires GLPI >= 0.84 and GLPI <= 0.85";
		return false;
    }

    return true;
}

/**
 * Control of the configuration
 *
 * @param type $verbose
 * @return boolean
 */
function plugin_fpsoftware_check_config($verbose = false) {
	return true;
}

/**
 * Initialization of the plugin
 *
 * @global array $PLUGIN_HOOKS
 */
function plugin_init_fpsoftware() {
	global $PLUGIN_HOOKS;

	$PLUGIN_HOOKS['csrf_compliant']['fpsoftware'] = true;

	Plugin::registerClass('PluginFpsoftwareCommon', array('addtabon' => array('SoftwareLicense')));
    Plugin::registerClass('PluginFpsoftwareUserdetails', array('addtabon' => array('User')));
    Plugin::registerClass('PluginFpsoftwareUsersLicenses', array('addtabon' => array('Software')));
    Plugin::registerClass('PluginFpsoftwareVersionhelper');
}
<?php

class PluginFpsoftwareConfig
{

    private static $configContext = 'plugin.fpsoftware';
    private static $defaultValues = array(
        'group_by_users' => 0,
    );

    /**
     * Get configuration values
     *
     * @param array $options
     *
     * @return array
     */
    public static function getConfigValues($options)
    {
        $config = Config::getConfigurationValues(self::$configContext, $options);

        return $config + self::$defaultValues;
    }

    /**
     * Set configuration value
     *
     * @param array $options
     */
    public static function setConfigValues($options)
    {
        Config::setConfigurationValues(self::$configContext, $options);
    }

    /**
     * Print the config form for display
     *
     */
    function showFormDisplay()
    {

        $options = self::getConfigValues(array('group_by_users'));

        echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='4'>".__('FP Software config')."</th></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td width='30%'> ".__('Calculate licenses number per user instead of per computer')."</td><td  width='20%'>";
        Dropdown::showYesNo('group_by_users', $options['group_by_users']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4' class='center'>";
        echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
        echo "</td></tr>";

        echo "</table></div>";
        Html::closeForm();
    }
}
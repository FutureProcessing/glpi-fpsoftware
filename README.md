# glpi-fpsoftware
GLPI plugin that allows to assign software to users.

## General Information
By default GLPI does not allow to assign software to a user. As we all know a lot of software is currently licensed in per user model. FP Software plugin allows you to assign software to a user and view all his/hers licenses.

It changes how software/license asset looks like by providing new tab to license where you can assign users to software and another tab to software so you can see all users using the software (no matter which licenses they are using).

Global view of all licenses lists all users along with their machines, in that case if user has more than one PC he/she is listed more than once. This view can be modified to aggregate computers per license per user, by switching plugin's configuration option "Calculate licenses number per user instead of per computer". To do that, please go to plugins list, click "FP Software" plugin name and select desired option. 


![License view – new “Users” tab](https://cloud.githubusercontent.com/assets/3634020/8588884/ca0d363a-260f-11e5-9a7c-8be8b4600eb2.png)

**(License view – new “Users” tab)**

![Software view – new “Licenses – Users” tab](https://cloud.githubusercontent.com/assets/3634020/8588883/ca0995ca-260f-11e5-9e55-31dd860081ea.png)

**(Software view – new “Licenses – Users” tab)**

![User view – new “Licenses” tab](https://cloud.githubusercontent.com/assets/3634020/8588885/ca1083b2-260f-11e5-85e3-0182aa70e4b4.png)

**(User view – new “Licenses” tab)**

In general this plugin makes GLPI more user-oriented instead of PC-oriented.

### Requirements
GLPI 0.85.x, 0.90.x, 9.1.x - 9.4.x

### Install instructions
Just like all other plugins, just copy to plugins and install/enable from Administration/Plugins section.

Please be sure that name of the folder that contains plugin file is fpsoftware.

If you want to display a sum of assigned computers and users in "Affected Computers" column you need to:
- open inc/softwwarelicense.class.php (take a backup of this file before making any changes!);
- look for following function - static function showForSoftware (Software $software);
- around lines 780-787 modify following line of code:

```
$nb_assoc   = Computer_SoftwareLicense::countForLicense($data['id']);
```

to

```
$nb_assoc   = Computer_SoftwareLicense::countForLicense($data['id']) + PluginFpsoftwareCommon::countForLicense($data['id']);
```

### What can be improved?
* plugin does not validate total number of licenses with number of assigned licenses to users;

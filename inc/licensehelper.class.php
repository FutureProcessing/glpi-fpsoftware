<?php

const SOFTWARE_LICENSE_TABLE = 'glpi_softwarelicenses';

class PluginFpsoftwareLicenseHelper
{
   /**
    * @var int
    */
   public $license_id;

   /**
    * @var bool
    */
   public $unlimited_licenses;

   /**
    * @var bool|int
    */
   public $number_of_available_licenses;

   public function __construct(int $license_id)
   {
      $this->license_id = $license_id;
      $this->unlimited_licenses = $this->setUnlimitedLicenses();
      $this->number_of_available_licenses = $this->getNumberOfAvailableLicenses();
   }

   private function setUnlimitedLicenses(): bool
   {
      return $this->getNumberOfLicenses() === -1;
   }

   private function getNumberOfLicenses(): int
   {
      global $DB;

      $result = $DB->request(
         [
            'SELECT' => 'number',
            'FROM' => SOFTWARE_LICENSE_TABLE,
            'WHERE' =>
               ['id' => $this->license_id]
         ]
      );

      $number_of_licenses = [];
      while ($data = $result->next()) {
         $number_of_licenses[] = $data['number'];
      }

      return $number_of_licenses[0];
   }

   public function getNumberOfAssignedLicenses(): int
   {
      global $DB;

      $query_number = "SELECT COUNT(*) AS cpt
                       FROM `glpi_users_softwarelicenses`
                       INNER JOIN `glpi_users`
                           ON (`glpi_users_softwarelicenses`.`users_id`
                                 = `glpi_users`.`id`)
                       WHERE `glpi_users_softwarelicenses`.`softwarelicenses_id` = '$this->license_id'";

      $number = 0;
      if ($result = $DB->query($query_number)) {
         $number = $DB->result($result, 0, 0);
      }

      return $number;
   }

   /**
    * Returns the number of available licenses.
    *
    * If the number of license is unlimited, it returns false.
    *
    * @return int|bool
    */
   private function getNumberOfAvailableLicenses(): int
   {
      if ($this->unlimited_licenses) {
         return false;
      }

      return $this->getNumberOfLicenses() - $this->getNumberOfAssignedLicenses();
   }
}

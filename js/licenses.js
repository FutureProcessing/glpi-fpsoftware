$(document).ready(function () {
   $('.tab_cadre_fixehov').dataTable(
      {
         order: [],
         columnDefs: [
            {targets: [0], orderable: false}
         ]
      }
   );
});

<?php
namespace Stanford\RandomizerOveride;
/** @var \Stanford\RandomizerOveride\RandomizerOveride $module */

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';


$overriden_records = $module->getManualRandomizationOverideLogs();

echo "<h3>Log of Manual Randomization Overides</h3>";
echo "<table class='table'>";
echo "<thead><tr><th>Record ID</th><th>userid</th><th>Change Reason</th><th>date</th><th>Grouping</th></thead></tr>";
echo "<tbody>";
foreach($overriden_records as $record_id => $record){
    echo "<tr>";
    echo "<td>$record_id</td>";
    echo "<td>".$record["user"]."</td>";
    echo "<td>".$record["reason"]."</td>";
    echo "<td>".$record["date"]."</td>";
    echo "<td>".$record["grouping"]."</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table>";
?>



<?php
namespace Stanford\RandomizerOverride;
/** @var \Stanford\RandomizerOverride\RandomizerOverride $module */

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$overridden_records = $module->getManualRandomizationOverrideLogs();

echo "<h3>Log of Manual Randomization Overrides</h3>";
echo "<div class='container'>";
echo "<table class='table' id='man_rando_logs'>";
echo "<thead><tr><th>Record ID</th><th>userid</th><th>project status</th><th>Change Reason</th><th>date</th><th>Grouping</th></thead></tr>";
echo "<tbody>";
foreach($overridden_records as $record_id => $record_raw){
    $record = $module->escape($record_raw);
    echo "<tr>";
    echo "<td>$record_id</td>";
    echo "<td>".$record["user"]."</td>";
    echo "<td>".$record["project_status"]."</td>";
    echo "<td>".$record["reason"]."</td>";
    echo "<td>".$record["date"]."</td>";
    echo "<td>".$record["grouping"]."</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table>";
echo "</div>";
?>
<style>
#man_rando_logs{ width:100%;}
</style>
<script>
$("#man_rando_logs").dataTable();
</script>


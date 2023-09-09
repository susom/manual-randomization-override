<?php
namespace Stanford\RandomizerOverride;
/** @var RandomizerOverride $module */

use REDCap;

$action                 = $module->escape($_POST["action"]);
$record_id              = $module->escape($_POST["record_id"]);
$source_fields          = $module->escape($_POST["source_fields"]);
$strata_fields          = $module->escape($_POST["strata_fields"]);
$strata_source_lookup   = array_flip($strata_fields);

switch($action){
    case "check_remaining":
        // Take the current instruments strata values (which may be unsaved)
        // check record for remaining strata fields outside of this instrument
        $check_fields   = $module->escape($_POST["check_fields"]);
        $source_fields  = $module->remainingStrataLookUp($record_id, $strata_source_lookup, $check_fields, $source_fields);
        break;

    default:
    break;
}

//finally check allocation availability for hopefully completely filled out strata
$available_allocations  = $module->checkAllocationAvailability($source_fields);
echo json_encode($available_allocations);

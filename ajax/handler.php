<?php
namespace Stanford\RandomizerOveride;
/** @var \Stanford\RandomizerOveride\RandomizerOveride $module */

use REDCap;

function clean($var) {
    if(is_array($var)) {
        array_map("clean",$var);
    } else {
        $var = htmlentities(strip_tags($var),ENT_QUOTES);
    }
    return $var;
}

$action               = !empty($_POST["action"])       ? clean($_POST["action"])        : NULL;
$source_fields        = isset($_POST["source_fields"]) ? clean($_POST["source_fields"]) : NULL;
$strata_fields        = isset($_POST["strata_fields"]) ? clean($_POST["strata_fields"]) : NULL;
$strata_source_lookup = array_flip($strata_fields);
$record_id            = isset($_POST["record_id"])     ? clean($_POST["record_id"])     : NULL ;


switch($action){
    case "check_remaining":
        // Take the current instruments strata values (which may be unsaved)

        // check record for remaining strata fields outside of this instrument
        $check_fields   = isset($_POST["check_fields"]) ? clean($_POST["check_fields"]) : NULL;

        $q              = REDCap::getData('json', array($record_id) , $check_fields);
        $results        = json_decode($q,true);
        $record         = current($results);
        $remainder      = array_filter($record);

        //loop through any found strata values and fill in the full source_fields array
        foreach($remainder as $strata_fieldname => $val){
            $source_field = $strata_source_lookup[$strata_fieldname];
            $source_fields[$source_field] = $val;
        }
    break;

    default:
    break;
}
//finally check allocation availability for hopefully completely filled out strata
$available_allocations  = $module->checkAllocationAvailability($source_fields);
echo json_encode($available_allocations);

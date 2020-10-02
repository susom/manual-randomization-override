<?php
namespace Stanford\RandomizerOveride;
/** @var \Stanford\RandomizerOveride\RandomizerOveride $module */

use REDCap;


$action                 = !empty($_POST["action"]) ? $_POST["action"] : null;
$source_fields          = isset($_POST["source_fields"])  ? $_POST["source_fields"] : NULL ;
$strata_fields          = isset($_POST["strata_fields"])  ? $_POST["strata_fields"] : NULL ;
$strata_source_lookup   = array_flip($strata_fields);
$record_id              = isset($_POST["record_id"])  ? $_POST["record_id"] : NULL ;

// $module->emDebug("check allocation for strata and available target values");

switch($action){
    case "check_remaining":
        // Take the current instruments strata values (which may be unsaved)

        // check record for remaining strata fields outside of this instrument
        // TODO THIS BLOCK FROM HERE TO.... 
        $check_fields   = isset($_POST["check_fields"])  ? $_POST["check_fields"] : NULL ;

        $q              = REDCap::getData('json', array($record_id) , $check_fields);
        $results        = json_decode($q,true);
        $record         = current($results);
        $remainder      = array_filter($record);

        //loop through any found strata values and fill in the full source_fields array
        foreach($remainder as $strata_fieldname => $val){
            $source_field = $strata_source_lookup[$strata_fieldname];
            $source_fields[$source_field] = $val;
        }
        // TODO HERE is similar to a block in RandomizerOveride.php  , break it out.
    break;

    default:
    break;
}
//finally check allocation availability for hopefully completely filled out strata
$available_allocations  = $module->checkAllocationAvailability($source_fields);
echo json_encode($available_allocations);
<?php
namespace Stanford\RandomizerOveride;
/** @var \Stanford\RandomizerOveride\RandomizerOveride $module */

use REDCap;

$action                 = !empty($_POST["action"]) ? filter_var($_POST["action"], FILTER_SANITIZE_STRING) : null;
$record_id              = isset($_POST["record_id"])  ? filter_var($_POST["record_id"] , FILTER_SANITIZE_NUMBER_INT): NULL ;

//FILTER WHEN INPUT IS ARRAY, CAN BE EXPLICIT LIKE WITH $args OR IF ONLY ONE KNOWN TYPE CAN JUST USE FILTER_SANITIZE_STRING
$args = array(
    'source_field1'     => FILTER_SANITIZE_STRING,
    'source_field2'     => FILTER_SANITIZE_STRING,
);
$source_fields          = isset($_POST["source_fields"])  ? filter_var_array($_POST["source_fields"], $args)  : NULL ;
$strata_fields          = isset($_POST["strata_fields"])  ? filter_var_array($_POST["strata_fields"], $args)  : NULL ;
$strata_source_lookup   = array_flip($strata_fields);

switch($action){
    case "check_remaining":
        // Take the current instruments strata values (which may be unsaved)
        // check record for remaining strata fields outside of this instrument
        $check_fields   = isset($_POST["check_fields"])  ? filter_var_array($_POST["check_fields"], FILTER_SANITIZE_STRING) : NULL ;
        $source_fields  = $module->remainingStrataLookUp($record_id, $strata_source_lookup, $check_fields, $source_fields);
        break;

    default:
    break;
}

//finally check allocation availability for hopefully completely filled out strata
$available_allocations  = $module->checkAllocationAvailability($source_fields);
echo json_encode($available_allocations);

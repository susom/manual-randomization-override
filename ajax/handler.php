<?php
namespace Stanford\RandomizerOveride;
/** @var \Stanford\RandomizerOveride\RandomizerOveride $module */


$module->emDebug("check allocation for strata and available target values");

$source_fields          = isset($_POST["source_fields"])  ? $_POST["source_fields"]     : NULL ;
$available_allocations  = $module->checkAllocationAvailability($source_fields);

echo json_encode($available_allocations);
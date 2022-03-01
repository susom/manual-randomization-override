<?php
namespace Stanford\RandomizerOveride;

require_once "emLoggerTrait.php";
use REDCap;
use Randomization;
use Records;

class RandomizerOveride extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

	const KEY_OVERRIDE_RECORDS 	= "override-record-list";
	const KEY_OVERRIDE_USERS 	= "override-user-list";

	private $randomizer_rid,
			$target_field,
			$source_fields,
			$randomization,
			$project_status,
			$grouping;

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}

	/*

	*/
	public function hasPermission(){
		// if not, check if person has user rigths to do manual override?
		$overide_users = array_map('trim', explode(',', $this->getProjectSetting(self::KEY_OVERRIDE_USERS)));
		if(!in_array( USERID, $overide_users)){
			return false;
		}
		return true;
	}
	/*
		Inserting UI to allow for MANual Overide fo Randomization Fields
	*/
	public function redcap_data_entry_form_top( $project_id, $record, $instrument, $event_id, $group_id = NULL, $repeat_instance = 1 ) {
		$this->loadRandomizationDetails();

		// Randomization isn't enabled
		if(empty($this->randomization) ){
			return;
		}

		$record_id = filter_var($_GET["id"], FILTER_SANITIZE_NUMBER_INT);

		// Is the current instrument the randomization insturment? if no, then do nothing

		// Is the record already randomized
        list($randField, $randValue) = Randomization::getRandomizedValue($record_id);
        if (!empty($randValue)) {
			$temp 				= $this->getProjectSetting(self::KEY_OVERRIDE_RECORDS);
			$overriden_records 	= json_decode($temp,1);
			// if yes, then see if it was overrided,
			if( isset($overriden_records[$record_id]) ){
				$reason 		= $overriden_records[$record_id]["reason"];
				$change_date 	= $overriden_records[$record_id]["date"];
				$change_user 	= $overriden_records[$record_id]["user"];
				?>
				<script>
				$(window).on('load', function () {
					// need to check for the alreadyRandomizedText
					//if yes, then insert js to update 'already randomized' text to indicate details about how random value was set... who did it, when they did it, and why they did it.
					if($("#alreadyRandomizedText").length){
						$("#alreadyRandomizedText").css("color","firebrick").html("Randomization overriden by <b><?=$change_user?></b> on <span><?=$change_date?></span> <div>Reason : \"<?=$reason?>\"</div>");
					}
				});
				</script>
				<?php
			}
		};

		// if not, check if person has user rigths to do manual override?
		if(!$this->hasPermission()){
			return;
		}

		$ajaxurl 	=  $this->getUrl('ajax/handler.php');

        $this->emDebug("did it not push?");
		?>
		<script>
		//  this over document.ready because we need this last!
		$(window).on('load', function () {
			// need to check for the redcapRandomizeBtn, already done ones wont have it
			if($("#redcapRandomizeBtn").length){
				// ADD NEW BUTTON OR ENTIRELY NEW UI
				var clone_or_show = $("#randomizationFieldHtml");
				clone_or_show.addClass("custom_override")

				// EXISTING UI ALREADY AVAILABLE, REVEAL AND AUGMENT
				var custom_label 	= $("<h6>").addClass("custom_label").addClass("mt-2").text("Manually override and set randomization variable as:");
				clone_or_show.prepend(custom_label);


				var custom_hidden 	= $("<input>").attr("type","hidden").prop("name","randomizer_overide").val(true);
				clone_or_show.prepend(custom_hidden);

				var custom_reason 	= $("<input>").attr("type","text").attr("name","custom_override_reason").prop("placeholder" , "reason for using overide?").addClass("custom_reason");
				clone_or_show.append(custom_reason);

				// var custom_or 		= $("<small>").addClass("custom_or").text("*Manually override and set randomization variable as:");
				// clone_or_show.prepend(custom_or);

				var custom_note 	= $("<small>").addClass("custom_note").text("*Press save to continue");
				clone_or_show.append(custom_note);



				//ONLY ENABLE MANUAL IF STRATA ARE ALL FILLED
				var source_fields  	= <?= json_encode($this->source_fields) ?>;
				var show_overide 	= $("<button>").addClass("jqbuttonmed ui-button ui-corner-all ui-widget btn-danger custom_btn").text("Manual Selection").click(function(e){
					e.preventDefault();

					if(clone_or_show.is(":visible")){
						$("#redcapRandomizeBtn").prop("disabled",false);
					}else{
						$("#redcapRandomizeBtn").prop("disabled",true);
					}

					clone_or_show.toggle();


					checkStrataComplete(source_fields, clone_or_show);

					// $(this).prop("disabled",true);
				});

				$("#redcapRandomizeBtn").after(clone_or_show);
				$("#redcapRandomizeBtn").after(show_overide);

				for(var i in source_fields){
					$("input[name='"+source_fields[i]+"']").siblings( ".choicevert" ).find(":input").change(function(){
						checkStrataComplete(source_fields, clone_or_show);
					});
				}
			}

			// CHECK NEWLY (not yet saved) INPUT STRATA ON CURRENT INSTRUMENT, BUT ALSO CHECK SAVED STRATA FROM OTHER INSTURMENTS
			function checkStrataComplete(source_fields, overide_ui_el){
				var complete 			= true;
				var source_field_values = {};
				var check_fields 		= [];
				for(var i in source_fields){
					var source_val = null;
					if($("input[name='"+source_fields[i]+"']").length && !$("input[name='"+source_fields[i]+"']").val() == ""){
						var source_val  = $("input[name='"+source_fields[i]+"']").val();
					}else{
						check_fields.push(source_fields[i]);
					}
					source_field_values[i] = source_val;
				}
				console.log("get what we can from this instrument, ajax for the rest",source_field_values, check_fields);

				// first AJAX GET all values
				var data = {"action" : "check_remaining", "record_id" : "<?=$record_id?>", "source_fields" : source_field_values, "check_fields" : check_fields, "strata_fields" : source_fields};
				$.ajax({
					url: "<?= $ajaxurl ?>",
					type:'POST',
					data: data,
					dataType: 'json',
					success:function(result){
						// preset them to show none avaialble, then unset if they actualy are available
						overide_ui_el.find(".choicevert").unbind("click");
						if(result.hasOwnProperty("error")){
							overide_ui_el.find(".choicevert").click(function(){
								alert("Incomplete strata, allocation values unavailable.");
							});
						}else{
							overide_ui_el.find(".choicevert").click(function(){
								alert("No allocations for this target value are available for this combination of strata.");
							});

							if( !$.isEmptyObject(result) ){
								// enable manual overide inputs
								for(var target_value in result){
									overide_ui_el.find("input[value='"+target_value+"'").prop("disabled",false);
									overide_ui_el.find("input[value='"+target_value+"'").parents(".choicevert").unbind("click");
								}
							}
						}
					}
				});
			}

			function hasValue(elem) {
				return $(elem).filter(function() { return $(this).val(); }).length > 0;
			}
		});
		</script>
		<style>
			.custom_reason{
				padding: 5px;
				margin: 8px 0 0;
			}
			.custom_btn{
				margin-left:5px;
				padding:3px 8px;
				background:#bd2130;
				color:#fff;
				cursor:pointer;
			}
			.custom_btn:disabled{
				background: #cccccc;
				border-color: #999;
				color: #999;
			}
			.custom_override {
				font-size:130%;
				color:#000;
			}
			.custom_or{
				margin:10px 0;
			}
			.custom_label{
				font-size:108%;
				margin:0;
			}
			.custom_note{
				display:block;
				margin:10px 0 5px;
			}
			.custom_override input {
				vertical-align:middle;
			}
			.custom_override label{
				vertical-align:middle;
			}
		</style>
		<?php
	}

	/*
		Inserting UI to allow for MANual Overide fo Randomization Fields
	*/
	public function redcap_module_link_check_display($project_id, $link){
		if($this->hasPermission()){
			return $link;
		}
	}

	/*
		Updates allocation table when manually overidden and saved
	*/
	public function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance=1) {
		//Look for custom post var for randomizer
		if(isset($_POST["randomizer_overide"])){
			$this->loadRandomizationDetails();
			$record_id = filter_var($_POST["record_id"], FILTER_SANITIZE_NUMBER_INT);

			$desired_target_value 	= !empty($_POST[$this->target_field]) ? filter_var($_POST[$this->target_field], FILTER_SANITIZE_STRING) : null;
			if(!empty($desired_target_value)){

				$source_fields 	= array();
				// Need to getData for strata fields that are OFF the current instrument then combine with these values before trying to save

				$check_fields 			= array();
				$strata_source_lookup   = array_flip($this->source_fields);
				foreach($this->source_fields as $source_field => $source_field_var){
					$source_field_value 				= !empty($_POST[$source_field_var]) ? filter_var($_POST[$source_field_var], FILTER_SANITIZE_STRING) : null;
					$source_fields[$source_field] 	= $source_field_value;

					if(is_null($source_field_value)){
						array_push($check_fields, $source_field_var);
					}
				}

                $source_fields = $this->remainingStrataLookUp($record_id, $strata_source_lookup, $check_fields, $source_fields);

				$this->claimAllocationValue($record_id, $desired_target_value, $source_fields);

				// STORE INTO EM Project Settings REcord of Manual Overide
				$temp 				= $this->getProjectSetting(self::KEY_OVERRIDE_RECORDS);
				$overriden_records 	= json_decode($temp, 1);
				$reason 			= !empty($_POST["custom_override_reason"]) ? filter_var($_POST["custom_override_reason"], FILTER_SANITIZE_STRING) : "n/a";
				$overriden_records[$record_id] = array("user" => USERID, "date" => Date("m/d/Y"), "reason" => $reason, "project_status" => $this->project_status);
				$this->setProjectSetting(self::KEY_OVERRIDE_RECORDS, json_encode($overriden_records));

				REDCap::logEvent("randomization manually overidden for this record", $desired_target_value);
			}else{
				$this->emDebug("missing target value");
			}
		}
	}

    public function remainingStrataLookUp($record_id, $strata_source_lookup, $check_fields, $source_fields = array()){
        $q              = REDCap::getData('json', array($record_id) , $check_fields);
        $results        = json_decode($q,true);
        $record         = current($results);

        $remainder      = array_filter($record, function($v){
            return $v !== false && !is_null($v) && ($v != '' || $v == '0');
        });

        //loop through any found strata values and fill in the full source_fields array
        foreach($remainder as $strata_fieldname => $val){
            $source_field = $strata_source_lookup[$strata_fieldname];
            $source_fields[$source_field] = $val;
        }

        return $source_fields;
    }

	/*
		Returns randomization details like RID + strata details + target field name
	*/
	public function loadRandomizationDetails(){
		/** @var \Project $Proj */
		global $Proj;

		// FIND THE randomization details (target + sourcefields) ENTRY IN redcap_randomization
		$pid 	= $this->getProjectId();
		$sql 	= "SELECT * FROM redcap_randomization WHERE project_id = ?" ;
		$q 		= $this->query($sql, array($pid));

		if($q->num_rows){
			while ($data = db_fetch_assoc($q)) {
				$this->randomizer_rid 	= $data["rid"];
				$this->target_field 	= $data["target_field"];

				$this->group_by 		= $data["group_by"];
				$this->grouping 		= null;
				$this->project_status 	= $Proj->project["status"];
				$this->randomization 	= $Proj->project["randomization"];

				$non_empty 				= array_filter($data);
				$source_fields_arr		= array();
				foreach($non_empty as $key => $val){
					if(strpos($key, "source_field") > -1){
						$source_fields_arr[$key] = $val;
					}
				}
				$this->source_fields = $source_fields_arr;
			}
		}
	}

	/*
		Returns array of count of available allocation slots for strata combo
	*/
	public function checkAllocationAvailability($source_field_arr){
		$this->loadRandomizationDetails();

		$temp           = array();
        $param_array    = array();

        foreach($source_field_arr as $source_field => $val){
			if(empty($val) && !isset($val) ){
				//empty val, so dont bother
				$this->emDebug("missing sourcefield val for $source_field");
				return array("error" => "incomplete strata");
				break;
			}
			$temp[]         = "$source_field = ?";
            $param_array[]  = $val;
		}
		$source_field_values 	= implode(" AND ", $temp);
		$target_field_values 	= array();

        if(!empty($this->randomizer_rid)){
			$sql 	= "SELECT * FROM redcap_randomization_allocation WHERE rid = ? AND project_status = ? AND $source_field_values";
			$params = array($this->randomizer_rid, $this->project_status);
            $params = array_merge($params, $param_array);
            $q 		= $this->query($sql, $params);
			if($q->num_rows){
				while ($data = db_fetch_assoc($q)) {
					if(!empty($data["is_used_by"])){
						continue;
					}
					$target_field_values[] 	= $data["target_field"];
				}
			}
		}

		// RETURNS COUNT OF AVAILABLE ALLOCATION SLOTS FOR THIS COMBINATION OF STRATA
		return array_count_values($target_field_values);
	}

	/*
		Updates next available allocation slot that matches strata combo for specified record
	*/
	public function claimAllocationValue($record_id, $desired_target_value, $source_field_arr){
		$this->loadRandomizationDetails();

		// SELECT TO FIND FIRST AVAILABLE TARGET VALUE WITH MATCHING STRATA
		$temp           = array();
        $param_array    = array();
		foreach($source_field_arr as $source_field => $val){
			$temp[]         = "$source_field = ?";
            $param_array[]  = $val;
		}
		$source_field_values 	= implode(" AND ", $temp);
		$sql 					= "SELECT * FROM redcap_randomization_allocation WHERE project_status = ? AND target_field= ? AND $source_field_values" ;
        $params = array($this->project_status, $desired_target_value);
        $params = array_merge($params, $param_array);
        $q 						= $this->query($sql, $params);

		if($q->num_rows){
			while ($data = db_fetch_assoc($q)) {
				if(empty($data["is_used_by"])){
					$available_aid = $data["aid"];
					break;
				}
			}

			if(isset($available_aid)){
				// THEN UPDATE - THIS IS GOOD
				$sql 	= "UPDATE redcap_randomization_allocation SET is_used_by = ? WHERE aid = ?";
				$q 		= $this->query($sql, array($record_id, $available_aid));
			}
		}
	}

	/*
		get logs of all manual randomizers
	*/
	public function getManualRandomizationOverideLogs(){
		$this->loadRandomizationDetails();
		$temp 				= $this->getProjectSetting(KEY_OVERRIDE_RECORDS);
		$overriden_records 	= json_decode($temp,1);
		ksort($overriden_records);

		$recordids  = array_keys($overriden_records);
		$fields     = array("record_id", $this->target_field);
		$q          = \REDCap::getData('json',$recordids , $fields);
		$results    = json_decode($q,true);

		foreach($results as $result){
			$record_id 	= $result["record_id"];
			$outcome 	= $result["outcome"];
			$overriden_records[$record_id]["grouping"] = $outcome;
		}
		return $overriden_records;
	}
}

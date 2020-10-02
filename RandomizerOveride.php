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
		Inserting UI to allow for MANual Overide fo Randomization Fields
	*/
	public function redcap_data_entry_form_top( $project_id, $record, $instrument, $event_id, $group_id = NULL, $repeat_instance = 1 ) {
		$this->loadRandomizationDetails();

		// Randomization isn't enabled
		if(empty($this->randomization) ){
			return;	
		}
		
		$record_id = $_GET["id"];
		
		// Is the current instrument the randomization insturment? if no, then do nothing
		// $this->emDebug($instrument);

		// Is the record already randomized
        list($randField, $randValue) = Randomization::getRandomizedValue($record_id);
        if (!empty($randValue)) {
			$temp 				= $this->getProjectSetting(KEY_OVERRIDE_RECORDS);
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
		$override_users = array();
		if(!in_array( USERID, $override_users) && false){
			return;
		}
		
		$ajaxurl 	=  $this->getUrl('ajax/handler.php');
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
				var custom_label 	= $("<h6>").addClass("custom_label").text("Manually set value:");
				clone_or_show.prepend(custom_label);
				
				var custom_or 		= $("<h5>").addClass("custom_or").text("-or-");
				clone_or_show.prepend(custom_or);
				
				var custom_reason 	= $("<input>").attr("type","text").attr("name","custom_override_reason").prop("placeholder" , "reason for using overide?").addClass("custom_reason");
				clone_or_show.append(custom_reason);

				var custom_note 	= $("<small>").addClass("custom_note").text("*Claims next available slot for value from the allocation table");
				clone_or_show.append(custom_note);

				var custom_hidden 	= $("<input>").attr("type","hidden").prop("name","randomizer_overide").val(true);
				clone_or_show.prepend(custom_hidden);
				
				var show_overide 	= $("<button>").addClass("jqbuttonmed ui-button ui-corner-all ui-widget btn-danger custom_btn").text("Manual Selection").click(function(e){
					e.preventDefault();
					clone_or_show.css("display","block");
					$(this).prop("disabled",true);
				});

				$("#redcapRandomizeBtn").after(clone_or_show);
				$("#redcapRandomizeBtn").after(show_overide);


				//ONLY ENABLE MANUAL IF STRATA ARE ALL FILLED
				var source_fields  = <?= json_encode($this->source_fields) ?>;
				for(var i in source_fields){
					$("input[name='"+source_fields[i]+"']").siblings( ".choicevert" ).find(":input").change(function(){
						checkStrataComplete(source_fields, clone_or_show);
					});
				}
			}

			function checkStrataComplete(source_fields, overide_ui_el){
				var complete = true;
				for(var i in source_fields){
					if(!hasValue( "input[name='"+source_fields[i]+"']" ) ){
						complete = false;
						break;
					}
				}

				if(complete){
					var source_field_values = {};
					for(var i in source_fields){
						source_field_values[i] = $("input[name='"+source_fields[i]+"']").val();
					}

					var data = {"source_fields" : source_field_values};
					$.ajax({
						url: "<?= $ajaxurl ?>",
						type:'POST',
						data: data,
						dataType: 'json',
						success:function(result){
							// preset them to show none avaialble, then unset if they actualy are available
							overide_ui_el.find(".choicevert").unbind("click");
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
					});
					
				}
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
		Updates allocation table when manually overidden and saved
	*/
	public function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance=1) {
		//Look for custom post var for randomizer 
		if(isset($_POST["randomizer_overide"])){
			$this->loadRandomizationDetails();

			$desired_target_value 	= !empty($_POST[$this->target_field]) ? $_POST[$this->target_field] : null;
			if(!empty($desired_target_value)){
				$source_field_arr 	= array();
				foreach($this->source_fields as $source_field => $source_field_var){
					$source_field_value 				= !empty($_POST[$source_field_var]) ? $_POST[$source_field_var] : null;
					$source_field_arr[$source_field] 	= $source_field_value;
				}
				$record_id = $_POST["record_id"];
				$this->claimAllocationValue($record_id, $desired_target_value, $source_field_arr);

				// STORE INTO EM Project Settings REcord of Manual Overide
				$temp 				= $this->getProjectSetting(KEY_OVERRIDE_RECORDS);
				$overriden_records 	= json_decode($temp, 1);
				$reason 			= !empty($_POST["custom_override_reason"]) ? $_POST["custom_override_reason"] : "n/a";
				$overriden_records[$record_id] = array("user" => USERID, "date" => Date("m/d/Y"), "reason" => $reason);
				$this->setProjectSetting(KEY_OVERRIDE_RECORDS, json_encode($overriden_records));
				// $this->emDebug("overide_records", $overriden_records);
				// $this->emDebug("augment save with randomizer overide functionality", $_POST, $source_field_arr);
			}else{
				$this->emDebug("missing target value");
			}
		}
	}

	/* 
		Returns randomization details like RID + strata details + target field name
	*/
	public function loadRandomizationDetails(){
		/** @var \Project $Proj */
		global $Proj;

		// FIND THE randomization details (target + sourcefields) ENTRY IN redcap_randomization
		$pid 	= $this->getProjectId();
		$sql 	= "SELECT * FROM redcap_randomization WHERE project_id = $pid" ;
		$q 		= $this->query($sql, array());

		if($q->num_rows){
			while ($data = db_fetch_assoc($q)) {
				$this->randomizer_rid 	= $data["rid"];
				$this->target_field 	= $data["target_field"];

				//TODO what and where these come from
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
		
		$temp = array();
		foreach($source_field_arr as $source_field => $val){
			$temp[] = "$source_field = $val";
		}
		$source_field_values 	= implode(" AND ", $temp);
		$target_field_values 	= array();
		$project_status 		= " AND project_status = " . $this->project_status; // " AND project_status=0
		$grouping 				= ""; // " AND group_id=n
		if(!empty($this->randomizer_rid)){
			$sql 	= "SELECT * FROM redcap_randomization_allocation WHERE rid=".$this->randomizer_rid." AND $source_field_values $project_status $grouping";
			$q 		= $this->query($sql, array());
			if($q->num_rows){
				while ($data = db_fetch_assoc($q)) {
					if(!empty($data["is_used_by"])){
						continue;
					}
					$temp 					= array();
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
		$temp = array();
		foreach($source_field_arr as $source_field => $val){
			$temp[] = "$source_field = $val";
		}
		$source_field_values 	= implode(" AND ", $temp);
		$project_status 		= " AND project_status = " . $this->project_status; // " AND project_status=0
		$grouping 				= ""; // " AND group_id=n

		//TODO will have to factor in "project_status" and "group_id"
		$sql 					= "SELECT * FROM redcap_randomization_allocation WHERE $source_field_values AND target_field=$desired_target_value $project_status $grouping" ;
		$q 						= $this->query($sql, array());
		// $this->emDebug("sql to search for available strata + target values, ", $sql);

		if($q->num_rows){
			while ($data = db_fetch_assoc($q)) {
				if(empty($data["is_used_by"])){
					$this->emDebug("first empty available ", $data["aid"]);
					$available_aid = $data["aid"];
					break;
				}
			}

			if(isset($available_aid)){
				// THEN UPDATE - THIS IS GOOD
				$sql 	= "UPDATE redcap_randomization_allocation SET is_used_by=$record_id WHERE aid=$available_aid";
				$q 		= $this->query($sql, array());
				$this->emDebug("random target value set to $desired_target_value for record id $record_id");
			}
		}
	}			
}

<?php
namespace Stanford\RandomizerOveride;

require_once "emLoggerTrait.php";

class RandomizerOveride extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

	private $randomizer_rid, $target_field, $source_fields, $project_status, $grouping;
	
    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated

		// small query, do it everytime we come to an add/edit entry page , or try to only limit call to pages with randomization that have not been set yet
		$this->getAllocationDetails();
	}

	/* 
		Inserting UI to allow for MANual Overide fo Randomization Fields
	*/
	public function redcap_data_entry_form_top(  ) {
		$ajaxurl 	=  $this->getUrl('ajax/handler.php', true, true);
		?>
		<script>
		//  this over document.ready because we need this last!
		$(window).on('load', function () {
			// need to check for the redcapRandomizeBtn, already done ones wont have it
			if($("#redcapRandomizeBtn").length){
				// ADD NEW BUTTON OR ENTIRELY NEW UI?
				// EXISTING UI ALREADY AVAILABLE, REVEAL AND AUGMENT
				var clone_or_show = $("#randomizationFieldHtml");
				clone_or_show.css("display","block");
				clone_or_show.addClass("custom_override")
				
				var custom_label 	= $("<h6>").addClass("custom_label").text("Manually set value:");
				clone_or_show.prepend(custom_label);
				
				var custom_or 		= $("<h5>").addClass("custom_or").text("-or-");
				clone_or_show.prepend(custom_or);
				
				var custom_note 	= $("<small>").addClass("custom_note").text("*Claims next available slot for value from the allocation table");
				clone_or_show.append(custom_note);

				var custom_hidden 	= $("<input>").attr("type","hidden").prop("name","randomizer_overide").val(true);
				clone_or_show.prepend(custom_hidden);
				$("#redcapRandomizeBtn").after(clone_or_show);

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
							}else{
								// add alert that no allocations available for this combination of strata
								overide_ui_el.find(".choicevert").click(function(){
									alert("No allocations for this value are available for this combination of strata.");
								});
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
	public function redcap_save_record() {
		/* 
			On Save Record 
			Find the unclaimed 

			(
				[submit-action] => submit-btn-saverecord
				[hidden_edit_flag] => 1
				[__old_id__] => 4
				[record_id] => 4
				[strata_1] => 1
				[strata_1___radio] => 1
				[strata_2] => 1
				[strata_2___radio] => 1
				[outcome] => 1
				[outcome___radio] => 1
				[my_first_instrument_complete] => 0
			)
		*/

		if(isset($_POST["randomizer_overide"])){
			$desired_target_value 	= !empty($_POST[$this->target_field]) ? $_POST[$this->target_field] : null;
			if(!empty($desired_target_value)){
				$source_field_arr 	= array();
				foreach($this->source_fields as $source_field => $source_field_var){
					$source_field_value 				= !empty($_POST[$source_field_var]) ? $_POST[$source_field_var] : null;
					$source_field_arr[$source_field] 	= $source_field_value;
				}
				$record_id = $_POST["record_id"];
				$this->claimAllocationValue($record_id, $desired_target_value, $source_field_arr);
				$this->emDebug("augment save with randomizer overide functionality", $_POST, $source_field_arr);
			}else{
				$this->emDebug("missing target value");
			}
		}
	}

	/* 
		Returns randomization details like RID + strata details + target field name
	*/
	public function getAllocationDetails(){
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
				$this->project_status 	= 0;

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
		$temp = array();
		foreach($source_field_arr as $source_field => $val){
			$temp[] = "$source_field = $val";
		}
		$source_field_values 	= implode(" AND ", $temp);
		$target_field_values 	= array();
		$project_status 		= ""; // " AND project_status=0
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
	public function claimAllocationValue($record_id=4, $desired_target_value=1, $source_field_arr=array("source_field1"=>1, "source_field2" => 1)){
		// SELECT TO FIND FIRST AVAILABLE TARGET VALUE WITH MATCHING STRATA
		$temp = array();
		foreach($source_field_arr as $source_field => $val){
			$temp[] = "$source_field = $val";
		}
		$source_field_values 	= implode(" AND ", $temp);
		$project_status 		= ""; // " AND project_status=0
		$grouping 				= ""; // " AND group_id=n

		//TODO will have to factor in "project_status" and "group_id"
		$sql 					= "SELECT * FROM redcap_randomization_allocation WHERE $source_field_values AND target_field=$desired_target_value $project_status $grouping" ;
		$q 						= $this->query($sql, array());
		$this->emDebug("sql to search for available strata + target values, ", $sql);

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

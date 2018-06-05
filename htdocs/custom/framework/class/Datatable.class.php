<?php
/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

Class Datatable {

	/**
		@var Limit
	*/
	public $DisplayLength = 50;
	/**
		@var file name destination
	*/
	public $destination = '';
	/**
		@var list of colomn
	*/
	public $cols = array();
	/**
		@var add column action after all cols
	*/
	public $colAction = false;
	/**
		@var search
	*/
	public $search = false;
	/**
		@var groupby
	*/
	public $groupby = '';
	/**
		@var sql requet string
	*/
	public $sql = '';
	/**
		@var Sort
	*/
	public $sort = array();


	public function __construct($destination, $search='', $DisplayLength){
		$this->destination = $destination.'.php';

		if( !empty($search) )
			$this->search = $search ;


		$this->DisplayLength = ( GETPOST('iDisplayLength') > 0 ?  GETPOST('iDisplayLength') : $DisplayLength);

	}

	/**
	*/
	public function SetAction($colAction=false){
		$this->colAction = $colAction;
	}

	/**
		@brief define Column
		@param $cols array colomn
	*/
	public function SetColumn($cols = array()){
		$this->cols = $cols;

		if($this->colAction){
			// add action column
			$this->cols[] = '';
		}

	}

	public function SetGroupBy($groupby=''){
		$this->groupby = " GROUP BY ".$groupby." ";
	}

	public function GetOutput( DoliDb $db,  $sql ){

		$sql .= ' '.$this->AppliFilter();
		$sql .= ' '.$this->groupby;
		$prevsql = $sql;

		$num = $db->num_rows( $db->query($prevsql ) );

		$sql .=$this->AppliSort();
		$sql .=$this->AppliLimit( $num );

		$view = intval( GETPOST('iDisplayLength') );
		if($view<=0)
			$view = $this->DisplayLength;

		if($num < $view)
			$view = $num;

		$output = array(
						"draw"=>1,
						"sEcho" => intval( GETPOST('sEcho') ),
						"iTotalRecords" =>  $view,
						"iTotalDisplayRecords" => $num ,
						"aaData" => array()
		);

		$this->sql = $sql;


		return $output;
	}

	public function GetSql(){
		return $this->sql;
	}

	public function AppliFilter(){
		$sql ='';
		/*
			* Filtering
			* NOTE this does not match the built-in DataTables filtering which does it
			* word by word on any field. It's possible to do here, but concerned about efficiency
			* on very large tables, and MySQL's regex functionality is very limited
			*/
		if ( $this->search !=false )
		{
				$search = addslashes(trim($this->search));
				$sql.=" AND ( ";
				$sqlt='';
				foreach($this->cols as $row)
					if(!empty($row)) $sqlt.="OR ".$row." LIKE '%".$search."%' ";
				$sql.=substr($sqlt, 2);

// 					$sql.="  OR  ";

				$sql.="  ) ";
		}


		return $sql;
	}

	public function AppliSort(){
		$sql = '';
		$sOrder = "ORDER BY";
		if ( isset($_GET['iSortCol_0']) ){
			for ( $i=0 ; $i<intval( GETPOST('iSortingCols') ) ; $i++ )
			{

							if ( GETPOST('bSortable_'.intval(GETPOST('iSortCol_'.$i))) == "true"  )
							{
									$sOrder .=" ".$this->cols[ intval(GETPOST('iSortCol_'.$i)) ]." ".(GETPOST('sSortDir_'.$i)==='asc' ? 'asc' : 'desc').", ";
							}
			}

			$sOrder = substr_replace( $sOrder, "", -2 );
			if ( $sOrder == "ORDER BY" )
			{
							$sOrder = "";
			}
			$sql .=" ".$sOrder;
		}
		else{
			$sql .=" ".$sOrder." ".$this->cols[0]." desc";
		}

		return $sql;
	}

	public function AppliLimit( $max = 1 ){
		$sql = " LIMIT ";
		$test = GETPOST('iDisplayStart');
		if ( !empty( $test ) && GETPOST('iDisplayLength') != '-1' )
		{
						$sql.= (($test<= $max )?intval( GETPOST('iDisplayStart') ) : 0 ).", ".
										intval( GETPOST('iDisplayLength') );
		}
		else
			$sql .=" ".$this->DisplayLength;

		return $sql;
	}


	public function DisplayTable(){
	}




	/**
		@brief Display Html Header Section
	*/
	public function DisplayHeader($searchkey='search', $params='', $functioncallback=false){

			if(!empty($params))
				$params ='&'.$params;
		?>
		<style type="text/css" >
				table  tr.modified{
						background:#bcbcbc;
				}
				#dataTable  tr.modified{
						background:#bcbcbc;
				}
		</style>
		<script type="text/javascript" language="javascript" src="<?php echo dol_buildpath('/framework/media/js/jquery.dataTables.min.js', 1) ?>"></script>
		<link href="<?php echo dol_buildpath('/framework/css/demo_table.css', 1) ?>" rel="stylesheet" type="text/css">

		<script type="text/javascript">

				function calllinkajax(){

					$('a.ajaxexe').click( function() {
						var curr = $(this);
						$.get( curr.attr('href'), function(data) {
								curr.replaceWith(data);

								setTimeout("calllinkajax()",200);
							});
						return false;
					});


				}





		// 		function newform(ligne){
		// 			$('div#ajaxcodebarre_'+ligne+' form').submit( function() {
		// 					var value = $('input[name=barcode]').val();
		// 					var random = $('input[name=random]').val();
		// 					$.get('liste.php?action=addcodebarconfirm&id='+ligne+'&value='+value+'&random='+random ,  function(data) {
		// 							if(data !=1)
		// 								$.get('liste.php?action=addcodebar&id='+ligne ,  function(data) {
		// 									$('div#ajaxcodebarre_'+ligne+'').replaceWith('<div id="ajaxcodebarre_'+ligne+'">'+data+'</div>');
		// 									newform(ligne);
		// 									return false;
		// 								});
		// 							else
		// 								$.get('liste.php?action=resetrowcodebarre&id='+ligne ,  function(data) { $('div#ajaxcodebarre_'+ligne+'').replaceWith(data);});
		//
		// 							return false;
		// 					});
		// 					return false;
		// 			});
		// 	}

				function InitoTable(){
						var oTable = $('#dataTable').dataTable( {
												"sDom": '<"top"ilfp<"clear">>rt<"bottom"ip<"clear">>',
												"iDisplayStart": <?php echo $this->DisplayLength; ?>,
												"iDisplayLength": <?php echo $this->DisplayLength; ?> ,
												"Processing": true,
												"bServerSide": true,
												"bStateSave": true,
												"aoColumns": [
													<?php foreach($this->cols as $row):
														 echo '{ "bSortable":  '.(empty($row)? 'false' : 'true' ).'  },'."\n";
															endforeach; ?>
														],
												"sAjaxSource": "<?php echo dol_buildpath($this->destination.'?action=dataTable'.$params,2) ?>",
												ajax: "<?php echo dol_buildpath($this->destination.'?action=dataTable'.$params,2) ?>",
												"fnDrawCallback": <?php echo ((!$functioncallback)?'fnCallback':$functioncallback) ?>,
												"fnInitComplete": function () {
														$('<input type="button" name="reset" id="reset" value="reset" />').insertAfter($('#dataTable_filter input[type=text]'));
												},
// 												"oSearch": {"sSearch": "<?php //echo GETPOST($searchkey); ?>"}
								} );



// 								oTable.ajax.reload();

								return oTable;
				}




				/* Generic fn callback */
						function fnCallback(){
								/* Inline ajax update */
									$('input.ajaxinput').change( function() {

											if($(this).attr('checked') == true){
													$.get('<?php echo dol_buildpath($this->destination,2); ?>?action=delcheckbox', { 'value': $(this).val()});
													$(this).parent().parent().addClass('modified' );
											}
											else{
													$.get('<?php echo dol_buildpath($this->destination,2); ?>?action=addcheckbox', { 'value': $(this).val()});
													$(this).parent().parent().removeClass('modified' );
											}
									} );


								/* Force row change color for indicate row modified */
								$('#dataTable tr').each( function() {
										var nTds = $('td:first-child input[type=checkbox]', this);
										if ( nTds.attr("checked") === "true")
												$(this).addClass('modified' );
								} );

								$('#reset').click( function() {
										$('#dataTable_filter input[type=text]').val('');
										oTable.fnFilter('');
								});


								calllinkajax();
						}



				$(document).ready(function() {

				/**
					Other
				*/

				$('#cocheTout').click(function() { // clic sur la case cocher/decocher

					var cases = $(".mutlicheck"); //.find(':checkbox'); // on cherche les checkbox qui dépendent de la liste 'cases'
					if(this.checked){ // si 'cocheTout' est coché

						$(".mutlicheck").each(function () {
							$(this).attr('checked', true);
							$.get('<?php echo dol_buildpath($this->destination,2); ?>?action=addcheckbox', { 'value': $(this).val()});
							$(this).parent().parent().addClass('modified' );
						});
	//             cases.attr('checked', true); // on coche les cases
							$('#cocheText').html('Tout decocher'); // mise à jour du texte de cocheText
					}else{ // si on décoche 'cocheTout'
						$(".mutlicheck").each(function () {
								$(this).attr('checked', false);
								$.get('<?php echo dol_buildpath($this->destination,2); ?>?action=delcheckbox', { 'value': $(this).val()});
								$(this).parent().parent().removeClass('modified' );
						});
	//             cases.attr('checked', false);// on coche les cases
							$('#cocheText').html('Cocher tout');// mise à jour du texte de cocheText
					}

			});

					var oTable=InitoTable();

				} );
		</script>

		<?php


	}

}



?>
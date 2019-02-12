<?php

/* Copyright (C) 2018      Alexis LAURIER             <alexis@alexislaurier.fr>
 * Copyright (C) 2018      Synergies-Tech             <infra@synergies-france.fr>
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
 */


	require 'config.php';
	dol_include_once('/core/lib/functions.lib.php');
	dol_include_once('/searcheverywhere/lib/searcheverywhere.lib.php');

	$langs->load('searcheverywhere@searcheverywhere');

	$keyword = GETPOST('keyword');

	llxHeader('', $langs->trans('Searcheverywhere'), '', '', 0, 0, array('/searcheverywhere/js/jquery.tile.min.js')  );
	$head = searcheverywhere_prepare_head($keyword);
	dol_fiche_head($head, 'search', $langs->trans('Searcheverywhere'), 0, 'searcheverywhere@searcheverywhere');


	?>
	<style type="text/css">
		#results {
			position:relative;
			margin-top:15px;
		}

		#results span.loading {
			padding : 20px;
			background-color: #f64f1c;
			border-radius: 10px;
			top:50px;
			left:50px;
			position:relative;
		}

		#results div.result {

			width:300px;
			float:left;

			border-color: #bbb #aaa #aaa;
		    border-style: solid;
		    border-width: 1px;
		    box-shadow: 3px 3px 4px #ddd;
		    margin: 0 10px 14px 0;



		}
		.highlight {
			font-weight: bold;
		}
	</style>

	<input type="text" name="keyword" id="keyword" value="" />
	<input type="button" name="btseach" id="btseach" value="Rechercher" />

	<div id="results">

	</div>
	<div style="clear:both"></div>

	<script type="text/javascript">
		var url = "<?php echo dol_buildpath('/searcheverywhere/search.php?keyword=', 1) ?>";
		var TSearch = ['product','company','contact','propal','order','invoice','projet','task','event','expedition','supplier_order'];

		$(document).ready(function() {

			$("#btseach").click(function() {

				var keyword = $("#keyword").val();
				$('#results').html("<span class=\"loading\">Chargement...</span>");
				$('a#search').attr('href', url+keyword);

				for(x in TSearch) {

					$.ajax({
						url : "./script/interface.php"
						,data :{
							get:'search'
							,type:TSearch[x]
							,keyword : keyword
						}

					}).done(function(data) {

						$('#results span.loading').remove();

						$div = $('<div class="result" />');
						$div.append(data);

						$('#results').append($div);

						$('#results div.result').tile();
						jQuery("#results div.result .classfortooltip").tipTip({maxWidth: "600px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
					})


				}


			});

			<?php
				if($keyword!='') {
					?>
					$("#keyword").val("<?php echo $keyword; ?>");
					$("#btseach").click();
					<?php
				}

			?>

		});
	</script>


	<?php


	llxFooter();

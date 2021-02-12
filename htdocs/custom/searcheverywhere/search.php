<?php
	require 'config.php';
	dol_include_once('/core/lib/functions.lib.php');
	dol_include_once('/searcheverywhere/lib/searcheverywhere.lib.php');
	
	$langs->load('searcheverywhere@searcheverywhere');
	
	$keyword = GETPOST('keyword');
	if (empty($keyword)) $keyword=GETPOST('sall');
	
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
	<input type="button" name="btsearch" id="btsearch" value="<?php print $langs->trans('Search'); ?>" />
	<div id="results"></div>
	<div style="clear:both"></div>
	<script type="text/javascript">
		var url = "<?php echo dol_buildpath('/searcheverywhere/search.php?keyword=', 1) ?>";
		var TSearch = [
			'product',
			'company',
			'contact',
			<?php if ($conf->propal->enabled) echo "'propal',"; ?>
			<?php if ($conf->commande->enabled) echo "'order',"; ?>
			<?php if ($conf->facture->enabled) echo "'invoice',"; ?>
			<?php if ($conf->projet->enabled) echo "'projet','task',"; ?>
			<?php if ($conf->agenda->enabled) echo "'event',"; ?>
			<?php if ($conf->expedition->enabled) echo "'expedition',"; ?>
			<?php if ($conf->fournisseur->enabled) echo "'supplier_order',"; ?>
			<?php if ($conf->of->enabled) echo "'of',"; ?>
			<?php if ($conf->nomenclature->enabled) echo "'nomenclature',"; ?>
			<?php if ($conf->workstation->enabled) echo "'workstation',"; ?>
			<?php if ($conf->configurateur->enabled) echo "'configurateur',"; ?>
		];
	
		$(document).ready(function() {
			$("#btsearch").click(function() {
				var keyword = $("#keyword").val();

				$('#results').html("<span class=\"loading\"><?php echo $langs->trans('Loading'); ?>...</span>");
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
					$("#btsearch").click();
					<?php
				}
			?>
		});
	</script>
	<?php
	llxFooter();

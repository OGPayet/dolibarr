<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

dol_include_once('/core/lib/functions.lib.php');

// Define mime type
top_httphead('text/javascript');
global $langs;
$var = false;

?>

$(document).ready(function(){
	textarea_autosize($('#notes_txt'));
	textarea_autosize($('#notes_txt'));
})
function textarea_autosize(x){
    $(x).each(function(textarea) {
        $(this).css('resize', 'none');
    }).on('input', function () {
        $(this).css('height', 'auto');
        $(this).height($(this)[0].scrollHeight);
    });
}

function getConducts(that) {
	var type=$(that).val();
    var id_conduct=$('#id_conduct').val();
	$.ajax({
		data:{'type':type,'id_conduct':id_conduct},
        url:"<?php echo dol_escape_js(dol_buildpath('/parcautomobile/conducteur.php',2)); ?>",
        type:'POST',
        success:function(data){
        	$('.Conducttype').html(data);
        }
	})
}
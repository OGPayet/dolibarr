$(document).on("select2-selecting", '#productid, #serviceid', function (e) {

    // Check si le produit est déjà dans l'ouvrage
    if ($( "#sortable input[type=hidden][name='products[]'][value="+e.choice.id+"]" ).length == 0) {
        var newelt = $('<li class="ui-state-default"></li>');
        $(newelt).append('<span class="icon-drag-drop"></span>');
        $(newelt).append('<input type="hidden" name="products[]" value="'+e.choice.id+'" />');
        $(newelt).append(e.choice.text);
        $(newelt).append('<input type="number" name="quantity[]" value="1" length="4" />');
        $(newelt).append('<a href="#" class="delete">X</a>');
        $( "#sortable" ).append($(newelt));
    }

    setTimeout(function(){ $('#productid, #serviceid').val(0).trigger('change'); }, 20);
});

$(document).on("click", '#sortable a.delete', function (e) {
    $(this).parent('li').remove();
});

$( function() {
$( "#sortable" ).sortable();
});
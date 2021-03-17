$(document).on("select2:selecting", '#productid, #serviceid',  function (e) {
    var data = e.params.args.data;
    
    var prodid = data.id;
    var prodtext = data.text;
    
    addProductOuvrage(prodid, prodtext);
    setTimeout(function () {
 $('#productid, #serviceid').val(0).trigger('change'); }, 20);
});
$(document).on("autocompleteselect", '#search_productid, #search_serviceid', function (event, ui) {
    var prodid = ui.item.id;
    var prodtext = ui.item.label;
    
    addProductOuvrage(prodid, prodtext);
    
    setTimeout(function () {
 $('#search_productid, #search_serviceid').val('').trigger('change'); }, 20);
});

function addProductOuvrage(prodid, prodtext)
{
    // Check si le produit est déjà dans l'ouvrage
    if ($("#sortable input[type=hidden][name='products[]'][value="+prodid+"]").length == 0) {
        var newelt = $('<li class="ui-state-default ui-sortable-handle" style="cursor:grab;"></li>');
        $(newelt).append('<span class="icon-drag-drop"></span>');
        $(newelt).append('<input type="hidden" name="products[]" value="'+prodid+'" />');
        $(newelt).append(prodtext);
        $(newelt).append('<input type="number" step="0.01" min="0" name="quantity[]" value="1.000" length="4" style="width:5vw;"/>');
        $(newelt).append('<a href="#" class="delete">X</a>');
        $("#sortable").append($(newelt));
    }
}

$(document).on("click", '#sortable a.delete', function (e) {
    $(this).parent('li').remove();
    return false;
});

$(function () {
    $("#sortable").sortable();
});
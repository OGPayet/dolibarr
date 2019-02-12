//
//
// Copyright (C) 2018      Alexis LAURIER              <alexis@alexislaurier.fr>
// Copyright (C) 2018      Synergies-Tech             <infra@synergies-france.fr>
//

$(function(){
	var tabAddress = $('#address').val().split("\n");
	var address = tabAddress.splice(0, 1);
	var address2 = tabAddress.join("\n");

	var inputAddress = $('<input type="text" size="60" placeholder="Adresse postale (ex: 3 rue dupont 69001 lyon)" value="'+address[0]+'"><br>').insertBefore('#address');
	var inputAddress2 = $('<textarea cols="80" rows="3" wrap="soft" placeholder="Adresse complémentaire">'+address2+'</textarea>').insertBefore('#address');
	$('#address').hide();

	inputAddress.autocomplete({
		source: function(request, response){
			$.ajax({
				url: "//api-adresse.data.gouv.fr/search/",
				dataType: "json",
				data: {
					q: request.term,
					limit: 20
				},
				success: function(data){
					var res = data.features;
					var list = [];
					for(var i=0;i<res.length;i++){
						list.push({
							label:	res[i].properties.label,
							name:		res[i].properties.name,
							zip:		res[i].properties.postcode,
							town:		res[i].properties.city
						});
					}
					response(list);
				}
			});
		},
		select: function(event, ui){
			inputAddress.val(ui.item.name);
			$('#zipcode').val(ui.item.zip);
			$('#town').val(ui.item.town);

			return false;
		},
		 minLength: 5
	});

	inputAddress2.keyup(function(){

	});

	$('form[name="formsoc"]').submit(function(){

		$('#address').val(inputAddress.val() + "\n" + inputAddress2.val());
		$('#address').show();

		inputAddress.remove();
		inputAddress2.remove();
	});
});

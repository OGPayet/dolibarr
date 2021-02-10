<?php
/* ouvrage
 * Copyright (C) 2017       Inovea-conseil.com     <info@inovea-conseil.com>
 */

/**
 * \file    class/actions_ouvrage.class.php
 * \ingroup ouvrage
 * \brief   Actionsouvrage
 *
 * ouvrage actions
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

/**
 * Class ActionsSmsCommande
 */
class ActionsOuvrage
{
    /**
     * @var DoliDB Database handler
     */
    private $db;

    /**
     * @var dash
     */
    private $dash;

    /**
     * @var Special Code
     */
    private $special_code = 501028;

    /**
     *  Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf;
        $this->dash = 0;//$conf->global->MAIN_PDF_DASH_BETWEEN_LINES;
        $this->db = $db;
    }

    function formAddObjectLine($parameters=false) {
        global $conf, $langs, $user;
        global $object, $bcnd, $var;

        $usemargins=0;
        $colspanbutton=4;

        if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
        {
            $colspantitle=10;
            $colspanlabel=7;
            $colspandesc=7;
        }
        else
        {
            $colspantitle=9;
            $colspanlabel=5;
            $colspandesc=5;
        }

        if (! empty($conf->margin->enabled) && ! empty($GLOBALS['object']->element) && in_array($GLOBALS['object']->element,array('facture','propal', 'askpricesupplier','commande')))
        {
            $usemargins=1;
            $colspantitle+=3;
            $colspanlabel++;
            $colspandesc+=1;
        }
        if (! empty($usemargins) && ! empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous)
        {
            $colspantitle+=2;
            $colspanlabel++;
            $colspandesc+=1;
        }
        if (! empty($usemargins) && ! empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous)
        {
            $colspantitle+=2;
            $colspanlabel++;
            $colspandesc+=1;
        }

        dol_include_once('/ouvrage/class/html.form_ouvrage.class.php');
        $form = new FormOuvrage($this->db);

        echo '<tr class="liste_titre nodrag nodrop"><td colspan="'.$colspantitle.'">'.$langs->trans($conf->global->OUVRAGE_TYPE.'ADDOUVRAGE').'</td></tr>';
        echo '<tr class="nodrag nodrop"><td colspan="'.$colspantitle.'">'.$form->select_ouvrage();

        echo '<div id="modal-ouvrage" title="'.$langs->trans($conf->global->OUVRAGE_TYPE."OUVRAGE").'" style="display:none;" >
</div>';

        echo '<script>';
        $btnSend = $langs->trans("Add");
        $url = dol_buildpath('/ouvrage/ajax/ajaxOuvrageForm.php', 1);
        echo <<<HEREDOC
        $('#ouvrageid').on('change', function (e) {
			console.log(e);
        var ouvrageid = this.value;
        $.ajax({
            url: "$url",
            data: {id: ouvrageid},
            dataType: 'html'
        }).done(function( data ) {
            $( "#modal-ouvrage" ).html(data);
                $( "#modal-ouvrage" ).dialog({
               resizable: true,
               height: "auto",
               width: 800,
               modal: true,
                   buttons:
               [{
                 text: "$btnSend",
                 click: function () {
                    if ($('#tablelinesouvrage input[name="ouvrage[qty]"]').val() > 0) {
                        calculTotalOuvrage();
                        $('#tablelinesouvrage').find('input, select').each(function(){
                            $('#addproduct').prepend('<input type="hidden" name="'+$(this).attr('name')+'" value="'+$(this).val()+'" />');
                        });
                        $('#addproduct').prepend('<input type="hidden" name="ouvrage[price]" value="'+$('#tablelinesouvrage').find('td.ouvrage-price-unit').text()+'" />');
                        $('#prod_entry_mode_predef').prop('checked', 'checked');
                        $('#addproduct').submit();
                    }
                 },

             }],
            });
HEREDOC;

        if (isset($conf->fournisseur) && $conf->fournisseur->enabled) {
            $urlAjaxFourn = dol_buildpath('/fourn/ajax/getSupplierPrices.php?bestpricefirst=1', 1);
            $langs->load('stocks');
            $langInputPrice = $langs->trans("InputPrice");

            // Margin Configuration
            $marginConfig = '';
            if (isset($conf->global->MARGIN_TYPE)) {
                if ($conf->global->MARGIN_TYPE == '1')   $marginConfig = 'bestsupplierprice';
                if ($conf->global->MARGIN_TYPE == 'pmp') $marginConfig = 'pmp';
                if ($conf->global->MARGIN_TYPE == 'costprice') $marginConfig = 'costprice';
            }

            echo <<<HEREDOC
            $('#tablelinesouvrage input[name$="[marge]"]').each(function(){
                var productId = $(this).data('product');
                var inputMarge = this;

                var idSelected = 'fournprice_predef_'+productId;

                if (data && data.length > 0)
		{
                    var options = '';
                    var defaultkey = '';
                    var defaultprice = '';
                    var bestpricefound = 0;

                    var bestpriceid = 0; var bestpricevalue = 0;
                    var pmppriceid = 0; var pmppricevalue = 0;
                    var costpriceid = 0; var costpricevalue = 0;

                    /* setup of margin calculation */
                    var defaultbuyprice = '$marginConfig';
                    $.post('$urlAjaxFourn', { 'idprod': productId }, function(data) {
                        if (data && data.length > 0) {
                            var i = 0;
			$(data).each(function() {
				if (this.id != 'pmpprice' && this.id != 'costprice')
				{
					i++;
                            this.price = parseFloat(this.price); // to fix when this.price >0
					// If margin is calculated on best supplier price, we set it by defaut (but only if value is not 0)
					if (bestpricefound == 0 && this.price > 0) { defaultkey = this.id; defaultprice = this.price; bestpriceid = this.id; bestpricevalue = this.price; bestpricefound=1; }	// bestpricefound is used to take the first price > 0
				}
				if (this.id == 'pmpprice')
				{
					// If margin is calculated on PMP, we set it by defaut (but only if value is not 0)
					if ('pmp' == defaultbuyprice || 'costprice' == defaultbuyprice)
					{
						if (this.price > 0) {
							defaultkey = this.id; defaultprice = this.price; pmppriceid = this.id; pmppricevalue = this.price;
						}
					}
				}
				if (this.id == 'costprice')
				{
					// If margin is calculated on Cost price, we set it by defaut (but only if value is not 0)
					if ('costprice' == defaultbuyprice)
					{
						if (this.price > 0) { defaultkey = this.id; defaultprice = this.price; costpriceid = this.id; costpricevalue = this.price; }
						else if (pmppricevalue > 0) { defaultkey = pmppriceid; defaultprice = pmppricevalue; }
					}
				}
				options += '<option value="'+this.id+'" price="'+this.price+'">'+this.label+'</option>';
			});
			options += '<option value="inputprice" price="'+defaultprice+'">$langInputPrice</option>';

                        var selectedElt = $("<select></select>").attr('id', idSelected).html(options);

			$(inputMarge).before(selectedElt);
                        $(selectedElt).after('<br/>')
			if (defaultkey != '')
				{
				$(selectedElt).val(defaultkey);
			}

			/* At loading, no product are yet selected, so we hide field of buying_price */
			$(inputMarge).hide();

			/* Define default price at loading */
			var defaultprice = $(selectedElt).find('option:selected').attr("price");
			    $(inputMarge).val(defaultprice);

			$(selectedElt).change(function() {
				/* Hide field buying_price according to choice into list (if 'inputprice' or not) */
					var linevalue=$(this).find('option:selected').val();
				var pricevalue = $(this).find('option:selected').attr("price");
				if (linevalue != 'inputprice' && linevalue != 'pmpprice') {
					$(inputMarge).val(pricevalue).hide();	/* We set value then hide field */
				}
				if (linevalue == 'inputprice') {
					$(inputMarge).show();
				}
				if (linevalue == 'pmpprice') {
					$(inputMarge).val(pricevalue);
					$(inputMarge).hide();
				}

                    $(inputMarge).trigger('change');
				});
                        }
                    },
                    'json');
                    }});
HEREDOC;
        }

        echo <<<HEREDOC
        setTimeout(calculTotalOuvrage, 500);
        //setInterval(function(){calculTotalOuvrage();}, 100);
        //setInterval(calculTotalOuvrage, 100);
        });

});

   $(document).on('change', '#tva_tx_ouvrage', function(e){
       $('#tablelinesouvrage td.product-tva').html($(this).val());
   });
   $(document).on('keyup change', '#tablelinesouvrage input[name="ouvrage[qty]"]', function(e){
        var qtyOuvrage = $(this).val();
        $('#tablelinesouvrage td.product-qty input').each(function(){
            $(this).val(qtyOuvrage*$(this).data('default'));
            $(this).trigger('change');
        });
        e.stopPropagation();
   });
    var reducOuvrage = 0;
   $(document).on('keyup change', '#tablelinesouvrage input[name="ouvrage[reduc]"]', function(e){
        var thisval = parseFloat($(this).val());
        thisval = isNaN(thisval) ? 0 : thisval;
        var tmpReducOuvrage = thisval - reducOuvrage;
        reducOuvrage =  thisval;
        $('#tablelinesouvrage td.product-reduc input').each(function(){
            //var thisval = parseFloat($(this).val());
            //thisval = isNaN(thisval) ? 0 : thisval;
            //var reducProduct = thisval+tmpReducOuvrage
            $(this).val(reducOuvrage);
            $(this).trigger('change');
        }).promise().done( function(){
            calculTotalOuvrage();
        } );
        e.stopPropagation();
   });
   $(document).on('keyup change', '#tablelinesouvrage td.product-qty input, #tablelinesouvrage td.product-price input, #tablelinesouvrage td.product-reduc input, #tablelinesouvrage td.product-marge input', function(e){
       var qtyProduct = $(this).parents('tr').find('td.product-qty input').val();

       // On change la quantité par défaut
       if ($('#tablelinesouvrage td.product-qty input').is(':focus') && $('#tablelinesouvrage input[name="ouvrage[qty]"]').val() == 1) {
                $(this).data('default', $(this).val());
       }

       var priceProduct = $(this).parents('tr').find('td.product-price input').val();
       var reducProduct = parseFloat($(this).parents('tr').find('td.product-reduc input').val());

       reducProduct = isNaN(reducProduct) ? 0 : reducProduct;

       $(this).parents('tr').find('td.product-reduc input').val(reducProduct);

       var totalProduct = priceProduct*qtyProduct*((100-reducProduct)/100);

       $(this).parents('tr').find('td.product-price-total').text(Math.round(totalProduct * 100)/100);
        e.stopPropagation();
   });74

   $(document).on('keyup change', '#tablelinesouvrage td.product-reduc input', function(e){
       if ($(this).val() != $('#tablelinesouvrage input[name="ouvrage[reduc]"]').val()) {
            calculTotalOuvrage();
            // On calcul la réduc de l'ouvrage
            var reducOuvrage = parseFloat($('#tablelinesouvrage td.ouvrage-price-total').text()) / $('#tablelinesouvrage input[name="ouvrage[qty]"]').val();
            reducOuvrage = reducOuvrage * 100 / parseFloat($('#tablelinesouvrage td.ouvrage-price-unit').text());
            reducOuvrage = 100 - reducOuvrage;
            $('#tablelinesouvrage input[name="ouvrage[reduc]"]').val(Math.round(reducOuvrage * 10000)/10000);
       }
   });


   $(document).on('keyup change', '#tablelinesouvrage td.product-qty input, #tablelinesouvrage td.product-price input', function(e){
       calculTotalOuvrage();
       var puOuvrage = 0;

       $('#tablelinesouvrage tr').each(function(){
            if ($(this).find('td.product-qty').length > 0 && $(this).find('td.product-qty input').val() > 0) {
                puOuvrage += $(this).find('td.product-qty input').val() * $(this).find('td.product-price input').val();
            }
       }).promise().done( function(){
            puOuvrage = puOuvrage / $('#tablelinesouvrage input[name="ouvrage[qty]"]').val();
            puOuvrage = isNaN(puOuvrage) ? 0 : puOuvrage;
            $('#tablelinesouvrage td.ouvrage-price-unit').text(Math.round(puOuvrage * 100)/100);
       });

   });

    $(document).on('focusout', '#tablelinesouvrage', function(e){
       calculTotalOuvrage();
       console.log('focusout');
   });


   function calculTotalOuvrage() {
       var totalOuvrage = 0;
        $('#tablelinesouvrage td.product-price-total').each(function(){
           totalOuvrage += parseFloat($(this).text().replace(',','.').replace(' ',''));
        }).promise().done( function(){


			//////////
			//////////
			/// To DO : Récupérer la variable globale de paramétrage
			////////
			////////



            $('#tablelinesouvrage td.ouvrage-price-total').text(totalOuvrage.toFixed(2));
        } );


        // Prix de revient ouvrage
        var prixRevientOuvrage = 0;
        if ($('input[name="ouvrage[marge]"]').length > 0) {
            $('#tablelinesouvrage td.product-marge input').each(function(){
                var qtyProduct = $(this).parents('tr').find('td.product-qty input').val();
                var prixRevientProduct = 0;

                if ($(this).val() == 0 && $(this).parents('td').find('select option:selected').attr('price') != '' && $(this).parents('td').find('select option:selected').attr('price') != 'undefined') {
                    prixRevientOuvrage += parseFloat($(this).parents('td').find('select option:selected').attr('price'))*qtyProduct;
                } else {
                    if ($(this).val().replace(',','.').length > 0) {
                        prixRevientOuvrage += parseFloat($(this).val().replace(',','.').replace(' ',''))*qtyProduct;
                    } else {
                        prixRevientOuvrage += 0;
                    }
                }
            });

            prixRevientOuvrage = isNaN(prixRevientOuvrage) ? 0 : prixRevientOuvrage;
            $('input[name="ouvrage[marge]"]').val(Math.round(prixRevientOuvrage * 100)/100);
        }
   }

HEREDOC;

        echo '</script>';

        echo '</td></tr>';
    }

    /**
     * 	Return action of hook
     * 	@param		object			Linked object
     */
    function doActions($parameters=false, &$object, &$action='')
    {
        global $conf,$user,$langs;

        $langs->load("ouvrage@ouvrage");

        dol_include_once('/ouvrage/class/ouvrage.class.php');
        dol_include_once('/product/class/product.class.php');
        if (GETPOST('ouvrage') && $action == 'addline') {
            $error=0;

            $ouvrage_post = GETPOST('ouvrage');

            ksort($ouvrage_post['product']);
            $products = $ouvrage_post['product'];

            $tot = 0;
            foreach ($products as $product) {
                $tot += $product['qty'];
            }

            if ($tot > 0) {
                $ouvrage = new Ouvrage($this->db);
                $ouvrage->fetch($ouvrage_post['id']);



                // Clean parameters
                $label			= trim($ouvrage->label);
                $description	= trim($ouvrage->description) != '' ? trim($ouvrage->description) : $label ;
                $product_type	= 9;
                $special_code	= $this->special_code;
                $ouvrage_qty = $ouvrage_post['qty'];
                $ouvrage_price = $ouvrage_post['price'] /*/ $ouvrage_post['qty']*/;
                //$ouvrage_price = 100 * $ouvrage_price / 100 - $ouvrage_post['reduc'];
                $ouvrage_remise	= $ouvrage_post['reduc'];
                $ouvrage_buy_price	= isset($ouvrage_post['marge']) ? $ouvrage_post['marge'] : 0;
                $tva = GETPOST('tva_tx_ouvrage', 'alpha');
                $rangtouse = $object->line_max()+1;

                // TODO uniformiser
                if ($object->element == 'propal') $fields = array($description,$ouvrage_price,$ouvrage_qty,$tva,0,0,0,$ouvrage_remise,"HT",0,0,$product_type,$rangtouse,$special_code,0,0,$ouvrage_buy_price,$label);
                if ($object->element == 'commande') $fields = array($description,$ouvrage_price,$ouvrage_qty,$tva,0,0,0,$ouvrage_remise,0,0,'HT',0,null,null,$product_type,$rangtouse,$special_code,0,null,$ouvrage_buy_price,$label);
                if ($object->element == 'facture') $fields = array($description,$ouvrage_price,$ouvrage_qty,$tva,0,0,0,$ouvrage_remise,null,null,0,0,0,'HT',0,$product_type,$rangtouse,$special_code,'',0,0,null,$ouvrage_buy_price,$label);

                $result_ouvrage = $object->addline($fields[0],$fields[1],$fields[2],$fields[3],$fields[4],$fields[5],$fields[6],$fields[7],$fields[8],$fields[9],$fields[10],$fields[11],$fields[12],$fields[13],$fields[14],$fields[15],$fields[16],$fields[17],$fields[18],$fields[19],$fields[20],$fields[21],$fields[22],$fields[23]);


                $rangproduit = $rangtouse+1;

                if ($result < 0) {

                } else {
                    foreach ($products as $product_id=>$p) {
                        if ($p['qty'] > 0) {
                            $product = new Product($this->db);
                            $product->fetch($product_id);
                            $label = trim($product->label);
                            $description	= trim($product->description);
                            $product_type	= $product->type;
                            $product_price	= $p['price_ht'];
                            $product_remise	= $p['reduc'];
                            $product_buy_price	= isset($p['marge']) ? $p['marge'] : 0;
                            $qty = $p['qty'];
                            $special_code = 0;
                            //$result_ouvrage = 0;



                            // TODO uniformiser
                            if ($object->element == 'propal') $fields = array($description,$product_price,$qty,$tva,0,0,$product_id,$product_remise,"HT",0,0,$product_type,$rangproduit++,$special_code,$result_ouvrage,0,$product_buy_price,$label);
                            if ($object->element == 'commande') $fields = array($description,$product_price,$qty,$tva,0,0,$product_id,$product_remise,0,0,'HT',0,null,null,$product_type,$rangproduit++,$special_code,$result_ouvrage,null,$product_buy_price,$label);
                            if ($object->element == 'facture') $fields = array($description,$product_price,$qty,$tva,0,0,$product_id,$product_remise,null,null,0,0, '', 'HT',0,$product_type,$rangproduit++,$special_code,'',0,$result_ouvrage,0,$product_buy_price,$label);

                            //
                            $result = $object->addline($fields[0],$fields[1],$fields[2],$fields[3],$fields[4],$fields[5],$fields[6],$fields[7],$fields[8],$fields[9],$fields[10],$fields[11],$fields[12],$fields[13],$fields[14],$fields[15],$fields[16],$fields[17],$fields[18],$fields[19],$fields[20],$fields[21],$fields[22],$fields[23]);
                        }
                    }
                    $object->update_price(1);


                    if ($object->element == 'facture') {
                        Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id);
                    } else {
                        Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
                    }
                }
            }


        }

        /**
         * Suppression Line
         */




        if ($action == 'confirm_deleteline' && GETPOST('confirm') == 'yes') {
            $error=0;
            $lineid = GETPOST('lineid', 'int');
            //$this->db->begin();

            //var_dump($lineid);exit;

            // on recherche la ligne à supprimer
            foreach($object->lines as $line) {
                if ($line->rowid == $lineid) {
                    $lineToDelete = $line;
                }
            }
            if ($lineToDelete->special_code == $this->special_code) {
                // Call trigger
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('OUVRAGE_DELETE',$lineToDelete,$user,$langs,$conf);
                if ($result < 0) $error++;
                // End call triggers
            }
        }



        /**
         * Mise à jour
         */
        if (($action == 'updateligne' || $action == 'updateline') && GETPOST('ouvrage_update') == 1) {

            $lineid = GETPOST('lineid', 'int');

            // on recherche la ligne modifiée
            foreach($object->lines as $line) {
                if ($line->rowid == $lineid) {
                    $lineUpdate = $line;
                }
            }

            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('OUVRAGE_UPDATE',$lineUpdate,$user,$langs,$conf);
            if ($result < 0) $error++;
            unset($_POST['action']);
            return 1;
            if ($object->element == 'facture') {
                Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id);
            }
            Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
        }

        if ($action == 'builddoc') {
            $_SESSION['OUVRAGE_HIDE_PRODUCT_DETAIL'] = GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL');
            $_SESSION['OUVRAGE_HIDE_PRODUCT_DESCRIPTION'] = GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION');
            $_SESSION['OUVRAGE_HIDE_MONTANT'] = GETPOST('OUVRAGE_HIDE_MONTANT');
        }

    }

    function printObjectLine($parameters=false, &$object, &$action='viewline') {
        global $conf,$langs,$user,$hookmanager;
        global $form,$bc,$bcnd;

        $element = $object->element;
        if ($element == 'propal') $element = 'propale';

        $lineid = GETPOST('lineid', 'int');

        if ($action == 'editline') {
            //On recherche la ligne à modifier
            foreach($object->lines as $line) {
                if ($line->rowid == $lineid) {
                    $lineToUpdate = $line;
                }
            }

            if ($lineToUpdate->special_code == $this->special_code) {
                echo '<input type="hidden" name="special_code" value="'.$this->special_code.'" />';
                echo '<input type="hidden" name="label" value="'.$lineToUpdate->label.'" />';
                echo '<input type="hidden" name="ouvrage_update" value="1" />';

                $textHelp = $form->textwithpicto('',$langs->trans($conf->global->OUVRAGE_TYPE."OUVRAGE_IMPOSSIBLEUPDATE"));

                echo '<script>';
                echo "setTimeout(function(){ $('#price_ht, #buying_price').attr('disabled', 'disabled'); $('#price_ht').attr('size', 4); $('#price_ht').after('".$textHelp."')}, 1);";
                echo '</script>';
            }

            if(!is_null ($lineToUpdate->fk_parent_line) && $lineToUpdate->fk_parent_line > 0) {
                // On vérifie si la ligne supprimée appartient à un ouvrage
                foreach($object->lines as $line) {
                    if ($line->rowid == $lineToUpdate->fk_parent_line) {
                        $parentLineToUpdate = $line;
                    }
                }
                if ($parentLineToUpdate->special_code == $this->special_code) {
                    echo '<input type="hidden" name="fk_parent_line" value="'.$lineToUpdate->fk_parent_line.'" />';
                    echo '<input type="hidden" name="ouvrage_update" value="1" />';
                }
            }
        }
    }

    /**
     * 	Return HTML form for builddoc bloc
     */
    function formBuilddocOptions($parameters=false)
    {
        global $conf, $langs;
        global $bc, $var;

        $langs->load('ouvrage@ouvrage');

        $out='';

        $checkedHideDetails = (!isset($_SESSION['OUVRAGE_HIDE_PRODUCT_DETAIL']) && $conf->global->OUVRAGE_HIDE_PRODUCT_DETAIL == 1) || (isset($_SESSION['OUVRAGE_HIDE_PRODUCT_DETAIL']) && $_SESSION['OUVRAGE_HIDE_PRODUCT_DETAIL'] == 1) ? 'checked="checked"' : '';
        $checkedHideDesc = (!isset($_SESSION['OUVRAGE_HIDE_PRODUCT_DESCRIPTION']) && $conf->global->OUVRAGE_HIDE_PRODUCT_DESCRIPTION == 1) || (isset($_SESSION['OUVRAGE_HIDE_PRODUCT_DESCRIPTION']) && $_SESSION['OUVRAGE_HIDE_PRODUCT_DESCRIPTION'] == 1) ? 'checked="checked"' : '';
        $checkedHideAmount = (!isset($_SESSION['OUVRAGE_HIDE_MONTANT']) && $conf->global->OUVRAGE_HIDE_MONTANT == 1) || (isset($_SESSION['OUVRAGE_HIDE_MONTANT']) && $_SESSION['OUVRAGE_HIDE_MONTANT'] == 1) ? 'checked="checked"' : '';

        $out.= '<tr '.$bc[$var].'>';
        $out.= '<td colspan="4"><input type="checkbox" name="OUVRAGE_HIDE_PRODUCT_DETAIL" value="1"' . $checkedHideDetails . ' /> '.$langs->trans('OUVRAGE_HIDE_PRODUCT_DETAIL').'</td>';
        $out.= '</tr>';
        $out.= '<tr '.$bc[$var].'>';
        $out.= '<td colspan="4"><input type="checkbox" name="OUVRAGE_HIDE_PRODUCT_DESCRIPTION" value="1"' . $checkedHideDesc . ' /> '.$langs->trans('OUVRAGE_HIDE_PRODUCT_DESCRIPTION').'</td>';
        $out.= '</tr>';
        $out.= '<tr '.$bc[$var].'>';
        $out.= '<td colspan="4"><input type="checkbox" name="OUVRAGE_HIDE_MONTANT" value="1"' . $checkedHideAmount . ' /> '.$langs->trans('OUVRAGE_HIDE_MONTANT').'</td>';
        $out.= '</tr>';

        $out .= <<<HEREDOC
                        <script>
                        $('input[type=checkbox][name=OUVRAGE_HIDE_PRODUCT_DETAIL]').on('change', function(){
                            if ($(this).prop('checked')) {
                                $('input[type=checkbox][name=OUVRAGE_HIDE_PRODUCT_DESCRIPTION]').prop('checked', true);
                                $('input[type=checkbox][name=OUVRAGE_HIDE_MONTANT]').prop('checked', false);
                            }
                        });
                        $('input[type=checkbox][name=OUVRAGE_HIDE_PRODUCT_DESCRIPTION]').on('change', function(){
                            if ($(this).prop('checked')) {
                                $('input[type=checkbox][name=OUVRAGE_HIDE_MONTANT]').prop('checked', false);
                            }
                        });
                        $('input[type=checkbox][name=OUVRAGE_HIDE_MONTANT]').on('change', function(){
                            if ($(this).prop('checked')) {
                                $('input[type=checkbox][name=OUVRAGE_HIDE_PRODUCT_DETAIL]').prop('checked', false);
                                $('input[type=checkbox][name=OUVRAGE_HIDE_PRODUCT_DESCRIPTION]').prop('checked', false);
                            }
                        });
                        </script>

HEREDOC;

        $this->resprints = $out;
        return 0;
    }


    /**
     *	Return line description translated in outputlangs and encoded in UTF8
     *
     *	@param		array	$parameters		Extra parameters
     *	@param		object	$object			Object
     *	@param    	string	$action			Type of action
     *	@return		void
     */
    function pdf_writelinedesc($parameters=false, &$object, &$action='') {
        global $conf;

        $conf->global->MAIN_PDF_DASH_BETWEEN_LINES = $this->dash;

        if (is_array($parameters) && ! empty($parameters)) {
            foreach($parameters as $key=>$value)
            {
                $$key=$value;
            }
        }

        $return = 0;

        if ($object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
            $backgroundcolor = array('230','230','230');

            $object->lines[$i]->rowid = (!empty($object->lines[$i]->rowid)?$object->lines[$i]->rowid:$object->lines[$i]->id);

            $pdf->SetXY ($posx, $posy-1);
            $pdf->SetFillColor($backgroundcolor[0],$backgroundcolor[1],$backgroundcolor[2]);
            $pdf->MultiCell($pdf->page_largeur-$posx-$pdf->marge_droite, $h+4.5, '', 0, '', 1);

            $posy = $pdf->GetY();
            $pdf->SetFont('', 'B', 8);
            $pdf->writeHTMLCell($w, $h+4.5, $posx, $posy-$h-5, $outputlangs->convToOutputCharset($object->lines[$i]->label), 0, 1);
            $nexy = $pdf->GetY();


            $description = dol_htmlentitiesbr($object->lines[$i]->desc, 1);
            if (! empty($description))
            {
                $pdf->SetFont('', 'I', 7);
                $pdf->writeHTMLCell($w, $h, $posx, $nexy-4.5, $outputlangs->convToOutputCharset($description), 0, 1);
            }

            $pdf->SetFont('', '', 9);

            $conf->global->MAIN_PDF_DASH_BETWEEN_LINES = 0;

            $return++;
        } elseif (isset($object->lines[$i]->fk_parent_line)) {

            $parent_line = $object->lines[$i]->fk_parent_line;

            // Vérifie que la ligne appartient à un ouvrage
            $inOuvrage = false;
            $listProductOuvrage = array();
            foreach ($object->lines as $k => $v) {
                if ($parent_line == $object->lines[$k]->rowid && $object->lines[$k]->product_type == 9 && $object->lines[$k]->special_code == $this->special_code) {
                    $inOuvrage = true;
                }
                if ($parent_line == $object->lines[$k]->fk_parent_line) {
                    $listProductOuvrage[] = $k;
                }
            }


            if ($inOuvrage) {
                if (GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1) {
                    $conf->global->MAIN_PDF_DASH_BETWEEN_LINES = $this->dash;
                    return 1;
                }
                $pdf->SetXY ($posx, $posy-1);
                $nexy = $pdf->GetY();
                //$pdf->SetLineStyle(array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(255, 255, 255)));
                //$pdf->SetFillColor(230, 230, 230);
                $pdf->writeHTMLCell($w, $h, $posx, $nexy, ' - ' . $outputlangs->convToOutputCharset($object->lines[$i]->product_label), 0, 1);
                if (end($listProductOuvrage) != $i) {
                    $conf->global->MAIN_PDF_DASH_BETWEEN_LINES = 0;
                }
                $return++;
            }
        } elseif (GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1) {
            $conf->global->MAIN_PDF_DASH_BETWEEN_LINES = $this->dash;
            return;
        }
        //$pdf->writeHTMLCell($w, $h, $posx, $nexy+1, $outputlangs->convToOutputCharset($object->lines[$i]->label), 0, 0);

        return $return;

    }

    /**
     * 	Return line vat rate
     * 	@param		object				Object
     * 	@param		$i					Current line number
     *  @param    	outputlang			Object lang for output0
     */
    function pdf_getlinevatrate($parameters=false,$object,$action='')
    {
        if (is_array($parameters) && ! empty($parameters))
        {
            foreach($parameters as $key=>$value)
            {
                $$key=$value;
            }
        }

        // si c'est un ouvrage

        if ($object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
            if (GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                $out = '';
            } else {
                $out = vatrate($object->lines[$i]->tva_tx,1,$object->lines[$i]->info_bits);
            }
        } elseif (isset($object->lines[$i]->fk_parent_line)) {

            // Vérifie que la ligne appartient à un ouvrage
            $parent_line = $object->lines[$i]->fk_parent_line;
            $inOuvrage = false;
            $listProductOuvrage = array();
            foreach ($object->lines as $k => $v) {
                if ($parent_line == $object->lines[$k]->rowid && $object->lines[$k]->product_type == 9 && $object->lines[$k]->special_code == $this->special_code) {
                    $inOuvrage = true;
                }
                if ($parent_line == $object->lines[$k]->fk_parent_line) {
                    $listProductOuvrage[] = $k;
                }
            }

            if ($inOuvrage) {
                if ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                    $out ='';
                } elseif ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && $object->lines[$i]->product_type != 9) {
                    $out = '';
                } elseif (GETPOST('OUVRAGE_HIDE_MONTANT') == 1 && $object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
                    $out = '';
                } else {
                    $out = vatrate($object->lines[$i]->tva_tx,1,$object->lines[$i]->info_bits);
                }
            }

        } else {
            return;
        }

        $this->resprints = $out;
        return 1;
    }

    function pdf_getlineupexcltax($parameters=false,$object,$action='')
    {
        if (is_array($parameters) && ! empty($parameters))
        {
            foreach($parameters as $key=>$value)
            {
                $$key=$value;
            }
        }

        $out='';

        // si c'est un ouvrage

        if ($object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
            if (GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                $out = '';
            } else {
                $out = price($object->lines[$i]->subprice);
            }
        } elseif (isset($object->lines[$i]->fk_parent_line)) {

            // Vérifie que la ligne appartient à un ouvrage
            $parent_line = $object->lines[$i]->fk_parent_line;
            $inOuvrage = false;
            $listProductOuvrage = array();
            foreach ($object->lines as $k => $v) {
                if ($parent_line == $object->lines[$k]->rowid && $object->lines[$k]->product_type == 9 && $object->lines[$k]->special_code == $this->special_code) {
                    $inOuvrage = true;
                }
                if ($parent_line == $object->lines[$k]->fk_parent_line) {
                    $listProductOuvrage[] = $k;
                }
            }

            if ($inOuvrage) {
                if ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                    $out ='';
                } elseif ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && $object->lines[$i]->product_type != 9) {
                    $out = '';
                } elseif (GETPOST('OUVRAGE_HIDE_MONTANT') == 1 && $object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
                    $out = '';
                } else {
                    $out = price($object->lines[$i]->subprice);
                }
            }

        } else {
            return;
        }

        $this->resprints = $out;
        return 1;
    }

    function pdf_getlineqty($parameters=false,$object,$action='')
    {
        if (is_array($parameters) && ! empty($parameters))
        {
            foreach($parameters as $key=>$value)
            {
                $$key=$value;
            }
        }

        // si c'est un ouvrage

        if ($object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
            if (GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                $out = '';
            } else {
                $out = $object->lines[$i]->qty;
            }
        } elseif (isset($object->lines[$i]->fk_parent_line)) {

            // Vérifie que la ligne appartient à un ouvrage
            $parent_line = $object->lines[$i]->fk_parent_line;
            $inOuvrage = false;
            $listProductOuvrage = array();
            foreach ($object->lines as $k => $v) {
                if ($parent_line == $object->lines[$k]->rowid && $object->lines[$k]->product_type == 9 && $object->lines[$k]->special_code == $this->special_code) {
                    $inOuvrage = true;
                }
                if ($parent_line == $object->lines[$k]->fk_parent_line) {
                    $listProductOuvrage[] = $k;
                }
            }

            if ($inOuvrage) {
                if ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                    $out ='';
                } elseif ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && $object->lines[$i]->product_type != 9) {
                    $out = '';
                } elseif (GETPOST('OUVRAGE_HIDE_MONTANT') == 1 && $object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
                    $out = '';
                } else {
                    $out = $object->lines[$i]->qty;
                }
            }

        } else {
            return;
        }

        $this->resprints = $out;
        return 1;
    }

    /**
     * 	Return line remise percent
     * 	@param		object				Object
     * 	@param		$i					Current line number
     *  @param    	outputlang			Object lang for output
     */
    function pdf_getlineremisepercent($parameters=false,$object,$action='')
    {
        if (is_array($parameters) && ! empty($parameters))
        {
            foreach($parameters as $key=>$value)
            {
                $$key=$value;
            }
        }

        $out='';

        // si c'est un ouvrage

        if ($object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
            if (GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                $out = '';
            } else {
                $out = dol_print_reduction($object->lines[$i]->remise_percent,$outputlangs);
            }
        } elseif (isset($object->lines[$i]->fk_parent_line)) {

            // Vérifie que la ligne appartient à un ouvrage
            $parent_line = $object->lines[$i]->fk_parent_line;
            $inOuvrage = false;
            $listProductOuvrage = array();
            foreach ($object->lines as $k => $v) {
                if ($parent_line == $object->lines[$k]->rowid && $object->lines[$k]->product_type == 9 && $object->lines[$k]->special_code == $this->special_code) {
                    $inOuvrage = true;
                }
                if ($parent_line == $object->lines[$k]->fk_parent_line) {
                    $listProductOuvrage[] = $k;
                }
            }

            if ($inOuvrage) {
                if ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                    $out ='';
                } elseif ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && $object->lines[$i]->product_type != 9) {
                    $out = '';
                } elseif (GETPOST('OUVRAGE_HIDE_MONTANT') == 1 && $object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
                    $out = '';
                } else {
                    $out = dol_print_reduction($object->lines[$i]->remise_percent,$outputlangs);
                }
            }

        } else {
            return;
        }

        $this->resprints = $out;
        return 1;

    }

    /**
     * 	Return line total including tax
     * 	@param		object				Object
     * 	@param		$i					Current line number
     *  @param    	outputlang			Object lang for output
     */
    function pdf_getlinetotalexcltax($parameters=false,$object,$action='')
    {
        if (is_array($parameters) && ! empty($parameters))
        {
            foreach($parameters as $key=>$value)
            {
                $$key=$value;
            }
        }

        // si c'est un ouvrage

        if ($object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
            if (GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                $out = '';
            } else {
                $out = price($object->lines[$i]->total_ht);
            }
        } elseif (isset($object->lines[$i]->fk_parent_line)) {

            // Vérifie que la ligne appartient à un ouvrage
            $parent_line = $object->lines[$i]->fk_parent_line;
            $inOuvrage = false;
            $listProductOuvrage = array();
            foreach ($object->lines as $k => $v) {
                if ($parent_line == $object->lines[$k]->rowid && $object->lines[$k]->product_type == 9 && $object->lines[$k]->special_code == $this->special_code) {
                    $inOuvrage = true;
                }
                if ($parent_line == $object->lines[$k]->fk_parent_line) {
                    $listProductOuvrage[] = $k;
                }
            }

            if ($inOuvrage) {
                if ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && GETPOST('OUVRAGE_HIDE_MONTANT') == 1) {
                    $out ='';
                } elseif ((GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1 || GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION') == 1) && $object->lines[$i]->product_type != 9) {
                    $out = '';
                } elseif (GETPOST('OUVRAGE_HIDE_MONTANT') == 1 && $object->lines[$i]->product_type == 9 && $object->lines[$i]->special_code == $this->special_code) {
                    $out = '';
                } else {
                    $out = price($object->lines[$i]->total_ht);
                }
            }

        } else {
            return;
        }
        $this->resprints = $out ;
        return 1;
    }
}

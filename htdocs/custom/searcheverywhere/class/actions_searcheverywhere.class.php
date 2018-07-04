<?php
class ActionsSearcheverywhere
{
	/** Overloading the doActions function : replacing the parent's function with the one below
	 *  @param	  parameters  meta datas of the hook (context, etc...)
	 *  @param	  object			 the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 *  @param	  action			 current action (if set). Generally create or edit or null
	 *  @return	  void
	 */
	function printSearchForm($parameters, &$object, &$action, $hookmanager) {
		global $langs,$db,$conf;

		if (in_array('searchform',explode(':',$parameters['context'])) && (DOL_VERSION <= 3.8
		      || !empty($conf->global->SEARCHEVERYWHERE_SEARCH_PREVIEW) ))
		{
			$langs->load('searcheverywhere@searcheverywhere');

			$res = '';

			$res.='<form method="post" action="'.dol_buildpath('/searcheverywhere/search.php',1).'">';
			$res.= '<div class="menu_titre menu_titre_search"><label for="sew_keyword"><a class="vsmenu" href="'.dol_buildpath('/searcheverywhere/search.php',1).'">'.img_object($langs->trans('searcheverywhere'),'searcheverywhere@searcheverywhere').' '.$langs->trans('Searcheverywhere').'</a></label></div>';
			$res.= '	<input type="text" size="10" name="keyword" title="'.$langs->trans('Keyword').'" class="flat" id="sew_keyword" /><input type="submit" value="'.$langs->trans('Go').'" class="button">
				</form>';

			if(!empty($conf->global->SEARCHEVERYWHERE_SEARCH_PREVIEW) ) {

                $res.= '<script type="text/javascript">
                $("#sew_keyword").autocomplete({
					      source: function( request, response ) {
					        $.ajax({
					          url: "'.dol_buildpath('/searcheverywhere/script/interface.php',1).'",
					          dataType: "json",
					          data: {
					            keyword: request.term
					            ,get:"search-all"
					          }
					          ,success: function( data ) {
							  var c = [];
					              $.each(data, function (i, cat) {

							var first = true;
							$.each(cat, function(j, obj) {

								if(first) {
									c.push({value:i, label:i, object:"title"});
									first = false;
								}

								c.push({ value: j, label:"  "+obj.label_clean, url:obj.url, desc:"  "+obj.desc, object:i});

							});


					              });

					              response(c);



					          }
					        });
					      },
					      minLength: 1,
					      select: function( event, ui ) {

                                if(ui.item.url) {
                                    document.location.href = ui.item.url;
                                }

                                return false;

					      },
					      open: function( event, ui ) {
					        $( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
					      },
					      close: function() {
					        $( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
					      }
			     });

				$( "#sew_keyword" ).autocomplete( "instance" )._renderItem = function( ul, item ) {

						  $li = $( "<li style=\"white-space: nowrap;\" />" )
								    .attr( "data-value", item.value )
                                    .append("<span class=\"select2-results\" >"+item.label+"</span>" )
								    .appendTo( ul );

							  if(item.object=="title") $li.css("font-weight","bold");

							  return $li;
			     };

                </script>
              ';


			}

			$this->resprints = $res;
		}

		return 0;
	}

	function addSearchEntry($parameters, &$object, &$action, $hookmanager) {
		global $langs;

		if (in_array('searchform',explode(':',$parameters['context'])) && DOL_VERSION > 3.8 && empty($conf->global->SEARCHEVERYWHERE_SEARCH_PREVIEW)) {
			$search_boxvalue = $parameters['search_boxvalue'];

			$langs->load('searcheverywhere@searcheverywhere');

			$this->results = array(
				'searcheverywhere' => array(
					'img'=>'object_searcheverywhere'
					,'label'=>$langs->trans('Searcheverywhere')
					,'text'=>img_picto('','object_searcheverywhere@searcheverywhere').' '.$langs->trans('Searcheverywhere')
					,'url'=>dol_buildpath('/searcheverywhere/search.php',1).'?keyword='.urlencode($search_boxvalue)
				)
			);
		}

		return 0;
	}
}
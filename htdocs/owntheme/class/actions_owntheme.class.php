<?php

class ActionsOWNTHEME
{
	function printTopRightMenu($parameters, $object, $action)
	{
		global $conf, $langs, $db, $user;
		$langs->load("owntheme@owntheme") ;
		?>
		<div id="ob_loadding">
		    <div class="lds-dual-ring"></div>
		    <style type="text/css">
		        body{overflow:hidden}
		        #ob_loadding{text-align: center;position:fixed;top:0;right:0;margin:0;width:100%;height:100%;z-index:999999;color:#fff;padding:20%}
		        .lds-dual-ring { display: inline-block; width: 95px; height: 95px; } .lds-dual-ring:after { content: " "; display: block; width: 65px; height: 65px; margin: 1px; border-radius: 50%; border: 10px solid #fff; border-color: #fff transparent #fff transparent; animation: lds-dual-ring 0.9s linear infinite; } @keyframes lds-dual-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
			</style>
		    <script>
		        $(window).on('load',function(){
		            $('#ob_loadding').remove();
		            sizes_calc();
		        });
		    </script>
	    </div>
		<?php
		echo '<script type="text/javascript">' ;
		echo "\n" . 'var dol_url_root = "' . DOL_URL_ROOT . '";' ;
		echo "\n" . 'var dol_version  = "' . DOL_VERSION  . '";' ;

		// if ( OWNTHEME_FIXED_MENU ) 
			echo "\n" . '$("body").addClass("fixed-menu");' ;

		if ( DOL_VERSION  >= "3.8.0"  && DOL_VERSION  <= "3.9.0" ) echo "\n" . '$("body").addClass("v3_8");' ;
		if ( DOL_VERSION  >= "3.7.0"  && DOL_VERSION  <= "3.8.0" ) echo "\n" . '$("body").addClass("v3_7");' ;
		if ( DOL_VERSION  >= "3.6.0"  && DOL_VERSION  <= "3.7.0" ) echo "\n" . '$("body").addClass("v3_6");' ;
		if ( DOL_VERSION  > "3.9.0" ) echo "\n" . '$("body").addClass("up_3_9version");' ;
		
		echo "\n" . '$("body").addClass("all_version");' ;

		if ( !empty($conf->global->OWNTHEME_CUSTOM_CSS) )	{
			echo "\n" . 'var custom_css = true;' ;
		} else {
			echo "\n" . 'var custom_css = false;' ;
		}

		if ( !empty($conf->global->OWNTHEME_CUSTOM_JS) )	{
			echo "\n" . 'var custom_js = true;' ;
		} else {
			echo "\n" . 'var custom_js = false;' ;
		}

		if ( !empty($conf->global->MAIN_MODULE_CASHDESK) )	{
			echo "\n" . 'var cashdesk_active = true;' ;
		} else {
			echo "\n" . 'var cashdesk_active = false;' ;
		}

		if ( !empty($conf->global->MAIN_MODULE_WEBMAIL) )	{
			echo "\n" . 'var webmail_active = true;' ;
		} else {
			echo "\n" . 'var webmail_active = false;' ;
		}

		// if ( !empty($conf->global->OWNTHEME_FIXED_MENU) )	{
			echo "\n" . 'var fixed_menu = true;' ;
		// } else {
		// 	echo "\n" . 'var fixed_menu = false;' ;
		// }

		echo "\n" . '</script>';

	}
}

?>
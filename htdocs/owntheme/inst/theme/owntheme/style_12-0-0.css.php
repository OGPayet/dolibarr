

/* Themes for badges */
<?php
// badge color ajustement for color blind
$colorblind_deuteranopes_badgeSuccess   = '#37de5d'; //! text color black
$colorblind_deuteranopes_badgeSuccess_textColor7 = '#000';
$colorblind_deuteranopes_badgeWarning   = '#e4e411';
$colorblind_deuteranopes_badgeDanger    = $badgeDanger; // currently not tested with a color blind people so use default color

/* default color for status : After a quick check, somme status can have oposite function according to objects
*  So this badges status uses default value according to theme eldy status img
*  TODO: use color definition vars above for define badges color status X -> exemple $badgeStatusValidate, $badgeStatusClosed, $badgeStatusActive ....
*/
$badgeStatus0 = '#cbd3d3'; // draft
$badgeStatus1 = '#bc9526'; // validated
$badgeStatus1b = '#bc9526'; // validated
$badgeStatus2 = '#9c9c26'; // approved
$badgeStatus3 = '#bca52b';
$badgeStatus4 = '#25a580'; // Color ok
$badgeStatus4b = '#25a580'; // Color ok
$badgeStatus5 = '#cad2d2';
$badgeStatus6 = '#cad2d2';
$badgeStatus7 = '#25a580';
$badgeStatus8 = '#993013';
$badgeStatus9 = '#e7f0f0';

// status color ajustement for color blind
$colorblind_deuteranopes_badgeStatus4 = $colorblind_deuteranopes_badgeStatus7 = $colorblind_deuteranopes_badgeSuccess; //! text color black
$colorblind_deuteranopes_badgeStatus_textColor4 = $colorblind_deuteranopes_badgeStatus_textColor7 = '#000';
$colorblind_deuteranopes_badgeStatus1 = $colorblind_deuteranopes_badgeWarning;
$colorblind_deuteranopes_badgeStatus_textColor1 = '#000';

?>

<?php include dol_buildpath('/theme/owntheme/badges.inc.php', 0); ?>

/*
 * Component: Info Box
 * -------------------
 */

.info-box-module-external span.info-box-icon-version {
    background: #bbb;
}

.info-box {
    display: block;
    position: relative;
    min-height: 90px;
    /* background: #fff; */
    width: 100%;
    box-shadow: 1px 1px 2px rgba(128, 128, 128, 0.2);
    border-radius: 2px;
    border: 1px solid #e9e9e9;
    margin-bottom: 15px;
}
.info-box.info-box-sm{
    min-height: 80px;
    margin-bottom: 10px;
}
.info-box-more {
    float: right;
    top: 5px;
    position: absolute;
    right: 8px;
}

.info-box small {
    font-size: 14px;
}
.info-box .progress {
    background: rgba(0, 0, 0, 0.2);
    margin: 5px -10px 5px -10px;
    height: 2px;
}
.info-box .progress,
.info-box .progress .progress-bar {
    border-radius: 0;
}

.info-box .progress .progress-bar {
        float: left;
        width: 0;
        height: 100%;
        font-size: 12px;
        line-height: 20px;
        color: #fff;
        text-align: center;
        background-color: #337ab7;
        -webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
        box-shadow: inset 0 -1px 0 rgba(0,0,0,.15);
        -webkit-transition: width .6s ease;
        -o-transition: width .6s ease;
        transition: width .6s ease;
}
.info-box-icon {
    border-top-left-radius: 2px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 2px;
    display: block;
    overflow: hidden;
    float: left;
    height: 90px;
    width: 90px;
    text-align: center;
    font-size: 45px;
    line-height: 90px;
    background: rgba(0, 0, 0, 0.08) !important
}
.info-box-sm .info-box-icon {
    height: 80px;
    width: 80px;
    font-size: 25px;
    line-height: 92px;
}
.opened-dash-board-wrap .info-box-sm .info-box-icon {
    line-height: 80px;
}
.info-box-module .info-box-icon {
    height: 98px;
}
.info-box-icon > img {
    max-width: 100%;
}
.info-box-module .info-box-icon > img {
    max-width: 60%;
}

a.info-box-text.info-box-text-a {
    display: table-cell;
}
a.info-box-text-a i.fa.fa-exclamation-triangle {
    font-size: 0.9em;
}

.info-box-icon-text{
    box-sizing: border-box;
    display: block;
    position: absolute;
    width: 90px;
    bottom: 0px;
    color: #ffffff;
    background-color: rgba(0,0,0,0.1);
    cursor: default;

    font-size: 10px;
    line-height: 15px;
    padding: 0px 3px;
    text-align: center;
    opacity: 0;
    -webkit-transition: opacity 0.5s, visibility 0s 0.5s;
    transition: opacity 0.5s, visibility 0s 0.5s;
}

.info-box-icon-version {
    box-sizing: border-box;
    display: block;
    position: absolute;
    width: 90px;
    bottom: 0px;
    color: #ffffff;
    background-color: rgba(0,0,0,0.1);
    cursor: default;

    font-size: 10px;
    line-height: 22px;
    padding: 0px 3px;
    text-align: center;
    opacity: 1;
    -webkit-transition: opacity 0.5s, visibility 0s 0.5s;
    transition: opacity 0.5s, visibility 0s 0.5s;
}
.box-flex-item.info-box-module.info-box-module-disabled {
    /* opacity: 0.6; */
}

.info-box-actions {
    position: absolute;
    right: 0;
    bottom: 0;
}

<?php if (empty($conf->global->MAIN_DISABLE_GLOBAL_BOXSTATS) && !empty($conf->global->MAIN_INCLUDE_GLOBAL_STATS_IN_OPENED_DASHBOARD)) { ?>
.info-box-icon-text{
    opacity: 1;
}
<?php } ?>

.info-box-sm .info-box-icon-text, .info-box-sm .info-box-icon-version{
    overflow: hidden;
    width: 80px;
}
.info-box:hover .info-box-icon-text{
    opacity: 1;
}

.info-box-content {
    padding: 5px 10px;
    margin-left: 84px;
}

.info-box-sm .info-box-content{
    margin-left: 80px;
}
/*.info-box-setup span {
    color: var(--colortexttitlenotab2);
}
.tdsetuppicto span {
    color: var(--colortexttitlenotab2);
}*/

.info-box-number {
    display: block;
    font-weight: bold;
    font-size: 18px;
}
.progress-description,
.info-box-text,
.info-box-title{
    display: block;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.info-box-title{
    text-transform: uppercase;
    font-weight: bold;
    margin-bottom: 3px; /* not too much space so we can add another lines */
}
.info-box-text{
    font-size: 0.92em;
}
.info-box-text:first-letter{text-transform: uppercase}
a.info-box-text{ text-decoration: none;}


.info-box-more {
    display: block;
}
.progress-description {
    margin: 0;
}



/* ICONS INFO BOX */
<?php
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$prefix = '';
if (!empty($conf->global->THEME_INFOBOX_COLOR_ON_BACKGROUND)) $prefix = 'background-';

if (!isset($conf->global->THEME_SATURATE_RATIO)) $conf->global->THEME_SATURATE_RATIO = 0.7;
if (GETPOSTISSET('THEME_SATURATE_RATIO')) $conf->global->THEME_SATURATE_RATIO = GETPOST('THEME_SATURATE_RATIO', 'int');

?>
.info-box-icon {
    <?php if ($prefix) { ?>
    color: #fff !important;
    <?php } ?>
    opacity: 0.95;
    <?php if (isset($conf->global->THEME_SATURATE_RATIO)) { ?>
        filter: saturate(<?php echo $conf->global->THEME_SATURATE_RATIO; ?>);
    <?php } ?>
}

.bg-infobox-project{
    <?php echo $prefix; ?>color: #6c6aa8 !important;
}
.bg-infobox-action{
    <?php echo $prefix; ?>color: #a47080 !important;
}
.bg-infobox-propal,
.bg-infobox-facture,
.bg-infobox-commande{
    <?php echo $prefix; ?>color: #99a17d !important;
}
.bg-infobox-supplier_proposal,
.bg-infobox-invoice_supplier,
.bg-infobox-order_supplier{
    <?php echo $prefix; ?>color: #599caf !important;
}
.bg-infobox-contrat, .bg-infobox-ticket{
    <?php echo $prefix; ?>color: #46a676 !important;
}
.bg-infobox-bank_account{
    <?php echo $prefix; ?>color: #b0a53e !important;
}
.bg-infobox-adherent, .bg-infobox-member {
    <?php echo $prefix; ?>color: #79633f !important;
}
.bg-infobox-expensereport{
    <?php echo $prefix; ?>color: #79633f !important;
}
.bg-infobox-holiday{
    <?php echo $prefix; ?>color: #755114 !important;
}

.infobox-adherent, .infobox-member {
    color: #79633f !important;
}
.infobox-project{
    color: #6c6aa8 !important;
}
.infobox-action{
    color: #a47080 !important;
}
.infobox-propal,
.infobox-facture,
.infobox-commande{
    color: #99a17d !important;
}
.infobox-supplier_proposal,
.infobox-invoice_supplier,
.infobox-order_supplier{
    color: #599caf !important;
}
.infobox-contrat, .infobox-ticket{
    color: #46a676 !important;
}
.infobox-bank_account{
    color: #b0a53e !important;
}
.infobox-adherent, .infobox-member {
    color: #79633f !important;
}
.infobox-expensereport{
    color: #79633f !important;
}
.infobox-holiday{
    color: #755114 !important;
}


.fa-dol-action:before {
    content: "\f073";
}
.fa-dol-propal:before,
.fa-dol-supplier_proposal:before {
    content: "\f573";
}
.fa-dol-facture:before,
.fa-dol-invoice_supplier:before {
    content: "\f571";
}
.fa-dol-project:before {
    content: "\f0e8";
}
.fa-dol-commande:before,
.fa-dol-order_supplier:before {
    content: "\f570";
}
.fa-dol-contrat:before {
    content: "\f1e6";
}
.fa-dol-ticket:before {
    content: "\f3ff";
}
.fa-dol-bank_account:before {
    content: "\f19c";
}
.fa-dol-member:before {
    content: "\f0c0";
}
.fa-dol-expensereport:before {
    content: "\f555";
}
.fa-dol-holiday:before {
    content: "\f5ca";
}


/* USING FONTAWESOME FOR WEATHER */
.info-box-weather .info-box-icon{
    background: rgba(0, 0, 0, 0.08) !important;
}
.fa-weather-level0:before{
    content: "\f185";
    color : #cccccc;
}
.fa-weather-level1:before{
    content: "\f6c4";
    color : #cccccc;
}
.fa-weather-level2:before{
    content: "\f0c2";
    color : #cccccc;
}
.fa-weather-level3:before{
    content: "\f740";
    color : #cccccc;
}
.fa-weather-level4:before{
    content: "\f0e7";
    color : #b91f1f;
}


.box-flex-container{
    display: flex; /* or inline-flex */
    flex-direction: row;
    flex-wrap: wrap;
    width: calc(100% + 14px);
    margin: 0 -8px 0 -8px;
    /*justify-content: space-between;*/
}

.box-flex-item{
    flex-grow : 1;
    flex-shrink: 1;
    flex-basis: auto;

    width: 280px;
    margin: 5px 8px 0px 8px;
}
.box-flex-item.filler{
    margin: 0px 0px 0px 15px !important;
    height: 0;
}

.info-box-title {
    width: calc(100% - 20px);
}
.info-box-module {
    min-width: 350px;
    max-width: 350px;
}
@media only screen and (max-width: 1740px) {
    .info-box-module {
        min-width: 315px;
        max-width: 315px;
    }
}
@media only screen and (max-width: 767px) {
    .info-box-module {
        min-width: 260px;
    }
}


.info-box-module .info-box-content {
    height: 6.4em;
}
/* Disabled. This break the responsive on smartphone
.box{
    overflow: visible;
}
*/

@media only screen and (max-width: 767px)
{
    .box-flex-container {
        margin: 0 0 0 0px !important;
        width: 100% !important;
    }

    .info-box-module {
        width: 100%;
        max-width: unset;
    }
}


.btnTitle, a.btnTitle {
    display: inline-block;
    padding: 4px 12px 4px 12px;
    font-weight: 400;
    /* line-height: 1; */
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    box-shadow: var(--btncolorbg);
    text-decoration: none;
    position: relative;
    margin: 0 0 0 10px;
    min-width: 80px;
    text-align: center;
    color: var(--btncolortext);
    border: none;
    font-size: 12px;
    font-weight: 300;
    background-color: var(--btncolorbg);
    border: 1px solid var(--btncolorborder);
}

a.btnTitle.btnTitleSelected {
    border: 1px solid #ccc;
    border-radius: 3px;
}

.btnTitle > .btnTitle-icon{

}

.btnTitle > .btnTitle-label{
    color: #666666;
}

.btnTitle:hover, a.btnTitle:hover {
    border: 1px solid #bbb;
    border-radius: 3px;
    position: relative;
    margin: 0 0 0 10px;
    text-align: center;
    /* color: #ffffff;
    background-color: rgb(<?php print $colortextlink; ?>); */
    font-size: 12px;
    text-decoration: none;
    box-shadow: none;
}

.btnTitle.refused, a.btnTitle.refused, .btnTitle.refused:hover, a.btnTitle.refused:hover {
        color: #8a8a8a;
        cursor: not-allowed;
        background-color: #fbfbfb;
        background: repeating-linear-gradient( 45deg, #ffffff, #f1f1f1 4px, #f1f1f1 4px, #f1f1f1 4px );
}

.btnTitle:hover .btnTitle-label{
     color: var(--btncolorborderhover);
}

.btnTitle.refused .btnTitle-label, .btnTitle.refused:hover .btnTitle-label{
    color: #8a8a8a;
}

.btnTitle>.fa {
    font-size: 20px;
    display: block;
}

div.pagination li:first-child a.btnTitle{
    margin-left: 10px;
}


.imgforviewmode {
    color: #aaa;
}

/* rule to reduce top menu - 2nd reduction: Reduce width of top menu icons again */
@media only screen and (max-width: <?php echo empty($conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC2) ? round($nbtopmenuentries * 69, 0) + 130 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC2; ?>px)    /* reduction 2 */
{
    .btnTitle, a.btnTitle {
        display: inline-block;
        padding: 4px 4px 4px 4px;
        min-width: unset;
    }
}

<?php if (!empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED) && (!$user->admin)) { ?>
.butActionRefused, .butActionNewRefused, .btnTitle.refused {
    display: none !important;
}
<?php } ?>


/*
 * BTN LINK
 */

.btn-link{
    margin-right: 5px;
    border: 1px solid #ddd;
    color: #333;
    padding: 5px 10px;
    border-radius:1em;
    text-decoration: none !important;
}

.btn-link:hover{
    background-color: #ddd;
    border: 1px solid #ddd;
}

.imgdoc {
    margin: 18px;
    border: 1px solid #ccc;
    box-shadow: 1px 1px 25px #aaa;
    max-width: calc(100% - 56px);
}
.fa-file-text-o, .fa-file-code-o, .fa-file-powerpoint-o, .fa-file-excel-o, .fa-file-word-o, .fa-file-o, .fa-file-image-o, .fa-file-video-o, .fa-file-audio-o, .fa-file-archive-o, .fa-file-pdf-o {
    color: #055;
}

.fa-15 {
    font-size: 1.5em;
}

.margintoponly {
    margin-top: 10px !important;
}


/* For table into table into card */
div.fichehalfright tr.liste_titre:first-child td table.nobordernopadding td {
    padding: 0 0 0 0;
}
div.nopadding {
    padding: 0 !important;
}

.containercenter {
    display : table;
    margin : 0px auto;
}

td.nobordernopadding.widthpictotitle.col-picto {
    color: #bbb;
    opacity: 0.85;
}
.table-list-of-attached-files .col-picto, .table-list-of-links .col-picto {
    opacity: 0.7 !important;
    font-size: 0.7em;
    width: 20px;
}
.table-list-of-attached-files .col-picto .widthpictotitle, .table-list-of-links .col-picto .widthpictotitle {
    width: unset;
    color: #999;
}

<?php
    $colortexttitlenotab = '0,123,140';
    if (!isset($conf->global->THEME_ELDY_TEXTTITLENOTAB)) $conf->global->THEME_ELDY_TEXTTITLENOTAB = $colortexttitlenotab;

?>
span.widthpictotitle.pictotitle {
    /* background: rgba(70, 3, 62, 0.5); */
    background: #608FBE;
    opacity: 0.8;
    color: #fff !important;
    padding: 7px;
    border-radius: 2px;
    min-width: 30px;
    text-align: center;
    font-size: 1.7em;
}
.pictotitle {
    /* margin-bottom: 4px; */
}
.pictoobjectwidth {
    width: 14px;
}
.pictosubstatus {
    padding-left: 2px;
    padding-right: 2px;
}
.pictostatus {
    width: 15px;
    vertical-align: middle;
    margin-top: -3px
}
.pictowarning, .pictopreview {
    padding-<?php echo $left; ?>: 3px;
}
.pictowarning {
    /* vertical-align: text-bottom; */
    color: <?php echo $badgeWarning ?>;
}
.pictoerror {
    color: <?php echo $badgeDanger ?>;
}
.pictomodule {
    width: 14px;
}
.pictomodule {
    width: 14px;
}

<?php if($dolvs >= 12){ ?>
    div#id-top div.login_block .login_block_other{
        float: none;
    }

<?php }?>
td.widthpictotitle {
    width: 26px;
    text-align: left;
}
/*
.optionblue {
    color: var(--colortextlink);
}
.optiongrey, .opacitymedium {
    opacity: 0.4;
}
.opacitymediumbycolor {
    color: rgba(0, 0, 0, 0.4);
}
.opacitylow {
    opacity: 0.6;
}
.opacityhigh {
    opacity: 0.2;
}
.opacitytransp {
    opacity: 0;
}
.colorwhite {
    color: #fff;
}
.colorblack {
    color: #000;
}*/

.info-box-module .info-box.info-box-sm{
    min-height: 100px !important;
}


.blockvmenu .menu_titre {
    margin-top: 4px;
    margin-bottom: 1px;
}
div.menu_titre {
    padding-top: 4px;
    padding-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
}

div.vmenu, td.vmenu {
    margin: 0px;
    position: relative;
    float: left;
    padding: 0px;
    padding-bottom: 0px;
    padding-top: 1px;
    width: 210px;
}

div.vmenu, td.vmenu {
}


body #id-left div.vmenu div.menu_contenu {
    padding: 3px 1.8em;
}



.optionblue {
    color: var(--colortextlink);
}
.optiongrey, .opacitymedium {
    opacity: 0.4;
}
.opacitymediumbycolor {
    color: rgba(0, 0, 0, 0.4);
}
.opacitylow {
    opacity: 0.6;
}
.opacityhigh {
    opacity: 0.2;
}
.opacitytransp {
    opacity: 0;
}
.colorwhite {
    color: #fff;
}
.colorblack {
    color: #000;
}
.paddingleft {
    padding-left: 4px;
}
td.col-title div.titre{
    font-size: 1.4em;
}

#img_logo, .img_logo {
    max-width: 170px;
    max-height: 90px;
}

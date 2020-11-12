<?php
session_cache_limiter('public');
// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server
// and if no cache-control added later, a default cache delay (10800) will be added by PHP.

require '../config.php';

$langs->load('fastupload@fastupload');

$max_file_size = 0;

// Dolibarr style @see html.formfile.php::form_attach_new_file
$max=$conf->global->MAIN_UPLOAD_DOC;		// En Kb
$maxphp=@ini_get('upload_max_filesize');	// En inconnu
if (preg_match('/k$/i',$maxphp)) $maxphp=$maxphp*1;
if (preg_match('/m$/i',$maxphp)) $maxphp=$maxphp*1024;
if (preg_match('/g$/i',$maxphp)) $maxphp=$maxphp*1024*1024;
if (preg_match('/t$/i',$maxphp)) $maxphp=$maxphp*1024*1024*1024;
// Now $max and $maxphp are in Kb
if ($maxphp > 0) $max=min($max,$maxphp);
if ($max > 0)
{
	$max_file_size = $max/1024; // Conversion Kb en Mb
}

?>
//<script type="text/javascript">
$(document).ready( function() {
	Dropzone.autoDiscover = false;

	enableDropzone = function(form, paramName) {
		var classPrefix = "dropzone";
		var zone_class = "." + classPrefix;
		var zone = $(zone_class);

		try {

			var zone_object = new Dropzone(form[0], {
				paramName: paramName,
				autoProcessQueue: <?php echo !empty($conf->global->FASTUPLOAD_ENABLE_AUTOUPLOAD) ? 'true' : 'false'; ?>,
				addRemoveLinks: !<?php echo !empty($conf->global->FASTUPLOAD_ENABLE_AUTOUPLOAD) ? 'true' : 'false'; ?>,
				clickable: zone_class,
				previewsContainer: "#" + classPrefix + "-previews-box",
				uploadMultiple: <?php echo (float) DOL_VERSION < 4.0 ? 'false' : 'true'; ?>,
				parallelUploads: 100,
				previewTemplate: "<div class=\"dz-preview dz-file-preview\">\n  \n\
										<div class=\"dz-details\">\n\n\
											<div class=\"dz-filename\">\n\
												<span data-dz-name></span>\n\
											</div>\n\n\
											<div class=\"dz-size\" data-dz-size></div>\n\n\
											<img data-dz-thumbnail />\n\n\
										</div>\n\n\
										<div class=\"dz-progress\"><span class=\"dz-upload\" data-dz-uploadprogress></span></div>\n\
										<div class=\"dz-success-mark\"><span></span></div>\n\n\
										<div class=\"dz-error-mark\"><span></span></div>\n\n\
										<div class=\"dz-error-message\"><span data-dz-errormessage></span></div>\n\n\
									</div>",
				maxFilesize: <?php echo $max_file_size; ?>,
				maxFiles: <?php echo !empty($conf->global->FASTUPLOAD_LIMIT_FILE_NUMBER) ? $conf->global->FASTUPLOAD_LIMIT_FILE_NUMBER : 50; ?>,
				dictDefaultMessage: "<?php echo addslashes($langs->transnoentities('FastUpload_DefaultMessage')); ?>",
				dictFallbackMessage: "<?php echo addslashes($langs->transnoentities('FastUpload_FallbackMessage')); ?>",
				dictFallbackText: "<?php echo addslashes($langs->transnoentities('FastUpload_FallbackText')); ?>",
				dictFileTooBig: "<?php echo addslashes($langs->transnoentities('FastUpload_FileTooBig')); ?>",
				dictInvalidFileType: "<?php echo addslashes($langs->transnoentities('FastUpload_InvalidFileType')); ?>",
				dictResponseError: "<?php echo addslashes($langs->transnoentities('FastUpload_ResponseError')); ?>",
				dictCancelUpload: "<?php echo addslashes($langs->transnoentities('FastUpload_CancelUpload')); ?>",
				dictCancelUploadConfirmation: "<?php echo addslashes($langs->transnoentities('FastUpload_CancelUploadConfirmation')); ?>",
				dictRemoveFile: "<?php echo addslashes($langs->transnoentities('FastUpload_RemoveFile')); ?>",
				dictRemoveFileConfirmation: "<?php echo addslashes($langs->transnoentities('FastUpload_RemoveFileConfirmation')); ?>",
				dictMaxFilesExceeded: "<?php echo addslashes($langs->transnoentities('FastUpload_MaxFilesExceeded')); ?>",

				init: function () {
					var dropzone = this;
					var form = $(this.options.clickable).closest("form");
					form.on("submit", function (e) {
						if (dropzone.getQueuedFiles().length) {
							e.preventDefault();
							e.stopPropagation();
							dropzone.processQueue();
						}
					});
				},
				fallback: function () {
					if ($("." + classPrefix).length) {
						$("." + classPrefix).hide();
					}
				},
				// Never call under 4.0 version
				successmultiple: function(files, response) {
					$("table.liste:first").replaceWith($(response).find("table.liste:first")); // DOL_VERSION < 6.0
					$("#tablelines").replaceWith($(response).find("#tablelines")); // DOL_VERSION >= 6.0
					this.removeAllFiles();
				},
				success: function(file, response) {
					<?php if ((float) DOL_VERSION < 4.0) { ?>
					$("table.liste:first").replaceWith($(response).find("table.liste:first"));
					this.removeFile(file);
					<?php } ?>
				}
			});

		} catch (e) {
			alert("<?php echo addslashes($langs->transnoentities('FastUpload_DropzoneNotSupported')); ?>");
		}
	};

});
//</scrip>
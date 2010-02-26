<?php
/**
* @version $Id$
* Kunena Component
* @package Kunena
*
* @Copyright (C) 2008 - 2010 Kunena Team All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @link http://www.kunena.com
*
* Based on FireBoard Component
* @Copyright (C) 2006 - 2007 Best Of Joomla All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @link http://www.bestofjoomla.com
*
* Based on Joomlaboard Component
* @copyright (C) 2000 - 2004 TSMF / Jan de Graaff / All Rights Reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @author TSMF & Jan de Graaff
**/

// Dont allow direct linking
defined( '_JEXEC' ) or die();


$kunena_db = &JFactory::getDBO();

require_once(KUNENA_PATH_LIB .DS. 'kunena.file.class.php');

function generateAvatarGD($gdversion, $src_img, $srcWidth, $srcHeight, $dstWidth, $dstHeight, $quality, $location)
{
	if ($srcWidth>$dstWidth || $srcHeight>$dstHeight) {
		$ratio = $srcWidth/$srcHeight;
		if ($dstWidth/$dstHeight > $ratio) {
			$dstWidth = $dstHeight*$ratio;
		} else {
			$dstHeight = $dstWidth/$ratio;
		}
	} else {
		$dstWidth = $srcWidth;
		$dstHeight = $srcHeight;
	}
	if ((int)$gdversion == 1) {
		$dst_img = imagecreate($dstWidth, $dstHeight);
		imagecopyresized($dst_img, $src_img, 0, 0, 0, 0, (int)$dstWidth, (int)$dstHeight, $srcWidth, $srcHeight);
	} else {
		$dst_img = imagecreatetruecolor($dstWidth, $dstHeight);
		imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, (int)$dstWidth, (int)$dstHeight, $srcWidth, $srcHeight);
	}
	$tmpfile = tempnam(CKunenaPath::tmpdir(), "kn_");
	imagejpeg($dst_img, $tmpfile, $quality);
	CKunenaFile::copy($tmpfile, $location);
	unlink($tmpfile);
	imagedestroy($dst_img);
}

function kn_myprofile_kn_myprofile_check_filesize($file, $maxSize)
{
    $size = filesize($file);

    if ($size <= $maxSize) {
        return true;
    }

    return false;
}

function kn_myprofile_display_avatar_gallery($avatar_gallery_path)
{
    $dir = @opendir($avatar_gallery_path);
    $avatar_images = array ();
    $avatar_col_count = 0;

    $file = @readdir($dir);

    while ($file)
    {
        if ($file != '.' && $file != '..' && is_file($avatar_gallery_path .DS . $file) && !is_link($avatar_gallery_path .DS . $file))
        {
            if (preg_match('/(\.gif$|\.png$|\.jpg|\.jpeg)$/is', $file))
            {
                $avatar_images[$avatar_col_count] = $file;
                $avatar_name[$avatar_col_count] = JString::ucfirst(str_replace("_", " ", preg_replace('/^(.*)\..*$/', '\1', $file)));
                $avatar_col_count++;
            }
        }
        $file = @readdir($dir);
    }

    @closedir($dir);
    @ksort($avatar_images);
    @reset($avatar_images);
    return $avatar_images;
}

// This function was modified from the one posted to PHP.net by rockinmusicgv
// It is available under the readdir() entry in the PHP online manual
function get_dirs($directory, $select_name, $selected = "")
{
	$filelist = array();

	$dir = @opendir($directory);
	if ($dir)
    {
    	$file = readdir($dir);

        while ($file)
        {
            if ($file != ".." && $file != ".")
            {
                if (is_dir($directory . "/" . $file))
                {
                    if (!($file[0] == '.')) {
                        $filelist[] = $file;
                    }
                }
            }
            $file = readdir($dir);
        }

        closedir($dir);
    }

    if ($selected)
        $selected = str_replace("%20", " ", $selected);

    echo "<select name=\"$select_name\" id=\"avatar_category_select\" onchange=\"switch_avatar_category(this.options[this.selectedIndex].value)\">\n";
    echo "<option value=\"default\"";

    if ($selected == "") {
        echo " selected=\"selected\"";
    }

    echo ">".JText::_('COM_KUNENA_DEFAULT_GALLERY')."</option>\n";

    asort($filelist);

    foreach ($filelist as $key => $val)
    {
        echo '<option value="'.$val.'"';

        if ($selected == $val) {
            echo " selected=\"selected\"";
        }

        echo ">$val</option>\n";
    }

    echo "</select>\n";
}

$kunena_my = &JFactory::getUser();

if ($kunena_my->id != "" && $kunena_my->id != 0)
{

$kunena_config =& CKunenaConfig::getInstance();
$task = JRequest::getCmd('action', 'default');
$gallery  = JRequest::getVar('gallery', '');
$kunena_app =& JFactory::getApplication();

switch ($task) {
	case "delete":
		$rowItemid = JRequest::getInt('Itemid');

		$deleteAvatar = JRequest::getInt('deleteAvatar', 0);
		$avatar = JRequest::getVar('avatar', '');

		if ($deleteAvatar == 1)
		{
			$avatar = "";
		}

		$kunena_db->setQuery("UPDATE #__fb_users SET avatar='{$avatar}' WHERE userid='{$kunena_my->id}'");

		if (!$kunena_db->query())
		{
			$kunena_app->enqueueMessage(JText::_('COM_KUNENA_USER_PROFILE_NOT_A').JText::_('COM_KUNENA_USER_PROFILE_NOT_B').JText::_('COM_KUNENA_USER_PROFILE_NOT_C'), 'notice');
		}
		else
		{
			$kunena_app->enqueueMessage(JText::_('COM_KUNENA_USER_PROFILE_UPDATED'));
		}
		check_dberror("Unable to delete avatar.");

		$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
		break;

	case 'upload':
		//numExtensions= people tend to upload malicious files using mutliple extensions like: virus.txt.vbs; we'll want to have the last extension to validate against..
		$filename = explode('.', $_FILES['avatar']['name']);
		$numExtensions = (count($filename)) - 1;
		$avatarName = $filename[0];
		$avatarExt = $filename[$numExtensions];
		$newFileName = $kunena_my->id . "." . $avatarExt;
		$imageType = array( 1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF', 5 => 'PSD', 6 => 'BMP', 7 => 'TIFF', 8 => 'TIFF', 9 => 'JPC', 10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF');

		//move it to the proper location
		//if (!move_uploaded_file($_FILES['avatar']['tmp_name'], KUNENA_PATH_UPLOADED .DS. "avatars/$newFileName"))
		//echo JText::_('COM_KUNENA_UPLOAD_ERROR_GENERAL');

		//Filename Medium + proper path
		$fileLocation = KUNENA_PATH_UPLOADED .DS. "avatars/$newFileName";
		//Filename Small + proper path
		$fileLocation_s = KUNENA_PATH_UPLOADED .DS. "avatars/s_$newFileName";
		//Filename Large + proper path
		$fileLocation_l = KUNENA_PATH_UPLOADED .DS. "avatars/l_$newFileName";
		echo '<table width = "100%" border = "0" cellspacing = "0" cellpadding = "0">';
		echo '<tr><td><div><div><div><div><table><tbody><tr><td>';
		//Avatar Size
		$avatarSize = $_FILES['avatar']['size'];
		$src_file = $_FILES['avatar']['tmp_name'];

		//check for empty file
		if (!is_uploaded_file($src_file) || empty($_FILES['avatar']['name']))
		{
			$kunena_app->enqueueMessage(JText::_('COM_KUNENA_UPLOAD_ERROR_EMPTY'), 'notice');
			$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
		}

		//check for allowed file type (jpeg, gif, png)
		if (!($imgtype = kunena_check_image_type($avatarExt)))
		{
			$kunena_app->enqueueMessage(JText::_('COM_KUNENA_UPLOAD_ERROR_TYPE'), 'notice');
			$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
		}

		//check file name characteristics
		if(preg_match('`[^0-9a-zA-Z_]`', $avatarExt))
		{
			$kunena_app->enqueueMessage(JText::_('COM_KUNENA_UPLOAD_ERROR_NAME'), 'notice');
			$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
		}

		//check filesize
		$maxAvSize = $kunena_config->avatarsize * 1024;

		if ($avatarSize > $maxAvSize)
		{
			$kunena_app->enqueueMessage(JText::_('COM_KUNENA_UPLOAD_ERROR_SIZE') . " (" . $kunena_config->avatarsize . " KiloBytes)", 'notice');
			$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
		}

		$imgInfo = false;
		if (function_exists('getimagesize')) {
			$imgInfo = @getimagesize($_FILES['avatar']['tmp_name']);
			if ($imgInfo !== false) {
				$imgInfo[2] = $imageType[$imgInfo[2]];
				$srcWidth = $imgInfo[0];
				$srcHeight = $imgInfo[1];
			}
		} else {
			$kunena_config->imageprocessor = 'none';
		}

		//$gdversion = preg_replace('[[:alpha:][:space:]()]+', '', $GDArray['GD Version']); // just FYI for detection from gd_info()

		switch ($kunena_config->imageprocessor) {
		case 'gd1' :
			if ( !function_exists('imagecreatefromjpeg' )) {
				$kunena_app->enqueueMessage(JText::_('COM_KUNENA_AVATAR_GDIMAGE_NOT'), 'error');
				$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
			}
			if ( $imgInfo[2] == 'JPG' ) {
				$src_img = imagecreatefromjpeg($src_file);
			} elseif ( $imgInfo[2] == 'PNG' ) {
				$src_img = imagecreatefrompng($src_file);
			} else {
				$src_img = imagecreatefromgif($src_file);
			}

			// Create Large Image
			generateAvatarGD(1, $src_img, $srcWidth, $srcHeight, $kunena_config->avatarlargewidth,
			$kunena_config->avatarlargeheight, $kunena_config->avatarquality, $fileLocation_l);
			// Create Medium Image
			generateAvatarGD(1, $src_img, $srcWidth, $srcHeight, $kunena_config->avatarwidth,
			$kunena_config->avatarheight, $kunena_config->avatarquality, $fileLocation);
			// Create Small Image
			generateAvatarGD(1, $src_img, $srcWidth, $srcHeight, $kunena_config->avatarsmallwidth,
			$kunena_config->avatarsmallheight, $kunena_config->avatarquality, $fileLocation_s);
			// Destroy source Image
			imagedestroy($src_img);
			break;

		case 'gd2' :

			if ( !function_exists('imagecreatefromjpeg') ) {
				$kunena_app->enqueueMessage(JText::_('COM_KUNENA_AVATAR_GDIMAGE_NOT'), 'error');
				$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
			}
			if ( !function_exists('imagecreatetruecolor') ) {
				$kunena_app->enqueueMessage(JText::_('COM_KUNENA_AVATAR_GD2IMAGE_NOT'), 'error');
				$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
			}
			if ( $imgInfo[2] == 'JPG' ) {
				$src_img = imagecreatefromjpeg($src_file);
			} elseif ($imgInfo[2] == 'PNG'){
				$src_img = imagecreatefrompng($src_file);
			} else {
				$src_img = imagecreatefromgif($src_file);
			}

			// Create Large Image
			generateAvatarGD(2, $src_img, $srcWidth, $srcHeight, $kunena_config->avatarlargewidth,
			$kunena_config->avatarlargeheight, $kunena_config->avatarquality, $fileLocation_l);
			// Create Medium Image
			generateAvatarGD(2, $src_img, $srcWidth, $srcHeight, $kunena_config->avatarwidth,
			$kunena_config->avatarheight, $kunena_config->avatarquality, $fileLocation);
			// Create Small Image
			generateAvatarGD(2, $src_img, $srcWidth, $srcHeight, $kunena_config->avatarsmallwidth,
			$kunena_config->avatarsmallheight, $kunena_config->avatarquality, $fileLocation_s);
			// Destroy source Image
			imagedestroy($src_img);
			break;

		default:
			if (isset($srcWidth) && ($srcWidth > $kunena_config->avatarlargewidth || $srcHeight > $kunena_config->avatarlargeheight)) {
				$kunena_app->enqueueMessage(JText::_('COM_KUNENA_UPLOAD_ERROR_SIZE') . " (" . $kunena_config->avatarlargewidth . " x ". $kunena_config->avatarlargeheight .")", 'notice');
				$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
			}
			// Make sure that we do not use wrong avatar image
			if (file_exists($fileLocation_s)) CKunenaFile::delete($fileLocation_s);
			if (file_exists($fileLocation_l)) CKunenaFile::delete($fileLocation_l);
			CKunenaFile::copy($src_file, $fileLocation);
			break;
		}

		// delete original file
		unlink($src_file);

		$kunena_db->setQuery("UPDATE #__fb_users SET avatar='{$newFileName}' WHERE userid={$kunena_my->id}");
		$kunena_db->query() or check_dberror("Unable to update avatar.");

		$kunena_app->redirect(CKunenaLink::GetMyProfileURL($kunena_config,'','','nofollow', true),JText::_('COM_KUNENA_UPLOAD_UPLOADED'));

	case 'gallery':
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		$newAvatarPath = JFolder::makeSafe(JRequest::getVar('newAvatarPath', ''));
		$newAvatar = JFile::makeSafe(JRequest::getVar('newAvatar', ''));

		if ($newAvatar == '') {
			$kunena_app->enqueueMessage(JText::_('COM_KUNENA_UPLOAD_ERROR_CHOOSE'), 'notice');
			$kunena_app->redirect(CKunenaLink::GetMyProfileAvatarURL('',true));
		}

		if ($newAvatarPath) $newAvatarPath .= '/';
		$kunena_db->setQuery("UPDATE #__fb_users SET avatar='gallery/{$newAvatarPath}{$newAvatar}' WHERE userid={$kunena_my->id}");
		$kunena_db->query() or check_dberror("Unable to update user avatar.");

		$kunena_app->redirect(CKunenaLink::GetMyProfileURL($kunena_config,'','','nofollow', true),JText::_('COM_KUNENA_UPLOAD_UPLOADED'));
		break;
}


if ($task == 'default')
{
    if ($kunena_config->allowavatar)
    {
?>
        <td class = "kmyprofile_right" valign = "top">
            <!-- B:My Profile Right -->
            <!-- B: My AVATAR -->
            <form action = "<?php echo CKunenaLink::GetMyProfileAvatarURL('delete'); ?>" method = "post" name = "postform">
    <table class = "kblocktablehalf fltlft" id="kforumprofile_sub" border = "0" cellspacing = "0" cellpadding = "0">
        <thead>
            <tr>
                <th colspan = "2">
                    <div class = "ktitle_cover">
                        <span class = "ktitle"><?php echo JText::_('COM_KUNENA_CHOOSE_AVATAR'); ?></span>
                    </div>
                </th>
            </tr>
        </thead>

        <tbody  class = "kmyprofile_general">
            <tr>
                <td >
                    <?php
                        echo JText::_('COM_KUNENA_YOUR_AVATAR') . ":</td><td >";
							$userprofile = CKunenaUserprofile::getInstance($kunena_my->id);
                            $avatar = $userprofile->avatar;
                            if ($avatar != "")
                            {
								if(!file_exists(KUNENA_PATH_UPLOADED .DS. 'avatars/l_' . $avatar)) {
									$msg_avatar = '<img src="' . KUNENA_LIVEUPLOADEDPATH . '/avatars/' . $avatar . '" alt="" style="max-width: '.$kunena_config->avatarlargewidth.'px; max-height: '.$kunena_config->avatarlargeheight.'px;" />';
								} else {
									$msg_avatar = '<img src="' . KUNENA_LIVEUPLOADEDPATH . '/avatars/l_' . $avatar . '" alt="" />';
								}
								echo $msg_avatar;
                    ?>

                                <br />

                                <input type = "checkbox" value = "1" name = "deleteAvatar"/><em> <?php echo JText::_('COM_KUNENA_USER_DELETEAV'); ?></em>

                    <?php
                            }
                            else
                            {
                                echo JText::_('COM_KUNENA_NON_SELECTED');
                    ?>

                            <br /> <?php echo CKunenaLink::GetMyProfileAvatarLink(JText::_('COM_KUNENA_SET_NEW_AVATAR'), JText::_('COM_KUNENA_SET_NEW_AVATAR')); ?>

                    <?php
                            }
                    ?>

                        <input type = "hidden" value = "<?php echo $avatar;?>" name = "avatar"/>
                </td>

            </tr>
<?php if ($avatar != ""){ ?>
            <tr><td colspan = "2" align="center">
            <input type = "submit" class = "button" value = "<?php echo JText::_('COM_KUNENA_GEN_DELETE');?>"/></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</form>
            <!-- F: My AVATAR -->

<?php
if ($kunena_config->allowavatarupload)
{
?>

            <!-- B: Upload -->
<table class = "kblocktablehalf" id ="kforumua" border = "0" cellspacing = "0" cellpadding = "0" >
            <thead>
                <tr>
                    <th>
                        <div class = "ktitle_cover">
                        <span class="ktitle" ><?php echo JText::_('COM_KUNENA_UPLOAD_SUBMIT'); ?></span>
                        <?php // echo JText::_('COM_KUNENA_UPLOAD_DIMENSIONS') . ": " . $kunena_config->avatarwidth . "x" . $kunena_config->avatarheight . " - " . $kunena_config->avatarsize . " KB"; ?>
                        </div>
					</th>
                </tr>
            </thead>
            <tbody>
            <tr>
            <td class="kuadesc">
<?php

        echo '<form action="' . CKunenaLink::GetMyProfileAvatarURL('upload') . '" method="post" name="adminForm" enctype="multipart/form-data">';
        echo "<table width='100%' border='0' cellpadding='4' cellspacing='2'>";
        echo "<tr align='center' valign='middle'><td align='center' valign='top'>";
        $uplabel = JText::_('COM_KUNENA_UPLOAD_UPLOAD');
        //echo " <input type='hidden' name='MAX_FILE_SIZE' value='".$maxAllowed."' />";
        echo JText::_('COM_KUNENA_UPLOAD_SELECT_FILE') . " <input type='file' class='button' name='avatar' />";
        echo "<input type='submit' class='button' value='" . JText::_('COM_KUNENA_UPLOAD_UPLOAD') . "' />";
        echo "</td></tr></table><br />";
        echo "</form>";
?>
            </td>
        </tr>
    </tbody>
</table>
            <!-- B: Upload -->
<?PHP
    }

	} // allow avatar upload

    if ($kunena_config->allowavatargallery)
    {
?>
            <!-- B: Gallery -->
<table class = "kblocktable" id ="kforumua_gal" border = "0" cellspacing = "0" cellpadding = "0" width="100%">
            <thead>
                <tr>
                    <th>
                        <div class = "ktitle_cover">
                        <span class="ktitle" ><?php echo JText::_('COM_KUNENA_UPLOAD_GALLERY'); ?></span>
                        </div>
					</th>
                </tr>
            </thead>
            <tbody>
            <tr>
            <td class="kuadesc">

        <script type = "text/javascript">
            <!--
            function switch_avatar_category(gallery)
            {
                if (gallery == "")
                    return;

                location.href = "<?php echo CKunenaLink::GetMyProfilAvatarGalleryURL();?>"+gallery;
            }
                    //-->
        </script>

<?php
        echo "<p align=\"center\">";
        get_dirs(KUNENA_PATH_UPLOADED .DS. 'avatars/gallery', "categoryid", $gallery);
        echo '<input type="button" value="'. JText::_('COM_KUNENA_GO') .'" class="button" onclick="switch_avatar_category(jQuery(\'#avatar_category_select\').val())" />'."\n";
        echo "</p>";
        echo "<br />\n";
        echo '<form action="' . CKunenaLink::GetMyProfileAvatarURL('gallery') . '" method="post" name="adminForm">';
        echo "<table width='100%' border='0' cellpadding='4' cellspacing='2'>";
        echo "<tr align='center' valign='middle'>";

        if ($gallery == "default") $gallery='';
        else $gallery .= '/';
        $avatar_gallery_path = KUNENA_PATH_UPLOADED .DS. 'avatars/gallery/' . $gallery;
        $avatar_images = array ();
        $avatar_images = kn_myprofile_display_avatar_gallery($avatar_gallery_path);

        for ($i = 0; $i < count($avatar_images); $i++)
        {
            $j = $i + 1;
            echo '<td>';
            echo '<img src="' . KUNENA_LIVEUPLOADEDPATH . '/avatars/gallery/' . $gallery . $avatar_images[$i] . '" alt="" />';
            echo '<input type="radio" name="newAvatar" value="' . $avatar_images[$i] . '"/>';
            echo "</td>\n";

            if (!fmod(($j), 5)) {
                echo '</tr><tr align="center" valign="middle">';
            }
        }

        echo '</tr>';
        echo '<tr><td colspan="5" align="center">';
        echo '<input type="radio" name="newAvatarPath" value="' . $gallery . '"/>';
        echo '<br /><br /><input type="submit" class="button" value="' . JText::_('COM_KUNENA_SAVE') . '"/><br />';
        echo '</td></tr></table>';
        echo "</form>";
?>
            </td>
        </tr>
    </tbody>
</table>
            <!-- F: Gallery -->
<?php
    }
}
?>
 <!-- F:My Profile Right -->

<?php
}
else
{
	$kunena_app->enqueueMessage ( JText::_('COM_KUNENA_A_REGISTERED_ONLY') . '<br/>' . JText::_('COM_KUNENA_FORUM_UNAUTHORIZIED2'), 'error' );
}
?>
<?php

/**
 * A class to group Array Methods together
 *
 * @author Julien Hoarau <jh@datasphere.ch>
 */
class UtilsJSON {
    
    public static function jsEscape($str) {
        return addcslashes($str,"\\\'\"&\n\r<>"); 
    }
    
    public static function generateLink($href = '', $imgUrl = '', $imgTitle = '', $linkText = '', $target = '')
    {
        $link = '';
        $link = "<a ";
        if ($target) {
            $link .= " target=\"".$target."\"";
        }
        $link .= " href=\"".$href."\">";
        if ($imgUrl) {
            $imgTitle   = htmlspecialchars($imgTitle);
            $link       .= "<img src=\"".$imgUrl."\" title=\"".$imgTitle."\"  style=\"width:16px;height:16px;\" />";
        }
        if ($linkText) {
            $link       .= $linkText;
        }
        $link .= "</a>";
        
        return $link;
    }
    
    public static function generateMainDialogLink($dialogTitle = '', $href = '', $imgUrl = '', $imgTitle = '', $linkText = '')
    {
        $link = '';
        $link = "<a href=\"#\" onClick=\"dijit.byId('MainDialog').attr('title', '".self::jsEscape($dialogTitle)."');dijit.byId('MainDialog').attr('href','".$href."');dijit.byId('MainDialog').show();dijit.byId('MainDialog').closeButtonNode.title = '"._('Cancel')."';\">";
        if ($imgUrl) {
            $imgTitle   = htmlspecialchars($imgTitle);
            $link       .= "<img src=\"".$imgUrl."\" title=\"".$imgTitle."\" alt=\"".$imgTitle."\" style=\"width:16px;height:16px;\"  />";
        }
        if ($linkText) {
            $link       .= $linkText;
        }
        $link .= "</a>";
        
        return $link;
    }
    
}

?>

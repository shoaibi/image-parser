<?php

/**
 * Class ThumbnailHtmlPageGenerator
 * Utility class to generate an html page with given array of converted images
 */
abstract class ThumbnailHtmlPageGenerator
{

    /**
     * Render a html page for converted images
     */
    public static function renderHtml($thumbnails)
    {
        $content = '';
        foreach ($thumbnails as $imagePath)
        {
            // could have hardcoded the <img> element, using a wrapper to add common
            // features like alt, etc in future and have them apply equally to all generated
            // tags
            $content .= static::generateThumbnailImageTag($imagePath);
            $content .= "<br />";
        }
        // heredoc to be used as a html template
        // in real life this could be a view template with strtr based transformations
        $template = <<<HTM
<!doctype html>
<html>
<head>
<title>Thumbails</title>
</head>
<body style="background-color:#CCCCCC;">
$content
</body>
</html>
HTM;
        // time to display the results of the hard word
        echo $template;
    }

    /**
     * Generate an image (html) tag for provided file path.
     * @param $imageFilePath
     * @return string
     */
    protected static function generateThumbnailImageTag($imageFilePath)
    {
        // nothing fancy
        return "<img src='$imageFilePath' />";
    }
}

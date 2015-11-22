<?php
require "StringUtil.php";
require "ThumbnailGenerator.php";
require "ThumbnailHtmlPageGenerator.php";

/**
 * Class ImageParser
 * Main implementation of the Remote Image Resizer and Parser
 */
abstract class ImageParser
{
    /**
     * Path to source image catalog
     */
    const IMAGE_CATALOG_URL = "https://shopgate-static.s3.amazonaws.com/worktrail/backend/image_scaling/images.dat";

    /**
     * Local path to cache downloaded images to save processing on multiple runs
     */
    const CACHE_PATH = "./cache";

    /**
     * Local path to save resized and adjusted thumbnails
     */
    const THUMB_PATH = './thumb';

    /**
     * Thumbnail Width
     */
    const THUMB_WIDTH = 600;

    /**
     * Thumbnail Height
     */
    const THUMB_HEIGHT = 600;

    /**
     * @var array
     * List of images that we could not load
     */
    protected static $failedToLoad = array();


    /**
     * @var array
     * List of images we could successfully convert to thumbnails along with their paths
     */
    protected static $thumbnails = array();

    /**
     * Wrapper function to carry out all chores
     */
    public static function generateHtmlPage()
    {
        static::createImageCacheDirectory();
        static::createThumbnailDirectory();
        $imageUrls = static::getImagesListFromSourceUrl();
        if (!empty($imageUrls))
        {
            static::cacheAndConvertImagesByUrls($imageUrls);
        }
        ThumbnailHtmlPageGenerator::renderHtml(static::$thumbnails);
    }

    /**
     * Get a list of images we should generate thumbnails for
     * @return array
     */
    protected static function getImagesListFromSourceUrl()
    {
        // TODO: @Shoaibi: Change to a better method of buffered chunked streamed I/O
        $catalogContent = file(static::IMAGE_CATALOG_URL, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $catalogContent;
    }

    /**
     * Create the local thumbnail directory
     * @throws Exception
     */
    protected static function createThumbnailDirectory()
    {
        static::createDirectory(static::THUMB_PATH);
    }

    /**
     * Create the local cache directory
     * @throws Exception
     */
    protected static function createImageCacheDirectory()
    {
        static::createDirectory(static::CACHE_PATH);
    }

    /**
     * A wrapper function to create a directory on local filesystem
     * @param $path
     * @throws Exception
     */
    protected static function createDirectory($path)
    {
        // is it a dir already?
        if (is_dir($path))
        {
            // do we even have writable access?
            if (!is_writable($path))
            {
                throw new Exception($path . " is not writable");
            }
        }
        // not a directory? lets create it.
        // please note that we do not care if their is another type of object (say a file) with same
        // path information as the directory we are trying to create.
        else if (!mkdir($path))
        {
            // whooppsie, something went kaboom!
            throw new Exception("Unable to create " . $path . " directory");
        }
    }

    /**
     * Download images from their remote paths, convert them to thumbnails and save them
     * @param $urls
     */
    protected static function cacheAndConvertImagesByUrls($urls)
    {
        foreach ($urls as $url)
        {
            try
            {
                $sourceFileName = basename($url);
                $targetFileName = static::generateTargetFileName($sourceFileName);
                $sourceFilePath = static::getSourceFilePathByName($sourceFileName);
                $targetFilePath = static::getThumbFilePathByName($targetFileName);
                // pull image from remote into local path for caching purposes
                static::cacheImage($url, $sourceFilePath);
                // got the image? time to create thumbnail for it.
                ThumbnailGenerator::generate($sourceFilePath, $targetFilePath, static::THUMB_WIDTH, static::THUMB_HEIGHT);
                // successfully generated thumbnail? add it to the items we have processed.
                static::$thumbnails[] = $targetFilePath;
            }
            catch (Exception $e)
            {
                // couldn't process the item for some reason? Say remote resource is not available?
                // add it to failed items so we know we need to fix these.
                static::$failedToLoad[] = $url;
            }
        }
    }

    /**
     * Generate path name for the local cache version of a remote file against its name
     * @param $fileName
     * @return string
     */
    protected static function getSourceFilePathByName($fileName)
    {
        return static::CACHE_PATH . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Generate path name for local thumbnail version of a remote file against its name
     * @param $fileName
     * @return string
     */
    protected static function getThumbFilePathByName($fileName)
    {
        return static::THUMB_PATH . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Generate file name for the target, resized version.
     * @param $sourceFileName
     * @return string
     */
    protected static function generateTargetFileName($sourceFileName)
    {
        // We are standardizing all images to be jpg
        if (!StringUtil::endsWith($sourceFileName, '.jpg'))
        {
            return pathinfo($sourceFileName, PATHINFO_FILENAME) . '.jpg';
        }
        return $sourceFileName;
    }

    /**
     * Given a url, download it and cache it locally
     * @param $url
     * @param $targetFilePath
     * @throws Exception
     */
    protected static function cacheImage($url, $targetFilePath)
    {
        // TODO: @Shoaibi: use headers to ensure that the url is indeed an image
        // TODO: @Shoaibi: use headers to get if the relevant image has changed on source, if so, update it even if it
        // already exists.
        if (file_exists($targetFilePath))
        {
            return;
        }
        // time to eat bandwidth
        $fileContents = @file_get_contents($url);
        // whaaaaatttt? no contents to save? or can't save them? COMPLAIN!
        if (empty($fileContents) || !file_put_contents($targetFilePath, $fileContents))
        {
            throw new Exception("Unable to cache: $url image");
        }
    }

}

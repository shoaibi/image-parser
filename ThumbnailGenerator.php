<?php

/**
 * Class ThumbnailGenerator
 * Generate thumbnails against provided source image with provided height, width and quality specification
 */
abstract class ThumbnailGenerator
{
    /**
     * The hard stuff. Resizing an image while applying an appropriate background
     * @param $sourceImage
     * @param $targetImage
     * @param $targetWidth
     * @param $targetHeight
     * @param int $quality
     * @throws Exception
     */
    public static function generate($sourceImage, $targetImage, $targetWidth, $targetHeight, $quality = 100)
    {
        // get source dimensions
        list($sourceWidth, $sourceHeight, $sourceMimeType) = getimagesize($sourceImage);

        // resolving mime type e.g. image/*
        $imageType = image_type_to_mime_type($sourceMimeType);

        // resolve Image Resource handler against the said image type and the source image path
        $sourceImageResource = static::getImageResourceFromImageTypeAndFile($imageType, $sourceImage);

        // resolve aspect-ratio maintained height and width for the thumbnail against source's dimensions and expected
        // dimensions
        list($calculatedTargetWidth, $calculatedTargetHeight) = static::resolveNewWidthAndHeight($sourceWidth, $sourceHeight, $targetWidth, $targetHeight);

        // create an image with the aspect-ration maintained height and width, this will be used to resample the source
        // image
        $resampledImage = imagecreatetruecolor(round($calculatedTargetWidth), round($calculatedTargetHeight));
        imagecopyresampled($resampledImage, $sourceImageResource, 0, 0, 0, 0, $calculatedTargetWidth, $calculatedTargetHeight, $sourceWidth, $sourceHeight);
        // create an image of the thumbnail size we desire (this may be less than the aspect-ration maintained height
        // and width
        $targetImageResource = imagecreatetruecolor($targetWidth, $targetHeight);
        // setup a padding color, in our case we use white.
        $paddingColor = imagecolorallocate($targetImageResource, 255, 255, 255);
        // paint the target image all in the padding color so the canvas is completely white.
        imagefill($targetImageResource, 0, 0, $paddingColor);

        // now copy the resampled aspect-ratio maintained resized thumbnail onto the white canvas
        imagecopy($targetImageResource, $resampledImage, (($targetWidth - $calculatedTargetWidth)/ 2), (($targetHeight - $calculatedTargetHeight) / 2), 0, 0, $calculatedTargetWidth, $calculatedTargetHeight);

        // save the resized thumbnail.
        if (!imagejpeg($targetImageResource, $targetImage, $quality)) {
            throw new Exception("Unable to save new image");
        }
    }

    /**
     * Resolve image resource handler against a given type and path
     * @param $imageType
     * @param $sourceImage
     * @return resource
     * @throws Exception
     */
    protected static function getImageResourceFromImageTypeAndFile($imageType, $sourceImage)
    {
        // for now just support jpe?g and png.
        switch ($imageType)
        {
            case 'image/jpeg':
                $sourceImageResource = imagecreatefromjpeg($sourceImage);
                break;
            case 'image/png':
                $sourceImageResource = imagecreatefrompng($sourceImage);
                break;
            default:
                throw new Exception("Invalid image type for: $sourceImage");
        }
        return $sourceImageResource;
    }

    /**
     * Resolve thumbnail's intelligent height and width while maintaining aspect ratio, so we can
     * have a background to cover the rest, if needed.
     * @param $sourceWidth
     * @param $sourceHeight
     * @param $targetWidth
     * @param $targetHeight
     * @return array
     */
    protected static function resolveNewWidthAndHeight($sourceWidth, $sourceHeight, $targetWidth, $targetHeight)
    {
        $xRatio = $targetWidth / $sourceWidth;
        $yRatio = $targetHeight / $sourceHeight;

        // source width and height are both less than what we desire. We would keep them as is and use
        // padding to paint the remainder of height and width
        if (($sourceWidth <= $targetWidth) && ($sourceHeight <= $targetHeight)) {
            $calculatedTargetWidth = $sourceWidth;
            $calculatedTargetHeight = $sourceHeight;
        } elseif (($xRatio * $sourceHeight) < $targetHeight) {
            // height of the provided image is less than what we desire.
            $calculatedTargetHeight = ceil($xRatio * $sourceHeight);
            $calculatedTargetWidth = $targetWidth;
        } else {
            // width of the provided image is less than what we desire.
            $calculatedTargetWidth = ceil($yRatio * $sourceWidth);
            $calculatedTargetHeight = $targetHeight;
        }
        return array($calculatedTargetWidth, $calculatedTargetHeight);
    }
}

<?php

if (!isset($argv[1])) {
    throw new Exception('Missing path to team images folder');
}

$imagesFolder = $argv[1];

echo 'Creating random image for the folder: '.$imagesFolder.PHP_EOL;

$files = scandir($imagesFolder);

if (!$files) {
    throw new Exception('Missing files in team images folder');
}

$inputImageSize = 300;
$images = [];
foreach ($files as $file) {
    $path = $imagesFolder.'/'.$file;
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if ($ext !== 'jpg' && $ext !== 'jpeg' && $ext !== 'png') continue;

    echo 'Loading '.$file.PHP_EOL;
    $imageSize = getimagesize($path);
    if ($imageSize[0] !== $imageSize[1]) {
        throw new Exception($file.' is not a square image. Please crop it.');
    }

    if ($imageSize[0] !== $inputImageSize) {
        throw new Exception('Automatic resizing to the smallest image size is not yet done, so you need to resize '.$file.' to '.$inputImageSize.'x'.$inputImageSize.'px.');
    }

    $ressource = null;
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $ressource = imagecreatefromjpeg($path);
    } elseif ($ext === 'png') {
        $ressource = imageCreateFromPNG($path);
    }

    if ($ressource !== null) {
        $images[] = [
            'ressource' => $ressource,
            'occurences' => 0,
        ];
    }
}

$nbInputImages = count($images);

if (!$nbInputImages) {
    throw new Exception('No images loaded');
}

echo 'Computing...'.PHP_EOL;

$outputImageWidth = 4096;
$outputImageHeight = 2304;

$resultImage = imagecreatetruecolor($outputImageWidth, $outputImageHeight);

$nbWidth = ceil($outputImageWidth / $inputImageSize);
$nbHeight = ceil($outputImageHeight / $inputImageSize);

$nbOccurencesPerImage = $nbWidth * $nbHeight / $nbInputImages;
$lastImageIndex = -1;

function getRandomImage($images, $nbInputImages, $nbOccurencesPerImage, $lastImageIndex) {
    $randomImageIndex = mt_rand(0, $nbInputImages - 1);
    $randomImage = $images[$randomImageIndex];

    if ($randomImage['occurences'] > $nbOccurencesPerImage || $lastImageIndex == $randomImageIndex) {
        return getRandomImage($images, $nbInputImages, $nbOccurencesPerImage, $lastImageIndex);
    }

    return [$randomImage['ressource'], $randomImageIndex];
}


for($j = 0; $j < $nbHeight; $j++) {
    for ($i = 0; $i < $nbWidth; $i++) {
        $x = $i*$inputImageSize;
        $y = $j*$inputImageSize;

        [$randomImageResource, $randomImageIndex] = getRandomImage($images, $nbInputImages, $nbOccurencesPerImage, $lastImageIndex);
        imagecopymerge($resultImage, $randomImageResource, $x, $y, 0, 0, $inputImageSize, $inputImageSize, 100);
        $images[$randomImageIndex]['occurences']++;
        $lastImageIndex = $randomImageIndex;
    }
}

echo 'Saving...'.PHP_EOL;

imagepng($resultImage, './banner.png');

foreach ($images as $image) {
    ImageDestroy($image['ressource']);
}
ImageDestroy($resultImage);

echo 'Done !';

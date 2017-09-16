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

$inputImageSize = 256;
$images = [];
foreach ($files as $file) {
    $path = $imagesFolder.'/'.$file;
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if ($ext !== 'jpg' && $ext !== 'jpeg' && $ext !== 'png') continue;

    echo 'Loading '.$file;
    $imageSize = getimagesize($path);
    if ($imageSize[0] !== $imageSize[1]) {
        throw new Exception($file.' is not a square image. Please crop it.');
    }

    if ($imageSize[0] !== $inputImageSize) {
        echo ' (resized)';
    }

    $resource = null;
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $resource = imagecreatefromjpeg($path);
    } elseif ($ext === 'png') {
        $resource = imageCreateFromPNG($path);
    }

    if ($resource === null) {
        throw new Exception('Error loading '.$file);
    }

    $images[] = [
        'resource' => $resource,
        'size' => $imageSize[0],
        'occurences' => 0,
    ];

    echo PHP_EOL;
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

$map = [[]];

function getRandomImage($images, $nbInputImages, $nbOccurencesPerImage, $map, $i, $j) {
    $randomImageIndex = mt_rand(0, $nbInputImages - 1);
    $randomImage = $images[$randomImageIndex];

    if ($randomImage['occurences'] > $nbOccurencesPerImage) {
        return getRandomImage($images, $nbInputImages, $nbOccurencesPerImage, $map, $i, $j);
    }

    // check for horizontal repeating
    for ($ii = 0; $ii < $i; $ii++) {
        if ($map[$ii][$j] === $randomImageIndex) {
            return getRandomImage($images, $nbInputImages, $nbOccurencesPerImage, $map, $i, $j);
        }
    }

    // check for vertical repeating
    for ($jj = 0; $jj < $j; $jj++) {
        if ($map[$i][$jj] === $randomImageIndex) {
            return getRandomImage($images, $nbInputImages, $nbOccurencesPerImage, $map, $i, $j);
        }
    }

    return [$randomImage, $randomImageIndex];
}

for($j = 0; $j < $nbHeight; $j++) {
    for ($i = 0; $i < $nbWidth; $i++) {
        $x = $i*$inputImageSize;
        $y = $j*$inputImageSize;

        [$randomImage, $randomImageIndex] = getRandomImage($images, $nbInputImages, $nbOccurencesPerImage, $map, $i, $j);
        imagecopyresized($resultImage, $randomImage['resource'], $x, $y, 0, 0, $inputImageSize, $inputImageSize, $randomImage['size'], $randomImage['size']);
        $images[$randomImageIndex]['occurences']++;
        $map[$i][$j] = $randomImageIndex;
    }
}

echo 'Saving...'.PHP_EOL;

imagepng($resultImage, './banner.png');

foreach ($images as $image) {
    ImageDestroy($image['resource']);
}
ImageDestroy($resultImage);

echo 'Done !';

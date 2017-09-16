# Automatic banner creation tool

Generate a banner image from multiple square images. See [example output](./example.jpg).

## Requirements

- PHP 7.1+
- Square images (JPG or PNG), minimum 256x256px is recommended

## Usage

`php index.php path/to/image/folder`

Output will be in `./banner.png`. It's recommended to use [guetzli](https://github.com/google/guetzli) to compress the image.

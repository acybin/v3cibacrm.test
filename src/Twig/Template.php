<?php


namespace App\Twig;

use framework\tools;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Template
{
    static private $twig_instance;

    public static function getTemplate()
    {
        if (!self::$twig_instance) {
            $filesystemLoader = new FilesystemLoader($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'templates');

            $twig_config = [
                'debug' => true
            ];

            //$twig_config['cache'] = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'twig';
            //$twig_config['debug'] = false;

            self::$twig_instance = new Environment($filesystemLoader, $twig_config);

            self::addFilters(self::$twig_instance);
            self::addFunctions(self::$twig_instance);

            self::$twig_instance->addExtension(new DebugExtension);
        }

        return self::$twig_instance;
    }

    private static function addFilters($instance)
    {
        $instance->addFilter(new TwigFilter('format_number', function ($v) {
            return tools::format_number($v);
        }));

        $instance->addFilter(new TwigFilter('format_phone', function ($v) {
            return tools::format_phone($v);
        }));

        $instance->addFilter(new TwigFilter('cut_phone', function ($v) {
            return tools::cut_phone($v);
        }));

        $instance->addFilter(new TwigFilter('encode', function ($v) {
            return tools::encode($v);
        }));

        $instance->addFilter(new TwigFilter('decode', function ($v) {
            return tools::decode($v);
        }));

        $instance->addFilter(new TwigFilter('format_price', function ($v) {
            return tools::format_price($v);
        }));

        $instance->addFilter(new TwigFilter('format_date', function ($v, $short = false, $rus = true) {
            return tools::format_date($v, $short, $rus);
        }));

        $instance->addFilter(new TwigFilter('declOfNum', function ($v, $arg) {
            return tools::declOfNum($v, $arg);
        }));

        $instance->addFilter(new TwigFilter('count_values', function ($v) {
            return array_count_values($v);
        }));

        $instance->addFilter(new TwigFilter('status_color', function ($v, $arg) {
            return tools::getColor($v, $arg);
        }));

        $instance->addFilter(new TwigFilter('get_q', function ($v) {
            return tools::encode($v);
        }));
    }

    private static function addFunctions($instance)
    {

        $instance->addFunction(new TwigFunction('serveAssets', function ($v) {
            $images_type = ['1' => 'IMAGETYPE_GIF', '2' => 'IMAGETYPE_JPEG', '3' => 'IMAGETYPE_PNG'];

            $v = ('/' === $v[0]) ? $v : DIRECTORY_SEPARATOR . $v;

            $file_out = $_SERVER['DOCUMENT_ROOT'] . $v;
            if (file_exists($file_out)) {
                if (array_key_exists(exif_imagetype($file_out), $images_type)) {
                    $file_type = strtolower(str_replace('IMAGETYPE_', '', $images_type[exif_imagetype($file_out)]));
                    return self::base64_encode_image($file_out, $file_type);
                }
            }

            return false;
        }));

    }

    private static function base64_encode_image ($filename,$filetype) {
        if ($filename) {
            $imgbinary = fread(fopen($filename, "r"), filesize($filename));
            return 'data:image/' . $filetype . ';base64,' . base64_encode($imgbinary);
        }
    }
}
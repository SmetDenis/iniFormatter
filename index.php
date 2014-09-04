<?php
/**
 * IniFormater is tool for nice & sexy & mi-mi-mi ini format
 *
 * @package    iniFormater
 * @author     SmetDenis <admin@jbzoo.com>
 * @copyright  Copyright (c) 2014 iniFormater
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 * @link       https://github.com/smetdenis/iniFormatter
 */


$root = dirname(__FILE__);
require_once $root . '/iniFormatter.php';


// init
$formatter = new iniFormatter(array(
    'root_path'   => $root,
    'output_path' => $root . '/output',
    'header'      => array(
        'Built by iniFormatter; SmetDenis / admin@jbzoo.com',
        '',
        'Note : All ini files need to be saved as UTF-8 - No BOM',
        'Common boolean values',
        'Note: YES, NO, TRUE, FALSE are reserved words in INI format',
    ),
    'defines'     => array(
        '_QQ_' => '"\""', // Joomla CMS hack
    ),
));


// some files
$ruFile = './input/ru-RU.ini';
$enFile = './input/en-GB.ini';
$uaFile = './input/uk-UA.ini';


// to nice format
$ruLines = $formatter->format($ruFile);
$enLines = $formatter->format($enFile);
$uaLines = $formatter->format($uaFile);


// get diff and save to it file
echo '<h3>Diff RU & EN</h3>';
$diff = $formatter->diff($ruFile, $enFile);
file_put_contents($root . '/output/diff-ru-en.ini', $diff);
$formatter->dump($diff);


// get diff and save to it file
echo '<h3>Diff RU & UA</h3>';
$diff = $formatter->diff($ruFile, $uaFile);
file_put_contents($root . '/output/diff-ru-ua.ini', $diff);
$formatter->dump($diff);


// save empty template for not translated lines
echo '<h3>Not exists lines in EN</h3>';
$noExists   = array_diff(array_keys($ruLines), array_keys($enLines));
$tmplExists = $formatter->getNotExists($noExists, $ruFile);
file_put_contents($root . '/output/not-exists-en.ini', $tmplExists);
$formatter->dump($tmplExists);


// save empty template for not translated lines
echo '<h3>Not exists lines in UA</h3>';
$noExists   = array_diff(array_keys($ruLines), array_keys($uaLines));
$tmplExists = $formatter->getNotExists($noExists, $ruFile);
file_put_contents($root . '/output/not-exists-ua.ini', $tmplExists);
$formatter->dump($tmplExists);

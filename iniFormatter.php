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


!defined('DIRECTORY_SEPARATOR') && define(DIRECTORY_SEPARATOR, '/');

/**
 * Class iniFormatter
 */
class iniFormatter
{
    /**
     * Default header
     * @var array
     */
    protected $_copyrights = array(
        'Built by iniFormater; SmetDenis / admin@jbzoo.com',
        '',
        'Note : All ini files need to be saved as UTF-8 - No BOM',
        'Common boolean values',
        'Note: YES, NO, TRUE, FALSE are reserved words in INI format',
    );

    protected $_defines = array(
        '_QQ_' => '"\""', // Joomla CMS hack
    );

    /**
     * @var string
     */
    protected $_rootPath = '';
    protected $_outputPath = '';
    protected $_lineEnd = "\n";

    /**
     * @param array $config
     */
    function __construct(array $config = array())
    {
        if (isset($config['copyrights'])) {
            $this->_copyrights = $config['copyrights'];
        }

        $this->_rootPath = dirname(__FILE__);
        if (isset($config['root_path'])) {
            $this->_rootPath = $config['root_path'];
        }

        $this->_outputPath = dirname(__FILE__);
        if (isset($config['output_path'])) {
            $this->_outputPath = $config['output_path'];
        }

        if (isset($config['defines']) && is_array($config['defines'])) {
            $this->_defines = array_merge($this->_defines, $config['defines']);
        }

        if (isset($config['line_end'])) {
            $this->_lineEnd = $config['line_end'];
        }
    }

    /**
     * @param $filename
     * @return array
     * @throws Exception
     */
    public function format($filename)
    {
        $filename = $this->_path($this->_rootPath . '/' . $filename);
        if (!file_exists($filename)) {
            $this->_error('File not exists: "' . $filename . '"');
        }

        // format
        $oldLines   = $this->_parseIni($filename);
        $groupLines = $this->_group($oldLines);
        $newIni     = $this->_writeIniFile($groupLines);

        // save result
        $newFilename = pathinfo($filename, PATHINFO_BASENAME);
        $newFile     = $this->_path($this->_outputPath . '/' . $newFilename);
        file_put_contents($newFile, $newIni);

        $newLines = $this->_parseIni($newFile);

        $oldCount = count($oldLines);
        $newCount = count($newLines);
        if ($oldCount != $newCount) {
            $this->_error("CRC error: $oldCount !== $newCount");
        }

        $this->_log(__METHOD__ . ' = ' . $newCount . '; file "' . $newFilename . '"');

        return $newLines;
    }

    /**
     * @param $diff
     * @param $file
     * @return string
     */
    public function getNotExists($diff, $file)
    {
        $lines = $this->_parseIni($file);

        $result = array();
        foreach ($diff as $key) {
            $result[] = $key . ' = "' . $lines[$key] . '"';
        }

        $this->_log(__METHOD__ . ' = ' . count($result));

        return implode($this->_lineEnd, $result);
    }

    /**
     * @param string $file1
     * @param string $file2
     * @return string|array
     */
    public function diff($file1, $file2)
    {
        $lines1 = $this->_parseIni($file1);
        $lines2 = $this->_parseIni($file2);

        $diff = array_diff(array_keys($lines1), array_keys($lines2));
        sort($diff);

        $this->_log(__METHOD__ . ' = ' . count($diff));

        $result = '';
        foreach ($diff as $line) {
            $result .= $line . " = \"\"" . $this->_lineEnd;
        }

        return $result;
    }

    /**
     * @param $var
     */
    public function dump($var)
    {
        if (class_exists('jbdump')) {
            jbdump($var, 0);
        } else {
            echo '<pre>' . print_r($var, true) . '</pre>';
        }

    }

    /**
     * @param $file
     * @return array
     */
    protected function _parseIni($file)
    {
        $contents = file_get_contents($file);

        if (empty($contents)) {
            $this->_error('File "' . $contents . '" is empty');
        }

        $contents = str_replace(
            array_keys($this->_defines),
            array_values($this->_defines),
            $contents
        );

        $result = parse_ini_string($contents);
        if (empty($result)) {
            $this->_error('File "' . $file . '" not parsed');
        }

        if (defined('SORT_NATURAL')) {
            ksort($result, SORT_NATURAL);
        } else {
            uksort($result, function ($a, $b) {
                $aNum = (float)$a;
                $bNum = (float)$b;

                if ($aNum == $bNum) {
                    return strcmp($a, $b);
                }

                return ($aNum < $bNum) ? -1 : 1;
            });
        }

        return $result;
    }

    /**
     * @param array $lines
     * @return array
     */
    protected function _group(array $lines)
    {
        $result = array('___' => array());
        foreach ($lines as $key => $string) {

            $keys   = explode('_', $key);
            $tmpRes =& $result;

            if (count($keys) > 2) {

                $keys = array_slice($keys, 0, 2);
                foreach ($keys as $i => $v) {
                    if (!isset($tmpRes[$v])) {
                        $tmpRes[$v] = array();
                    }

                    $tmpRes =& $tmpRes[$v];
                    if ($i == 1) {
                        $tmpRes[$key] = $string;
                    }
                }

            } else {
                $result['___'][$key] = $string;
            }

        }

        return $result;
    }

    /**
     * Clean path
     * @param $path
     * @param string $ds
     * @return mixed|string
     */
    protected function _path($path, $ds = DIRECTORY_SEPARATOR)
    {
        $path = trim($path);
        $path = rtrim($path, '/');
        $path = rtrim($path, '\\');

        if (($ds == '\\') && ($path[0] == '\\') && ($path[1] == '\\')) {
            $path = "\\" . preg_replace('#[/\\\\]+#', $ds, $path);
        } else {
            $path = preg_replace('#[/\\\\]+#', $ds, $path);
        }

        return $path;
    }

    /**
     * Show error
     * @param string $message
     * @throws Exception
     */
    protected function _error($message)
    {
        throw new Exception('iniFormatter:' . $message);
    }

    /**
     * @param $message
     */
    protected function _log($message)
    {
        echo $message . '<br />';
    }

    /**
     * @param $value
     * @return string
     */
    protected function _prepareValue($value)
    {
        $value = trim(str_replace(
            array_values($this->_defines),
            array_keys($this->_defines),
            $value
        ));

        return $value;
    }

    /**
     * @param array $options
     * @return string
     */
    protected function _writeIniFile(array $options)
    {
        $result = array();

        foreach ($this->_copyrights as $value) {
            $result[] = '; ' . $value;
        }

        $result[] = $this->_lineEnd;

        foreach ($options as $key => $values) {
            if ($key == '___') {
                $result[] = $this->_addComment('Others');
                $result   = array_merge($result, $this->_addBlock($values));
                $result[] = $this->_lineEnd;

            } else {
                $result[] = $this->_addComment($key, true);
                foreach ($values as $realKeyInner => $valueInner) {
                    $result[] = $this->_addComment($realKeyInner);
                    $result   = array_merge($result, $this->_addBlock($valueInner));
                    $result[] = $this->_lineEnd;
                }
            }
        }

        $result[] = $this->_lineEnd;

        return implode($this->_lineEnd, $result);
    }

    /**
     * @param string $iniKey
     * @param array $rawValues
     * @return string
     */
    protected function _addLine($iniKey, $rawValues)
    {
        $line = '';
        if (is_array($rawValues)) {

            foreach ($rawValues as $key => $value) {
                $value = $this->_prepareValue($value);
                $line .= "{$iniKey}[$key] = \"$value\"";
            }

        } else {
            $rawValues = $this->_prepareValue($rawValues);
            $line .= "$iniKey = \"$rawValues\"";
        }

        return $line;
    }

    /**
     * @param $key
     * @param bool $isBig
     * @return string
     */
    protected function _addComment($key, $isBig = true)
    {
        $key = ucfirst($this->_strtolower($key));

        if ($isBig) {
            $res = str_repeat(';', 40) . '     ' . $key . '    ' . str_repeat(';', 100);
            $res = substr($res, 0, 120);
            return $res;
        }

        return '; ' . $key;
    }

    /**
     * @param $list
     * @return array
     */
    protected function _addBlock($list)
    {
        $keys = array_keys($list);
        $max  = 0;

        foreach ($keys as $item) {
            $length = $this->_strlen($item);
            if ($max < $length) {
                $max = $length;
            }
        }

        $lines = array();
        foreach ($list as $key => $item) {
            $kLen    = $this->_strlen($key);
            $lines[] = $this->_addLine($key . str_repeat(' ', $max - $kLen), $item);
        }

        return $lines;
    }

    /**
     * @param $string
     * @return int
     */
    protected function _strlen($string)
    {
        if (function_exists('mb_strlen')) {
            $length = mb_strlen($string);
        } else {
            $length = strlen($string);
        }

        return $length;
    }

    /**
     * @param $string
     * @return string
     */
    protected function _strtolower($string)
    {
        if (function_exists('mb_strtolower')) {
            $lower = mb_strtolower($string);
        } else {
            $lower = strtolower($string);
        }

        return $lower;
    }

}

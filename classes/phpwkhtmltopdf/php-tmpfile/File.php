<?php
namespace mikehaertl\tmp;

/**
 * File
 *
 * A convenience class for temporary files.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @license http://www.opensource.org/licenses/MIT
 */
class File
{
    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    /**
     * @var bool whether to delete the tmp file when it's no longer referenced
     * or when the request ends.  Default is `true`.
     */
    public $delete = true;

    /**
     * @var array the list of static default headers to send when `send()` is
     * called as key/value pairs.
     */
    public static $defaultHeaders = array(
        'Pragma' => 'public',
        'Expires' => 0,
        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        'Content-Transfer-Encoding' => 'binary',
    );

    /**
     * @var string the name of this file
     */
    protected $_fileName;

    /**
     * Constructor
     *
     * @param string $content the tmp file content
     * @param string|null $suffix the optional suffix for the tmp file
     * @param string|null $prefix the optional prefix for the tmp file. If null
     * 'php_tmpfile_' is used.
     * @param string|null $directory directory where the file should be
     * created. Autodetected if not provided.
     */
    public function __construct($content, $suffix = null, $prefix = null, $directory = null)
    {
        if ($directory === null) {
            $directory = self::getTempDir();
        }

        if ($prefix === null) {
            $prefix = 'php_tmpfile_';
        }

        $this->_fileName = tempnam($directory,$prefix);
        if ($suffix !== null) {
            $newName = $this->_fileName . $suffix;
            rename($this->_fileName, $newName);
            $this->_fileName = $newName;
        }
        file_put_contents($this->_fileName, $content);
    }

    /**
     * Delete tmp file on shutdown if `$delete` is `true`
     */
    public function __destruct()
    {
        if ($this->delete && file_exists($this->_fileName)) {
            unlink($this->_fileName);
        }
    }

    /**
     * Send tmp file to client, either inline or as download
     *
     * @param string|null $filename the filename to send. If empty, the file is
     * streamed inline.
     * @param string|null $contentType the Content-Type header to send. If
     * `null` the type is auto-detected and if that fails
     * 'application/octet-stream' is used.
     * @param bool $inline whether to force inline display of the file, even if
     * filename is present.
     * @param array $headers a list of additional HTTP headers to send in the
     * response as an array. The array keys are the header names like
     * 'Cache-Control' and the array values the header value strings to send.
     * Each array value can also be another array of strings if the same header
     * should be sent multiple times. This can also be used to override
     * automatically created headers like 'Expires' or 'Content-Length'. To suppress
     * automatically created headers, `false` can also be used as header value.
     */
    public function send($filename = null, $contentType = null, $inline = false, $headers = array())
    {
        $headers = array_merge(self::$defaultHeaders, $headers);

        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType;
        } elseif (!isset($headers['Content-Type'])) {
            $contentType = @mime_content_type($this->_filename);
            if ($contentType === false) {
                $contentType = self::DEFAULT_CONTENT_TYPE;
            }
            $headers['Content-Type'] = $contentType;
        }

        if (!isset($headers['Content-Length'])) {
            // #11 Undefined index: HTTP_USER_AGENT
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

            // #84: Content-Length leads to "network connection was lost" on iOS
            $isIOS = preg_match('/i(phone|pad|pod)/i', $userAgent);
            if (!$isIOS) {
                $headers['Content-Length'] = filesize($this->_fileName);
            }
        }

        if (($filename !== null || $inline) && !isset($headers['Content-Disposition'])) {
            $disposition = $inline ? 'inline' : 'attachment';
            $encodedFilename = rawurlencode($filename);
            $headers['Content-Disposition'] = "$disposition; " .
                "filename=\"$filename\"; " .
                "filename*=UTF-8''$encodedFilename";
        }

        $this->sendHeaders($headers);
        readfile($this->_fileName);
    }

    /**
     * @param string $name the name to save the file as
     * @return bool whether the file could be saved
     */
    public function saveAs($name)
    {
        return copy($this->_fileName, $name);
    }

    /**
     * @return string the full file name
     */
    public function getFileName()
    {
        return $this->_fileName;
    }

    /**
     * @return string the path to the temp directory
     */
    public static function getTempDir()
    {
        if (function_exists('sys_get_temp_dir')) {
            return sys_get_temp_dir();
        } elseif (
            ($tmp = getenv('TMP')) ||
            ($tmp = getenv('TEMP')) ||
            ($tmp = getenv('TMPDIR'))
        ) {
            return realpath($tmp);
        } else {
            return '/tmp';
        }
    }

    /**
     * @return string the full file name
     */
    public function __toString()
    {
        return $this->_fileName;
    }

    /**
     * Send the given list of headers
     *
     * @param array $headers the list of headers to send as key/value pairs.
     * Value can either be a string or an array of strings to send the same
     * header multiple times.
     */
    protected function sendHeaders($headers)
    {
        foreach ($headers as $name => $value) {
            if ($value === false) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $v) {
                    header("$name: $v");
                }
            } else {
                header("$name: $value");
            }
        }
    }
}

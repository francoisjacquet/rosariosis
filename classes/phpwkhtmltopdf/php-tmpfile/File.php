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
     */
    public function send($filename = null, $contentType = null, $inline = false)
    {
        if ($contentType === null) {
            $contentType = @mime_content_type($this->_filename);
            if ($contentType === false) {
                $contentType = self::DEFAULT_CONTENT_TYPE;
            }
        }
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: ' . $contentType);
        header('Content-Transfer-Encoding: binary');

        //#11 Undefined index: HTTP_USER_AGENT
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        // #84: Content-Length leads to "network connection was lost" on iOS
        $isIOS = preg_match('/i(phone|pad|pod)/i', $userAgent);
        if (!$isIOS) {
            header('Content-Length: ' . filesize($this->_fileName));
        }

        if ($filename !== null || $inline) {
            $disposition = $inline ? 'inline' : 'attachment';
            $encodedFilename = rawurlencode($filename);
            header(
                "Content-Disposition: $disposition; " .
                "filename=\"$filename\"; " .
                "filename*=UTF-8''$encodedFilename"
            );
        }

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
}

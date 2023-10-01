<?php
namespace mikehaertl\shellcommand;

/**
 * Command
 *
 * This class represents a shell command.
 *
 * Its meant for exuting a single command and capturing stdout and stderr.
 *
 * Example:
 *
 * ```
 * $command = new Command('/usr/local/bin/mycommand -a -b');
 * $command->addArg('--name=', "d'Artagnan");
 * if ($command->execute()) {
 *     echo $command->getOutput();
 * } else {
 *     echo $command->getError();
 *     $exitCode = $command->getExitCode();
 * }
 * ```
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @license http://www.opensource.org/licenses/MIT
 */
class Command
{
    /**
     * @var bool whether to escape any argument passed through `addArg()`.
     * Default is `true`.
     */
    public $escapeArgs = true;

    /**
     * @var bool whether to escape the command passed to `setCommand()` or the
     * constructor.  This is only useful if `$escapeArgs` is `false`. Default
     * is `false`.
     */
    public $escapeCommand = false;

    /**
     * @var bool whether to use `exec()` instead of `proc_open()`. This can be
     * used on Windows system to workaround some quirks there. Note, that any
     * errors from your command will be output directly to the PHP output
     * stream. `getStdErr()` will also not work anymore and thus you also won't
     * get the error output from `getError()` in this case. You also can't pass
     * any environment variables to the command if this is enabled. Default is
     * `false`.
     */
    public $useExec = false;

    /**
     * @var bool whether to capture stderr (2>&1) when `useExec` is true. This
     * will try to redirect the stderr to stdout and provide the complete
     * output of both in `getStdErr()` and `getError()`.  Default is `true`.
     */
    public $captureStdErr = true;

    /**
     * @var string|null the initial working dir for `proc_open()`. Default is
     * `null` for current PHP working dir.
     */
    public $procCwd;

    /**
     * @var array|null an array with environment variables to pass to
     * `proc_open()`. Default is `null` for none.
     */
    public $procEnv;

    /**
     * @var array|null an array of other_options for `proc_open()`. Default is
     * `null` for none.
     */
    public $procOptions;

    /**
     * @var bool|null whether to set the stdin/stdout/stderr streams to
     * non-blocking mode when `proc_open()` is used. This allows to have huge
     * inputs/outputs without making the process hang. The default is `null`
     * which will enable the feature on Non-Windows systems. Set it to `true`
     * or `false` to manually enable/disable it. It does not work on Windows.
     */
    public $nonBlockingMode;

    /**
     * @var int the time in seconds after which a command should be terminated.
     * This only works in non-blocking mode. Default is `null` which means the
     * process is never terminated.
     */
    public $timeout;

    /**
     * @var null|string the locale to temporarily set before calling
     * `escapeshellargs()`. Default is `null` for none.
     */
    public $locale;

    /**
     * @var null|string|resource to pipe to standard input
     */
    protected $_stdIn;

    /**
     * @var string the command to execute
     */
    protected $_command;

    /**
     * @var array the list of command arguments
     */
    protected $_args = array();

    /**
     * @var string the stdout output
     */
    protected $_stdOut = '';

    /**
     * @var string the stderr output
     */
    protected $_stdErr = '';

    /**
     * @var int the exit code
     */
    protected $_exitCode;

    /**
     * @var string the error message
     */
    protected $_error = '';

    /**
     * @var bool whether the command was successfully executed
     */
    protected $_executed = false;

    /**
     * @param string|array $options either a command string or an options array
     * @see setOptions
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif (is_string($options)) {
            $this->setCommand($options);
        }
    }

    /**
     * @param array $options array of name => value options (i.e. public
     * properties) that should be applied to this object. You can also pass
     * options that use a setter, e.g. you can pass a `fileName` option which
     * will be passed to `setFileName()`.
     * @throws \Exception on unknown option keys
     * @return static for method chaining
     */
    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } else {
                $method = 'set'.ucfirst($key);
                if (method_exists($this, $method)) {
                    call_user_func(array($this,$method), $value);
                } else {
                    throw new \Exception("Unknown configuration option '$key'");
                }
            }
        }
        return $this;
    }

    /**
     * @param string $command the command or full command string to execute,
     * like 'gzip' or 'gzip -d'.  You can still call addArg() to add more
     * arguments to the command. If `$escapeCommand` was set to true, the command
     * gets escaped with `escapeshellcmd()`.
     * @return static for method chaining
     */
    public function setCommand($command)
    {
        if ($this->escapeCommand) {
            $command = escapeshellcmd($command);
        }
        if ($this->getIsWindows()) {
            // Make sure to switch to correct drive like "E:" first if we have
            // a full path in command
            if (isset($command[1]) && $command[1] === ':') {
                $position = 1;
                // Could be a quoted absolute path because of spaces.
                // i.e. "C:\Program Files (x86)\file.exe"
            } elseif (isset($command[2]) && $command[2] === ':') {
                $position = 2;
            } else {
                $position = false;
            }

            // Absolute path. If it's a relative path, let it slide.
            if ($position) {
                $command = sprintf(
                    $command[$position - 1] . ': && cd %s && %s',
                    escapeshellarg(dirname($command)),
                    escapeshellarg(basename($command))
                );
            }
        }
        $this->_command = $command;
        return $this;
    }

    /**
     * @param string|resource $stdIn If set, the string will be piped to the
     * command via standard input. This enables the same functionality as
     * piping on the command line. It can also be a resource like a file
     * handle or a stream in which case its content will be piped into the
     * command like an input redirection.
     * @return static for method chaining
     */
    public function setStdIn($stdIn) {
        $this->_stdIn = $stdIn;
        return $this;
    }

    /**
     * @return string|null the command that was set through `setCommand()` or
     * passed to the constructor. `null` if none.
     */
    public function getCommand()
    {
        return $this->_command;
    }

    /**
     * @return string|bool the full command string to execute. If no command
     * was set with `setCommand()` or passed to the constructor it will return
     * `false`.
     */
    public function getExecCommand()
    {
        $command = $this->getCommand();
        if (!$command) {
            $this->_error = 'Could not locate any executable command';
            return false;
        }

        $args = $this->getArgs();
        return $args ? $command.' '.$args : $command;
    }

    /**
     * @param string $args the command arguments as string like `'--arg1=value1
     * --arg2=value2'`. Note that this string will not get escaped. This will
     * overwrite the args added with `addArgs()`.
     * @return static for method chaining
     */
    public function setArgs($args)
    {
        $this->_args = array($args);
        return $this;
    }

    /**
     * @return string the command args that where set with `setArgs()` or added
     * with `addArg()` separated by spaces.
     */
    public function getArgs()
    {
        return implode(' ', $this->_args);
    }

    /**
     * @param string $key the argument key to add e.g. `--feature` or
     * `--name=`. If the key does not end with `=`, the (optional) $value will
     * be separated by a space. The key will get escaped if `$escapeArgs` is `true`.
     * @param string|array|null $value the optional argument value which will
     * get escaped if $escapeArgs is true.  An array can be passed to add more
     * than one value for a key, e.g.
     * `addArg('--exclude', array('val1','val2'))`
     * which will create the option
     * `'--exclude' 'val1' 'val2'`.
     * @param bool|null $escape if set, this overrides the `$escapeArgs` setting
     * and enforces escaping/no escaping of keys and values
     * @return static for method chaining
     */
    public function addArg($key, $value = null, $escape = null)
    {
        $doEscape = $escape !== null ? $escape : $this->escapeArgs;
        $useLocale = $doEscape && $this->locale !== null;

        if ($useLocale) {
            $locale = setlocale(LC_CTYPE, 0);   // Returns current locale setting
            setlocale(LC_CTYPE, $this->locale);
        }
        if ($value === null) {
            $this->_args[] = $doEscape ? escapeshellarg($key) : $key;
        } else {
            if (substr($key, -1) === '=') {
                $separator = '=';
                $argKey = substr($key, 0, -1);
            } else {
                $separator = ' ';
                $argKey = $key;
            }
            $argKey = $doEscape ? escapeshellarg($argKey) : $argKey;

            if (is_array($value)) {
                $params = array();
                foreach ($value as $v) {
                    $params[] = $doEscape ? escapeshellarg($v) : $v;
                }
                $this->_args[] = $argKey . $separator . implode(' ', $params);
            } else {
                $this->_args[] = $argKey . $separator .
                    ($doEscape ? escapeshellarg($value) : $value);
            }
        }
        if ($useLocale) {
            setlocale(LC_CTYPE, $locale);
        }

        return $this;
    }

    /**
     * @param bool $trim whether to `trim()` the return value. The default is `true`.
     * @param string $characters the list of characters to trim. The default
     * is ` \t\n\r\0\v\f`.
     * @return string the command output (stdout). Empty if none.
     */
    public function getOutput($trim = true, $characters = " \t\n\r\0\v\f")
    {
        return $trim ? trim($this->_stdOut, $characters) : $this->_stdOut;
    }

    /**
     * @param bool $trim whether to `trim()` the return value. The default is `true`.
     * @param string $characters the list of characters to trim. The default
     * is ` \t\n\r\0\v\f`.
     * @return string the error message, either stderr or an internal message.
     * Empty string if none.
     */
    public function getError($trim = true, $characters = " \t\n\r\0\v\f")
    {
        return $trim ? trim($this->_error, $characters) : $this->_error;
    }

    /**
     * @param bool $trim whether to `trim()` the return value. The default is `true`.
     * @param string $characters the list of characters to trim. The default
     * is ` \t\n\r\0\v\f`.
     * @return string the stderr output. Empty if none.
     */
    public function getStdErr($trim = true, $characters = " \t\n\r\0\v\f")
    {
        return $trim ? trim($this->_stdErr, $characters) : $this->_stdErr;
    }

    /**
     * @return int|null the exit code or null if command was not executed yet
     */
    public function getExitCode()
    {
        return $this->_exitCode;
    }

    /**
     * @return string whether the command was successfully executed
     */
    public function getExecuted()
    {
        return $this->_executed;
    }

    /**
     * Execute the command
     *
     * @return bool whether execution was successful. If `false`, error details
     * can be obtained from `getError()`, `getStdErr()` and `getExitCode()`.
     */
    public function execute()
    {
        $command = $this->getExecCommand();

        if (!$command) {
            return false;
        }

        if ($this->useExec) {
            $execCommand = $this->captureStdErr ? "$command 2>&1" : $command;
            exec($execCommand, $output, $this->_exitCode);
            $this->_stdOut = implode("\n", $output);
            if ($this->_exitCode !== 0) {
                $this->_stdErr = $this->_stdOut;
                $this->_error = empty($this->_stdErr) ? 'Command failed' : $this->_stdErr;
                return false;
            }
        } else {
            $isInputStream = $this->_stdIn !== null &&
                is_resource($this->_stdIn) &&
                in_array(get_resource_type($this->_stdIn), array('file', 'stream'));
            $isInputString = is_string($this->_stdIn);
            $hasInput = $isInputStream || $isInputString;
            $hasTimeout = $this->timeout !== null && $this->timeout > 0;

            $descriptors = array(
                1   => array('pipe','w'),
                2   => array('pipe', $this->getIsWindows() ? 'a' : 'w'),
            );
            if ($hasInput) {
                $descriptors[0] = array('pipe', 'r');
            }


            // Issue #20 Set non-blocking mode to fix hanging processes
            $nonBlocking = $this->nonBlockingMode === null ?
                !$this->getIsWindows() : $this->nonBlockingMode;

            $startTime = $hasTimeout ? time() : 0;
            $process = proc_open($command, $descriptors, $pipes, $this->procCwd, $this->procEnv, $this->procOptions);

            if (is_resource($process)) {

                if ($nonBlocking) {
                    stream_set_blocking($pipes[1], false);
                    stream_set_blocking($pipes[2], false);
                    if ($hasInput) {
                        $writtenBytes = 0;
                        $isInputOpen = true;
                        stream_set_blocking($pipes[0], false);
                        if ($isInputStream) {
                            stream_set_blocking($this->_stdIn, false);
                        }
                    }

                    // Due to the non-blocking streams we now have to check in
                    // a loop if the process is still running. We also need to
                    // ensure that all the pipes are written/read alternately
                    // until there's nothing left to write/read.
                    $isRunning = true;
                    while ($isRunning) {
                        $status = proc_get_status($process);
                        $isRunning = $status['running'];

                        // We first write to stdIn if we have an input. For big
                        // inputs it will only write until the input buffer of
                        // the command is full (the command may now wait that
                        // we read the output buffers - see below). So we may
                        // have to continue writing in another cycle.
                        //
                        // After everything is written it's safe to close the
                        // input pipe.
                        if ($isRunning && $hasInput && $isInputOpen) {
                            if ($isInputStream) {
                                $written = stream_copy_to_stream($this->_stdIn, $pipes[0], 16 * 1024, $writtenBytes);
                                if ($written === false || $written === 0) {
                                    $isInputOpen = false;
                                    fclose($pipes[0]);
                                } else {
                                    $writtenBytes += $written;
                                }
                            } else {
                                if ($writtenBytes < strlen($this->_stdIn)) {
                                    $writtenBytes += fwrite($pipes[0], substr($this->_stdIn, $writtenBytes));
                                } else {
                                    $isInputOpen = false;
                                    fclose($pipes[0]);
                                }
                            }
                        }

                        // Read out the output buffers because if they are full
                        // the command may block execution. We do this even if
                        // $isRunning is `false`, because there could be output
                        // left in the buffers.
                        //
                        // The latter is only an assumption and needs to be
                        // verified - but it does not hurt either and works as
                        // expected.
                        //
                        while (($out = fgets($pipes[1])) !== false) {
                            $this->_stdOut .= $out;
                        }
                        while (($err = fgets($pipes[2])) !== false) {
                            $this->_stdErr .= $err;
                        }

                        $runTime = $hasTimeout ? time() - $startTime : 0;
                        if ($isRunning && $hasTimeout && $runTime >= $this->timeout) {
                            // Only send a SIGTERM and handle status in the next cycle
                            proc_terminate($process);
                        }

                        if (!$isRunning) {
                            $this->_exitCode = $status['exitcode'];
                            if ($this->_exitCode !== 0 && empty($this->_stdErr)) {
                                if ($status['stopped']) {
                                    $signal = $status['stopsig'];
                                    $this->_stdErr = "Command stopped by signal $signal";
                                } elseif ($status['signaled']) {
                                    $signal = $status['termsig'];
                                    $this->_stdErr = "Command terminated by signal $signal";
                                } else {
                                    $this->_stdErr = 'Command unexpectedly terminated without error message';
                                }
                            }
                            fclose($pipes[1]);
                            fclose($pipes[2]);
                            proc_close($process);
                        } else {
                            // The command is still running. Let's wait some
                            // time before we start the next cycle.
                            usleep(10000);
                        }
                    }
                } else {
                    if ($hasInput) {
                        if ($isInputStream) {
                            stream_copy_to_stream($this->_stdIn, $pipes[0]);
                        } elseif ($isInputString) {
                            fwrite($pipes[0], $this->_stdIn);
                        }
                        fclose($pipes[0]);
                    }
                    $this->_stdOut = stream_get_contents($pipes[1]);
                    $this->_stdErr = stream_get_contents($pipes[2]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    $this->_exitCode = proc_close($process);
                }

                if ($this->_exitCode !== 0) {
                    $this->_error = $this->_stdErr ?
                        $this->_stdErr :
                        "Failed without error message: $command (Exit code: {$this->_exitCode})";
                    return false;
                }
            } else {
                $this->_error = "Could not run command $command";
                return false;
            }
        }

        $this->_executed = true;

        return true;
    }

    /**
     * @return bool whether we are on a Windows OS
     */
    public function getIsWindows()
    {
        return strncasecmp(PHP_OS, 'WIN', 3)===0;
    }

    /**
     * @return string the current command string to execute
     */
    public function __toString()
    {
        return (string) $this->getExecCommand();
    }
}

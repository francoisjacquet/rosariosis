php-shellcommand
================

[![GitHub Tests](https://github.com/mikehaertl/php-shellcommand/workflows/Tests/badge.svg)](https://github.com/mikehaertl/php-shellcommand/actions)
[![Packagist Version](https://img.shields.io/packagist/v/mikehaertl/php-shellcommand?label=version)](https://packagist.org/packages/mikehaertl/php-shellcommand)
[![Packagist Downloads](https://img.shields.io/packagist/dt/mikehaertl/php-shellcommand)](https://packagist.org/packages/mikehaertl/php-shellcommand)
[![GitHub license](https://img.shields.io/github/license/mikehaertl/php-shellcommand)](https://github.com/mikehaertl/php-shellcommand/blob/master/LICENSE)
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/mikehaertl/php-shellcommand)](https://packagist.org/packages/mikehaertl/php-shellcommand)

php-shellcommand provides a simple object oriented interface to execute shell commands.

## Installing

### Prerequisites

Your php version must be `5.4` or later.

### Installing with composer

This package can be installed easily using composer.

```
composer require mikehaertl/php-shellcommand
```

## Features

 * Catches `stdOut`, `stdErr` and `exitCode`
 * Handle argument escaping
 * Pass environment vars and other options to `proc_open()`
 * Pipe resources like files or streams into the command
 * Timeout for execution

## Examples

### Basic Example

```php
<?php
use mikehaertl\shellcommand\Command;

// Basic example
$command = new Command('/usr/local/bin/mycommand -a -b');
if ($command->execute()) {
    echo $command->getOutput();
} else {
    echo $command->getError();
    $exitCode = $command->getExitCode();
}
```

### Advanced Features

#### Add Arguments
```php
<?php
$command = new Command('/bin/somecommand');
// Add arguments with correct escaping:
// results in --name='d'\''Artagnan'
$command->addArg('--name=', "d'Artagnan");

// Add argument with several values
// results in --keys key1 key2
$command->addArg('--keys', ['key1','key2']);
```

### Pipe Input Into Command

From string:
```php
<?php
$command = new ('jq'); // jq is a pretty printer
$command->setStdIn('{"foo": 0}');
if (!$command->execute()) {
    echo $command->getError();
} else {
    echo $command->getOutput();
}
// Output:
// {
//   "foo": 0
// }
```

From file:
```php
<?php
$fh = fopen('test.json', 'r');
// error checks left out...
$command = new Command('jq');
$command->setStdIn($fh);
if (!$command->execute()) {
    echo $command->getError();
} else {
    echo $command->getOutput();
}
fclose($fh);
```
From URL:
```php
<?php
$fh = fopen('https://api.open-meteo.com/v1/forecast?latitude=52.52&longitude=13.41&hourly=temperature_2m,relativehumidity_2m,windspeed_10m', 'r');
// error checks left out...
$command = new Command('jq');
$command->setStdIn($fh);
if (!$command->execute()) {
    echo $command->getError();
} else {
    echo $command->getOutput();
}
fclose($fh);
```

#### Set Command Instance Options
```php
<?php
// Create command with options array
$command = new Command([
    'command' => '/usr/local/bin/mycommand',

    // Will be passed as environment variables to the command
    'procEnv' => [
        'DEMOVAR' => 'demovalue'
    ],

    // Will be passed as options to proc_open()
    'procOptions' => [
        'bypass_shell' => true,
    ],
]);
```

## API

### Properties

 * `$escapeArgs`: Whether to escape any argument passed through `addArg()`. Default is `true`.
 * `$escapeCommand`: Whether to escape the command passed to `setCommand()` or the constructor.
    This is only useful if `$escapeArgs` is `false`. Default is `false`.
 * `$useExec`: Whether to use `exec()` instead of `proc_open()`. This is a workaround for OS which
   have problems with `proc_open()`. Default is `false`.
 * `$captureStdErr`: Whether to capture stderr when `useExec` is set. This will try to redirect
   the otherwhise unavailable `stderr` to `stdout`, so that both have the same content on error.
   Default is `true`.
 * `$procCwd`: The initial working dir passed to `proc_open()`. Default is `null` for current
    PHP working dir.
 * `$procEnv`: An array with environment variables to pass to `proc_open()`. Default is `null` for none.
 * `$procOptions`: An array of `other_options` for `proc_open()`. Default is `null` for none.
 * `$nonBlockingMode`: Whether to set the stdin/stdout/stderr streams to non-blocking
    mode when `proc_open()` is used. This allows to have huge inputs/outputs
    without making the process hang. The default is `null` which will enable
    the feature on Non-Windows systems. Set it to `true` or `false` to manually
    enable/disable it. Note that it doesn't work on Windows.
 * `$timeout`: The time in seconds after which the command should be
    terminated. This only works in non-blocking mode. Default is `null` which
    means the process is never terminated.
 * `$locale`: The locale to (temporarily) set with `setlocale()` before running the command.
   This can be set to e.g. `en_US.UTF-8` if you have issues with UTF-8 encoded arguments.

You can configure all these properties via an array that you pass in the constructor. You can also
pass `command`, `execCommand` and `args` as options. This will call the respective setter (`setCommand()`,
`setExecCommand()`, etc.).

### Methods

 * `__construct($options = null)`
    * `$options`: either a command string or an options array (see `setOptions()`)
 * `__toString()`: The result from `getExecCommand()`
 * `setOptions($options)`: Set command options
    * `$options`: array of name => value options that should be applied to the object.
       You can also pass options that use a setter, e.g. you can pass a `command` option which
       will be passed to `setCommand().`
 * `setCommand($command)`: Set command
    * `$command`: The command or full command string to execute, like `gzip` or `gzip -d`.
       You can still call `addArg()` to add more arguments to the command. If `$escapeCommand` was
       set to `true`, the command gets escaped through `escapeshellcmd()`.
 * `getCommand()`: The command that was set through `setCommand()` or passed to the constructor.
 * `getExecCommand()`: The full command string to execute.
 * `setArgs($args)`: Set argument as string
    * `$args`: The command arguments as string. Note, that these will not get escaped. This
      will overwrite the args added with `addArgs()`.
 * `getArgs()`: The command arguments that where set through `setArgs()` or `addArg()`, as string
 * `addArg($key, $value=null, $escape=null)`: Add argument with correct escaping
    * `$key`: The argument key to add e.g. `--feature` or `--name=`. If the key does not end with
       and `=`, the (optional) `$value` will be separated by a space. The key will get
       escaped if `$escapeArgs` is `true`.
    * `$value`: The optional argument value which will get escaped if `$escapeArgs` is `true`.
       An array can be passed to add more than one value for a key, e.g. `addArg('--exclude', ['val1','val2'])`
       which will create the option "--exclude 'val1' 'val2'".
    * `$escape`: If set, this overrides the `$escapeArgs` setting and enforces escaping/no escaping
 * `setStdIn()`: String or resource to supply to command via standard input.
   This enables the same functionality as piping on the command line. It can
   also be a resource like a file handle or a stream in which case its content
   will be piped into the command like an input redirection.
 * `getOutput()`: The command output as string. Empty if none.
 * `getError()`: The error message, either stderr or internal message. Empty if no error.
 * `getStdErr()`: The stderr output. Empty if none.
 * `getExitCode()`: The exit code or `null` if command was not executed.
 * `getExecuted()`: Whether the command was successfully executed.
 * `getIsWindows()`: Whether we are on a Windows Owe are on a Windows OS
 * `execute()`: Executes the command and returns `true` on success, `false` otherwhise.

> **Note:** `getError()`, `getStdErr()` and `getOutput()` return the trimmed output.
> You can pass `false` to these methods if you need any possible line breaks at the end.

# CHANGELOG

## 1.6.4

 * Let getExecCommand() not cache the created command string

## 1.6.3

 * Include PHP 5.3 in version requirements

## 1.6.2

 * Add .gitattributes to reduce package size

## 1.6.1

 * Issue #44 Fix potential security issue with escaping shell args (@Kirill89 / https://snyk.io/)

## 1.6.0

 * Issue #24 Implement timeout feature

## 1.5.0

 * Issue #20 Refactor handling of stdin/stdou/sterr streams with proc_open().
   By default these streams now operate in non-blocking mode which should fix
   many hanging issues that were caused when the command received/sent a lot of
   input/output. This is the new default on Non-Windows systems (it's not
   supported on Windows, though). To get the old behavior the nonBlockingMode
   option can be set to false.

## 1.4.1

 * Allow command names with spaces on Windows (@Robindfuller )

## 1.4.0

 * Allow stdin to be a stream or a file handle (@Arzaroth)

## 1.3.0

 * Add setStdIn() which allows to pipe an input string to the command (@martinqvistgard)

## 1.2.5

 * Issue #22 Fix execution of relative file paths on windows

## 1.2.4

 * Reverted changes for Issue #20 as this introduced BC breaking problems

## 1.2.3

 * Issue #20: Read stderr before stdout to avoid hanging processes

## 1.2.2

 * Issue #16: Command on different drive didn't work on windows

## 1.2.1

 * Issue #1: Command with spaces didn't work on windows

## 1.2.0

 * Add option to return untrimmed output and error

## 1.1.0

 * Issue #7: UTF-8 encoded arguments where truncated

## 1.0.7

 * Issue #6: Solve `proc_open()` pipe configuration for both, Windows / Linux

## 1.0.6

 * Undid `proc_open()` changes as it broke error capturing

## 1.0.5

 * Improve `proc_open()` pipe configuration

## 1.0.4

 * Add `$useExec` option to fix Windows issues (#3)

## 1.0.3

 * Add `getExecuted()` to find out execution status of the command

## 1.0.2

 * Add `$escape` parameter to `addArg()` to override escaping settings per call

## 1.0.1

 * Minor fixes

## 1.0.0

 * Initial release

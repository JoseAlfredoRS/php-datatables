<?php

/**
 * PHP library for Log.
 *
 * @author    JoseAlfredoRS <alfredors.developer@gmail.com>
 * @copyright 2019 - 2020 (c) JoseAlfredoRS - PHP-Log
 * @license   https://opensource.org/licenses/MIT - The MIT License (MIT)
 * @link      https://github.com/JoseAlfredoRS/php-datatables
 * @since     1.0.0
 */

/**
 * Log handler.
 *
 * @since 1.0.0
 */

class Log
{

    # @string, Log directory name
    private $path = '/logs/';

    # @void, Default Constructor, Sets the timezone and path of the log files.
    public function __construct()
    {
        date_default_timezone_set('America/Lima');
        $this->path  = dirname(__FILE__)  . $this->path;
    }

    /**
     *   @void 
     *	Creates the log
     *
     *   @param string $message the message which is written into the log.
     *	@description:
     *	 1. Checks if directory exists, if not, create one and call this method again.
     *	 2. Checks if log already exists.
     *	 3. If not, new log gets created. Log is written into the logs folder.
     *	 4. Logname is current date(Year - Month - Day).
     *	 5. If log exists, edit method called.
     *	 6. Edit method modifies the current log.
     */
    public function write($message)
    {
        $date = new DateTime();
        $log = $this->path . $date->format('Y-m-d') . ".txt";

        if (is_dir($this->path)) {
            if (!file_exists($log)) {
                $fh  = fopen($log, 'a+') or die("Fatal Error !");
                $logcontent = "Time : " . $date->format('H:i:s') . "\r\n" . $message . "\r\n";
                fwrite($fh, $logcontent);
                fclose($fh);
            } else {
                $this->edit($log, $date, $message);
            }
        } else {
            if (mkdir($this->path, 0777) === true) {
                $this->write($message);
            }
        }
    }

    /** 
     *  @void
     *  Gets called if log exists. 
     *  Modifies current log and adds the message to the log.
     *
     * @param string $log
     * @param DateTimeObject $date
     * @param string $message
     */
    private function edit($log, $date, $message)
    {
        $logcontent = "Time : " . $date->format('H:i:s') . "\r\n" . $message . "\r\n\r\n";
        $logcontent = $logcontent . file_get_contents($log);
        file_put_contents($log, $logcontent);
    }
}

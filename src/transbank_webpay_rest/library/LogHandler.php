<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

define('Webpay_ROOT', dirname(__DIR__));

class LogHandler
{
    //constants for log handler
    const LOG_DEBUG_ENABLED = false; //enable or disable debug logs
    const LOG_INFO_ENABLED = true; //enable or disable info logs
    const LOG_ERROR_ENABLED = true; //enable or disable error logs
    const DEFAULT_CONF_DAYS = 7;

    private $logDir;

    public function __construct($ecommerce = 'virtuemart', $days = 7, $weight = '2MB')
    {
        $this->reponse = null;
        $this->logDir = null;
        $this->lockfile = Webpay_ROOT . '/set_logs_activate.lock';

        $this->confdays = $days;
        $this->confweight = $weight;
        $this->logDir = JPATH_ROOT . '/administrator/logs/Transbank_webpay';

        try {
            if (!file_exists($this->logDir)) {
                mkdir($this->logDir, 0755, true);
            }
            $this->protectLogDir();
        } catch (Exception $e) {
            error_log('Transbank Webpay: ' . $e->getMessage());
        }

        $dia = date('Y-m-d');
        $logFile = "{$this->logDir}/log_transbank_{$ecommerce}_{$dia}.log";

        $formatter = new LineFormatter("[%datetime%] [%level_name%] %message%\n", 'Y-m-d H:i:s');
        $handler = new StreamHandler($logFile, Logger::DEBUG);
        $handler->setFormatter($formatter);
        $this->logger = new Logger('main');
        $this->logger->pushHandler($handler);
    }

    private function formatBytes($path)
    {
        $bytes = sprintf('%u', filesize($path));
        if ($bytes > 0) {
            $unit = intval(log($bytes, 1024));
            $units = ['B', 'KB', 'MB', 'GB'];
            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }

        return $bytes;
    }

    private function getIsLogDir()
    {
        if (!file_exists($this->logDir)) {
            //echo "error!: no existe directorio de logs, favor crear uno";
            return false;
        } else {
            return true;
        }
    }

    private function setMakeLogDir()
    {
        if ($this->getIsLogDir() === false) {
            mkdir($this->logDir, 0755, true);
            $this->protectLogDir();
        } else {
            exit;
        }
    }

    private function protectLogDir()
    {
        $htaccess = $this->logDir . '/.htaccess';

        if ((!file_exists($htaccess) || filesize($htaccess) === 0)
            && file_put_contents($htaccess, "Require all denied\nDeny from all\n") === false
        ) {
            error_log('Transbank Webpay: could not write .htaccess to log directory: ' . $this->logDir);
        }
    }

    private function setparamsconf($days, $weight)
    {
        if (file_exists($this->lockfile)) {
            $file = fopen($this->lockfile, 'w') or exit('No se puede truncar archivo');
            if (!is_numeric($days) or $days == null or $days == '' or $days === false) {
                $days = 7;
            }
            $txt = "{$days}\n";
            fwrite($file, $txt);
            $txt = "{$weight}\n";
            fwrite($file, $txt);
            fclose($file);
            chmod($this->lockfile, 0600);
        } else {
            //  echo "error!: no se ha podido renovar configuracion";
            exit;
        }
    }

    private function setLockFile()
    {

        if (!file_exists($this->lockfile)) {
            $file = fopen($this->lockfile, 'w') or exit('No se puede crear archivo de bloqueo');
            if (!is_numeric($this->confdays) or $this->confdays == null or $this->confdays == '' or $this->confdays === false) {
                $this->confdays = self::DEFAULT_CONF_DAYS;
            }
            $txt = "{$this->confdays}\n";
            fwrite($file, $txt);
            $txt = "{$this->confweight}\n";
            fwrite($file, $txt);
            fclose($file);
            chmod($this->lockfile, 0600);

            return true;
        } else {
            // echo "Error!; archivo ya existe!";
            return false;
        }
    }

    public function getValidateLockFile()
    {
        if (!file_exists($this->lockfile)) {
            $result = [
                'status'         => false,
                'lock_file'      => basename($this->lockfile),
                'max_logs_days'  => '7',
                'max_log_weight' => '2',
            ];
        } else {
            $lines = file($this->lockfile);
            $this->confdays = trim(preg_replace('/\s\s+/', ' ', $lines[0]));
            $this->confweight = trim(preg_replace('/\s\s+/', ' ', $lines[1]));
            $result = [
                'status'         => true,
                'lock_file'      => basename($this->lockfile),
                'max_logs_days'  => $this->confdays,
                'max_log_weight' => $this->confweight,
            ];
        }

        return $result;
    }

    private function delLockFile()
    {
        if (file_exists($this->lockfile)) {
            unlink($this->lockfile);
        }
    }

    private function setLogList()
    {
        $arr = array_filter(
            array_diff(scandir($this->logDir), ['.', '..']),
            [$this, 'isLogFilename']
        );

        if (!empty($arr)) {
            $this->logList = array_values($arr);
        } else {
            $this->logList = null;
        }

        return $this->logList;
    }

    public function setTransactionId($token)
    {
        $this->transactionID = $token;
    }

    private function setLastLog()
    {
        $files = glob($this->logDir . '/*.log');
        if (!$files) {
            return ['No existen Logs disponibles'];
        }
        $files = array_combine($files, array_map('filemtime', $files));
        arsort($files);
        $this->lastLog = key($files);
        if (isset($this->lastLog)) {
            $var = file_get_contents($this->lastLog);
        } else {
            $var = null;
        }
        $return = [
            'log_file'       => basename($this->lastLog),
            'log_weight'     => $this->formatBytes($this->lastLog),
            'log_regs_lines' => count(file($this->lastLog)),
            'log_content'    => $var,
        ];

        return $return;
    }

    private function readLogByFile($filename)
    {
        $var = file_get_contents($this->logDir . '/' . $filename);
        $return = [
            'log_file'    => $filename,
            'log_content' => $var,
        ];

        return $return;
    }

    private function setCountLogByFile($filename)
    {
        $fp = file($this->logDir . '/' . $filename);
        $return = [
            'log_file'   => $filename,
            'lines_regs' => count($fp),
        ];

        return $return;
    }

    private function setLastLogCountLines()
    {
        $lastfile = $this->setLastLog();
        $fp = file($this->logDir . '/' . $lastfile['log_file']);
        $return = [
            'log_file'   => basename($lastfile['log_file']),
            'lines_regs' => count($fp),
        ];

        return $return;
    }

    private function setLogCount()
    {
        $count = count($this->setLogList());
        $result = ['log_count' => $count];

        return $result;
    }

    private function sanitizeMessage($msg): string
    {
        $msg = strip_tags((string) $msg);
        $msg = preg_replace('/[\r\n]+/', ' ', $msg);

        return trim($msg);
    }

    /** Funciones de mantencion de directorio de logs**/

    // limpieza total de directorio

    private function delAllLogs()
    {
        if (!file_exists($this->logDir)) {
            // echo "error!: no existe directorio de logs";
            exit;
        }
        $files = glob($this->logDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    // mantiene solo los ultimos n dias de logs
    private function digestLogs()
    {
        if (!file_exists($this->logDir)) {
            // echo "error!: no existe directorio de logs";
            $this->setMakeLogDir();
            //exit;
        }
        $files = glob($this->logDir . '/*', GLOB_ONLYDIR);
        $deletions = array_slice($files, 0, count($files) - $this->confdays);
        foreach ($deletions as $to_delete) {
            array_map('unlink', glob("$to_delete"));
            //$deleted = rmdir($to_delete);
        }

        return true;
    }

    /**Funciones de retorno**/

    // Obtiene archivo de bloqueo
    public function getLockFile()
    {
        return json_encode($this->getValidateLockFile());
    }

    // obtiene directorio de log
    public function getLogDir()
    {
        return json_encode($this->getLogDirValue());
    }

    /**
     * Returns the raw (non-JSON-encoded) log directory path.
     *
     * @return string
     */
    public function getLogDirValue()
    {
        return $this->logDir;
    }

    // obtiene conteo de logs en logdir definido
    public function getLogCount()
    {
        return json_encode($this->setLogCount());
    }

    /**
     * Checks whether a filename matches the naming convention for log files
     * managed by this handler, including rotated backups.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function isLogFilename($filename)
    {
        return preg_match('/^log_transbank_[A-Za-z0-9_\-]+\.log(\.\d+)?$/', $filename) === 1;
    }

    // obtiene listado de logs en logdir
    public function getLogList()
    {
        return json_encode($this->setLogList());
    }

    // obtiene ultimo log modificado (al crearse con timestamp es tambien el ultimo creado)
    public function getLastLog()
    {
        return json_encode($this->setLastLog());
    }

    // obtiene conteo de lineas de ultimo log creado
    public function getLastLogCountLines()
    {
        return json_encode($this->setLastLogCountLines());
    }

    // obtiene log en base a parametro
    public function getLogByFile($filename)
    {
        return json_encode($this->readLogByFile($filename));
    }

    // obtiene conteo de lineas de log en base a parametro
    public function getCountLogByFile($filename)
    {
        return json_encode($this->setCountLogByFile($filename));
    }

    public function delLogsFromDir()
    {
        $this->delAllLogs();
    }

    public function delKeepOnlyLastLogs()
    {
        $this->digestLogs();
    }

    public function setLockStatus($status = true)
    {
        if ($status === true) {
            $this->setLockFile();
        } else {
            $this->delLockFile();
        }
    }

    public function getResume()
    {
        $result = [
            'config'     => $this->getValidateLockFile(),
            'log_dir'    => $this->getLogDirValue(),
            'logs_count' => $this->setLogCount(),
            'logs_list'  => $this->setLogList(),
            'last_log'   => $this->setLastLog(),
        ];

        return json_encode($result);
    }

    public function setnewconfig($days, $weight)
    {
        $this->setparamsconf($days, $weight);
    }

    /**
     * print DEBUG log.
     */
    public function logDebug($msg)
    {
        if (self::LOG_DEBUG_ENABLED) {
            $this->logger->debug('DEBUG: ' . $this->sanitizeMessage($msg));
        }
    }

    /**
     * print INFO log.
     */
    public function logInfo($msg)
    {
        if (self::LOG_INFO_ENABLED) {
            $this->logger->info('INFO: ' . $this->sanitizeMessage($msg));
        }
    }

    /**
     * print ERROR log.
     */
    public function logError($msg)
    {
        if (self::LOG_ERROR_ENABLED) {
            $this->logger->error('ERROR: ' . $this->sanitizeMessage($msg));
        }
    }
}

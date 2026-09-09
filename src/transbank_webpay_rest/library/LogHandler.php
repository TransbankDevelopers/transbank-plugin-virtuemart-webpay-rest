<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Exception\FilesystemException;

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
                Folder::create($this->logDir, 0755);
            }
            $this->protectLogDir();
        } catch (FilesystemException $e) {
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

    private function protectLogDir()
    {
        $htaccess = $this->logDir . '/.htaccess';
        $rules = "Require all denied\nDeny from all\n";

        if ((!file_exists($htaccess) || filesize($htaccess) === 0)
            && !File::write($htaccess, $rules)
        ) {
            error_log('Transbank Webpay: could not write .htaccess to log directory: ' . $this->logDir);
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

    /**
     * Returns the raw (non-JSON-encoded) log directory path.
     *
     * @return string
     */
    public function getLogDirValue()
    {
        return $this->logDir;
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

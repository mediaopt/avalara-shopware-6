<?php declare(strict_types=1);

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 * *
 * * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use Composer\Package\Archiver\ZipArchiver;

class LogHelper
{
    private AvalaraSDKAdapter $adapter;

    private const DEFAULT_LOG_LEVEL = 'INFO';

    /**
     * @param AvalaraSDKAdapter $adapter
     */
    public function __construct(AvalaraSDKAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param string $message
     * @param int|Level $logLevel
     * @param mixed $additionalData
     * @return void
     */
    public function log(string $message, int|Level $logLevel = 0, mixed $additionalData = ''): void
    {
        if ($logLevel == 0) {
            $logLevel = $this->getLogLevel();
        }

        self::addLog($logLevel, $message, $additionalData);
    }

    /**
     * get monolog log-level by module configuration
     * @return Level
     */
    private function getLogLevel(): Level
    {
        $logLevel = self::DEFAULT_LOG_LEVEL;

        if ($overrideLogLevel = $this->adapter->getPluginConfig(Form::LOG_LEVEL)) {
            $logLevel = $overrideLogLevel;
        }

        //set levels
        return match ($logLevel) {
            'INFO' => Level::Info,
            'ERROR' => Level::Error,
            'DEBUG' => Level::Debug
        };
    }

    /**
     * @param int|Level $logLevel
     * @param string $message
     * @param mixed $additionalData
     * @return void
     */
    public static function addLog(int|Level $logLevel, string $message, mixed $additionalData = ''): void
    {
        $logger = new Logger('Avalara');
        self::addRecord($logger, $logLevel, $message, $additionalData);
        self::addPluginLog($logLevel, $message, $additionalData);
    }

    /**
     * @param int|Level $logLevel
     * @param string $message
     * @param mixed $additionalData
     * @return void
     */
    public static function addPluginLog(int|Level $logLevel, string $message, mixed $additionalData = ''): void
    {
        $fullPath = dirname(__DIR__, 5) . Form::LOG_DIR_PATH . 'log';
        $streamHandler = new RotatingFileHandler($fullPath, Form::LOG_FILE_MAX, $logLevel);
        $logger = new Logger('avalara', [$streamHandler]);
        self::addRecord($logger, $logLevel, $message, $additionalData);
    }

    /**
     * @param Logger $logger
     * @param int|Level $logLevel
     * @param string $message
     * @param mixed $additionalData
     * @return void
     */
    public static function addRecord(Logger $logger, int|Level $logLevel, string $message, mixed $additionalData = '')
    {
        $logger->addRecord(
            $logLevel,
            $message,
            [
                'source' => 'Avalara',
                'additionalData' => json_encode($additionalData),
            ]
        );
    }

    /**
     * @return string
     */
    public static function getArchive(): string
    {
        $zip = new ZipArchiver();
        $archivePath = dirname(__DIR__, 5) . Form::LOG_ARCHIVE_PATH;
        $zip->archive(
            dirname(__DIR__, 5) . Form::LOG_DIR_PATH,
            $archivePath,
            'zip'
        );

        return $archivePath;
    }
}
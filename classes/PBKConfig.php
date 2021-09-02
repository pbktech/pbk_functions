<?php

final class PBKConfig {

    static private ?object $config = null;
    static private string $file = "/var/www/html/config.json";

    public function __construct() {
    }

    /**
     * @throws Exception
     */
    private function getConfigFile(): void {
        if (file_exists(PBKConfig::$file)) {
            try {
                $file = file_get_contents(PBKConfig::$file);
                PBKConfig::$config = json_decode($file);
            } catch (Exception $e) {
                throw new Exception("Error reading or converting config: " . $e->getMessage());
            }
        } else {
            throw new Exception("Config file not found");
        }
    }

    /**
     * @param object $config
     */
    public function setConfig(object $config): void {
        PBKConfig::$config = $config;
    }

    /**
     * @return object
     * @throws Exception
     */
    public function getConfig(string $key): ?object {
        try {
            if (PBKConfig::$config === null) {
                $this->getConfigFile();
            }
        } catch (Exception $e) {
            return null;
        }

        return PBKConfig::$config->$key;
    }

    public function getString(string $key): ?string{
        try {
            if (PBKConfig::$config === null) {
                $this->getConfigFile();
            }
        } catch (Exception $e) {
            return null;
        }

        return PBKConfig::$config->$key;
    }

}
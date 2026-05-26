<?php
namespace SACCO\Services;

require_once __DIR__ . '/../../config/db_connection.php';

class SettingsService
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function getSetting(string $key, $default = null)
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? json_decode($row['setting_value'], true) : $default;
    }

    public function getAllSettings(): array
    {
        $stmt = $this->db->query('SELECT * FROM system_settings ORDER BY `group`, setting_key');
        return $stmt->fetchAll();
    }

    public function saveSetting(string $key, $value, string $label = '', string $group = 'general'): bool
    {
        $jsonValue = json_encode($value);
        $stmt = $this->db->prepare('INSERT INTO system_settings (setting_key, setting_value, label, `group`, updated_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), label = VALUES(label), `group` = VALUES(`group`), updated_at = NOW()');
        return $stmt->execute([$key, $jsonValue, $label, $group]);
    }
}

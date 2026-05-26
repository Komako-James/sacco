<?php
/**
 * MemberService - Member registration, KYC, biometric enrollment, profile management
 */

namespace SACCO\Services;

require_once __DIR__ . '/../../config/db_connection.php';

class MemberService {
    private $db;

    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function registerMember(array $data, int $createdBy) : array {
        try {
            $this->db->beginTransaction();

            // Basic validation
            if (empty($data['membership_number']) || !$this->validateMembershipNumber($data['membership_number'])) {
                throw new \Exception('Invalid membership number format. Expect 3 digits 001-700.');
            }

            // Check uniqueness
            $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM members WHERE membership_number = ? OR national_id = ? OR phone_number = ?");
            $stmt->execute([$data['membership_number'], $data['national_id'] ?? null, $data['phone_number'] ?? null]);
            $row = $stmt->fetch();
            if ($row && $row['cnt'] > 0) {
                throw new \Exception('Member with same membership number, national id or phone already exists');
            }

            $stmt = $this->db->prepare("INSERT INTO members (
                membership_number, national_id, full_name, email, phone_number,
                date_of_birth, gender, employment_status, employer_id, basic_salary,
                created_by, account_status, kyc_status, date_joined
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', NOW())");

            $stmt->execute([
                $data['membership_number'],
                $data['national_id'] ?? null,
                $data['full_name'] ?? '',
                $data['email'] ?? null,
                $data['phone_number'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? 'M',
                $data['employment_status'] ?? 'Employed',
                $data['employer_id'] ?? null,
                $data['basic_salary'] ?? 0,
                $createdBy
            ]);

            $memberId = $this->db->lastInsertId();

            $this->logAudit($createdBy, 'Member Registration', 'members', $memberId, null, $data);

            $this->db->commit();

            return ['success' => true, 'member_id' => $memberId];

        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function verifyKYC(int $memberId, int $verifiedBy, string $status = 'Verified', ?string $reason = null) : array {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM members WHERE member_id = ?");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
            if (!$member) throw new \Exception('Member not found');

            $old = $member;

            $stmt = $this->db->prepare("UPDATE members SET kyc_status = ?, kyc_verified_date = NOW(), kyc_verified_by = ?, account_status = CASE WHEN ? = 'Verified' THEN 'Active' ELSE account_status END WHERE member_id = ?");
            $stmt->execute([$status, $verifiedBy, $status, $memberId]);

            $this->logAudit($verifiedBy, 'KYC Verification', 'members', $memberId, $old, ['kyc_status' => $status, 'reason' => $reason]);

            $this->db->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function enrollBiometric(int $memberId, string $biometricTemplate, int $enrolledBy) : array {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM members WHERE member_id = ?");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
            if (!$member) throw new \Exception('Member not found');

            // NOTE: For production, encrypt the template before storing (AES-256)
            $stmt = $this->db->prepare("UPDATE members SET biometric_template = ?, biometric_enrolled = 1, modified_by = ?, last_modified = NOW() WHERE member_id = ?");
            $stmt->execute([$biometricTemplate, $enrolledBy, $memberId]);

            $this->logAudit($enrolledBy, 'Biometric Enrollment', 'members', $memberId, null, ['biometric_enrolled' => 1]);

            $this->db->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateProfile(int $memberId, array $data, int $modifiedBy) : array {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT * FROM members WHERE member_id = ?");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
            if (!$member) throw new \Exception('Member not found');

            $old = $member;

            $stmt = $this->db->prepare("UPDATE members SET full_name = ?, email = ?, phone_number = ?, basic_salary = ?, employer_id = ?, modified_by = ?, last_modified = NOW() WHERE member_id = ?");
            $stmt->execute([
                $data['full_name'] ?? $member['full_name'],
                $data['email'] ?? $member['email'],
                $data['phone_number'] ?? $member['phone_number'],
                $data['basic_salary'] ?? $member['basic_salary'],
                $data['employer_id'] ?? $member['employer_id'],
                $modifiedBy,
                $memberId
            ]);

            $this->logAudit($modifiedBy, 'Profile Update', 'members', $memberId, $old, $data);

            $this->db->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getMember(int $memberId) {
        $stmt = $this->db->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$memberId]);
        return $stmt->fetch();
    }

    public function listMembers(int $page = 1, int $limit = 20) {
        $offset = ($page - 1) * $limit;
        $stmt = $this->db->prepare("SELECT * FROM members ORDER BY member_id DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function deactivateMember(int $memberId, int $by, string $reason) : array {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE members SET account_status = 'Inactive', status_changed_date = NOW(), modified_by = ? WHERE member_id = ?");
            $stmt->execute([$by, $memberId]);

            $this->logAudit($by, 'Deactivate Member', 'members', $memberId, null, ['reason' => $reason]);

            $this->db->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validateMembershipNumber(string $m) : bool {
        return preg_match('/^[0-9]{3}$/', $m) && ((int)$m >= 1 && (int)$m <= 700);
    }

    private function logAudit(int $userId, string $action, string $entityType, int $entityId, $oldValues = null, $newValues = null) : void {
        $stmt = $this->db->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    }
}

?>
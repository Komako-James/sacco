<?php
/**
 * Member Profile Page
 */

require_once '../member/auth-middleware.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

requireMemberLogin();

$member = getMemberData();
$db = getDB();

$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_profile') {
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';

    if (empty($phone) || empty($email)) {
        $message = 'Phone and email are required';
        $messageType = 'danger';
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE members SET phone = ?, email = ?, address = ?
                WHERE member_id = ?
            ");
            $stmt->execute([$phone, $email, $address, $member['member_id']]);
            $message = 'Profile updated successfully';
            $messageType = 'success';
            $member = getMemberData();
        } catch (Exception $e) {
            $message = 'Failed to update profile';
            $messageType = 'danger';
        }
    }
}

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'upload_photo') {
    if (isset($_FILES['profile_photo'])) {
        $file = $_FILES['profile_photo'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];

        if ($file['size'] > $maxSize) {
            $message = 'File too large. Maximum 5MB.';
            $messageType = 'danger';
        } elseif (!in_array($file['type'], $allowed)) {
            $message = 'Only JPG, PNG, and GIF files allowed.';
            $messageType = 'danger';
        } else {
            try {
                $uploadDir = '../uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = 'member_' . $member['member_id'] . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $stmt = $db->prepare("UPDATE members SET profile_photo = ? WHERE member_id = ?");
                    $stmt->execute([$fileName, $member['member_id']]);
                    $message = 'Profile photo updated successfully';
                    $messageType = 'success';
                    $member = getMemberData();
                } else {
                    $message = 'Failed to upload file';
                    $messageType = 'danger';
                }
            } catch (Exception $e) {
                $message = 'Upload failed: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Member Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="member-content">
        <div class="container-fluid p-4">
            <div class="mb-4">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <h2>My Profile</h2>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <?php if (!empty($member['profile_photo'])): ?>
                            <img src="../uploads/profiles/<?php echo htmlspecialchars($member['profile_photo']); ?>" alt="Profile" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                            <i class="bi bi-person-circle" style="font-size: 150px; color: #ccc;"></i>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" class="mt-3">
                                <input type="hidden" name="action" value="upload_photo">
                                <div class="input-group input-group-sm">
                                    <input type="file" name="profile_photo" class="form-control form-control-sm" accept="image/*" onchange="this.form.submit()">
                                </div>
                            </form>
                            <small class="text-muted d-block mt-2">Max 5MB</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fullName" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="fullName" value="<?php echo htmlspecialchars($member['full_name']); ?>" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="membershipNo" class="form-label">Membership Number</label>
                                        <input type="text" class="form-control" id="membershipNo" value="<?php echo htmlspecialchars($member['membership_no']); ?>" disabled>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="joinDate" class="form-label">Join Date</label>
                                    <input type="text" class="form-control" id="joinDate" value="<?php echo date('M d, Y', strtotime($member['join_date'])); ?>" disabled>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

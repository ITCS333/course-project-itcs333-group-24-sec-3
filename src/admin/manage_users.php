<!--
  Student Name: [Enter Your Name]
  Requirement: Create a Responsive Admin Portal

  Instructions:
  Fill in the HTML elements as described in the comments.
  Use the provided IDs for the elements that require them.
  Focus on creating a clear and semantic HTML structure.
-->
<?php
require_once __DIR__ . '/../common/auth.php';
require_once __DIR__ . '/../common/db.php';

requireAdmin();

$db = getDatabaseConnection();
$messages = [];
$errors = [];
$editStudent = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword === '' || strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $adminId = (int) $_SESSION['user']['id'];
            $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ? AND role = ? LIMIT 1');
            $stmt->execute([$adminId, 'admin']);
            $admin = $stmt->fetch();

            if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $update = $db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
                $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $adminId]);
                $messages[] = 'Password updated successfully.';
            }
        }
    } elseif (isset($_POST['create_student'])) {
        $name = trim($_POST['student_name'] ?? '');
        $studentId = trim($_POST['student_identifier'] ?? '');
        $email = trim($_POST['student_email'] ?? '');
        $defaultPassword = $_POST['default_password'] ?? '';

        if ($name === '' || $studentId === '' || $email === '' || $defaultPassword === '') {
            $errors[] = 'All student fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email for the student.';
        } elseif (strlen($defaultPassword) < 8) {
            $errors[] = 'Default password must be at least 8 characters long.';
        } else {
            try {
                $insert = $db->prepare('INSERT INTO users (name, student_id, email, role, password_hash) VALUES (?, ?, ?, ?, ?)');
                $insert->execute([
                    $name,
                    $studentId,
                    $email,
                    'student',
                    password_hash($defaultPassword, PASSWORD_DEFAULT),
                ]);
                $messages[] = 'Student added successfully.';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'A student with that email or student ID already exists.';
                } else {
                    $errors[] = 'Failed to add the student. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['update_student'])) {
        $studentId = (int) ($_POST['student_primary_id'] ?? 0);
        $name = trim($_POST['edit_student_name'] ?? '');
        $studentIdentifier = trim($_POST['edit_student_identifier'] ?? '');
        $email = trim($_POST['edit_student_email'] ?? '');

        if ($studentId <= 0) {
            $errors[] = 'Invalid student selected for update.';
        } elseif ($name === '' || $studentIdentifier === '' || $email === '') {
            $errors[] = 'All student fields are required for update.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email for the student.';
        } else {
            try {
                $update = $db->prepare('UPDATE users SET name = ?, student_id = ?, email = ?, updated_at = NOW() WHERE id = ? AND role = ?');
                $update->execute([$name, $studentIdentifier, $email, $studentId, 'student']);
                if ($update->rowCount() > 0) {
                    $messages[] = 'Student details updated successfully.';
                } else {
                    $errors[] = 'No changes were made, or the student no longer exists.';
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'Another student already uses that email or student ID.';
                } else {
                    $errors[] = 'Failed to update the student. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['delete_student'])) {
        $studentId = (int) ($_POST['student_primary_id'] ?? 0);
        if ($studentId <= 0) {
            $errors[] = 'Invalid student selected for deletion.';
        } else {
            $delete = $db->prepare('DELETE FROM users WHERE id = ? AND role = ?');
            $delete->execute([$studentId, 'student']);
            if ($delete->rowCount() > 0) {
                $messages[] = 'Student removed successfully.';
            } else {
                $errors[] = 'Unable to delete the student. It may have already been removed.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $targetId = (int) $_GET['edit'];
    if ($targetId > 0) {
        $stmt = $db->prepare('SELECT id, name, student_id, email FROM users WHERE id = ? AND role = ? LIMIT 1');
        $stmt->execute([$targetId, 'student']);
        $editStudent = $stmt->fetch() ?: null;
    }
}

$studentsStmt = $db->prepare('SELECT id, name, student_id, email, created_at FROM users WHERE role = ? ORDER BY name');
$studentsStmt->execute(['student']);
$students = $studentsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- TODO: Add the 'meta' tag for character encoding (UTF-8). -->
    <meta charset="UTF-8">
    <!-- TODO: Add the responsive 'viewport' meta tag. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- TODO: Add a 'title' for the page, e.g., "Admin Portal". -->
    <title>Admin Portal</title>
    <!-- TODO: Link to a CSS file or a CSS framework. -->
        <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/index.php">Course Admin</a>
            <div>
                <span class="navbar-text text-white me-3">Logged in as <?= htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <a class="btn btn-outline-light" href="/src/auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-success" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>

    <!-- TODO: Create a 'header' element for the top of the page. -->
    <header class="container mb-4">
        <!-- TODO: Inside the header, add a main heading (e.g., 'h1') with the text "Admin Portal". -->
         <h1 class="display-5">Admin Portal</h1>
    <!-- End of the header. -->
    </header>
    <!-- TODO: Create a 'main' element to hold the primary content of the portal. -->
    <main class="container">
        <!-- Section 1: Password Management -->
        <!-- TODO: Create a 'section' for the password management functionality. -->
        <section class="mb-5">
            <!-- TODO: Add a sub-heading (e.g., 'h2') with the text "Change Your Password". -->
            <h2 class="h4">Change Your Password</h2>
            <!-- TODO: Create a 'form' for changing the password. The 'action' can be '#'. -->
             <form action="#" method="post" class="card p-4 shadow-sm" novalidate>
                <input type="hidden" name="change_password" value="1">
                <!-- TODO: Use a 'fieldset' to group the password-related fields. -->
                <fieldset class="border-0 p-0">
                    <!-- TODO: Add a 'legend' for the fieldset, e.g., "Password Update". -->
                    <legend class="visually-hidden">Password Update</legend>
                    <!-- TODO: Add a 'label' for the current password input. 'for' should be "current-password". -->
                    <label class="form-label" for="current-password">Current Password:</label>
                    <!-- TODO: Add an 'input' for the current password.
                         - type="password"
                         - id="current-password"
                         - required -->
                    <input type="password" name="current_password" id="current-password" class="form-control mb-3" required>

                    <!-- TODO: Add a 'label' for the new password input. 'for' should be "new-password". -->
                    <label class="form-label" for="new-password">New Password:</label>
                    <!-- TODO: Add an 'input' for the new password.
                         - type="password"
                         - id="new-password"
                         - minlength="8"
                         - required -->
                    <input type="password" name="new_password" id="new-password" class="form-control mb-3" minlength="8" required>

                    <!-- TODO: Add a 'label' for the confirm password input. 'for' should be "confirm-password". -->
                    <label class="form-label" for="confirm-password">Confirm New Password:</label>
                    <!-- TODO: Add an 'input' to confirm the new password.
                         - type="password"
                         - id="confirm-password"
                         - required -->
                    <input type="password" name="confirm_password" id="confirm-password" class="form-control mb-4" required>

                    <!-- TODO: Add a 'button' to submit the form.
                         - type="submit"
                         - id="change"
                         - Text: "Update Password" -->
                    <button type="submit" id="change" class="btn btn-primary">Update Password</button>
                </fieldset>
                <!-- End of the fieldset. -->
            <!-- End of the password form. -->
            </form>
        <!-- End of the password management section. -->
        </section>


        <!-- Section 2: Student Management -->
        <!-- TODO: Create another 'section' for the student management functionality. -->
        <section class="mb-5">
            <!-- TODO: Add a sub-heading (e.g., 'h2') with the text "Manage Students". -->
            <h2 class="h4">Manage Students</h2>
            <!-- Subsection 2.1: Add New Student Form -->
            <!-- TODO: Create a 'details' element so the "Add Student" form can be collapsed. -->
            <details class="mb-4" <?= isset($_POST['create_student']) ? 'open' : '' ?>>
                <!-- TODO: Add a 'summary' element inside 'details' with the text "Add New Student". -->
                <summary>Add New Student</summary>
                <!-- TODO: Create a 'form' for adding a new student. 'action' can be '#'. -->
                <form action="#" method="post" class="mt-3" novalidate>
                    <input type="hidden" name="create_student" value="1">
                    <!-- TODO: Use a 'fieldset' to group the new student fields. -->
                    <fieldset class="card p-4 shadow-sm border-0">
                        <!-- TODO: Add a 'legend' for the fieldset, e.g., "New Student Information". -->
                        <legend class="visually-hidden">New Student Information</legend>
                        <!-- TODO: Add a 'label' and 'input' for the student's full name.
                             - label 'for': "student-name"
                             - input 'id': "student-name"
                             - input 'type': "text"
                             - input: required -->
                        <label class="form-label" for="student-name">Full Name:</label>
                        <input type="text" name="student_name" id="student-name" class="form-control mb-3" required value="<?= htmlspecialchars($_POST['student_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <!-- TODO: Add a 'label' and 'input' for the student's ID.
                             - label 'for': "student-id"
                             - input 'id': "student-id"
                             - input 'type': "text"
                             - input: required -->
                        <label class="form-label" for="student-id">Student ID:</label>
                        <input type="text" name="student_identifier" id="student-id" class="form-control mb-3" required value="<?= htmlspecialchars($_POST['student_identifier'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <!-- TODO: Add a 'label' and 'input' for the student's email.
                             - label 'for': "student-email"
                             - input 'id': "student-email"
                             - input 'type': "email"
                             - input: required -->
                        <label class="form-label" for="student-email">Email:</label>
                        <input type="email" name="student_email" id="student-email" class="form-control mb-3" required value="<?= htmlspecialchars($_POST['student_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        
                        <!-- TODO: Add a 'label' and 'input' for the default password.
                             - label 'for': "default-password"
                             - input 'id': "default-password"
                             - input 'type': "text"
                             - You can pre-fill this with a value like "password123" or leave it blank. -->
                        <label class="form-label" for="default-password">Default Password:</label>
                        <input type="text" name="default_password" id="default-password" value="<?= htmlspecialchars($_POST['default_password'] ?? 'password123', ENT_QUOTES, 'UTF-8') ?>" class="form-control mb-4">
                        <!-- TODO: Add a 'button' to submit the form.
                             - type="submit"
                             - id="add"
                             - Text: "Add Student" -->
                        <button type="submit" id="add" class="btn btn-success">Add Student</button>
                    </fieldset>
                    <!-- End of the fieldset. -->
                </form>
                <!-- End of the add student form. -->
            </details>
            <!-- End of the 'details' element. -->


            <!-- Subsection 2.2: Student List -->
            <!-- TODO: Add a sub-heading (e.g., 'h3') for the list of students, "Registered Students". -->
            <h3 class="h5">Registered Students</h3>
            <!-- TODO: Create a 'table' to display the list of students. Give it an id="student-table". -->
            <div class="table-responsive bg-white shadow-sm">
            <table id="student-table" class="table table-striped mb-0">
                <!-- TODO: Create a 'thead' for the table headers. -->
                <thead>
                    <!-- TODO: Create a 'tr' (table row) inside the 'thead'. -->
                    <tr>
                        <!-- TODO: Create 'th' (table header) cells for "Name", "Student ID", "Email", and "Actions". -->
                        <th scope="col">Name</th>
                        <th scope="col">Student ID</th>
                        <th scope="col">Email</th>
                        <th scope="col">Registered</th>
                        <th scope="col">Actions</th>
                    </tr>    
                    <!-- End of the row. -->
                </thead>
                <!-- End of 'thead'. -->
                <!-- TODO: Create a 'tbody' for the table body, where student data will go. -->
                <tbody>
                    <!-- TODO: For now, add 2-3 rows of dummy data so you can see how the table is structured. -->
                    <!-- Example Student Row: -->
                    <!-- TODO: Create a 'tr' for a student record. -->
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No students have been added yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <!-- TODO: Create 'td' (table data) cells for a student's name (e.g., "John Doe"), ID (e.g., "12345"), and email (e.g., "john.doe@example.com"). -->
                                <td><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($student['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <!-- TODO: Create a final 'td' for action buttons. -->
                                <td class="d-flex gap-2">

                                    <!-- TODO: Add an "Edit" button. -->
                                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int) $student['id'] ?>">Edit</a>
                                    <!-- TODO: Add a "Delete" button. -->
                                    <form action="#" method="post" class="d-inline" onsubmit="return confirm('Delete this student?');">
                                        <input type="hidden" name="delete_student" value="1">
                                        <input type="hidden" name="student_primary_id" value="<?= (int) $student['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                                <!-- End of the actions 'td'. -->
                            </tr>
                            <!-- End of the student row. -->
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
            <!-- End of the table. -->
        </section>
        <!-- End of the student management section. -->

        <?php if ($editStudent): ?>
            <section class="mb-5">
                <h2 class="h5">Edit Student</h2>
                <form action="#" method="post" class="card p-4 shadow-sm" novalidate>
                    <input type="hidden" name="update_student" value="1">
                    <input type="hidden" name="student_primary_id" value="<?= (int) $editStudent['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label" for="edit-student-name">Full Name:</label>
                        <input type="text" class="form-control" id="edit-student-name" name="edit_student_name" required value="<?= htmlspecialchars($editStudent['name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit-student-id">Student ID:</label>
                        <input type="text" class="form-control" id="edit-student-id" name="edit_student_identifier" required value="<?= htmlspecialchars($editStudent['student_id'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit-student-email">Email:</label>
                        <input type="email" class="form-control" id="edit-student-email" name="edit_student_email" required value="<?= htmlspecialchars($editStudent['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Save Changes</button>
                        <a class="btn btn-secondary" href="manage_users.php">Cancel</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </main>
    <!-- End of the main content area. -->

</body>
</html>


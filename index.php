<!--
  Requirement: Create the Course Homepage

  Instructions:
  This page will serve as the main entry point to the course website.
  It should contain a simple navigation menu with links to all the key pages.
-->
<?php
require_once __DIR__ . '/src/common/auth.php';

ensureSessionStarted();
$currentUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- TODO: Add the 'meta' tag for character encoding (UTF-8). -->
    <meta charset="UTF-8" />
    <!-- TODO: Add the responsive 'viewport' meta tag. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- TODO: Add a title, e.g., "Web Dev Course Homepage". -->
    <title>Wed Dev Course Homepage</title>
    <!-- TODO: Link to your CSS framework or stylesheet. -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body>
    <!-- TODO: Create a 'header' with a main heading ('h1') like "Welcome to the Web Development Course". -->
    <header class="container py-4">
      <div class="d-flex justify-content-between align-items-center">
        <h1>Welcome to the Web Development Course</h1>
        <?php if ($currentUser): ?>
          <div class="text-end">
            <p class="mb-1">Signed in as <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?>)</p>
            <a class="btn btn-outline-secondary btn-sm" href="/src/auth/logout.php">Logout</a>
          </div>
        <?php else: ?>
          <a class="btn btn-primary" href="/src/auth/login.php">Login</a>
        <?php endif; ?>
      </div>
    </header>
    <!-- TODO: Create the 'main' content area. -->
    <main class="container pb-5">
      <!-- TODO: Create a 'nav' element to hold the site navigation. -->
      <nav class="card p-4 shadow-sm">
        <!-- TODO: Add a sub-heading ('h2') "Site Navigation". -->
        <h2 class="h4">Site Navigation</h2>
        <!-- TODO: Create an unordered list ('ul') to hold the links. -->
        <ul class="list-unstyled">
          <!-- Section: General -->
          <!-- TODO: Add a list item ('li') with a link ('a') to the Login page.
                     - Text: "Login" -->
          <li class="mb-2"><a href="/src/auth/login.php">Login</a></li>
          <!-- Section: Admin Pages -->
          <!-- TODO: Add a list item ('li') with a link ('a') to the Admin Portal.
                     - Text: "Admin Portal (Manage Students)" -->
          <li>
            <a href="/src/admin/manage_users.php"
              >Admin Portal (Manage Students)</a
            >
          </li>
          <!-- TODO: Add a list item ('li') with a link ('a') to the Admin Resources page.
                     - Text: "Admin: Manage Resources" -->
          <li>
            <a href="src/resources/admin.html">Admin: Manage Resources</a>
          </li>
          <!-- TODO: Add a list item ('li') with a link ('a') to the Admin Weekly Breakdown page.
                     - Text: "Admin: Manage Weekly Breakdown" -->
          <li>
            <a href="src/weekly/admin.html">Admin: Manage Weekly Breakdown</a>
          </li>
          <!-- TODO: Add a list item ('li') with a link ('a') to the Admin Assignments page.
                     - Text: "Admin: Manage Assignments" -->
          <li>
            <a href="src/assignments/admin.html">Admin: Manage Assignments</a>
          </li>
          <!-- Section: Student Pages -->
          <!-- TODO: Add a list item ('li') with a link ('a') to the Student Resources page.
                     - Text: "View Course Resources" -->
          <li><a href="src/resources/list.html">View Course Resources</a></li>
          <!-- TODO: Add a list item ('li') with a link ('a') to the Student Weekly Breakdown page.
                     - Text: "View Weekly Breakdown" -->
          <li><a href="src/weekly/list.html">View Weekly Breakdown</a></li>

          <!-- TODO: Add a list item ('li') with a link ('a') to the Student Assignments page.
                     - Text: "View Assignments" -->
          <li><a href="src/assignments/list.html">View Assignments</a></li>
          <!-- TODO: Add a list item ('li') with a link ('a') to the Discussion Board.
                     - Text: "General Discussion Board" -->
          <li>
            <a href="src/discussion/board.html">General Discussion Board</a>
          </li>
        </ul>
        <!-- End of the unordered list. -->
      </nav>
      <!-- End of the 'nav' element. -->
    </main>
    <!-- End of 'main'. -->
  </body>
</html>

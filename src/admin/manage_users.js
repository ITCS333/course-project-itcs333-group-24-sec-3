/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
let students = [];

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the student table body (tbody).
let tableBody = document.querySelector("tbody");

// TODO: Select the "Add Student" form.
// (You'll need to add id="add-student-form" to this form in your HTML).
let addStudentForm = document.getElementById("add-student-form");
// TODO: Select the "Change Password" form.
// (You'll need to add id="password-form" to this form in your HTML).
let changePasswordForm = document.getElementById("password-form");

// TODO: Select the search input field.
// (You'll need to add id="search-input" to this input in your HTML).
let searchInput = document.getElementById("search-input");
// TODO: Select all table header (th) elements in thead.
let tableHeaders = document.querySelectorAll("thead th");

let editStudentModal = document.getElementById("editStudentModal");
let editStudentForm = document.getElementById("edit-student-form");
let editStudentName = document.getElementById("edit-student-name");
let editStudentId = document.getElementById("edit-student-id");
let editStudentEmail = document.getElementById("edit-student-email");
let currentEditStudentId = null;
// --- Functions ---

/**
 * TODO: Implement the createStudentRow function.
 * This function should take a student object {name, id, email} and return a <tr> element.
 * The <tr> should contain:
 * 1. A <td> for the student's name.
 * 2. A <td> for the student's ID.
 * 3. A <td> for the student's email.
 * 4. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
 * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
 */
function createStudentRow(student) {
  let tr = document.createElement("tr");
  tr.classList.add("table-light"); // Bootstrap class for table rows

  let nameTd = document.createElement("td");
  nameTd.textContent = student.name;

  let idTd = document.createElement("td");
  idTd.textContent = student.id;

  let emailTd = document.createElement("td");
  emailTd.textContent = student.email;

  let actionsTd = document.createElement("td");

  let editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.classList.add("btn", "btn-warning", "btn-sm", "edit-btn", "me-2"); // Added margin-end for spacing
  editBtn.setAttribute("data-id", student.id);

  let deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("btn", "btn-danger", "btn-sm", "delete-btn");
  deleteBtn.setAttribute("data-id", student.id);

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(nameTd);
  tr.appendChild(idTd);
  tr.appendChild(emailTd);
  tr.appendChild(actionsTd);

  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:
 * 1. Clear the current content of the `studentTableBody`.
 * 2. Loop through the provided array of students.
 * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
 */
function renderTable(studentArray) {
  // ... your implementation here ...
  tableBody.innerHTML = "";
  studentArray.forEach((student) => {
    let row = createStudentRow(student);
    tableBody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
 * 3. Perform validation:
 * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
 * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
 * 4. If validation passes, show an alert: "Password updated successfully!"
 * 5. Clear all three password input fields.
 */
function handleChangePassword(event) {
  // ... your implementation here ...
  event.preventDefault();

  const selectedUserId = document.getElementById("select-user").value;
  const currentPassword = document.getElementById("current-password").value;
  const newPassword = document.getElementById("new-password").value;
  const confirmPassword = document.getElementById("confirm-password").value;

  if (!selectedUserId) {
    alert("Please select a user.");
    return;
  }

  if (newPassword !== confirmPassword) {
    alert("Passwords do not match.");
    return;
  }
  if (newPassword.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  // Send request to API
  fetch("api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    credentials: "same-origin",
    body: JSON.stringify({
      action: "change_password",
      student_id: selectedUserId,
      current_password: currentPassword,
      new_password: newPassword,
    }),
  })
    .then((response) => {
      return response
        .json()
        .then((data) => ({ status: response.status, ok: response.ok, data }));
    })
    .then(({ status, ok, data }) => {
      if (!ok) {
        // Check if this is an auth error (from requireApiAdmin) vs business logic error
        // Auth errors have 'success' key, business errors have 'status' key
        if (status === 401 && data.error === "UNAUTHORIZED") {
          // Session expired or not logged in
          alert(
            data.message || "Your session has expired. Please log in again."
          );
          window.location.href = "/src/auth/login.html";
          return;
        }

        if (status === 403 && data.error === "FORBIDDEN") {
          // Not admin
          alert(data.message || "Admin privileges required.");
          window.location.href = "/src/auth/login.html";
          return;
        }

        // For password change business logic errors (wrong password = 401 with 'status' key)
        if (status === 401 && data.status === "error") {
          // This is a password verification error, not an auth error
          alert(data.message);
          return;
        }

        // Handle other errors (400, 404, 500, etc.)
        alert(data.message || "Error updating password");
        return;
      }

      // Success case - check both 'status' and 'success' keys for compatibility
      if (data.status === "success" || data.success === true) {
        alert(data.message || "Password updated successfully!");
        document.getElementById("select-user").value = "";
        document.getElementById("current-password").value = "";
        document.getElementById("new-password").value = "";
        document.getElementById("confirm-password").value = "";
      } else {
        alert("Error updating password: " + (data.message || "Unknown error"));
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred while updating the password.");
    });
}

/**
 * Populate the user dropdown for password change
 */
function populateUserDropdown() {
  const selectUser = document.getElementById("select-user");
  selectUser.innerHTML = '<option value="">-- Select a student --</option>';

  students.forEach((student) => {
    const option = document.createElement("option");
    option.value = student.id || student.student_id;
    option.textContent = `${student.name} (${student.email})`;
    selectUser.appendChild(option);
  });
}

/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "student-name", "student-id", and "student-email".
 * 3. Perform validation:
 * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
 * - (Optional) Check if a student with the same ID already exists in the 'students' array.
 * 4. If validation passes:
 * - Create a new student object: { name, id, email }.
 * - Add the new student object to the global 'students' array.
 * - Call `renderTable(students)` to update the view.
 * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
 */
function handleAddStudent(event) {
  // ... your implementation here ...
  event.preventDefault();
  const name = document.getElementById("student-name").value;
  const id = document.getElementById("student-id").value;
  const email = document.getElementById("student-email").value;
  const password = document.getElementById("default-password").value;

  if (name === "" || id === "" || email === "") {
    alert("Please fill out all required fields.");
    return;
  }

  const newStudent = {
    name: name,
    student_id: id,
    email: email,
    password: password,
  };

  fetch("api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    credentials: "same-origin",
    body: JSON.stringify(newStudent),
  })
    .then((response) => {
      return response
        .json()
        .then((data) => ({ status: response.status, ok: response.ok, data }));
    })
    .then(({ status, ok, data }) => {
      if (!ok) {
        // Handle authentication/authorization errors
        if (status === 401 || status === 403) {
          alert(data.message || "Authentication required. Please log in.");
          window.location.href = "/src/auth/login.html";
          return;
        }
        // Handle other errors
        alert(data.message || "Error adding student");
        return;
      }

      // Success case
      if (data.status === "success") {
        // Refresh the list to show the new student
        loadStudentsAndInitialize();

        document.getElementById("student-name").value = "";
        document.getElementById("student-id").value = "";
        document.getElementById("student-email").value = "";
        document.getElementById("default-password").value = "";
      } else {
        alert("Error adding student: " + (data.message || "Unknown error"));
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred while adding the student.");
    });
}

/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it is a "delete-btn":
 * - Get the `data-id` attribute from the button.
 * - Update the global 'students' array by filtering out the student with the matching ID.
 * - Call `renderTable(students)` to update the view.
 * 3. (Optional) Check for "edit-btn" and implement edit logic.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  if (event.target.classList.contains("delete-btn")) {
    const studentId = event.target.getAttribute("data-id");

    if (confirm("Are you sure you want to delete this student?")) {
      fetch(`api/index.php?student_id=${studentId}`, {
        method: "DELETE",
        credentials: "same-origin",
      })
        .then((response) => {
          return response.json().then((data) => ({
            status: response.status,
            ok: response.ok,
            data,
          }));
        })
        .then(({ status, ok, data }) => {
          if (!ok) {
            // Handle authentication/authorization errors
            if (status === 401 || status === 403) {
              alert(data.message || "Authentication required. Please log in.");
              window.location.href = "/src/auth/login.html";
              return;
            }
            // Handle other errors
            alert(data.message || "Error deleting student");
            return;
          }

          // Success case
          if (data.status === "success") {
            // Refresh the list
            loadStudentsAndInitialize();
          } else {
            alert(
              "Error deleting student: " + (data.message || "Unknown error")
            );
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred while deleting the student.");
        });
    }
  }
  if (event.target.classList.contains("edit-btn")) {
    const studentId = event.target.getAttribute("data-id");
    const student = students.find(
      (s) => s.id == studentId || s.student_id == studentId
    );
    if (student) {
      // Fill modal fields
      editStudentName.value = student.name;
      editStudentId.value = student.id || student.student_id;
      editStudentEmail.value = student.email;
      currentEditStudentId = student.id || student.student_id;
      // Show modal
      let modal = new bootstrap.Modal(editStudentModal);
      modal.show();
    }
  }
}

if (editStudentForm) {
  editStudentForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const updatedName = editStudentName.value;
    const updatedEmail = editStudentEmail.value;
    const studentId = currentEditStudentId;
    fetch("api/index.php", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({
        student_id: studentId,
        name: updatedName,
        email: updatedEmail,
      }),
    })
      .then((response) => {
        return response
          .json()
          .then((data) => ({ status: response.status, ok: response.ok, data }));
      })
      .then(({ status, ok, data }) => {
        if (!ok) {
          // Handle authentication/authorization errors
          if (status === 401 || status === 403) {
            alert(data.message || "Authentication required. Please log in.");
            window.location.href = "/src/auth/login.html";
            return;
          }
          // Handle other errors
          Swal.fire({
            icon: "error",
            title: "Error updating student",
            text: data.message || "Unknown error",
          });
          return;
        }

        // Success case
        if (data.status === "success") {
          // Hide modal
          let modal = bootstrap.Modal.getInstance(editStudentModal);
          modal.hide();
          // Refresh table
          loadStudentsAndInitialize();
        } else {
          Swal.fire({
            icon: "error",
            title: "Error updating student",
            text: data.message || "Unknown error",
          });
        }
      })
      .catch((error) => {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "An error occurred while updating the student.",
        });
      });
  });
}

/**
 * TODO: Implement the handleSearch function.
 * This function will be called on the "input" event of the `searchInput`.
 * It should:
 * 1. Get the search term from `searchInput.value` and convert it to lowercase.
 * 2. If the search term is empty, call `renderTable(students)` to show all students.
 * 3. If the search term is not empty:
 * - Filter the global 'students' array to find students whose name (lowercase)
 * includes the search term.
 * - Call `renderTable` with the *filtered array*.
 */
function handleSearch(event) {
  // ... your implementation here ...
  const searchTerm = searchInput.value.trim();

  let url = "api/index.php";
  if (searchTerm !== "") {
    url += `?search=${encodeURIComponent(searchTerm)}`;
  }

  fetch(url)
    .then((response) => response.json())
    .then((jsonResponse) => {
      students = jsonResponse.data || [];
      renderTable(students);
    })
    .catch((error) => console.error("Error searching students:", error));
}

/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:
 * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
 * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
 * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
 * to track the current direction. Toggle between "asc" and "desc".
 * 4. Sort the global 'students' array *in place* using `array.sort()`.
 * - For 'name' and 'email', use `localeCompare` for string comparison.
 * - For 'id', compare the values as numbers.
 * 5. Respect the sort direction (ascending or descending).
 * 6. After sorting, call `renderTable(students)` to update the view.
 */
function handleSort(event) {
  // ... your implementation here ...
  const th = event.currentTarget;
  const columnIndex = th.cellIndex;
  // Prevent sorting for Actions column (index 3)
  if (columnIndex === 3) {
    return;
  }
  let sortProperty;

  if (columnIndex === 0) {
    sortProperty = "name";
  } else if (columnIndex === 1) {
    sortProperty = "id"; // Changed from 'id' to match DB column/API param
  } else {
    sortProperty = "email";
  }

  const currentSortDir = th.getAttribute("data-sort-dir") || "asc";
  const newSortDir = currentSortDir === "asc" ? "desc" : "asc";

  // Remove sort indicators from all headers
  tableHeaders.forEach((header) => {
    if (header.cellIndex !== 3) {
      // Skip Actions column
      const text = header.textContent.replace(/ ▲| ▼/g, "").trim();
      header.textContent = text;
      header.style.cursor = "pointer";
    }
  });

  // Add sort indicator to current header
  const headerText = th.textContent.replace(/ ▲| ▼/g, "").trim();
  th.textContent = headerText + (newSortDir === "asc" ? " ▲" : " ▼");

  // Update UI state
  th.setAttribute("data-sort-dir", newSortDir);

  // Fetch sorted data from API
  fetch(`api/index.php?sort=${sortProperty}&order=${newSortDir}`)
    .then((response) => response.json())
    .then((jsonResponse) => {
      students = jsonResponse.data || [];
      renderTable(students);
    })
    .catch((error) => console.error("Error sorting students:", error));
}

/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use the `fetch()` API to get data from 'students.json'.
 * 2. Check if the response is 'ok'. If not, log an error.
 * 3. Parse the JSON response (e.g., `await response.json()`).
 * 4. Assign the resulting array to the global 'students' variable.
 * 5. Call `renderTable(students)` to populate the table for the first time.
 * 6. After data is loaded, set up all the event listeners:
 * - "submit" on `changePasswordForm` -> `handleChangePassword`
 * - "submit" on `addStudentForm` -> `handleAddStudent`
 * - "click" on `studentTableBody` -> `handleTableClick`
 * - "input" on `searchInput` -> `handleSearch`
 * - "click" on each header in `tableHeaders` -> `handleSort`
 */
async function loadStudentsAndInitialize() {
  // ... your implementation here ...
  try {
    const response = await fetch("api/index.php", {
      credentials: "same-origin",
    });

    // Check for authentication errors
    if (response.status === 401 || response.status === 403) {
      alert(
        "Admin access required. Please log in with an admin account to access student management."
      );
      window.location.href = "/src/auth/login.html";
      return;
    }

    if (!response.ok) {
      console.error("Failed to fetch students from API");
      return;
    }
    const jsonResponse = await response.json();
    students = jsonResponse.data || [];
    renderTable(students);
    populateUserDropdown();

    changePasswordForm.addEventListener("submit", handleChangePassword);
    addStudentForm.addEventListener("submit", handleAddStudent);
    tableBody.addEventListener("click", handleTableClick);
    searchInput.addEventListener("input", handleSearch);
    tableHeaders.forEach((th) => th.addEventListener("click", handleSort));

    // Setup password visibility toggle buttons
    setupPasswordToggle("toggle-current-password", "current-password");
    setupPasswordToggle("toggle-new-password", "new-password");
    setupPasswordToggle("toggle-confirm-password", "confirm-password");
  } catch (error) {
    console.error("An error occurred:", error);
  }
}

/**
 * Setup password visibility toggle
 */
function setupPasswordToggle(buttonId, inputId) {
  const toggleButton = document.getElementById(buttonId);
  const passwordInput = document.getElementById(inputId);

  if (toggleButton && passwordInput) {
    toggleButton.addEventListener("click", function () {
      const icon = this.querySelector("i");
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
      } else {
        passwordInput.type = "password";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
      }
    });
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadStudentsAndInitialize();

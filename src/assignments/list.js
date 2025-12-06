/*
  Requirement: Populate the "Course Assignments" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="assignment-list-section"` to the
     <section> element that will contain the assignment articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the assignment list ('#assignment-list-section').
const listSection = document.querySelector("#assignment-list-section");
// --- Functions ---

/**
 * TODO: Implement the createAssignmentArticle function.
 * It takes one assignment object {id, title, dueDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Details" link's `href` MUST be set to `details.html?id=${id}`.
 * This is how the detail page will know which assignment to load.
 */
function createAssignmentArticle(assignment) {
  // ... your implementation here ...
  const { id, title, dueDate, description } = assignment;

  const article = document.createElement("article");
  article.classList.add("card", "p-4", "mb-3");

  // Create elements to safely insert text content
  const h2 = document.createElement("h2");
  h2.textContent = title;

  const duePara = document.createElement("p");
  duePara.innerHTML = `<strong>Due:</strong> ${dueDate}`;

  const descPara = document.createElement("p");
  descPara.textContent = description; // Use textContent to prevent HTML injection

  const link = document.createElement("a");
  link.href = `details.html?id=${id}`;
  link.className = "btn btn-primary btn-sm";
  link.textContent = "View Details & Discussion";

  article.appendChild(h2);
  article.appendChild(duePara);
  article.appendChild(descPara);
  article.appendChild(link);

  return article;
}

/**
 * TODO: Implement the loadAssignments function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the assignments array. For each assignment:
 * - Call `createAssignmentArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadAssignments() {
  // ... your implementation here ...
  try {
    const response = await fetch("assignments.json");
    const assignments = await response.json();

    listSection.innerHTML = "";

    assignments.forEach((assignment) => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });
  } catch (error) {
    console.error("Error loading assignments:", error);
    listSection.innerHTML = "<p>Failed to load assignments.</p>";
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadAssignments();

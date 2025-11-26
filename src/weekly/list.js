/*
 Requirement: Populate the "Weekly Course Breakdown" list page.
 Instructions:
 1. Link this file to `list.html` using:
<script src="list.js" defer></script>
 2. In `list.html`, add an id="week-list-section" to the
<section> element that will contain the weekly articles.
 3. Implement the TODOs below.
*/
// --- Element Selections ---
// TODO: Select the section for the week list ('#week-list-section').
const listSection = document.querySelector("#week-list-section");
// --- Functions ---
/**
* TODO: Implement the createWeekArticle function.
* It takes one week object {id, title, startDate, description}.
* It should return an <article> element matching the structure in `list.html`.
* - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
*/
function createWeekArticle(week) {
   const article = document.createElement("article");
   article.classList.add("week");
   // Week title
   const h2 = document.createElement("h2");
   h2.textContent = week.title;
   // Start date
   const dateP = document.createElement("p");
   dateP.textContent = `Starts on: ${week.startDate}`;
   // Description
   const descP = document.createElement("p");
   descP.textContent = week.description;
   // Link to details page
   const link = document.createElement("a");
   link.href = `details.html?id=${week.id}`;
   link.textContent = "View Details & Discussion";
   // Append everything
   article.appendChild(h2);
   article.appendChild(dateP);
   article.appendChild(descP);
   article.appendChild(link);
   return article;
}
/**
* TODO: Implement the loadWeeks function.
* This function needs to be 'async'.
* It should:
* 1. Use fetch() to get data from 'weeks.json'.
* 2. Parse the JSON response into an array.
* 3. Clear any existing content from `listSection`.
* 4. Loop through the weeks array. For each week:
*    - Call createWeekArticle().
*    - Append the returned <article> element to `listSection`.
*/
async function loadWeeks() {
   try {
       const response = await fetch("weeks.json");
       const weeks = await response.json();
       // Clear old content
       listSection.innerHTML = "";
       // Loop & display weeks
       weeks.forEach(week => {
           const article = createWeekArticle(week);
           listSection.appendChild(article);
       });
   } catch (error) {
       console.error("Error loading weeks:", error);
   }
}
// --- Initial Page Load ---
loadWeeks();
// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();

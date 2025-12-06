/*
 Requirement: Populate the "Weekly Course Breakdown" list page.
 Instructions:
 1. Link this file to `list.html` using:
<script src="list.js" defer></script>
 2. In `list.html`, add an `id="week-list-section"` to the
<section> element that will contain the weekly articles.
 3. Implement the TODOs below.
*/
// --- Element Selections ---
// TODO: Select the section for the week list ('#week-list-section').
const listSection = document.querySelector('#week-list-section');
// --- Functions ---
/**
* TODO: Implement the createWeekArticle function.
* It takes one week object {id, title, startDate, description}.
* It should return an <article> element matching the structure in `list.html`.
* - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
* (This is how the detail page will know which week to load).
*/
function createWeekArticle(week) {
 const { id, title, startDate, description } = week;
 // Create article container
 const article = document.createElement('article');
 // Week title
 const heading = document.createElement('h2');
 heading.textContent = title;
 // Start date
 const dateParagraph = document.createElement('p');
 dateParagraph.textContent = 'Starts on: ' + startDate;
 // Description
 const descriptionParagraph = document.createElement('p');
 descriptionParagraph.textContent = description;
 // Details link
 const link = document.createElement('a');
 link.href = `details.html?id=${id}`;
 link.textContent = 'View Details & Discussion';
 // Append to article
 article.appendChild(heading);
 article.appendChild(dateParagraph);
 article.appendChild(descriptionParagraph);
 article.appendChild(link);
 return article;
}
/**
* TODO: Implement the loadWeeks function.
* This function needs to be 'async'.
* It should:
* 1. Use `fetch()` to get data from 'weeks.json'.
* 2. Parse the JSON response into an array.
* 3. Clear any existing content from `listSection`.
* 4. Loop through the weeks array. For each week:
* - Call `createWeekArticle()`.
* - Append the returned <article> element to `listSection`.
*/
async function loadWeeks() {
 try {
   const response = await fetch('weeks.json');
   // If fetch fails or not OK, just clear and exit
   if (!response.ok) {
     listSection.innerHTML = '';
     return;
   }
   const weeks = await response.json();
   // Clear existing content
   listSection.innerHTML = '';
   // Create and append an article for each week
   weeks.forEach((week) => {
     const article = createWeekArticle(week);
     listSection.appendChild(article);
   });
 } catch (error) {
   // On error, clear section (could also show an error message if desired)
   listSection.innerHTML = '';
 }
}
// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();

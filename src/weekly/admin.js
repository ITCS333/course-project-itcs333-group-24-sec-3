/*
 Requirement: Make the "Manage Weekly Breakdown" page interactive.
*/
let weeks = []; // global data
// ---- Element Selections ----
const weekForm = document.querySelector("#week-form");
const weeksTableBody = document.querySelector("#weeks-tbody");
// ---- Functions ----
// Create one table row
function createWeekRow(week) {
   const tr = document.createElement("tr");
   const titleTd = document.createElement("td");
   titleTd.textContent = week.title;
   const descTd = document.createElement("td");
   descTd.textContent = week.description;
   const actionsTd = document.createElement("td");
   const editBtn = document.createElement("button");
   editBtn.textContent = "Edit";
   editBtn.classList.add("edit-btn");
editBtn.dataset.id = week.id;
   const deleteBtn = document.createElement("button");
   deleteBtn.textContent = "Delete";
   deleteBtn.classList.add("delete-btn");
deleteBtn.dataset.id = week.id;
   actionsTd.appendChild(editBtn);
   actionsTd.appendChild(deleteBtn);
   tr.appendChild(titleTd);
   tr.appendChild(descTd);
   tr.appendChild(actionsTd);
   return tr;
}
// Re-render the table
function renderTable() {
   weeksTableBody.innerHTML = ""; // clear old data
   weeks.forEach((week) => {
       const row = createWeekRow(week);
       weeksTableBody.appendChild(row);
   });
}
// Handle Add Week form submit
function handleAddWeek(event) {
   event.preventDefault();
   const title = document.querySelector("#week-title").value;
   const startDate = document.querySelector("#week-start-date").value;
   const desc = document.querySelector("#week-description").value;
   const linksText = document.querySelector("#week-links").value;
   const linksArray = linksText.split("\n").map(l => l.trim()).filter(l => l !== "");
   const newWeek = {
       id: `week_${Date.now()}`,
       title: title,
       startDate: startDate,
       description: desc,
       links: linksArray
   };
   weeks.push(newWeek);
   renderTable();
   weekForm.reset();
}
// Handle delete button click
function handleTableClick(event) {
   if (event.target.classList.contains("delete-btn")) {
       const id = event.target.dataset.id;
       weeks = weeks.filter(week => week.id !== id);
       renderTable();
   }
}
// Load JSON + initialize listeners
async function loadAndInitialize() {
   try {
       const response = await fetch("weeks.json");
       const data = await response.json();
       weeks = data;
       renderTable();
       weekForm.addEventListener("submit", handleAddWeek);
       weeksTableBody.addEventListener("click", handleTableClick);
   } catch (error) {
       console.error("Error loading weeks.json:", error);
   }
}
// Start everything
loadAndInitialize();

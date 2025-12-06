/*
 Requirement: Make the "Manage Weekly Breakdown" page interactive.
 Instructions:
 1. Link this file to `admin.html` using:
<script src="admin.js" defer></script>
 2. In `admin.html`, add an `id="weeks-tbody"` to the <tbody> element.
 3. Implement all TODOs below.
*/
// ----------------------------
// Global Data Store
// ----------------------------
let weeks = []; // will hold data loaded from weeks.json
// ----------------------------
// Element Selectors
// ----------------------------
const weekForm = document.querySelector('#week-form');        // Week form
const weeksTableBody = document.querySelector('#weeks-tbody'); // Table body

// ----------------------------
// Create a table row for a week
// ----------------------------
function createWeekRow(week) {
   const { id, title, description } = week;
   const tr = document.createElement('tr');
   // Title cell
   const titleTd = document.createElement('td');
   titleTd.textContent = title;
   // Description cell
   const descTd = document.createElement('td');
   descTd.textContent = description;
   // Actions cell
   const actionsTd = document.createElement('td');
   // Edit button
   const editBtn = document.createElement('button');
   editBtn.textContent = 'Edit';
   editBtn.classList.add('edit-btn');
editBtn.dataset.id = id;
   // Delete button
   const deleteBtn = document.createElement('button');
   deleteBtn.textContent = 'Delete';
   deleteBtn.classList.add('delete-btn');
deleteBtn.dataset.id = id;
   // Append buttons to actions cell
   actionsTd.appendChild(editBtn);
   actionsTd.appendChild(deleteBtn);
   // Append all <td> to row
   tr.appendChild(titleTd);
   tr.appendChild(descTd);
   tr.appendChild(actionsTd);
   return tr;
}

// ----------------------------
// Render the table from weeks[]
// ----------------------------
function renderTable() {
   weeksTableBody.innerHTML = ''; // clear existing rows
   weeks.forEach(week => {
       const row = createWeekRow(week);
       weeksTableBody.appendChild(row);
   });
}

// ----------------------------
// Handle adding a new week
// ----------------------------
function handleAddWeek(event) {
   event.preventDefault();
   const title = document.querySelector('#week-title').value.trim();
   const startDate = document.querySelector('#week-start-date').value.trim();
   const description = document.querySelector('#week-description').value.trim();
   const linksRaw = document.querySelector('#week-links').value;
   const links = linksRaw
       .split('\n')
       .map(line => line.trim())
       .filter(line => line.length > 0);
   const newWeek = {
       id: `week_${Date.now()}`,
       title,
       startDate,
       description,
       links
   };
   weeks.push(newWeek);       // save in memory
   renderTable();             // refresh table
   weekForm.reset();          // clear form
}

// ----------------------------
// Handle Delete Button Click
// ----------------------------
function handleTableClick(event) {
   const element = event.target;
   if (!element.classList.contains('delete-btn')) return;
   const idToDelete = element.dataset.id;
   weeks = weeks.filter(week => week.id !== idToDelete);
   renderTable();
}

// ----------------------------
// Load JSON + Initialize Page
// ----------------------------
async function loadAndInitialize() {
   try {
       const res = await fetch('weeks.json');
       if (res.ok) {
           const data = await res.json();
           if (Array.isArray(data)) {
               weeks = data;
           }
       }
   } catch (err) {
       weeks = [];
   }
   renderTable();
   weekForm.addEventListener('submit', handleAddWeek);
   weeksTableBody.addEventListener('click', handleTableClick);
}

// ----------------------------
// Start App
// ----------------------------
loadAndInitialize();

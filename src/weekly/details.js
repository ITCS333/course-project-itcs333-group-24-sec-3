// --- Global Data Store ---
let currentWeekId = null;
let currentComments = [];
// --- Element Selections ---
const weekTitle = document.getElementById("week-title");
const weekStartDate = document.getElementById("week-start-date");
const weekDescription = document.getElementById("week-description");
const weekLinksList = document.getElementById("week-links-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");
// --- Functions ---
// Get week ID from URL
function getWeekIdFromURL() {
   const query = window.location.search;
   const params = new URLSearchParams(query);
   const id = params.get("id");
   return id;
}
// Render week details (title, date, description, links)
function renderWeekDetails(week) {
   weekTitle.textContent = week.title;
   weekStartDate.textContent = "Starts on: " + week.startDate;
   weekDescription.textContent = week.description;
   // Clear old links
   weekLinksList.innerHTML = "";
   week.links.forEach(link => {
       const li = document.createElement("li");
       const a = document.createElement("a");
       a.href = link;
       a.textContent = link;
       li.appendChild(a);
       weekLinksList.appendChild(li);
   });
}
// Create one comment element
function createCommentArticle(comment) {
   const article = document.createElement("article");
   article.classList.add("comment");
   const p = document.createElement("p");
   p.textContent = comment.text;
   const footer = document.createElement("footer");
   footer.textContent = "Posted by: " + comment.author;
   article.appendChild(p);
   article.appendChild(footer);
   return article;
}
// Render all comments
function renderComments() {
   commentList.innerHTML = "";
   currentComments.forEach(c => {
       const el = createCommentArticle(c);
       commentList.appendChild(el);
   });
}
// Handle adding a new comment
function handleAddComment(event) {
   event.preventDefault();
   const text = newCommentText.value.trim();
   if (text === "") return;
   const newComment = {
       author: "Student",
       text: text
   };
   currentComments.push(newComment);
   renderComments();
   newCommentText.value = "";
}
// Initialize the page
async function initializePage() {
   currentWeekId = getWeekIdFromURL();
   if (!currentWeekId) {
       weekTitle.textContent = "Week not found.";
       return;
   }
   try {
       const [weeksRes, commentsRes] = await Promise.all([
           fetch("weeks.json"),
           fetch("week-comments.json")
       ]);
       const weeks = await weeksRes.json();
       const commentsData = await commentsRes.json();
       const selectedWeek = weeks.find(w => w.id == currentWeekId);
       currentComments = commentsData[currentWeekId] || [];
       if (selectedWeek) {
           renderWeekDetails(selectedWeek);
           renderComments();
           commentForm.addEventListener("submit", handleAddComment);
       } else {
           weekTitle.textContent = "Week not found.";
       }
   } catch (err) {
       weekTitle.textContent = "Error loading data.";
       console.log(err);
   }
}
// --- Initial Page Load ---
initializePage();

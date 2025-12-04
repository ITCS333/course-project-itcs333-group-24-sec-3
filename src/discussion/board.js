/*
  Requirement: Make the "Discussion Board" page interactive.

  Instructions:
  1. Link this file to `board.html` (or `baord.html`) using:
     <script src="board.js" defer></script>
  
  2. In `board.html`, add an `id="topic-list-container"` to the 'div'
     that holds the list of topic articles.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the topics loaded from the JSON file.
let topics = [];

// --- Element Selections ---
// TODO: Select the new topic form ('#new-topic-form').
const newTopicForm = document.querySelector('#new-topic-form');

// TODO: Select the topic list container ('#topic-list-container').
const topicListContainer = document.querySelector('#topic-list-container');

// --- Functions ---

/**
 * TODO: Implement the createTopicArticle function.
 * It takes one topic object {id, subject, author, date}.
 * It should return an <article> element matching the structure in `board.html`.
 * - The main link's `href` MUST be `topic.html?id=${id}`.
 * - The footer should contain the author and date.
 * - The actions div should contain an "Edit" button and a "Delete" button.
 * - The "Delete" button should have a class "delete-btn" and `data-id="${id}"`.
 */
function createTopicArticle(topic) {
  // Create article element
  const article = document.createElement('article');
  
  // Create h3 with link
  const h3 = document.createElement('h3');
  const link = document.createElement('a');
  link.href = `topic.html?id=${topic.id}`;
  link.textContent = topic.subject;
  h3.appendChild(link);
  article.appendChild(h3);
  
  // Create footer with metadata
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${topic.author} on ${topic.date}`;
  article.appendChild(footer);
  
  // Create actions container with Edit and Delete buttons
  const actionsDiv = document.createElement('div');
  const editButton = document.createElement('button');
  editButton.textContent = 'Edit';
  const deleteButton = document.createElement('button');
  deleteButton.textContent = 'Delete';
  deleteButton.className = 'delete-btn';
  deleteButton.setAttribute('data-id', topic.id);
  actionsDiv.appendChild(editButton);
  actionsDiv.appendChild(deleteButton);
  article.appendChild(actionsDiv);
  
  return article;
}

/**
 * TODO: Implement the renderTopics function.
 * It should:
 * 1. Clear the `topicListContainer`.
 * 2. Loop through the global `topics` array.
 * 3. For each topic, call `createTopicArticle()`, and
 * append the resulting <article> to `topicListContainer`.
 */
function renderTopics() {
  // Clear the container
  topicListContainer.innerHTML = '';
  
  // Loop through topics and append each article
  topics.forEach(topic => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

/**
 * TODO: Implement the handleCreateTopic function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the '#topic-subject' and '#topic-message' inputs.
 * 3. Create a new topic object with the structure:
 * {
 * id: `topic_${Date.now()}`,
 * subject: (subject value),
 * message: (message value),
 * author: 'Student' (use a hardcoded author for this exercise),
 * date: new Date().toISOString().split('T')[0] // Gets today's date YYYY-MM-DD
 * }
 * 4. Add this new topic object to the global `topics` array (in-memory only).
 * 5. Call `renderTopics()` to refresh the list.
 * 6. Reset the form.
 */
function handleCreateTopic(event) {
  // Prevent default form submission
  event.preventDefault();
  
  // Get form values
  const subjectInput = document.querySelector('#topic-subject');
  const messageInput = document.querySelector('#topic-message');
  const subject = subjectInput.value.trim();
  const message = messageInput.value.trim();
  
  // Validate inputs
  if (!subject || !message) {
    return;
  }
  
  // Create new topic object
  const newTopic = {
    id: `topic_${Date.now()}`,
    subject: subject,
    message: message,
    author: 'Student',
    date: new Date().toISOString().split('T')[0]
  };
  
  // Add to topics array
  topics.push(newTopic);
  
  // Refresh the list
  renderTopics();
  
  // Reset the form
  newTopicForm.reset();
}

/**
 * TODO: Implement the handleTopicListClick function.
 * This is an event listener on the `topicListContainer` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `topics` array by filtering out the topic
 * with the matching ID (in-memory only).
 * 4. Call `renderTopics()` to refresh the list.
 */
function handleTopicListClick(event) {
  // Check if clicked element is a delete button
  if (event.target.classList.contains('delete-btn')) {
    // Get the topic id from data attribute
    const topicId = event.target.getAttribute('data-id');
    
    // Filter out the topic with matching id
    topics = topics.filter(topic => topic.id !== topicId);
    
    // Refresh the list
    renderTopics();
  }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'topics.json'.
 * 2. Parse the JSON response and store the result in the global `topics` array.
 * 3. Call `renderTopics()` to populate the list for the first time.
 * 4. Add the 'submit' event listener to `newTopicForm` (calls `handleCreateTopic`).
 * 5. Add the 'click' event listener to `topicListContainer` (calls `handleTopicListClick`).
 */
async function loadAndInitialize() {
  try {
    // Fetch topics from JSON file
    const response = await fetch('api/topics.json');
    
    // Check if response is ok
    if (!response.ok) {
      throw new Error('Failed to load topics');
    }
    
    // Parse JSON and store in global array
    topics = await response.json();
    
    // Render topics for the first time
    renderTopics();
    
    // Add event listeners
    newTopicForm.addEventListener('submit', handleCreateTopic);
    topicListContainer.addEventListener('click', handleTopicListClick);
  } catch (error) {
    console.error('Error loading topics:', error);
    // Display error message in the container
    topicListContainer.innerHTML = '<p>Error loading topics. Please try again later.</p>';
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();

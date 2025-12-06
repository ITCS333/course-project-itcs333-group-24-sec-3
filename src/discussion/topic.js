/*
  Requirement: Populate the single topic page and manage replies.

  Instructions:
  1. Link this file to `topic.html` using:
     <script src="topic.js" defer></script>

  2. In `topic.html`, add the following IDs:
     - To the <h1>: `id="topic-subject"`
     - To the <article id="original-post">:
       - Add a <p> with `id="op-message"` for the message text.
       - Add a <footer> with `id="op-footer"` for the metadata.
     - To the <div> for the list of replies: `id="reply-list-container"`
     - To the "Post a Reply" <form>: `id="reply-form"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = []; // Will hold replies for *this* topic

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
const topicSubject = document.querySelector('#topic-subject');
const opMessage = document.querySelector('#op-message');
const opFooter = document.querySelector('#op-footer');
const replyListContainer = document.querySelector('#reply-list-container');
const replyForm = document.querySelector('#reply-form');
const newReplyText = document.querySelector('#new-reply');

// --- Functions ---

/**
 * TODO: Implement the getTopicIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getTopicIdFromURL() {
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  return urlParams.get('id');
}

/**
 * TODO: Implement the renderOriginalPost function.
 * It takes one topic object.
 * It should:
 * 1. Set the `textContent` of `topicSubject` to the topic's subject.
 * 2. Set the `textContent` of `opMessage` to the topic's message.
 * 3. Set the `textContent` of `opFooter` to "Posted by: {author} on {date}".
 * 4. (Optional) Add a "Delete" button with `data-id="${topic.id}"` to the OP.
 */
function renderOriginalPost(topic) {
  topicSubject.textContent = topic.subject;
  opMessage.textContent = topic.message;
  opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;
}

/**
 * TODO: Implement the createReplyArticle function.
 * It takes one reply object {id, author, date, text}.
 * It should return an <article> element matching the structure in `topic.html`.
 * - Include a <p> for the `text`.
 * - Include a <footer> for the `author` and `date`.
 * - Include a "Delete" button with class "delete-reply-btn" and `data-id="${id}"`.
 */
function createReplyArticle(reply) {
  // Create article element
  const article = document.createElement('article');
  
  // Create paragraph for reply text
  const p = document.createElement('p');
  p.textContent = reply.text;
  article.appendChild(p);
  
  // Create footer for author and date
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${reply.author} on ${reply.date}`;
  article.appendChild(footer);
  
  // Create actions container with Edit and Delete buttons
  const actionsDiv = document.createElement('div');
  const editButton = document.createElement('button');
  editButton.textContent = 'Edit';
  const deleteButton = document.createElement('button');
  deleteButton.textContent = 'Delete';
  deleteButton.className = 'delete-reply-btn';
  deleteButton.setAttribute('data-id', reply.id);
  actionsDiv.appendChild(editButton);
  actionsDiv.appendChild(deleteButton);
  article.appendChild(actionsDiv);
  
  return article;
}

/**
 * TODO: Implement the renderReplies function.
 * It should:
 * 1. Clear the `replyListContainer`.
 * 2. Loop through the global `currentReplies` array.
 * 3. For each reply, call `createReplyArticle()`, and
 * append the resulting <article> to `replyListContainer`.
 */
function renderReplies() {
  // Clear the container
  replyListContainer.innerHTML = '';
  
  // Loop through replies and append each article
  currentReplies.forEach(reply => {
    const article = createReplyArticle(reply);
    replyListContainer.appendChild(article);
  });
}

/**
 * TODO: Implement the handleAddReply function.
 * This is the event handler for the `replyForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newReplyText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new reply object:
 * {
 * id: `reply_${Date.now()}`,
 * author: 'Student' (hardcoded),
 * date: new Date().toISOString().split('T')[0],
 * text: (reply text value)
 * }
 * 5. Add this new reply to the global `currentReplies` array (in-memory only).
 * 6. Call `renderReplies()` to refresh the list.
 * 7. Clear the `newReplyText` textarea.
 */
function handleAddReply(event) {
  // Prevent default form submission
  event.preventDefault();
  
  // Get reply text
  const text = newReplyText.value.trim();
  
  // If text is empty, return
  if (!text) {
    return;
  }
  
  // Create new reply object
  const newReply = {
    id: `reply_${Date.now()}`,
    author: 'Student',
    date: new Date().toISOString().split('T')[0],
    text: text
  };
  
  // Add to currentReplies array
  currentReplies.push(newReply);
  
  // Refresh the list
  renderReplies();
  
  // Clear the textarea
  newReplyText.value = '';
}

/**
 * TODO: Implement the handleReplyListClick function.
 * This is an event listener on the `replyListContainer` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-reply-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `currentReplies` array by filtering out the reply
 * with the matching ID (in-memory only).
 * 4. Call `renderReplies()` to refresh the list.
 */
function handleReplyListClick(event) {
  // Check if clicked element is a delete reply button
  if (event.target.classList.contains('delete-reply-btn')) {
    // Get the reply id from data attribute
    const replyId = event.target.getAttribute('data-id');
    
    // Filter out the reply with matching id
    currentReplies = currentReplies.filter(reply => reply.id !== replyId);
    
    // Refresh the list
    renderReplies();
  }
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentTopicId` by calling `getTopicIdFromURL()`.
 * 2. If no ID is found, set `topicSubject.textContent = "Topic not found."` and stop.
 * 3. `fetch` both 'topics.json' and 'replies.json' (you can use `Promise.all`).
 * 4. Parse both JSON responses.
 * 5. Find the correct topic from the topics array using the `currentTopicId`.
 * 6. Get the correct replies array from the replies object using the `currentTopicId`.
 * Store this in the global `currentReplies` variable. (If no replies exist, use an empty array).
 * 7. If the topic is found:
 * - Call `renderOriginalPost()` with the topic object.
 * - Call `renderReplies()` to show the initial replies.
 * - Add the 'submit' event listener to `replyForm` (calls `handleAddReply`).
 * - Add the 'click' event listener to `replyListContainer` (calls `handleReplyListClick`).
 * 8. If the topic is not found, display an error in `topicSubject`.
 */
async function initializePage() {
  // Get topic ID from URL
  currentTopicId = getTopicIdFromURL();
  
  // If no ID found, display error and stop
  if (!currentTopicId) {
    topicSubject.textContent = 'Topic not found.';
    return;
  }
  
  try {
    // Fetch both topics.json and comments.json (replies)
    const [topicsResponse, repliesResponse] = await Promise.all([
      fetch('api/topics.json'),
      fetch('api/comments.json')
    ]);
    
    // Check if responses are ok
    if (!topicsResponse.ok || !repliesResponse.ok) {
      throw new Error('Failed to load data');
    }
    
    // Parse both JSON responses
    const topics = await topicsResponse.json();
    const replies = await repliesResponse.json();
    
    // Find the correct topic from the topics array
    const topic = topics.find(t => t.id === currentTopicId);
    
    // If topic not found, display error
    if (!topic) {
      topicSubject.textContent = 'Topic not found.';
      return;
    }
    
    // Get the replies array for this topic (or empty array if none exist)
    currentReplies = replies[currentTopicId] || [];
    
    // Render the original post
    renderOriginalPost(topic);
    
    // Render the replies
    renderReplies();
    
    // Add event listeners
    replyForm.addEventListener('submit', handleAddReply);
    replyListContainer.addEventListener('click', handleReplyListClick);
  } catch (error) {
    console.error('Error initializing page:', error);
    topicSubject.textContent = 'Error loading topic. Please try again later.';
  }
}

// --- Initial Page Load ---
initializePage();

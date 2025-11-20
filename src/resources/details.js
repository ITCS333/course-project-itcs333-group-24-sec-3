const API_ENDPOINT = 'api/index.php';
const resourceTitle = document.querySelector('#resource-title');
const resourceSummary = document.querySelector('#resource-summary');
const resourceDescription = document.querySelector('#resource-description');
const resourceLink = document.querySelector('#resource-link');
const resourceCreatedAt = document.querySelector('#resource-created-at');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const commentAuthorInput = document.querySelector('#comment-author');
const commentTextarea = document.querySelector('#new-comment');
const commentFeedback = document.querySelector('#comment-feedback');
const commentCountBadge = document.querySelector('#comment-count');

let currentResourceId = null;
let resourceData = null;
let currentComments = [];

const escapeHtml = (value = '') =>
  String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

const formatDateTime = (value) => {
  if (!value) {
    return '';
  }
  const date = new Date(value);
  return date.toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const showResourceError = (message) => {
  if (resourceTitle) {
    resourceTitle.textContent = message;
  }
  if (resourceDescription) {
    resourceDescription.textContent = 'Please return to the resources list and try again.';
  }
  if (resourceLink) {
    resourceLink.classList.add('disabled');
    resourceLink.setAttribute('aria-disabled', 'true');
    resourceLink.removeAttribute('href');
  }
};

const updateCommentCount = () => {
  if (!commentCountBadge) {
    return;
  }

  const count = currentComments.length;
  commentCountBadge.textContent = `${count} comment${count === 1 ? '' : 's'}`;
};

const showCommentAlert = (message, type = 'info') => {
  if (!commentFeedback) {
    return;
  }

  commentFeedback.textContent = message;
  commentFeedback.className = `alert alert-${type}`;
};

const clearCommentAlert = () => {
  if (!commentFeedback) {
    return;
  }

  commentFeedback.classList.add('d-none');
  commentFeedback.textContent = '';
};

const request = async (url, options = {}) => {
  const headers = {
    Accept: 'application/json',
    ...(options.headers || {}),
  };

  if (options.body && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(url, { ...options, headers });
  const payload = await response.json().catch(() => ({
    success: false,
    message: 'Unable to parse server response.',
  }));

  if (!response.ok || payload.success === false) {
    throw new Error(payload.message || 'Request failed.');
  }

  return payload;
};

const renderResourceDetails = (resource) => {
  if (resourceTitle) {
    resourceTitle.textContent = resource.title;
  }

  if (resourceDescription) {
    resourceDescription.textContent = resource.description || 'No description provided for this resource.';
  }

  if (resourceSummary) {
    try {
      const hostname = new URL(resource.link).hostname.replace('www.', '');
      resourceSummary.textContent = `Hosted on ${hostname}`;
    } catch (error) {
      resourceSummary.textContent = 'Online resource';
    }
  }

  if (resourceLink) {
    resourceLink.href = resource.link;
    resourceLink.classList.remove('disabled');
    resourceLink.removeAttribute('aria-disabled');
  }

  if (resourceCreatedAt) {
    resourceCreatedAt.textContent = formatDateTime(resource.created_at);
  }
};

const createCommentArticle = (comment) => {
  const article = document.createElement('article');
  article.className = 'border rounded-3 p-3 mb-3';
  article.innerHTML = `
    <p class="mb-2">${escapeHtml(comment.text)}</p>
    <footer class="text-muted small d-flex justify-content-between flex-wrap gap-2">
      <span>Posted by: ${escapeHtml(comment.author)}</span>
      <span>${formatDateTime(comment.created_at)}</span>
    </footer>
  `;
  return article;
};

const renderComments = () => {
  if (!commentList) {
    return;
  }

  commentList.innerHTML = '';

  if (!currentComments.length) {
    commentList.innerHTML = `
      <div class="alert alert-light border mb-0" role="alert">
        No comments yet. Be the first to start the discussion!
      </div>
    `;
    updateCommentCount();
    return;
  }

  currentComments.forEach((comment) => {
    commentList.appendChild(createCommentArticle(comment));
  });
  updateCommentCount();
};

const loadResource = async () => {
  if (!currentResourceId) {
    return;
  }

  const { data } = await request(`${API_ENDPOINT}?id=${encodeURIComponent(currentResourceId)}`);
  resourceData = data;
  renderResourceDetails(resourceData);
};

const loadComments = async () => {
  if (!currentResourceId) {
    return;
  }

  const { data } = await request(
    `${API_ENDPOINT}?action=comments&resource_id=${encodeURIComponent(currentResourceId)}`
  );

  currentComments = Array.isArray(data) ? data : [];
  renderComments();
};

const handleAddComment = async (event) => {
  event.preventDefault();

  if (!commentForm) {
    return;
  }

  commentForm.classList.add('was-validated');

  const author = commentAuthorInput?.value.trim();
  const text = commentTextarea?.value.trim();

  if (!author || !text || !currentResourceId) {
    return;
  }

  try {
    showCommentAlert('Posting your comment...', 'info');
    const { id } = await request(`${API_ENDPOINT}?action=comment`, {
      method: 'POST',
      body: JSON.stringify({
        resource_id: currentResourceId,
        author,
        text,
      }),
    });

    const newComment = {
      id,
      resource_id: Number(currentResourceId),
      author,
      text,
      created_at: new Date().toISOString(),
    };

    currentComments.push(newComment);
    renderComments();
    showCommentAlert('Thanks! Your comment has been posted.', 'success');
    commentForm.reset();
    commentForm.classList.remove('was-validated');
  } catch (error) {
    console.error('Unable to post comment:', error);
    showCommentAlert(error.message || 'Unable to post your comment right now.', 'danger');
  }
};

const initializePage = async () => {
  const params = new URLSearchParams(window.location.search);
  currentResourceId = params.get('id');

  if (!currentResourceId) {
    showResourceError('Resource not found.');
    if (commentForm) {
      commentForm.classList.add('d-none');
    }
    return;
  }

  try {
    await Promise.all([loadResource(), loadComments()]);
    clearCommentAlert();
  } catch (error) {
    console.error('Unable to load resource details:', error);
    showResourceError(error.message || 'Unable to load resource details.');
    showCommentAlert('Comments are unavailable right now.', 'danger');
  }

  commentForm?.addEventListener('submit', handleAddComment);
};

initializePage();


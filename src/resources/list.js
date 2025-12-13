const API_ENDPOINT = "api/index.php";
const listSection = document.querySelector("#resource-list-section");
const searchInput = document.querySelector("#resource-search");
const resourceCountBadge = document.querySelector("#resource-count");
const resourceFeedback = document.querySelector("#resource-feedback");

let resources = [];

const escapeHtml = (value = "") =>
  String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");

const truncate = (text = "", length = 180) => {
  if (text.length <= length) {
    return text;
  }

  return `${text.slice(0, length).trim()}...`;
};

const updateCountBadge = (visibleCount, totalCount = resources.length) => {
  if (!resourceCountBadge) {
    return;
  }

  if (!totalCount) {
    resourceCountBadge.textContent = "No resources yet";
    return;
  }

  if (visibleCount === totalCount) {
    resourceCountBadge.textContent = `${visibleCount} resource${
      visibleCount === 1 ? "" : "s"
    }`;
    return;
  }

  resourceCountBadge.textContent = `Showing ${visibleCount} of ${totalCount}`;
};

const showFeedback = (message, type = "warning") => {
  if (!resourceFeedback) {
    return;
  }

  resourceFeedback.textContent = message;
  resourceFeedback.className = `alert alert-${type}`;
};

const clearFeedback = () => {
  if (!resourceFeedback) {
    return;
  }

  resourceFeedback.classList.add("d-none");
  resourceFeedback.textContent = "";
};

const setLoadingState = () => {
  if (!listSection) {
    return;
  }

  listSection.innerHTML = `
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading resources...</span>
          </div>
        </div>
      </div>
    </div>
  `;
};

const getSafeLink = (value) => {
  try {
    const url = new URL(value);
    if (url.protocol === "http:" || url.protocol === "https:") {
      return url.href;
    }
  } catch (error) {
    return null;
  }
  return null;
};

function createResourceArticle(resource) {
  const article = document.createElement("article");
  article.className = "col-12 col-lg-6";
  const safeLink = getSafeLink(resource.link);

  article.innerHTML = `
    <div class="card h-100 shadow-sm border-0">
      <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between gap-2">
          <h2 class="h4 mb-1 text-primary">${escapeHtml(resource.title)}</h2>
          <span class="badge text-bg-light align-self-start">${new Date(
            resource.created_at
          ).toLocaleDateString()}</span>
        </div>
        <p class="text-muted flex-grow-1">
          ${escapeHtml(
            truncate(resource.description || "No description provided.")
          )}
        </p>
        <div class="mt-3 d-flex flex-wrap gap-2">
          <a
            href="${safeLink ?? "#"}"
            class="btn btn-outline-secondary ${safeLink ? "" : "disabled"}"
            ${
              safeLink
                ? 'target="_blank" rel="noopener"'
                : 'aria-disabled="true"'
            }
          >
            Open Resource
          </a>
          <a
            href="details.html?id=${encodeURIComponent(resource.id)}"
            class="btn btn-primary"
          >
            View Details & Discussion
          </a>
        </div>
      </div>
    </div>
  `;

  return article;
}

const renderResources = (list = []) => {
  if (!listSection) {
    return;
  }

  listSection.innerHTML = "";

  if (!list.length) {
    listSection.innerHTML = `
      <div class="col-12">
        <div class="alert alert-info mb-0" role="alert">
          No resources match your search yet. Please try a different keyword.
        </div>
      </div>
    `;
    return;
  }

  list.forEach((resource) => {
    listSection.appendChild(createResourceArticle(resource));
  });
};

const filterResources = () => {
  const query = (searchInput?.value || "").trim().toLowerCase();

  if (!query) {
    renderResources(resources);
    updateCountBadge(resources.length);
    clearFeedback();
    return;
  }

  const filtered = resources.filter((resource) => {
    const haystack = `${resource.title} ${
      resource.description || ""
    }`.toLowerCase();
    return haystack.includes(query);
  });

  if (!filtered.length) {
    showFeedback("No matches found. Please adjust your search.", "secondary");
  } else {
    clearFeedback();
  }

  renderResources(filtered);
  updateCountBadge(filtered.length, resources.length);
};

async function loadResources() {
  if (!listSection) {
    return;
  }

  setLoadingState();
  clearFeedback();

  try {
    const response = await fetch(API_ENDPOINT, {
      headers: { Accept: "application/json" },
    });
    const payload = await response.json();

    if (!response.ok || payload.success === false) {
      throw new Error(payload.message || "Unable to load resources.");
    }

    resources = Array.isArray(payload.data) ? payload.data : [];

    if (!resources.length) {
      showFeedback(
        "No resources have been shared yet. Check back soon!",
        "info"
      );
    }

    if (searchInput && searchInput.value.trim()) {
      filterResources();
    } else {
      renderResources(resources);
      updateCountBadge(resources.length);
    }
  } catch (error) {
    console.error("Unable to load resources:", error);
    showFeedback(
      "Sorry, we could not load the resources right now. Please try again later.",
      "danger"
    );
    listSection.innerHTML = `
      <div class="col-12">
        <div class="alert alert-danger mb-0" role="alert">
          ${escapeHtml(error.message || "Unexpected error occurred.")}
        </div>
      </div>
    `;
    updateCountBadge(0);
  }
}

searchInput?.addEventListener("input", filterResources);
loadResources();

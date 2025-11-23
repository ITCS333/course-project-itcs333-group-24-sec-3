const API_ENDPOINT = "api/index.php";
const resourceForm = document.querySelector("#resource-form");
const resourceFeedback = document.querySelector("#resource-feedback");
const resourcesTableBody = document.querySelector("#resources-tbody");
const tableFeedback = document.querySelector("#table-feedback");
const searchInput = document.querySelector("#resource-search");
const modalElement = document.getElementById("resource-modal");
const modalForm = document.getElementById("resource-modal-form");
const editResourceIdInput = document.getElementById("edit-resource-id");
const editResourceTitleInput = document.getElementById("edit-resource-title");
const editResourceDescriptionInput = document.getElementById(
  "edit-resource-description"
);
const editResourceLinkInput = document.getElementById("edit-resource-link");
const addResourceButton = document.getElementById("add-resource");

let resources = [];
let filteredResources = [];
let resourceModal = null;

const addTitleInput = document.getElementById("resource-title");
const addDescriptionInput = document.getElementById("resource-description");
const addLinkInput = document.getElementById("resource-link");

const escapeHtml = (value = "") =>
  String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");

const formatDate = (value) => {
  if (!value) {
    return "";
  }
  const date = new Date(value);
  return date.toLocaleString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const clearAlert = (element) => {
  if (!element) {
    return;
  }
  element.classList.add("d-none");
  element.textContent = "";
};

const showAlert = (element, message, type = "info") => {
  if (!element) {
    return;
  }
  element.textContent = message;
  element.className = `alert alert-${type} mt-3`;
};

const setTableLoadingState = () => {
  if (!resourcesTableBody) {
    return;
  }
  resourcesTableBody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading resources...</span>
        </div>
      </td>
    </tr>
  `;
};

const renderTable = (list = []) => {
  if (!resourcesTableBody) {
    return;
  }

  resourcesTableBody.innerHTML = "";

  if (!list.length) {
    resourcesTableBody.innerHTML = `
      <tr>
        <td colspan="4" class="text-center py-4 text-muted">
          No resources found. Use the form above to add one.
        </td>
      </tr>
    `;
    return;
  }

  list.forEach((resource) => {
    const hasValidLink = validateUrl(resource.link);
    const row = document.createElement("tr");
    row.innerHTML = `
      <td class="fw-semibold">
        ${escapeHtml(resource.title)}
        <div class="small text-muted">${formatDate(resource.created_at)}</div>
      </td>
      <td class="text-muted small">
        ${escapeHtml(resource.description || "No description provided.")}
      </td>
      <td>
        ${
          hasValidLink
            ? `<a href="${
                resource.link
              }" target="_blank" rel="noopener" class="text-break">${escapeHtml(
                resource.link
              )}</a>`
            : '<span class="badge text-bg-secondary">Invalid link</span>'
        }
      </td>
      <td class="text-center">
        <button class="btn btn-warning btn-sm edit-btn me-2" data-id="${
          resource.id
        }" type="button">
          Edit
        </button>
        <button class="btn btn-danger btn-sm delete-btn" data-id="${
          resource.id
        }" type="button">
          Delete
        </button>
      </td>
    `;
    resourcesTableBody.appendChild(row);
  });
};

const applySearchFilter = () => {
  const query = (searchInput?.value || "").trim().toLowerCase();

  if (!query) {
    filteredResources = [...resources];
    renderTable(filteredResources);
    return;
  }

  filteredResources = resources.filter((resource) => {
    const haystack = `${resource.title} ${resource.description || ""} ${
      resource.link
    }`.toLowerCase();
    return haystack.includes(query);
  });

  renderTable(filteredResources);
};

const request = async (url, options = {}) => {
  const headers = {
    Accept: "application/json",
    ...(options.headers || {}),
  };

  if (options.body && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  const payload = await response.json().catch(() => ({
    success: false,
    message: "Unable to parse server response.",
  }));

  if (!response.ok || payload.success === false) {
    throw new Error(payload.message || "Request failed.");
  }

  return payload;
};

const loadResources = async () => {
  setTableLoadingState();
  clearAlert(tableFeedback);

  try {
    const { data } = await request(API_ENDPOINT, {
      method: "GET",
      headers: { Accept: "application/json" },
    });
    resources = Array.isArray(data) ? data : [];
    applySearchFilter();
  } catch (error) {
    console.error(error);
    resources = [];
    renderTable(resources);
    showAlert(
      tableFeedback,
      error.message || "Failed to load resources.",
      "danger"
    );
  }
};

const validateUrl = (value) => {
  try {
    const url = new URL(value);
    return Boolean(url.protocol === "http:" || url.protocol === "https:");
  } catch (error) {
    return false;
  }
};

const handleCreateResource = async (event) => {
  event.preventDefault();
  if (!resourceForm) {
    return;
  }

  resourceForm.classList.add("was-validated");

  const title = addTitleInput?.value.trim() || "";
  const description = addDescriptionInput?.value.trim() || "";
  const link = addLinkInput?.value.trim() || "";

  if (!title || !link || !validateUrl(link)) {
    showAlert(
      resourceFeedback,
      "Please provide a valid title and https:// link.",
      "danger"
    );
    return;
  }

  addResourceButton?.setAttribute("disabled", "disabled");
  showAlert(resourceFeedback, "Saving resource...", "info");

  try {
    await request(API_ENDPOINT, {
      method: "POST",
      body: JSON.stringify({ title, description, link }),
    });

    showAlert(resourceFeedback, "Resource added successfully.", "success");
    resourceForm.reset();
    resourceForm.classList.remove("was-validated");
    await loadResources();
  } catch (error) {
    console.error(error);
    showAlert(
      resourceFeedback,
      error.message || "Unable to save resource.",
      "danger"
    );
  } finally {
    addResourceButton?.removeAttribute("disabled");
  }
};

const openEditModal = (resourceId) => {
  const resource = resources.find(
    (item) => String(item.id) === String(resourceId)
  );
  if (!resource || !resourceModal) {
    return;
  }

  editResourceIdInput.value = resource.id;
  editResourceTitleInput.value = resource.title;
  editResourceDescriptionInput.value = resource.description || "";
  editResourceLinkInput.value = resource.link;
  modalForm.classList.remove("was-validated");
  resourceModal.show();
};

const handleUpdateResource = async (event) => {
  event.preventDefault();
  if (!modalForm) {
    return;
  }

  modalForm.classList.add("was-validated");

  const id = editResourceIdInput.value;
  const title = editResourceTitleInput.value.trim();
  const description = editResourceDescriptionInput.value.trim();
  const link = editResourceLinkInput.value.trim();

  if (!id || !title || !link || !validateUrl(link)) {
    return;
  }

  try {
    await request(API_ENDPOINT, {
      method: "PUT",
      body: JSON.stringify({ id, title, description, link }),
    });

    resourceModal?.hide();
    await loadResources();
  } catch (error) {
    console.error(error);
    showAlert(
      tableFeedback,
      error.message || "Unable to update the resource.",
      "danger"
    );
  }
};

const handleDeleteResource = async (resourceId) => {
  if (!resourceId) {
    return;
  }

  const confirmed = window.confirm(
    "Are you sure you want to delete this resource?"
  );
  if (!confirmed) {
    return;
  }

  try {
    await request(`${API_ENDPOINT}?id=${encodeURIComponent(resourceId)}`, {
      method: "DELETE",
    });

    await loadResources();
  } catch (error) {
    console.error(error);
    showAlert(
      tableFeedback,
      error.message || "Unable to delete the resource.",
      "danger"
    );
  }
};

const handleTableInteraction = (event) => {
  const target = event.target;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  const resourceId = target.dataset.id;
  if (target.classList.contains("edit-btn")) {
    openEditModal(resourceId);
    return;
  }

  if (target.classList.contains("delete-btn")) {
    handleDeleteResource(resourceId);
  }
};

const initializeAdminPage = () => {
  if (!resourcesTableBody) {
    return;
  }

  if (modalElement && window.bootstrap) {
    resourceModal = new bootstrap.Modal(modalElement);
  }

  resourceForm?.addEventListener("submit", handleCreateResource);
  resourcesTableBody.addEventListener("click", handleTableInteraction);
  modalForm?.addEventListener("submit", handleUpdateResource);
  searchInput?.addEventListener("input", applySearchFilter);

  loadResources();
};

initializeAdminPage();

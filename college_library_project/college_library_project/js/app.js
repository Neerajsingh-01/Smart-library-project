const views = ["dashboard", "suggest", "profile", "admin"];
const state = {
  user: null,
  books: [],
  suggestions: [],
  alerted: new Set(),
  notificationTimer: null
};

const $ = id => document.getElementById(id);
const isAdmin = () => state.user?.role === "admin";
const isStudent = () => state.user?.role === "student";

function body(data) {
  return new URLSearchParams(data).toString();
}

function api(url, options = {}) {
  return fetch(url, options).then(response => {
    if (!response.ok) {
      return response.text().then(text => {
        throw new Error(text || `HTTP ${response.status}`);
      });
    }

    return response.text().then(text => {
      try {
        return JSON.parse(text);
      } catch {
        throw new Error(text || "Invalid server response");
      }
    });
  });
}

function showMessage(text, type = "info", popup = false) {
  const message = $("message");
  message.textContent = text;
  message.className = `message ${type}`;
  if (popup) alert(text);
}

function clearMessage() {
  $("message").textContent = "";
  $("message").className = "message hidden";
}

function login() {
  clearMessage();

  api("login.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: body({
      id: $("username").value.trim(),
      password: $("password").value
    })
  })
    .then(data => {
      if (!data.success) {
        showMessage(data.message || "Login failed", "error", true);
        return;
      }

      state.user = data.user;
      state.alerted = loadAlertedNotifications();
      $("studentInfo").textContent = `${data.user.name} (${data.user.id}) - ${data.user.role}`;
      $("loginPage").style.display = "none";
      $("mainApp").classList.remove("hidden");

      if (isStudent()) requestBrowserPermission();
      renderTabs();
      switchView("dashboard");
      refreshAll();
      startNotificationPolling();
      showMessage(data.message || "Login successful", "success", true);
    })
    .catch(error => showMessage(`Login request failed: ${error.message}`, "error", true));
}

function logout() {
  api("logout.php").finally(() => {
    stopNotificationPolling();
    state.user = null;
    state.books = [];
    state.suggestions = [];
    state.alerted = new Set();
    $("username").value = "";
    $("password").value = "";
    $("mainApp").classList.add("hidden");
    $("loginPage").style.display = "flex";
  });
}

function renderTabs() {
  const tabs = isAdmin()
    ? [["dashboard", "Books"], ["admin", "Admin"]]
    : [["dashboard", "Books"], ["suggest", "Suggest"], ["profile", "Profile"]];

  $("tabs").replaceChildren(...tabs.map(([view, label]) => {
    const button = document.createElement("button");
    button.textContent = label;
    button.onclick = () => switchView(view);
    return button;
  }));
}

function switchView(viewName) {
  views.forEach(view => {
    const section = $(`${view}View`);
    if (section) section.classList.toggle("active", view === viewName);
  });

  [...$("tabs").children].forEach(button => {
    button.classList.toggle("active", button.textContent.toLowerCase().startsWith(viewName === "dashboard" ? "books" : viewName));
  });

  if (viewName === "suggest") loadSuggestions();
  if (viewName === "profile") {
    loadProfile();
    loadNotifications();
  }
  if (viewName === "admin") loadAdminData();
}

function refreshAll() {
  loadBooks();

  if (isAdmin()) {
    loadAdminData();
  } else {
    loadSuggestions();
    loadProfile();
    loadNotifications();
  }
}

function loadBooks() {
  const params = new URLSearchParams({
    q: $("search")?.value || "",
    course: $("courseFilter")?.value || "",
    semester: $("semesterFilter")?.value || "",
    subject: $("subjectFilter")?.value || ""
  });

  $("results").textContent = "Loading books...";

  api(`getBooks.php?${params}`)
    .then(data => {
      if (!data.success) {
        showMessage(data.message || "Books could not load", "error");
        return;
      }

      state.books = data.books;
      renderFilters(data.books);
      renderStats(data.books);
      renderBooks($("results"), data.books, isStudent() ? "student" : "readonly");
      if (isAdmin()) renderBooks($("adminBooks"), data.books, "admin");
    })
    .catch(() => showMessage("Could not connect to getBooks.php", "error"));
}

function renderFilters(books) {
  fillSelect("courseFilter", "All courses", unique(books.map(book => book.course).filter(Boolean)));
  fillSelect("semesterFilter", "All semesters", unique(books.map(book => book.semester).filter(Boolean)));
  fillSelect("subjectFilter", "All subjects", unique(books.map(book => book.subject).filter(Boolean)));
}

function fillSelect(id, label, values) {
  const select = $(id);
  if (!select) return;

  const current = select.value;
  select.replaceChildren(new Option(label, ""), ...values.map(value => new Option(value, value)));
  select.value = values.includes(current) ? current : "";
}

function unique(values) {
  return [...new Set(values)].sort((a, b) => String(a).localeCompare(String(b), undefined, { numeric: true }));
}

function renderStats(books) {
  const totalBooks = books.length;
  const copies = books.reduce((sum, book) => sum + Number(book.total), 0);
  const available = books.reduce((sum, book) => sum + Number(book.available), 0);
  const unavailable = copies - available;

  $("stats").replaceChildren(
    statCard("Books", totalBooks),
    statCard("Copies", copies),
    statCard("Available", available),
    statCard("Issued", unavailable)
  );
}

function statCard(label, value) {
  const card = document.createElement("div");
  card.className = "stat-card";
  card.innerHTML = `<span>${label}</span><strong>${value}</strong>`;
  return card;
}

function renderBooks(container, books, mode) {
  if (!container) return;
  container.replaceChildren();

  if (!books.length) {
    container.textContent = "No books found.";
    return;
  }

  books.forEach(book => {
    const available = Number(book.available) > 0;
    const card = document.createElement("article");
    card.className = "book-card";
    card.innerHTML = `
      <div class="book-top">
        <span class="badge">${escapeHtml(book.course || "General")} Sem ${escapeHtml(book.semester || "-")}</span>
        <span class="${available ? "available" : "not-available"}">${available ? "Available" : "Not available"}</span>
      </div>
      <h3>${escapeHtml(book.name)}</h3>
      <p>${escapeHtml(book.author)}</p>
      <dl>
        <div><dt>Subject</dt><dd>${escapeHtml(book.subject || "-")}</dd></div>
        <div><dt>Publisher</dt><dd>${escapeHtml(book.publisher || "-")}</dd></div>
        <div><dt>Copies</dt><dd>${book.available}/${book.total}</dd></div>
        <div><dt>Rack</dt><dd>${escapeHtml(book.rack_position || "-")}</dd></div>
      </dl>
    `;

    const actions = document.createElement("div");
    actions.className = "actions";

    if (mode === "student" && available) {
      actions.append(button("Issue", () => issueBook(book.id)));
    }
    if (mode === "student" && !available) {
      actions.append(button("Notify Me", () => notifyBook(book.id), "secondary"));
    }
    if (mode === "admin") {
      actions.append(button("Edit", () => fillBookForm(book), "secondary"));
      actions.append(button("Delete", () => deleteBook(book.id), "danger"));
    }

    if (actions.children.length) card.append(actions);
    container.append(card);
  });
}

function button(text, onClick, className = "") {
  const element = document.createElement("button");
  element.textContent = text;
  element.className = className;
  element.onclick = onClick;
  return element;
}

function postAction(url, data, onSuccess) {
  clearMessage();

  api(url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: body(data)
  })
    .then(result => {
      showMessage(result.message || "Action completed", result.success ? "success" : "error", true);
      if (result.success && onSuccess) onSuccess(result);
    })
    .catch(() => showMessage("Request failed", "error", true));
}

function issueBook(id) {
  if (!isStudent()) return;
  postAction("issueBook.php", { id }, () => {
    loadBooks();
    loadProfile();
    loadNotifications();
  });
}

function notifyBook(book) {
  if (!isStudent()) return;
  requestBrowserPermission();
  postAction("notify.php", { book }, loadNotifications);
}

function submitSuggestion(event) {
  event.preventDefault();
  if (!isStudent()) return;

  postAction("suggestBook.php", {
    name: $("suggestName").value,
    author: $("suggestAuthor").value,
    edition: $("suggestEdition").value
  }, () => {
    event.target.reset();
    loadSuggestions();
  });
}

function loadSuggestions() {
  api("getSuggestions.php").then(data => {
    if (!data.success) return;
    state.suggestions = data.suggestions;
    renderStudentSuggestions();
    renderAdminSuggestions();
  });
}

function renderStudentSuggestions() {
  const list = $("suggestionsList");
  if (!list || isAdmin()) return;
  renderList(list, state.suggestions, item => item.book_name, item => `${item.author} - ${item.status}`);
}

function renderAdminSuggestions() {
  const list = $("adminSuggestions");
  if (!list || !isAdmin()) return;

  const pending = state.suggestions.filter(item => item.status === "pending");
  renderList(
    list,
    pending,
    item => item.book_name,
    item => `${item.author} - ${item.student_name || item.user_id}`,
    item => {
      const actions = document.createElement("div");
      actions.className = "actions";
      actions.append(button("Approve", () => reviewSuggestion(item.id, "approved")));
      actions.append(button("Reject", () => reviewSuggestion(item.id, "rejected"), "danger"));
      return actions;
    }
  );
}

function reviewSuggestion(id, decision) {
  postAction("approveSuggestion.php", { id, decision }, () => {
    loadSuggestions();
    loadBooks();
  });
}

function loadProfile() {
  if (!isStudent()) return;

  api("getProfile.php").then(data => {
    if (!data.success) return;
    renderList(
      $("issuedList"),
      data.issued,
      item => item.name,
      item => item.returned_at ? `Returned ${formatDate(item.returned_at)}` : `Issued ${formatDate(item.issued_at)} - return at library desk`
    );
  });
}

function loadNotifications() {
  if (!isStudent()) return;

  api("getNotifications.php").then(data => {
    if (!data.success) return;
    showBookReadyAlerts(data.notifications);
    renderList(
      $("notificationsList"),
      data.notifications,
      item => item.name,
      item => item.status === "notified" ? "Available now" : "Waiting in priority queue"
    );
  });
}

function saveBook(event) {
  event.preventDefault();
  if (!isAdmin()) return;

  postAction("saveBook.php", {
    id: $("bookId").value,
    name: $("bookName").value,
    author: $("bookAuthor").value,
    publisher: $("bookPublisher").value,
    subject: $("bookSubject").value,
    course: $("bookCourse").value,
    semester: $("bookSemester").value,
    total: $("bookTotal").value,
    available: $("bookAvailable").value,
    rack_position: $("bookRack").value
  }, () => {
    resetBookForm();
    loadBooks();
    loadAdminData();
  });
}

function fillBookForm(book) {
  $("bookId").value = book.id;
  $("bookName").value = book.name;
  $("bookAuthor").value = book.author;
  $("bookPublisher").value = book.publisher || "";
  $("bookSubject").value = book.subject || "";
  $("bookCourse").value = book.course || "";
  $("bookSemester").value = book.semester || "";
  $("bookTotal").value = book.total;
  $("bookAvailable").value = book.available;
  $("bookRack").value = book.rack_position || "";
  $("bookName").focus();
}

function resetBookForm() {
  $("bookForm").reset();
  $("bookId").value = "";
}

function deleteBook(id) {
  if (!isAdmin() || !confirm("Delete this book?")) return;
  postAction("deleteBook.php", { id }, () => {
    loadBooks();
    loadAdminData();
  });
}

function loadAdminData() {
  if (!isAdmin()) return;
  loadSuggestions();
  loadIssuedRecords();
  loadUsers();
  loadAdminNotifications();
}

function loadIssuedRecords() {
  api("getIssuedBooks.php").then(data => {
    if (!data.success) return;
    renderList(
      $("issuedRecords"),
      data.records,
      item => `${item.book_name} - ${item.student_name}`,
      item => item.returned_at ? `Returned ${formatDate(item.returned_at)}` : `Issued ${formatDate(item.issued_at)}`,
      item => item.returned_at ? null : button("Mark Returned", () => returnBook(item.id), "secondary")
    );
  });
}

function returnBook(issueId) {
  if (!isAdmin()) return;
  postAction("returnBook.php", { issue_id: issueId }, () => {
    loadBooks();
    loadIssuedRecords();
    loadAdminNotifications();
  });
}

function loadUsers() {
  api("getUsers.php").then(data => {
    if (!data.success) return;
    renderList($("usersList"), data.users, item => `${item.name} (${item.id})`, item => `${item.role} - ${item.active_issues} active issues`);
  });
}

function loadAdminNotifications() {
  api("getAllNotifications.php").then(data => {
    if (!data.success) return;
    renderList($("adminNotifications"), data.notifications, item => `${item.book_name} - ${item.student_name}`, item => `${item.status} since ${formatDate(item.requested_at)}`);
  });
}

function renderList(container, rows, titleFn, metaFn, actionFn) {
  if (!container) return;
  container.replaceChildren();

  if (!rows.length) {
    container.textContent = "Nothing to show.";
    return;
  }

  rows.forEach(row => {
    const item = document.createElement("div");
    item.className = "list-item";
    const title = document.createElement("strong");
    title.textContent = titleFn(row);
    const meta = document.createElement("span");
    meta.textContent = metaFn(row);
    item.append(title, meta);

    if (actionFn) {
      const action = actionFn(row);
      if (action) item.append(action);
    }

    container.append(item);
  });
}

function requestBrowserPermission() {
  if ("Notification" in window && Notification.permission === "default") {
    Notification.requestPermission();
  }
}

function startNotificationPolling() {
  stopNotificationPolling();
  if (isStudent()) {
    state.notificationTimer = setInterval(loadNotifications, 30000);
  }
}

function stopNotificationPolling() {
  if (state.notificationTimer) clearInterval(state.notificationTimer);
  state.notificationTimer = null;
}

function notificationKey() {
  return `library-notified-${state.user?.id || "guest"}`;
}

function loadAlertedNotifications() {
  try {
    return new Set(JSON.parse(localStorage.getItem(notificationKey()) || "[]"));
  } catch {
    return new Set();
  }
}

function saveAlertedNotifications() {
  localStorage.setItem(notificationKey(), JSON.stringify([...state.alerted]));
}

function showBookReadyAlerts(notifications) {
  notifications
    .filter(item => item.status === "notified" && !state.alerted.has(String(item.id)))
    .forEach(item => {
      const text = `${item.name} is now available. Please issue it from the library portal.`;
      state.alerted.add(String(item.id));
      showMessage(text, "success", true);

      if ("Notification" in window && Notification.permission === "granted") {
        new Notification("Book available", { body: text });
      }
    });

  saveAlertedNotifications();
}

function formatDate(value) {
  if (!value) return "";
  return new Date(value.replace(" ", "T")).toLocaleDateString();
}

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, char => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    "\"": "&quot;",
    "'": "&#039;"
  }[char]));
}

["search", "courseFilter", "semesterFilter", "subjectFilter"].forEach(id => {
  document.addEventListener("input", event => {
    if (event.target.id === id) loadBooks();
  });
  document.addEventListener("change", event => {
    if (event.target.id === id) loadBooks();
  });
});

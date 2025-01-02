const toast = (config) => {
  const { type, timeout, title } = config;

  const icon =
    type === "success"
      ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 16.17l-3.88-3.88L4 13.41l5 5 10-10-1.41-1.42z"/></svg>'
      : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M13 3h-2v10h2zm0 14h-2v2h2z"/></svg>';

  const toast = $(`
            <div class="toast ${type}">
                <div class="header">
                    <span class="icon">${icon}</span>
                    <span>${title}</span>
                    <span class="close-btn">&times;</span>
                </div>
                <div class="progress-bar" style="animation-duration: ${timeout}ms"></div>
            </div>
        `);

  $("#toast-container").append(toast);

  // Remove toast on close button click
  toast.find(".close-btn").on("click", function () {
    toast.remove();
  });

  // Auto-remove toast after timeout
  setTimeout(() => {
    toast.remove();
  }, timeout);
};

export default toast;
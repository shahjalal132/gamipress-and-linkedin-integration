(function ($) {
  $(document).ready(function () {
    // show toast start
    function showToast(config) {
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
    }
    // show toast end

    function setCookie(name, value, hours) {
      const date = new Date();
      date.setTime(date.getTime() + hours * 60 * 60 * 1000); // Convert hours to milliseconds
      const expires = "expires=" + date.toUTCString();
      document.cookie =
        name + "=" + encodeURIComponent(value) + ";" + expires + ";path=/";
    }

    // Share on LinkedIn process start
    $("#gli-share-linkedin").click(function (e) {
      e.preventDefault();

      let post_url = $(this).data("post-url");
      let post_title = $(this).data("post-title");

      let post_content = $(this).data("post-content");
      // remove html comments
      post_content = post_content.replace(/<!--(.|\n)*?-->/g, "");

      // Regex to capture the value of data-trx-lazyload-src
      const regex = /data-trx-lazyload-src="([^"]+)"/g;
      // Use the regex to extract the value
      const matches = post_content.match(regex);
      // get image url
      let imageUrl = "";
      if (matches && matches.length > 0) {
        imageUrl = matches[0].replace('data-trx-lazyload-src="', "");
      }

      // get post preview prompt
      let postPreviewPrompt = $("#gli-share-linkedin-popup-input");

      // set post url to cookie for 1 hours
      setCookie("gli_current_post_url", post_url, 1);

      $.ajax({
        type: "POST",
        url: wpb_public_localize.ajax_url,
        data: {
          action: "check_user_logged_in",
          nonce: wpb_public_localize.nonce,
        },
        success: function (response) {
          if (response.is_logged_in === "no" || response.is_logged_in === "") {
            // open sign in popup
            $("#gli-sign-in-with-linkedin").fadeIn();
          } else if (response.is_logged_in === "yes") {
            // open share popup
            $("#gli-share-linkedin-popup").fadeIn();
            // set post content
            postPreviewPrompt.val(post_content);
          }
        },
      });

      // send ajax to backend when click on share button
      $("#gli-share-linkedin-popup-share").click(function (e) {
        e.preventDefault();

        // get input value
        let input_prompt_value = postPreviewPrompt.val();
        const spinner = $(".spinner-loader-wrapper");

        // add loading spinner
        $(spinner).addClass("loader-spinner");

        // send ajax to backend
        $.ajax({
          type: "POST",
          url: wpb_public_localize.ajax_url,
          data: {
            action: "share_on_linkedin",
            nonce: wpb_public_localize.nonce,
            predefined_url: post_url,
            post_title: post_title,
            input_prompt_value: input_prompt_value,
            image_url: imageUrl,
          },
          success: function (response) {
            console.log(response);
            // remove loading spinner
            $(spinner).removeClass("loader-spinner");

            if (response.success) {
              showToast({
                type: "success",
                title: "Post shared successfully, You earned 10 points!",
                timeout: 5000,
              });
              $("#gli-share-linkedin-popup").fadeOut();
            } else {
              showToast({
                type: "error",
                title: "Failed to share the post. Please try again.",
                // title: response.data,
                timeout: 5000,
              });
              $("#gli-share-linkedin-popup").fadeOut();
            }
          },
          error: function (xhr, status, error) {
            $(spinner).removeClass("loader-spinner");
            console.error("Error:", xhr.responseText || error);
            $("#gli-share-linkedin-popup").fadeOut();
          },
        });
      });
    });

    // close popup when click close button
    $("#gli-share-linkedin-popup-close").click(function (e) {
      e.preventDefault();
      $("#gli-share-linkedin-popup").fadeOut();
    });

    // close popup when click close button
    $("#gli-sign-in-with-linkedin-popup-close").click(function (e) {
      e.preventDefault();
      $("#gli-sign-in-with-linkedin").fadeOut();
    });
    // Share on LinkedIn process end
  });
})(jQuery);

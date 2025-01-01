(function ($) {
  $(document).ready(function () {
    // Share on LinkedIn process start

    $("#gli-share-linkedin").click(function (e) {
      e.preventDefault();

      let post_url = $(this).data("post-url");
      let post_title = $(this).data("post-title");

      // open popup
      $("#gli-share-linkedin-popup").fadeIn();

      // send ajax to backend when click on share button
      $("#gli-share-linkedin-popup-share").click(function (e) {
        e.preventDefault();

        // get input value
        let input_prompt_value = $("#gli-share-linkedin-popup-input").val();
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
          },
          success: function (response) {
            // remove loading spinner
            $(spinner).removeClass("loader-spinner");
            console.log(response);
          },
          error: function (xhr, status, error) {
            // remove loading spinner
            $(spinner).removeClass("loader-spinner");
            console.log(error);
          },
        });
      });
    });

    // close popup when click close button
    $("#gli-share-linkedin-popup-close").click(function (e) {
      e.preventDefault();
      $("#gli-share-linkedin-popup").fadeOut();
    });
    // Share on LinkedIn process end
  });
})(jQuery);

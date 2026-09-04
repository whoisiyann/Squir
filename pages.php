<?php
  session_start();
  session_destroy()
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<head>
  <title>404 Not Found</title>
    <title>Log-In</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="." />
    <meta name="keywords" content="." />
    <meta name="author" content="Sniper 2025" />
    <link rel="icon" href="./assets/images/PTA.png" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./dist/assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/feather.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/material.css" />
    <link rel="stylesheet" href="./dist/assets/css/style.css" id="main-style-link" />
    <script type="text/javascript">
      function preventBack() {
        history.pushState(null, '', location.href);
      }
      window.addEventListener('load', function () {
        preventBack();
      window.addEventListener('popstate', function () {
          preventBack(); // Push state again
          alert("Back button is disabled on this page.");
        });
      });
  </script>
</head>
<body onload="preventBack();">
  <!-- [ Main Content ] start -->
  <div class="pc-container">
    <div class="pc-content">
      <!-- [ Main Content ] start -->
        <div class="col-span-12">
          <div class="card">
            <div class="card-header">
              <h5>Error 404 System Not Found! Please contact the System Developer!</h5>
            </div>
            <div class="card-body">
              <img src="./dist/assets/images/pages/404.png" alt="System Not Found" class="w-full h-auto mb-4" />
            </div>
          </div>
        </div>
       <!-- [ Main Content ] end -->
    </div>
  </div>
<!-- [ Main Content ] end -->
    <!-- Required Js -->
    <script src="../dist/assets/js/plugins/simplebar.min.js"></script>
    <script src="../dist/assets/js/plugins/popper.min.js"></script>
    <script src="../dist/assets/js/icon/custom-icon.js"></script>
    <script src="../dist/assets/js/plugins/feather.min.js"></script>
    <script src="../dist/assets/js/component.js"></script>
    <script src="../dist/assets/js/theme.js"></script>
    <script src="../dist/assets/js/script.js"></script>

    <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]"></div>
    <script>
      layout_change('false');
      layout_theme_sidebar_change('dark');
      change_box_container('false');
      layout_caption_change('true');
      layout_rtl_change('false');
      preset_change('preset-1');
      main_layout_change('vertical');
    </script>
  </body>
</html>
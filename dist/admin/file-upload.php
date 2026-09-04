<?php
  session_start();
  if(isset($_POST['submit'])){

  } else {

?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr"
  data-pc-theme="light">

<head>
  <title>Feather Icon Pack Template</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="description" content="." />
  <meta name="keywords" content="." />
  <meta name="author" content="Sniper 2025" />
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
  <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
  <link rel="stylesheet" href="../assets/fonts/feather.css" />
  <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
  <link rel="stylesheet" href="../assets/fonts/material.css" />
  <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
</head>
<!-- [Head] end -->
<!-- [Body] Start -->

<body>
  <!-- [ Pre-loader ] start -->
  <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
    <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
      <div
        class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]">
      </div>
    </div>
  </div>
  <!-- [ Pre-loader ] End -->

  <!-- [ Sidebar Menu ] start -->
  <?php include '../includes/sidebar.php'; ?>
  <!-- [ Sidebar Menu ] end -->
  <!-- [ Header Topbar ] start -->
  <?php include '../includes/header.php'; ?>
  <!-- [ Header ] end -->

  <!-- [ Main Content ] start -->
  <div class="pc-container">
    <div class="pc-content">
      <!-- [ breadcrumb ] start -->
      <div class="page-header">
        <div class="page-block">
          <div class="page-header-title">
            <h5 class="mb-0 font-medium">Upload File</h5>
          </div>
          <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
            <li class="breadcrumb-item" aria-current="page">Upload File</li>
          </ul>
        </div>
      </div>
      <!-- [ breadcrumb ] end -->

      <!-- [ Main Content ] start -->
      <div class="card mb-4">
        <h6 class="card-header">File Upload</h6>
        <div class="card-body">
          <form action="/upload" class="dropzone needsclick" id="dropzone-demo">
            <div class="dz-message needsclick">Click button to upload a file here!</div>
            <div class="fallback">
              <input name="file" type="file" multiple class="btn btn-success mx-auto shadow-2xl">
              <div class="clearfix"></div>
            </div>
            </br>
            <div class="flex mt-1 justify-between items-center flex-wrap">
              <div class="form-check">
                <button type="button" class="btn btn-primary mx-auto shadow-2xl"><a href="#">Upload</a></button>
                <button type="button" class="btn btn-warning mx-auto shadow-2xl"><a href="#">Cancel</a></button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- [ content ] Start -->
    </div>
  </div>
  </div>
  <!-- [ content ] End -->
  </div>
  </div>
  <!-- [ sample-page ] end -->
  </div>
  <!-- [ Main Content ] end -->
  </div>
  </div>
  </div>
  <!-- [ Main Content ] end -->
  <?php include '../includes/footer.php'; ?>
  <!-- Required Js -->
  <script src="../assets/js/plugins/simplebar.min.js"></script>
  <script src="../assets/js/plugins/popper.min.js"></script>
  <script src="../assets/js/icon/custom-icon.js"></script>
  <script src="../assets/js/plugins/feather.min.js"></script>
  <script src="../assets/js/component.js"></script>
  <script src="../assets/js/theme.js"></script>
  <script src="../assets/js/script.js"></script>

  <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
  </div>

  <script>
    layout_change('false');
    layout_theme_sidebar_change('dark');
    change_box_container('false');
    layout_caption_change('true');
    layout_rtl_change('false');
    preset_change('preset-1');
    main_layout_change('vertical');
  </script>

  <!-- [Page Specific JS] start -->
  <script src="../assets/js/plugins/clipboard.min.js"></script>
</body>
<!-- [Body] end -->
</html>
<?php } ?>
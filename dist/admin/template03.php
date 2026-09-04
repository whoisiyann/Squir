<?php
  session_start();
  if(isset($_POST['submit'])){

  } else {

?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<head>
  <title>template03</title>
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
<body>
  <!-- [ Pre-loader ] start -->
<div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
  <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
    <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
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
            <h5 class="mb-0 font-medium">Template 03</h5>
          </div>
          <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="../admin/dashboard.php">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript: void(0)">Other</a></li>
            <li class="breadcrumb-item" aria-current="page">Template 03</li>
          </ul>
        </div>
      </div>
      <!-- [ breadcrumb ] end -->

      <!-- [ Main Content ] start -->
      <div class="grid grid-cols-12 gap-x-6">
        <!-- [ sample-page ] start -->
        <div class="col-span-12">
          <div class="card">
            <div class="card-header">
              <h5>Title Here</h5>
            </div>
            <div class="card-body">
              <form class="form-horizontal"> <!-- Form elements -->
                <!-- Two Column Layout -->
                <div class="grid grid-cols-12 gap-6">

                  <!-- Left Column -->
                  <div class="col-span-12 md:col-span-6">
                    <h5>Title #1 Here (<span style="color: red; font-weight: bold;">*</span> Required)</h5>
                    <hr class="mb-3 dark:bg-primary-500 text-primary-500">
                    <div class="mb-3">
                      <label for="floatingInput" class="form-label">
                        Input 1 <span style="color:red;font-weight:bold;">*</span>
                      </label>
                      <input type="text" class="form-control" id="floatingInput" placeholder="Input 1">
                    </div>
                    <div class="mb-3">
                      <label for="floatingInput1" class="form-label">
                        Input 2 <span style="color:red;font-weight:bold;">*</span>
                      </label>
                      <input type="text" class="form-control" id="floatingInput1" placeholder="Input 2">
                    </div>
                  </div>

                  <!-- Right Column -->
                  <div class="col-span-12 md:col-span-6">
                    <h5>Title #2 Here (<span style="color: red; font-weight: bold;">*</span> Required)</h5>
                    <hr class="mb-3 dark:bg-primary-500 text-primary-500">
                    <div class="mb-3">
                      <label for="floatingInput2" class="form-label">
                        Input 3 <span style="color:red;font-weight:bold;">*</span>
                      </label>
                      <input type="text" class="form-control" id="floatingInput2" placeholder="Input 3">
                    </div>
                    <div class="mb-3">
                      <label for="floatingInput3" class="form-label">
                        Input 4 <span style="color:red;font-weight:bold;">*</span>
                      </label>
                      <input type="text" class="form-control" id="floatingInput3" placeholder="Input 4">
                    </div>
                  </div>

                </div>
                <div class="flex mt-1 justify-between items-center flex-wrap">
                  <div class="form-check">
                    <button type="button" class="btn btn-primary mx-auto shadow-2xl"><a href="#">Primary</a></button>
                    <button type="button" class="btn btn-secondary mx-auto shadow-2xl"><a href="#">Secondary</a></button>
                    <button type="button" class="btn btn-success mx-auto shadow-2xl"><a href="#">Success</a></button>
                    <button type="button" class="btn btn-danger mx-auto shadow-2xl"><a href="#">Danger</a></button>
                    <button type="button" class="btn btn-warning mx-auto shadow-2xl"><a href="#">Warning</a></button>
                    <button type="button" class="btn btn-dark mx-auto shadow-2xl"><a href="#">Dark</a></button>
                    <button type="button" class="btn btn-info mx-auto shadow-2xl"><a href="#">Info</a></button>
                  </div>
                </div>
              </form><!-- Form ends -->
            </div>
          </div>

        </div>
        <!-- [ sample-page ] end -->
      </div>
      <!-- [ Main Content ] end -->
    </div>
  </div>
  <!-- [ Main Content ] end -->

 <!-- Required Js -->
<script src="../assets/js/plugins/simplebar.min.js"></script>
<script src="../assets/js/plugins/popper.min.js"></script>
<script src="../assets/js/icon/custom-icon.js"></script>
<script src="../assets/js/plugins/feather.min.js"></script>
<script src="../assets/js/component.js"></script>
<script src="../assets/js/theme.js"></script>
<script src="../assets/js/script.js"></script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
<?php } ?>
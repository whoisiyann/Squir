<?php
  session_start();
  if(isset($_POST['submit'])){
    // Handle form submission logic here to database
  } else {
    //error message here if needed
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<head>
  <title>Template01</title>
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
            <h5 class="mb-0 font-medium">Template 01</h5>
          </div>
          <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="../admin/dashboard.php">Home</a></li>
            <li class="breadcrumb-item" aria-current="page">Template 01</li>
          </ul>
          <ul class="breadcrumb" rowspan="2">
            <button type="button" class="btn btn-primary flex justify-end items-center" command="show-modal" commandfor="addDialog"><i data-feather="plus" class="me-2"></i>Add Entry</button>
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
              <h5>Title Head Here</h5>
            </div>
            <div class="card-body">
              <form class="form-horizontal"> <!-- Form elements -->
                <table class="table table-striped table-bordered">
                  <thead>
                    <tr class="bg-dark text-white text-center font-weight-bold">
                      <th class="font-weight-bold">No</th>
                      <th class="font-weight-bold">User Name</th>
                      <th class="font-weight-bold">Fullname</th>
                      <th class="font-weight-bold">Contact Number</th>
                      <th class="font-weight-bold">Email</th>
                      <th class="font-weight-bold">Entry Date</th>
                      <th class="font-weight-bold">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="text-center">1</td>
                      <td class="text-center">admin</td>
                      <td class="text-center">Administrator</td>
                      <td class="text-center">1234567890</td>
                      <td class="text-center">admin@example.com</td>
                      <td class="text-center">2023-01-01</td>
                      <td class="text-center">
                        <button type="button" class="btn btn-primary btn-sm" command="show-modal" commandfor="editDialog">Edit</button>
                        <button type="button" class="btn btn-warning btn-sm" command="show-modal" commandfor="archiveDialog">Archive</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
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
              </form> <!-- Form ends -->
            </div>
          </div>
        </div>
        <!-- [ sample-page ] end -->
      </div>
      <!-- [ Main Content ] end -->
    </div>
  </div>
  <!-- [ Main Content ] end -->

    <!-- Add Entry Main Modal -->
    <dialog id="addDialog" aria-labelledby="dialog-title" class="fixed inset-0 w-screen h-screen p-0 bg-transparent border-0" >
    <div class="fixed inset-0 bg-black/50"></div>
      <div class="fixed inset-0 flex items-center justify-center p-6">
        <div class="w-[60%] max-w-5xl bg-white rounded-lg shadow-xl border border-gray-200 p-6">
          <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
            <h3 class="text-lg font-medium text-heading">Add entry</h3>
            <button type="button" command="close" commandfor="addDialog" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center" data-modal-hide="crud-modal">
              <i data-feather="x-circle"></i>
            </button>
          </div>
          <form class="form-horizontal"> <!-- Form elements -->
                <div class="mb-3">
                  <label for="floatingInput" class="form-label">User Name<span style="color: red; font-weight: bold;">*</span></label>
                  <input type="text" size="60" class="form-control" id="floatingInput" placeholder="Type User Name" />
                </div>
                <div class="mb-3">
                  <label for="floatingInput" class="form-label">Full Name<span style="color: red; font-weight: bold;">*</span></label>
                  <input type="text" size="60" class="form-control" id="floatingInput" placeholder="Type Full Name" />
                </div>
                <div class="mb-3">
                  <label for="floatingInput" class="form-label">Phone Number<span style="color: red; font-weight: bold;">*</span></label>
                  <input type="text" size="60" class="form-control" id="floatingInput" placeholder="Type Phone Number" />
                </div>
                <div class="mb-3">
                  <label for="floatingInput" class="form-label">Email Address<span style="color: red; font-weight: bold;">*</span></label>
                  <input type="text" size="60" class="form-control" id="floatingInput" placeholder="Type Email Address" />
                </div>
                <!-- <div class="flex items-center space-x-8 border-t border-default pt-4 md:pt-6">
                  <button type="button" command="close" commandfor="addDialog" class="inline-flex w-full justify-center rounded-md bg-blue-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-400 sm:ml-3 sm:w-auto">Save</button>
                  <button type="button" command="close" commandfor="addDialog" class="inline-flex w-full justify-center rounded-md bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-400 sm:ml-3 sm:w-auto">Cancel</button>
                </div> -->
                <div class="flex mt-4 justify-between items-center">
                  <div class="form-check">
                    <!-- <button type="button" class="btn btn-primary flex justify-end items-center" command="show-modal" commandfor="addDialog"><i data-feather="plus" class="me-2"></i>Add Entry</button> -->
                    <button type="button"class="btn btn-danger mx-auto shadow-2xlr" command="close" commandfor="addDialog"><a href="#">Save</a></button>
                    <button type="button" class="btn btn-warning mx-auto shadow-2xl" command="close" commandfor="addDialog"><a href="#">Cancel</a></button>
                  </div>
                </div>
          </form>
        </div>
      </div>
    </dialog>
  <!-- end Add Entry Main Modal -->

  <!-- Edit Main Modal -->
    <dialog id="editDialog" aria-labelledby="dialog-title" class="fixed inset-0 w-screen h-screen p-0 bg-transparent border-0" >
    <div class="fixed inset-0 bg-black/50"></div>
      <div class="fixed inset-0 flex items-center justify-center p-6">
        <div class="w-[60%] max-w-5xl bg-white rounded-lg shadow-xl border border-gray-200 p-6">
          <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
            <h3 class="text-lg font-medium text-heading">Edit entry</h3>
            <button type="button"  command="close" commandfor="editDialog" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center" data-modal-hide="crud-modal">
              <i data-feather="x-circle"></i>
            </button>
          </div>
          <form class="form-horizontal"> <!-- Form elements -->
                <div class="mb-3">
                  <label for="floatingInput" class="form-label">User Name<span style="color: red; font-weight: bold;">*</span></label>
                  <input type="text" size="60" class="form-control" id="floatingInput" value="admin" />
                </div>
                <div class="mb-3">
                  <label for="floatingInput" class="form-label">Full Name<span style="color: red; font-weight: bold;">*</span></label>
                  <input type="text" size="60" class="form-control" id="floatingInput" value="Juan dela Cruz" />
                </div>
                <div class="mb-3">
                  <label for="floatingInput" class="form-label">Phone Number<span style="color: red; font-weight: bold;">*</span></label>
                  <input type="text" size="60" class="form-control" id="floatingInput" value="1234567890" />
                </div>
                <div class="mb-3">
                  <label for="floatingInput" class="form-label">Email Address<span style="color: red; font-weight: bold;">*</span></label>
                  <input type="text" size="60" class="form-control" id="floatingInput" value="email@example.com" />
                </div>
                <!-- <div class="flex items-center space-x-8 border-t border-default pt-4 md:pt-6">
                  <button type="button" command="close" commandfor="editDialog" class="inline-flex w-full justify-center rounded-md bg-blue-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-400 sm:ml-3 sm:w-auto">Save</button>
                  <button type="button" command="close" commandfor="editDialog" class="inline-flex w-full justify-center rounded-md bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-400 sm:ml-3 sm:w-auto">Cancel</button>
                </div> -->
                <div class="flex mt-4 justify-between items-center">
                  <div class="form-check">
                    <button type="button" class="btn btn-danger mx-auto shadow-2xl" command="close" commandfor="editDialog"><a href="#">Update</a></button>
                    <button type="button" class="btn btn-warning mx-auto shadow-2xl" command="close" commandfor="editDialog"><a href="#">Cancel</a></button>
                  </div>
                </div>
          </form>
        </div>
      </div>
    </dialog>
  <!-- end Edit Main Modal -->

    <!-- Archive Main Modal -->
    <dialog id="archiveDialog" aria-labelledby="dialog-title" class="fixed inset-0 w-screen h-screen p-0 bg-transparent border-0" >
    <div class="fixed inset-0 bg-black/50"></div>
      <div class="fixed inset-0 flex items-center justify-center p-6">
        <div class="w-[60%] max-w-5xl bg-white rounded-lg shadow-xl border border-gray-200 p-6">
          <div class="flex items-center justify-between border-b border-default pb-4 md:pb-5">
            <h3 class="text-lg font-medium text-heading">Archive entry</h3>
            <button type="button"  command="close" commandfor="archiveDialog" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center" data-modal-hide="crud-modal">
              <i data-feather="x-circle"></i>
            </button>
          </div>
          <form class="form-horizontal"> <!-- Form elements -->
                <div class="mb-3">
                  <label for="floatingInput" class="form-label">Archiving this entry will move it to the archive section.</br></br> Are you sure you want to archive this entry?<span style="color: red; font-weight: bold;">*</span></label>
                </div>
                <!-- <div class="flex items-center space-x-8 border-t border-default pt-4 md:pt-6">
                  <button type="button" command="close" commandfor="archiveDialog" class="inline-flex w-full justify-center rounded-md bg-blue-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-400 sm:ml-3 sm:w-auto">Yes</button>
                  <button type="button" command="close" commandfor="archiveDialog" class="inline-flex w-full justify-center rounded-md bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-400 sm:ml-3 sm:w-auto">No</button>
                </div> -->
                <div class="flex mt-4 justify-between items-center">
                  <div class="form-check">
                    <button type="button" class="btn btn-danger mx-auto shadow-2xl" command="close" commandfor="archiveDialog"><a href="#">Yes</a></button>
                    <button type="button" class="btn btn-warning mx-auto shadow-2xl" command="close" commandfor="archiveDialog"><a href="#">Cancel</a></button>
                  </div>
                </div>
          </form>
        </div>
      </div>
    </dialog>
  <!-- end Archive Main Modal -->

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
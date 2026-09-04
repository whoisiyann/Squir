<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
  <title>Typography Template</title>
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
            <h5 class="mb-0 font-medium">Typography</h5>
          </div>
          <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
            <li class="breadcrumb-item" aria-current="page">Typography</li>
          </ul>
        </div>
      </div>
      <!-- [ breadcrumb ] end -->

      <!-- [ Main Content ] start -->
      <div class="grid grid-cols-12 gap-6">
        <!-- [ Typography ] start -->
        <div class="col-span-12">
          <div class="card">
            <div class="card-header">
              <h5>Headings</h5>
            </div>
            <div class="card-body pc-component">
              <h1>h1. Heading</h1>
              <div class="clearfix"></div>
              <h2>h2. Heading</h2>
              <div class="clearfix"></div>
              <h3>This is a H3</h3>
              <div class="clearfix"></div>
              <h4>This is a H4</h4>
              <div class="clearfix"></div>
              <h5>This is a H5</h5>
              <div class="clearfix"></div>
              <h6>This is a H6</h6>
            </div>
          </div>
        </div>
        <div class="col-span-12 md:col-span-6">
          <div class="card">
            <div class="card-header">
              <h5>Inline Text Elements</h5>
            </div>
            <div class="card-body pc-component">
              <p class="lead m-t-0">Your title goes here</p>
              You can use the mark tag to
              <mark>highlight</mark>
              text.
              <br />
              <del>This line of text is meant to be treated as deleted text.</del>
              <br />
              <ins>This line of text is meant to be treated as an addition to the document.</ins>
              <br />
              <strong>rendered as bold text</strong>
              <br />
              <em>rendered as italicized text</em>
            </div>
          </div>
        </div>
        <div class="col-span-12 md:col-span-6">
          <div class="card">
            <div class="card-header">
              <h5>Contextual Text Colors</h5>
            </div>
            <div class="card-body pc-component">
              <p class="text-muted mb-1">Fusce dapibus, tellus ac cursus commodo, tortor mauris nibh.</p>
              <p class="text-primary-500 mb-1">Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
              <p class="text-success-500 mb-1">Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
              <p class="text-info-500 mb-1">Maecenas sed diam eget risus varius blandit sit amet non magna.</p>
              <p class="text-warning-500 mb-1">Etiam porta sem malesuada magna mollis euismod.</p>
              <p class="text-danger-500 mb-1">Donec ullamcorper nulla non metus auctor fringilla.</p>
              <p class="text-dark-500 dark:text-white mb-1">Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
            </div>
          </div>
        </div>
        <div class="col-span-12 md:col-span-6 lg:col-span-4">
          <div class="card">
            <div class="card-header">
              <h5>Unordered</h5>
            </div>
            <div class="card-body pc-component">
              <ul class="list-disc ltr:pl-4 rtl:pr-4">
                <li>Lorem ipsum dolor sit amet</li>
                <li>Consectetur adipiscing elit</li>
                <li>Integer molestie lorem at massa</li>
                <li>Facilisis in pretium nisl aliquet</li>
                <li>
                  Nulla volutpat aliquam velit
                  <ul class="list-[circle] ltr:pl-4 rtl:pr-4">
                    <li>Phasellus iaculis neque</li>
                    <li>Purus sodales ultricies</li>
                    <li>Vestibulum laoreet porttitor sem</li>
                    <li>Ac tristique libero volutpat at</li>
                  </ul>
                </li>
                <li>Faucibus porta lacus fringilla vel</li>
                <li>Aenean sit amet erat nunc</li>
                <li>Eget porttitor lorem</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-span-12 md:col-span-6 lg:col-span-4">
          <div class="card">
            <div class="card-header">
              <h5>Ordered</h5>
            </div>
            <div class="card-body pc-component">
              <ol class="list-decimal ltr:pl-4 rtl:pr-4">
                <li>Lorem ipsum dolor sit amet</li>
                <li>Consectetur adipiscing elit</li>
                <li>Integer molestie lorem at massa</li>
                <li>Facilisis in pretium nisl aliquet</li>
                <li>
                  Nulla volutpat aliquam velit
                  <ul class="list-[circle] ltr:pl-4 rtl:pr-4">
                    <li>Phasellus iaculis neque</li>
                    <li>Purus sodales ultricies</li>
                    <li>Vestibulum laoreet porttitor sem</li>
                    <li>Ac tristique libero volutpat at</li>
                  </ul>
                </li>
                <li>Faucibus porta lacus fringilla vel</li>
                <li>Aenean sit amet erat nunc</li>
                <li>Eget porttitor lorem</li>
              </ol>
            </div>
          </div>
        </div>
        <div class="col-span-12 md:col-span-6 lg:col-span-4">
          <div class="card">
            <div class="card-header">
              <h5>Unstyled</h5>
            </div>
            <div class="card-body pc-component">
              <ul>
                <li>Lorem ipsum dolor sit amet</li>
                <li>
                  Integer molestie lorem at massa
                  <ul class="list-[circle] pl-4">
                    <li>Phasellus iaculis neque</li>
                  </ul>
                </li>
                <li>Faucibus porta lacus fringilla vel</li>
                <li>Eget porttitor lorem</li>
              </ul>
              <h5 class="mt-3">Inline</h5>
              <hr class="my-4 border-0 border-t border-theme-border dark:border-themedark-border" />
              <ul>
                <li class="inline-block mr-2">Lorem ipsum</li>
                <li class="inline-block mr-2">Phasellus iaculis</li>
                <li class="inline-block mr-2">Nulla volutpat</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-span-12 md:col-span-6">
          <div class="card">
            <div class="card-header">
              <h5>Blockquotes</h5>
            </div>
            <div class="card-body pc-component">
              <p class="text-muted mb-1">Your awesome text goes here.</p>
              <blockquote class="py-2 px-4 text-[1rem]">
                <p class="mb-2">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.
                </p>
                <footer
                  class="text-[80%] before:content-['—'] text-theme-bodycolor/70 dark:text-themedark-bodycolor/70">
                  Someone famous in
                  <cite title="Source Title">Source Title</cite>
                </footer>
              </blockquote>
              <p class="text-muted m-b-15 m-t-20">
                Add
                <code class="text-danger-400 text-sm">.text-right</code>
                for a blockquote with right-aligned content.
              </p>
              <blockquote class="py-2 px-4 text-[1rem] text-right">
                <p class="mb-2">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.
                </p>
                <footer
                  class="text-[80%] before:content-['—'] text-theme-bodycolor/70 dark:text-themedark-bodycolor/70">
                  Someone famous in
                  <cite title="Source Title">Source Title</cite>
                </footer>
              </blockquote>
            </div>
          </div>
        </div>
        <div class="col-span-12 md:col-span-6">
          <div class="card">
            <div class="card-header">
              <h5>Horizontal Description</h5>
            </div>
            <div class="card-body pc-component">
              <dl class="grid grid-cols-12 gap-6">
                <dt class="col-span-12 sm:col-span-3 font-semibold">Description lists</dt>
                <dd class="col-span-12 sm:col-span-9">A description list is perfect for defining terms.</dd>
                <dt class="col-span-12 sm:col-span-3 font-semibold">Euismod</dt>
                <dd class="col-span-12 sm:col-span-9">
                  Vestibulum id ligula porta felis euismod semper eget lacinia odio sem nec elit.
                </dd>
                <dd class="col-span-12 sm:col-span-9">Donec id elit non mi porta gravida at eget metus.</dd>
                <dt class="col-span-12 sm:col-span-3 font-semibold">Malesuada porta</dt>
                <dd class="col-span-12 sm:col-span-9">Etiam porta sem malesuada magna mollis euismod.</dd>
                <dt class="col-span-12 sm:col-span-3 font-semibold">Truncated term is truncated</dt>
                <dd class="col-span-12 sm:col-span-9">
                  Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit
                  amet risus.
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <!-- [ Typography ] end -->
      </div>
      <!-- [ Main Content ] end -->
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
</body>
</html>
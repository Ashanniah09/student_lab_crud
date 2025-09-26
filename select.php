<?php
require __DIR__ . '/db_connect.php';

/* ----------------------- query params ----------------------- */
$perPage = max(1, intval($_GET['per'] ?? 5));
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$allowedSort = ['id','student_id','first_name','last_name','suffix','email','course','year','section','remarks','status','created_at'];
$sort = $_GET['sort'] ?? 'created_at';
$sort = in_array($sort, $allowedSort, true) ? $sort : 'created_at';
$dir  = (strtolower($_GET['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

$q    = trim($_GET['q'] ?? '');

/* keep rest of query string for links */
$qs = $_GET;
unset($qs['page']);

/* ----------------------- filtering -------------------------- */
$whereSql = '';
$params   = [];
$types    = '';

if ($q !== '') {
  $whereSql = "WHERE (
      CAST(id AS CHAR) LIKE ? OR
      student_id       LIKE ? OR
      first_name       LIKE ? OR
      middle_name      LIKE ? OR
      last_name        LIKE ? OR
      suffix           LIKE ? OR
      email            LIKE ? OR
      course           LIKE ? OR
      year             LIKE ? OR
      section          LIKE ? OR
      remarks          LIKE ? OR
      status           LIKE ?
    )";
  $needle = "%$q%";
  $params = array_fill(0, 12, $needle);
  $types  = str_repeat('s', 12);
}

/* ----------------------- counting --------------------------- */
if ($whereSql) {
  $countSql = "SELECT COUNT(*) AS c FROM students $whereSql";
  $stmt = $mysqli->prepare($countSql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $total = (int)$stmt->get_result()->fetch_assoc()['c'];
  $stmt->close();
} else {
  $totalRes = $mysqli->query("SELECT COUNT(*) AS c FROM students");
  $total    = (int)($totalRes ? $totalRes->fetch_assoc()['c'] : 0);
}
$pages = max(1, (int)ceil($total / $perPage));

/* ----------------------- fetch page ------------------------- */
$sql  = "SELECT * FROM students $whereSql ORDER BY $sort $dir LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
if ($whereSql) {
  $stmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
} else {
  $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$res  = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

/* flash message (for success popup or inline error) */
$status = $_GET['status'] ?? '';
$msg    = $_GET['msg'] ?? '';

function sortLink($label,$key,$sort,$dir,$page,$per,$q){
  $next = ($sort===$key && strtolower($dir)==='asc') ? 'desc' : 'asc';
  $icon = ($sort===$key) ? (strtolower($dir)==='asc' ? ' ▲' : ' ▼') : '';
  $qstr = ['sort'=>$key,'dir'=>$next,'page'=>$page,'per'=>$per];
  if ($q!=='') $qstr['q']=$q;
  return '<a class="text-decoration-none sort-link" href="?'.http_build_query($qstr).'">'.$label.$icon.'</a>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Students</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Your theme CSS -->
  <link rel="stylesheet" href="assets/css/crud.css">
</head>

<body class="theme-dark">
<div class="container my-5 px-4">
  <div class="table-wrapper">

    <div class="table-title d-flex align-items-center justify-content-between flex-wrap gap-3">
      <h2 class="m-0 fw-bold">Manage Students</h2>

      <div class="d-flex align-items-center gap-3 flex-wrap">
        <!-- Search -->
        <form class="search-wrap" role="search" method="get" action="">
          <?php foreach (['sort','dir','per'] as $keep)
            if (isset($_GET[$keep])) echo '<input type="hidden" name="'.$keep.'" value="'.htmlspecialchars($_GET[$keep]).'">'; ?>
          <div class="input-group search-pill">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" type="search" name="q" placeholder="Search" value="<?= htmlspecialchars($q) ?>" aria-label="Search">
            <?php if ($q!==''): $qsNoQ=$qs; unset($qsNoQ['q']); ?>
              <a class="btn btn-clear" title="Clear" href="?<?= http_build_query($qsNoQ) ?>"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
          </div>
        </form>

        <div class="ms-auto d-flex gap-2">
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> <span>Add New Student</span>
          </button>
        </div>
      </div>
    </div>

    <!-- show only error inline (success uses popup) -->
    <?php if ($msg && $status !== 'success'): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg, ENT_QUOTES) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form id="bulkForm" method="post" action="delete_bulk.php">
      <input type="hidden" name="ids" id="bulkIds">

      <table class="table table-striped table-hover align-middle">
        <thead>
        <tr>
          <th style="width:52px">
            <span class="custom-checkbox">
              <input type="checkbox" id="selectAll">
              <label for="selectAll"></label>
            </span>
          </th>
          <th><?= sortLink('Student ID','student_id',$sort,$dir,$page,$perPage,$q) ?></th>
          <th><?= sortLink('First Name','first_name',$sort,$dir,$page,$perPage,$q) ?></th>
          <th>Middle Name</th>
          <th><?= sortLink('Last Name','last_name',$sort,$dir,$page,$perPage,$q) ?></th>
          <th><?= sortLink('Suffix','suffix',$sort,$dir,$page,$perPage,$q) ?></th>
          <th><?= sortLink('Email Address','email',$sort,$dir,$page,$perPage,$q) ?></th>
          <th><?= sortLink('Course','course',$sort,$dir,$page,$perPage,$q) ?></th>
          <th><?= sortLink('Year','year',$sort,$dir,$page,$perPage,$q) ?></th>
          <th><?= sortLink('Section','section',$sort,$dir,$page,$perPage,$q) ?></th>
          <th><?= sortLink('Remarks','remarks',$sort,$dir,$page,$perPage,$q) ?></th>
          <th><?= sortLink('Status','status',$sort,$dir,$page,$perPage,$q) ?></th>
          <th class="text-end">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="12" class="text-center text-muted py-4">No records found.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td>
              <span class="custom-checkbox">
                <input class="row-check" type="checkbox" id="c<?= $r['id'] ?>" value="<?= $r['id'] ?>">
                <label for="c<?= $r['id'] ?>"></label>
              </span>
            </td>
            <td><?= htmlspecialchars($r['student_id'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['first_name']) ?></td>
            <td><?= htmlspecialchars($r['middle_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['last_name']) ?></td>
            <td><?= htmlspecialchars($r['suffix'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($r['course'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['year'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['section'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['remarks'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
            <td class="text-end">
            <!-- VIEW -->
            <a href="#"
                class="view me-2"
                data-bs-toggle="modal"
                data-bs-target="#viewModal"
                title="View details"
                data-id="<?= $r['id'] ?>"
                data-student_id="<?= htmlspecialchars($r['student_id'] ?? '',ENT_QUOTES) ?>"
                data-first="<?= htmlspecialchars($r['first_name'] ?? '',ENT_QUOTES) ?>"
                data-middle="<?= htmlspecialchars($r['middle_name'] ?? '',ENT_QUOTES) ?>"
                data-last="<?= htmlspecialchars($r['last_name'] ?? '',ENT_QUOTES) ?>"
                data-suffix="<?= htmlspecialchars($r['suffix'] ?? '',ENT_QUOTES) ?>"
                data-email="<?= htmlspecialchars($r['email'] ?? '',ENT_QUOTES) ?>"
                data-course="<?= htmlspecialchars($r['course'] ?? '',ENT_QUOTES) ?>"
                data-year="<?= htmlspecialchars($r['year'] ?? '',ENT_QUOTES) ?>"
                data-section="<?= htmlspecialchars($r['section'] ?? '',ENT_QUOTES) ?>"
                data-remarks="<?= htmlspecialchars($r['remarks'] ?? '',ENT_QUOTES) ?>"
                data-status="<?= htmlspecialchars($r['status'] ?? '',ENT_QUOTES) ?>"
            ><i class="bi bi-eye fs-5"></i></a>

            <!-- EDIT -->
            <a href="#"
                class="edit me-2"
                data-bs-toggle="modal"
                data-bs-target="#editModal"
                title="Edit"
                data-id="<?= $r['id'] ?>"
                data-student_id="<?= htmlspecialchars($r['student_id'] ?? '',ENT_QUOTES) ?>"
                data-first="<?= htmlspecialchars($r['first_name'] ?? '',ENT_QUOTES) ?>"
                data-middle="<?= htmlspecialchars($r['middle_name'] ?? '',ENT_QUOTES) ?>"
                data-last="<?= htmlspecialchars($r['last_name'] ?? '',ENT_QUOTES) ?>"
                data-email="<?= htmlspecialchars($r['email'] ?? '',ENT_QUOTES) ?>"
                data-course="<?= htmlspecialchars($r['course'] ?? '',ENT_QUOTES) ?>"
                data-year="<?= htmlspecialchars($r['year'] ?? '',ENT_QUOTES) ?>"
                data-suffix="<?= htmlspecialchars($r['suffix'] ?? '',ENT_QUOTES) ?>"
                data-section="<?= htmlspecialchars($r['section'] ?? '',ENT_QUOTES) ?>"
                data-status="<?= htmlspecialchars($r['status'] ?? '',ENT_QUOTES) ?>"
                data-remarks="<?= htmlspecialchars($r['remarks'] ?? '',ENT_QUOTES) ?>"
            ><i class="bi bi-pencil-square fs-5"></i></a>

            <!-- DELETE -->
            <a href="#"
                class="delete"
                data-bs-toggle="modal"
                data-bs-target="#deleteConfirmModal"
                data-id="<?= $r['id'] ?>"
                data-name="<?= htmlspecialchars(trim(($r['first_name'] ?? '').' '.($r['last_name'] ?? '')),ENT_QUOTES) ?>"
                title="Delete"><i class="bi bi-trash-fill fs-5"></i></a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </form>

    <div class="table-footer">
      <div class="hint-text">
        Showing <b><?= $total ? min($perPage, max(0, $total - $offset)) : 0 ?></b> out of <b><?= $total ?></b> entries
      </div>
      <nav>
        <ul class="pagination mb-0">
          <?php
            $prevQ = ['page'=>max(1,$page-1),'per'=>$perPage,'sort'=>$sort,'dir'=>$dir]; if($q!=='') $prevQ['q']=$q;
            $nextQ = ['page'=>min($pages,$page+1),'per'=>$perPage,'sort'=>$sort,'dir'=>$dir]; if($q!=='') $nextQ['q']=$q;
          ?>
          <li class="page-item <?= $page<=1?'disabled':'' ?>">
            <a class="page-link" href="?<?= http_build_query($prevQ) ?>">Previous</a>
          </li>
          <?php for($p=1;$p<=$pages;$p++): $pQ=['page'=>$p,'per'=>$perPage,'sort'=>$sort,'dir'=>$dir]; if($q!=='') $pQ['q']=$q; ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
              <a class="page-link" href="?<?= http_build_query($pQ) ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
            <a class="page-link" href="?<?= http_build_query($nextQ) ?>">Next</a>
          </li>
        </ul>
      </nav>
    </div>

  </div>
</div>

<!-- Add Modal-->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form id="addForm" class="modal-content needs-validation" action="insert.php" method="post" novalidate>
      <div class="modal-header bg-brand text-white py-3 rounded-top-4 border-0">
        <h5 class="modal-title fw-bold text-uppercase">Add Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Student ID</label>
            <input type="text" name="student_id" class="form-control soft-input"
                   required pattern="^[A-Za-z0-9\-_.]+$" maxlength="50">
            <div class="invalid-feedback">Student ID is required (letters/numbers, dashes, underscores, or dots).</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Course</label>
            <input type="text" name="course" class="form-control soft-input"
                   required pattern="^[A-Za-z0-9\s\-&()./]+$" maxlength="100">
            <div class="invalid-feedback">Course is required (use letters, numbers and common symbols only).</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Year</label>
            <select name="year" class="form-select soft-input" required>
              <option value="" selected>Select</option>
              <option>1st</option><option>2nd</option><option>3rd</option><option>4th</option><option>5th</option>
            </select>
            <div class="invalid-feedback">Year is required.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-control soft-input"
                   required pattern="^[A-Za-z\s]+$" maxlength="100">
            <div class="invalid-feedback">First name is required and must contain letters only.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Middle Name (Optional)</label>
            <input type="text" name="middle_name" class="form-control soft-input"
                   pattern="^[A-Za-z\s]*$" maxlength="100">
            <div class="invalid-feedback">Middle name should contain letters only.</div>
          </div>

          <div class="col-md-8">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-control soft-input"
                   required pattern="^[A-Za-z\s]+$" maxlength="100">
            <div class="invalid-feedback">Last name is required and must contain letters only.</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Suffix Name</label>
            <select name="suffix" class="form-select soft-input" required>
              <option value="" selected>Select</option>
              <option value="N/A">N/A</option>
              <option>Jr.</option><option>Sr.</option><option>I</option><option>II</option><option>III</option><option>IV</option>
            </select>
            <div class="invalid-feedback">Suffix is required. Select <strong>N/A</strong> if you don’t have one.</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Section</label>
            <input type="text" name="section" class="form-control soft-input"
                   required pattern="^[A-Za-z0-9\- ]+$" maxlength="50" placeholder="e.g., A-1">
            <div class="invalid-feedback">Section is required (letters, numbers, spaces or dashes).</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select soft-input" required>
              <option value="" selected>Select</option>
              <option>Active</option><option>Inactive</option>
            </select>
            <div class="invalid-feedback">Status is required.</div>
          </div>

          <div class="col-md-12">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control soft-input" required maxlength="190">
            <div class="invalid-feedback">Please enter a valid email address.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Remarks</label>
            <select name="remarks" class="form-select soft-input">
              <option value="" selected>Select</option>
              <option value="Old Student">Old Student</option>
              <option value="New Student">New Student</option>
              <option value="Transferee">Transferee</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 p-4">
        <button class="btn btn-light-dark px-4 py-2 rounded-3 ms-auto">Add Student</button>
      </div>
    </form>
  </div>
</div>
<!--VIEW DETAILS Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content details-card">
      <div class="modal-header">
        <h5 class="modal-title">User Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span id="view-status-badge"  class="badge badge-pill"></span>
          <span id="view-remarks-badge" class="badge badge-pill badge-hint d-none"></span>
          <span class="ms-auto small text-muted" id="view-email-top"></span>
        </div>

        <table class="details-table">
          <thead>
            <tr>
              <th style="width:220px">Field</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="details-field"><i class="bi bi-person-badge"></i> Full Name</td>
              <td id="v-fullname"></td>
            </tr>
            <tr>
              <td class="details-field"><i class="bi bi-envelope"></i> Email</td>
              <td id="v-email"></td>
            </tr>
            <tr>
              <td class="details-field"><i class="bi bi-hash"></i> Student ID</td>
              <td id="v-studentid"></td>
            </tr>
            <tr>
              <td class="details-field"><i class="bi bi-mortarboard"></i> Course</td>
              <td id="v-course"></td>
            </tr>
            <tr>
              <td class="details-field"><i class="bi bi-123"></i> Year</td>
              <td id="v-year"></td>
            </tr>
            <tr>
              <td class="details-field"><i class="bi bi-diagram-3"></i> Section</td>
              <td id="v-section"></td>
            </tr>
            <tr>
              <td class="details-field"><i class="bi bi-type"></i> Suffix</td>
              <td id="v-suffix"></td>
            </tr>
            <tr>
              <td class="details-field"><i class="bi bi-toggle-on"></i> Status</td>
              <td id="v-status"></td>
            </tr>
            <tr>
              <td class="details-field"><i class="bi bi-chat-left-text"></i> Remarks</td>
              <td id="v-remarks"></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light-dark ms-auto" data-bs-dismiss="modal">Back to list</button>
      </div>
    </div>
  </div>
</div>
<!-- EDIT Modal (mirrors Add)-->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content needs-validation" action="update.php" method="post" novalidate>
      <div class="modal-header bg-brand text-white py-3 rounded-top-4 border-0">
        <h5 class="modal-title fw-bold text-uppercase">Edit Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4">
        <input type="hidden" name="id" id="edit-id">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Student ID</label>
            <input type="text" name="student_id" id="edit-student_id" class="form-control soft-input"
                   required pattern="^[A-Za-z0-9\-_.]+$" maxlength="50">
            <div class="invalid-feedback">Student ID is required (letters/numbers, dashes, underscores, or dots).</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Course</label>
            <input type="text" name="course" id="edit-course" class="form-control soft-input"
                   required pattern="^[A-Za-z0-9\s\-&()./]+$" maxlength="100">
            <div class="invalid-feedback">Course is required.</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Year</label>
            <select name="year" id="edit-year" class="form-select soft-input" required>
              <option value="" selected>Select</option>
              <option>1st</option><option>2nd</option><option>3rd</option><option>4th</option><option>5th</option>
            </select>
            <div class="invalid-feedback">Year is required.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" id="edit-first" class="form-control soft-input"
                   required pattern="^[A-Za-z\s]+$" maxlength="100">
            <div class="invalid-feedback">First name is required and must contain letters only.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Middle Name (Optional)</label>
            <input type="text" name="middle_name" id="edit-middle" class="form-control soft-input"
                   pattern="^[A-Za-z\s]*$" maxlength="100">
            <div class="invalid-feedback">Middle name should contain letters only.</div>
          </div>

          <div class="col-md-8">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" id="edit-last" class="form-control soft-input"
                   required pattern="^[A-Za-z\s]+$" maxlength="100">
            <div class="invalid-feedback">Last name is required and must contain letters only.</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Suffix Name</label>
            <select name="suffix" id="edit-suffix" class="form-select soft-input" required>
              <option value="" selected>Select</option>
              <option value="N/A">N/A</option>
              <option>Jr.</option><option>Sr.</option><option>I</option><option>II</option><option>III</option><option>IV</option>
            </select>
            <div class="invalid-feedback">Suffix is required. Select <strong>N/A</strong> if none.</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Section</label>
            <input type="text" name="section" id="edit-section" class="form-control soft-input"
                   required pattern="^[A-Za-z0-9\- ]+$" maxlength="50" placeholder="e.g., A-1">
            <div class="invalid-feedback">Section is required (letters, numbers, spaces or dashes).</div>
          </div>

          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" id="edit-status" class="form-select soft-input" required>
              <option value="" selected>Select</option>
              <option>Active</option><option>Inactive</option>
            </select>
            <div class="invalid-feedback">Status is required.</div>
          </div>

          <div class="col-md-12">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" id="edit-email" class="form-control soft-input" required maxlength="190">
            <div class="invalid-feedback">Please enter a valid email address.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Remarks</label>
            <select name="remarks" id="edit-remarks" class="form-select soft-input">
              <option value="" selected>Select</option>
              <option value="Old Student">Old Student</option>
              <option value="New Student">New Student</option>
              <option value="Transferee">Transferee</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 p-4">
        <button class="btn btn-light-dark px-4 py-2 rounded-3 ms-auto">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!--Success Popup-->
<div class="modal fade" id="successAddedModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content success-card">
      <div class="success-body">
        <div class="success-icon"><i class="bi bi-check-lg"></i></div>
        <h4 class="success-title">Added Student Successfully!</h4>
        <div class="text-end">
          <button type="button" id="successContinueBtn" class="btn success-cta" data-bs-dismiss="modal">
            Continue
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Popups (success-style) -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content confirm-card" action="delete.php" method="post">
      <div class="confirm-body">
        <input type="hidden" name="id" id="delete-id">
        <div class="confirm-icon"><i class="bi bi-x-lg"></i></div>
        <h4 class="confirm-title">Delete Student</h4>
        <p class="confirm-text">
          Are you sure you want to delete <strong id="delete-name"></strong>?<br/>
          <span style="color:#fbb4b4">This action cannot be undone.</span>
        </p>
        <div class="confirm-actions">
          <button type="button" class="btn confirm-cancel" data-bs-dismiss="modal">Cancel</button>
          <button class="btn confirm-cta" type="submit">Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="bulkDeleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content confirm-card" method="post" action="delete_bulk.php" onsubmit="return prepBulkDelete()">
      <div class="confirm-body">
        <input type="hidden" name="ids" id="bulkIdsConfirm">
        <div class="confirm-icon"><i class="bi bi-x-lg"></i></div>
        <h4 class="confirm-title">Delete Selected</h4>
        <p class="confirm-text">
          Are you sure you want to delete the selected records?<br/>
          <span style="color:#fbb4b4">This action cannot be undone.</span>
        </p>
        <div class="confirm-actions">
          <button type="button" class="btn confirm-cancel" data-bs-dismiss="modal">Cancel</button>
          <button class="btn confirm-cta" type="submit">Delete</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Client-side validation
  (() => {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', evt => {
        if (!form.checkValidity()) {
          evt.preventDefault();
          evt.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();
 // VIEW modal populate
const viewModal = document.getElementById('viewModal');
if (viewModal){
  viewModal.addEventListener('show.bs.modal', (e) => {
    const b = e.relatedTarget;

    const first  = b.getAttribute('data-first')  || '';
    const middle = b.getAttribute('data-middle') || '';
    const last   = b.getAttribute('data-last')   || '';
    const suffix = b.getAttribute('data-suffix') || '';
    const fullname = [first, middle, last].filter(Boolean).join(' ') + (suffix ? ' ' + suffix : '');

    // Fill fields
    document.getElementById('v-fullname').textContent = fullname.trim();
    document.getElementById('v-email').textContent    = b.getAttribute('data-email')     || '';
    document.getElementById('v-studentid').textContent= b.getAttribute('data-student_id')|| '';
    document.getElementById('v-course').textContent   = b.getAttribute('data-course')    || '';
    document.getElementById('v-year').textContent     = b.getAttribute('data-year')      || '';
    document.getElementById('v-section').textContent  = b.getAttribute('data-section')   || '';
    document.getElementById('v-suffix').textContent   = suffix || 'N/A';
    document.getElementById('v-status').textContent   = b.getAttribute('data-status')    || '';
    document.getElementById('v-remarks').textContent  = b.getAttribute('data-remarks')   || '';
    document.getElementById('view-email-top').textContent = b.getAttribute('data-email') || '';

    // Badges 
    const sBadge = document.getElementById('view-status-badge');
    const status = (b.getAttribute('data-status') || '').toLowerCase();
    sBadge.textContent = (status || 'Status');
    sBadge.className = 'badge badge-pill ' + (status==='active' ? 'badge-active' : 'badge-inactive');

    const rBadge = document.getElementById('view-remarks-badge');
    const remarks = b.getAttribute('data-remarks') || '';
    if (remarks) {
      rBadge.textContent = remarks;
      rBadge.classList.remove('d-none');
    } else {
      rBadge.classList.add('d-none');
    }
  });
}
  // Edit modal populate (all fields)
  const editModal = document.getElementById('editModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', (e) => {
      const b = e.relatedTarget;
      const set = (id, v='') => { const el = document.getElementById(id); if (el) el.value = v ?? ''; };

      set('edit-id',          b.getAttribute('data-id'));
      set('edit-student_id',  b.getAttribute('data-student_id'));
      set('edit-course',      b.getAttribute('data-course'));
      set('edit-year',        b.getAttribute('data-year'));
      set('edit-first',       b.getAttribute('data-first'));
      set('edit-middle',      b.getAttribute('data-middle'));
      set('edit-last',        b.getAttribute('data-last'));
      set('edit-suffix',      b.getAttribute('data-suffix') || 'N/A');
      set('edit-section',     b.getAttribute('data-section'));
      set('edit-status',      b.getAttribute('data-status'));
      set('edit-email',       b.getAttribute('data-email'));
      set('edit-remarks',     b.getAttribute('data-remarks') || '');
    });
  }

  // Delete modal populate (single)
  const delModal = document.getElementById('deleteConfirmModal');
  if (delModal) {
    delModal.addEventListener('show.bs.modal', (e) => {
      const b = e.relatedTarget;
      document.getElementById('delete-id').value = b.getAttribute('data-id');
      document.getElementById('delete-name').textContent = b.getAttribute('data-name') || '';
    });
  }

  // Select all + bulk state
  const selectAll = document.getElementById('selectAll');
  const rowChecks = Array.from(document.querySelectorAll('.row-check'));
  const bulkBtn   = document.getElementById('bulkDeleteBtn');

  function refreshBulkState(){
    const any = rowChecks.some(c => c.checked);
    if (bulkBtn) bulkBtn.disabled = !any;
  }
  if (selectAll){
    selectAll.addEventListener('change', ()=>{
      rowChecks.forEach(c => c.checked = selectAll.checked);
      refreshBulkState();
    });
  }
  rowChecks.forEach(c => c.addEventListener('change', ()=>{
    if (!c.checked && selectAll) selectAll.checked = false;
    refreshBulkState();
  }));

  function prepBulkDelete(){
    const ids = rowChecks.filter(c => c.checked).map(c => c.value).join(',');
    const hiddenA = document.getElementById('bulkIds');
    const hiddenB = document.getElementById('bulkIdsConfirm');
    if (hiddenA) hiddenA.value = ids;
    if (hiddenB) hiddenB.value = ids;
    return ids.length>0;
  }
  window.prepBulkDelete = prepBulkDelete;

  // Show success popup when ?status=success
  (function showSuccessIfPresent(){
    const params = new URLSearchParams(window.location.search);
    if (params.get('status') === 'success') {
      const modalEl = document.getElementById('successAddedModal');
      const modal   = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: false });
      modal.show();

      // Clear status/msg on Continue and reset to page 1
      document.getElementById('successContinueBtn').addEventListener('click', ()=>{
        const keep = new URLSearchParams(window.location.search);
        keep.delete('status'); keep.delete('msg');
        keep.set('page','1');
        const base = window.location.pathname;
        const q = keep.toString();
        window.location.href = q ? `${base}?${q}` : base;
      });
    }
  })();
   (function () {
    const form = document.querySelector('form[role="search"]');
    if (!form) return;

    // Live search (debounced).
    const input = form.querySelector('input[name="q"]');
    let t = null, last = input.value;
    input.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => {
        if (input.value !== last) {
          last = input.value;
          form.querySelector('input[name="page"]').value = '1';
          form.submit();
        }
      }, 400); 
    });

    // If user presses Enter, also reset to page 1
    form.addEventListener('submit', () => {
      const p = form.querySelector('input[name="page"]');
      if (p) p.value = '1';
    });
  })();
</script>
</script>
</body>
</html>

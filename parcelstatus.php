<?php
include_once 'auth.php';
authorize([2]);
include_once 'database.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Keep a small parcel list for search functionality (main parcel table)
    $stmt = $conn->prepare("SELECT fld_parcel_ID, fld_parcel_status FROM tbl_parcel_ezparcel");
    $stmt->execute();
    $parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$parcels) $parcels = [];

    // Describe collection table to build headers
    $cols = [];
    try {
        $colStmt = $conn->query("DESCRIBE tbl_parcelcollection_ezparcel");
        $colInfo = $colStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($colInfo as $c) $cols[] = $c['Field'];
    } catch (Exception $e) {
        // table may not exist yet — leave columns empty
        $cols = [];
    }

    // Fetch collection rows for the logged-in user
    $collections = [];
    if (!empty($_SESSION['user_email']) && !empty($cols)) {
        $cstmt = $conn->prepare("SELECT * FROM tbl_parcelcollection_ezparcel WHERE fld_user_email = :email");
        $cstmt->execute([':email' => $_SESSION['user_email']]);
        $collections = $cstmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parcel Status</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: Arial, sans-serif; background:#f4f6fb; margin:0; }
.container { max-width:600px; margin:30px auto; background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
h2 { margin-top:0; }
.search-bar { display:flex; gap:10px; margin-bottom:20px; flex-wrap: wrap; }
.search-bar input { flex:1; padding:10px; border-radius:8px; border:1px solid #ccc; }
.search-bar button { padding:10px 14px; border:none; border-radius:8px; background:#1f6fff; color:#fff; cursor:pointer; }
#backBtn { background:#ccc; color:#000; }
#parcelList { list-style:none; padding:0; margin:0; }
#parcelList li { display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #eee; font-weight:500; }
.badge { padding:4px 12px; border-radius:20px; font-size:13px; font-weight:bold; }
.badge.complete { background:#d4edda; color:#155724; }
.badge.incomplete { background:#f8d7da; color:#721c24; }
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Parcel Status</h2>

    <div class="search-bar">
        <input type="text" id="trackingInput" placeholder="Enter tracking number">
        <button onclick="searchTracking()"><i class="fa-solid fa-magnifying-glass"></i></button>
        <button onclick="pasteClipboard()"><i class="fa-solid fa-paste"></i></button>
        <button id="backBtn" onclick="backToList()" style="display:none;">Back</button>
    </div>

    <div id="statusMessage" style="margin-bottom:12px;min-height:26px"></div>

    <div id="collectionWrap">
        <table id="collectionTable" style="width:100%;border-collapse:collapse;">
            <thead id="collectionHead">
                <!-- headers injected by JS -->
            </thead>
            <tbody id="collectionBody">
                <!-- rows injected by JS -->
            </tbody>
        </table>
    </div>
</div>

<script>
// PHP → JS
let parcels = <?php echo json_encode($parcels, JSON_UNESCAPED_SLASHES); ?>;
let collectionCols = <?php echo json_encode($cols ?? [], JSON_UNESCAPED_SLASHES); ?>;
let collections = <?php echo json_encode($collections ?? [], JSON_UNESCAPED_SLASHES); ?>;

// Map status to badge
function mapStatus(status){
    return status.toLowerCase() === 'collected'
        ? { text: 'Complete', class: 'complete' }
        : { text: 'Incomplete', class: 'incomplete' };
}

// Render parcels
// Render collection table headers and rows
function renderCollections(list){
    const head = document.getElementById('collectionHead');
    const body = document.getElementById('collectionBody');
    head.innerHTML = '';
    body.innerHTML = '';

    // Build header based on discovered columns; if none, display placeholder
    if (!collectionCols || collectionCols.length === 0) {
        head.innerHTML = `<tr><th>No collection table available</th></tr>`;
        return;
    }

    let hrow = '<tr>';
    collectionCols.forEach(c => { hrow += `<th style="text-align:left;padding:8px;border-bottom:1px solid #eee">${c}</th>`; });
    hrow += '</tr>';
    head.innerHTML = hrow;

    if (!list || list.length === 0) {
        // empty body but keep headers
        return;
    }

    list.forEach(r => {
        let row = '<tr>';
        collectionCols.forEach(c => {
            const v = r[c] !== undefined && r[c] !== null ? String(r[c]) : '';
            row += `<td style="padding:8px;border-bottom:1px solid #f2f2f2">${escapeHtml(v)}</td>`;
        });
        row += '</tr>';
        body.innerHTML += row;
    });
}

// Search
function searchTracking(){
    const keyword = document.getElementById('trackingInput').value.trim();
    const backBtn = document.getElementById('backBtn');
    const msg = document.getElementById('statusMessage');
    msg.innerHTML = '';

    if(!keyword){ 
        renderParcels(parcels); 
        backBtn.style.display = 'none';
        return; 
    }

    const keyLower = keyword.toLowerCase();
    // Exact match first
    const exact = parcels.find(p => p.fld_parcel_ID.toLowerCase() === keyLower);
    if (exact) {
        // If parcel exists, determine readiness
        const status = (exact.fld_parcel_status || '').toLowerCase();
        if (status === 'collected') {
            msg.innerHTML = `<div style="padding:10px;border-radius:8px;background:#fff3cd;color:#856404;border:1px solid #ffeeba">Parcel <strong>${escapeHtml(keyword)}</strong> has already been collected.</div>`;
        } else {
            msg.innerHTML = `<div style="padding:10px;border-radius:8px;background:#d4edda;color:#155724;border:1px solid #c3e6cb">Parcel <strong>${escapeHtml(keyword)}</strong> is ready to be collected.</div>`;
        }

        renderParcels([exact]);
        backBtn.style.display = 'inline-block';
        return;
    }

    // No exact match — show failure
    msg.innerHTML = `<div style="padding:10px;border-radius:8px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb">No parcel found with ID <strong>${escapeHtml(keyword)}</strong>. It has not arrived yet.</div>`;
    renderParcels([]);
    backBtn.style.display = 'inline-block';
}

// Paste from clipboard
async function pasteClipboard(){
    try{
        const text = await navigator.clipboard.readText();
        document.getElementById('trackingInput').value = text;
        searchTracking();
    }catch(err){
        alert('Unable to access clipboard');
    }
}

// Simple HTML escape
function escapeHtml(unsafe) {
    return unsafe.replace(/[&<"'`=\/]/g, function (s) {
        return ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        })[s];
    });
}

// Back to full list
function backToList(){
    document.getElementById('trackingInput').value = '';
    document.getElementById('statusMessage').innerHTML = '';
    renderCollections(collections);
    document.getElementById('backBtn').style.display = 'none';
}

// Initial render
renderCollections(collections);
</script>

</body>
</html>

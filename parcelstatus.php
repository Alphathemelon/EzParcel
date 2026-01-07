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
    if (!empty($_SESSION['user_email'])) {
        // Join collection rows with parcel status from main table
        $cstmt = $conn->prepare("SELECT c.fld_parcel_ID, COALESCE(p.fld_parcel_status,'') AS fld_parcel_status, COALESCE(p.fld_parcel_amount,0) AS fld_parcel_amount, COALESCE(p.fld_parcel_date,'') AS fld_parcel_date FROM tbl_parcelcollection_ezparcel c LEFT JOIN tbl_parcel_ezparcel p ON p.fld_parcel_ID = c.fld_parcel_ID WHERE c.fld_user_email = :email ORDER BY c.fld_id DESC");
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
/* Detail pop-out overlay (centered modal-style) */
#detailOverlay { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.35); z-index:9999; }
#detailOverlay.open { display:flex; }
#parcelDetailBox { width:340px; background:#fff; border-radius:10px; padding:16px; box-shadow:0 10px 30px rgba(0,0,0,0.2); transform:translateY(8px) scale(.98); opacity:0; transition: transform 200ms ease, opacity 200ms ease; }
#detailOverlay.open #parcelDetailBox { transform:translateY(0) scale(1); opacity:1; }
#parcelDetailBox .pay-btn { padding:8px 12px;border-radius:8px;border:0;background:#1f6fff;color:#fff;cursor:pointer }
#parcelDetailBox .pay-btn.hidden { display:none }
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
        <!-- detail pop-out moved to overlay below -->
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

    <!-- Detail overlay (pop-out) -->
    <div id="detailOverlay" aria-hidden="true">
        <div id="parcelDetailBox" role="dialog" aria-modal="true">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <strong id="detailParcelID">Parcel ID</strong>
                <button id="closeDetailBtn" style="background:transparent;border:0;font-size:18px;cursor:pointer">✕</button>
            </div>
            <div style="margin-top:10px">
                 <div><strong>Parcel Price:</strong> RM <span id="detailParcelPrice">0.00</span></div>
                <div><strong>Days Late:</strong> <span id="detailLateDays">0</span> days</div>
                <div><strong>Late Fee:</strong> RM <span id="detailLateFee">0.00</span></div>
                <hr>
                <div><strong>Total Amount:</strong> RM <span id="detailTotal">0.00</span></div>
    </div>
    <div style="margin-top:12px">
                <button id="payQrBtn" class="pay-btn" type="button">Pay via Qr</button>
            </div>
        </div>
    </div>
<!-- QR Modal -->
<div id="qrOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:10000;">
    <div style="background:#fff; border-radius:10px; padding:16px; max-width:420px; text-align:center; position:relative;">
        <button id="qrCloseBtn" style="position:absolute; right:8px; top:8px; border:0; background:none; font-size:18px; cursor:pointer">✕</button>
        <img id="qrImage" src="uploads/Qr_Payment.jpg" alt="QR Payment" style="max-width:100%; height:auto; border-radius:8px;" />
        <p style="margin-top:10px; font-size:14px;">Please show proof of payment to nearby staff for parcel recival confirmation</p>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
function calculateLateInfo(dateStr){
    if(!dateStr) return { days: 0, fee: 0 };

    const arrived = new Date(dateStr);
    if (isNaN(arrived.getTime())) return { days: 0, fee: 0 };

    const today = new Date();
    const diffDays = Math.floor((today - arrived) / 86400000);

    let fee = 0;
    if (diffDays > 30) fee = 10;
    else if (diffDays > 7) fee = 3;

    return {
        days: diffDays > 0 ? diffDays : 0,
        fee: fee
    };
}
// Render parcels
// Render collections as simple two-column table: Parcel ID and Status


function renderCollections(list){
    const head = document.getElementById('collectionHead');
    const body = document.getElementById('collectionBody');
    head.innerHTML = '';
    body.innerHTML = '';

    // Header: Parcel ID | Status
    head.innerHTML = '<tr><th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Parcel ID</th><th style="text-align:left;padding:8px;border-bottom:1px solid #eee">Status</th></tr>';

    if (!list || list.length === 0) {
        // keep headers, empty body
        return;
    }

    list.forEach(r => {
        const pid = r.fld_parcel_ID ?? r.fld_tracking_number ?? '';
        const st = r.fld_parcel_status ?? r.status ?? '';
        const amt = (r.fld_parcel_amount !== undefined) ? parseFloat(r.fld_parcel_amount).toFixed(2) : '';
        const date = r.fld_parcel_date ?? r.date_received ?? '';
        const row = `<tr><td style="padding:8px;border-bottom:1px solid #f2f2f2"><a href="#" class="parcel-link" data-pid="${escapeHtml(pid)}" data-amt="${escapeHtml(amt)}" data-status="${escapeHtml(st)}" data-date="${escapeHtml(date)}">${escapeHtml(pid)}</a></td><td style="padding:8px;border-bottom:1px solid #f2f2f2">${escapeHtml(st)}</td></tr>`;
        body.innerHTML += row;
    });
}

// Show detail box for a parcel id and amount (from element)
function showParcelDetailFromEl(el){
    const id = el.dataset.pid || '';
    const amount = el.dataset.amt || '';
    const status = (el.dataset.status || '').toLowerCase();
    const date = el.dataset.date || '';
    showParcelDetail(id, amount, status, date);
}

function showParcelDetail(id, amount, status, date){
    injectParcelQR(id);
    const overlay = document.getElementById('detailOverlay');
    document.getElementById('detailParcelID').textContent = id;
    document.getElementById('detailParcelPrice').textContent = (amount !== '') ? amount : '0.00';
    const late = calculateLateInfo(date);
    document.getElementById('detailLateDays').textContent = late.days;
    document.getElementById('detailLateFee').textContent = late.fee.toFixed(2);

    const total = parseFloat(amount) + late.fee;
    document.getElementById('detailTotal').textContent = total.toFixed(2);
    const payBtn = document.getElementById('payQrBtn');
    if (status === 'collected') {
        payBtn.classList.add('hidden');
    } else {
        payBtn.classList.remove('hidden');
    }
    overlay.classList.add('open');
}


function injectParcelQR(parcelID){
    // Remove old QR if exists
    const old = document.getElementById('inlineParcelQR');
    if (old) old.remove();

    const idEl = document.getElementById('detailParcelID');
    if (!idEl) return;

    // The flex header row
    const headerRow = idEl.closest('div');
    if (!headerRow) return;

    // Create QR wrapper
    const qrWrap = document.createElement('div');
    qrWrap.id = 'inlineParcelQR';
    qrWrap.style.margin = '12px 0';
    qrWrap.style.textAlign = 'center';

    // Insert QR AFTER the header row (not inside it)
    headerRow.parentNode.insertBefore(qrWrap, headerRow.nextSibling);

    new QRCode(qrWrap, {
        text: parcelID,
        width: 120,
        height: 120,
        correctLevel: QRCode.CorrectLevel.H
    });
}


document.getElementById('closeDetailBtn').addEventListener('click', function(){
    document.getElementById('detailOverlay').classList.remove('open');
     const qr = document.getElementById('inlineParcelQR');
    if (qr) qr.remove();
});

// Close when clicking on overlay background (outside the box)
document.getElementById('detailOverlay').addEventListener('click', function(e){
    if (e.target === this) {
        this.classList.remove('open');
       const qr = document.getElementById('inlineParcelQR');
        if (qr) qr.remove();
    }
});

// Event delegation: handle clicks on parcel links inside the collection table
document.getElementById('collectionBody').addEventListener('click', function(e){
    const a = e.target.closest && e.target.closest('a.parcel-link');
    if (!a) return;
    e.preventDefault();
    showParcelDetailFromEl(a);
});

// Search
async function searchTracking(){
    const keyword = document.getElementById('trackingInput').value.trim();
    const backBtn = document.getElementById('backBtn');
    const msg = document.getElementById('statusMessage');
    msg.innerHTML = '';

    if(!keyword){ 
        renderCollections(collections); 
        backBtn.style.display = 'none';
        return; 
    }

    const keyLower = keyword.toLowerCase();
    // Exact match first
    const exact = parcels.find(p => p.fld_parcel_ID.toLowerCase() === keyLower);
    if (exact) {
        // If parcel exists, determine readiness
        const status = (exact.fld_parcel_status || '').toLowerCase();
        // If already collected, treat as failure per request
        if (status === 'collected') {
            msg.innerHTML = `<div style="padding:10px;border-radius:8px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb">No parcel found with ID <strong>${escapeHtml(keyword)}</strong>. It has not arrived yet.</div>`;
            renderCollections(collections);
            backBtn.style.display = 'inline-block';
            return;
        }

        // Not collected — add to collection table server-side
        try {
            const form = new URLSearchParams();
            form.append('parcelID', exact.fld_parcel_ID);

            const resp = await fetch('parcel_collection.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: form.toString()
            });
            const j = await resp.json();
            if (resp.ok && j.success) {
                // Update local collections: push the minimal record if not already present
                const newRow = { fld_parcel_ID: exact.fld_parcel_ID, fld_parcel_status: exact.fld_parcel_status };
                if (!collections.some(c => (c.fld_parcel_ID || '').toLowerCase() === (newRow.fld_parcel_ID || '').toLowerCase())) {
                    collections = collections.concat([newRow]);
                }
                msg.innerHTML = `<div style="padding:10px;border-radius:8px;background:#d4edda;color:#155724;border:1px solid #c3e6cb">Parcel <strong>${escapeHtml(keyword)}</strong> is ready to be collected.</div>`;
                renderCollections(collections);
                backBtn.style.display = 'inline-block';
                return;
            } else {
                msg.innerHTML = `<div style="padding:10px;border-radius:8px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb">No parcel found with ID <strong>${escapeHtml(keyword)}</strong>. It has not arrived yet.</div>`;
                renderCollections(collections);
                backBtn.style.display = 'inline-block';
                return;
            }
        } catch (e) {
            msg.innerHTML = `<div style="padding:10px;border-radius:8px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb">Unable to record parcel. Try again later.</div>`;
            renderCollections(collections);
            backBtn.style.display = 'inline-block';
            return;
        }
    }

    // No exact match — show failure
    msg.innerHTML = `<div style="padding:10px;border-radius:8px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb">No parcel found with ID <strong>${escapeHtml(keyword)}</strong>. It has not arrived yet.</div>`;
    renderCollections(collections);
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


// QR modal handlers
document.addEventListener('click', function(e){
    if(!e.target) return;
    if (e.target.id === 'payQrBtn' || e.target.classList && e.target.classList.contains('payQrBtn')) {
        const qrOverlay = document.getElementById('qrOverlay');
        if (qrOverlay) qrOverlay.style.display = 'flex';
    }
});

const qrCloseBtn = document.getElementById('qrCloseBtn');
if (qrCloseBtn) qrCloseBtn.addEventListener('click', function(){
    const qrOverlay = document.getElementById('qrOverlay');
    if (qrOverlay) qrOverlay.style.display = 'none';
});

const qrOverlayElem = document.getElementById('qrOverlay');
if (qrOverlayElem) qrOverlayElem.addEventListener('click', function(e){
    if (e.target === this) this.style.display = 'none';
});
</script>


</body>
</html>
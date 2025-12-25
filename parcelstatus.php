<?php
include_once 'auth.php';
authorize([2]);
include_once 'database.php';

try {
    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT fld_parcel_ID, fld_parcel_status FROM tbl_parcel_ezparcel");
    $stmt->execute();
    $parcels = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $collections = [];
    if (!empty($_SESSION['user_email'])) {
        $cstmt = $conn->prepare("
            SELECT 
                c.fld_parcel_ID,
                COALESCE(p.fld_parcel_status,'') AS fld_parcel_status,
                COALESCE(p.fld_parcel_amount,0) AS fld_parcel_amount,
                p.fld_parcel_date
            FROM tbl_parcelcollection_ezparcel c
            LEFT JOIN tbl_parcel_ezparcel p 
                ON p.fld_parcel_ID = c.fld_parcel_ID
            WHERE c.fld_user_email = :email
            ORDER BY c.fld_id DESC
        ");
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
.container {
    max-width:600px;
    margin:30px auto;
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}
.search-bar {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:15px;
}
.search-bar input {
    flex:1;
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
}
.search-bar button {
    padding:10px 14px;
    border-radius:8px;
    border:0;
    cursor:pointer;
    background:#1f6fff;
    color:#fff;
}
table { width:100%; border-collapse:collapse }
th,td {
    padding:8px;
    border-bottom:1px solid #eee;
    text-align:left;
}

#detailOverlay {
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.35);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
}
#detailOverlay.open { display:flex }

#parcelDetailBox {
    width:340px;
    background:#fff;
    border-radius:10px;
    padding:16px;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
}
.pay-btn {
    padding:8px 12px;
    border-radius:8px;
    border:0;
    cursor:pointer;
    color:#fff;
    background:#1f6fff;
}
.hidden { display:none }
</style>
</head>

<body>
<?php include 'navbar.php'; ?>

<div class="container">
<h2>Parcel Status</h2>

<div class="search-bar">
    <input id="trackingInput" placeholder="Enter tracking number">
    <button onclick="searchTracking()"><i class="fa fa-search"></i></button>
</div>

<table>
<thead>
<tr>
    <th>Parcel ID</th>
    <th>Status</th>
</tr>
</thead>
<tbody id="collectionBody"></tbody>
</table>
</div>

<!-- Detail Modal -->
<div id="detailOverlay">
<div id="parcelDetailBox">
<div style="display:flex;justify-content:space-between">
    <strong id="detailParcelID"></strong>
    <button onclick="closeDetail()" style="border:0;background:none;font-size:18px">✕</button>
</div>

<div style="margin-top:10px">
    <div><strong>Parcel Price:</strong> RM <span id="detailParcelPrice">0.00</span></div>
    <div><strong>Days Late:</strong> <span id="detailLateDays">0</span> days</div>
    <div><strong>Late Fee:</strong> RM <span id="detailLateFee">0.00</span></div>
    <hr>
    <div><strong>Total Amount:</strong> RM <span id="detailTotal">0.00</span></div>
</div>

<div style="margin-top:12px">
    <button id="payQrBtn" class="pay-btn">Pay via QR</button>
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
<script>
let parcels = <?= json_encode($parcels) ?>;
let collections = <?= json_encode($collections) ?>;

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

function renderCollections(list){
    const body = document.getElementById('collectionBody');
    body.innerHTML = '';

    list.forEach(r => {
        body.innerHTML += `
        <tr>
            <td>
                <a href="#" onclick="showDetail(
                    '${r.fld_parcel_ID}',
                    '${r.fld_parcel_amount}',
                    '${r.fld_parcel_status}',
                    '${r.fld_parcel_date}'
                )">${r.fld_parcel_ID}</a>
            </td>
            <td>${r.fld_parcel_status}</td>
        </tr>`;
    });
}

function showDetail(id, amount, status, date){
    document.getElementById('detailParcelID').textContent = id;

    const price = parseFloat(amount);
    document.getElementById('detailParcelPrice').textContent = price.toFixed(2);

    const late = calculateLateInfo(date);
    document.getElementById('detailLateDays').textContent = late.days;
    document.getElementById('detailLateFee').textContent = late.fee.toFixed(2);

    const total = price + late.fee;
    document.getElementById('detailTotal').textContent = total.toFixed(2);

    document.getElementById('payQrBtn').classList.toggle(
        'hidden',
        status.toLowerCase() === 'collected'
    );

    document.getElementById('detailOverlay').classList.add('open');
}

function closeDetail(){
    document.getElementById('detailOverlay').classList.remove('open');
}

function searchTracking(){
    const key = document.getElementById('trackingInput').value.trim().toLowerCase();
    if(!key){
        renderCollections(collections);
        return;
    }

    const found = parcels.find(p => p.fld_parcel_ID.toLowerCase() === key);
    document.getElementById('collectionBody').innerHTML = found
        ? `<tr><td colspan="2" style="color:green">Parcel exists in system</td></tr>`
        : `<tr><td colspan="2" style="color:red">Parcel not found</td></tr>`;
}

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

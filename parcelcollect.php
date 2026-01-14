<?php
include_once 'auth.php';
authorize([1]);
include_once 'database.php';

try {
    // Connect
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read all parcels
    $stmt = $conn->prepare("
        SELECT 
            fld_parcel_ID,
            fld_parcel_status,
            fld_parcel_storage,
            fld_parcel_date,
            fld_parcel_amount,
            fld_parcel_weight,
            fld_parcel_location,
            fld_user_name,
            fld_parcel_pic
        FROM tbl_parcel_ezparcel
        ORDER BY fld_parcel_date DESC
    ");
    $stmt->execute();
    $parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>Parcel Collection</title>
    <link rel="stylesheet" href="css/stylecollect.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link rel="icon" href="images/logo.png" type="image/png">
</head>
<body>

<?php include 'navbar.php'; ?>

<h2 style="text-align:center;">Parcel Collection</h2>

<div class="search-wrapper">
        <form class="search-form" onsubmit="searchParcel(event);" role="search">
            <input id="orderID" class="search-input" type="search" placeholder="Search Order ID..." autofocus required />
            <button type="button" class="qr-btn" onclick="startScanner()" title="Scan QR Code">📷</button>
            <button type="submit" class="search-btn">Go</button>  
        </form>
</div>

<div id="reader-container" style="display:none; max-width: 500px; margin: 0 auto 20px auto;">
    <div id="reader"></div>
    <button onclick="stopScanner()" style="width:100%; padding:10px; background:#ef4444; color:white; border:none; border-radius:8px; margin-top:10px;">Close Scanner</button>
</div>

<div id="result"></div>
<script>
let parcels = <?php echo json_encode($parcels); ?>;

function searchParcel(e) {
    e.preventDefault();
    const id = document.getElementById("orderID").value.trim();
    
    // 1. Cari parcel berdasarkan ID
    const p = parcels.find(x => x.fld_parcel_ID == id);
    const box = document.getElementById("result");

    // 2. Jika ID tidak wujud langsung dalam database
    if (!p) {
        box.innerHTML = `
            <div style="text-align:center; padding: 50px; color: #64748b; background: white; border-radius: 15px; margin: 20px auto; max-width: 500px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <p>🔍 Order ID <b>#${id}</b> tidak dijumpai.</p>
            </div>`;
        return;
    }

    // // 3. If parcel status is already 'Collected'
    if (p.fld_parcel_status === 'Collected') {
        box.innerHTML = `
            <div style="text-align:center; padding: 50px; color: #e11d48; background: #fff1f2; border: 1px solid #fda4af; border-radius: 15px; margin: 20px auto; max-width: 500px;">
                <h3 style="margin-top:0;">Already Collected</h3>
                <p>Parcel ID <b>#${id}</b> was successfully picked up by the recipient.</p>
            </div>`;
        return;
    }

    // 4. If status is 'Uncollected', display the details card
    box.innerHTML = `
    <div class="modern-card">
        <div class="card-header">
            <div>
                <span class="slot-code">${p.fld_parcel_storage}${p.fld_parcel_location}</span>
                <small>Storage Slot</small>
            </div>
            <div class="price-tag">RM${parseFloat(p.fld_parcel_amount).toFixed(2)}</div>
        </div>

        <div class="card-content">
            ${p.fld_parcel_pic 
                ? `<img src="${p.fld_parcel_pic}" class="parcel-img-preview">` 
                : `<div class="no-img">No Image Available</div>`
            }
            
            <div class="info-group">
                <label>Recipient Name</label>
                <p>${p.fld_user_name}</p>
            </div>

            <div class="info-group">
                <label>Order ID</label>
                <p>#${p.fld_parcel_ID}</p>
            </div>

            <div class="info-grid">
                <div class="info-group">
                    <label>Weight</label>
                    <p>${p.fld_parcel_weight} kg</p>
                </div>
                <div class="info-group">
                    <label>Date Arrived</label>
                    <p>${new Date(p.fld_parcel_date).toLocaleDateString('en-GB')}</p>
                </div>
            </div>
        </div>

        <button class="confirm-btn" onclick="markPaid('${p.fld_parcel_ID}','${p.fld_parcel_amount}','${p.fld_parcel_date}')">
            CONFIRM COLLECTION & PAID
        </button>
    </div>
    `;
}


function computeLateFee(dateStr){
    if(!dateStr) return 0;
    const arrived = new Date(dateStr);
    if (isNaN(arrived.getTime())) return 0;
    const today = new Date();
    const diffDays = Math.floor((today - arrived) / 86400000);
    if (diffDays > 30) return 10;
    if (diffDays > 7) return 3;
    return 0;
}

async function markPaid(orderid, amount, dateStr) {
    const ok = confirm("Confirm parcel collected?");
    if (!ok) return;

    const fee = computeLateFee(dateStr);
    const total = (parseFloat(amount) || 0) + fee;

    const form = new URLSearchParams();
    form.append('action', 'update');
    form.append('parcelID', orderid);
    form.append('status', 'Collected');
    form.append('total', total.toFixed(2));
    // client no longer sends user email; server records total by parcel ID

    const res = await fetch('parcel_CRUD.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: form.toString()
    });

    const data = await res.json();

    if (data.success) {
        alert("Parcel collected! Total: RM" + total.toFixed(2));
        location.reload();
    } else {
        alert("Failed!");
    }
}
let html5QrCode;

async function startScanner() {
    // Tunjukkan container kamera
    document.getElementById('reader-container').style.display = 'block';
    
    // Scroll ke arah kamera supaya user nampak
    document.getElementById('reader-container').scrollIntoView({ behavior: 'smooth' });

    html5QrCode = new Html5Qrcode("reader");
    
    const config = { 
        fps: 10, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0 
    };

    try {
        await html5QrCode.start(
            { facingMode: "environment" }, // Guna kamera belakang telefon
            config,
            (decodedText) => {
                // APA JADI BILA SCAN BERJAYA:
                document.getElementById('orderID').value = decodedText; // Masukkan ID dlm kotak search
                stopScanner(); // Tutup kamera
                
                // Automatik tekan butang 'Go'
                const event = new Event('submit', { 'cancelable': true });
                searchParcel(event); 
            },
            (errorMessage) => {
                // Sedang mencari QR... (biarkan kosong)
            }
        );
    } catch (err) {
        console.error("Unable to start scanning", err);
        alert("Error: Camera permission denied or device not found.");
    }
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            document.getElementById('reader-container').style.display = 'none';
        }).catch((err) => {
            console.error("Failed to stop scanner", err);
            document.getElementById('reader-container').style.display = 'none';
        });
    }
}
</script>


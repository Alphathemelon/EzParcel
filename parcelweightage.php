<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EZParcel – Scanner + Storage</title>

    <link rel="stylesheet" href="css/parcelweightage.css">
</head>

<body>
<?php include 'navbar.php'; ?>

<div class="container" id="scannerPage">
    <h2>Parcel Scanner</h2>
    <p id="currentDate" style="color:#555; margin-bottom: 15px;"></p>
    <div id="messageBox" aria-live="polite" style="margin-bottom:10px;"></div>

    <video id="camera" autoplay></video>
    <canvas id="qr-canvas" style="display:none;"></canvas>

    <label>Parcel QR Code</label>
    <input type="text" id="parcelID" readonly>

    <label>Phone Number</label>
    <input type="text" id="phoneNumber" placeholder="Enter phone number">

    <label>Parcel Weight (KG)</label>
    <input type="number" id="parcelWeight" placeholder="Enter weight">

    <button onclick="submitParcel()">Submit Parcel Info</button>
</div>

<!-- ============================ STORAGE PAGE ============================ -->
<div class="container hidden" id="storagePage">
    <p id="currentDateStorage" style="color:#555; margin-bottom: 15px;"></p>
    <div class="storage-header" id="shelfTitle">Storage Location</div>
    <div id="gridContainer"></div>
    <button id="confirmBtn" onclick="confirmLocation()" disabled>Confirm Location</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>

<script>
/* ===== DATE ===== */
function updateDate() {
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = now.toLocaleDateString('en-GB', options);
    document.getElementById("currentDate").innerText = formattedDate;
    document.getElementById("currentDateStorage").innerText = formattedDate;
}
updateDate();

/* ===== QR SCANNER ===== */
const video = document.getElementById("camera");
const canvas = document.getElementById("qr-canvas");
const ctx = canvas.getContext("2d");

let scanning = false;
let stream;

function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
        .then(s => {
            stream = s;
            video.srcObject = s;
            scanning = true;
            scanLoop();
        })
        .catch(err => console.error("Camera error:", err));
}

startCamera();

function scanLoop() {
    if (!scanning) return;

    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, canvas.width, canvas.height);

        if (code) {
            document.getElementById("parcelID").value = code.data;
            stopCamera();
        }
    }

    requestAnimationFrame(scanLoop);
}

function stopCamera() {
    scanning = false;
    if (stream) stream.getTracks().forEach(t => t.stop());
}

/* ===== SUBMIT PARCEL ===== */
function submitParcel() {
    let parcel = parcelID.value.trim();
    let phone = phoneNumber.value.trim();
    let weight = parseFloat(parcelWeight.value);

    if (!parcel || !phone || !weight) {
        showMessage('error', "Please scan QR, enter phone & weight.");
        return;
    }

    // Determine storage code (S, M, L) and a human-friendly shelf title
    let shelfLabel = "";
    let shelfCode = "";
    if (weight <= 2) { shelfCode = 'S'; shelfLabel = "SMALL – SHELF B"; }
    else if (weight <= 5) { shelfCode = 'M'; shelfLabel = "MEDIUM – SHELF C"; }
    else { shelfCode = 'L'; shelfLabel = "LARGE – SHELF D"; }

    // expose code for confirmLocation to use
    window.currentShelfCode = shelfCode;

    shelfTitle.innerText = shelfLabel;

    scannerPage.classList.add("hidden");
    storagePage.classList.remove("hidden");

    generateSlots();
}

/* ===== GENERATE SLOTS ===== */
async function generateSlots() {
    const container = document.getElementById("gridContainer");
    container.innerHTML = "";

    // Fetch current occupied slots for this storage code
    const shelfCode = window.currentShelfCode || '';

    // Default: no occupied slots until we fetch
    let occupied = new Set();

    try {
        const res = await fetch('parcel_CRUD.php?action=list');
        const data = await res.json();
        if (data && data.success && Array.isArray(data.data)) {
            data.data.forEach(r => {
                // match storage code and uncollected status
                const code = (r.fld_parcel_storage || '').toString().toUpperCase();
                const status = (r.fld_parcel_status || '').toString();
                const loc = (r.fld_parcel_location || '').toString().padStart(2, '0');
                if (code === shelfCode && status === 'Uncollected' && loc) {
                    occupied.add(loc);
                }
            });
        }
    } catch (e) {
        console.error('Could not fetch occupied slots', e);
    }

    for (let i = 1; i <= 24; i++) {
        const num = i.toString().padStart(2, "0");
        const div = document.createElement("div");

        // If occupied, mark disabled
        if (occupied.has(num)) {
            div.className = "slot disabled";
            div.textContent = num;
            div.setAttribute('aria-disabled', 'true');
        } else {
            div.className = "slot available";
            div.textContent = num;
            div.onclick = () => {
                document.querySelectorAll(".slot.selected").forEach(el => el.classList.remove("selected"));
                div.classList.add("selected");
                updateConfirmButton();
            };
        }

        container.appendChild(div);
    }

    updateConfirmButton();
}

function updateConfirmButton() {
    const selected = document.querySelector(".slot.selected");
    confirmBtn.disabled = !selected;
}

function confirmLocation() {
    const selectedEl = document.querySelector(".slot.selected");
    if (!selectedEl) {
        showMessage('error', 'No slot selected');
        return;
    }

    const selected = selectedEl.textContent;

    // gather values from the form
    const parcel = document.getElementById('parcelID').value.trim();
    const phone = document.getElementById('phoneNumber').value.trim();
    const weight = document.getElementById('parcelWeight').value;
    // Use the short shelf code for storage (S/M/L); fall back to shelfTitle text if missing
    const storage = window.currentShelfCode || document.getElementById('shelfTitle').innerText || '';
    const location = selected;

    if (!parcel || !phone || !weight) {
        showMessage('error', 'Missing parcel data. Please scan QR, enter phone and weight.');
        return;
    }

    // Disable confirm button while sending
    const btn = document.getElementById('confirmBtn');
    btn.disabled = true;

    // Prepare form data
    const payload = new URLSearchParams();
    payload.append('action', 'create');
    payload.append('parcelID', parcel);
    payload.append('phoneNumber', phone);
    payload.append('parcelWeight', weight);
    payload.append('storage', storage);
    payload.append('location', location);

    fetch('parcel_CRUD.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.toString()
    })
    .then(res => res.json())
    .then(json => {
        if (json && json.success) {
            const statusText = json.status || 'Uncollected';
            const fee = (json.amount !== undefined && json.amount !== null) ? parseFloat(json.amount).toFixed(2) : '0.00';
            showMessage('success', `Parcel saved — Status: ${statusText} • Fee: RM${fee}`);
        } else {
            // server may return error message (e.g., duplicate)
            const msg = json && (json.error || json.message) ? (json.error || json.message) : 'Unknown error';
            showMessage('error', 'Save failed: ' + msg);
        }

        // Reset UI and start camera again
        document.getElementById('storagePage').classList.add('hidden');
        document.getElementById('scannerPage').classList.remove('hidden');

        document.getElementById('parcelID').value = '';
        document.getElementById('phoneNumber').value = '';
        document.getElementById('parcelWeight').value = '';
        document.querySelectorAll('.slot.selected').forEach(el => el.classList.remove('selected'));

        startCamera();
    })
    .catch(err => {
        console.error('Network error:', err);
        showMessage('error', 'Network error while saving parcel');
    })
    .finally(() => { btn.disabled = false; });
}

// Inline message helper
function showMessage(type, text, timeout = 5000) {
    const box = document.getElementById('messageBox');
    if (!box) return;
    box.innerHTML = '';
    const div = document.createElement('div');
    div.className = 'msg ' + (type === 'success' ? 'success' : type === 'error' ? 'error' : 'info');
    div.textContent = text;
    div.style.padding = '10px';
    div.style.borderRadius = '8px';
    div.style.fontWeight = '600';
    div.style.textAlign = 'center';
    if (type === 'success') {
        div.style.background = '#d4edda';
        div.style.color = '#155724';
        div.style.border = '1px solid #c3e6cb';
    } else if (type === 'error') {
        div.style.background = '#f8d7da';
        div.style.color = '#721c24';
        div.style.border = '1px solid #f5c6cb';
    } else {
        div.style.background = '#d1ecf1';
        div.style.color = '#0c5460';
        div.style.border = '1px solid #bee5eb';
    }
    box.appendChild(div);
    if (timeout > 0) {
        setTimeout(() => { if (box.contains(div)) box.removeChild(div); }, timeout);
    }
}
</script>

</body>
</html>

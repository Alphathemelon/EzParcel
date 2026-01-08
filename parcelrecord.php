<?php
include_once 'auth.php';
authorize([1]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZParcel – Scanner + Storage</title>
    <link rel="stylesheet" href="css/parcelweightage.css">
    <link rel="icon" href="images/logo.png" type="image/png">
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

    <label>Name</label>
    <input type="text" id="userName" placeholder="Enter name">

    <label>Parcel Weight (KG)</label>
    <input type="number" id="parcelWeight" placeholder="Enter weight">

    <button onclick="submitParcel()">Submit Parcel Info</button>
</div>

<!-- ============================ STORAGE PAGE ============================ -->
<div class="container hidden" id="storagePage">
    <p id="currentDateStorage" style="color:#555; margin-bottom: 15px;"></p>
    <div class="storage-header" id="shelfTitle">Storage Location</div>
    <div id="gridContainer"></div>
    <label>Parcel Photo</label>
    <video id="photoCam" autoplay style="width:100%;max-width:280px;border-radius:10px;"></video>
    <canvas id="photoCanvas" hidden></canvas>

    <button onclick="capturePhoto()">Capture Photo</button>
    <img id="photoPreview" style="display:none;width:100%;max-width:280px;margin-top:10px;border-radius:10px;">
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

startCamera();

/* ===== SUBMIT PARCEL ===== */
function submitParcel() {
    let parcel = document.getElementById('parcelID').value.trim();
    let name = document.getElementById('userName').value.trim();
    let weight = parseFloat(document.getElementById('parcelWeight').value);

    if (!parcel || !name || !weight) {
        showMessage('error', "Please scan QR, enter name & weight.");
        return;
    }

    let shelfLabel = "";
    let shelfCode = "";
    if (weight <= 2) { shelfCode = 'S'; shelfLabel = "SMALL – SHELF B"; }
    else if (weight <= 5) { shelfCode = 'M'; shelfLabel = "MEDIUM – SHELF C"; }
    else { shelfCode = 'L'; shelfLabel = "LARGE – SHELF D"; }

    window.currentShelfCode = shelfCode;
    document.getElementById('shelfTitle').innerText = shelfLabel;

    document.getElementById('scannerPage').classList.add("hidden");
    document.getElementById('storagePage').classList.remove("hidden");

    generateSlots();
    startPhotoCam();
}

/* ===== GENERATE SLOTS ===== */
async function generateSlots() {
    const container = document.getElementById("gridContainer");
    container.innerHTML = "";

    const shelfCode = window.currentShelfCode || '';
    let occupied = new Set();

    try {
        const res = await fetch('parcel_CRUD.php?action=list');
        const data = await res.json();
        if (data && data.success && Array.isArray(data.data)) {
            data.data.forEach(r => {
                const code = (r.fld_parcel_storage || '').toUpperCase();
                const status = (r.fld_parcel_status || '').toString();
                const loc = (r.fld_parcel_location || '').toString().padStart(2,'0');
                if (code === shelfCode && status === 'Uncollected' && loc) {
                    occupied.add(loc);
                }
            });
        }
    } catch (e) {
        console.error('Could not fetch occupied slots', e);
    }

    for (let i = 1; i <= 24; i++) {
        const num = i.toString().padStart(2,"0");
        const div = document.createElement("div");

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

/* ===== PHOTO ===== */
let photoStream, photoBlob = null;
const photoCam = document.getElementById('photoCam');
const photoCanvas = document.getElementById('photoCanvas');
const photoPreview = document.getElementById('photoPreview');

function startPhotoCam(){
    navigator.mediaDevices.getUserMedia({video:true})
    .then(s=>{photoStream=s;photoCam.srcObject=s;});
}

function capturePhoto(){
    photoCanvas.width=photoCam.videoWidth;
    photoCanvas.height=photoCam.videoHeight;
    photoCanvas.getContext('2d').drawImage(photoCam,0,0);
    photoCanvas.toBlob(b=>{
        photoBlob=b;
        photoPreview.src=URL.createObjectURL(b);
        photoPreview.style.display='block';
    },'image/jpeg');
    photoStream.getTracks().forEach(t=>t.stop());
}
function updateConfirmButton() {
    const selected = document.querySelector(".slot.selected");
    document.getElementById('confirmBtn').disabled = !selected;
}

function confirmLocation() {
    const selectedEl = document.querySelector(".slot.selected");
    if (!selectedEl) {
        showMessage('error','No slot selected');
        return;
    }

    if (!photoBlob) {
        showMessage('error','Please capture parcel photo first');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('parcelID', document.getElementById('parcelID').value.trim());
    formData.append('userName', document.getElementById('userName').value.trim());
    formData.append('parcelWeight', document.getElementById('parcelWeight').value);
    formData.append('storage', window.currentShelfCode);
    formData.append('location', selectedEl.textContent);
    formData.append('parcel_pic', photoBlob, 'parcel.jpg'); // 📸

    fetch('parcel_CRUD.php?action=create', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(json => {
        if (json.success) {
            showMessage('success', 'Parcel saved with photo');
        } else {
            showMessage('error', json.error || 'Save failed');
        }

        document.getElementById('storagePage').classList.add('hidden');
        document.getElementById('scannerPage').classList.remove('hidden');
        startCamera();
    })
    .catch(err => {
        console.error(err);
        showMessage('error','Network error');
    });
}


function showMessage(type, text, timeout=5000){
    const box = document.getElementById('messageBox');
    if(!box) return;
    box.innerHTML = '';
    const div = document.createElement('div');
    div.className = 'msg ' + (type==='success'?'success':type==='error'?'error':'info');
    div.textContent = text;
    div.style.padding='10px';
    div.style.borderRadius='8px';
    div.style.fontWeight='600';
    div.style.textAlign='center';
    if(type==='success'){ div.style.background='#d4edda'; div.style.color='#155724'; div.style.border='1px solid #c3e6cb'; }
    else if(type==='error'){ div.style.background='#f8d7da'; div.style.color='#721c24'; div.style.border='1px solid #f5c6cb'; }
    else{ div.style.background='#d1ecf1'; div.style.color='#0c5460'; div.style.border='1px solid #bee5eb'; }
    box.appendChild(div);
    if(timeout>0){ setTimeout(()=>{ if(box.contains(div)) box.removeChild(div); },timeout);}
}
</script>

</body>
</html>